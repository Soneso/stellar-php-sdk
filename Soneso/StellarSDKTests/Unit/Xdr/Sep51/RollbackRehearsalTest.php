<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr\Sep51;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrJsonHelper;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;

/**
 * SEP-51 frozen-reference regression test.
 *
 * Holds a known-good TransactionEnvelope JSON byte string and its matching
 * XDR base64 encoding as constants. The single test asserts that
 * fromJson(<canonical JSON>) decodes and re-encodes back to the canonical
 * XDR base64 byte-identically.
 *
 * The constants are pinned literally (not derived from corpus or generator
 * output) so a generator or helper change that perturbs key ordering,
 * whitespace, or value formatting trips the test even when the round-trip
 * itself is structurally correct. A second assertion confirms that the
 * re-emitted toJson output canonicalises to the same bytes as the constant,
 * isolating "JSON output drift but XDR round-trip still works" as a
 * separate failure direction.
 */
class RollbackRehearsalTest extends TestCase
{
    /**
     * Canonical TransactionEnvelope XDR base64.
     *
     * Source: tools/sep-51-test-fixtures/corpus.json entry transaction_envelope_canonical
     * (also matches SEP-0051 §Examples > TransactionEnvelope).
     */
    private const CANONICAL_TX_ENVELOPE_XDR_BASE64 =
        'AAAAAgAAAADmmSZkwY3163TMouB2TY8MljqXw2IxVYTGyvDrR6YtAAAqmmQAABpuAAAAAQAAAAAAAAAAAAAAAQAAAAAAAAAYAAAAAQAAAAEAAAAAAAAAAQAAAAAAAAABAAAAAAAAAAAAAAABAAAABgAAAAHXkotywnA8z+r365/0701QSlWouXn8m0UOoshCtNHOYQAAABQAAAABAAI9fQAAAAAAAAD4AAAAAAAqmgAAAAABR6YtAAAAAEArDtxbqUI+CsdkRmV0lFhVt0wyB7fyrmmkM6Fr35wpPcK8WKcXeKTl4BQ+akE14MZtpaea9LMdhXopaW3pJA0E';

    /**
     * Canonical TransactionEnvelope JSON, in the byte-form the SDK emits.
     *
     * Whitespace-free, key order matches generator emission order. Future
     * regenerations must produce the identical byte string after
     * canonicalJson normalisation; raw byte equality is asserted as a
     * tighter complementary check.
     */
    private const CANONICAL_TX_ENVELOPE_JSON =
        '{"tx":{"tx":{"source_account":"GDTJSJTEYGG7L23UZSROA5SNR4GJMOUXYNRDCVMEY3FPB22HUYWQBZIA","fee":2792036,"seq_num":"29059748724737","cond":"none","memo":"none","operations":[{"source_account":null,"body":{"invoke_host_function":{"host_function":{"create_contract":{"contract_id_preimage":{"asset":"native"},"executable":"stellar_asset"}},"auth":[]}}}],"ext":{"v1":{"ext":"v0","resources":{"footprint":{"read_only":[],"read_write":[{"contract_data":{"contract":"CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC","key":"ledger_key_contract_instance","durability":"persistent"}}]},"instructions":146813,"disk_read_bytes":0,"write_bytes":248},"resource_fee":"2791936"}}},"signatures":[{"hint":"47a62d00","signature":"2b0edc5ba9423e0ac764466574945855b74c3207b7f2ae69a433a16bdf9c293dc2bc58a71778a4e5e0143e6a4135e0c66da5a79af4b31d857a29696de9240d04"}]}}';

    /**
     * Primary regression guard: fromJson(<canonical>)->toBase64Xdr() must
     * equal the canonical XDR byte-identically.
     */
    public function testFromJsonRoundTripsToCanonicalXdr(): void
    {
        $envelope = XdrTransactionEnvelope::fromJson(self::CANONICAL_TX_ENVELOPE_JSON);
        $this->assertSame(
            self::CANONICAL_TX_ENVELOPE_XDR_BASE64,
            $envelope->toBase64Xdr(),
            'Frozen-reference regression: fromJson(CANONICAL_TX_ENVELOPE_JSON)->toBase64Xdr()'
            . ' must match the pinned canonical XDR byte-identically.'
        );
    }

    /**
     * Secondary guard: the canonical JSON byte string must be reproducible
     * by decoding the XDR and re-emitting to JSON. If a generator change
     * causes whitespace or key-order drift the raw-equality assertion catches
     * it at full fidelity; the canonicalJson assertion absorbs structural
     * equivalents and is therefore a softer check that runs unconditionally.
     */
    public function testToJsonReproducesCanonicalJson(): void
    {
        $envelope = XdrTransactionEnvelope::fromBase64Xdr(self::CANONICAL_TX_ENVELOPE_XDR_BASE64);
        $jsonOut = $envelope->toJson();

        // Tight check: the SDK output must equal the pinned constant byte for
        // byte (caught on first emission drift).
        $this->assertSame(
            self::CANONICAL_TX_ENVELOPE_JSON,
            $jsonOut,
            'Frozen-reference regression: toJson output drifted from the'
            . ' pinned canonical JSON. If the change is intentional, update the'
            . ' constant and document the bump in the SDK changelog as part of'
            . ' the next minor release.'
        );

        // Defensive secondary: even if the byte-equality somehow tolerated a
        // change in the future (e.g. by re-pinning), the canonical normalised
        // forms must still agree.
        $this->assertSame(
            XdrJsonHelper::canonicalJson(self::CANONICAL_TX_ENVELOPE_JSON),
            XdrJsonHelper::canonicalJson($jsonOut),
            'canonicalJson normalisation diverged between the pinned canonical JSON'
            . ' and the regenerated SDK output.'
        );
    }
}
