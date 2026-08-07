<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr\Sep51;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrAllowTrustOperation;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum12;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum4;
use Soneso\StellarSDK\Xdr\XdrBinaryFuseFilterType;
use Soneso\StellarSDK\Xdr\XdrBucketEntry;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceEntry;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceID;
use Soneso\StellarSDK\Xdr\XdrClaimantType;
use Soneso\StellarSDK\Xdr\XdrClaimClaimableBalanceOperation;
use Soneso\StellarSDK\Xdr\XdrClawbackClaimableBalanceOperation;
use Soneso\StellarSDK\Xdr\XdrClawbackResult;
use Soneso\StellarSDK\Xdr\XdrConfigSettingEntry;
use Soneso\StellarSDK\Xdr\XdrContractCostType;
use Soneso\StellarSDK\Xdr\XdrContractEvent;
use Soneso\StellarSDK\Xdr\XdrCreateAccountResult;
use Soneso\StellarSDK\Xdr\XdrCurve25519Public;
use Soneso\StellarSDK\Xdr\XdrCurve25519Secret;
use Soneso\StellarSDK\Xdr\XdrDataValue;
use Soneso\StellarSDK\Xdr\XdrDecoratedSignature;
use Soneso\StellarSDK\Xdr\XdrDiagnosticEvent;
use Soneso\StellarSDK\Xdr\XdrHmacSha256Key;
use Soneso\StellarSDK\Xdr\XdrHmacSha256Mac;
use Soneso\StellarSDK\Xdr\XdrHotArchiveBucketEntry;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrInt256Parts;
use Soneso\StellarSDK\Xdr\XdrIPAddrType;
use Soneso\StellarSDK\Xdr\XdrJsonHelper;
use Soneso\StellarSDK\Xdr\XdrLedgerBounds;
use Soneso\StellarSDK\Xdr\XdrLedgerCloseMeta;
use Soneso\StellarSDK\Xdr\XdrLedgerCloseMetaV1;
use Soneso\StellarSDK\Xdr\XdrLedgerCloseMetaV2;
use Soneso\StellarSDK\Xdr\XdrLedgerHeader;
use Soneso\StellarSDK\Xdr\XdrLedgerHeaderHistoryEntry;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyClaimableBalance;
use Soneso\StellarSDK\Xdr\XdrMemo;
use Soneso\StellarSDK\Xdr\XdrMuxedAccount;
use Soneso\StellarSDK\Xdr\XdrOperation;
use Soneso\StellarSDK\Xdr\XdrOperationResult;
use Soneso\StellarSDK\Xdr\XdrPeerAddress;
use Soneso\StellarSDK\Xdr\XdrPeerAddressIp;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntry;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSerializedBinaryFuseFilter;
use Soneso\StellarSDK\Xdr\XdrShortHashSeed;
use Soneso\StellarSDK\Xdr\XdrSignedPayload;
use Soneso\StellarSDK\Xdr\XdrSignerKey;
use Soneso\StellarSDK\Xdr\XdrStoredDebugTransactionSet;
use Soneso\StellarSDK\Xdr\XdrTimeBounds;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrTransactionEvent;
use Soneso\StellarSDK\Xdr\XdrTransactionResultMeta;
use Soneso\StellarSDK\Xdr\XdrTransactionResultMetaV1;
use Soneso\StellarSDK\Xdr\XdrUInt128Parts;
use Soneso\StellarSDK\Xdr\XdrUInt256Parts;

/**
 * SEP-51 corpus correctness test.
 *
 * Loads tools/sep-51-test-fixtures/corpus.json and, for each entry, decodes the
 * base64-encoded XDR through the matching PHP SDK class and asserts that
 * the resulting toJson() output matches the committed `spec_reference_json`
 * byte-for-byte after canonicalJson normalisation on both sides.
 *
 * The committed corpus is generated from the rs-stellar-xdr CLI oracle (the
 * SEP-0051 reference implementation, >= 28.0.0) by
 * tools/sep-51-test-fixtures/generate_corpus.py, so a failure here means the
 * SDK's emission diverges from the reference implementation: fix the
 * emission, do not regenerate the corpus from PHP output to make the test
 * pass. Entries carrying an `oracle_incomparable` marker are the documented
 * exceptions whose reference JSON is the SDK's own output (drift pin only).
 */
