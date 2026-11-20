<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\{XdrBuffer, XdrJsonHelper, XdrLedgerCloseMetaBatch, XdrPersistedSCPState, XdrSCSpecEntry,
    XdrSCVal};

/**
 * XDR-JSON of the deepest values the binary decoder accepts, and the nesting limit of the writer.
 */
class XdrJsonDepthTest extends TestCase
{
    private string|false $nestingLimit;

    protected function setUp(): void
    {
        // The debugger's call-stack limit must allow the JSON conversion of these values.
        $this->nestingLimit = ini_set('xdebug.max_nesting_level', '20000');
    }

    protected function tearDown(): void
    {
        if ($this->nestingLimit !== false) {
            ini_set('xdebug.max_nesting_level', $this->nestingLimit);
        }
    }

    private static function n(int ...$words): string
    {
        return pack('N*', ...$words);
    }

    private static function zeros(int $count): string
    {
        return str_repeat("\0", $count);
    }

    /** A contract address. */
    private static function contract(): string
    {
        return self::n(1) . self::zeros(32);
    }

    /** $levels SCVal vectors, each holding the next, the innermost empty. */
    private static function vectors(int $levels): string
    {
        return str_repeat(self::n(16, 1, 1), $levels - 1) . self::n(16, 1, 0);
    }

    /** A contract function call with no name and no arguments, as an authorized function. */
    private static function contractFn(): string
    {
        return self::n(0) . self::contract() . self::n(0, 0);
    }

    /** One operation in a fee-bump envelope, the only transaction of a stored transaction set. */
    private static function persistedState(string $operation): string
    {
        $account = self::n(0) . self::zeros(32);
        // source account, fee, sequence number, no preconditions, no memo, one operation, ext v0
        $transaction = $account . self::n(100) . self::zeros(8) . self::n(0, 0, 1) . $operation . self::n(0);
        // fee source, fee, inner v1 envelope, its signatures, ext v0, signatures
        $envelope = self::n(5) . $account . self::zeros(8) . self::n(2) . $transaction . self::n(0, 0, 0);
        // v0: no envelopes, no quorum sets, one generalized v1 transaction set, previous ledger hash,
        // one phase v0 with one component, no base fee, one envelope
        return self::n(0, 0, 0, 1, 1, 1) . self::zeros(32) . self::n(1, 0, 1, 0, 0, 1) . $envelope;
    }

    /** An invoke-contract operation with one authorization entry. */
    private static function authorization(string $credentials, string $rootInvocation): string
    {
        // no source account, INVOKE_HOST_FUNCTION, invoke contract, no name, no arguments, one entry
        return self::n(0, 24, 0) . self::contract() . self::n(0, 0, 1) . $credentials . $rootInvocation;
    }

    /** Address credentials with delegates: address, nonce, expiration ledger, signature, delegates. */
    private static function withDelegates(string $signature, string $delegates): string
    {
        $credentials = self::n(3) . self::contract() . self::zeros(8) . self::n(0) . $signature . $delegates;
        return self::authorization($credentials, self::contractFn() . self::n(0));
    }

