<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCContractInstance;
use Soneso\StellarSDK\Xdr\XdrContractExecutable;
use Soneso\StellarSDK\Xdr\XdrContractExecutableType;
use Soneso\StellarSDK\Xdr\XdrSCError;
use Soneso\StellarSDK\Xdr\XdrSCErrorType;
use Soneso\StellarSDK\Xdr\XdrSCMapEntry;
use Soneso\StellarSDK\Xdr\XdrSCNonceKey;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;

class XdrSCValToNativeTest extends TestCase
{
    // Copied from XdrSCAddressTest.php, which keeps these as private const and
    // therefore unreferenceable across classes.
    private const TEST_ACCOUNT_ID = 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H';
    private const TEST_CONTRACT_ID_HEX = 'e5c244f77f8e6b82f1a8d3e9b0c5a6d7f8e9a0b1c2d3e4f5a6b7c8d9e0f1a2b3';
    private const TEST_CONTRACT_ID_STRKEY = 'CDS4ERHXP6HGXAXRVDJ6TMGFU3L7R2NAWHBNHZHVU234RWPA6GRLHZ4R';

    // Scalars

    public function testBool(): void
    {
        $this->assertSame(true, XdrSCVal::forBool(true)->toNative());
        $this->assertSame(false, XdrSCVal::forBool(false)->toNative());
    }

    public function testVoid(): void
    {
        $this->assertNull(XdrSCVal::forVoid()->toNative());
    }

    public function testU32(): void
    {
        $this->assertSame(0, XdrSCVal::forU32(0)->toNative());
        $this->assertSame(4294967295, XdrSCVal::forU32(4294967295)->toNative());
    }

    public function testI32(): void
    {
        $this->assertSame(-2147483648, XdrSCVal::forI32(-2147483648)->toNative());
        $this->assertSame(2147483647, XdrSCVal::forI32(2147483647)->toNative());
    }

    public function testI64(): void
    {
        $this->assertSame(PHP_INT_MIN, XdrSCVal::forI64(PHP_INT_MIN)->toNative());
        $this->assertSame(PHP_INT_MAX, XdrSCVal::forI64(PHP_INT_MAX)->toNative());
    }

    public function testU64(): void
    {
        $this->assertSame(0, XdrSCVal::forU64(0)->toNative());
        $this->assertSame(PHP_INT_MAX, XdrSCVal::forU64(PHP_INT_MAX)->toNative());
        // PHP_INT_MIN as the stored (wrapped) bit pattern is 2^63, the true unsigned value.
        $this->assertSame('9223372036854775808', XdrSCVal::forU64(PHP_INT_MIN)->toNative());
        // -1 as the stored bit pattern is all ones, i.e. 2^64-1 unsigned.
        $this->assertSame('18446744073709551615', XdrSCVal::forU64(-1)->toNative());
    }

    public function testTimepoint(): void
    {
        $this->assertSame(1700000000, XdrSCVal::forTimepoint(1700000000)->toNative());
        $this->assertSame('18446744073709551615', XdrSCVal::forTimepoint(-1)->toNative());
    }

    public function testDuration(): void
    {
        $this->assertSame('18446744073709551615', XdrSCVal::forDuration(-1)->toNative());
    }

    // 128/256-bit (GMP)

    public function testU128BigIntMax(): void
    {
        $val = XdrSCVal::forU128BigInt('340282366920938463463374607431768211455');
        $result = $val->toNative();
        $this->assertInstanceOf(\GMP::class, $result);
        $this->assertSame(0, gmp_cmp($result, gmp_init('340282366920938463463374607431768211455')));
    }

    public function testI128BigIntMin(): void
    {
        $val = XdrSCVal::forI128BigInt('-170141183460469231731687303715884105728');
        $result = $val->toNative();
        $this->assertInstanceOf(\GMP::class, $result);
        $this->assertSame(0, gmp_cmp($result, gmp_init('-170141183460469231731687303715884105728')));
    }

    public function testI128BigIntNegativeOne(): void
    {
        $val = XdrSCVal::forI128BigInt(-1);
        $result = $val->toNative();
        $this->assertInstanceOf(\GMP::class, $result);
        $this->assertSame(0, gmp_cmp($result, gmp_init(-1)));
    }

    public function testU256BigIntMax(): void
    {
        $expected = '115792089237316195423570985008687907853269984665640564039457584007913129639935';
        $val = XdrSCVal::forU256BigInt($expected);
        $result = $val->toNative();
        $this->assertInstanceOf(\GMP::class, $result);
        $this->assertSame(0, gmp_cmp($result, gmp_init($expected)));
    }