class CorpusSnapshotTest extends TestCase
{
    /** @var array<int, array<string, mixed>>|null */
    private static ?array $corpus = null;

    /**
     * Lazily load the corpus once per process.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function corpus(): array
    {
        if (self::$corpus === null) {
            $path = __DIR__ . '/../../../../../tools/sep-51-test-fixtures/corpus.json';
            $real = realpath($path);
            if ($real === false || !is_file($real)) {
                throw new \RuntimeException(
                    'CorpusSnapshotTest: corpus.json not found at expected location: ' . $path
                );
            }
            $raw = file_get_contents($real);
            if ($raw === false) {
                throw new \RuntimeException('CorpusSnapshotTest: failed to read corpus.json');
            }
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($decoded) || !isset($decoded['entries']) || !is_array($decoded['entries'])) {
                throw new \RuntimeException('CorpusSnapshotTest: malformed corpus.json (missing entries)');
            }
            self::$corpus = $decoded['entries'];
        }
        return self::$corpus;
    }

    /**
     * Map a corpus type identifier to a PHP class with a fromBase64Xdr method
     * that returns an instance carrying toJson().
     *
     * @return class-string
     */
    private static function classForType(string $type): string
    {
        return match ($type) {
            'AccountID' => XdrAccountID::class,
            'AllowTrustOperation' => XdrAllowTrustOperation::class,
            'Asset' => XdrAsset::class,
            'AssetAlphaNum4' => XdrAssetAlphaNum4::class,
            'AssetAlphaNum12' => XdrAssetAlphaNum12::class,
            'BinaryFuseFilterType' => XdrBinaryFuseFilterType::class,
            'BucketEntry' => XdrBucketEntry::class,
            'ClaimClaimableBalanceOperation' => XdrClaimClaimableBalanceOperation::class,
            'ClaimableBalanceEntry' => XdrClaimableBalanceEntry::class,
            'ClaimableBalanceID' => XdrClaimableBalanceID::class,
            'ClaimantType' => XdrClaimantType::class,
            'ClawbackClaimableBalanceOperation' => XdrClawbackClaimableBalanceOperation::class,
            'ClawbackResult' => XdrClawbackResult::class,
            'ConfigSettingEntry' => XdrConfigSettingEntry::class,
            'ContractCostType' => XdrContractCostType::class,
            'ContractEvent' => XdrContractEvent::class,
            'CreateAccountResult' => XdrCreateAccountResult::class,
            'Curve25519Public' => XdrCurve25519Public::class,
            'Curve25519Secret' => XdrCurve25519Secret::class,
            'DataValue' => XdrDataValue::class,
            'DecoratedSignature' => XdrDecoratedSignature::class,
            'DiagnosticEvent' => XdrDiagnosticEvent::class,
            'HmacSha256Key' => XdrHmacSha256Key::class,
            'HmacSha256Mac' => XdrHmacSha256Mac::class,
            'HotArchiveBucketEntry' => XdrHotArchiveBucketEntry::class,
            'IPAddrType' => XdrIPAddrType::class,
            'Int128Parts' => XdrInt128Parts::class,
            'Int256Parts' => XdrInt256Parts::class,
            'LedgerBounds' => XdrLedgerBounds::class,
            'LedgerCloseMeta' => XdrLedgerCloseMeta::class,
            'LedgerCloseMetaV1' => XdrLedgerCloseMetaV1::class,
            'LedgerCloseMetaV2' => XdrLedgerCloseMetaV2::class,
            'LedgerHeader' => XdrLedgerHeader::class,
            'LedgerHeaderHistoryEntry' => XdrLedgerHeaderHistoryEntry::class,
            'LedgerKey' => XdrLedgerKey::class,
            'LedgerKeyClaimableBalance' => XdrLedgerKeyClaimableBalance::class,
            'Memo' => XdrMemo::class,
            'MuxedAccount' => XdrMuxedAccount::class,
            'Operation' => XdrOperation::class,
            'OperationResult' => XdrOperationResult::class,
            'PeerAddress' => XdrPeerAddress::class,
            'PeerAddressIp' => XdrPeerAddressIp::class,
            'SCSpecEntry' => XdrSCSpecEntry::class,
            'SCVal' => XdrSCVal::class,
            'SerializedBinaryFuseFilter' => XdrSerializedBinaryFuseFilter::class,
            'ShortHashSeed' => XdrShortHashSeed::class,
            'SignedPayload' => XdrSignedPayload::class,
            'SignerKey' => XdrSignerKey::class,
            'StoredDebugTransactionSet' => XdrStoredDebugTransactionSet::class,
            'TimeBounds' => XdrTimeBounds::class,
            'TransactionEnvelope' => XdrTransactionEnvelope::class,
            'TransactionEvent' => XdrTransactionEvent::class,
            'TransactionResultMeta' => XdrTransactionResultMeta::class,
            'TransactionResultMetaV1' => XdrTransactionResultMetaV1::class,
            'UInt128Parts' => XdrUInt128Parts::class,
            'UInt256Parts' => XdrUInt256Parts::class,
            default => throw new \RuntimeException(
                "CorpusSnapshotTest: unknown corpus type '$type'; update classForType() to dispatch it."
            ),
        };
    }

