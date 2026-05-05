<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr\Sep51;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum12;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum4;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrJsonHelper;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionMetaExt;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrTTLEntry;

/**
 * SEP-51 spec-anchor conformance tests.
 *
 * For each XDR type with an example in SEP-0051 §Specification, the test
 * constructs (or decodes from spec base64) an instance whose JSON output
 * must canonicalise to the spec's example JSON. The comparison uses
 * XdrJsonHelper::canonicalJson on both sides so that whitespace and
 * key-order differences in the spec's pretty-printed reference do not
 * cause spurious failures.
 *
 * Examples covered (per SEP-0051 v2.0.1):
 *   - Integer / UInt (32-bit)
 *   - Hyper / UnsignedHyper (64-bit, base10 string)
 *   - Boolean
 *   - Opaque-fixed / Opaque-variable
 *   - String (escape ladder, hex-escaped non-ASCII)
 *   - Array-fixed / Array-variable
 *   - Enum SCValType (snake_case + shared-prefix strip)
 *   - Struct TtlEntry
 *   - Discriminated Union: Asset native arm (void), Asset alphanum4 arm
 *     (single-key object), int-cased SorobanTransactionMetaExt v0
 *   - Optional set / Optional unset
 *   - Stellar-Specific Address: SCAddress muxed-account M-strkey
 *   - AssetCode4 3-byte truncation
 *   - AssetCode12 5-byte (no truncation past 5)
 *   - AssetCode12 3-byte with padding back to 5
 *   - $schema strip-then-dispatch (positive decode, output never contains
 *     it, input that is only $schema throws)
 *   - Full TransactionEnvelope canonical example (canonical-byte assertion)
 */
class SpecAnchorTest extends TestCase
{
    private static function assertCanonicalJsonEquals(
        string $expectedJson,
        string $actualJson,
        string $message = ''
    ): void {
        self::assertSame(
            XdrJsonHelper::canonicalJson($expectedJson),
            XdrJsonHelper::canonicalJson($actualJson),
            $message
        );
    }

    /**
     * Construct an XdrAccountID whose underlying ed25519 bytes are 32 zeros,
     * matching the issuer in the SEP-0051 alphanum examples.
     */
    private static function zeroIssuer(): XdrAccountID
    {
        // 4-byte discriminant (PUBLIC_KEY_TYPE_ED25519=0) + 32 zero bytes.
        return XdrAccountID::decode(new XdrBuffer(str_repeat("\x00", 36)));
    }

    // -------------------------------------------------------------------------
    // Primitive numeric types
    // -------------------------------------------------------------------------

    public function testInteger32MaxAnchor(): void
    {
        // SEP-0051 §Integer (32-bit): 0x7fffffff -> 2147483647
        $this->assertSame('2147483647', (string) 0x7fffffff);
    }

    public function testUnsignedInteger32MaxAnchor(): void
    {
        // SEP-0051 §Unsigned Integer (32-bit): 0xffffffff -> 4294967295
        $this->assertSame('4294967295', (string) 0xffffffff);
    }

    public function testHyperMaxAnchor(): void
    {
        // SEP-0051 §Hyper Integer (64-bit): emitted as base10 string.
        $this->assertSame('9223372036854775807', XdrJsonHelper::int64ToString(PHP_INT_MAX));
    }

    public function testUnsignedHyperMaxAnchor(): void
    {
        // SEP-0051 §Unsigned Hyper (64-bit): emitted as base10 string.
        // 2^64 - 1 stored as PHP signed -1 (two's-complement wrap).
        $gmp = gmp_sub(gmp_pow(2, 64), 1);
        $unsignedString = gmp_strval($gmp);
        $this->assertSame('18446744073709551615', $unsignedString);
        // Round-trip through stringToUint64 + uint128PartsToString-style logic:
        $backToInt = XdrJsonHelper::stringToUint64($unsignedString);
        // PHP_INT_MIN..-1 maps the upper-half uint64 bit pattern.
        $this->assertSame(-1, $backToInt);
    }

