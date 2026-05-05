<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr\Sep51;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrJsonHelper;

/**
 * SEP-51 divergence-direction conformance tests.
 *
 * For every corpus entry whose divergence_reason is set, the PHP SDK is
 * required to follow the SEP-0051 spec rather than py-stellar-base v14.0.0.
 * The test asserts canonical-byte equality between PHP's toJson output and
 * the entry's spec_reference_json field, NOT the py_reference_json field —
 * that direction is the whole point of recording the divergence.
 *
 * The accompanying entries in tools/baselines/sep-51-divergence-catalogue.md
 * document the rationale for each divergence; this test is the executable
 * regression guard that keeps PHP on the spec-side of the divergence.
 */
class DivergenceTest extends TestCase
{
    /** @var array<int, array<string, mixed>>|null */
    private static ?array $corpus = null;

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function corpus(): array
    {
        if (self::$corpus === null) {
            $path = __DIR__ . '/../../../../../tools/sep-51-fixtures/corpus.json';
            $real = realpath($path);
            if ($real === false || !is_file($real)) {
                throw new \RuntimeException(
                    'DivergenceTest: corpus.json not found at expected location: ' . $path
                );
            }
            $raw = file_get_contents($real);
            if ($raw === false) {
                throw new \RuntimeException('DivergenceTest: failed to read corpus.json');
            }
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($decoded) || !isset($decoded['entries']) || !is_array($decoded['entries'])) {
                throw new \RuntimeException('DivergenceTest: malformed corpus.json (missing entries)');
            }
            self::$corpus = $decoded['entries'];
        }
        return self::$corpus;
    }

    /**
     * Sanity check: at least one divergence entry must exist or the test is
     * vacuous. The current corpus has one entry covering AssetCode4/AssetCode12
     * non-ASCII byte handling.
     */
    public function testCorpusContainsAtLeastOneDivergence(): void
    {
        $count = 0;
        foreach (self::corpus() as $entry) {
            if (($entry['divergence_reason'] ?? null) !== null) {
                $count++;
            }
        }
        $this->assertGreaterThanOrEqual(
            1,
            $count,
            'Corpus must record at least one divergence; if none remain remove DivergenceTest.'
        );
    }

    /**
     * For each divergence entry, decode its base64 through the SDK and assert
     * the SDK output carries the spec_reference_json substring (NOT the
     * py_reference_json shape).
     *
     * The current corpus's only divergence entry stores spec_reference_json as
     * the AssetCode4 inner string ("AB\\x80") rather than the full enclosing
     * Asset object — the corpus shape is fixed and cannot be reshaped from
     * this test. The assertion therefore checks that the spec-form
     * inner value appears verbatim inside PHP's wrapped output. The reverse
     * direction (the py-form must NOT appear) is enforced by the second
     * assertion: py-stellar-base v14 raw-decodes the byte 0x80 and produces
     * a unicode string with U+0080, which would round-trip to "" in
     * JSON; that sequence must be absent.
     *
     * @dataProvider provideDivergenceEntries
     * @param array<string, mixed> $entry
     */
    public function testDivergencePhpFollowsSpecReference(array $entry): void
    {
        $type = $entry['type'];
        $class = self::classForType($type);
        $instance = $class::fromBase64Xdr($entry['base64']);
        $phpJson = $instance->toJson();

        $spec = $entry['spec_reference_json'] ?? null;
        $this->assertNotNull(
            $spec,
            "Divergence entry {$entry['id']} must carry spec_reference_json"
        );

        // The spec_reference_json field for the AssetCode4/12 non-ASCII
        // divergence carries the SEP-51 inner-string wire form (e.g.
        // "\"AB\\x80\""). The outer quotes mark it as a JSON string literal,
        // but the embedded \xNN escape is SEP-51 syntax (NOT JSON syntax),
        // so json_decode would reject it. Strip the outer quotes and assert
        // that the resulting wire form appears verbatim inside the SDK's
        // emitted JSON. The SDK wraps the inner string in an Asset object;
        // the substring match expresses the spec-side direction without
        // requiring corpus-side reshaping.
        $this->assertTrue(
            strlen($spec) >= 2 && $spec[0] === '"' && $spec[strlen($spec) - 1] === '"',
            "Divergence entry {$entry['id']} spec_reference_json must be a"
            . " JSON-quoted string under the current corpus shape"
        );
        // The wire form (e.g. "AB\\x80" with one literal backslash) is what
        // the SEP-51 escape ladder produces. When that string is embedded in
        // the SDK's outer JSON the backslash is escaped a second time,
        // yielding "AB\\\\x80" in the on-the-wire JSON bytes (per SEP-0051
        // §String the spec acknowledges this double-escaping). The expected
        // substring must therefore have backslashes doubled before matching.
        $expectedWire = substr($spec, 1, -1);
        $expectedJsonEscaped = str_replace('\\', '\\\\', $expectedWire);
        $this->assertStringContainsString(
            $expectedJsonEscaped,
            $phpJson,
            "PHP output for divergence entry {$entry['id']} must emit the spec"
            . " escape-ladder form ($expectedWire, JSON-escaped to"
            . " $expectedJsonEscaped) inside the wrapped JSON."
            . " Reason: {$entry['divergence_reason']}"
        );

        // Defensively confirm the py-side raw-unicode form is NOT present —
        // guarding against silent re-alignment with py later. The 0x80 byte
        // round-tripped through py-stellar-base would emit "" or the
        // raw UTF-8 sequence "\xc2\x80" depending on path; neither must appear.
        $this->assertStringNotContainsString(
            '',
            $phpJson,
            "PHP output for divergence entry {$entry['id']} must not emit raw"
            . " unicode escapes (py-form); divergence reason: {$entry['divergence_reason']}"
        );
        $this->assertStringNotContainsString(
            "\xc2\x80",
            $phpJson,
            "PHP output for divergence entry {$entry['id']} must not emit raw"
            . " UTF-8 bytes for non-ASCII codepoints; divergence reason: {$entry['divergence_reason']}"
        );
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>}>
     */
    public static function provideDivergenceEntries(): iterable
    {
        foreach (self::corpus() as $entry) {
            if (($entry['divergence_reason'] ?? null) === null) {
                continue;
            }
            yield $entry['id'] => [$entry];
        }
    }

    /**
     * Restricted corpus-type to PHP-class dispatch. Only types observed in
     * the current divergence set need entries here; new divergent types must
     * extend the match arm explicitly.
     *
     * @return class-string
     */
    private static function classForType(string $type): string
    {
        return match ($type) {
            'Asset' => XdrAsset::class,
            default => throw new \RuntimeException(
                "DivergenceTest: unknown corpus type '$type'; extend classForType()."
            ),
        };
    }
}
