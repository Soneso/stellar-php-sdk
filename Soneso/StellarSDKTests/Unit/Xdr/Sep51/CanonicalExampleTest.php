<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr\Sep51;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;

/**
 * SEP-51 canonical TransactionEnvelope example.
 *
 * The reference base64 + JSON pair is taken verbatim from
 * SEP-0051 §Examples > TransactionEnvelope. The PHP SDK decodes the base64
 * through XdrTransactionEnvelope::fromBase64Xdr and renders to JSON via
 * toJson(); the test then compares structurally against the spec example
 * using assertEquals over json_decode (NOT byte-equality), per the plan
 * requirement that this gate is structural-only.
 *
 * The canonical-byte side of the comparison is owned by SpecAnchorTest and
 * by CrossSdkFixtureTest's subgate (b); this test's contract is that the
 * decoded structure round-trips with the spec output, not that the bytes
 * are identical.
 */
class CanonicalExampleTest extends TestCase
{
    /**
     * Base64-encoded XDR from SEP-0051 §Examples > TransactionEnvelope.
     */
    private const SPEC_BASE64 = 'AAAAAgAAAADmmSZkwY3163TMouB2TY8MljqXw2IxVYTGyvDrR6YtAAAqmmQAABpuAAAAAQAAAAAAAAAAAAAAAQAAAAAAAAAYAAAAAQAAAAEAAAAAAAAAAQAAAAAAAAABAAAAAAAAAAAAAAABAAAABgAAAAHXkotywnA8z+r365/0701QSlWouXn8m0UOoshCtNHOYQAAABQAAAABAAI9fQAAAAAAAAD4AAAAAAAqmgAAAAABR6YtAAAAAEArDtxbqUI+CsdkRmV0lFhVt0wyB7fyrmmkM6Fr35wpPcK8WKcXeKTl4BQ+akE14MZtpaea9LMdhXopaW3pJA0E';

    /**
     * Reference JSON output from SEP-0051 §Examples > TransactionEnvelope.
     *
     * The structure mirrors the spec exactly. assertEquals on the decoded
     * value tolerates key-order drift and is therefore a structural check
     * by definition.
     */
    private const SPEC_JSON = <<<'JSON'
    {
      "tx": {
        "tx": {
          "source_account": "GDTJSJTEYGG7L23UZSROA5SNR4GJMOUXYNRDCVMEY3FPB22HUYWQBZIA",
          "fee": 2792036,
          "seq_num": "29059748724737",
          "cond": "none",
          "memo": "none",
          "operations": [
            {
              "source_account": null,
              "body": {
                "invoke_host_function": {
                  "host_function": {
                    "create_contract": {
                      "contract_id_preimage": {
                        "asset": "native"
                      },
                      "executable": "stellar_asset"
                    }
                  },
                  "auth": []
                }
              }
            }
          ],
          "ext": {
            "v1": {
              "ext": "v0",
              "resources": {
                "footprint": {
                  "read_only": [],
                  "read_write": [
                    {
                      "contract_data": {
                        "contract": "CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC",
                        "key": "ledger_key_contract_instance",
                        "durability": "persistent"
                      }
                    }
                  ]
                },
                "instructions": 146813,
                "disk_read_bytes": 0,
                "write_bytes": 248
              },
              "resource_fee": "2791936"
            }
          }
        },
        "signatures": [
          {
            "hint": "47a62d00",
            "signature": "2b0edc5ba9423e0ac764466574945855b74c3207b7f2ae69a433a16bdf9c293dc2bc58a71778a4e5e0143e6a4135e0c66da5a79af4b31d857a29696de9240d04"
          }
        ]
      }
    }
    JSON;

    public function testTransactionEnvelopeMatchesSpecStructurally(): void
    {
        $envelope = XdrTransactionEnvelope::fromBase64Xdr(self::SPEC_BASE64);
        $actual = $envelope->toJson();

        $expectedDecoded = json_decode(self::SPEC_JSON, true, 512, JSON_THROW_ON_ERROR);
        $actualDecoded = json_decode($actual, true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(
            $expectedDecoded,
            $actualDecoded,
            'PHP SEP-51 output for the canonical TransactionEnvelope example diverges from SEP-0051 §Examples'
        );
    }

    public function testTransactionEnvelopeRoundTripsBackToOriginalXdr(): void
    {
        $envelope = XdrTransactionEnvelope::fromBase64Xdr(self::SPEC_BASE64);
        $json = $envelope->toJson();
        $reconstructed = XdrTransactionEnvelope::fromJson($json);
        $this->assertSame(
            self::SPEC_BASE64,
            $reconstructed->toBase64Xdr(),
            'Canonical TransactionEnvelope JSON must round-trip byte-identically to the original XDR'
        );
    }
}