    public function testI256BigIntMin(): void
    {
        $expected = '-57896044618658097711785492504343953926634992332820282019728792003956564819968';
        $val = XdrSCVal::forI256BigInt($expected);
        $result = $val->toNative();
        $this->assertInstanceOf(\GMP::class, $result);
        $this->assertSame(0, gmp_cmp($result, gmp_init($expected)));
    }

    // Bytes, strings, symbols

    public function testBytes(): void
    {
        $value = "\x00\x01\xff";
        $this->assertSame($value, XdrSCVal::forBytes($value)->toNative());
    }

    public function testString(): void
    {
        $this->assertSame('hello', XdrSCVal::forString('hello')->toNative());

        $multiByte = "caf\xc3\xa9 \xe4\xbd\xa0\xe5\xa5\xbd";
        $this->assertSame($multiByte, XdrSCVal::forString($multiByte)->toNative());

        // No UTF-8 validation or transcoding: an invalid byte sequence round-trips byte-exact.
        $invalidUtf8 = "\xff\xfe";
        $this->assertSame($invalidUtf8, XdrSCVal::forString($invalidUtf8)->toNative());
    }

    public function testSymbol(): void
    {
        $this->assertSame('transfer', XdrSCVal::forSymbol('transfer')->toNative());
    }

    // Vec

    public function testVecMixedElements(): void
    {
        $val = XdrSCVal::forVec([
            XdrSCVal::forU32(1),
            XdrSCVal::forSymbol('a'),
            XdrSCVal::forVec([XdrSCVal::forBool(true)]),
        ]);
        $this->assertSame([1, 'a', [true]], $val->toNative());
    }

    public function testVecEmpty(): void
    {
        $this->assertSame([], XdrSCVal::forVec([])->toNative());
    }

    public function testVecNullPayload(): void
    {
        // Built the way the factories construct instances, but without setting the
        // payload: a legitimate wire state per the null-vec rule.
        $val = new XdrSCVal(XdrSCValType::VEC());
        $this->assertSame([], $val->toNative());
    }

    // Map

