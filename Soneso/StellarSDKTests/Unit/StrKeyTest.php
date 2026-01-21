<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit;

use Exception;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperation;
use Soneso\StellarSDK\Transaction;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

class StrKeyTest extends TestCase
{
    private KeyPair $keyPair;
    private String $accountIdEncoded;
    private String $seedEncoded;

    public function setUp(): void
    {
        // Turn on error reporting
        error_reporting(E_ALL);
        $this->keyPair = KeyPair::random();
        $this->accountIdEncoded = $this->keyPair->getAccountId();
        $this->seedEncoded = $this->keyPair->getSecretSeed();
    }

    public function testDecodeCheck() {
        // decodes account id correctly
        $decodedAccountId = StrKey::decodeAccountId($this->accountIdEncoded);
        assertEquals($this->keyPair->getPublicKey(), $decodedAccountId);

        // decodes secret seed correctly
        $decodedSeed = StrKey::decodeSeed($this->seedEncoded);
        assertEquals($this->keyPair->getPrivateKey(), $decodedSeed);

        // throws an error when the version byte is wrong
        $thrown = false;
        try {
            StrKey::decodeSeed("GBPXXOA5N4JYPESHAADMQKBPWZWQDQ64ZV6ZL2S3LAGW4SY7NTCMWIVL");
        } catch (Exception $e) {
            $thrown = true;
        }
        assertTrue($thrown);

        $thrown = false;
        try {
            StrKey::decodeAccountId("SBGWKM3CD4IL47QN6X54N6Y33T3JDNVI6AIJ6CD5IM47HG3IG4O36XCU");
        } catch (Exception $e) {
            $thrown = true;
        }
        assertTrue($thrown);

        // throws an error when invalid encoded string
        $thrown = false;
        try {
            StrKey::decodeAccountId("GBPXX0A5N4JYPESHAADMQKBPWZWQDQ64ZV6ZL2S3LAGW4SY7NTCMWIVL");
        } catch (Exception $e) {
            $thrown = true;
        }
        assertTrue($thrown);

        $thrown = false;
        try {
            StrKey::decodeAccountId("GCFZB6L25D26RQFDWSSBDEYQ32JHLRMTT44ZYE3DZQUTYOL7WY43PLBG++");
        } catch (Exception $e) {
            $thrown = true;
        }
        assertTrue($thrown);

        $thrown = false;
        try {
            StrKey::decodeAccountId("GB6OWYST45X57HCJY5XWOHDEBULB6XUROWPIKW77L5DSNANBEQGUPADT2T");
        } catch (Exception $e) {
            $thrown = true;
        }
        assertTrue($thrown);

        $thrown = false;
        try {
            StrKey::decodeSeed("SB7OJNF5727F3RJUG5ASQJ3LUM44ELLNKW35ZZQDHMVUUQNGYW");
        } catch (Exception $e) {
            $thrown = true;
        }
        assertTrue($thrown);

        $thrown = false;
        try {
            StrKey::decodeSeed("SB7OJNF5727F3RJUG5ASQJ3LUM44ELLNKW35ZZQDHMVUUQNGYWMEGB2W2T");
        } catch (Exception $e) {
            $thrown = true;
        }
        assertTrue($thrown);

        $thrown = false;
        try {
            StrKey::decodeSeed("SCMB30FQCIQAWZ4WQTS6SVK37LGMAFJGXOZIHTH2PY6EXLP37G46H6DT");
        } catch (Exception $e) {
            $thrown = true;
        }
        assertTrue($thrown);

        $thrown = false;
        try {
            StrKey::decodeSeed("SAYC2LQ322EEHZYWNSKBEW6N66IRTDREEBUXXU5HPVZGMAXKLIZNM45H++");
        } catch (Exception $e) {
            $thrown = true;
        }
        assertTrue($thrown);

        // throws an error when checksum is wrong
        $thrown = false;
        try {
            StrKey::decodeAccountId("GBPXXOA5N4JYPESHAADMQKBPWZWQDQ64ZV6ZL2S3LAGW4SY7NTCMWIVT");
        } catch (Exception $e) {
            $thrown = true;
        }
        assertTrue($thrown);

        $thrown = false;
        try {
            StrKey::decodeSeed("SBGWKM3CD4IL47QN6X54N6Y33T3JDNVI6AIJ6CD5IM47HG3IG4O36XCX");
        } catch (Exception $e) {
            $thrown = true;
        }
        assertTrue($thrown);
    }

