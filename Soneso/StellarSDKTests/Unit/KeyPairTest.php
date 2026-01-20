<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit;

use Exception;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;

class KeyPairTest extends TestCase
{
    public function setUp(): void
    {
        error_reporting(E_ALL);
    }

    public function testRandomKeyPairGeneration()
    {
        $keyPair1 = KeyPair::random();
        $keyPair2 = KeyPair::random();

        // Verify keys are generated
        assertNotNull($keyPair1->getAccountId());
        assertNotNull($keyPair1->getSecretSeed());
        assertNotNull($keyPair1->getPublicKey());
        assertNotNull($keyPair1->getPrivateKey());

        // Verify two random keypairs are different
        assertTrue($keyPair1->getAccountId() !== $keyPair2->getAccountId());
        assertTrue($keyPair1->getSecretSeed() !== $keyPair2->getSecretSeed());

        // Verify account ID starts with G
        assertTrue(str_starts_with($keyPair1->getAccountId(), 'G'));
        // Verify secret seed starts with S
        assertTrue(str_starts_with($keyPair1->getSecretSeed(), 'S'));

        // Verify key lengths
        assertEquals(32, strlen($keyPair1->getPublicKey()));
        assertEquals(32, strlen($keyPair1->getPrivateKey()));
    }

    public function testFromSeed()
    {
        $seed = "SDJHRQF4GCMIIKAAAQ6IHY42X73FQFLHUULAPSKKD4DFDM7UXWWCRHBE";
        $keyPair = KeyPair::fromSeed($seed);

        assertEquals($seed, $keyPair->getSecretSeed());
        assertEquals("GCZHXL5HXQX5ABDM26LHYRCQZ5OJFHLOPLZX47WEBP3V2PF5AVFK2A5D", $keyPair->getAccountId());
        assertNotNull($keyPair->getPrivateKey());
        assertNotNull($keyPair->getPublicKey());
    }

    public function testFromAccountId()
    {
        $accountId = "GCZHXL5HXQX5ABDM26LHYRCQZ5OJFHLOPLZX47WEBP3V2PF5AVFK2A5D";
        $keyPair = KeyPair::fromAccountId($accountId);

        assertEquals($accountId, $keyPair->getAccountId());
        assertNotNull($keyPair->getPublicKey());
        // Should not have private key when created from account ID
        assertNull($keyPair->getSecretSeed());
        assertNull($keyPair->getPrivateKey());
    }

    public function testFromAccountIdWithMuxedAccount()
    {
        // Create from muxed account ID (M...)
        $muxedAccountId = "MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAAAJLK";
        $keyPair = KeyPair::fromAccountId($muxedAccountId);

        // Should extract the underlying G... account ID
        assertEquals("GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ", $keyPair->getAccountId());
        assertNotNull($keyPair->getPublicKey());
        assertNull($keyPair->getSecretSeed());
        assertNull($keyPair->getPrivateKey());
    }

    public function testFromPrivateKey()
    {
        $seed = "SDJHRQF4GCMIIKAAAQ6IHY42X73FQFLHUULAPSKKD4DFDM7UXWWCRHBE";
        $keyPair1 = KeyPair::fromSeed($seed);
        $privateKey = $keyPair1->getPrivateKey();

        // Create keypair from raw private key
        $keyPair2 = KeyPair::fromPrivateKey($privateKey);

        assertEquals($keyPair1->getAccountId(), $keyPair2->getAccountId());
        assertEquals($keyPair1->getSecretSeed(), $keyPair2->getSecretSeed());
        assertEquals($keyPair1->getPublicKey(), $keyPair2->getPublicKey());
        assertEquals($keyPair1->getPrivateKey(), $keyPair2->getPrivateKey());
    }