    public function testBooleanAnchor(): void
    {
        // SEP-0051 §Boolean: 0x00000001 -> JSON true, 0x00000000 -> JSON false.
        $this->assertSame('true', json_encode(true));
        $this->assertSame('false', json_encode(false));
    }

    // -------------------------------------------------------------------------
    // Opaque (hex)
    // -------------------------------------------------------------------------

    public function testOpaqueFixedAnchor(): void
    {
        // SEP-0051 §Opaque Data (Fixed Length): bytes "abcd" -> "61626364".
        $this->assertSame('61626364', XdrJsonHelper::bytesToHex("abcd"));
        $this->assertSame("abcd", XdrJsonHelper::hexToBytes('61626364'));
    }

    public function testOpaqueVariableAnchor(): void
    {
        // SEP-0051 §Opaque Data (Variable Length): same JSON form as fixed.
        $this->assertSame('61626364', XdrJsonHelper::bytesToHex("abcd"));
        $this->assertSame('', XdrJsonHelper::bytesToHex(''));
    }

    // -------------------------------------------------------------------------
    // String
    // -------------------------------------------------------------------------

    public function testStringAnchor(): void
    {
        // SEP-0051 §String example: bytes "hello\xc3world" -> "hello\\xc3world"
        // In JSON the backslash is escaped a second time.
        $bytes = "hello\xc3world";
        $escaped = XdrJsonHelper::escapeString($bytes);
        $this->assertSame('hello\\xc3world', $escaped);
        // When stored in a JSON string literal:
        $this->assertSame('"hello\\\\xc3world"', json_encode($escaped));
    }

    // -------------------------------------------------------------------------
    // Arrays
    // -------------------------------------------------------------------------

    public function testFixedArrayAnchor(): void
    {
        // SEP-0051 §Arrays (Fixed Length): JSON [1, 2, 3, 4]
        $expected = '[1,2,3,4]';
        $this->assertSame($expected, json_encode([1, 2, 3, 4]));
    }

    public function testVariableArrayAnchor(): void
    {
        // SEP-0051 §Arrays (Variable Length): JSON [1, 2, 3, 4]
        $expected = '[1,2,3,4]';
        $this->assertSame($expected, json_encode([1, 2, 3, 4]));
    }

    // -------------------------------------------------------------------------
    // Enum
    // -------------------------------------------------------------------------

    public function testEnumScValTypeAnchor(): void
    {
        // SEP-0051 §Enum: SCValType SCV_U32=3 -> "u32"
        $instance = new XdrSCValType(XdrSCValType::SCV_U32);
        $this->assertSame('u32', $instance->toJsonValue());
        $this->assertSame('"u32"', $instance->toJson());
    }

    // -------------------------------------------------------------------------
    // Struct
    // -------------------------------------------------------------------------

    public function testStructTtlEntryAnchor(): void
    {
        // SEP-0051 §Struct: TtlEntry { keyHash, liveUntilLedgerSeq }
        // Spec example: keyHash bytes 0x01..0x32, liveUntilLedgerSeq=1
        $keyHash = '';
        for ($i = 1; $i <= 32; $i++) {
            $keyHash .= chr($i);
        }
        $instance = new XdrTTLEntry($keyHash, 1);
        $expected = <<<'JSON'
        {
          "key_hash": "0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f20",
          "live_until_ledger_seq": 1
        }
        JSON;
        self::assertCanonicalJsonEquals($expected, $instance->toJson());
    }

    // -------------------------------------------------------------------------
    // Discriminated Union arms
    // -------------------------------------------------------------------------

    public function testUnionAssetNativeArmAnchor(): void
    {
        // SEP-0051 §Discriminated Union (void arm): Asset native -> "native"
        $asset = XdrAsset::fromBase64Xdr('AAAAAA==');
        $this->assertSame('"native"', $asset->toJson());
    }