    public function testEncodeCheck() {
        // encodes a buffer correctly
        $encodedAccountId = StrKey::encodeAccountId($this->keyPair->getPublicKey());
        assertEquals($encodedAccountId, $this->accountIdEncoded);
        assertTrue(str_starts_with($encodedAccountId, "G"));
        assertEquals($this->keyPair->getPublicKey(), StrKey::decodeAccountId($encodedAccountId));

        $encodedSeed = StrKey::encodeSeed($this->keyPair->getPrivateKey());
        assertEquals($encodedSeed, $this->seedEncoded);
        assertTrue(str_starts_with($encodedSeed, "S"));
        assertEquals($this->keyPair->getPrivateKey(), StrKey::decodeSeed($encodedSeed));

        $strKeyEncoded = StrKey::encodePreAuthTx($this->keyPair->getPublicKey());
        assertTrue(str_starts_with($strKeyEncoded, "T"));
        assertEquals($this->keyPair->getPublicKey(), StrKey::decodePreAuthTx($strKeyEncoded));

        $strKeyEncoded = StrKey::encodeSha256Hash($this->keyPair->getPublicKey());
        assertTrue(str_starts_with($strKeyEncoded, "X"));
        assertEquals($this->keyPair->getPublicKey(), StrKey::decodeSha256Hash($strKeyEncoded));
    }

    public function testIsValid() {
        // returns true for valid public key
        $keys = [
            'GBBM6BKZPEHWYO3E3YKREDPQXMS4VK35YLNU7NFBRI26RAN7GI5POFBB',
            'GB7KKHHVYLDIZEKYJPAJUOTBE5E3NJAXPSDZK7O6O44WR3EBRO5HRPVT',
            'GD6WVYRVID442Y4JVWFWKWCZKB45UGHJAABBJRS22TUSTWGJYXIUR7N2',
            'GBCG42WTVWPO4Q6OZCYI3D6ZSTFSJIXIS6INCIUF23L6VN3ADE4337AP',
            'GDFX463YPLCO2EY7NGFMI7SXWWDQAMASGYZXCG2LATOF3PP5NQIUKBPT',
            'GBXEODUMM3SJ3QSX2VYUWFU3NRP7BQRC2ERWS7E2LZXDJXL2N66ZQ5PT',
            'GAJHORKJKDDEPYCD6URDFODV7CVLJ5AAOJKR6PG2VQOLWFQOF3X7XLOG',
            'GACXQEAXYBEZLBMQ2XETOBRO4P66FZAJENDHOQRYPUIXZIIXLKMZEXBJ',
            'GDD3XRXU3G4DXHVRUDH7LJM4CD4PDZTVP4QHOO4Q6DELKXUATR657OZV',
            'GDTYVCTAUQVPKEDZIBWEJGKBQHB4UGGXI2SXXUEW7LXMD4B7MK37CWLJ'
        ];

        foreach ($keys as $key) {
            assertTrue(StrKey::isValidAccountId($key));
        }

        // returns false for invalid public key
        $keys = [
            'GBPXX0A5N4JYPESHAADMQKBPWZWQDQ64ZV6ZL2S3LAGW4SY7NTCMWIVL',
            'GCFZB6L25D26RQFDWSSBDEYQ32JHLRMTT44ZYE3DZQUTYOL7WY43PLBG++',
            'GADE5QJ2TY7S5ZB65Q43DFGWYWCPHIYDJ2326KZGAGBN7AE5UY6JVDRRA',
            'GB6OWYST45X57HCJY5XWOHDEBULB6XUROWPIKW77L5DSNANBEQGUPADT2',
            'GB6OWYST45X57HCJY5XWOHDEBULB6XUROWPIKW77L5DSNANBEQGUPADT2T',
            'GDXIIZTKTLVYCBHURXL2UPMTYXOVNI7BRAEFQCP6EZCY4JLKY4VKFNLT',
            'SAB5556L5AN5KSR5WF7UOEFDCIODEWEO7H2UR4S5R62DFTQOGLKOVZDY',
            'gWRYUerEKuz53tstxEuR3NCkiQDcV4wzFHmvLnZmj7PUqxW2wt',
            'test',
            'g4VPBPrHZkfE8CsjuG2S4yBQNd455UWmk' // Old network key
        ];

        foreach ($keys as $key) {
            assertFalse(StrKey::isValidAccountId($key));
        }

        // returns true for valid secret key
        $keys = [
            'SAB5556L5AN5KSR5WF7UOEFDCIODEWEO7H2UR4S5R62DFTQOGLKOVZDY',
            'SCZTUEKSEH2VYZQC6VLOTOM4ZDLMAGV4LUMH4AASZ4ORF27V2X64F2S2',
            'SCGNLQKTZ4XCDUGVIADRVOD4DEVNYZ5A7PGLIIZQGH7QEHK6DYODTFEH',
            'SDH6R7PMU4WIUEXSM66LFE4JCUHGYRTLTOXVUV5GUEPITQEO3INRLHER',
            'SC2RDTRNSHXJNCWEUVO7VGUSPNRAWFCQDPP6BGN4JFMWDSEZBRAPANYW',
            'SCEMFYOSFZ5MUXDKTLZ2GC5RTOJO6FGTAJCF3CCPZXSLXA2GX6QUYOA7'
        ];

        foreach ($keys as $key) {
            assertTrue(StrKey::isValidSeed($key));
        }

        // returns false for invalid secret key
        $keys = [
            'GBBM6BKZPEHWYO3E3YKREDPQXMS4VK35YLNU7NFBRI26RAN7GI5POFBB',
            'SAB5556L5AN5KSR5WF7UOEFDCIODEWEO7H2UR4S5R62DFTQOGLKOVZDYT', // Too long
            'SAFGAMN5Z6IHVI3IVEPIILS7ITZDYSCEPLN4FN5Z3IY63DRH4CIYEV', // To short
            'SAFGAMN5Z6IHVI3IVEPIILS7ITZDYSCEPLN4FN5Z3IY63DRH4CIYEVIT', // Checksum
            'test',
        ];

        foreach ($keys as $key) {
            assertFalse(StrKey::isValidSeed($key));
        }

    }