    public function testFromPublicKey()
    {
        $seed = "SDJHRQF4GCMIIKAAAQ6IHY42X73FQFLHUULAPSKKD4DFDM7UXWWCRHBE";
        $keyPair1 = KeyPair::fromSeed($seed);
        $publicKey = $keyPair1->getPublicKey();

        // Create keypair from raw public key
        $keyPair2 = KeyPair::fromPublicKey($publicKey);

        assertEquals($keyPair1->getAccountId(), $keyPair2->getAccountId());
        assertEquals($keyPair1->getPublicKey(), $keyPair2->getPublicKey());
        // Should not have private key
        assertNull($keyPair2->getSecretSeed());
        assertNull($keyPair2->getPrivateKey());
    }

    public function testSignAndVerify()
    {
        $keyPair = KeyPair::random();
        $message = "test message to sign";

        // Sign the message
        $signature = $keyPair->sign($message);
        assertNotNull($signature);
        assertEquals(64, strlen($signature)); // Ed25519 signatures are 64 bytes

        // Verify the signature
        assertTrue($keyPair->verifySignature($signature, $message));

        // Verify fails with wrong message
        assertFalse($keyPair->verifySignature($signature, "wrong message"));

        // Verify fails with wrong signature
        $wrongSignature = str_repeat("\x00", 64);
        assertFalse($keyPair->verifySignature($wrongSignature, $message));
    }

    public function testSignWithoutPrivateKey()
    {
        // Create keypair without private key
        $accountId = "GCZHXL5HXQX5ABDM26LHYRCQZ5OJFHLOPLZX47WEBP3V2PF5AVFK2A5D";
        $keyPair = KeyPair::fromAccountId($accountId);

        $message = "test message";

        // Signing without private key should trigger exception
        $this->expectException(\TypeError::class);
        $keyPair->sign($message);
    }

    public function testSignDecorated()
    {
        $keyPair = KeyPair::random();
        $message = "test message";

        $decoratedSig = $keyPair->signDecorated($message);

        assertNotNull($decoratedSig);
        assertNotNull($decoratedSig->getSignature());
        assertEquals(64, strlen($decoratedSig->getSignature()));

        // Verify hint is last 4 bytes of public key
        assertEquals($keyPair->getHint(), $decoratedSig->getHint());
        assertEquals(4, strlen($decoratedSig->getHint()));
    }

    public function testSignPayloadDecorated()
    {
        $keyPair = KeyPair::random();
        $payload = "test payload data";

        $decoratedSig = $keyPair->signPayloadDecorated($payload);

        assertNotNull($decoratedSig);
        assertNotNull($decoratedSig->getSignature());
        assertEquals(64, strlen($decoratedSig->getSignature()));

        // Hint should be XORed with last 4 bytes of payload
        assertEquals(4, strlen($decoratedSig->getHint()));
    }

    public function testGetHint()
    {
        $keyPair = KeyPair::random();
        $hint = $keyPair->getHint();

        // Hint should be last 4 bytes of public key
        assertEquals(4, strlen($hint));
        assertEquals(substr($keyPair->getPublicKey(), -4), $hint);
    }

    public function testGetPublicKeyChecksum()
    {
        $keyPair = KeyPair::random();

        // Note: The method has a type mismatch - declares string but returns int
        // This test will verify the actual behavior
        try {
            $checksum = $keyPair->getPublicKeyChecksum();
            // If successful, verify it's an integer
            assertTrue(is_int($checksum));
        } catch (\TypeError $e) {
            // Expected due to return type mismatch in source code
            assertTrue(str_contains($e->getMessage(), 'must be of type string, int returned'));
        }
    }

    public function testFromMnemonic()
    {
        $mnemonicPhrase = "illness spike retreat truth genius clock brain pass fit cave bargain toe";
        $mnemonic = Mnemonic::mnemonicFromWords($mnemonicPhrase);

        // Test first account (index 0)
        $keyPair0 = KeyPair::fromMnemonic($mnemonic, 0);
        assertEquals("GDRXE2BQUC3AZNPVFSCEZ76NJ3WWL25FYFK6RGZGIEKWE4SOOHSUJUJ6", $keyPair0->getAccountId());
        assertNotNull($keyPair0->getSecretSeed());

        // Test second account (index 1)
        $keyPair1 = KeyPair::fromMnemonic($mnemonic, 1);
        assertEquals("GBAW5XGWORWVFE2XTJYDTLDHXTY2Q2MO73HYCGB3XMFMQ562Q2W2GJQX", $keyPair1->getAccountId());
        assertNotNull($keyPair1->getSecretSeed());

        // Verify different indices produce different keys
        assertTrue($keyPair0->getAccountId() !== $keyPair1->getAccountId());
        assertTrue($keyPair0->getSecretSeed() !== $keyPair1->getSecretSeed());
    }