    /**
     * The deepest value of each recursive family the binary decoder accepts
     * (XdrBuffer::DECODE_DEPTH_LIMIT active recursive decodes, 128 delegates) in the record
     * that nests it deepest, and the JSON depth: containers of the record plus of the value.
     *
     * @return array<string, array{class-string, string, int}>
     */
    public static function deepestValues(): array
    {
        $signature = fn (string $value) => self::persistedState(self::withDelegates($value, self::n(0)));
        $createFromAsset = self::n(1, 1, 1) . 'USDC' . self::n(0) . self::zeros(32) . self::n(1);
        $error = self::n(2, 0, 0);
        return [
            'vectors' => [XdrPersistedSCPState::class, $signature(self::vectors(500)), 27 + 2 * 500],
            'maps' => [XdrPersistedSCPState::class,
                $signature(str_repeat(self::n(17, 1, 1, 1), 249) . self::n(17, 1, 0)), 27 + 3 * 249 + 2],
            'maps of vectors' => [XdrPersistedSCPState::class,
                $signature(str_repeat(self::n(17, 1, 1, 1, 16, 1, 1), 166) . self::vectors(2)), 27 + 5 * 166 + 4],
            'contract instances' => [XdrPersistedSCPState::class,
                $signature(str_repeat(self::n(19, 1, 1, 1, 1), 166) . self::n(19, 2) . self::contract() . self::n(0, 0)),
                27 + 4 * 166 + 4],
            'claim predicates' => [XdrPersistedSCPState::class, self::persistedState(self::n(0, 14, 0)
                . self::zeros(8) . self::n(1, 0, 0) . self::zeros(32) . str_repeat(self::n(1, 1), 499) . self::n(1, 0)),
                25 + 2 * 500],
            'quorum sets' => [XdrLedgerCloseMetaBatch::class, self::n(0, 0, 1, 0) . self::zeros(360 + 32)
                . self::n(0, 0, 0, 1, 0, 1) . str_repeat(self::n(1, 0, 1), 499) . self::n(1, 0, 0) . self::n(0, 0),
                8 + 2 * 500],
            'spec type definitions' => [XdrSCSpecEntry::class,
                self::n(2, 0, 0, 0, 1, 1, 0, 0, 1) . str_repeat(self::n(1005, 1), 249) . self::n(1005, 0), 6 + 3 * 250],
            'authorized invocations' => [XdrPersistedSCPState::class, self::persistedState(self::authorization(self::n(0),
                str_repeat(self::contractFn() . self::n(1), 499) . $createFromAsset . self::n(0))), 24 + 2 * 499 + 6],
            'delegates' => [XdrPersistedSCPState::class, self::persistedState(self::withDelegates(self::n(1),
                self::n(1) . str_repeat(self::contract() . self::n(1, 1), 127) . self::contract() . $error . self::n(0))),
                27 + 2 * 127 + 3],
            'delegate with vectors' => [XdrPersistedSCPState::class, self::persistedState(self::withDelegates(self::n(1),
                self::n(1) . self::contract() . self::vectors(499) . self::n(0))), 27 + 1 + 2 * 499],
        ];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('deepestValues')]
    public function testDeepestBinaryValueRoundTripsThroughJson(string $class, string $bytes, int $depth): void
    {
        $buffer = new XdrBuffer($bytes);
        $value = $class::decode($buffer);
        $buffer->assertFullyConsumed($class);
        $json = $value->toJson();
        self::assertSame($depth, self::textDepth($json));
        self::assertSame($bytes, $class::fromJson($json)->encode());
    }

    public function testNestedVectorsDeeperThan512JsonLevelsRoundTrip(): void
    {
        foreach ([300, 400] as $levels) {
            $bytes = self::vectors($levels);
            $json = XdrSCVal::fromBase64Xdr(base64_encode($bytes))->toJson();
            self::assertSame(2 * $levels, self::textDepth($json));
            self::assertSame($bytes, XdrSCVal::fromJson($json)->encode());
        }
    }

    public function testValueAtTheNestingLimitWritesAndADeeperValueIsRefused(): void
    {
        $limit = XdrJsonHelper::JSON_DEPTH_LIMIT;
        $json = self::scValOfDepth($limit)->toJson();
        self::assertSame($limit, self::textDepth($json));
        self::assertSame(self::scValOfDepth($limit)->encode(), XdrSCVal::fromJson($json)->encode());
        try {
            self::scValOfDepth($limit + 1)->toJson();
            self::fail('Expected the nesting limit to refuse the value');
        } catch (JsonException $e) {
            self::assertSame(JSON_ERROR_DEPTH, $e->getCode());
            self::assertSame('XDR-JSON nesting depth limit (' . $limit . ') exceeded', $e->getMessage());
        }
    }

    /** An SCVal whose XDR-JSON nests exactly $depth objects and arrays. */
    private static function scValOfDepth(int $depth): XdrSCVal
    {
        $value = $depth % 2 === 0 ? XdrSCVal::forVec([]) : XdrSCVal::forU32(7);
        for ($nested = 2 - $depth % 2; $nested < $depth; $nested += 2) {
            $value = XdrSCVal::forVec([$value]);
        }
        return $value;
    }

    /** Deepest nesting of objects and arrays in a JSON text, counted outside strings. */
    private static function textDepth(string $json): int
    {
        $depth = 0;
        $max = 0;
        $inString = false;
        for ($i = 0, $len = strlen($json); $i < $len; $i++) {
            $c = $json[$i];
            if ($inString) {
                if ($c === '\\') {
                    $i++;
                } elseif ($c === '"') {
                    $inString = false;
                }
            } elseif ($c === '"') {
                $inString = true;
            } elseif ($c === '{' || $c === '[') {
                $max = max($max, ++$depth);
            } elseif ($c === '}' || $c === ']') {
                $depth--;
            }
        }
        return $max;
    }
}