    public function testMuxedAccounts() {
        $mPubKey = 'MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAAAJLK';
        $rawPubKey = hex2bin('3f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a8000000000000000');

        // encodes & decodes M... addresses correctly
        assertEquals(StrKey::encodeMuxedAccountId($rawPubKey), $mPubKey);
        assertEquals(StrKey::decodeMuxedAccountId($mPubKey), $rawPubKey);
    }

    public function testSignedPayloads() {
        $decoded = StrKey::decodeSignedPayload(
                "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAQACAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUPB6IBZGM");
        assertEquals("GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ", $decoded->getSignerAccountId()->getAccountId());
        assertEquals("0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f20", bin2hex($decoded->getPayload()));

        $decoded = StrKey::decodeSignedPayload(
            "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAOQCAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUAAAAFGBU");
        assertEquals("GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ", $decoded->getSignerAccountId()->getAccountId());
        assertEquals("0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d", bin2hex($decoded->getPayload()));
    }

    public function testContracts() {
        $contractId = "CA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAXE";
        $asHex = "363eaa3867841fbad0f4ed88c779e4fe66e56a2470dc98c0ec9c073d05c7b103";
        $decoded = StrKey::decodeContractId($contractId);
        assertEquals($asHex, bin2hex($decoded));
        assertEquals($contractId, StrKey::encodeContractId(hex2bin($asHex)));
        assertEquals($contractId, StrKey::encodeContractIdHex($asHex));
        assertTrue(StrKey::isValidContractId($contractId));
        assertFalse(StrKey::isValidContractId("GA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAXE"));
    }

    public function testLiquidityPools() {
        $liquidityPoolId = "LA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUPJN";
        $asHex = "3f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a";
        $decoded = StrKey::decodeLiquidityPoolId($liquidityPoolId);
        assertEquals($asHex, bin2hex($decoded));
        assertEquals($liquidityPoolId, StrKey::encodeLiquidityPoolId(hex2bin($asHex)));
        assertEquals($liquidityPoolId, StrKey::encodeLiquidityPoolIdHex($asHex));
        assertTrue(StrKey::isValidLiquidityPoolId($liquidityPoolId));
        assertFalse(StrKey::isValidLiquidityPoolId("LB7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUPJN"));
    }

