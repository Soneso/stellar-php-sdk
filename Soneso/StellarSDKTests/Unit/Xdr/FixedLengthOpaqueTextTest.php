<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use InvalidArgumentException;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\TxRepHelper;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrAllowTrustOperationAsset;
use Soneso\StellarSDK\Xdr\XdrAllowTrustOperationAssetBase;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum12;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum4;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrChangeTrustAsset;
use Soneso\StellarSDK\Xdr\XdrConfigUpgradeSetKey;
use Soneso\StellarSDK\Xdr\XdrDecoratedSignature;
use Soneso\StellarSDK\Xdr\XdrFreezeBypassTxs;
use Soneso\StellarSDK\Xdr\XdrInnerTransactionResultPair;
use Soneso\StellarSDK\Xdr\XdrIPAddrType;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyContractCode;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyLiquidityPool;
use Soneso\StellarSDK\Xdr\XdrMemo;
use Soneso\StellarSDK\Xdr\XdrMemoType;
use Soneso\StellarSDK\Xdr\XdrPeerAddressIp;
use Soneso\StellarSDK\Xdr\XdrSequenceNumber;
use Soneso\StellarSDK\Xdr\XdrShortHashSeed;
use Soneso\StellarSDK\Xdr\XdrTransactionV0;
use Soneso\StellarSDK\Xdr\XdrTrustlineAsset;
use Soneso\StellarSDK\Xdr\XdrTxAdvertVector;

class FixedLengthOpaqueTextTest extends TestCase
{
    private const ISSUER = 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN';
    private const CONTRACT_STRKEY = 'CA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAXE';

    private const HEX_32 = '000102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f';
    private const HEX_31 = '000102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e';
    private const HEX_33 = '000102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f20';

    private const HEX_16 = '00112233445566778899aabbccddeeff';
    private const HEX_15 = '00112233445566778899aabbccddee';
    private const HEX_17 = '00112233445566778899aabbccddeeff00';

    private function assertThrowsFixedLength(callable $fn, int $actual, int $expected, string $context): void
    {
        $expectedMessage = sprintf('Unexpected length for value. Has length %d, expected %d', $actual, $expected);
        try {
            $fn();
            $this->fail($context . ': expected InvalidArgumentException(' . $expectedMessage . '), none thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertSame($expectedMessage, $e->getMessage(), $context);
        }
    }

    // -------------------------------------------------------------------------
    // Valid values: pin exact output in both XDR-JSON and TxRep
    // -------------------------------------------------------------------------

    public function testHashStructValidOutput(): void
    {
        $bytes32 = hex2bin(self::HEX_32);
        $obj = new XdrLedgerKeyContractCode($bytes32);

        $expectedJson = '{"hash":"' . self::HEX_32 . '"}';
        $this->assertSame($expectedJson, $obj->toJson());
        $fromJson = XdrLedgerKeyContractCode::fromJson($expectedJson);
        $this->assertSame($bytes32, $fromJson->getHash());

        $lines = [];
        $obj->toTxRep('k', $lines);
        $this->assertSame(['k.hash' => self::HEX_32], $lines);
        $fromTxRep = XdrLedgerKeyContractCode::fromTxRep($lines, 'k');
        $this->assertSame($bytes32, $fromTxRep->getHash());
    }

    public function testSignatureHintValidOutput(): void
    {
        $hint = "\x01\x02\x03\x04";
        $sig = "\xaa\xbb";
        $obj = new XdrDecoratedSignature($hint, $sig);

        $expectedJson = '{"hint":"01020304","signature":"aabb"}';
        $this->assertSame($expectedJson, $obj->toJson());
        $fromJson = XdrDecoratedSignature::fromJson($expectedJson);
        $this->assertSame($hint, $fromJson->getHint());
        $this->assertSame($sig, $fromJson->getSignature());

        $lines = [];
        $obj->toTxRep('s', $lines);
        $this->assertSame(['s.hint' => '01020304', 's.signature' => 'aabb'], $lines);
        $fromTxRep = XdrDecoratedSignature::fromTxRep($lines, 's');
        $this->assertSame($hint, $fromTxRep->getHint());
        $this->assertSame($sig, $fromTxRep->getSignature());

        // Variable-length opaque keeps empty / '0' behavior.
        $emptySig = new XdrDecoratedSignature($hint, '');
        $this->assertSame('{"hint":"01020304","signature":""}', $emptySig->toJson());
        $this->assertSame('', XdrDecoratedSignature::fromJson('{"hint":"01020304","signature":""}')->getSignature());
        $emptyLines = [];
        $emptySig->toTxRep('s', $emptyLines);
        $this->assertSame(['s.hint' => '01020304', 's.signature' => '0'], $emptyLines);
        $this->assertSame('', XdrDecoratedSignature::fromTxRep($emptyLines, 's')->getSignature());
    }

    public function testInlineFixedOpaqueStructValidOutput(): void
    {
        $seed = hex2bin(self::HEX_16);
        $obj = new XdrShortHashSeed($seed);

        $expectedJson = '{"seed":"' . self::HEX_16 . '"}';
        $this->assertSame($expectedJson, $obj->toJson());
        $fromJson = XdrShortHashSeed::fromJson($expectedJson);
        $this->assertSame($seed, $fromJson->getSeed());
    }

    public function testUnionArmMemoAndPeerIpValidOutput(): void
    {
        $bytes32 = hex2bin(self::HEX_32);

        $memoHash = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_HASH));
        $memoHash->setHash($bytes32);
        $expectedHashJson = '{"hash":"' . self::HEX_32 . '"}';
        $this->assertSame($expectedHashJson, $memoHash->toJson());
        $this->assertSame($bytes32, XdrMemo::fromJson($expectedHashJson)->getHash());