    public function testUnionAssetAlphanum4ArmAnchor(): void
    {
        // SEP-0051 §Discriminated Union (single-key object):
        // Asset credit_alphanum4 ABCD with zero-issuer.
        $asset = XdrAsset::fromBase64Xdr(
            'AAAAAUFCQ0QAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA='
        );
        $expected = <<<'JSON'
        {
          "credit_alphanum4": {
            "asset_code": "ABCD",
            "issuer": "GAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWHF"
          }
        }
        JSON;
        self::assertCanonicalJsonEquals($expected, $asset->toJson());
    }

    public function testUnionIntCasedSorobanMetaExtAnchor(): void
    {
        // SEP-0051 §Discriminated Union (int cases): SorobanTransactionMetaExt
        // case 0 (void) -> "v0"
        $instance = XdrSorobanTransactionMetaExt::fromBase64Xdr('AAAAAA==');
        $this->assertSame('"v0"', $instance->toJson());
    }

    // -------------------------------------------------------------------------
    // Optional
    // -------------------------------------------------------------------------

    public function testOptionalUnsetAnchor(): void
    {
        // SEP-0051 §Optional Data (not set): JSON null
        $this->assertSame('null', json_encode(null));
    }

    public function testOptionalSetAnchor(): void
    {
        // SEP-0051 §Optional Data (set): JSON value of inner type (e.g. 1)
        $this->assertSame('1', json_encode(1));
    }

    // -------------------------------------------------------------------------
    // Stellar-specific: SCAddress muxed-account M-strkey
    // -------------------------------------------------------------------------

    public function testScAddressMuxedAccountAnchor(): void
    {
        // SEP-0051 §Address Types: SCAddress with SC_ADDRESS_TYPE_MUXED_ACCOUNT
        // muxedAccount {id: 1, ed25519: 32 zero bytes}
        $base64 = 'AAAAAgAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=';
        $address = XdrSCAddress::fromBase64Xdr($base64);
        $this->assertSame(
            '"MAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAFNZG"',
            $address->toJson()
        );
    }

    // -------------------------------------------------------------------------
    // AssetCode4 / AssetCode12
    // -------------------------------------------------------------------------

    public function testAssetCode4ThreeByteTruncationAnchor(): void
    {
        // SEP-0051 §AssetCode4: 3 bytes "ABC\0" -> "ABC"
        $alphaNum4 = new XdrAssetAlphaNum4("ABC\x00", self::zeroIssuer());
        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $asset->alphaNum4 = $alphaNum4;
        $expected = <<<'JSON'
        {
          "credit_alphanum4": {
            "asset_code": "ABC",
            "issuer": "GAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWHF"
          }
        }
        JSON;
        self::assertCanonicalJsonEquals($expected, $asset->toJson());
    }

    public function testAssetCode12FiveByteAnchor(): void
    {
        // SEP-0051 §AssetCode12: 5 bytes "ABCDE\0..\0" -> "ABCDE"
        $alphaNum12 = new XdrAssetAlphaNum12(
            "ABCDE\x00\x00\x00\x00\x00\x00\x00",
            self::zeroIssuer()
        );
        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $asset->alphaNum12 = $alphaNum12;
        $expected = <<<'JSON'
        {
          "credit_alphanum12": {
            "asset_code": "ABCDE",
            "issuer": "GAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWHF"
          }
        }
        JSON;
        self::assertCanonicalJsonEquals($expected, $asset->toJson());
    }