    public function testMapSymbolKeysPreserveOrder(): void
    {
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forSymbol('name'), XdrSCVal::forString('Alice')),
            new XdrSCMapEntry(XdrSCVal::forSymbol('age'), XdrSCVal::forU32(30)),
        ]);
        $this->assertSame(['name' => 'Alice', 'age' => 30], $val->toNative());
    }

    public function testMapU32Keys(): void
    {
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forU32(1), XdrSCVal::forString('one')),
            new XdrSCMapEntry(XdrSCVal::forU32(2), XdrSCVal::forString('two')),
        ]);
        $this->assertSame([1 => 'one', 2 => 'two'], $val->toNative());
    }

    public function testMapI64KeyNegative(): void
    {
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forI64(-5), XdrSCVal::forBool(true)),
        ]);
        $result = $val->toNative();
        $this->assertSame([-5 => true], $result);
    }

    public function testMapStringKey(): void
    {
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forString('greeting'), XdrSCVal::forBool(true)),
        ]);
        $this->assertSame(['greeting' => true], $val->toNative());
    }

    public function testMapI32Key(): void
    {
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forI32(-7), XdrSCVal::forBool(false)),
        ]);
        $this->assertSame([-7 => false], $val->toNative());
    }

    public function testMapAccountAddressKey(): void
    {
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry(
                XdrSCVal::forAddress(XdrSCAddress::forAccountId(self::TEST_ACCOUNT_ID)),
                XdrSCVal::forU32(1)
            ),
        ]);
        $result = $val->toNative();
        $this->assertSame([self::TEST_ACCOUNT_ID => 1], $result);
    }

    public function testMapContractAddressKey(): void
    {
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry(
                XdrSCVal::forAddress(XdrSCAddress::forContractId(self::TEST_CONTRACT_ID_HEX)),
                XdrSCVal::forU32(2)
            ),
        ]);
        $result = $val->toNative();
        $this->assertSame([self::TEST_CONTRACT_ID_STRKEY => 2], $result);
    }

    public function testMapNumericSymbolKeyCoercesToInt(): void
    {
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forSymbol('123'), XdrSCVal::forU32(9)),
        ]);
        $result = $val->toNative();
        // PHP array behavior, not a conversion toNative() performs: documented explicitly.
        $this->assertSame(123, array_keys($result)[0]);
        $this->assertSame([123 => 9], $result);
    }

    public function testMapU64KeyAboveIntMax(): void
    {
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forU64(-1), XdrSCVal::forBool(true)),
        ]);
        $result = $val->toNative();
        $this->assertSame(['18446744073709551615' => true], $result);
    }

    public function testMapTimepointKey(): void
    {
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forTimepoint(1700000000), XdrSCVal::forBool(true)),
        ]);
        $this->assertSame([1700000000 => true], $val->toNative());
    }

    public function testMapDurationKey(): void
    {
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forDuration(-1), XdrSCVal::forBool(true)),
        ]);
        $this->assertSame(['18446744073709551615' => true], $val->toNative());
    }

    public function testMapI128BigIntKey(): void
    {
        $decimal = '170141183460469231731687303715884105727';
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forI128BigInt($decimal), XdrSCVal::forBool(true)),
        ]);
        $this->assertSame([$decimal => true], $val->toNative());
    }

    public function testMapU128KeyAndU256KeyAndI256Key(): void
    {
        $u128 = '340282366920938463463374607431768211455';
        $valU128 = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forU128BigInt($u128), XdrSCVal::forBool(true)),
        ]);
        $this->assertSame([$u128 => true], $valU128->toNative());

        $u256 = '115792089237316195423570985008687907853269984665640564039457584007913129639935';
        $valU256 = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forU256BigInt($u256), XdrSCVal::forBool(true)),
        ]);
        $this->assertSame([$u256 => true], $valU256->toNative());

        $i256 = '-57896044618658097711785492504343953926634992332820282019728792003956564819968';
        $valI256 = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forI256BigInt($i256), XdrSCVal::forBool(true)),
        ]);
        $this->assertSame([$i256 => true], $valI256->toNative());
    }

    public function testMapBytesKey(): void
    {
        $keyBytes = "\x01\x02";
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forBytes($keyBytes), XdrSCVal::forBool(true)),
        ]);
        $this->assertSame([$keyBytes => true], $val->toNative());
    }

    public function testMapBoolKeyFallsBack(): void
    {
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forBool(true), XdrSCVal::forU32(1)),
        ]);
        $this->assertSame($val, $val->toNative());
    }

    public function testMapVecKeyFallsBack(): void
    {
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forVec([]), XdrSCVal::forU32(1)),
        ]);
        $this->assertSame($val, $val->toNative());
    }

    public function testMapCollisionFallsBack(): void
    {
        // Symbol "1" and U32 1 both coerce/convert to the PHP int key 1.
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forSymbol('1'), XdrSCVal::forU32(100)),
            new XdrSCMapEntry(XdrSCVal::forU32(1), XdrSCVal::forU32(200)),
        ]);
        $this->assertSame($val, $val->toNative());
    }

    public function testMapEmpty(): void
    {
        $this->assertSame([], XdrSCVal::forMap([])->toNative());
    }

    public function testMapNullPayload(): void
    {
        // Built the way the factories construct instances, but without setting the
        // payload: a legitimate wire state per the null-map rule.
        $val = new XdrSCVal(XdrSCValType::MAP());
        $this->assertSame([], $val->toNative());
    }

    public function testMapFallbackContainedInEnclosingVec(): void
    {
        $mapWithBoolKey = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forBool(true), XdrSCVal::forU32(1)),
        ]);
        $val = XdrSCVal::forVec([XdrSCVal::forU32(7), $mapWithBoolKey]);
        $result = $val->toNative();
        $this->assertSame(7, $result[0]);
        $this->assertSame($mapWithBoolKey, $result[1]);
    }

    public function testMapFallbackContainedInEnclosingMap(): void
    {
        $mapWithVecKey = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forVec([]), XdrSCVal::forU32(1)),
        ]);
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forSymbol('inner'), $mapWithVecKey),
            new XdrSCMapEntry(XdrSCVal::forSymbol('other'), XdrSCVal::forU32(9)),
        ]);
        $result = $val->toNative();
        $this->assertSame($mapWithVecKey, $result['inner']);
        $this->assertSame(9, $result['other']);
    }

    // Address values

    public function testAddressAccountValue(): void
    {
        $val = XdrSCVal::forAddress(XdrSCAddress::forAccountId(self::TEST_ACCOUNT_ID));
        $result = $val->toNative();
        $this->assertInstanceOf(Address::class, $result);
        $this->assertSame(Address::TYPE_ACCOUNT, $result->type);
        $this->assertSame(self::TEST_ACCOUNT_ID, $result->accountId);
        $this->assertSame(self::TEST_ACCOUNT_ID, $result->toStrKey());
    }

    public function testAddressContractValue(): void
    {
        $val = XdrSCVal::forAddress(XdrSCAddress::forContractId(self::TEST_CONTRACT_ID_HEX));
        $result = $val->toNative();
        $this->assertInstanceOf(Address::class, $result);
        $this->assertSame(Address::TYPE_CONTRACT, $result->type);
        $this->assertSame(self::TEST_CONTRACT_ID_STRKEY, $result->toStrKey());
    }

    // Fallback arms

    public function testErrorFallsBack(): void
    {
        $error = new XdrSCError(XdrSCErrorType::SCE_CONTRACT());
        $error->setContractCode(1);
        $val = XdrSCVal::forError($error);
        $this->assertSame($val, $val->toNative());
    }

    public function testContractInstanceFallsBack(): void
    {
        $executable = new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM());
        $executable->setWasmIdHex(str_repeat('01', 32));
        $instance = new XdrSCContractInstance($executable, null);
        $val = XdrSCVal::forContractInstance($instance);
        $this->assertSame($val, $val->toNative());
    }

    public function testLedgerKeyContractInstanceFallsBack(): void
    {
        $val = XdrSCVal::forLedgerKeyContractInstance();
        $this->assertSame($val, $val->toNative());
    }

    public function testLedgerNonceKeyFallsBack(): void
    {
        $val = XdrSCVal::forLedgerNonceKey(new XdrSCNonceKey(1));
        $this->assertSame($val, $val->toNative());
    }

    public function testExecutableTagFallsBack(): void
    {
        $val = XdrSCVal::forExecutableTag('v1');
        $this->assertSame($val, $val->toNative());
    }

    public function testIllFormedU128FallsBack(): void
    {
        // Built the way the factories construct instances, but without setting the
        // required payload: the null-payload rule requires falling back rather than
        // faking a value.
        $val = new XdrSCVal(XdrSCValType::U128());
        $this->assertSame($val, $val->toNative());
    }

    public function testIllFormedI128FallsBack(): void
    {
        $val = new XdrSCVal(XdrSCValType::I128());
        $this->assertSame($val, $val->toNative());
    }

    public function testIllFormedU256FallsBack(): void
    {
        $val = new XdrSCVal(XdrSCValType::U256());
        $this->assertSame($val, $val->toNative());
    }

    public function testIllFormedI256FallsBack(): void
    {
        $val = new XdrSCVal(XdrSCValType::I256());
        $this->assertSame($val, $val->toNative());
    }

    public function testIllFormedBoolFallsBack(): void
    {
        $val = new XdrSCVal(XdrSCValType::BOOL());
        $this->assertSame($val, $val->toNative());
    }

    public function testIllFormedU32FallsBack(): void
    {
        $val = new XdrSCVal(XdrSCValType::U32());
        $this->assertSame($val, $val->toNative());
    }

    public function testIllFormedI32FallsBack(): void
    {
        $val = new XdrSCVal(XdrSCValType::I32());
        $this->assertSame($val, $val->toNative());
    }

    public function testIllFormedU64FallsBack(): void
    {
        $val = new XdrSCVal(XdrSCValType::U64());
        $this->assertSame($val, $val->toNative());
    }

    public function testIllFormedI64FallsBack(): void
    {
        $val = new XdrSCVal(XdrSCValType::I64());
        $this->assertSame($val, $val->toNative());
    }

    public function testIllFormedTimepointFallsBack(): void
    {
        $val = new XdrSCVal(XdrSCValType::TIMEPOINT());
        $this->assertSame($val, $val->toNative());
    }

    public function testIllFormedDurationFallsBack(): void
    {
        $val = new XdrSCVal(XdrSCValType::DURATION());
        $this->assertSame($val, $val->toNative());
    }

    public function testIllFormedBytesFallsBack(): void
    {
        $val = new XdrSCVal(XdrSCValType::BYTES());
        $this->assertSame($val, $val->toNative());
    }

    public function testIllFormedStringFallsBack(): void
    {
        $val = new XdrSCVal(XdrSCValType::STRING());
        $this->assertSame($val, $val->toNative());
    }

    public function testIllFormedSymbolFallsBack(): void
    {
        $val = new XdrSCVal(XdrSCValType::SYMBOL());
        $this->assertSame($val, $val->toNative());
    }

    public function testIllFormedAddressValueFallsBackNoException(): void
    {
        // XdrSCAddress::forContractId does not validate; the garbage id only surfaces
        // when getCanonicalContractIdHex() (via toStrKey()/Address::fromXdr()) rejects it.
        $val = XdrSCVal::forAddress(XdrSCAddress::forContractId('not-a-valid-id'));
        $this->assertSame($val, $val->toNative());
    }

    public function testIllFormedAddressPayloadNullFallsBack(): void
    {
        $val = new XdrSCVal(XdrSCValType::ADDRESS());
        $this->assertSame($val, $val->toNative());
    }

    public function testIllFormedAddressAsMapKeyFallsBackForWholeMap(): void
    {
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry(
                XdrSCVal::forAddress(XdrSCAddress::forContractId('not-a-valid-id')),
                XdrSCVal::forU32(1)
            ),
        ]);
        $this->assertSame($val, $val->toNative());
    }

    public function testAddressMapKeyWithNullPayloadFallsBackForWholeMap(): void
    {
        $illFormedAddressKey = new XdrSCVal(XdrSCValType::ADDRESS());
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry($illFormedAddressKey, XdrSCVal::forU32(1)),
        ]);
        $this->assertSame($val, $val->toNative());
    }

    public function testU64MapKeyWithNullPayloadFallsBackForWholeMap(): void
    {
        $illFormedU64Key = new XdrSCVal(XdrSCValType::U64());
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry($illFormedU64Key, XdrSCVal::forU32(1)),
        ]);
        $this->assertSame($val, $val->toNative());
    }

    public function testTimepointMapKeyWithNullPayloadFallsBackForWholeMap(): void
    {
        $illFormedTimepointKey = new XdrSCVal(XdrSCValType::TIMEPOINT());
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry($illFormedTimepointKey, XdrSCVal::forU32(1)),
        ]);
        $this->assertSame($val, $val->toNative());
    }

    public function testDurationMapKeyWithNullPayloadFallsBackForWholeMap(): void
    {
        $illFormedDurationKey = new XdrSCVal(XdrSCValType::DURATION());
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry($illFormedDurationKey, XdrSCVal::forU32(1)),
        ]);
        $this->assertSame($val, $val->toNative());
    }

    public function testI128MapKeyWithNullPayloadFallsBackForWholeMap(): void
    {
        // U128/I128/U256/I256 share one case block in mapKeyToNative(), so this one
        // arm stands in for the whole group.
        $illFormedI128Key = new XdrSCVal(XdrSCValType::I128());
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry($illFormedI128Key, XdrSCVal::forU32(1)),
        ]);
        $this->assertSame($val, $val->toNative());
    }

    public function testBytesMapKeyWithNullPayloadFallsBackForWholeMap(): void
    {
        $illFormedBytesKey = new XdrSCVal(XdrSCValType::BYTES());
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry($illFormedBytesKey, XdrSCVal::forU32(1)),
        ]);
        $this->assertSame($val, $val->toNative());
    }

    public function testFutureArmFallsBack(): void
    {
        // An arm value beyond every defined XdrSCValType constant exercises the
        // switch's default case in both toNative() and mapKeyToNative().
        $val = new XdrSCVal(new XdrSCValType(9999));
        $this->assertSame($val, $val->toNative());

        $mapVal = XdrSCVal::forMap([
            new XdrSCMapEntry(new XdrSCVal(new XdrSCValType(9999)), XdrSCVal::forU32(1)),
        ]);
        $this->assertSame($mapVal, $mapVal->toNative());
    }

    // Round trip through wire format

    public function testWireFormatRoundTrip(): void
    {
        $val = XdrSCVal::forMap([
            new XdrSCMapEntry(
                XdrSCVal::forSymbol('items'),
                XdrSCVal::forVec([
                    XdrSCVal::forI128BigInt('-170141183460469231731687303715884105728'),
                    XdrSCVal::forAddress(XdrSCAddress::forAccountId(self::TEST_ACCOUNT_ID)),
                ])
            ),
            new XdrSCMapEntry(
                XdrSCVal::forSymbol('big_u64'),
                XdrSCVal::forU64(-1)
            ),
        ]);

        $roundTripped = XdrSCVal::fromBase64Xdr($val->toBase64Xdr())->toNative();

        // assertEquals, not assertSame: the GMP and Address object trees are distinct
        // instances of value-equal objects, produced independently by decode() versus
        // the factories.
        $this->assertEquals($val->toNative(), $roundTripped);
    }
}