        $hashLines = [];
        $memoHash->toTxRep('m', $hashLines);
        $this->assertSame(['m.type' => 'MEMO_HASH', 'm.hash' => self::HEX_32], $hashLines);
        $this->assertSame($bytes32, XdrMemo::fromTxRep($hashLines, 'm')->getHash());

        $memoReturn = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_RETURN));
        $memoReturn->setReturnHash($bytes32);
        $expectedReturnJson = '{"return":"' . self::HEX_32 . '"}';
        $this->assertSame($expectedReturnJson, $memoReturn->toJson());
        $this->assertSame($bytes32, XdrMemo::fromJson($expectedReturnJson)->getReturnHash());

        $retLines = [];
        $memoReturn->toTxRep('m', $retLines);
        $this->assertSame(['m.type' => 'MEMO_RETURN', 'm.retHash' => self::HEX_32], $retLines);
        $this->assertSame($bytes32, XdrMemo::fromTxRep($retLines, 'm')->getReturnHash());

        $ipv4 = new XdrPeerAddressIp(new XdrIPAddrType(XdrIPAddrType::IPv4));
        $ipv4->setIpv4("\x7f\x00\x00\x01");
        $this->assertSame('{"i_pv4":"7f000001"}', $ipv4->toJson());
        $this->assertSame("\x7f\x00\x00\x01", XdrPeerAddressIp::fromJson('{"i_pv4":"7f000001"}')->getIpv4());

        $ipv6Bytes = hex2bin(self::HEX_16);
        $ipv6 = new XdrPeerAddressIp(new XdrIPAddrType(XdrIPAddrType::IPv6));
        $ipv6->setIpv6($ipv6Bytes);
        $this->assertSame('{"i_pv6":"' . self::HEX_16 . '"}', $ipv6->toJson());
        $this->assertSame($ipv6Bytes, XdrPeerAddressIp::fromJson('{"i_pv6":"' . self::HEX_16 . '"}')->getIpv6());
    }

    public function testArrayElementFixedOpaqueValidOutput(): void
    {
        $bytes32 = hex2bin(self::HEX_32);

        $freeze = new XdrFreezeBypassTxs([$bytes32]);
        $expectedFreezeJson = '{"tx_hashes":["' . self::HEX_32 . '"]}';
        $this->assertSame($expectedFreezeJson, $freeze->toJson());
        $this->assertSame([$bytes32], XdrFreezeBypassTxs::fromJson($expectedFreezeJson)->getTxHashes());

        $advert = new XdrTxAdvertVector([$bytes32]);
        $expectedAdvertJson = '["' . self::HEX_32 . '"]';
        $this->assertSame($expectedAdvertJson, $advert->toJson());
        $this->assertSame([$bytes32], XdrTxAdvertVector::fromJson($expectedAdvertJson)->txAdvertVector);
    }

    public function testPoolShareAndStorageAndTxV0ValidOutput(): void
    {
        $bytes32 = hex2bin(self::HEX_32);

        $poolAsset = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_POOL_SHARE));
        $poolAsset->setLiquidityPoolID($bytes32);
        $this->assertSame(self::HEX_32, TxRepHelper::formatTrustlineAsset($poolAsset));

        $lkPool = new XdrLedgerKeyLiquidityPool($bytes32);
        $lkLines = [];
        $lkPool->toTxRep('lp', $lkLines);
        $this->assertSame(['lp.liquidityPoolID' => self::HEX_32], $lkLines);
        $this->assertSame($bytes32, XdrLedgerKeyLiquidityPool::fromTxRep($lkLines, 'lp')->getLiquidityPoolID());

        $txV0 = new XdrTransactionV0($bytes32, new XdrSequenceNumber(new BigInteger(1)), []);
        $expectedTxV0Json = '{"source_account_ed25519":"' . self::HEX_32 . '","fee":100,"seq_num":"1","time_bounds":null,"memo":"none","operations":[],"ext":"v0"}';
        $this->assertSame($expectedTxV0Json, $txV0->toJson());
        $this->assertSame($bytes32, XdrTransactionV0::fromJson($expectedTxV0Json)->getSourceAccountEd25519());

        $txLines = [];
        $txV0->toTxRep('tx', $txLines);
        $this->assertSame(self::HEX_32, $txLines['tx.sourceAccountEd25519']);
        $this->assertSame($bytes32, XdrTransactionV0::fromTxRep($txLines, 'tx')->getSourceAccountEd25519());

        $cfgJson = '{"contract_id":"' . self::CONTRACT_STRKEY . '","content_hash":"' . self::HEX_32 . '"}';
        $cfg = XdrConfigUpgradeSetKey::fromJson($cfgJson);
        $this->assertSame(self::HEX_32, $cfg->getContentHash());

        $pairJson = '{"transaction_hash":"' . self::HEX_32 . '","result":{"fee_charged":"100","result":"tx_too_early","ext":"v0"}}';
        $pair = XdrInnerTransactionResultPair::fromJson($pairJson);
        $this->assertSame(self::HEX_32, $pair->getTransactionHash());
    }

    public function testAssetCodesValidOutput(): void
    {
        $issuer = XdrAccountID::fromAccountId(self::ISSUER);

        // 3-byte AssetCode4 ("USD") keeps present output in both formats and reads back.
        $a4 = new XdrAssetAlphaNum4('USD', $issuer);
        $expectedA4Json = '{"asset_code":"USD","issuer":"' . self::ISSUER . '"}';
        $this->assertSame($expectedA4Json, $a4->toJson());
        $fromA4Json = XdrAssetAlphaNum4::fromJson($expectedA4Json);
        $this->assertSame("USD\x00", $fromA4Json->getAssetCode());

        $a4Lines = [];
        $a4->toTxRep('a4', $a4Lines);
        $this->assertSame(['a4.assetCode' => '555344', 'a4.issuer' => self::ISSUER], $a4Lines);
        $this->assertSame('USD', XdrAssetAlphaNum4::fromTxRep($a4Lines, 'a4')->getAssetCode());

        // Empty AssetCode4 (0 bytes / TxRep '0') is accepted.
        $emptyA4 = new XdrAssetAlphaNum4('', $issuer);
        $emptyA4Lines = [];
        $emptyA4->toTxRep('a4', $emptyA4Lines);
        $this->assertSame(['a4.assetCode' => '0', 'a4.issuer' => self::ISSUER], $emptyA4Lines);
        $this->assertSame('', XdrAssetAlphaNum4::fromTxRep($emptyA4Lines, 'a4')->getAssetCode());

        // Compact TxRep helpers for AlphaNum4.
        $asset4 = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $asset4->setAlphaNum4($a4);
        $this->assertSame('USD:' . self::ISSUER, TxRepHelper::formatAsset($asset4));

        $ct4 = new XdrChangeTrustAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $ct4->setAlphaNum4($a4);
        $this->assertSame('USD:' . self::ISSUER, TxRepHelper::formatChangeTrustAsset($ct4));

        $tl4 = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $tl4->setAlphaNum4($a4);
        $this->assertSame('USD:' . self::ISSUER, TxRepHelper::formatTrustlineAsset($tl4));

        // 10-byte AssetCode12 ("LONGASSET1") keeps present output in both formats and reads back.
        $a12 = new XdrAssetAlphaNum12('LONGASSET1', $issuer);
        $expectedA12Json = '{"asset_code":"LONGASSET1","issuer":"' . self::ISSUER . '"}';
        $this->assertSame($expectedA12Json, $a12->toJson());
        $fromA12Json = XdrAssetAlphaNum12::fromJson($expectedA12Json);
        $this->assertSame("LONGASSET1\x00\x00", $fromA12Json->getAssetCode());

        $a12Lines = [];
        $a12->toTxRep('a12', $a12Lines);
        $this->assertSame(['a12.assetCode' => '4c4f4e47415353455431', 'a12.issuer' => self::ISSUER], $a12Lines);
        $this->assertSame('LONGASSET1', XdrAssetAlphaNum12::fromTxRep($a12Lines, 'a12')->getAssetCode());

        $asset12 = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $asset12->setAlphaNum12($a12);
        $this->assertSame('LONGASSET1:' . self::ISSUER, TxRepHelper::formatAsset($asset12));

        // AllowTrust asset codes (3-byte and 10-byte).
        $allow4 = XdrAllowTrustOperationAsset::fromAlphaNumAssetCode('USD');
        $this->assertSame('"USD"', $allow4->toJson());
        $this->assertSame('USD', TxRepHelper::formatAllowTrustAsset($allow4));

        $allow12 = XdrAllowTrustOperationAsset::fromAlphaNumAssetCode('LONGASSET1');
        $this->assertSame('"LONGASSET1"', $allow12->toJson());
        $this->assertSame('LONGASSET1', TxRepHelper::formatAllowTrustAsset($allow12));

        // Existing XDR-JSON reader error messages for over-length asset codes stay unchanged.
        try {
            XdrAssetAlphaNum4::fromJson('{"asset_code":"ABCDE","issuer":"' . self::ISSUER . '"}');
            $this->fail('Expected AssetCode4 fromJson to reject 5 bytes');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('AssetCode4 must not exceed 4 bytes; got 5', $e->getMessage());
        }
        try {
            XdrAssetAlphaNum12::fromJson('{"asset_code":"ABCDEFGHIJKLM","issuer":"' . self::ISSUER . '"}');
            $this->fail('Expected AssetCode12 fromJson to reject 13 bytes');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('AssetCode12 must not exceed 12 bytes; got 13', $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Wrong lengths: writers throw before writing, readers throw after hex decode
    // -------------------------------------------------------------------------

    public function testHashStructJsonRejectsWrongLength(): void
    {
        $short = new XdrLedgerKeyContractCode(hex2bin(self::HEX_31));
        $this->assertThrowsFixedLength(fn () => $short->toJson(), 31, 32, 'XdrLedgerKeyContractCode toJson 31B');

        $long = new XdrLedgerKeyContractCode(hex2bin(self::HEX_33));
        $this->assertThrowsFixedLength(fn () => $long->toJson(), 33, 32, 'XdrLedgerKeyContractCode toJson 33B');

        $this->assertThrowsFixedLength(
            fn () => XdrLedgerKeyContractCode::fromJson('{"hash":"' . self::HEX_31 . '"}'),
            31,
            32,
            'XdrLedgerKeyContractCode fromJson 31B'
        );
        $this->assertThrowsFixedLength(
            fn () => XdrLedgerKeyContractCode::fromJson('{"hash":"' . self::HEX_33 . '"}'),
            33,
            32,
            'XdrLedgerKeyContractCode fromJson 33B'
        );
    }

    public function testHashStructTxRepRejectsWrongLength(): void
    {
        $shortLines = [];
        $short = new XdrLedgerKeyContractCode(hex2bin(self::HEX_31));
        $this->assertThrowsFixedLength(
            function () use ($short, &$shortLines): void {
                $short->toTxRep('k', $shortLines);
            },
            31,
            32,
            'XdrLedgerKeyContractCode toTxRep 31B'
        );
        $this->assertSame([], $shortLines, 'toTxRep must throw before writing');

        $longLines = [];
        $long = new XdrLedgerKeyContractCode(hex2bin(self::HEX_33));
        $this->assertThrowsFixedLength(
            function () use ($long, &$longLines): void {
                $long->toTxRep('k', $longLines);
            },
            33,
            32,
            'XdrLedgerKeyContractCode toTxRep 33B'
        );
        $this->assertSame([], $longLines, 'toTxRep must throw before writing');

        $this->assertThrowsFixedLength(
            fn () => XdrLedgerKeyContractCode::fromTxRep(['k.hash' => self::HEX_31], 'k'),
            31,
            32,
            'XdrLedgerKeyContractCode fromTxRep 31B'
        );
        $this->assertThrowsFixedLength(
            fn () => XdrLedgerKeyContractCode::fromTxRep(['k.hash' => self::HEX_33], 'k'),
            33,
            32,
            'XdrLedgerKeyContractCode fromTxRep 33B'
        );
        $this->assertThrowsFixedLength(
            fn () => XdrLedgerKeyContractCode::fromTxRep(['k.hash' => '0'], 'k'),
            0,
            32,
            'XdrLedgerKeyContractCode fromTxRep 0'
        );
    }

    public function testSignatureHintJsonRejectsWrongLength(): void
    {
        $short = new XdrDecoratedSignature("\x01\x02\x03", "\xaa");
        $this->assertThrowsFixedLength(fn () => $short->toJson(), 3, 4, 'XdrDecoratedSignature hint toJson 3B');

        $long = new XdrDecoratedSignature("\x01\x02\x03\x04\x05", "\xaa");
        $this->assertThrowsFixedLength(fn () => $long->toJson(), 5, 4, 'XdrDecoratedSignature hint toJson 5B');

        $this->assertThrowsFixedLength(
            fn () => XdrDecoratedSignature::fromJson('{"hint":"010203","signature":"aa"}'),
            3,
            4,
            'XdrDecoratedSignature hint fromJson 3B'
        );
        $this->assertThrowsFixedLength(
            fn () => XdrDecoratedSignature::fromJson('{"hint":"0102030405","signature":"aa"}'),
            5,
            4,
            'XdrDecoratedSignature hint fromJson 5B'
        );
    }

    public function testSignatureHintTxRepRejectsWrongLength(): void
    {
        $shortLines = [];
        $short = new XdrDecoratedSignature("\x01\x02\x03", "\xaa");
        $this->assertThrowsFixedLength(
            function () use ($short, &$shortLines): void {
                $short->toTxRep('s', $shortLines);
            },
            3,
            4,
            'XdrDecoratedSignature hint toTxRep 3B'
        );
        $this->assertSame([], $shortLines, 'toTxRep must throw before writing');

        $longLines = [];
        $long = new XdrDecoratedSignature("\x01\x02\x03\x04\x05", "\xaa");
        $this->assertThrowsFixedLength(
            function () use ($long, &$longLines): void {
                $long->toTxRep('s', $longLines);
            },
            5,
            4,
            'XdrDecoratedSignature hint toTxRep 5B'
        );
        $this->assertSame([], $longLines, 'toTxRep must throw before writing');

        $this->assertThrowsFixedLength(
            fn () => XdrDecoratedSignature::fromTxRep(['s.hint' => '010203', 's.signature' => 'aa'], 's'),
            3,
            4,
            'XdrDecoratedSignature hint fromTxRep 3B'
        );
        $this->assertThrowsFixedLength(
            fn () => XdrDecoratedSignature::fromTxRep(['s.hint' => '0102030405', 's.signature' => 'aa'], 's'),
            5,
            4,
            'XdrDecoratedSignature hint fromTxRep 5B'
        );
        $this->assertThrowsFixedLength(
            fn () => XdrDecoratedSignature::fromTxRep(['s.hint' => '0', 's.signature' => 'aa'], 's'),
            0,
            4,
            'XdrDecoratedSignature hint fromTxRep 0'
        );
    }

    public function testInlineFixedOpaqueStructJsonRejectsWrongLength(): void
    {
        $short = new XdrShortHashSeed(hex2bin(self::HEX_15));
        $this->assertThrowsFixedLength(fn () => $short->toJson(), 15, 16, 'XdrShortHashSeed toJson 15B');

        $long = new XdrShortHashSeed(hex2bin(self::HEX_17));
        $this->assertThrowsFixedLength(fn () => $long->toJson(), 17, 16, 'XdrShortHashSeed toJson 17B');

        $this->assertThrowsFixedLength(
            fn () => XdrShortHashSeed::fromJson('{"seed":"' . self::HEX_15 . '"}'),
            15,
            16,
            'XdrShortHashSeed fromJson 15B'
        );
        $this->assertThrowsFixedLength(
            fn () => XdrShortHashSeed::fromJson('{"seed":"' . self::HEX_17 . '"}'),
            17,
            16,
            'XdrShortHashSeed fromJson 17B'
        );
    }

    public function testMemoUnionArmJsonRejectsWrongLength(): void
    {
        $hashShort = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_HASH));
        $hashShort->setHash(hex2bin(self::HEX_31));
        $this->assertThrowsFixedLength(fn () => $hashShort->toJson(), 31, 32, 'XdrMemo hash toJson 31B');

        $hashLong = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_HASH));
        $hashLong->setHash(hex2bin(self::HEX_33));
        $this->assertThrowsFixedLength(fn () => $hashLong->toJson(), 33, 32, 'XdrMemo hash toJson 33B');

        $this->assertThrowsFixedLength(
            fn () => XdrMemo::fromJson('{"hash":"' . self::HEX_31 . '"}'),
            31,
            32,
            'XdrMemo hash fromJson 31B'
        );
        $this->assertThrowsFixedLength(
            fn () => XdrMemo::fromJson('{"hash":"' . self::HEX_33 . '"}'),
            33,
            32,
            'XdrMemo hash fromJson 33B'
        );

        $retShort = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_RETURN));
        $retShort->setReturnHash(hex2bin(self::HEX_31));
        $this->assertThrowsFixedLength(fn () => $retShort->toJson(), 31, 32, 'XdrMemo return toJson 31B');

        $retLong = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_RETURN));
        $retLong->setReturnHash(hex2bin(self::HEX_33));
        $this->assertThrowsFixedLength(fn () => $retLong->toJson(), 33, 32, 'XdrMemo return toJson 33B');

        $this->assertThrowsFixedLength(
            fn () => XdrMemo::fromJson('{"return":"' . self::HEX_31 . '"}'),
            31,
            32,
            'XdrMemo return fromJson 31B'
        );
        $this->assertThrowsFixedLength(
            fn () => XdrMemo::fromJson('{"return":"' . self::HEX_33 . '"}'),
            33,
            32,
            'XdrMemo return fromJson 33B'
        );
    }

    public function testMemoUnionArmTxRepRejectsWrongLength(): void
    {
        $hashShort = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_HASH));
        $hashShort->setHash(hex2bin(self::HEX_31));
        $lines = [];
        $this->assertThrowsFixedLength(
            function () use ($hashShort, &$lines): void {
                $hashShort->toTxRep('m', $lines);
            },
            31,
            32,
            'XdrMemo hash toTxRep 31B'
        );
        $this->assertArrayNotHasKey('m.hash', $lines);

        $hashLong = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_HASH));
        $hashLong->setHash(hex2bin(self::HEX_33));
        $lines = [];
        $this->assertThrowsFixedLength(
            function () use ($hashLong, &$lines): void {
                $hashLong->toTxRep('m', $lines);
            },
            33,
            32,
            'XdrMemo hash toTxRep 33B'
        );

        $this->assertThrowsFixedLength(
            fn () => XdrMemo::fromTxRep(['m.type' => 'MEMO_HASH', 'm.hash' => self::HEX_31], 'm'),
            31,
            32,
            'XdrMemo hash fromTxRep 31B'
        );
        $this->assertThrowsFixedLength(
            fn () => XdrMemo::fromTxRep(['m.type' => 'MEMO_HASH', 'm.hash' => self::HEX_33], 'm'),
            33,
            32,
            'XdrMemo hash fromTxRep 33B'
        );
        $this->assertThrowsFixedLength(
            fn () => XdrMemo::fromTxRep(['m.type' => 'MEMO_HASH', 'm.hash' => '0'], 'm'),
            0,
            32,
            'XdrMemo hash fromTxRep 0'
        );

        $retShort = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_RETURN));
        $retShort->setReturnHash(hex2bin(self::HEX_31));
        $lines = [];
        $this->assertThrowsFixedLength(
            function () use ($retShort, &$lines): void {
                $retShort->toTxRep('m', $lines);
            },
            31,
            32,
            'XdrMemo return toTxRep 31B'
        );
        $this->assertArrayNotHasKey('m.retHash', $lines);

        $this->assertThrowsFixedLength(
            fn () => XdrMemo::fromTxRep(['m.type' => 'MEMO_RETURN', 'm.retHash' => self::HEX_33], 'm'),
            33,
            32,
            'XdrMemo return fromTxRep 33B'
        );
    }

    public function testPeerAddressIpUnionArmJsonRejectsWrongLength(): void
    {
        $v4Short = new XdrPeerAddressIp(new XdrIPAddrType(XdrIPAddrType::IPv4));
        $v4Short->setIpv4("\x7f\x00\x00");
        $this->assertThrowsFixedLength(fn () => $v4Short->toJson(), 3, 4, 'XdrPeerAddressIp ipv4 toJson 3B');

        $v4Long = new XdrPeerAddressIp(new XdrIPAddrType(XdrIPAddrType::IPv4));
        $v4Long->setIpv4("\x7f\x00\x00\x01\x02");
        $this->assertThrowsFixedLength(fn () => $v4Long->toJson(), 5, 4, 'XdrPeerAddressIp ipv4 toJson 5B');

        $this->assertThrowsFixedLength(
            fn () => XdrPeerAddressIp::fromJson('{"i_pv4":"7f0000"}'),
            3,
            4,
            'XdrPeerAddressIp ipv4 fromJson 3B'
        );
        $this->assertThrowsFixedLength(
            fn () => XdrPeerAddressIp::fromJson('{"i_pv4":"7f00000102"}'),
            5,
            4,
            'XdrPeerAddressIp ipv4 fromJson 5B'
        );

        $v6Short = new XdrPeerAddressIp(new XdrIPAddrType(XdrIPAddrType::IPv6));
        $v6Short->setIpv6(hex2bin(self::HEX_15));
        $this->assertThrowsFixedLength(fn () => $v6Short->toJson(), 15, 16, 'XdrPeerAddressIp ipv6 toJson 15B');

        $this->assertThrowsFixedLength(
            fn () => XdrPeerAddressIp::fromJson('{"i_pv6":"' . self::HEX_17 . '"}'),
            17,
            16,
            'XdrPeerAddressIp ipv6 fromJson 17B'
        );
    }

    public function testArrayElementFixedOpaqueJsonRejectsWrongLength(): void
    {
        $freezeShort = new XdrFreezeBypassTxs([hex2bin(self::HEX_31)]);
        $this->assertThrowsFixedLength(fn () => $freezeShort->toJson(), 31, 32, 'XdrFreezeBypassTxs toJson 31B');

        $freezeLong = new XdrFreezeBypassTxs([hex2bin(self::HEX_33)]);
        $this->assertThrowsFixedLength(fn () => $freezeLong->toJson(), 33, 32, 'XdrFreezeBypassTxs toJson 33B');

        $this->assertThrowsFixedLength(
            fn () => XdrFreezeBypassTxs::fromJson('{"tx_hashes":["' . self::HEX_31 . '"]}'),
            31,
            32,
            'XdrFreezeBypassTxs fromJson 31B'
        );
        $this->assertThrowsFixedLength(
            fn () => XdrFreezeBypassTxs::fromJson('{"tx_hashes":["' . self::HEX_33 . '"]}'),
            33,
            32,
            'XdrFreezeBypassTxs fromJson 33B'
        );

        $advertShort = new XdrTxAdvertVector([hex2bin(self::HEX_31)]);
        $this->assertThrowsFixedLength(fn () => $advertShort->toJson(), 31, 32, 'XdrTxAdvertVector toJson 31B');

        $this->assertThrowsFixedLength(
            fn () => XdrTxAdvertVector::fromJson('["' . self::HEX_33 . '"]'),
            33,
            32,
            'XdrTxAdvertVector fromJson 33B'
        );
    }

    public function testPoolShareTxRepRejectsWrongLength(): void
    {
        $poolShort = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_POOL_SHARE));
        $poolShort->setLiquidityPoolID(hex2bin(self::HEX_31));
        $this->assertThrowsFixedLength(
            fn () => TxRepHelper::formatTrustlineAsset($poolShort),
            31,
            32,
            'TxRepHelper::formatTrustlineAsset pool 31B'
        );

        $poolLong = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_POOL_SHARE));
        $poolLong->setLiquidityPoolID(hex2bin(self::HEX_33));
        $this->assertThrowsFixedLength(
            fn () => TxRepHelper::formatTrustlineAsset($poolLong),
            33,
            32,
            'TxRepHelper::formatTrustlineAsset pool 33B'
        );

        $lkShort = new XdrLedgerKeyLiquidityPool(hex2bin(self::HEX_31));
        $lines = [];
        $this->assertThrowsFixedLength(
            function () use ($lkShort, &$lines): void {
                $lkShort->toTxRep('lp', $lines);
            },
            31,
            32,
            'XdrLedgerKeyLiquidityPool toTxRep 31B'
        );
        $this->assertSame([], $lines);

        $this->assertThrowsFixedLength(
            fn () => XdrLedgerKeyLiquidityPool::fromTxRep(['lp.liquidityPoolID' => self::HEX_33], 'lp'),
            33,
            32,
            'XdrLedgerKeyLiquidityPool fromTxRep 33B'
        );
        $this->assertThrowsFixedLength(
            fn () => XdrLedgerKeyLiquidityPool::fromTxRep(['lp.liquidityPoolID' => '0'], 'lp'),
            0,
            32,
            'XdrLedgerKeyLiquidityPool fromTxRep 0'
        );
    }

    public function testStorageJsonReadersRejectWrongLength(): void
    {
        $this->assertThrowsFixedLength(
            fn () => XdrConfigUpgradeSetKey::fromJson(
                '{"contract_id":"' . self::CONTRACT_STRKEY . '","content_hash":"' . self::HEX_31 . '"}'
            ),
            31,
            32,
            'XdrConfigUpgradeSetKey fromJson content_hash 31B'
        );
        $this->assertThrowsFixedLength(
            fn () => XdrConfigUpgradeSetKey::fromJson(
                '{"contract_id":"' . self::CONTRACT_STRKEY . '","content_hash":"' . self::HEX_33 . '"}'
            ),
            33,
            32,
            'XdrConfigUpgradeSetKey fromJson content_hash 33B'
        );

        $this->assertThrowsFixedLength(
            fn () => XdrInnerTransactionResultPair::fromJson(
                '{"transaction_hash":"' . self::HEX_31 . '","result":{"fee_charged":"100","result":"tx_too_early","ext":"v0"}}'
            ),
            31,
            32,
            'XdrInnerTransactionResultPair fromJson transaction_hash 31B'
        );
        $this->assertThrowsFixedLength(
            fn () => XdrInnerTransactionResultPair::fromJson(
                '{"transaction_hash":"' . self::HEX_33 . '","result":{"fee_charged":"100","result":"tx_too_early","ext":"v0"}}'
            ),
            33,
            32,
            'XdrInnerTransactionResultPair fromJson transaction_hash 33B'
        );
    }

    public function testTransactionV0SourceAccountRejectsWrongLength(): void
    {
        $txShort = new XdrTransactionV0(hex2bin(self::HEX_31), new XdrSequenceNumber(new BigInteger(1)), []);
        $this->assertThrowsFixedLength(fn () => $txShort->toJson(), 31, 32, 'XdrTransactionV0 toJson 31B');

        $txLong = new XdrTransactionV0(hex2bin(self::HEX_33), new XdrSequenceNumber(new BigInteger(1)), []);
        $this->assertThrowsFixedLength(fn () => $txLong->toJson(), 33, 32, 'XdrTransactionV0 toJson 33B');

        $this->assertThrowsFixedLength(
            fn () => XdrTransactionV0::fromJson(
                '{"source_account_ed25519":"' . self::HEX_31 . '","fee":100,"seq_num":"1","time_bounds":null,"memo":"none","operations":[],"ext":"v0"}'
            ),
            31,
            32,
            'XdrTransactionV0 fromJson 31B'
        );
        $this->assertThrowsFixedLength(
            fn () => XdrTransactionV0::fromJson(
                '{"source_account_ed25519":"' . self::HEX_33 . '","fee":100,"seq_num":"1","time_bounds":null,"memo":"none","operations":[],"ext":"v0"}'
            ),
            33,
            32,
            'XdrTransactionV0 fromJson 33B'
        );

        $lines = [];
        $this->assertThrowsFixedLength(
            function () use ($txShort, &$lines): void {
                $txShort->toTxRep('tx', $lines);
            },
            31,
            32,
            'XdrTransactionV0 toTxRep 31B'
        );
        $this->assertSame([], $lines);

        $this->assertThrowsFixedLength(
            fn () => XdrTransactionV0::fromTxRep([
                'tx.sourceAccountEd25519' => self::HEX_33,
                'tx.fee' => '100',
                'tx.seqNum' => '1',
                'tx.timeBounds._present' => 'false',
                'tx.memo.type' => 'MEMO_NONE',
                'tx.operations.len' => '0',
                'tx.ext.v' => '0',
            ], 'tx'),
            33,
            32,
            'XdrTransactionV0 fromTxRep 33B'
        );
    }

    public function testAssetCode4RejectsOverFourBytes(): void
    {
        $issuer = XdrAccountID::fromAccountId(self::ISSUER);

        foreach (['ABCDE', "USD\x00\x00"] as $badCode) {
            $a4 = new XdrAssetAlphaNum4($badCode, $issuer);
            $this->assertThrowsFixedLength(fn () => $a4->toJson(), 5, 4, 'XdrAssetAlphaNum4 toJson');

            $lines = [];
            $this->assertThrowsFixedLength(
                function () use ($a4, &$lines): void {
                    $a4->toTxRep('a4', $lines);
                },
                5,
                4,
                'XdrAssetAlphaNum4 toTxRep'
            );
            $this->assertSame([], $lines);

            $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
            $asset->setAlphaNum4($a4);
            $this->assertThrowsFixedLength(fn () => TxRepHelper::formatAsset($asset), 5, 4, 'TxRepHelper::formatAsset a4');

            $ct = new XdrChangeTrustAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
            $ct->setAlphaNum4($a4);
            $this->assertThrowsFixedLength(fn () => TxRepHelper::formatChangeTrustAsset($ct), 5, 4, 'TxRepHelper::formatChangeTrustAsset a4');

            $tl = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
            $tl->setAlphaNum4($a4);
            $this->assertThrowsFixedLength(fn () => TxRepHelper::formatTrustlineAsset($tl), 5, 4, 'TxRepHelper::formatTrustlineAsset a4');

            $allow = new XdrAllowTrustOperationAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
            $allow->setAssetCode4($badCode);
            $this->assertThrowsFixedLength(fn () => $allow->toJson(), 5, 4, 'XdrAllowTrustOperationAsset toJson a4');
            $this->assertThrowsFixedLength(fn () => TxRepHelper::formatAllowTrustAsset($allow), 5, 4, 'TxRepHelper::formatAllowTrustAsset a4');

            $allowBase = new XdrAllowTrustOperationAssetBase(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
            $allowBase->setAssetCode4($badCode);
            $baseLines = [];
            $this->assertThrowsFixedLength(
                function () use ($allowBase, &$baseLines): void {
                    $allowBase->toTxRep('at', $baseLines);
                },
                5,
                4,
                'XdrAllowTrustOperationAssetBase toTxRep a4'
            );
        }

        $this->assertThrowsFixedLength(
            fn () => XdrAssetAlphaNum4::fromTxRep(['a4.assetCode' => '4142434445', 'a4.issuer' => self::ISSUER], 'a4'),
            5,
            4,
            'XdrAssetAlphaNum4 fromTxRep 5B'
        );
        $this->assertThrowsFixedLength(
            fn () => XdrAllowTrustOperationAssetBase::fromTxRep([
                'at.type' => 'ASSET_TYPE_CREDIT_ALPHANUM4',
                'at.assetCode4' => '4142434445',
            ], 'at'),
            5,
            4,
            'XdrAllowTrustOperationAssetBase fromTxRep 5B'
        );
    }

    public function testAssetCode12RejectsOverTwelveBytes(): void
    {
        $issuer = XdrAccountID::fromAccountId(self::ISSUER);

        foreach (['ABCDEFGHIJKLM', "LONGASSET1\x00\x00\x00"] as $badCode) {
            $a12 = new XdrAssetAlphaNum12($badCode, $issuer);
            $this->assertThrowsFixedLength(fn () => $a12->toJson(), 13, 12, 'XdrAssetAlphaNum12 toJson');

            $lines = [];
            $this->assertThrowsFixedLength(
                function () use ($a12, &$lines): void {
                    $a12->toTxRep('a12', $lines);
                },
                13,
                12,
                'XdrAssetAlphaNum12 toTxRep'
            );
            $this->assertSame([], $lines);

            $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
            $asset->setAlphaNum12($a12);
            $this->assertThrowsFixedLength(fn () => TxRepHelper::formatAsset($asset), 13, 12, 'TxRepHelper::formatAsset a12');

            $ct = new XdrChangeTrustAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
            $ct->setAlphaNum12($a12);
            $this->assertThrowsFixedLength(fn () => TxRepHelper::formatChangeTrustAsset($ct), 13, 12, 'TxRepHelper::formatChangeTrustAsset a12');

            $tl = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
            $tl->setAlphaNum12($a12);
            $this->assertThrowsFixedLength(fn () => TxRepHelper::formatTrustlineAsset($tl), 13, 12, 'TxRepHelper::formatTrustlineAsset a12');

            $allow = new XdrAllowTrustOperationAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
            $allow->setAssetCode12($badCode);
            $this->assertThrowsFixedLength(fn () => $allow->toJson(), 13, 12, 'XdrAllowTrustOperationAsset toJson a12');
            $this->assertThrowsFixedLength(fn () => TxRepHelper::formatAllowTrustAsset($allow), 13, 12, 'TxRepHelper::formatAllowTrustAsset a12');

            $allowBase = new XdrAllowTrustOperationAssetBase(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
            $allowBase->setAssetCode12($badCode);
            $baseLines = [];
            $this->assertThrowsFixedLength(
                function () use ($allowBase, &$baseLines): void {
                    $allowBase->toTxRep('at', $baseLines);
                },
                13,
                12,
                'XdrAllowTrustOperationAssetBase toTxRep a12'
            );
        }

        $this->assertThrowsFixedLength(
            fn () => XdrAssetAlphaNum12::fromTxRep([
                'a12.assetCode' => '4142434445464748494a4b4c4d',
                'a12.issuer' => self::ISSUER,
            ], 'a12'),
            13,
            12,
            'XdrAssetAlphaNum12 fromTxRep 13B'
        );
        $this->assertThrowsFixedLength(
            fn () => XdrAllowTrustOperationAssetBase::fromTxRep([
                'at.type' => 'ASSET_TYPE_CREDIT_ALPHANUM12',
                'at.assetCode12' => '4142434445464748494a4b4c4d',
            ], 'at'),
            13,
            12,
            'XdrAllowTrustOperationAssetBase fromTxRep 13B'
        );
    }
}