    /**
     * Verify that every corpus entry uses a known type and that the dispatch
     * map exposes a usable PHP class. This guards against silent skips when
     * the corpus grows without a corresponding test-side dispatch update.
     */
    public function testCorpusDispatchCoversAllTypes(): void
    {
        $entries = self::corpus();
        $this->assertNotEmpty($entries, 'corpus.json must contain entries');
        $seen = [];
        foreach ($entries as $entry) {
            $type = $entry['type'];
            $seen[$type] = true;
            $class = self::classForType($type);
            $this->assertTrue(class_exists($class),
                "Class $class for corpus type $type does not exist");
            $this->assertTrue(method_exists($class, 'fromBase64Xdr'),
                "Class $class for corpus type $type lacks fromBase64Xdr");
            $this->assertTrue(method_exists($class, 'toJson'),
                "Class $class for corpus type $type lacks toJson");
        }
        $this->assertNotEmpty($seen, 'must have observed at least one corpus type');
    }

    /**
     * Snapshot regression assertion: PHP's toJson output for the entry's
     * base64 XDR input must equal the committed `spec_reference_json` on
     * canonical-byte comparison. Drift fails the test immediately.
     *
     * @dataProvider provideAllEntries
     * @param array<string, mixed> $entry
     */
    public function testCorpusToJsonMatchesCommittedSnapshot(array $entry): void
    {
        $type = $entry['type'];
        $class = self::classForType($type);
        $instance = $class::fromBase64Xdr($entry['base64']);
        $phpJson = $instance->toJson();

        $expected = $entry['spec_reference_json'] ?? null;
        $this->assertNotNull(
            $expected,
            "Corpus entry {$entry['id']} is missing spec_reference_json; "
            . 'regenerate corpus.json via tools/sep-51-test-fixtures/generate_corpus.py'
        );

        $this->assertSame(
            XdrJsonHelper::canonicalJson($expected),
            XdrJsonHelper::canonicalJson($phpJson),
            "Snapshot drift on corpus entry {$entry['id']}: "
            . 'PHP toJson output diverges from the committed spec_reference_json. '
            . 'Either revert the emission change or regenerate the corpus snapshot.'
        );
    }

    /**
     * Provider for every corpus entry. The snapshot covers all entries
     * uniformly; the snapshot is the regression baseline.
     *
     * @return iterable<string, array{0: array<string, mixed>}>
     */
    public static function provideAllEntries(): iterable
    {
        foreach (self::corpus() as $entry) {
            yield $entry['id'] => [$entry];
        }
    }
}
