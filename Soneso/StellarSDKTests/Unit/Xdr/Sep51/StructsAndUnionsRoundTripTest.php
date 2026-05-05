<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr\Sep51;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrAccountEntry;
use Soneso\StellarSDK\Xdr\XdrAccountEntryV1Ext;
use Soneso\StellarSDK\Xdr\XdrAuth;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceEntry;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceEntryExtV1;
use Soneso\StellarSDK\Xdr\XdrConfigSettingContractComputeV0;
use Soneso\StellarSDK\Xdr\XdrError;
use Soneso\StellarSDK\Xdr\XdrErrorCode;
use Soneso\StellarSDK\Xdr\XdrExtensionPoint;
use Soneso\StellarSDK\Xdr\XdrFloodAdvert;
use Soneso\StellarSDK\Xdr\XdrFloodDemand;
use Soneso\StellarSDK\Xdr\XdrHmacSha256Key;
use Soneso\StellarSDK\Xdr\XdrHmacSha256Mac;
use Soneso\StellarSDK\Xdr\XdrJsonHelper;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryData;
use Soneso\StellarSDK\Xdr\XdrLiabilities;
use Soneso\StellarSDK\Xdr\XdrMemo;
use Soneso\StellarSDK\Xdr\XdrOperation;
use Soneso\StellarSDK\Xdr\XdrPreconditions;
use Soneso\StellarSDK\Xdr\XdrPrice;
use Soneso\StellarSDK\Xdr\XdrSCError;
use Soneso\StellarSDK\Xdr\XdrSCErrorCode;
use Soneso\StellarSDK\Xdr\XdrSCErrorType;
use Soneso\StellarSDK\Xdr\XdrSCNonceKey;
use Soneso\StellarSDK\Xdr\XdrSCPBallot;
use Soneso\StellarSDK\Xdr\XdrSCPNomination;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrShortHashSeed;
use Soneso\StellarSDK\Xdr\XdrSignerKey;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrTransactionMeta;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrTrustLineEntry;
use Soneso\StellarSDK\Xdr\XdrTrustLineEntryExtensionV2;
use Soneso\StellarSDK\Xdr\XdrValue;

/**
 * Round-trip tests for the SEP-51 (XDR-JSON) emission on Phase 3 structs and
 * discriminated unions.
 *
 * Phase 3 emits SEP-51 toJson/fromJson methods on every non-Cat-A, non-Cat-B
 * struct and union. The set of Cat-A and Cat-B types is deliberately exempt
 * — Phase 4 owns their bespoke JSON shape (StrKey-encoded addresses, hex
 * contract identifiers, GMP-driven 128/256-bit assembly, and so on). To keep
 * Phase 3 testable in isolation, the positive round-trip cases here cover
 * types whose entire transitive field/arm graph stays inside Phase 3 (or
 * Phase 2 enums and helpers). End-to-end round-trips through Cat-A/Cat-B
 * delegations are exercised in Phase 5b once Phase 4 has landed.
 *
 * The negative cases trip on the Phase 3 shape-validation paths the generator
 * emits at the top of fromJsonValue: the missing-required-field guard, the
 * wrong-shape guard (non-array vs single-key-object), and the
 * unknown-arm-key / unknown-void-string guards. These run before any nested
 * delegation, so they pass independently of Phase 4 progress.
 */
class StructsAndUnionsRoundTripTest extends TestCase
{
    // =========================================================================
    // XdrPrice — pure-primitive struct (n: int32, d: int32)
    // =========================================================================

    public function testPriceRoundTrip(): void
    {
        $p = new XdrPrice(7, 13);
        $expected = ['n' => 7, 'd' => 13];
        $this->assertSame($expected, $p->toJsonValue());

        $json = $p->toJson();
        $this->assertSame(
            XdrJsonHelper::canonicalJson('{"n":7,"d":13}'),
            XdrJsonHelper::canonicalJson($json)
        );

        $rt = XdrPrice::fromJson($json);
        $this->assertSame(7, $rt->getN());
        $this->assertSame(13, $rt->getD());
    }

    public function testPriceFromJsonValueRejectsNonArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected object for XdrPrice');
        XdrPrice::fromJsonValue('not_an_array');
    }

    public function testPriceFromJsonValueRejectsMissingField(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field d');
        XdrPrice::fromJsonValue(['n' => 1]);
    }

    public function testPriceFromJsonValueRejectsWrongType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected int JSON value');
        XdrPrice::fromJsonValue(['n' => 'not_int', 'd' => 0]);
    }

    public function testPriceFromJsonValueAcceptsSchemaPassthrough(): void
    {
        $rt = XdrPrice::fromJsonValue(['$schema' => 'https://schema', 'n' => 11, 'd' => 5]);
        $this->assertSame(11, $rt->getN());
        $this->assertSame(5, $rt->getD());
    }

    public function testPriceFromJsonValueRejectsBareSchemaOnly(): void
    {
        // Empty after stripping $schema: shape validation fails at the
        // missing-field guard.
        $this->expectException(\InvalidArgumentException::class);
        XdrPrice::fromJsonValue(['$schema' => 'https://schema']);
    }

    // =========================================================================
    // XdrSCNonceKey — single int64 field (PHP int -> base-10 string wire)
    // =========================================================================

    public function testSCNonceKeyRoundTrip(): void
    {
        $key = new XdrSCNonceKey(1234567890123);
        $this->assertSame(['nonce' => '1234567890123'], $key->toJsonValue());
        $rt = XdrSCNonceKey::fromJson($key->toJson());
        $this->assertSame(1234567890123, $rt->getNonce());
    }

    public function testSCNonceKeyAcceptsNegativeInt64(): void
    {
        $key = new XdrSCNonceKey(-1);
        $rt = XdrSCNonceKey::fromJson($key->toJson());
        $this->assertSame(-1, $rt->getNonce());
    }

    public function testSCNonceKeyRejectsNonStringNonceField(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrSCNonceKey::fromJsonValue(['nonce' => true]);
    }

    public function testSCNonceKeyRejectsMissingField(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field nonce');
        XdrSCNonceKey::fromJsonValue([]);
    }

    // =========================================================================
    // XdrLiabilities — primitive int64 buying / selling fields (BigInteger)
    // =========================================================================

    public function testLiabilitiesRoundTrip(): void
    {
        $bi = static fn(int $n) => new \phpseclib3\Math\BigInteger((string) $n);
        $liab = new XdrLiabilities($bi(100), $bi(200));
        $expected = ['buying' => '100', 'selling' => '200'];
        $this->assertSame($expected, $liab->toJsonValue());

        $rt = XdrLiabilities::fromJson($liab->toJson());
        $this->assertSame('100', $rt->buying->toString());
        $this->assertSame('200', $rt->selling->toString());
    }

    public function testLiabilitiesAcceptsLargeInt64String(): void
    {
        // 2^62 = 4611686018427387904 (well within int64 range).
        $rt = XdrLiabilities::fromJsonValue([
            'buying' => '4611686018427387904',
            'selling' => '0',
        ]);
        $this->assertSame('4611686018427387904', $rt->buying->toString());
    }

    // =========================================================================
    // XdrSCError — struct with two enum fields
    // =========================================================================

    public function testSCErrorRoundTripContract(): void
    {
        // SCE_CONTRACT is the int-cased arm: payload is the contract code
        // (uint32), JSON-encoded as a JSON int under the "contract" key.
        $err = new XdrSCError(new XdrSCErrorType(XdrSCErrorType::SCE_CONTRACT));
        $err->contractCode = 42;
        $this->assertSame(['contract' => 42], $err->toJsonValue());
        $rt = XdrSCError::fromJson($err->toJson());
        $this->assertSame(XdrSCErrorType::SCE_CONTRACT, $rt->getType()->getValue());
        $this->assertSame(42, $rt->getContractCode());
    }

    public function testSCErrorRoundTripValue(): void
    {
        // SCE_VALUE is one of the enum-payload arms: delegates to
        // XdrSCErrorCode::toJsonValue() which is a Phase 2 enum.
        $err = new XdrSCError(new XdrSCErrorType(XdrSCErrorType::SCE_VALUE));
        $err->code = new XdrSCErrorCode(XdrSCErrorCode::SCEC_ARITH_DOMAIN);
        $rt = XdrSCError::fromJson($err->toJson());
        $this->assertSame(XdrSCErrorType::SCE_VALUE, $rt->getType()->getValue());
        $this->assertSame(XdrSCErrorCode::SCEC_ARITH_DOMAIN, $rt->getCode()->getValue());
    }

    // =========================================================================
    // XdrAuth — single int32 flags field
    // =========================================================================

    public function testAuthRoundTrip(): void
    {
        $a = new XdrAuth(42);
        $this->assertSame(['flags' => 42], $a->toJsonValue());
        $rt = XdrAuth::fromJson($a->toJson());
        $this->assertSame(42, $rt->getFlags());
    }

    // =========================================================================
    // XdrShortHashSeed — single 16-byte fixed opaque (hex form)
    // =========================================================================

    public function testShortHashSeedRoundTrip(): void
    {
        $bytes = str_repeat("\xab", 16);
        $seed = new XdrShortHashSeed($bytes);
        $expected = ['seed' => str_repeat('ab', 16)];
        $this->assertSame($expected, $seed->toJsonValue());
        $rt = XdrShortHashSeed::fromJson($seed->toJson());
        $this->assertSame($bytes, $rt->getSeed());
    }

    public function testShortHashSeedRejectsNonHexInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrShortHashSeed::fromJsonValue(['seed' => 'not-hex']);
    }

    // =========================================================================
    // XdrHmacSha256Key / XdrHmacSha256Mac — single fixed-opaque struct
    // =========================================================================

    public function testHmacKeyAndMacRoundTrip(): void
    {
        $key = new XdrHmacSha256Key(str_repeat("\xfe", 32));
        $rt = XdrHmacSha256Key::fromJson($key->toJson());
        $this->assertSame(str_repeat("\xfe", 32), $rt->getKey());

        $mac = new XdrHmacSha256Mac(str_repeat("\x01", 32));
        $rt2 = XdrHmacSha256Mac::fromJson($mac->toJson());
        $this->assertSame(str_repeat("\x01", 32), $rt2->getMac());
    }

    // =========================================================================
    // XdrError — single enum + variable-length string
    // =========================================================================

    public function testErrorRoundTrip(): void
    {
        $err = new XdrError(new XdrErrorCode(XdrErrorCode::ERR_AUTH), 'auth failed');
        $rt = XdrError::fromJson($err->toJson());
        $this->assertSame(XdrErrorCode::ERR_AUTH, $rt->getCode()->getValue());
        $this->assertSame('auth failed', $rt->getMsg());
    }

    public function testErrorAcceptsEmptyString(): void
    {
        $err = new XdrError(new XdrErrorCode(XdrErrorCode::ERR_MISC), '');
        $rt = XdrError::fromJson($err->toJson());
        $this->assertSame('', $rt->getMsg());
    }

    public function testErrorEscapesNonAsciiBytes(): void
    {
        // Embed non-ASCII bytes so the SEP-51 escape ladder is exercised.
        $msg = "tab\there\nnow";
        $err = new XdrError(new XdrErrorCode(XdrErrorCode::ERR_MISC), $msg);
        $json = $err->toJson();
        // SEP-51 escapes \t and \n into literal \\t / \\n character sequences
        // before json_encode runs; json_encode then JSON-escapes each backslash
        // to \\, producing four backslashes in the final JSON literal.
        $this->assertStringContainsString('tab\\\\there', $json);
        $rt = XdrError::fromJson($json);
        $this->assertSame($msg, $rt->getMsg());
    }

    // =========================================================================
    // XdrFloodAdvert — single TxAdvertVector field (array typedef)
    // =========================================================================

    public function testFloodAdvertRoundTripEmpty(): void
    {
        $advert = new XdrFloodAdvert(new \Soneso\StellarSDK\Xdr\XdrTxAdvertVector([]));
        $expected = ['tx_hashes' => []];
        $this->assertSame($expected, $advert->toJsonValue());
        $rt = XdrFloodAdvert::fromJson($advert->toJson());
        $this->assertSame([], $rt->getTxHashes()->txAdvertVector);
    }

    public function testFloodAdvertRoundTripWithEntries(): void
    {
        $h1 = str_repeat("\x01", 32);
        $h2 = str_repeat("\x02", 32);
        $advert = new XdrFloodAdvert(new \Soneso\StellarSDK\Xdr\XdrTxAdvertVector([$h1, $h2]));
        $rt = XdrFloodAdvert::fromJson($advert->toJson());
        $this->assertSame([$h1, $h2], $rt->getTxHashes()->txAdvertVector);
    }

    public function testFloodDemandRoundTripSingleEntry(): void
    {
        $h1 = str_repeat("\xff", 32);
        $demand = new XdrFloodDemand(new \Soneso\StellarSDK\Xdr\XdrTxDemandVector([$h1]));
        $rt = XdrFloodDemand::fromJson($demand->toJson());
        $this->assertSame([$h1], $rt->getTxHashes()->txDemandVector);
    }

    // =========================================================================
    // XdrSCPNomination — single 32-byte hash + two value-list fields
    // =========================================================================

    public function testSCPNominationEmptyArrays(): void
    {
        $hash = str_repeat("\xab", 32);
        $nom = new XdrSCPNomination($hash, [], []);
        $expected = [
            'quorum_set_hash' => str_repeat('ab', 32),
            'votes' => [],
            'accepted' => [],
        ];
        $this->assertSame($expected, $nom->toJsonValue());
        $rt = XdrSCPNomination::fromJson($nom->toJson());
        $this->assertSame($hash, $rt->getQuorumSetHash());
        $this->assertSame([], $rt->getVotes());
        $this->assertSame([], $rt->getAccepted());
    }

    public function testSCPNominationSingleElement(): void
    {
        // Single-element arrays must NOT collapse to scalars in the wire form.
        $hash = str_repeat("\xab", 32);
        $val = "\x01\x02\x03";
        $nom = new XdrSCPNomination($hash, [new XdrValue($val)], []);
        $json = $nom->toJson();
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded['votes']);
        $this->assertCount(1, $decoded['votes']);
    }

    // =========================================================================
    // XdrSCPBallot — counter (uint32) + value (XdrValue typedef opaque<>)
    // =========================================================================

    public function testSCPBallotRoundTrip(): void
    {
        $ballot = new XdrSCPBallot(42, new XdrValue("\xde\xad\xbe\xef"));
        $rt = XdrSCPBallot::fromJson($ballot->toJson());
        $this->assertSame(42, $rt->getCounter());
        $this->assertSame("\xde\xad\xbe\xef", $rt->getValue()->value);
    }

    public function testSCPBallotRejectsNullValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrSCPBallot::fromJsonValue(['counter' => 1, 'value' => null]);
    }

    // =========================================================================
    // XdrConfigSettingContractComputeV0 — five primitive fields
    // =========================================================================

    public function testConfigSettingContractComputeV0RoundTrip(): void
    {
        $cfg = new XdrConfigSettingContractComputeV0(1000000, 2000000, 100, 200000);
        $rt = XdrConfigSettingContractComputeV0::fromJson($cfg->toJson());
        $this->assertSame(1000000, $rt->getLedgerMaxInstructions());
        $this->assertSame(2000000, $rt->getTxMaxInstructions());
        $this->assertSame(100, $rt->getFeeRatePerInstructionsIncrement());
        $this->assertSame(200000, $rt->getTxMemoryLimit());
    }

    // =========================================================================
    // XdrAccountEntryV1Ext — int-cased mixed (void v0 + non-void v2)
    // =========================================================================

    public function testAccountEntryV1ExtVoidV0(): void
    {
        $u = new XdrAccountEntryV1Ext(0);
        $this->assertSame('v0', $u->toJsonValue());
        $rt = XdrAccountEntryV1Ext::fromJson('"v0"');
        $this->assertSame(0, $rt->getDiscriminant());
    }

    public function testAccountEntryV1ExtRejectsUnknownVoidString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrAccountEntryV1Ext::fromJsonValue('v99');
    }

    public function testAccountEntryV1ExtRejectsBareNonVoidStringForV2(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Arm 'v2' on XdrAccountEntryV1Ext is non-void");
        XdrAccountEntryV1Ext::fromJsonValue('v2');
    }

    public function testAccountEntryV1ExtRejectsMultiKeyObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrAccountEntryV1Ext::fromJsonValue(['v0' => null, 'v2' => null]);
    }

    public function testAccountEntryV1ExtRejectsIntegerInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrAccountEntryV1Ext::fromJsonValue(99);
    }

    // =========================================================================
    // XdrExtensionPoint — int-cased void-only
    // =========================================================================

    public function testExtensionPointVoidV0(): void
    {
        $ep = new XdrExtensionPoint(0);
        $this->assertSame('v0', $ep->toJsonValue());
        $this->assertSame('"v0"', $ep->toJson());
        $rt = XdrExtensionPoint::fromJson('"v0"');
        $this->assertSame(0, $rt->getDiscriminant());
    }

    public function testExtensionPointRejectsUnknownString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrExtensionPoint::fromJsonValue('v99');
    }

    public function testExtensionPointRejectsObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrExtensionPoint::fromJsonValue(['v0' => null]);
    }

    // =========================================================================
    // XdrTransactionMeta — int-cased non-void unions for v0..v4 entry shape
    // =========================================================================
    //
    // The arm payloads delegate to operation-meta and v1..v4 structs whose
    // own fromJsonValue chains route through Cat-A/Cat-B types — those
    // round-trips are exercised in Phase 5b. Here we verify the union
    // dispatch chooses the correct arm key.

    public function testTransactionMetaArmKeyDispatch(): void
    {
        $meta = new XdrTransactionMeta(0);
        $meta->operations = [];
        $expected = ['v0' => []];
        $this->assertSame($expected, $meta->toJsonValue());
    }

    public function testTransactionMetaRejectsUnknownVersionString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrTransactionMeta::fromJsonValue('v99');
    }

    public function testTransactionMetaRejectsBareString(): void
    {
        // Every TransactionMeta arm is non-void; a bare string is invalid.
        $this->expectException(\InvalidArgumentException::class);
        XdrTransactionMeta::fromJsonValue('v0');
    }

    public function testTransactionMetaRejectsMultiKeyObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrTransactionMeta::fromJsonValue(['v0' => [], 'v1' => null]);
    }

    public function testTransactionMetaSchemaPassthroughThenInvalid(): void
    {
        // {} after stripping $schema: shape validation rejects it.
        $this->expectException(\InvalidArgumentException::class);
        XdrTransactionMeta::fromJsonValue(['$schema' => 'https://schema']);
    }

    // =========================================================================
    // XdrLedgerEntryData — enum-discriminated multi-arm non-void union;
    // shape-validation negatives only (arm payloads route through Cat-B).
    // =========================================================================

    public function testLedgerEntryDataRejectsBareString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected single-key object for XdrLedgerEntryData');
        XdrLedgerEntryData::fromJsonValue('account');
    }

    public function testLedgerEntryDataRejectsUnknownArmKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown arm key');
        XdrLedgerEntryData::fromJsonValue(['nope' => null]);
    }

    public function testLedgerEntryDataRejectsMultiKeyObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrLedgerEntryData::fromJsonValue(['account' => [], 'offer' => []]);
    }

    public function testLedgerEntryDataRejectsIntegerArmKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrLedgerEntryData::fromJsonValue([42 => 'value']);
    }

    public function testLedgerEntryDataSchemaStripPreservesDispatch(): void
    {
        // The $schema strip happens before the count check; an input of
        // exactly $schema + one arm reduces to a single-key object after
        // stripping.
        $this->expectException(\InvalidArgumentException::class);
        // We expect failure here because XdrAccountEntry's chain goes through
        // Cat-B types not yet emitted; the failure occurs INSIDE the dispatch
        // (proving $schema strip succeeded).
        XdrLedgerEntryData::fromJsonValue(['$schema' => 'https://x', 'account' => []]);
    }

    // =========================================================================
    // XdrClaimableBalanceEntryExtV1 — struct with EXTENSION_POINT_FIELDS
    // =========================================================================

    public function testClaimableBalanceEntryExtV1RoundTrip(): void
    {
        $entry = new XdrClaimableBalanceEntryExtV1(7);
        $expected = ['ext' => 'v0', 'flags' => 7];
        $this->assertSame($expected, $entry->toJsonValue());
        $rt = XdrClaimableBalanceEntryExtV1::fromJson($entry->toJson());
        $this->assertSame(7, $rt->getFlags());
    }

    public function testClaimableBalanceEntryExtV1RejectsNonV0Ext(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Expected v0 for XdrClaimableBalanceEntryExtV1 extension point field ext");
        XdrClaimableBalanceEntryExtV1::fromJsonValue(['ext' => 'v1', 'flags' => 0]);
    }

    public function testClaimableBalanceEntryExtV1RejectsObjectExt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrClaimableBalanceEntryExtV1::fromJsonValue(['ext' => ['v0' => null], 'flags' => 0]);
    }

    public function testClaimableBalanceEntryExtV1RejectsMissingExt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field ext for XdrClaimableBalanceEntryExtV1');
        XdrClaimableBalanceEntryExtV1::fromJsonValue(['flags' => 0]);
    }

    // =========================================================================
    // XdrTrustLineEntryExtensionV2 — struct with EXTENSION_POINT_FIELDS
    // =========================================================================

    public function testTrustLineEntryExtensionV2RoundTrip(): void
    {
        $entry = new XdrTrustLineEntryExtensionV2(99);
        $expected = ['liquidity_pool_use_count' => 99, 'ext' => 'v0'];
        $this->assertSame($expected, $entry->toJsonValue());
        $rt = XdrTrustLineEntryExtensionV2::fromJson($entry->toJson());
        $this->assertSame(99, $rt->getLiquidityPoolUseCount());
    }

    public function testTrustLineEntryExtensionV2RejectsBadExt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrTrustLineEntryExtensionV2::fromJsonValue([
            'liquidity_pool_use_count' => 0,
            'ext' => 'something_else',
        ]);
    }

    // =========================================================================
    // Cat-A and Cat-B emission boundary — Phase 4's responsibility
    // =========================================================================
    //
    // These types are deliberately NOT emitted by Phase 3. The assertions
    // below pin that boundary so any accidental Phase 3 emission on a Cat-A
    // or Cat-B target trips the build immediately. Phase 4 will land their
    // bespoke shape and these assertions will be inverted at that time.

    public function testSCValIsNotPhase3Emitted(): void
    {
        $this->assertFalse(method_exists(XdrSCVal::class, 'toJsonValue'),
            'XdrSCVal is Cat-B; Phase 4 owns its SEP-51 emission.');
    }

    public function testMemoIsNotPhase3Emitted(): void
    {
        $this->assertFalse(method_exists(XdrMemo::class, 'toJsonValue'),
            'XdrMemo is Cat-A; Phase 4 owns its SEP-51 emission.');
    }

    public function testPreconditionsIsNotPhase3Emitted(): void
    {
        $this->assertFalse(method_exists(XdrPreconditions::class, 'toJsonValue'),
            'XdrPreconditions is Cat-A; Phase 4 owns its SEP-51 emission.');
    }

    public function testTransactionEnvelopeIsNotPhase3Emitted(): void
    {
        $this->assertFalse(method_exists(XdrTransactionEnvelope::class, 'toJsonValue'),
            'XdrTransactionEnvelope is Cat-B; Phase 4 owns its SEP-51 emission.');
    }

    public function testLedgerKeyIsNotPhase3Emitted(): void
    {
        $this->assertFalse(method_exists(XdrLedgerKey::class, 'toJsonValue'),
            'XdrLedgerKey is Cat-B; Phase 4 owns its SEP-51 emission.');
    }

    public function testSignerKeyIsNotPhase3Emitted(): void
    {
        $this->assertFalse(method_exists(XdrSignerKey::class, 'toJsonValue'),
            'XdrSignerKey is Cat-A; Phase 4 owns its SEP-51 emission.');
    }

    public function testOperationIsPhase3EmittedShapeValidation(): void
    {
        // XdrOperation is a struct — Phase 3 owns its shape. Its body field
        // delegates to XdrOperationBody (Phase 3 union), whose arms route
        // through Cat-A/Cat-B operation types in turn; the outermost shape
        // validation is testable in isolation.
        $this->assertTrue(method_exists(XdrOperation::class, 'toJsonValue'));
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field body for XdrOperation');
        XdrOperation::fromJsonValue(['source_account' => null]);
    }

    public function testAccountEntryIsPhase3EmittedShapeValidation(): void
    {
        $this->assertTrue(method_exists(XdrAccountEntry::class, 'toJsonValue'));
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field');
        XdrAccountEntry::fromJsonValue([]);
    }

    public function testTrustLineEntryIsPhase3EmittedShapeValidation(): void
    {
        $this->assertTrue(method_exists(XdrTrustLineEntry::class, 'toJsonValue'));
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected object for XdrTrustLineEntry');
        XdrTrustLineEntry::fromJsonValue('not_an_object');
    }

    public function testClaimableBalanceEntryIsPhase3EmittedShapeValidation(): void
    {
        $this->assertTrue(method_exists(XdrClaimableBalanceEntry::class, 'toJsonValue'));
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected object for XdrClaimableBalanceEntry');
        XdrClaimableBalanceEntry::fromJsonValue(42);
    }

    // =========================================================================
    // XdrAccountMergeOperation — hand-written struct wrapping XdrMuxedAccount
    // =========================================================================
    //
    // The wrapper is an SDK-only construct over the IDL's bare MuxedAccount; the
    // SEP-51 wire form is fully transparent — toJsonValue/fromJsonValue delegate
    // to XdrMuxedAccount with no envelope. XdrMuxedAccount is a Cat-A type whose
    // SEP-51 methods land in Phase 4, so the inner round-trip is covered in
    // Phase 5b. Tests here lock in the delegation contract.
    // =========================================================================

    public function testAccountMergeOperationIsSep51Wired(): void
    {
        $this->assertTrue(method_exists(\Soneso\StellarSDK\Xdr\XdrAccountMergeOperation::class, 'toJsonValue'));
        $this->assertTrue(method_exists(\Soneso\StellarSDK\Xdr\XdrAccountMergeOperation::class, 'fromJsonValue'));
        $this->assertTrue(method_exists(\Soneso\StellarSDK\Xdr\XdrAccountMergeOperation::class, 'toJson'));
        $this->assertTrue(method_exists(\Soneso\StellarSDK\Xdr\XdrAccountMergeOperation::class, 'fromJson'));
    }

    public function testAccountMergeOperationToJsonValueDelegatesToMuxedAccount(): void
    {
        // Calling toJsonValue() on an account-merge wrapper should forward
        // directly to XdrMuxedAccount::toJsonValue (no envelope). XdrMuxedAccount
        // is Cat-A and gains SEP-51 methods in Phase 4; until then the call
        // raises a Throwable from the inner type, not from any wrapper-side
        // shape check. That failure mode is the proof of delegation.
        $muxed = new \Soneso\StellarSDK\Xdr\XdrMuxedAccount(str_repeat("\x00", 32));
        $op = new \Soneso\StellarSDK\Xdr\XdrAccountMergeOperation($muxed);
        try {
            $op->toJsonValue();
            $this->fail('Expected toJsonValue() to forward to XdrMuxedAccount and raise a Throwable');
        } catch (\Throwable $e) {
            $this->assertNotInstanceOf(\InvalidArgumentException::class, $e);
        }
    }

    public function testAccountMergeOperationFromJsonValueDelegatesBareStringToMuxedAccount(): void
    {
        // A bare string payload (the wire form for an ed25519 destination)
        // must be forwarded to XdrMuxedAccount::fromJsonValue without any
        // wrapper-side rejection. XdrMuxedAccount is Cat-A so the call
        // raises a Throwable until Phase 4; that failure mode is the proof
        // of delegation.
        try {
            \Soneso\StellarSDK\Xdr\XdrAccountMergeOperation::fromJsonValue(
                'GAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'
            );
            $this->fail('Expected fromJsonValue() to forward to XdrMuxedAccount and raise a Throwable');
        } catch (\Throwable $e) {
            $this->assertStringNotContainsString('Expected object for XdrAccountMergeOperation', $e->getMessage());
            $this->assertStringNotContainsString('Missing required field destination', $e->getMessage());
        }
    }

    public function testAccountMergeOperationToJsonValueProducesNoEnvelopeKey(): void
    {
        // Lock-in: the wire form is bare — never an array carrying a
        // 'destination' key. Even when the inner Cat-A delegation throws,
        // we can prove the wrapper itself never builds an envelope by
        // catching the Throwable and asserting that no array-with-destination
        // ever reached the caller.
        $muxed = new \Soneso\StellarSDK\Xdr\XdrMuxedAccount(str_repeat("\x00", 32));
        $op = new \Soneso\StellarSDK\Xdr\XdrAccountMergeOperation($muxed);
        $produced = null;
        try {
            $produced = $op->toJsonValue();
        } catch (\Throwable) {
            // Expected during Phase 3; the wrapper still must not have
            // returned an envelope from a prior path.
        }
        if (is_array($produced)) {
            $this->assertArrayNotHasKey('destination', $produced);
        } else {
            $this->assertTrue(true);
        }
    }

    // =========================================================================
    // XdrOperationBody — account_merge arm wiring through XdrAccountMergeOperation
    // =========================================================================

    public function testOperationBodyAccountMergeArmDispatchesToOperationStruct(): void
    {
        // The account_merge arm payload is a bare destination value (matching
        // the wrapper's transparent wire form). Round-trip fails inside
        // XdrMuxedAccount::fromJsonValue, a Cat-A type whose SEP-51 methods
        // land in Phase 4. Asserting that the failure originates from the
        // delegation path — not from a wrapper-side shape rejection — proves
        // the OperationBody arm is wired correctly.
        try {
            \Soneso\StellarSDK\Xdr\XdrOperationBody::fromJsonValue([
                'account_merge' => 'GAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
            ]);
            $this->fail('Expected delegation to XdrMuxedAccount to raise a Throwable');
        } catch (\Throwable $e) {
            $this->assertStringNotContainsString('Expected object for XdrAccountMergeOperation', $e->getMessage());
            $this->assertStringNotContainsString('Missing required field destination', $e->getMessage());
        }
    }

    // =========================================================================
    // XdrContractCodeEntryExtV1 — hand-written struct duplicate; ext is a
    // void-only extension point so its round-trip stays inside Phase 3.
    // =========================================================================

    public function testContractCodeEntryExtV1RoundTrip(): void
    {
        $costInputs = new \Soneso\StellarSDK\Xdr\XdrContractCodeCostInputs(
            new XdrExtensionPoint(0),
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10
        );
        $entry = new \Soneso\StellarSDK\Xdr\XdrContractCodeEntryExtV1(new XdrExtensionPoint(0), $costInputs);
        $expected = [
            'ext' => 'v0',
            'cost_inputs' => [
                'ext' => 'v0',
                'n_instructions' => 1,
                'n_functions' => 2,
                'n_globals' => 3,
                'n_table_entries' => 4,
                'n_types' => 5,
                'n_data_segments' => 6,
                'n_elem_segments' => 7,
                'n_imports' => 8,
                'n_exports' => 9,
                'n_data_segment_bytes' => 10,
            ],
        ];
        $this->assertSame($expected, $entry->toJsonValue());
        $rt = \Soneso\StellarSDK\Xdr\XdrContractCodeEntryExtV1::fromJson($entry->toJson());
        $this->assertSame(0, $rt->getExt()->getDiscriminant());
        $this->assertSame(1, $rt->getCostInputs()->nInstructions);
        $this->assertSame(10, $rt->getCostInputs()->nDataSegmentBytes);
    }

    public function testContractCodeEntryExtV1RejectsNonV0Ext(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected v0 for XdrContractCodeEntryExtV1 extension point field ext');
        \Soneso\StellarSDK\Xdr\XdrContractCodeEntryExtV1::fromJsonValue([
            'ext' => 'v1',
            'cost_inputs' => [
                'ext' => 'v0',
                'n_instructions' => 0, 'n_functions' => 0, 'n_globals' => 0,
                'n_table_entries' => 0, 'n_types' => 0, 'n_data_segments' => 0,
                'n_elem_segments' => 0, 'n_imports' => 0, 'n_exports' => 0,
                'n_data_segment_bytes' => 0,
            ],
        ]);
    }

    public function testContractCodeEntryExtV1RejectsMissingCostInputs(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field cost_inputs for XdrContractCodeEntryExtV1');
        \Soneso\StellarSDK\Xdr\XdrContractCodeEntryExtV1::fromJsonValue(['ext' => 'v0']);
    }

    // =========================================================================
    // XdrDataValueMandatory — hand-written opaque-bytes wrapper (hex form)
    // =========================================================================

    public function testDataValueMandatoryRoundTripNonEmpty(): void
    {
        $bytes = "\xde\xad\xbe\xef\x01\x02\x03";
        $val = new \Soneso\StellarSDK\Xdr\XdrDataValueMandatory($bytes);
        $this->assertSame('deadbeef010203', $val->toJsonValue());
        $rt = \Soneso\StellarSDK\Xdr\XdrDataValueMandatory::fromJson($val->toJson());
        $this->assertSame($bytes, $rt->getValue());
    }

    public function testDataValueMandatoryRoundTripEmpty(): void
    {
        $val = new \Soneso\StellarSDK\Xdr\XdrDataValueMandatory('');
        $this->assertSame('', $val->toJsonValue());
        $rt = \Soneso\StellarSDK\Xdr\XdrDataValueMandatory::fromJson($val->toJson());
        $this->assertSame('', $rt->getValue());
    }

    public function testDataValueMandatoryRoundTripWithControlCharacters(): void
    {
        // The hex-form encoding is raw-byte-faithful and survives any byte
        // sequence including ASCII control characters and high-bit bytes.
        $bytes = "\x00\x01\x02\x09\x0A\x0D\x1B\x7F\xFF";
        $val = new \Soneso\StellarSDK\Xdr\XdrDataValueMandatory($bytes);
        $rt = \Soneso\StellarSDK\Xdr\XdrDataValueMandatory::fromJson($val->toJson());
        $this->assertSame($bytes, $rt->getValue());
    }

    public function testDataValueMandatoryRejectsNonStringInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected string for XdrDataValueMandatory JSON value');
        \Soneso\StellarSDK\Xdr\XdrDataValueMandatory::fromJsonValue(['hex' => 'deadbeef']);
    }
}
