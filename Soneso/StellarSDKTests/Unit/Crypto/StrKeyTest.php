<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Crypto;

use Base32\Base32;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\CryptoException;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Crypto\VersionByte;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperation;
use Soneso\StellarSDK\Transaction;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNotEquals;
use function PHPUnit\Framework\assertNotFalse;
use function PHPUnit\Framework\assertNotSame;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;
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

    public function testClaimableBalanceDiscriminantMustBeZero() {
        // SEP-23's own vector for this rule, described there as "Invalid claimable
        // balance type (first byte of binary key is not 0)". Its checksum is correct
        // and its payload is 33 bytes, so only the discriminant can turn it away.
        $this->assertClaimableBalanceDiscriminantRejected(
            "BAAT6DBUX6J22DMZOHIEZTEQ64CVCHEDRKWZONFEUL5Q26QD7R76RGXACA",
            0x01
        );

        // Two more discriminants over the same framing, so the rule cannot be
        // satisfied by singling out one value.
        $this->assertClaimableBalanceDiscriminantRejected(
            "BABD6DBUX6J22DMZOHIEZTEQ64CVCHEDRKWZONFEUL5Q26QD7R76RGVFSY",
            0x02
        );
        $this->assertClaimableBalanceDiscriminantRejected(
            "BD7QGAYDAMBQGAYDAMBQGAYDAMBQGAYDAMBQGAYDAMBQGAYDAMBQGAZFLE",
            0xff
        );

        // SEP-23's valid vector differs from the first one above only in its
        // discriminant, so it pins down that the rule turns away nothing else.
        $valid = "BAAD6DBUX6J22DMZOHIEZTEQ64CVCHEDRKWZONFEUL5Q26QD7R76RGR4TU";
        $validHex = "003f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a";
        assertEquals($validHex, bin2hex(StrKey::decodeClaimableBalanceId($valid)));
        assertEquals($validHex, StrKey::decodeClaimableBalanceIdHex($valid));
        assertEquals($valid, StrKey::encodeClaimableBalanceIdHex($validHex));
        assertTrue(StrKey::isValidClaimableBalanceId($valid));
    }

    public function testEncodeClaimableBalanceIdRejectsNonZeroDiscriminant() {
        $hash = hex2bin("3f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a");
        $expected = "BAAD6DBUX6J22DMZOHIEZTEQ64CVCHEDRKWZONFEUL5Q26QD7R76RGR4TU";

        // The bare 32-byte hash: the zero discriminant is prepended for the caller.
        assertEquals($expected, StrKey::encodeClaimableBalanceId($hash));

        // The same hash behind a discriminant the caller supplied itself.
        assertEquals($expected, StrKey::encodeClaimableBalanceId("\x00" . $hash));

        // A 33-byte payload the decode path would refuse to read back must not be
        // minted.
        try {
            StrKey::encodeClaimableBalanceId("\x01" . $hash);
            $this->fail("A claimable balance id with a non-zero discriminant should raise");
        } catch (InvalidArgumentException $e) {
            assertEquals($this->claimableBalanceDiscriminantMessage(0x01), $e->getMessage());
        }

        // The hex encoder passes its decoded bytes to encodeClaimableBalanceId(),
        // so it turns away the same input without a rule of its own.
        try {
            StrKey::encodeClaimableBalanceIdHex(
                "ff3f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a"
            );
            $this->fail("A claimable balance id with a non-zero discriminant should raise");
        } catch (InvalidArgumentException $e) {
            assertEquals($this->claimableBalanceDiscriminantMessage(0xff), $e->getMessage());
        }
    }

    public function testEncodeClaimableBalanceIdAcceptsTheXdrForm() {
        $hashHex = "3f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a";
        $expected = "BAAD6DBUX6J22DMZOHIEZTEQ64CVCHEDRKWZONFEUL5Q26QD7R76RGR4TU";

        // The XDR form carries the discriminant as 4 big-endian bytes ahead of the
        // hash; its hexadecimal is the id Horizon reports.
        assertEquals($expected, StrKey::encodeClaimableBalanceId(hex2bin("00000000" . $hashHex)));
        assertEquals($expected, StrKey::encodeClaimableBalanceIdHex("00000000" . $hashHex));

        // A non-zero discriminant is refused whichever of its 4 bytes carries it, so
        // a value that is zero in its low byte cannot slip through the narrowing.
        foreach (["00000001", "01000000"] as $discriminantHex) {
            try {
                StrKey::encodeClaimableBalanceIdHex($discriminantHex . $hashHex);
                $this->fail("A claimable balance id with a non-zero discriminant should raise");
            } catch (InvalidArgumentException $e) {
                assertEquals(
                    $this->claimableBalanceDiscriminantMessage(hexdec($discriminantHex)),
                    $e->getMessage()
                );
            }
        }
    }

    /**
     * Asserts that a B-strkey carrying $discriminant is turned away by every
     * claimable balance entry point that reads one.
     *
     * The message is compared in full so that the test fails if the string ever
     * starts being rejected by the encoded-length, checksum or payload-length rule
     * instead of by the discriminant rule.
     */
    private function assertClaimableBalanceDiscriminantRejected(string $strkey, int $discriminant) : void {
        $expectedMessage = $this->claimableBalanceDiscriminantMessage($discriminant);
        try {
            StrKey::decodeClaimableBalanceId($strkey);
            $this->fail("A claimable balance id with a non-zero discriminant should raise");
        } catch (InvalidArgumentException $e) {
            assertEquals($expectedMessage, $e->getMessage());
        }
        try {
            StrKey::decodeClaimableBalanceIdHex($strkey);
            $this->fail("A claimable balance id with a non-zero discriminant should raise");
        } catch (InvalidArgumentException $e) {
            assertEquals($expectedMessage, $e->getMessage());
        }
        assertFalse(StrKey::isValidClaimableBalanceId($strkey));
    }

    private function claimableBalanceDiscriminantMessage(int $discriminant) : string {
        return sprintf(
            'Claimable balance discriminant 0x%02X is not defined:'
                . ' CLAIMABLE_BALANCE_ID_TYPE_V0 (0) is the only case ClaimableBalanceID has',
            $discriminant
        );
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

    public function testPublicKeyFromPrivateKeyInvalidInput() {
        $this->expectException(CryptoException::class);
        StrKey::publicKeyFromPrivateKey('invalid');
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

        // Payload lengths that pad up to one 4-byte region (1 to 4 raw bytes)
        foreach ([1, 2, 3] as $shortLength) {
            $shortPayload = random_bytes($shortLength);
            $xdrShort = new \Soneso\StellarSDK\Xdr\XdrSignedPayload($keyPair->getPublicKey(), $shortPayload);
            $decodedShort = StrKey::decodeXdrSignedPayload(StrKey::encodeXdrSignedPayload($xdrShort));
            assertEquals($shortPayload, $decodedShort->getPayload());
        }

        // Test with the smallest padded payload region (4 bytes, a 4-byte raw payload)
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

    public function testSignedPayloadLengthBounds() {
        // A zero-length payload is valid XDR but has no SEP-23 strkey the
        // ecosystem accepts (1 to 64 payload bytes), so every encode and
        // decode path refuses it.
        $accountId = "GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ";
        $pk = StrKey::decodeAccountId($accountId);
        $zeroLengthStrkey = "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAKH4Y";

        try {
            StrKey::encodeXdrSignedPayload(new \Soneso\StellarSDK\Xdr\XdrSignedPayload($pk, ""));
            $this->fail("Encoding a zero-length XdrSignedPayload should raise");
        } catch (\InvalidArgumentException $e) {
            assertTrue(str_contains($e->getMessage(), "Zero-length signed payload"));
        }

        // SignedPayloadSigner enforces the bounds at construction, so the
        // encodeSignedPayload path cannot receive a zero-length payload.
        try {
            \Soneso\StellarSDK\SignedPayloadSigner::fromPublicKey($pk, "");
            $this->fail("Constructing a zero-length SignedPayloadSigner should raise");
        } catch (\InvalidArgumentException $e) {
            assertTrue(str_contains($e->getMessage(), "invalid payload length 0"));
        }

        // 63 characters: below the 69-character minimum, which is the shortest
        // P-strkey a 1-byte payload can produce.
        assertEquals(63, strlen($zeroLengthStrkey));
        try {
            StrKey::decodeXdrSignedPayload($zeroLengthStrkey);
            $this->fail("Decoding a zero-length P-strkey should raise");
        } catch (\InvalidArgumentException $e) {
            assertSame("P-strkey must be 69 to 165 characters long, 63 characters given", $e->getMessage());
        }

        try {
            StrKey::decodeSignedPayload($zeroLengthStrkey);
            $this->fail("Decoding a zero-length P-strkey should raise");
        } catch (\InvalidArgumentException $e) {
            assertSame("P-strkey must be 69 to 165 characters long, 63 characters given", $e->getMessage());
        }

        assertFalse(StrKey::isValidSignedPayload($zeroLengthStrkey));

        // A zero-length payload inside the length bounds, so that the rule which
        // turns it away is the one that reads the declared payload length rather
        // than the one that measures the string. 69 characters: the 32-byte
        // signer, a length prefix of 0, and four further bytes that the prefix
        // says are not payload.
        $inBoundsZeroLength = "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAABO6A";
        assertSame(69, strlen($inBoundsZeroLength));
        $this->assertSignedPayloadRejected(
            $inBoundsZeroLength,
            'Zero-length signed payload has no SEP-23 strkey representation'
        );

        // Above the SEP-23 maximum of 64 payload bytes.
        try {
            StrKey::encodeXdrSignedPayload(
                new \Soneso\StellarSDK\Xdr\XdrSignedPayload($pk, str_repeat("\x01", 65))
            );
            $this->fail("Encoding a 65-byte signed payload should raise");
        } catch (\InvalidArgumentException $e) {
            assertTrue(str_contains($e->getMessage(), "exceeds the maximum"));
        }

        // The decode paths enforce the maximum as well: a structurally
        // well-formed P-strkey carrying a 65-byte payload for the
        // \x88-repeated signer key, kept as a literal because StrKey
        // cannot produce it. A 65-byte payload pads to 68 bytes and takes 172
        // characters to encode, past the 165-character maximum that the largest
        // legal payload of 64 bytes reaches.
        $overMaxStrkey = "PCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIQAAAABAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQAAAAH5MA";
        assertEquals(172, strlen($overMaxStrkey));
        try {
            StrKey::decodeXdrSignedPayload($overMaxStrkey);
            $this->fail("Decoding a 65-byte-payload P-strkey should raise");
        } catch (\InvalidArgumentException $e) {
            assertSame("P-strkey must be 69 to 165 characters long, 172 characters given", $e->getMessage());
        }
        try {
            StrKey::decodeSignedPayload($overMaxStrkey);
            $this->fail("Decoding a 65-byte-payload P-strkey should raise");
        } catch (\InvalidArgumentException $e) {
            assertSame("P-strkey must be 69 to 165 characters long, 172 characters given", $e->getMessage());
        }

        assertFalse(StrKey::isValidSignedPayload($overMaxStrkey));

        // One byte is the minimum representable payload and round-trips.
        $oneByte = StrKey::encodeXdrSignedPayload(
            new \Soneso\StellarSDK\Xdr\XdrSignedPayload($pk, "\x01")
        );
        assertEquals("\x01", StrKey::decodeXdrSignedPayload($oneByte)->getPayload());
    }

    public function testIsValidSignedPayload() {
        // The two valid vectors from SEP-23 (32-byte and 29-byte payloads).
        assertTrue(StrKey::isValidSignedPayload(
            "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAQACAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUPB6IBZGM"));
        assertTrue(StrKey::isValidSignedPayload(
            "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAOQCAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUAAAAFGBU"));

        // Zero-length payload, 69 characters, so the length bounds pass and the
        // declared payload length is what turns it away.
        assertFalse(StrKey::isValidSignedPayload(
            "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAABO6A"));
        // Zero-length payload encoded without the four trailing bytes: 63
        // characters, below the 69 the shortest legal payload reaches.
        assertFalse(StrKey::isValidSignedPayload(
            "PCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIQAAAAAAI4NI"));
        // 65-byte payload, which takes 172 characters and so runs past the
        // 165-character maximum.
        assertFalse(StrKey::isValidSignedPayload(
            "PCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIRCEIQAAAABAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQAAAAH5MA"));
        // SEP-23 invalid vector: length prefix shorter than the payload.
        assertFalse(StrKey::isValidSignedPayload(
            "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAQACAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUPB6IAAAAAAAAPM"));
        // Wrong strkey type entirely.
        assertFalse(StrKey::isValidSignedPayload(
            "GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ"));
    }

    public function testSignedPayloadRejectsSep23InvalidVectors() {
        // The three invalid signed-payload vectors from SEP-23. Each message is
        // compared in full, so a vector that starts being turned away by a
        // different rule than the one it was written for fails the test.

        // "Length prefix specifies length that is shorter than payload": the
        // prefix declares 32 bytes, and 4 bytes remain after the padded payload.
        $shorterThanPayload = "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAQACAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUPB6IAAAAAAAAPM";
        $this->assertSignedPayloadRejected(
            $shorterThanPayload,
            'Signed payload declares 32 payload bytes, but the decoded data is 72 bytes where 68 are expected'
        );

        // "Length prefix specifies length that is longer than payload": the
        // declared 32 bytes run past the end of the decoded data, so the read of
        // the payload stops there.
        $longerThanPayload = "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAOQCAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4Z2PQ";
        $this->assertSignedPayloadRejected($longerThanPayload, 'Unexpected end of XDR data');

        // "No zero padding in signed payload": this vector is a truncated
        // encoding. Its 29-byte payload is followed by none of the three padding
        // bytes that reach the next 4-byte boundary, so the read of the padded
        // region runs off the end of the decoded data and no padding byte is ever
        // inspected. The padding rule itself is exercised by
        // testSignedPayloadRejectsNonZeroPadding(), which uses correctly sized
        // vectors.
        $truncatedPadding = "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAOQCAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DXFH6";
        assertSame(109, strlen($truncatedPadding));
        // 65 payload bytes: the 32-byte signer, the 4-byte length prefix and 29
        // payload bytes, with none of the three padding bytes that follow them.
        assertSame(65, strlen(Base32::decode($truncatedPadding)) - 3);
        $this->assertSignedPayloadRejected($truncatedPadding, 'Unexpected end of XDR data');
    }

    /**
     * Asserts that every signed-payload entry point turns $signedPayload away
     * with exactly $expectedMessage.
     */
    private function assertSignedPayloadRejected(string $signedPayload, string $expectedMessage) : void {
        foreach (['decodeXdrSignedPayload', 'decodeSignedPayload'] as $decodeMethod) {
            try {
                StrKey::{$decodeMethod}($signedPayload);
                $this->fail(sprintf('%s() accepted %s', $decodeMethod, $signedPayload));
            } catch (InvalidArgumentException $e) {
                assertSame($expectedMessage, $e->getMessage());
            }
        }
        assertFalse(StrKey::isValidSignedPayload($signedPayload));
    }

    public function testSignedPayloadRejectsNonZeroPadding() {
        $signerHex = "3f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a";
        $signerAccountId = "GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ";
        $payload29Hex = "0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d";

        // Two 114-character strings carrying the same signer and the same 29-byte
        // payload. They differ only in the three padding bytes, zero in the first
        // and 0xff in the second. Accepting both would give one signer two
        // spellings, so any string-level comparison of it could be evaded.
        $canonical = "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAOQCAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUAAAAFGBU";
        $threePaddingBytes = "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAOQCAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DX77776K34";
        assertNotEquals($canonical, $threePaddingBytes);
        assertEquals(strlen($canonical), strlen($threePaddingBytes));

        $xdrSignedPayload = StrKey::decodeXdrSignedPayload($canonical);
        assertEquals($signerHex, bin2hex($xdrSignedPayload->getEd25519()));
        assertEquals($payload29Hex, bin2hex($xdrSignedPayload->getPayload()));

        $signedPayloadSigner = StrKey::decodeSignedPayload($canonical);
        assertEquals($signerAccountId, $signedPayloadSigner->getSignerAccountId()->getAccountId());
        assertEquals($payload29Hex, bin2hex($signedPayloadSigner->getPayload()));
        assertTrue(StrKey::isValidSignedPayload($canonical));

        // The padding follows the 32-byte signer, the 4-byte length prefix and the
        // payload, so a 29-byte payload puts the first padding byte at offset 65.
        $this->assertNonZeroSignedPayloadPaddingRejected($threePaddingBytes, 0xff, 65);

        // A 31-byte payload pads with one byte and a 30-byte payload with two, so
        // the rule reaches every padding width rather than only the three bytes a
        // 29-byte payload leaves.
        $payload31Hex = "0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f";
        $onePaddingByte = "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAPQCAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUPB6AAQJU";
        $onePaddingByteSet = "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAPQCAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUPB777AKM";
        assertEquals($payload31Hex, bin2hex(StrKey::decodeXdrSignedPayload($onePaddingByte)->getPayload()));
        $this->assertNonZeroSignedPayloadPaddingRejected($onePaddingByteSet, 0xff, 67);

        $payload30Hex = "0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e";
        $twoPaddingBytes = "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAPACAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUPAAAEB2M";
        $twoPaddingBytesSet = "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAPACAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUPKXK4ECY";
        assertEquals($payload30Hex, bin2hex(StrKey::decodeXdrSignedPayload($twoPaddingBytes)->getPayload()));
        // Both padding bytes carry 0xab; the rejection names the first one.
        $this->assertNonZeroSignedPayloadPaddingRejected($twoPaddingBytesSet, 0xab, 66);

        // A payload whose length is a multiple of four leaves no padding region at
        // all, so the rule has nothing to inspect and must not misfire.
        $payload28Hex = "0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c";
        $noPadding = "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAOACAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4FBXQ";
        assertEquals($payload28Hex, bin2hex(StrKey::decodeXdrSignedPayload($noPadding)->getPayload()));
        assertEquals($payload28Hex, bin2hex(StrKey::decodeSignedPayload($noPadding)->getPayload()));
        assertTrue(StrKey::isValidSignedPayload($noPadding));
    }

    /**
     * Asserts that a correctly sized P-strkey whose padding region carries
     * $paddingByte at $offset is rejected by every signed-payload entry point.
     * The message is compared in full so that the test fails if the string ever
     * starts being turned away by the declared-length or exact-fit rule instead
     * of by the zero-padding rule.
     */
    private function assertNonZeroSignedPayloadPaddingRejected(string $signedPayload, int $paddingByte, int $offset) : void {
        $expectedMessage = sprintf(
            'Signed payload padding must be zero, byte 0x%02X found at offset %d',
            $paddingByte,
            $offset
        );
        try {
            StrKey::decodeXdrSignedPayload($signedPayload);
            $this->fail("A signed payload with non-zero padding should raise");
        } catch (InvalidArgumentException $e) {
            assertEquals($expectedMessage, $e->getMessage());
        }
        try {
            StrKey::decodeSignedPayload($signedPayload);
            $this->fail("A signed payload with non-zero padding should raise");
        } catch (InvalidArgumentException $e) {
            assertEquals($expectedMessage, $e->getMessage());
        }
        assertFalse(StrKey::isValidSignedPayload($signedPayload));
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

    /**
     * @dataProvider validVectorProvider
     */
    public function testValidVectorsRoundTripForEveryVersionByte(
        string $prefix,
        string $strKey,
        string $payloadHex,
        string $encodeMethod,
        string $decodeMethod,
        string $isValidMethod
    ) : void {
        $payload = hex2bin($payloadHex);
        assertSame($prefix, $strKey[0]);
        assertSame($payload, StrKey::{$decodeMethod}($strKey));
        assertSame($strKey, StrKey::{$encodeMethod}($payload));
        assertTrue(StrKey::{$isValidMethod}($strKey));
    }

    /**
     * The valid test vectors of SEP-23, one row per strkey type, so that the
     * round trip is pinned to the specification rather than to whatever this SDK
     * happens to produce. The specification gives no vector for the seed, the
     * pre-authorized transaction hash or the sha256 hash, so those three rows
     * carry a string built over the same 32-byte hash the other vectors use.
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string}>
     */
    public static function validVectorProvider() : array {
        $hash = '3f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a';
        return [
            'G — non-multiplexed account' => [
                'G',
                'GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ',
                $hash,
                'encodeAccountId', 'decodeAccountId', 'isValidAccountId',
            ],
            'M — multiplexed account, id 0' => [
                'M',
                'MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUQ',
                $hash . '0000000000000000',
                'encodeMuxedAccountId', 'decodeMuxedAccountId', 'isValidMuxedAccountId',
            ],
            'M — multiplexed account, id past the signed 64-bit maximum' => [
                'M',
                'MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAAAJLK',
                $hash . '8000000000000000',
                'encodeMuxedAccountId', 'decodeMuxedAccountId', 'isValidMuxedAccountId',
            ],
            'C — contract' => [
                'C',
                'CA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUWDA',
                $hash,
                'encodeContractId', 'decodeContractId', 'isValidContractId',
            ],
            'L — liquidity pool' => [
                'L',
                'LA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUPJN',
                $hash,
                'encodeLiquidityPoolId', 'decodeLiquidityPoolId', 'isValidLiquidityPoolId',
            ],
            'B — claimable balance' => [
                'B',
                'BAAD6DBUX6J22DMZOHIEZTEQ64CVCHEDRKWZONFEUL5Q26QD7R76RGR4TU',
                '00' . $hash,
                'encodeClaimableBalanceId', 'decodeClaimableBalanceId', 'isValidClaimableBalanceId',
            ],
            'S — secret seed' => [
                'S',
                'SA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUWVG',
                $hash,
                'encodeSeed', 'decodeSeed', 'isValidSeed',
            ],
            'T — pre-authorized transaction hash' => [
                'T',
                'TA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUPUI',
                $hash,
                'encodePreAuthTx', 'decodePreAuthTx', 'isValidPreAuthTx',
            ],
            'X — sha256 hash' => [
                'X',
                'XA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVLRR',
                $hash,
                'encodeSha256Hash', 'decodeSha256Hash', 'isValidSha256Hash',
            ],
        ];
    }

    /**
     * @dataProvider sep23ValidSignedPayloadVectorProvider
     */
    public function testSep23ValidSignedPayloadVectorsRoundTrip(
        string $strKey,
        string $signerAccountId,
        string $payloadHex
    ) : void {
        $signerKey = StrKey::decodeAccountId($signerAccountId);

        $xdrSignedPayload = StrKey::decodeXdrSignedPayload($strKey);
        assertSame($signerKey, $xdrSignedPayload->getEd25519());
        assertSame($payloadHex, bin2hex($xdrSignedPayload->getPayload()));
        assertSame($strKey, StrKey::encodeXdrSignedPayload($xdrSignedPayload));

        $signedPayloadSigner = StrKey::decodeSignedPayload($strKey);
        assertSame($signerAccountId, $signedPayloadSigner->getSignerAccountId()->getAccountId());
        assertSame($payloadHex, bin2hex($signedPayloadSigner->getPayload()));
        assertSame($strKey, StrKey::encodeSignedPayload($signedPayloadSigner));

        assertTrue(StrKey::isValidSignedPayload($strKey));
    }

    /**
     * The two valid signed payload vectors of SEP-23: one payload that is a
     * multiple of four bytes and one that is padded up to the next multiple.
     *
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function sep23ValidSignedPayloadVectorProvider() : array {
        $signerAccountId = 'GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ';
        return [
            'P — 32-byte payload' => [
                'PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAQACAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUPB6IBZGM',
                $signerAccountId,
                '0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f20',
            ],
            'P — 29-byte payload, zero padded' => [
                'PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAOQCAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUAAAAFGBU',
                $signerAccountId,
                '0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d',
            ],
        ];
    }

    /**
     * @dataProvider sep23InvalidVectorProvider
     */
    public function testSep23InvalidVectorsAreRejected(
        string $strKey,
        string $decodeMethod,
        string $isValidMethod,
        string $expectedMessage
    ) : void {
        try {
            StrKey::{$decodeMethod}($strKey);
            $this->fail(sprintf('%s() accepted %s', $decodeMethod, $strKey));
        } catch (InvalidArgumentException $e) {
            assertSame($expectedMessage, $e->getMessage());
        }
        assertFalse(StrKey::{$isValidMethod}($strKey), sprintf('%s() accepted %s', $isValidMethod, $strKey));
    }

    /**
     * All fifteen invalid test vectors of SEP-23, each paired with the decode and
     * isValid methods its prefix selects and with the message it is turned away
     * with. The key names the rule the specification wrote the vector for, and
     * the message is compared in full, so a vector that starts being rejected by
     * a different rule fails the test rather than passing for the wrong reason.
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function sep23InvalidVectorProvider() : array {
        $versionByteMismatch = 'version byte in encoded data does not match passed version byte by parameter';
        return [
            'ed25519 length 5, not 32' => [
                'GAAAAAAAACGC6',
                'decodeAccountId', 'isValidAccountId',
                'G-strkey must be 56 characters long, 13 characters given',
            ],
            'unused trailing bit of the last symbol is not zero' => [
                'MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUR',
                'decodeMuxedAccountId', 'isValidMuxedAccountId',
                'invalid encoded string',
            ],
            'encoded length congruent to 1 mod 8' => [
                'GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZA',
                'decodeAccountId', 'isValidAccountId',
                'G-strkey must be 56 characters long, 57 characters given',
            ],
            'base32 decoding yields 36 bytes, not 35' => [
                'GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUACUSI',
                'decodeAccountId', 'isValidAccountId',
                'G-strkey must be 56 characters long, 58 characters given',
            ],
            'invalid algorithm, low 3 bits of the account version byte are 7' => [
                'G47QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVP2I',
                'decodeAccountId', 'isValidAccountId',
                $versionByteMismatch,
            ],
            'encoded length congruent to 6 mod 8' => [
                'MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAAAJLKA',
                'decodeMuxedAccountId', 'isValidMuxedAccountId',
                'M-strkey must be 69 characters long, 70 characters given',
            ],
            'base32 decoding yields 44 bytes, not 43' => [
                'MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAAAAV75I',
                'decodeMuxedAccountId', 'isValidMuxedAccountId',
                'M-strkey must be 69 characters long, 71 characters given',
            ],
            'invalid algorithm, low 3 bits of the muxed version byte are 7' => [
                'M47QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUQ',
                'decodeMuxedAccountId', 'isValidMuxedAccountId',
                $versionByteMismatch,
            ],
            'base32 padding characters are not allowed' => [
                'MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUK===',
                'decodeMuxedAccountId', 'isValidMuxedAccountId',
                'M-strkey must be 69 characters long, 72 characters given',
            ],
            'invalid checksum' => [
                'MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUO',
                'decodeMuxedAccountId', 'isValidMuxedAccountId',
                'invalid checksum in encoded data',
            ],
            'signed payload length prefix shorter than the payload' => [
                'PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAQACAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUPB6IAAAAAAAAPM',
                'decodeSignedPayload', 'isValidSignedPayload',
                'Signed payload declares 32 payload bytes, but the decoded data is 72 bytes where 68 are expected',
            ],
            'signed payload length prefix longer than the payload' => [
                'PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAOQCAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4Z2PQ',
                'decodeSignedPayload', 'isValidSignedPayload',
                'Unexpected end of XDR data',
            ],
            'signed payload without zero padding' => [
                'PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAOQCAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DXFH6',
                'decodeSignedPayload', 'isValidSignedPayload',
                'Unexpected end of XDR data',
            ],
            'unused trailing 2 bits of the last symbol are not zero' => [
                'BAAD6DBUX6J22DMZOHIEZTEQ64CVCHEDRKWZONFEUL5Q26QD7R76RGR4TV',
                'decodeClaimableBalanceId', 'isValidClaimableBalanceId',
                'invalid encoded string',
            ],
            'claimable balance type is not 0' => [
                'BAAT6DBUX6J22DMZOHIEZTEQ64CVCHEDRKWZONFEUL5Q26QD7R76RGXACA',
                'decodeClaimableBalanceId', 'isValidClaimableBalanceId',
                'Claimable balance discriminant 0x01 is not defined:'
                    . ' CLAIMABLE_BALANCE_ID_TYPE_V0 (0) is the only case ClaimableBalanceID has',
            ],
        ];
    }

    /**
     * @dataProvider wrongPayloadLengthProvider
     */
    public function testDecodeRejectsWrongPayloadLength(
        string $decodeMethod,
        int $versionByte,
        string $prefix,
        int $legalEncodedLength,
        int $wrongPayloadLength
    ) : void {
        $strKey = self::buildStrKey($versionByte, self::payloadOfLength($prefix, $wrongPayloadLength));
        assertSame($prefix, $strKey[0]);
        // A payload of the wrong size cannot reach the encoded length of its
        // type: base32 maps every payload size to its own encoded size.
        assertNotEquals($legalEncodedLength, strlen($strKey));

        try {
            StrKey::{$decodeMethod}($strKey);
            $this->fail(sprintf(
                '%s() accepted a payload of %d bytes',
                $decodeMethod,
                $wrongPayloadLength
            ));
        } catch (InvalidArgumentException $e) {
            assertSame(sprintf(
                '%s-strkey must be %d characters long, %d characters given',
                $prefix,
                $legalEncodedLength,
                strlen($strKey)
            ), $e->getMessage());
        }
    }

    /**
     * The eleven decode methods of the fixed-length strkey types, each with a
     * payload one byte short of and one byte past what the type carries. The
     * three methods that answer with hexadecimal are listed alongside the ones
     * that answer with raw bytes because they read the encoded string themselves
     * rather than passing it to their sibling.
     *
     * @return array<string, array{0: string, 1: int, 2: string, 3: int, 4: int}>
     */
    public static function wrongPayloadLengthProvider() : array {
        $types = [
            'decodeAccountId' => [VersionByte::ACCOUNT_ID, 'G', 56, 32],
            'decodeSeed' => [VersionByte::SEED, 'S', 56, 32],
            'decodePreAuthTx' => [VersionByte::PRE_AUTH_TX, 'T', 56, 32],
            'decodeSha256Hash' => [VersionByte::SHA256_HASH, 'X', 56, 32],
            'decodeContractId' => [VersionByte::CONTRACT_ID, 'C', 56, 32],
            'decodeContractIdHex' => [VersionByte::CONTRACT_ID, 'C', 56, 32],
            'decodeLiquidityPoolId' => [VersionByte::LIQUIDITY_POOL_ID, 'L', 56, 32],
            'decodeLiquidityPoolIdHex' => [VersionByte::LIQUIDITY_POOL_ID, 'L', 56, 32],
            'decodeMuxedAccountId' => [VersionByte::MUXED_ACCOUNT_ID, 'M', 69, 40],
            'decodeClaimableBalanceId' => [VersionByte::CLAIMABLE_BALANCE_ID, 'B', 58, 33],
            'decodeClaimableBalanceIdHex' => [VersionByte::CLAIMABLE_BALANCE_ID, 'B', 58, 33],
        ];

        $rows = [];
        foreach ($types as $decodeMethod => [$versionByte, $prefix, $legalEncodedLength, $payloadLength]) {
            $rows[$decodeMethod . ', one byte short'] =
                [$decodeMethod, $versionByte, $prefix, $legalEncodedLength, $payloadLength - 1];
            $rows[$decodeMethod . ', one byte long'] =
                [$decodeMethod, $versionByte, $prefix, $legalEncodedLength, $payloadLength + 1];
        }
        return $rows;
    }

    /**
     * @dataProvider strkeyTypeProvider
     */
    public function testDecodeAndIsValidAgreeOnTheSameInput(
        string $prefix,
        int $versionByte,
        string $decodeMethod,
        string $isValidMethod,
        string $validStrKey,
        string $payloadHex,
        string $encodedLengthRule
    ) : void {
        $payload = hex2bin($payloadHex);

        $this->assertDecodeAndIsValidAgree($decodeMethod, $isValidMethod, $validStrKey, true, 'the valid vector');

        $this->assertDecodeAndIsValidAgree(
            $decodeMethod,
            $isValidMethod,
            self::buildStrKey($versionByte, substr($payload, 0, -1)),
            false,
            'a payload one byte short'
        );
        $this->assertDecodeAndIsValidAgree(
            $decodeMethod,
            $isValidMethod,
            self::buildStrKey($versionByte, $payload . "\x2a"),
            false,
            'a payload one byte long'
        );

        // Another type's version byte over the same payload: the encoded length
        // is the one this type expects, so only the version byte is wrong.
        $otherVersionByte = $versionByte === VersionByte::ACCOUNT_ID
            ? VersionByte::SEED
            : VersionByte::ACCOUNT_ID;
        $this->assertDecodeAndIsValidAgree(
            $decodeMethod,
            $isValidMethod,
            self::buildStrKey($otherVersionByte, $payload),
            false,
            "another type's version byte"
        );

        $this->assertDecodeAndIsValidAgree(
            $decodeMethod,
            $isValidMethod,
            self::buildStrKeyWithCorruptedChecksum($versionByte, $payload),
            false,
            'a corrupted checksum'
        );
    }

    /**
     * @dataProvider strkeyTypeProvider
     */
    public function testOversizedInputIsRejectedOnLength(
        string $prefix,
        int $versionByte,
        string $decodeMethod,
        string $isValidMethod,
        string $validStrKey,
        string $payloadHex,
        string $encodedLengthRule
    ) : void {
        // Far past the longest strkey of any type, and made of base32 alphabet
        // characters so that nothing but the length rule can turn it away.
        $oversized = str_repeat('A', 1000000);

        try {
            StrKey::{$decodeMethod}($oversized);
            $this->fail(sprintf('%s() accepted a %d-character string', $decodeMethod, strlen($oversized)));
        } catch (InvalidArgumentException $e) {
            assertSame(sprintf(
                '%s-strkey must be %s characters long, %d characters given',
                $prefix,
                $encodedLengthRule,
                strlen($oversized)
            ), $e->getMessage());
        }
        assertFalse(StrKey::{$isValidMethod}($oversized));
    }

    /**
     * @dataProvider numericLookalikeProvider
     */
    public function testNumericLookalikeIsRejectedOnCharacters(
        string $decodeMethod,
        string $isValidMethod,
        string $input
    ) : void {
        // What the input decodes to encodes back to its eight-character tail: the
        // base32 filter drops every leading zero, and eight base32 characters carry
        // a whole number of bytes, so the tail is already canonical.
        $canonical = preg_replace('/[^A-Z2-7]/', '', Base32::encode(Base32::decode($input)));
        assertNotSame($input, $canonical);

        // Read as characters the two differ. Read as numbers they are the same
        // integer, because leading zeros carry no value and eight digits fit in an
        // int. Decoding compares them as characters, so the input is turned away.
        assertTrue($input == $canonical, 'the input has to be numerically equal to its canonical encoding');

        try {
            StrKey::{$decodeMethod}($input);
            $this->fail(sprintf('%s() accepted a non-canonical encoding', $decodeMethod));
        } catch (InvalidArgumentException $e) {
            assertSame('invalid encoded string', $e->getMessage());
        }
        assertFalse(StrKey::{$isValidMethod}($input));
    }

    /**
     * Strings that a numeric reading would accept as the canonical encoding of what
     * they decode to: a run of zeros, which is outside the base32 alphabet and so
     * decodes to nothing, followed by eight base32 digits.
     *
     * One row per encoded length any strkey type admits, so that no type reads such
     * a string through a sibling's length rule.
     *
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function numericLookalikeProvider() : array {
        $tail = '23456723';
        $rows = [];
        foreach ([
            'decodeAccountId' => ['isValidAccountId', 56],
            'decodeSeed' => ['isValidSeed', 56],
            'decodePreAuthTx' => ['isValidPreAuthTx', 56],
            'decodeSha256Hash' => ['isValidSha256Hash', 56],
            'decodeContractId' => ['isValidContractId', 56],
            'decodeLiquidityPoolId' => ['isValidLiquidityPoolId', 56],
            'decodeClaimableBalanceId' => ['isValidClaimableBalanceId', 58],
            'decodeMuxedAccountId' => ['isValidMuxedAccountId', 69],
        ] as $decodeMethod => [$isValidMethod, $length]) {
            $rows[$decodeMethod] = [
                $decodeMethod,
                $isValidMethod,
                str_repeat('0', $length - strlen($tail)) . $tail,
            ];
        }
        // The signed payload admits a range of lengths, so both ends of it.
        foreach ([69, 165] as $length) {
            $rows['decodeSignedPayload, ' . $length . ' characters'] = [
                'decodeSignedPayload',
                'isValidSignedPayload',
                str_repeat('0', $length - strlen($tail)) . $tail,
            ];
        }
        return $rows;
    }

    /**
     * One row per strkey type: the letter its encoded form starts with, the
     * version byte behind it, the decode and isValid methods that read it, a
     * valid string of that type, the payload that string carries, and the
     * encoded length the type states when it turns a string away.
     *
     * Shared by the tests that hold for every type, each of which reads the
     * columns its own rule needs.
     *
     * @return array<string, array{0: string, 1: int, 2: string, 3: string, 4: string, 5: string, 6: string}>
     */
    public static function strkeyTypeProvider() : array {
        $hash = '3f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a';
        return [
            'G — account id' => [
                'G', VersionByte::ACCOUNT_ID, 'decodeAccountId', 'isValidAccountId',
                'GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ', $hash, '56',
            ],
            'S — secret seed' => [
                'S', VersionByte::SEED, 'decodeSeed', 'isValidSeed',
                'SA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUWVG', $hash, '56',
            ],
            'T — pre-authorized transaction hash' => [
                'T', VersionByte::PRE_AUTH_TX, 'decodePreAuthTx', 'isValidPreAuthTx',
                'TA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUPUI', $hash, '56',
            ],
            'X — sha256 hash' => [
                'X', VersionByte::SHA256_HASH, 'decodeSha256Hash', 'isValidSha256Hash',
                'XA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVLRR', $hash, '56',
            ],
            'C — contract' => [
                'C', VersionByte::CONTRACT_ID, 'decodeContractId', 'isValidContractId',
                'CA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUWDA', $hash, '56',
            ],
            'L — liquidity pool' => [
                'L', VersionByte::LIQUIDITY_POOL_ID, 'decodeLiquidityPoolId', 'isValidLiquidityPoolId',
                'LA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUPJN', $hash, '56',
            ],
            'M — muxed account' => [
                'M', VersionByte::MUXED_ACCOUNT_ID, 'decodeMuxedAccountId', 'isValidMuxedAccountId',
                'MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAAAJLK',
                $hash . '8000000000000000', '69',
            ],
            'B — claimable balance' => [
                'B', VersionByte::CLAIMABLE_BALANCE_ID, 'decodeClaimableBalanceId', 'isValidClaimableBalanceId',
                'BAAD6DBUX6J22DMZOHIEZTEQ64CVCHEDRKWZONFEUL5Q26QD7R76RGR4TU', '00' . $hash, '58',
            ],
            'P — signed payload' => [
                'P', VersionByte::SIGNED_PAYLOAD, 'decodeSignedPayload', 'isValidSignedPayload',
                'PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAOQCAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUAAAAFGBU',
                // The 32-byte signer, the 4-byte length prefix announcing 29
                // payload bytes, the payload, and three zero padding bytes.
                $hash . '0000001d' . '0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d' . '000000',
                '69 to 165',
            ],
        ];
    }

    /**
     * Requires the decode method and the isValid method of one strkey type to
     * answer the same way about $strKey: a decode that returns paired with true,
     * a decode that throws paired with false.
     *
     * Both families read one length rule, so a disagreement means a string is
     * readable through one of them and refused by the other, which is what any
     * caller that validates with one and reads with the other depends on.
     */
    private function assertDecodeAndIsValidAgree(
        string $decodeMethod,
        string $isValidMethod,
        string $strKey,
        bool $expectedValid,
        string $case
    ) : void {
        $decodeAccepted = true;
        try {
            StrKey::{$decodeMethod}($strKey);
        } catch (InvalidArgumentException) {
            $decodeAccepted = false;
        }
        assertSame($expectedValid, $decodeAccepted, sprintf('%s() on %s', $decodeMethod, $case));
        assertSame(
            $expectedValid,
            StrKey::{$isValidMethod}($strKey),
            sprintf('%s() on %s', $isValidMethod, $case)
        );
    }

    /**
     * @dataProvider shortInputProvider
     */
    public function testShortInputIsRejectedWithoutPhpDiagnostic(
        string $decodeMethod,
        string $input,
        string $expectedMessage
    ) : void {
        $this->assertRejectedWithoutPhpDiagnostic(
            function () use ($decodeMethod, $input) : void { StrKey::{$decodeMethod}($input); },
            function (string $message) use ($expectedMessage) : void { assertSame($expectedMessage, $message); },
            sprintf('%s() on a %d-character string', $decodeMethod, strlen($input))
        );
    }

    /**
     * The shortest inputs a caller can pass, one decode method per encoded length
     * class, so that no length rule is reached through a sibling. A string this
     * short holds neither a version byte nor a checksum, and the rejection has to
     * say so rather than letting a read run off the end of it.
     *
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function shortInputProvider() : array {
        $rows = [];
        foreach ([
            'decodeAccountId' => 'G-strkey must be 56 characters long, %d characters given',
            'decodeMuxedAccountId' => 'M-strkey must be 69 characters long, %d characters given',
            'decodeClaimableBalanceId' => 'B-strkey must be 58 characters long, %d characters given',
            'decodeSignedPayload' => 'P-strkey must be 69 to 165 characters long, %d characters given',
        ] as $decodeMethod => $messageFormat) {
            foreach (['an empty string' => '', 'one character' => 'G', 'two characters' => 'GA'] as $label => $input) {
                $rows[$decodeMethod . ' with ' . $label] =
                    [$decodeMethod, $input, sprintf($messageFormat, strlen($input))];
            }
        }
        return $rows;
    }

    /**
     * @dataProvider encodePayloadLengthProvider
     * @param array<int, int> $wrongPayloadLengths
     */
    public function testEncodeRejectsWrongPayloadLength(
        string $encodeMethod,
        string $isValidMethod,
        string $prefix,
        int $expectedPayloadLength,
        int $expectedEncodedLength,
        bool $takesHexadecimal,
        array $wrongPayloadLengths
    ) : void {
        $correct = self::payloadOfLength($prefix, $expectedPayloadLength);
        $encoded = StrKey::{$encodeMethod}($takesHexadecimal ? bin2hex($correct) : $correct);
        assertSame($prefix, $encoded[0]);
        assertSame($expectedEncodedLength, strlen($encoded));
        assertTrue(StrKey::{$isValidMethod}($encoded));

        foreach ($wrongPayloadLengths as $wrongPayloadLength) {
            $wrong = self::payloadOfLength($prefix, $wrongPayloadLength);
            try {
                StrKey::{$encodeMethod}($takesHexadecimal ? bin2hex($wrong) : $wrong);
                $this->fail(sprintf(
                    '%s() encoded a payload of %d bytes',
                    $encodeMethod,
                    $wrongPayloadLength
                ));
            } catch (InvalidArgumentException $e) {
                assertSame(sprintf(
                    '%s-strkey requires a payload of %d bytes, %d bytes given',
                    $prefix,
                    $expectedPayloadLength,
                    $wrongPayloadLength
                ), $e->getMessage());
            }
        }
    }

    /**
     * Every encode method of the fixed-length strkey types, with the payload
     * length it requires and the lengths it must turn away. A string minted from
     * a wrongly sized payload could never be decoded back, so it is refused at
     * the encoder instead of being handed to the caller.
     *
     * The claimable balance encoders take 32 bytes as well, the bare balance hash
     * they prepend the discriminant to, so their rejected lengths straddle both
     * accepted sizes.
     *
     * The two signed payload encoders are absent because their payload has no
     * fixed size: the bounds they hold their callers to are exercised by
     * testSignedPayloadLengthBounds().
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: int, 4: int, 5: bool, 6: array<int, int>}>
     */
    public static function encodePayloadLengthProvider() : array {
        return [
            // 20 bytes reaches 37 encoded characters, a string no decoder reads
            // back, which is why a payload that size is refused here.
            'encodeAccountId' => ['encodeAccountId', 'isValidAccountId', 'G', 32, 56, false, [20, 31, 33]],
            'encodeSeed' => ['encodeSeed', 'isValidSeed', 'S', 32, 56, false, [31, 33]],
            'encodePreAuthTx' => ['encodePreAuthTx', 'isValidPreAuthTx', 'T', 32, 56, false, [31, 33]],
            'encodeSha256Hash' => ['encodeSha256Hash', 'isValidSha256Hash', 'X', 32, 56, false, [31, 33]],
            'encodeContractId' => ['encodeContractId', 'isValidContractId', 'C', 32, 56, false, [31, 33]],
            'encodeContractIdHex' => ['encodeContractIdHex', 'isValidContractId', 'C', 32, 56, true, [31, 33]],
            'encodeLiquidityPoolId' => [
                'encodeLiquidityPoolId', 'isValidLiquidityPoolId', 'L', 32, 56, false, [31, 33],
            ],
            'encodeLiquidityPoolIdHex' => [
                'encodeLiquidityPoolIdHex', 'isValidLiquidityPoolId', 'L', 32, 56, true, [31, 33],
            ],
            'encodeMuxedAccountId' => [
                'encodeMuxedAccountId', 'isValidMuxedAccountId', 'M', 40, 69, false, [39, 41],
            ],
            'encodeClaimableBalanceId' => [
                'encodeClaimableBalanceId', 'isValidClaimableBalanceId', 'B', 33, 58, false, [31, 34],
            ],
            'encodeClaimableBalanceIdHex' => [
                'encodeClaimableBalanceIdHex', 'isValidClaimableBalanceId', 'B', 33, 58, true, [31, 34],
            ],
        ];
    }

    public function testIsValidWithInvalidDecodedLengths() {
        // A muxed account carries 40 decoded payload bytes: the 32-byte ed25519
        // key followed by the 8-byte id.
        $muxedAccountId = "MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAAAJLK";
        assertTrue(StrKey::isValidMuxedAccountId($muxedAccountId));
        assertSame(
            "3f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a8000000000000000",
            bin2hex(StrKey::decodeMuxedAccountId($muxedAccountId))
        );

        // 39 and 41 payload bytes carry a correct checksum but reach 68 and 71
        // encoded characters, neither of which is the 69 an M-strkey has.
        foreach ([39, 41] as $wrongPayloadLength) {
            $wrongLength = self::buildStrKey(
                VersionByte::MUXED_ACCOUNT_ID,
                self::payloadOfLength('M', $wrongPayloadLength)
            );
            assertFalse(StrKey::isValidMuxedAccountId($wrongLength));
        }

        // The SEP-23 signed payload vectors, at the 4-byte-aligned payload length
        // and at a length that needs padding.
        $signerAccountId = "GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ";
        $paddedPayload = StrKey::decodeSignedPayload(
            "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAOQCAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUAAAAFGBU");
        assertSame($signerAccountId, $paddedPayload->getSignerAccountId()->getAccountId());
        assertSame("0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d", bin2hex($paddedPayload->getPayload()));
        assertSame(29, strlen($paddedPayload->getPayload()));

        $alignedPayload = StrKey::decodeSignedPayload(
            "PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAQACAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUPB6IBZGM");
        assertSame($signerAccountId, $alignedPayload->getSignerAccountId()->getAccountId());
        assertSame(
            "0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f20",
            bin2hex($alignedPayload->getPayload())
        );
        assertSame(32, strlen($alignedPayload->getPayload()));

        // A claimable balance carries 33 decoded payload bytes: the discriminant
        // followed by the 32-byte balance hash.
        $claimableBalanceId = "BAAD6DBUX6J22DMZOHIEZTEQ64CVCHEDRKWZONFEUL5Q26QD7R76RGR4TU";
        assertTrue(StrKey::isValidClaimableBalanceId($claimableBalanceId));
        assertSame(33, strlen(StrKey::decodeClaimableBalanceId($claimableBalanceId)));

        // 32 and 34 payload bytes reach 56 and 60 encoded characters, neither of
        // which is the 58 a B-strkey has.
        foreach ([32, 34] as $wrongPayloadLength) {
            $wrongLength = self::buildStrKey(
                VersionByte::CLAIMABLE_BALANCE_ID,
                self::payloadOfLength('B', $wrongPayloadLength)
            );
            assertFalse(StrKey::isValidClaimableBalanceId($wrongLength));
        }
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

    public function testEncodeContractIdHexRejectsMalformedHexadecimal() {
        $validHex = "363eaa3867841fbad0f4ed88c779e4fe66e56a2470dc98c0ec9c073d05c7b103";
        $encoded = StrKey::encodeContractIdHex($validHex);
        assertTrue(StrKey::isValidContractId($encoded));
        assertEquals($validHex, StrKey::decodeContractIdHex($encoded));

        $oddLength = substr($validHex, 0, 63);
        $this->assertHexRejectedWithoutPhpDiagnostic(
            function () use ($oddLength) { StrKey::encodeContractIdHex($oddLength); },
            '$contractId',
            'must be a hexadecimal string of even length, 63 characters given',
            'encodeContractIdHex with 63 hex characters'
        );

        $nonHex = substr($validHex, 0, 63) . 'z';
        $this->assertHexRejectedWithoutPhpDiagnostic(
            function () use ($nonHex) { StrKey::encodeContractIdHex($nonHex); },
            '$contractId',
            'must contain only hexadecimal characters [0-9a-fA-F], "z" found at index 63',
            'encodeContractIdHex with a non-hexadecimal character'
        );

        $this->assertHexRejectedWithoutPhpDiagnostic(
            function () { StrKey::encodeContractIdHex(''); },
            '$contractId',
            'must be a hexadecimal string, an empty string given',
            'encodeContractIdHex with an empty string'
        );

        // A byte that is not printable ASCII is reported as an \xNN escape rather than
        // copied into the message, which would leave it invalid UTF-8.
        $highByte = substr($validHex, 0, 10) . "\xFF" . substr($validHex, 11);
        $this->assertHexRejectedWithoutPhpDiagnostic(
            function () use ($highByte) { StrKey::encodeContractIdHex($highByte); },
            '$contractId',
            'must contain only hexadecimal characters [0-9a-fA-F], "\xFF" found at index 10',
            'encodeContractIdHex with a 0xFF byte'
        );
    }

    public function testEncodeLiquidityPoolIdHexRejectsMalformedHexadecimal() {
        $validHex = "3f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a";
        $encoded = StrKey::encodeLiquidityPoolIdHex($validHex);
        assertTrue(StrKey::isValidLiquidityPoolId($encoded));
        assertEquals($validHex, StrKey::decodeLiquidityPoolIdHex($encoded));

        $oddLength = substr($validHex, 0, 63);
        $this->assertHexRejectedWithoutPhpDiagnostic(
            function () use ($oddLength) { StrKey::encodeLiquidityPoolIdHex($oddLength); },
            '$liquidityPoolId',
            'must be a hexadecimal string of even length, 63 characters given',
            'encodeLiquidityPoolIdHex with 63 hex characters'
        );

        $nonHex = 'x' . substr($validHex, 1);
        $this->assertHexRejectedWithoutPhpDiagnostic(
            function () use ($nonHex) { StrKey::encodeLiquidityPoolIdHex($nonHex); },
            '$liquidityPoolId',
            'must contain only hexadecimal characters [0-9a-fA-F], "x" found at index 0',
            'encodeLiquidityPoolIdHex with a non-hexadecimal character'
        );

        $this->assertHexRejectedWithoutPhpDiagnostic(
            function () { StrKey::encodeLiquidityPoolIdHex(''); },
            '$liquidityPoolId',
            'must be a hexadecimal string, an empty string given',
            'encodeLiquidityPoolIdHex with an empty string'
        );

        // The lead byte of a multibyte character is reported as an \xNN escape: on its
        // own it is not valid UTF-8, so copying it into the message would break it.
        $multibyte = substr($validHex, 0, 4) . "\u{00E4}" . substr($validHex, 6);
        $this->assertHexRejectedWithoutPhpDiagnostic(
            function () use ($multibyte) { StrKey::encodeLiquidityPoolIdHex($multibyte); },
            '$liquidityPoolId',
            'must contain only hexadecimal characters [0-9a-fA-F], "\xC3" found at index 4',
            'encodeLiquidityPoolIdHex with a multibyte character'
        );
    }

    public function testEncodeClaimableBalanceIdHexRejectsMalformedHexadecimal() {
        $validHex = "003f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a";
        $encoded = StrKey::encodeClaimableBalanceIdHex($validHex);
        assertTrue(StrKey::isValidClaimableBalanceId($encoded));
        assertEquals($validHex, StrKey::decodeClaimableBalanceIdHex($encoded));

        $oddLength = substr($validHex, 0, 65);
        $this->assertHexRejectedWithoutPhpDiagnostic(
            function () use ($oddLength) { StrKey::encodeClaimableBalanceIdHex($oddLength); },
            '$claimableBalanceId',
            'must be a hexadecimal string of even length, 65 characters given',
            'encodeClaimableBalanceIdHex with 65 hex characters'
        );

        $nonHex = substr($validHex, 0, 65) . 'g';
        $this->assertHexRejectedWithoutPhpDiagnostic(
            function () use ($nonHex) { StrKey::encodeClaimableBalanceIdHex($nonHex); },
            '$claimableBalanceId',
            'must contain only hexadecimal characters [0-9a-fA-F], "g" found at index 65',
            'encodeClaimableBalanceIdHex with a non-hexadecimal character'
        );

        $this->assertHexRejectedWithoutPhpDiagnostic(
            function () { StrKey::encodeClaimableBalanceIdHex(''); },
            '$claimableBalanceId',
            'must be a hexadecimal string, an empty string given',
            'encodeClaimableBalanceIdHex with an empty string'
        );

        // Same for a four-byte character: the message names its lead byte as an escape.
        $emoji = substr($validHex, 0, 2) . "\u{1F600}" . substr($validHex, 6);
        $this->assertHexRejectedWithoutPhpDiagnostic(
            function () use ($emoji) { StrKey::encodeClaimableBalanceIdHex($emoji); },
            '$claimableBalanceId',
            'must contain only hexadecimal characters [0-9a-fA-F], "\xF0" found at index 2',
            'encodeClaimableBalanceIdHex with a four-byte character'
        );
    }

    /**
     * Requires $call to reject its input with an InvalidArgumentException that names the
     * argument and the rule it broke, and to raise no PHP diagnostic on the way there.
     *
     * The error handler is installed for the duration of the call: a notice, warning or
     * deprecation means the argument reached a function that reports malformed input
     * through the error handler instead of being checked before it got there.
     *
     * The message is also required to be valid UTF-8 and to survive json_encode(),
     * whatever bytes the rejected input carried, so that a caller which logs or
     * serializes it is not handed something it cannot encode.
     *
     * @param callable $call the rejected call
     * @param string $expectedArgumentName the parameter name the message must name
     * @param string $expectedRuleFragment the rule the message must state
     * @param string $description names the case in a failure message
     */
    private function assertHexRejectedWithoutPhpDiagnostic(
        callable $call,
        string $expectedArgumentName,
        string $expectedRuleFragment,
        string $description
    ): void {
        $this->assertRejectedWithoutPhpDiagnostic(
            $call,
            function (string $message) use ($expectedArgumentName, $expectedRuleFragment, $description): void {
                assertStringContainsString($expectedArgumentName, $message, $description);
                assertStringContainsString($expectedRuleFragment, $message, $description);
                assertTrue(
                    mb_check_encoding($message, 'UTF-8'),
                    sprintf('%s produced a message that is not valid UTF-8', $description)
                );
                assertNotFalse(
                    json_encode(['message' => $message]),
                    sprintf('%s produced a message that json_encode() cannot encode', $description)
                );
            },
            $description
        );
    }

    /**
     * Requires $call to reject its input with an InvalidArgumentException whose
     * message satisfies $assertMessage, and to raise no PHP diagnostic on the way
     * there.
     *
     * The error handler is installed for the duration of the call: a notice,
     * warning or deprecation means the input reached a function that reports
     * malformed data through the error handler instead of being checked before it
     * got there. An application that turns diagnostics into exceptions would see
     * that as a fatal error rather than as the documented rejection.
     *
     * @param callable $call the rejected call
     * @param callable $assertMessage receives the rejection message
     * @param string $description names the case in a failure message
     */
    private function assertRejectedWithoutPhpDiagnostic(
        callable $call,
        callable $assertMessage,
        string $description
    ): void {
        set_error_handler(function (int $errno, string $errstr) use ($description): bool {
            $this->fail(sprintf('%s raised a PHP diagnostic: %s', $description, $errstr));
        });
        try {
            $call();
            $this->fail(sprintf('%s was accepted, but the input is invalid', $description));
        } catch (InvalidArgumentException $e) {
            $assertMessage($e->getMessage());
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Builds a strkey the way SEP-23 prescribes: version byte, payload, CRC-16
     * checksum, base32, with everything outside the base32 alphabet removed.
     *
     * The checksum is computed here rather than borrowed from StrKey, so that a
     * fixture stays a fixture: one built from the checksum of the code under test
     * would agree with that code even if the checksum were wrong.
     *
     * @param int $versionByte one of the VersionByte constants
     * @param string $payload the payload bytes to place between the version byte
     * and the checksum, of any length
     * @param string|null $checksum the two checksum bytes, or null to compute them
     */
    private static function buildStrKey(int $versionByte, string $payload, ?string $checksum = null) : string {
        $version = pack('C', $versionByte);
        if ($checksum === null) {
            $checksum = self::crc16Xmodem($version . $payload);
        }
        return preg_replace('/[^A-Z2-7]/', '', Base32::encode($version . $payload . $checksum));
    }

    /**
     * Builds a strkey whose checksum is one bit away from the correct one. The
     * length and the base32 round trip are untouched, so the checksum rule is the
     * only thing left to turn the string away.
     */
    private static function buildStrKeyWithCorruptedChecksum(int $versionByte, string $payload) : string {
        $checksum = self::crc16Xmodem(pack('C', $versionByte) . $payload);
        $checksum[0] = chr(ord($checksum[0]) ^ 0x01);
        return self::buildStrKey($versionByte, $payload, $checksum);
    }

    /**
     * The CRC-16/XMODEM of $data as two little-endian bytes: polynomial 0x1021,
     * initial value 0, which is the checksum SEP-23 puts at the end of a strkey.
     */
    private static function crc16Xmodem(string $data) : string {
        $crc = 0x0000;
        foreach (str_split($data) as $character) {
            $byte = ord($character);
            for ($bitIndex = 0; $bitIndex < 8; $bitIndex++) {
                $bit = (($byte >> (7 - $bitIndex)) & 1) === 1;
                $topBitSet = (($crc >> 15) & 1) === 1;
                $crc <<= 1;
                if ($topBitSet !== $bit) {
                    $crc ^= 0x1021;
                }
            }
        }
        return pack('v', $crc & 0xFFFF);
    }

    /**
     * A payload of $length bytes for a strkey of $prefix. Claimable balance
     * payloads lead with the zero discriminant, so that a fixture built for a
     * length rule is not turned away by the discriminant rule instead.
     */
    private static function payloadOfLength(string $prefix, int $length) : string {
        if ($prefix === 'B' && $length > 0) {
            return "\x00" . str_repeat("\x2a", $length - 1);
        }
        return str_repeat("\x2a", $length);
    }

}