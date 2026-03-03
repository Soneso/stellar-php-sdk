<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Util;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Soneso\StellarSDK\Util\CustomFriendBot;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDK\Util\FuturenetFriendBot;

class FriendBotTest extends TestCase
{
    private const TEST_ACCOUNT_ID = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';

    private function createMockClient(array $responses, ?array &$history = null, bool $httpErrors = true): Client
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        if ($history !== null) {
            $stack->push(Middleware::history($history));
        }
        return new Client(['handler' => $stack, 'http_errors' => $httpErrors]);
    }

    #[Test]
    public function friendBotFundSucceeds(): void
    {
        $client = $this->createMockClient([new Response(200)]);
        $result = FriendBot::fundTestAccount(self::TEST_ACCOUNT_ID, $client);
        $this->assertTrue($result);
    }

    #[Test]
    public function friendBotFundReturnsFalseOnNon200(): void
    {
        $client = $this->createMockClient([new Response(400)], httpErrors: false);
        $result = FriendBot::fundTestAccount(self::TEST_ACCOUNT_ID, $client);
        $this->assertFalse($result);
    }

    #[Test]
    public function friendBotThrowsOnHttpError(): void
    {
        $client = $this->createMockClient([
            new RequestException('Connection failed', new Request('GET', 'test'))
        ]);
        $this->expectException(RequestException::class);
        FriendBot::fundTestAccount(self::TEST_ACCOUNT_ID, $client);
    }

    #[Test]
    public function friendBotUrlEncodesAccountId(): void
    {
        $history = [];
        $client = $this->createMockClient([new Response(200)], $history);
        FriendBot::fundTestAccount('G+special/chars', $client);

        $this->assertCount(1, $history);
        $url = (string)$history[0]['request']->getUri();
        $this->assertStringContainsString('addr=G%2Bspecial%2Fchars', $url);
    }

    #[Test]
    public function futurenetFriendBotFundSucceeds(): void
    {
        $client = $this->createMockClient([new Response(200)]);
        $result = FuturenetFriendBot::fundTestAccount(self::TEST_ACCOUNT_ID, $client);
        $this->assertTrue($result);
    }

    #[Test]
    public function futurenetFriendBotFundReturnsFalseOnNon200(): void
    {
        $client = $this->createMockClient([new Response(400)], httpErrors: false);
        $result = FuturenetFriendBot::fundTestAccount(self::TEST_ACCOUNT_ID, $client);
        $this->assertFalse($result);
    }

    #[Test]
    public function futurenetFriendBotThrowsOnHttpError(): void
    {
        $client = $this->createMockClient([
            new RequestException('Connection failed', new Request('GET', 'test'))
        ]);
        $this->expectException(RequestException::class);
        FuturenetFriendBot::fundTestAccount(self::TEST_ACCOUNT_ID, $client);
    }

    #[Test]
    public function customFriendBotFundSucceeds(): void
    {
        $client = $this->createMockClient([new Response(200)]);
        $bot = new CustomFriendBot('http://localhost:8000/friendbot');
        $result = $bot->fundAccount(self::TEST_ACCOUNT_ID, $client);
        $this->assertTrue($result);
    }

    #[Test]
    public function customFriendBotFundReturnsFalseOnNon200(): void
    {
        $client = $this->createMockClient([new Response(400)], httpErrors: false);
        $bot = new CustomFriendBot('http://localhost:8000/friendbot');
        $result = $bot->fundAccount(self::TEST_ACCOUNT_ID, $client);
        $this->assertFalse($result);
    }

    #[Test]
    public function customFriendBotThrowsOnHttpError(): void
    {
        $client = $this->createMockClient([
            new RequestException('Connection failed', new Request('GET', 'test'))
        ]);
        $bot = new CustomFriendBot('http://localhost:8000/friendbot');
        $this->expectException(RequestException::class);
        $bot->fundAccount(self::TEST_ACCOUNT_ID, $client);
    }

    #[Test]
    public function customFriendBotUrlEncodesAccountId(): void
    {
        $history = [];
        $client = $this->createMockClient([new Response(200)], $history);
        $bot = new CustomFriendBot('http://localhost:8000/friendbot');
        $bot->fundAccount('G+special/chars', $client);

        $this->assertCount(1, $history);
        $url = (string)$history[0]['request']->getUri();
        $this->assertStringContainsString('addr=G%2Bspecial%2Fchars', $url);
    }
}
