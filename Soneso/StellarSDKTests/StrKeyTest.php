<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use Exception;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
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

}