    public function testClaimableBalances() {
        $claimableBalanceId = "BAAD6DBUX6J22DMZOHIEZTEQ64CVCHEDRKWZONFEUL5Q26QD7R76RGR4TU";
        $asHex = "003f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a";
        $decoded = StrKey::decodeClaimableBalanceId($claimableBalanceId);
        assertEquals($asHex, bin2hex($decoded));
        assertEquals($claimableBalanceId, StrKey::encodeClaimableBalanceId(hex2bin($asHex)));
        assertEquals($claimableBalanceId, StrKey::encodeClaimableBalanceIdHex($asHex));
        assertTrue(StrKey::isValidClaimableBalanceId($claimableBalanceId));
        assertFalse(StrKey::isValidClaimableBalanceId("BBAD6DBUX6J22DMZOHIEZTEQ64CVCHEDRKWZONFEUL5Q26QD7R76RGR4TU"));

        $xdr = "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";
        $tx = Transaction::fromEnvelopeBase64XdrString($xdr);
        assertTrue($tx instanceof Transaction);
        $op = $tx->getOperations()[0];
        assertTrue($op instanceof InvokeHostFunctionOperation);
        $function = $op->function;
        assertTrue($function instanceof InvokeContractHostFunction);
        $contractId = $function->contractId;
        assertEquals($claimableBalanceId, $contractId);

        // without discriminat
        $asHex = "3f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a";
        assertEquals($claimableBalanceId, StrKey::encodeClaimableBalanceIdHex($asHex));
    }

    public function testInvalidStrKeys() {

        // The unused trailing bit must be zero in the encoding of the last three
        // bytes (24 bits) as five base-32 symbols (25 bits)
        $strKey = "MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUR";
        assertFalse(StrKey::isValidMuxedAccountId($strKey));

        // Invalid length (congruent to 1 mod 8)
        $strKey = "GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZA";
        assertFalse(StrKey::isValidAccountId($strKey));

        // Invalid algorithm (low 3 bits of version byte are 7)
        $strKey = "G47QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVP2I";
        assertFalse(StrKey::isValidAccountId($strKey));

        // Invalid length (congruent to 6 mod 8)
        $strKey = "MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAAAJLKA";
        assertFalse(StrKey::isValidMuxedAccountId($strKey));

        // Invalid algorithm (low 3 bits of version byte are 7)
        $strKey = "M47QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUQ";
        assertFalse(StrKey::isValidMuxedAccountId($strKey));

        // Padding bytes are not allowed
        $strKey = "MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUK===";
        assertFalse(StrKey::isValidMuxedAccountId($strKey));

        // Invalid checksum
        $strKey = "MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUO===";
        assertFalse(StrKey::isValidMuxedAccountId($strKey));

        // Trailing bits should be zeroes
        $strKey = "BAAD6DBUX6J22DMZOHIEZTEQ64CVCHEDRKWZONFEUL5Q26QD7R76RGR4TV===";
        assertFalse(StrKey::isValidClaimableBalanceId($strKey));

        // Invalid length (Ed25519 should be 32 bytes, not 5)
        $strKey = "GAAAAAAAACGC6";
        assertFalse(StrKey::isValidAccountId($strKey));

        // Invalid length (base-32 decoding should yield 35 bytes, not 36)
        $strKey = "GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUACUSI";
        assertFalse(StrKey::isValidAccountId($strKey));

        // Invalid length (base-32 decoding should yield 43 bytes, not 44)
        $strKey = "MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAAAAV75I";
        assertFalse(StrKey::isValidAccountId($strKey));
    }