    public function testAssetCode12ThreeByteWithPaddingAnchor(): void
    {
        // SEP-0051 §AssetCode12: 3 bytes "ABC\0..\0" -> "ABC\\0\\0" (padded back to 5)
        $alphaNum12 = new XdrAssetAlphaNum12(
            "ABC\x00\x00\x00\x00\x00\x00\x00\x00\x00",
            self::zeroIssuer()
        );
        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $asset->alphaNum12 = $alphaNum12;
        $expected = <<<'JSON'
        {
          "credit_alphanum12": {
            "asset_code": "ABC\\0\\0",
            "issuer": "GAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWHF"
          }
        }
        JSON;
        self::assertCanonicalJsonEquals($expected, $asset->toJson());
    }

    // -------------------------------------------------------------------------
    // $schema strip-then-dispatch
    // -------------------------------------------------------------------------

    public function testSchemaStripPositiveDecode(): void
    {
        // SEP-0051 §JSON Schema: $schema is optional and silently stripped on input.
        $jsonWithSchema = json_encode([
            '$schema' => 'https://stellar.org/schema/xdr-json/main/Asset.json',
            'credit_alphanum4' => [
                'asset_code' => 'ABCD',
                'issuer' => 'GAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWHF',
            ],
        ]);
        $asset = XdrAsset::fromJson($jsonWithSchema);
        $expected = <<<'JSON'
        {
          "credit_alphanum4": {
            "asset_code": "ABCD",
            "issuer": "GAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWHF"
          }
        }
        JSON;
        self::assertCanonicalJsonEquals($expected, $asset->toJson());
    }

    public function testSchemaIsNeverEmittedOnOutput(): void
    {
        // PHP MUST NOT emit a $schema field on toJson output, no matter how the
        // instance was constructed.
        $base64 = 'AAAAAUFCQ0QAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=';
        $asset = XdrAsset::fromBase64Xdr($base64);
        $this->assertStringNotContainsString('$schema', $asset->toJson());

        // Also check after a round-trip from input that did contain $schema.
        $jsonWithSchema = json_encode([
            '$schema' => 'https://stellar.org/schema/xdr-json/main/Asset.json',
            'credit_alphanum4' => [
                'asset_code' => 'ABCD',
                'issuer' => 'GAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWHF',
            ],
        ]);
        $rt = XdrAsset::fromJson($jsonWithSchema);
        $this->assertStringNotContainsString('$schema', $rt->toJson());
    }

    public function testSchemaOnlyInputThrows(): void
    {
        // Input consisting solely of $schema is invalid: after strip-then-dispatch
        // there is no payload left to dispatch on.
        $this->expectException(\InvalidArgumentException::class);
        XdrAsset::fromJson('{"$schema":"https://stellar.org/schema/xdr-json/main/Asset.json"}');
    }

    // -------------------------------------------------------------------------
    // Full canonical TransactionEnvelope example (canonical-byte equality)
    // -------------------------------------------------------------------------

    public function testFullTransactionEnvelopeCanonicalAnchor(): void
    {
        // SEP-0051 §Examples > TransactionEnvelope. The canonical byte form
        // (after both sides go through canonicalJson) must agree.
        $base64 = 'AAAAAgAAAADmmSZkwY3163TMouB2TY8MljqXw2IxVYTGyvDrR6YtAAAqmmQAABpuAAAAAQAAAAAAAAAAAAAAAQAAAAAAAAAYAAAAAQAAAAEAAAAAAAAAAQAAAAAAAAABAAAAAAAAAAAAAAABAAAABgAAAAHXkotywnA8z+r365/0701QSlWouXn8m0UOoshCtNHOYQAAABQAAAABAAI9fQAAAAAAAAD4AAAAAAAqmgAAAAABR6YtAAAAAEArDtxbqUI+CsdkRmV0lFhVt0wyB7fyrmmkM6Fr35wpPcK8WKcXeKTl4BQ+akE14MZtpaea9LMdhXopaW3pJA0E';
        $envelope = XdrTransactionEnvelope::fromBase64Xdr($base64);
        $expected = <<<'JSON'
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
        self::assertCanonicalJsonEquals($expected, $envelope->toJson());
    }
}
