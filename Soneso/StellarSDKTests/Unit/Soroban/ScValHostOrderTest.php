<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Soroban;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Soroban\Contract\ContractSpec;
use Soneso\StellarSDK\Soroban\ScValHostOrder;
use Soneso\StellarSDK\Xdr as X;
use Soneso\StellarSDK\Xdr\XdrSCVal as V;

class ScValHostOrderTest extends TestCase
{
    public function testNullVectorsThrow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ScValHostOrder::compare(new V(X\XdrSCValType::VEC()), new V(X\XdrSCValType::VEC()));
    }

    public function testNullMapsThrow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ScValHostOrder::compare(new V(X\XdrSCValType::MAP()), new V(X\XdrSCValType::MAP()));
    }

    public function testUnsupportedTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ScValHostOrder::compare(new V(new X\XdrSCValType(99)), new V(new X\XdrSCValType(99)));
    }

    private function keys(): array
    {
        $contract1 = new X\XdrSCError(X\XdrSCErrorType::SCE_CONTRACT());
        $contract1->contractCode = 1;
        $contract2 = clone $contract1;
        $contract2->contractCode = 2;
        $wasm = new X\XdrSCError(X\XdrSCErrorType::SCE_WASM_VM());
        $wasm->code = X\XdrSCErrorCode::SCEC_INVALID_INPUT();
        $zero = str_repeat("\0", 32);
        $ff = str_repeat("\xff", 32);
        $mux0 = new X\XdrSCAddress(X\XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT());
        $mux0->muxedAccount = new X\XdrMuxedAccountMed25519(0, $ff);
        $mux1 = new X\XdrSCAddress(X\XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT());
        $mux1->muxedAccount = new X\XdrMuxedAccountMed25519(1, $zero);
        // Unsigned 64-bit fields use the PHP int bit pattern; -1 encodes max64.
        return [
            V::forFalse(), V::forTrue(), V::forVoid(),
            V::forError($contract1), V::forError($contract2), V::forError($wasm),
            V::forU32(0), V::forU32(4294967295),
            V::forI32(-2147483648), V::forI32(-1), V::forI32(0), V::forI32(1),
            V::forU64(0), V::forU64(-1), V::forI64(PHP_INT_MIN), V::forI64(-1), V::forI64(0),
            V::forTimepoint(0), V::forTimepoint(1), V::forDuration(0),
            V::forU128Parts(0, 1), V::forU128Parts(0, -1), V::forU128Parts(1, 0),
            V::forI128Parts(-1, -1), V::forI128Parts(0, 0), V::forI128Parts(0, -1), V::forI128Parts(1, 0),
            V::forU256(new X\XdrUInt256Parts(0, 0, 0, 1)), V::forU256(new X\XdrUInt256Parts(1, 0, 0, 0)),
            V::forI256(new X\XdrInt256Parts(-1, -1, -1, -1)), V::forI256(new X\XdrInt256Parts(0, 0, 0, 0)),
            V::forBytes(''), V::forBytes("\x01"), V::forBytes("\x01\0"), V::forBytes("\x02"), V::forBytes("\xff"),
            V::forString(''), V::forString('a'), V::forString('ab'), V::forString('b'),
            V::forSymbol('A'), V::forSymbol('AB'), V::forSymbol('B'), V::forSymbol('_'), V::forSymbol('a'),
            V::forVec([]), V::forVec([V::forU32(1)]), V::forVec([V::forU32(1), V::forU32(0)]),
            V::forVec([V::forU32(2)]), V::forVec([V::forI32(-1)]), V::forMap([]),
            V::forMap([new X\XdrSCMapEntry(V::forU32(1), V::forU32(1))]),
            V::forMap([new X\XdrSCMapEntry(V::forU32(1), V::forU32(2))]),
            V::forMap([new X\XdrSCMapEntry(V::forU32(2), V::forU32(0))]),
            V::forAddress(X\XdrSCAddress::forAccountId(StrKey::encodeAccountId($zero))),
            V::forAddress(X\XdrSCAddress::forAccountId(StrKey::encodeAccountId($ff))),
            V::forAddress(X\XdrSCAddress::forContractId(bin2hex($zero))),
            V::forAddress(X\XdrSCAddress::forContractId(bin2hex($ff))), V::forAddress($mux0), V::forAddress($mux1),
            V::forLedgerKeyContractInstance(), V::forLedgerNonceKey(new X\XdrSCNonceKey(-1)),
            V::forLedgerNonceKey(new X\XdrSCNonceKey(0)),
        ];
    }

    private function shuffled(array $values): array
    {
        $values = array_reverse($values);
        for ($i = 0; $i + 1 < count($values); $i += 2) {
            [$values[$i], $values[$i + 1]] = [$values[$i + 1], $values[$i]];
        }
        return $values;
    }

    public function testSharedVectorPairwise(): void
    {
        $keys = $this->keys();
        $this->assertCount(63, $keys);
        foreach ($keys as $i => $a) {
            $this->assertSame(0, ScValHostOrder::compare($a, V::fromBase64Xdr($a->toBase64Xdr())));
            for ($j = $i + 1; $j < count($keys); $j++) {
                $this->assertLessThan(0, ScValHostOrder::compare($a, $keys[$j]), "$i < $j");
                $this->assertGreaterThan(0, ScValHostOrder::compare($keys[$j], $a), "$j > $i");
            }
        }
    }

    public function testSortedMapAndSeparateDuplicateKey(): void
    {
        $keys = $this->keys();
        $entries = array_map(fn($key) => new X\XdrSCMapEntry($key, V::forVoid()), $this->shuffled($keys));
        $map = V::forMap($entries);
        $this->assertSame($keys, array_map(fn($entry) => $entry->key, $map->map));
        $this->assertSame($map->encode(), V::forMap($map->map)->encode());
        $entries[] = new X\XdrSCMapEntry(V::forI32(-1), V::forTrue());
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(V::forI32(-1)->toBase64Xdr());
        V::forMap($entries);
    }

    public function testSpecMapAndStructConversionOrder(): void
    {
        $spec = new ContractSpec([]);
        foreach ([[X\XdrSCSpecType::I32(), [-2147483648, -1, 0, 1]],
                  [X\XdrSCSpecType::SYMBOL(), ['A', 'AB', 'B', '_', 'a']]] as [$type, $keys]) {
            $def = X\XdrSCSpecTypeDef::forMap(new X\XdrSCSpecTypeMap(
                new X\XdrSCSpecTypeDef($type), new X\XdrSCSpecTypeDef(X\XdrSCSpecType::U32()),
            ));
            $input = array_fill_keys($this->shuffled($keys), 0);
            $result = $spec->nativeToXdrSCVal($input, $def);
            $expected = array_map(fn($key) => $spec->nativeToXdrSCVal($key, $def->map->keyType)->encode(), $keys);
            $this->assertSame($expected, array_map(fn($entry) => $entry->key->encode(), $result->map));
        }
        $fields = array_map(fn($name) => new X\XdrSCSpecUDTStructFieldV0('', $name, new X\XdrSCSpecTypeDef(X\XdrSCSpecType::U32())), ['b', 'ab', 'a']);
        $entry = new X\XdrSCSpecEntry(X\XdrSCSpecEntryKind::UDT_STRUCT_V0());
        $entry->udtStructV0 = new X\XdrSCSpecUDTStructV0('', '', 'Record', $fields);
        $spec = new ContractSpec([$entry]);
        $def = X\XdrSCSpecTypeDef::forUDT(new X\XdrSCSpecTypeUDT('Record'));
        $map = $spec->nativeToXdrSCVal(['b' => 1, 'ab' => 2, 'a' => 3], $def);
        $this->assertSame(['a', 'ab', 'b'], array_map(fn($entry) => $entry->key->sym, $map->map));
    }

    public function testDecodedMapPreservesWireOrder(): void
    {
        $raw = new V(X\XdrSCValType::MAP());
        $raw->map = [new X\XdrSCMapEntry(V::forI32(1), V::forVoid()), new X\XdrSCMapEntry(V::forI32(-1), V::forVoid())];
        $wire = $raw->toBase64Xdr();
        $decoded = V::fromBase64Xdr($wire);
        $this->assertSame($wire, $decoded->toBase64Xdr());
        $this->assertNotSame($wire, V::forMap($decoded->map)->toBase64Xdr());
    }

    public function testAdditionalArmsAndRecursiveSignedValues(): void
    {
        $owner = X\XdrSCAddress::forContractId(str_repeat('00', 32));
        $token = X\XdrContractExecutable::forToken();
        $pairs = [
            [V::forDuration(0), V::forDuration(-1)],
            [V::forTimepoint(PHP_INT_MAX), V::forTimepoint(PHP_INT_MIN)],
            [V::forVec([V::forI32(-1)]), V::forVec([V::forI32(0)])],
            [V::forMap([new X\XdrSCMapEntry(V::forU32(0), V::forI64(-1))]), V::forMap([new X\XdrSCMapEntry(V::forU32(0), V::forI64(0))])],
            [V::forAddress(X\XdrSCAddress::forClaimableBalanceId(str_repeat('00', 32))), V::forAddress(X\XdrSCAddress::forLiquidityPoolId(str_repeat('00', 32)))],
            [V::forContractInstance(new X\XdrSCContractInstance(X\XdrContractExecutable::forWasmId(str_repeat('00', 32)))), V::forContractInstance(new X\XdrSCContractInstance($token))],
            [V::forContractInstance(new X\XdrSCContractInstance($token)), V::forContractInstance(new X\XdrSCContractInstance($token, []))],
            [V::forContractInstance(new X\XdrSCContractInstance($token, [])), V::forContractInstance(new X\XdrSCContractInstance($token, [new X\XdrSCMapEntry(V::forI32(-1), V::forVoid())]))],
            [V::forContractInstance(new X\XdrSCContractInstance(X\XdrContractExecutable::forExternalRef($owner, 'ab'))), V::forContractInstance(new X\XdrSCContractInstance(X\XdrContractExecutable::forExternalRef($owner, 'b')))],
            [V::forExecutableTag('ab'), V::forExecutableTag('b')],
        ];
        foreach ($pairs as [$a, $b]) {
            $this->assertLessThan(0, ScValHostOrder::compare($a, $b));
            $this->assertGreaterThan(0, ScValHostOrder::compare($b, $a));
        }
    }
}
