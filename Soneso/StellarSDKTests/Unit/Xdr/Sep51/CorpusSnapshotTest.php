<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr\Sep51;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrBucketEntry;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceID;
use Soneso\StellarSDK\Xdr\XdrConfigSettingEntry;
use Soneso\StellarSDK\Xdr\XdrDecoratedSignature;
use Soneso\StellarSDK\Xdr\XdrHotArchiveBucketEntry;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrInt256Parts;
use Soneso\StellarSDK\Xdr\XdrJsonHelper;
use Soneso\StellarSDK\Xdr\XdrLedgerBounds;
use Soneso\StellarSDK\Xdr\XdrLedgerCloseMeta;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrMemo;
use Soneso\StellarSDK\Xdr\XdrMuxedAccount;
use Soneso\StellarSDK\Xdr\XdrOperation;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntry;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSignedPayload;
use Soneso\StellarSDK\Xdr\XdrSignerKey;
use Soneso\StellarSDK\Xdr\XdrTimeBounds;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrUInt128Parts;
use Soneso\StellarSDK\Xdr\XdrUInt256Parts;

/**
 * SEP-51 cross-SDK fixture conformance tests.
 *
 * Loads tools/sep-51-fixtures/corpus.json and, for each non-divergent entry,
 * decodes the base64-encoded XDR through the matching PHP SDK class and
 * compares the resulting JSON to py-stellar-base v14.0.0's reference output
 * along two subgates:
 *
 *   (a) Structural-equal: assertEquals over json_decode(..., true) on both
 *       sides. Catches missing keys, wrong arm names, divergent value types.
 *   (b) Canonical-byte equal: assertSame over XdrJsonHelper::canonicalJson on
 *       both sides. Catches key-order drift and whitespace divergence after
 *       the structural gate has already passed.
 *
 * The corpus entries currently carry py_reference_json: null because the py
 * reference is populated on the first CI run; the tests below mark those
 * cases as skipped without removing the test methods, so the cross-SDK gate
 * activates automatically once py reference data lands. Entries with
 * divergence_reason set are excluded — DivergenceTest covers those against
 * the spec reference rather than py.
 */
class CrossSdkFixtureTest extends TestCase
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
            $path = __DIR__ . '/../../../../../tools/sep-51-fixtures/corpus.json';
            $real = realpath($path);
            if ($real === false || !is_file($real)) {
                throw new \RuntimeException(
                    'CrossSdkFixtureTest: corpus.json not found at expected location: ' . $path
                );
            }
            $raw = file_get_contents($real);
            if ($raw === false) {
                throw new \RuntimeException('CrossSdkFixtureTest: failed to read corpus.json');
            }
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($decoded) || !isset($decoded['entries']) || !is_array($decoded['entries'])) {
                throw new \RuntimeException('CrossSdkFixtureTest: malformed corpus.json (missing entries)');
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
            'Asset' => XdrAsset::class,
            'BucketEntry' => XdrBucketEntry::class,
            'ClaimableBalanceID' => XdrClaimableBalanceID::class,
            'ConfigSettingEntry' => XdrConfigSettingEntry::class,
            'DecoratedSignature' => XdrDecoratedSignature::class,
            'HotArchiveBucketEntry' => XdrHotArchiveBucketEntry::class,
            'Int128Parts' => XdrInt128Parts::class,
            'Int256Parts' => XdrInt256Parts::class,
            'LedgerBounds' => XdrLedgerBounds::class,
            'LedgerCloseMeta' => XdrLedgerCloseMeta::class,
            'LedgerKey' => XdrLedgerKey::class,
            'Memo' => XdrMemo::class,
            'MuxedAccount' => XdrMuxedAccount::class,
            'Operation' => XdrOperation::class,
            'SCSpecEntry' => XdrSCSpecEntry::class,
            'SCVal' => XdrSCVal::class,
            'SignedPayload' => XdrSignedPayload::class,
            'SignerKey' => XdrSignerKey::class,
            'TimeBounds' => XdrTimeBounds::class,
            'TransactionEnvelope' => XdrTransactionEnvelope::class,
            'UInt128Parts' => XdrUInt128Parts::class,
            'UInt256Parts' => XdrUInt256Parts::class,
            default => throw new \RuntimeException(
                "CrossSdkFixtureTest: unknown corpus type '$type'; update classForType() to dispatch it."
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
     * Subgate (a) Structural-equal: assertEquals on json_decode of both sides.
     *
     * For entries with py_reference_json: null the test is marked as skipped;
     * once the CI lane populates the field the assertion activates.
     *
     * @dataProvider provideNonDivergentEntries
     * @param array<string, mixed> $entry
     */
    public function testCorpusStructuralEqualToPyReference(array $entry): void
    {
        $type = $entry['type'];
        $class = self::classForType($type);
        $instance = $class::fromBase64Xdr($entry['base64']);
        $phpJson = $instance->toJson();
        // Light PHP-side integrity check: result must be valid JSON.
        $this->assertNotFalse(json_decode($phpJson, true),
            "PHP toJson output for entry {$entry['id']} must be valid JSON");

        $py = $entry['py_reference_json'] ?? null;
        if ($py === null) {
            $this->markTestSkipped(
                "py reference deferred for {$entry['id']}; CI lane populates it"
            );
        }

        $this->assertEquals(
            json_decode($py, true, 512, JSON_THROW_ON_ERROR),
            json_decode($phpJson, true, 512, JSON_THROW_ON_ERROR),
            "Structural mismatch on corpus entry {$entry['id']}"
        );
    }

    /**
     * Subgate (b) Canonical-byte equal: identical bytes after canonicalJson.
     *
     * @dataProvider provideNonDivergentEntries
     * @param array<string, mixed> $entry
     */
    public function testCorpusCanonicalByteEqualToPyReference(array $entry): void
    {
        $type = $entry['type'];
        $class = self::classForType($type);
        $instance = $class::fromBase64Xdr($entry['base64']);
        $phpJson = $instance->toJson();

        $py = $entry['py_reference_json'] ?? null;
        if ($py === null) {
            $this->markTestSkipped(
                "py reference deferred for {$entry['id']}; CI lane populates it"
            );
        }

        $this->assertSame(
            XdrJsonHelper::canonicalJson($py),
            XdrJsonHelper::canonicalJson($phpJson),
            "Canonical-byte mismatch on corpus entry {$entry['id']}"
        );
    }

    /**
     * Provider for non-divergent corpus entries. Divergent entries are owned
     * by DivergenceTest which compares against spec_reference_json instead.
     *
     * @return iterable<string, array{0: array<string, mixed>}>
     */
    public static function provideNonDivergentEntries(): iterable
    {
        foreach (self::corpus() as $entry) {
            if (($entry['divergence_reason'] ?? null) !== null) {
                continue;
            }
            yield $entry['id'] => [$entry];
        }
    }
}