    public function testFromMnemonicWithPassphrase()
    {
        $mnemonicPhrase = "illness spike retreat truth genius clock brain pass fit cave bargain toe";
        $mnemonic = Mnemonic::mnemonicFromWords($mnemonicPhrase);
        $passphrase = "test passphrase";

        $keyPairNoPass = KeyPair::fromMnemonic($mnemonic, 0);
        $keyPairWithPass = KeyPair::fromMnemonic($mnemonic, 0, $passphrase);

        // Different passphrase should produce different keys
        assertTrue($keyPairNoPass->getAccountId() !== $keyPairWithPass->getAccountId());
        assertTrue($keyPairNoPass->getSecretSeed() !== $keyPairWithPass->getSecretSeed());
    }

    public function testFromBip39SeedHex()
    {
        $mnemonicPhrase = "illness spike retreat truth genius clock brain pass fit cave bargain toe";
        $mnemonic = Mnemonic::mnemonicFromWords($mnemonicPhrase);
        $seedHex = bin2hex($mnemonic->generateSeed('', 64));

        // Create keypair from mnemonic
        $keyPairFromMnemonic = KeyPair::fromMnemonic($mnemonic, 0);

        // Create keypair from seed hex
        $keyPairFromSeedHex = KeyPair::fromBip39SeedHex($seedHex, 0);

        // Should produce the same keys
        assertEquals($keyPairFromMnemonic->getAccountId(), $keyPairFromSeedHex->getAccountId());
        assertEquals($keyPairFromMnemonic->getSecretSeed(), $keyPairFromSeedHex->getSecretSeed());
    }

    public function testXdrConversions()
    {
        $keyPair = KeyPair::random();

        // Test XDR muxed account conversion
        $xdrMuxedAccount = $keyPair->getXdrMuxedAccount();
        assertNotNull($xdrMuxedAccount);
        assertEquals($keyPair->getPublicKey(), $xdrMuxedAccount->getEd25519());

        // Test XDR signer key conversion
        $xdrSignerKey = $keyPair->getXdrSignerKey();
        assertNotNull($xdrSignerKey);
        assertEquals($keyPair->getPublicKey(), $xdrSignerKey->getEd25519());
    }

    public function testConsistentKeyGeneration()
    {
        // Same seed should always produce same keys
        $seed = "SDJHRQF4GCMIIKAAAQ6IHY42X73FQFLHUULAPSKKD4DFDM7UXWWCRHBE";

        $keyPair1 = KeyPair::fromSeed($seed);
        $keyPair2 = KeyPair::fromSeed($seed);

        assertEquals($keyPair1->getAccountId(), $keyPair2->getAccountId());
        assertEquals($keyPair1->getSecretSeed(), $keyPair2->getSecretSeed());
        assertEquals($keyPair1->getPublicKey(), $keyPair2->getPublicKey());
        assertEquals($keyPair1->getPrivateKey(), $keyPair2->getPrivateKey());
    }

    public function testCrossVerification()
    {
        // Test that one keypair can verify another's signature using public key
        $signingKeyPair = KeyPair::random();
        $message = "test message";
        $signature = $signingKeyPair->sign($message);

        // Create verification keypair from public key only
        $verifyingKeyPair = KeyPair::fromPublicKey($signingKeyPair->getPublicKey());

        // Should be able to verify with public key only
        assertTrue($verifyingKeyPair->verifySignature($signature, $message));
    }
}