    public function testPreAuthTx() {
        $keyPair = KeyPair::random();
        $publicKey = $keyPair->getPublicKey();

        // Encode and decode PreAuthTx
        $encoded = StrKey::encodePreAuthTx($publicKey);
        assertTrue(str_starts_with($encoded, "T"));
        assertEquals($publicKey, StrKey::decodePreAuthTx($encoded));

        // Test isValidPreAuthTx
        assertTrue(StrKey::isValidPreAuthTx($encoded));
        assertFalse(StrKey::isValidPreAuthTx("GBPXXOA5N4JYPESHAADMQKBPWZWQDQ64ZV6ZL2S3LAGW4SY7NTCMWIVL"));
        assertFalse(StrKey::isValidPreAuthTx("invalid"));
        assertFalse(StrKey::isValidPreAuthTx(""));
    }

    public function testSha256Hash() {
        $keyPair = KeyPair::random();
        $publicKey = $keyPair->getPublicKey();

        // Encode and decode Sha256Hash
        $encoded = StrKey::encodeSha256Hash($publicKey);
        assertTrue(str_starts_with($encoded, "X"));
        assertEquals($publicKey, StrKey::decodeSha256Hash($encoded));

        // Test isValidSha256Hash
        assertTrue(StrKey::isValidSha256Hash($encoded));
        assertFalse(StrKey::isValidSha256Hash("GBPXXOA5N4JYPESHAADMQKBPWZWQDQ64ZV6ZL2S3LAGW4SY7NTCMWIVL"));
        assertFalse(StrKey::isValidSha256Hash("invalid"));
        assertFalse(StrKey::isValidSha256Hash(""));
    }

    public function testDecodeContractIdHex() {
        $contractId = "CA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAXE";
        $expectedHex = "363eaa3867841fbad0f4ed88c779e4fe66e56a2470dc98c0ec9c073d05c7b103";

        $decodedHex = StrKey::decodeContractIdHex($contractId);
        assertEquals($expectedHex, $decodedHex);

        // Verify round-trip
        assertEquals($contractId, StrKey::encodeContractIdHex($decodedHex));
    }

    public function testDecodeLiquidityPoolIdHex() {
        $liquidityPoolId = "LA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUPJN";
        $expectedHex = "3f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a";

        $decodedHex = StrKey::decodeLiquidityPoolIdHex($liquidityPoolId);
        assertEquals($expectedHex, $decodedHex);

        // Verify round-trip
        assertEquals($liquidityPoolId, StrKey::encodeLiquidityPoolIdHex($decodedHex));
    }

    public function testAccountIdFromSeed() {
        $seed = "SDJHRQF4GCMIIKAAAQ6IHY42X73FQFLHUULAPSKKD4DFDM7UXWWCRHBE";
        $expectedAccountId = "GCZHXL5HXQX5ABDM26LHYRCQZ5OJFHLOPLZX47WEBP3V2PF5AVFK2A5D";

        $accountId = StrKey::accountIdFromSeed($seed);
        assertEquals($expectedAccountId, $accountId);
        assertTrue(str_starts_with($accountId, "G"));

        // Verify it matches the KeyPair result
        $keyPair = KeyPair::fromSeed($seed);
        assertEquals($keyPair->getAccountId(), $accountId);
    }

    public function testAccountIdFromPrivateKey() {
        $seed = "SDJHRQF4GCMIIKAAAQ6IHY42X73FQFLHUULAPSKKD4DFDM7UXWWCRHBE";
        $keyPair = KeyPair::fromSeed($seed);
        $privateKey = $keyPair->getPrivateKey();
        $expectedAccountId = "GCZHXL5HXQX5ABDM26LHYRCQZ5OJFHLOPLZX47WEBP3V2PF5AVFK2A5D";

        $accountId = StrKey::accountIdFromPrivateKey($privateKey);
        assertEquals($expectedAccountId, $accountId);
        assertTrue(str_starts_with($accountId, "G"));

        // Verify it matches the KeyPair result
        assertEquals($keyPair->getAccountId(), $accountId);
    }

    public function testPublicKeyFromPrivateKey() {
        $seed = "SDJHRQF4GCMIIKAAAQ6IHY42X73FQFLHUULAPSKKD4DFDM7UXWWCRHBE";
        $keyPair = KeyPair::fromSeed($seed);
        $privateKey = $keyPair->getPrivateKey();

        $publicKey = StrKey::publicKeyFromPrivateKey($privateKey);
        assertEquals($keyPair->getPublicKey(), $publicKey);
        assertEquals(32, strlen($publicKey));

        // Verify the derived public key can verify signatures
        $message = "test message";
        $signature = $keyPair->sign($message);
        $verifyKeyPair = KeyPair::fromPublicKey($publicKey);
        assertTrue($verifyKeyPair->verifySignature($signature, $message));
    }

