<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as HttpResponse;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\Responses\Response;

/**
 * Tests the SSE line buffering / event extraction used by RequestBuilder::getAndStream.
 */
class RequestBuilderStreamTest extends TestCase
{
    /**
     * Invokes the private static RequestBuilder::consumeStreamEvents with a by-reference buffer.
     *
     * @return array{events: array<int, mixed>, stop: bool}
     */
    private function consume(string &$buffer): array
    {
        $method = new ReflectionMethod(RequestBuilder::class, 'consumeStreamEvents');
        $method->setAccessible(true);
        $args = array(&$buffer);
        return $method->invokeArgs(null, $args);
    }

    public function testParsesMultipleDataLinesInOneChunk(): void
    {
        $buffer = 'data: {"id":"1"}' . "\n" . 'data: {"id":"2"}' . "\n";
        $result = $this->consume($buffer);

        $this->assertFalse($result['stop']);
        $this->assertCount(2, $result['events']);
        $this->assertEquals('1', $result['events'][0]['id']);
        $this->assertEquals('2', $result['events'][1]['id']);
        $this->assertSame('', $buffer);
    }

    public function testRetainsPartialTrailingLine(): void
    {
        $buffer = 'data: {"id":"1"}' . "\n" . 'data: {"id":"2"';
        $result = $this->consume($buffer);

        $this->assertCount(1, $result['events']);
        $this->assertEquals('1', $result['events'][0]['id']);
        // The incomplete line (no trailing newline) is kept for the next read.
        $this->assertSame('data: {"id":"2"', $buffer);
    }

    public function testLineSplitAcrossChunks(): void
    {
        $buffer = 'data: {"id":"1"';
        $result = $this->consume($buffer);
        $this->assertCount(0, $result['events']);
        $this->assertSame('data: {"id":"1"', $buffer);

        // The next chunk completes the first line and adds a second.
        $buffer .= '}' . "\n" . 'data: {"id":"2"}' . "\n";
        $result = $this->consume($buffer);
        $this->assertCount(2, $result['events']);
        $this->assertEquals('1', $result['events'][0]['id']);
        $this->assertEquals('2', $result['events'][1]['id']);
        $this->assertSame('', $buffer);
    }

    public function testIgnoresEmptyHelloAndNonDataLines(): void
    {
        $buffer = "\n"
            . 'data: "hello"' . "\n"
            . ': keep-alive comment' . "\n"
            . 'event: message' . "\n"
            . 'data: {"id":"1"}' . "\n";
        $result = $this->consume($buffer);

        $this->assertFalse($result['stop']);
        $this->assertCount(1, $result['events']);
        $this->assertEquals('1', $result['events'][0]['id']);
        $this->assertSame('', $buffer);
    }

    public function testByebyeStopsAndSignalsReconnect(): void
    {
        $buffer = 'data: {"id":"1"}' . "\n"
            . 'data: "byebye"' . "\n"
            . 'data: {"id":"2"}' . "\n";
        $result = $this->consume($buffer);

        $this->assertTrue($result['stop']);
        // Events before the byebye line are returned; parsing stops at byebye.
        $this->assertCount(1, $result['events']);
        $this->assertEquals('1', $result['events'][0]['id']);
        // Lines after byebye remain unconsumed in the buffer.
        $this->assertSame('data: {"id":"2"}' . "\n", $buffer);
    }

    public function testIgnoresUndecodableData(): void
    {
        $buffer = 'data: not-json' . "\n" . 'data: {"id":"1"}' . "\n";
        $result = $this->consume($buffer);

        $this->assertCount(1, $result['events']);
        $this->assertEquals('1', $result['events'][0]['id']);
        $this->assertSame('', $buffer);
    }

    public function testEmptyBufferYieldsNoEvents(): void
    {
        $buffer = '';
        $result = $this->consume($buffer);

        $this->assertFalse($result['stop']);
        $this->assertCount(0, $result['events']);
        $this->assertSame('', $buffer);
    }

    public function testByebyeWithNoTrailingLinesLeavesEmptyBuffer(): void
    {
        $buffer = 'data: {"id":"1"}' . "\n" . 'data: "byebye"' . "\n";
        $result = $this->consume($buffer);

        $this->assertTrue($result['stop']);
        $this->assertCount(1, $result['events']);
        $this->assertSame('', $buffer);
    }

    public function testCarriageReturnLineEndingsStillDecode(): void
    {
        // Lines are split on "\n" only; a trailing "\r" is tolerated by json_decode.
        $buffer = 'data: {"id":"1"}' . "\r\n" . 'data: {"id":"2"}' . "\r\n";
        $result = $this->consume($buffer);

        $this->assertFalse($result['stop']);
        $this->assertCount(2, $result['events']);
        $this->assertEquals('1', $result['events'][0]['id']);
        $this->assertEquals('2', $result['events'][1]['id']);
        $this->assertSame('', $buffer);
    }

    /**
     * Returns a concrete RequestBuilder backed by a mocked HTTP client that
     * replies with the given streamed response bodies.
     */
    private function builderWithStreamedResponses(string ...$bodies): RequestBuilder
    {
        $responses = array_map(static fn (string $b) => new HttpResponse(200, [], $b), $bodies);
        $client = new Client(['handler' => HandlerStack::create(new MockHandler($responses))]);
        return new class($client) extends RequestBuilder {
            public function execute(): Response
            {
                throw new \RuntimeException('not used in this test');
            }
        };
    }

    public function testGetAndStreamDispatchesEventsInOrderAndSkipsHandshake(): void
    {
        $sse = 'data: "hello"' . "\n"
            . 'data: {"id":"1"}' . "\n"
            . 'data: {"id":"2"}' . "\n";
        $builder = $this->builderWithStreamedResponses($sse);

        $received = [];
        try {
            // getAndStream loops forever, reconnecting when a stream ends. After the
            // single mocked response is consumed the reconnect hits an empty mock
            // queue, which throws and ends the otherwise-infinite loop.
            $builder->getAndStream('/ledgers', function ($event) use (&$received) {
                $received[] = $event;
            });
            $this->fail('expected the exhausted mock queue to end the stream loop');
        } catch (\Throwable $e) {
            // expected once the mock queue is empty on reconnect
        }

        $this->assertCount(2, $received);
        $this->assertEquals('1', $received[0]['id']);
        $this->assertEquals('2', $received[1]['id']);
    }

    public function testGetAndStreamStopsDispatchingAtByebye(): void
    {
        $sse = 'data: {"id":"1"}' . "\n"
            . 'data: "byebye"' . "\n"
            . 'data: {"id":"2"}' . "\n";
        $builder = $this->builderWithStreamedResponses($sse);

        $received = [];
        try {
            $builder->getAndStream('/ledgers', function ($event) use (&$received) {
                $received[] = $event;
            });
            $this->fail('expected the exhausted mock queue to end the stream loop');
        } catch (\Throwable $e) {
            // expected: after byebye triggers a reconnect, the mock queue is empty
        }

        // Only the event before "byebye" is dispatched; the line after it is not.
        $this->assertCount(1, $received);
        $this->assertEquals('1', $received[0]['id']);
    }
}