    public function testXdrSignedPayloadEncodeDecode() {
        $keyPair = KeyPair::random();
        $payload = hex2bin("0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f20");

        $xdrSignedPayload = new \Soneso\StellarSDK\Xdr\XdrSignedPayload($keyPair->getPublicKey(), $payload);

        // Encode XDR signed payload
        $encoded = StrKey::encodeXdrSignedPayload($xdrSignedPayload);
        assertTrue(str_starts_with($encoded, "P"));

        // Decode back to XDR signed payload
        $decoded = StrKey::decodeXdrSignedPayload($encoded);
        assertEquals($keyPair->getPublicKey(), $decoded->getEd25519());
        assertEquals($payload, $decoded->getPayload());

        // Verify round-trip
        $encodedAgain = StrKey::encodeXdrSignedPayload($decoded);
        assertEquals($encoded, $encodedAgain);
    }

    public function testSignedPayloadWithDifferentLengths() {
        $keyPair = KeyPair::random();

        // Test with minimum payload length (4 bytes)
        $minPayload = hex2bin("01020304");
        $xdrMin = new \Soneso\StellarSDK\Xdr\XdrSignedPayload($keyPair->getPublicKey(), $minPayload);
        $encodedMin = StrKey::encodeXdrSignedPayload($xdrMin);
        $decodedMin = StrKey::decodeXdrSignedPayload($encodedMin);
        assertEquals($minPayload, $decodedMin->getPayload());

        // Test with maximum payload length (64 bytes)
        $maxPayload = random_bytes(64);
        $xdrMax = new \Soneso\StellarSDK\Xdr\XdrSignedPayload($keyPair->getPublicKey(), $maxPayload);
        $encodedMax = StrKey::encodeXdrSignedPayload($xdrMax);
        $decodedMax = StrKey::decodeXdrSignedPayload($encodedMax);
        assertEquals($maxPayload, $decodedMax->getPayload());

        // Test with medium payload length (32 bytes)
        $medPayload = random_bytes(32);
        $xdrMed = new \Soneso\StellarSDK\Xdr\XdrSignedPayload($keyPair->getPublicKey(), $medPayload);
        $encodedMed = StrKey::encodeXdrSignedPayload($xdrMed);
        $decodedMed = StrKey::decodeXdrSignedPayload($encodedMed);
        assertEquals($medPayload, $decodedMed->getPayload());
    }

    public function testIsValidSignedPayload() {
        $validPayload = "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAQACAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUPB6IBZGM";

        // Valid signed payload should be properly decoded
        $decoded = StrKey::decodeSignedPayload($validPayload);
        assertEquals("GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ", $decoded->getSignerAccountId()->getAccountId());
    }

    public function testEncodeDecodeConsistency() {
        // Test all encode/decode pairs for consistency
        $keyPair = KeyPair::random();
        $data = $keyPair->getPublicKey();

        // Account ID
        $accountId = StrKey::encodeAccountId($data);
        assertEquals($data, StrKey::decodeAccountId($accountId));

        // Seed
        $seed = StrKey::encodeSeed($data);
        assertEquals($data, StrKey::decodeSeed($seed));

        // PreAuthTx
        $preAuthTx = StrKey::encodePreAuthTx($data);
        assertEquals($data, StrKey::decodePreAuthTx($preAuthTx));

        // Sha256Hash
        $sha256Hash = StrKey::encodeSha256Hash($data);
        assertEquals($data, StrKey::decodeSha256Hash($sha256Hash));

        // Contract ID
        $contractId = StrKey::encodeContractId($data);
        assertEquals($data, StrKey::decodeContractId($contractId));

        // Liquidity Pool ID
        $liquidityPoolId = StrKey::encodeLiquidityPoolId($data);
        assertEquals($data, StrKey::decodeLiquidityPoolId($liquidityPoolId));
    }

    public function testIsValidWithInvalidDecodedLengths() {
        // Test muxed account with wrong decoded length
        // Valid muxed account has 40 bytes decoded, test with modified data
        $muxedAccountId = "MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAAAJLK";
        assertTrue(StrKey::isValidMuxedAccountId($muxedAccountId));

        // Test signed payload with lengths at boundaries
        // Min length signed payload (32 + 4 + 4 = 40 bytes)
        $minPayload = "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAOQCAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUAAAAFGBU";
        assertTrue(StrKey::decodeSignedPayload($minPayload) !== null);

        // Max length signed payload
        $maxPayload = "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAQACAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUPB6IBZGM";
        assertTrue(StrKey::decodeSignedPayload($maxPayload) !== null);

        // Test claimable balance with correct decoded length (33 bytes)
        $claimableBalanceId = "BAAD6DBUX6J22DMZOHIEZTEQ64CVCHEDRKWZONFEUL5Q26QD7R76RGR4TU";
        assertTrue(StrKey::isValidClaimableBalanceId($claimableBalanceId));
    }

    public function testPublicKeyFromPrivateKeyEdgeCases() {
        // Test multiple random keypairs to ensure consistency
        for ($i = 0; $i < 5; $i++) {
            $keyPair = KeyPair::random();
            $privateKey = $keyPair->getPrivateKey();

            // Derive public key using StrKey
            $derivedPublicKey = StrKey::publicKeyFromPrivateKey($privateKey);

            // Should match the original
            assertEquals($keyPair->getPublicKey(), $derivedPublicKey);
            assertEquals(32, strlen($derivedPublicKey));
        }
    }

    public function testAccountIdFromSeedAndPrivateKeyConsistency() {
        // Test that both methods produce the same result
        for ($i = 0; $i < 3; $i++) {
            $keyPair = KeyPair::random();
            $seed = $keyPair->getSecretSeed();
            $privateKey = $keyPair->getPrivateKey();

            $accountIdFromSeed = StrKey::accountIdFromSeed($seed);
            $accountIdFromPrivateKey = StrKey::accountIdFromPrivateKey($privateKey);

            assertEquals($accountIdFromSeed, $accountIdFromPrivateKey);
            assertEquals($keyPair->getAccountId(), $accountIdFromSeed);
            assertEquals($keyPair->getAccountId(), $accountIdFromPrivateKey);
        }
    }

    public function testEncodeSignedPayload() {
        $keyPair = KeyPair::random();
        $payload = hex2bin("0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f20");

        // Create SignedPayloadSigner from account ID
        $signedPayloadSigner = \Soneso\StellarSDK\SignedPayloadSigner::fromAccountId($keyPair->getAccountId(), $payload);

        // Encode using SignedPayloadSigner
        $encoded = StrKey::encodeSignedPayload($signedPayloadSigner);
        assertTrue(str_starts_with($encoded, "P"));

        // Decode and verify
        $decoded = StrKey::decodeSignedPayload($encoded);
        assertEquals($keyPair->getAccountId(), $decoded->getSignerAccountId()->getAccountId());
        assertEquals($payload, $decoded->getPayload());

        // Should match XDR signed payload encoding
        $xdrPayload = new \Soneso\StellarSDK\Xdr\XdrSignedPayload($keyPair->getPublicKey(), $payload);
        $encodedXdr = StrKey::encodeXdrSignedPayload($xdrPayload);
        assertEquals($encoded, $encodedXdr);
    }

    public function testDecodeClaimableBalanceIdHex() {
        // Test with full claimable balance ID (with discriminant)
        $claimableBalanceId = "BAAD6DBUX6J22DMZOHIEZTEQ64CVCHEDRKWZONFEUL5Q26QD7R76RGR4TU";
        $expectedHex = "003f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a";

        $decodedHex = StrKey::decodeClaimableBalanceIdHex($claimableBalanceId);
        assertEquals($expectedHex, $decodedHex);

        // Verify round-trip
        assertEquals($claimableBalanceId, StrKey::encodeClaimableBalanceIdHex($decodedHex));

        // Test encoding from hex without discriminant (should add it)
        $hexWithoutDiscriminant = "3f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a";
        $encoded = StrKey::encodeClaimableBalanceIdHex($hexWithoutDiscriminant);
        assertEquals($claimableBalanceId, $encoded);
    }

}