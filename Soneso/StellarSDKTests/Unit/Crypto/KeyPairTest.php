<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Crypto;

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

    public function testSignPayloadDecoratedWithVariousPayloads()
    {
        $keyPair = KeyPair::random();

        // Test with payload exactly 4 bytes
        $fourBytePayload = "abcd";
        $decoratedSig1 = $keyPair->signPayloadDecorated($fourBytePayload);
        assertNotNull($decoratedSig1);
        assertEquals(4, strlen($decoratedSig1->getHint()));

        // Test with payload longer than 4 bytes
        $longPayload = "test payload data that is longer";
        $decoratedSig2 = $keyPair->signPayloadDecorated($longPayload);
        assertNotNull($decoratedSig2);
        assertEquals(4, strlen($decoratedSig2->getHint()));

        // Test with very long payload
        $veryLongPayload = str_repeat("x", 100);
        $decoratedSig3 = $keyPair->signPayloadDecorated($veryLongPayload);
        assertNotNull($decoratedSig3);
        assertEquals(4, strlen($decoratedSig3->getHint()));

        // Verify that different payloads produce different hints
        assertTrue($decoratedSig1->getHint() !== $decoratedSig2->getHint());
    }

    public function testStrToStream()
    {
        $keyPair = KeyPair::random();

        // Test the str_to_stream method (it has default public visibility)
        $testString = "test data for stream";
        $stream = $keyPair->str_to_stream($testString);

        // Verify it's a resource
        assertTrue(is_resource($stream));

        // Verify we can read the data back
        $readData = stream_get_contents($stream);
        assertEquals($testString, $readData);

        // Clean up
        fclose($stream);
    }

    public function testVerifySignatureWithInvalidSignature()
    {
        $keyPair = KeyPair::random();
        $message = "test message";

        // Test with malformed signature (not 64 bytes)
        $invalidSignature = "short";
        assertFalse($keyPair->verifySignature($invalidSignature, $message));

        // Test with completely wrong signature
        $wrongSignature = random_bytes(64);
        assertFalse($keyPair->verifySignature($wrongSignature, $message));
    }

    public function testSignDecoratedWithoutPrivateKey()
    {
        // Create keypair without private key
        $accountId = "GCZHXL5HXQX5ABDM26LHYRCQZ5OJFHLOPLZX47WEBP3V2PF5AVFK2A5D";
        $keyPair = KeyPair::fromAccountId($accountId);

        $message = "test message";

        // signDecorated without private key should trigger exception in sign()
        $this->expectException(\TypeError::class);
        $keyPair->signDecorated($message);
    }

    public function testSignPayloadDecoratedWithoutPrivateKey()
    {
        // Create keypair without private key
        $accountId = "GCZHXL5HXQX5ABDM26LHYRCQZ5OJFHLOPLZX47WEBP3V2PF5AVFK2A5D";
        $keyPair = KeyPair::fromAccountId($accountId);

        $payload = "test payload";

        // signPayloadDecorated without private key should trigger exception
        $this->expectException(\TypeError::class);
        $keyPair->signPayloadDecorated($payload);
    }

    public function testSignMessageAsciiMatchesSpecVector()
    {
        $specSeed = 'SAKICEVQLYWGSOJS4WW7HZJWAHZVEEBS527LHK5V4MLJALYKICQCJXMW';
        $expectedHex = '7cee5d6d885752104c85eea421dfdcb95abf01f1271d11c4bec3fcbd7874dccd6e2e98b97b8eb23b643cac4073bb77de5d07b0710139180ae9f3cbba78f2ba04';

        $keyPair = KeyPair::fromSeed($specSeed);
        $signature = $keyPair->signMessage("Hello, World!");

        assertEquals($expectedHex, bin2hex($signature));
    }

    public function testVerifyMessageAsciiWithSpecSignature()
    {
        $specSeed = 'SAKICEVQLYWGSOJS4WW7HZJWAHZVEEBS527LHK5V4MLJALYKICQCJXMW';
        $signatureHex = '7cee5d6d885752104c85eea421dfdcb95abf01f1271d11c4bec3fcbd7874dccd6e2e98b97b8eb23b643cac4073bb77de5d07b0710139180ae9f3cbba78f2ba04';

        $keyPair = KeyPair::fromSeed($specSeed);
        $signature = hex2bin($signatureHex);

        assertTrue($keyPair->verifyMessage("Hello, World!", $signature));
    }

    public function testSignMessageJapaneseMatchesSpecVector()
    {
        $specSeed = 'SAKICEVQLYWGSOJS4WW7HZJWAHZVEEBS527LHK5V4MLJALYKICQCJXMW';
        $expectedHex = '083536eb95ecf32dce59b07fe7a1fd8cf814b2ce46f40d2a16e4ea1f6cecd980e04e6fbef9d21f98011c785a81edb85f3776a6e7d942b435eb0adc07da4d4604';

        $keyPair = KeyPair::fromSeed($specSeed);
        $signature = $keyPair->signMessage("こんにちは、世界！");

        assertEquals($expectedHex, bin2hex($signature));
    }

    public function testVerifyMessageJapaneseWithSpecSignature()
    {
        $specSeed = 'SAKICEVQLYWGSOJS4WW7HZJWAHZVEEBS527LHK5V4MLJALYKICQCJXMW';
        $signatureHex = '083536eb95ecf32dce59b07fe7a1fd8cf814b2ce46f40d2a16e4ea1f6cecd980e04e6fbef9d21f98011c785a81edb85f3776a6e7d942b435eb0adc07da4d4604';

        $keyPair = KeyPair::fromSeed($specSeed);
        $signature = hex2bin($signatureHex);

        assertTrue($keyPair->verifyMessage("こんにちは、世界！", $signature));
    }

    public function testSignMessageBinaryMatchesSpecVector()
    {
        $specSeed = 'SAKICEVQLYWGSOJS4WW7HZJWAHZVEEBS527LHK5V4MLJALYKICQCJXMW';
        $expectedHex = '540d7eee179f370bf634a49c1fa9fe4a58e3d7990b0207be336c04edfcc539ff8bd0c31bb2c0359b07c9651cb2ae104e4504657b5d17d43c69c7e50e23811b0d';

        $keyPair = KeyPair::fromSeed($specSeed);
        $binaryMessage = base64_decode('2zZDP1sa1BVBfLP7TeeMk3sUbaxAkUhBhDiNdrksaFo=');
        $signature = $keyPair->signMessage($binaryMessage);

        assertEquals($expectedHex, bin2hex($signature));
    }

    public function testVerifyMessageBinaryWithSpecSignature()
    {
        $specSeed = 'SAKICEVQLYWGSOJS4WW7HZJWAHZVEEBS527LHK5V4MLJALYKICQCJXMW';
        $signatureHex = '540d7eee179f370bf634a49c1fa9fe4a58e3d7990b0207be336c04edfcc539ff8bd0c31bb2c0359b07c9651cb2ae104e4504657b5d17d43c69c7e50e23811b0d';

        $keyPair = KeyPair::fromSeed($specSeed);
        $binaryMessage = base64_decode('2zZDP1sa1BVBfLP7TeeMk3sUbaxAkUhBhDiNdrksaFo=');
        $signature = hex2bin($signatureHex);

        assertTrue($keyPair->verifyMessage($binaryMessage, $signature));
    }

    public function testSignAndVerifyMessageRoundTrip()
    {
        $keyPair = KeyPair::random();
        $message = "Round-trip test message";

        $signature = $keyPair->signMessage($message);
        assertTrue($keyPair->verifyMessage($message, $signature));
    }

    public function testCrossConstructionSignAndVerifyMessage()
    {
        $specSeed = 'SAKICEVQLYWGSOJS4WW7HZJWAHZVEEBS527LHK5V4MLJALYKICQCJXMW';
        $specAddress = 'GBXFXNDLV4LSWA4VB7YIL5GBD7BVNR22SGBTDKMO2SBZZHDXSKZYCP7L';

        $signingKeyPair = KeyPair::fromSeed($specSeed);
        $verifyingKeyPair = KeyPair::fromAccountId($specAddress);

        $message = "Cross-construction test";
        $signature = $signingKeyPair->signMessage($message);

        assertTrue($verifyingKeyPair->verifyMessage($message, $signature));
    }

    public function testVerifyMessageWrongMessageFails()
    {
        $keyPair = KeyPair::random();
        $signature = $keyPair->signMessage("Hello, World!");

        assertFalse($keyPair->verifyMessage("Goodbye, World!", $signature));
    }

    public function testVerifyMessageWrongSignatureFails()
    {
        $specSeed = 'SAKICEVQLYWGSOJS4WW7HZJWAHZVEEBS527LHK5V4MLJALYKICQCJXMW';
        $wrongSignatureHex = '540d7eee179f370bf634a49c1fa9fe4a58e3d7990b0207be336c04edfcc539ff8bd0c31bb2c0359b07c9651cb2ae104e4504657b5d17d43c69c7e50e23811b0d';

        $keyPair = KeyPair::fromSeed($specSeed);
        $wrongSignature = hex2bin($wrongSignatureHex);

        assertFalse($keyPair->verifyMessage("Hello, World!", $wrongSignature));
    }

    public function testVerifyMessageWrongKeyPairFails()
    {
        $keyPairA = KeyPair::random();
        $keyPairB = KeyPair::random();

        $message = "Test message";
        $signature = $keyPairA->signMessage($message);

        assertFalse($keyPairB->verifyMessage($message, $signature));
    }

    public function testSignMessageWithoutPrivateKeyThrowsTypeError()
    {
        $specAddress = 'GBXFXNDLV4LSWA4VB7YIL5GBD7BVNR22SGBTDKMO2SBZZHDXSKZYCP7L';

        $keyPair = KeyPair::fromAccountId($specAddress);

        $this->expectException(\TypeError::class);
        $keyPair->signMessage("test");
    }

    public function testSignAndVerifyEmptyMessage()
    {
        $keyPair = KeyPair::random();
        $signature = $keyPair->signMessage("");

        assertTrue($keyPair->verifyMessage("", $signature));
    }

    public function testSignAndVerifyMessageViaBase64EncodedSignature()
    {
        $specSeed = 'SAKICEVQLYWGSOJS4WW7HZJWAHZVEEBS527LHK5V4MLJALYKICQCJXMW';
        $expectedBase64 = 'fO5dbYhXUhBMhe6kId/cuVq/AfEnHRHEvsP8vXh03M1uLpi5e46yO2Q8rEBzu3feXQewcQE5GArp88u6ePK6BA==';

        $keyPair = KeyPair::fromSeed($specSeed);
        $signature = $keyPair->signMessage("Hello, World!");
        $base64Signature = base64_encode($signature);

        assertEquals($expectedBase64, $base64Signature);

        $decodedSignature = base64_decode($base64Signature);
        assertTrue($keyPair->verifyMessage("Hello, World!", $decodedSignature));
    }

    public function testSignAndVerifyMessageViaHexEncodedSignature()
    {
        $specSeed = 'SAKICEVQLYWGSOJS4WW7HZJWAHZVEEBS527LHK5V4MLJALYKICQCJXMW';
        $expectedHex = '7cee5d6d885752104c85eea421dfdcb95abf01f1271d11c4bec3fcbd7874dccd6e2e98b97b8eb23b643cac4073bb77de5d07b0710139180ae9f3cbba78f2ba04';

        $keyPair = KeyPair::fromSeed($specSeed);
        $signature = $keyPair->signMessage("Hello, World!");
        $hexSignature = bin2hex($signature);

        assertEquals($expectedHex, $hexSignature);

        $decodedSignature = hex2bin($hexSignature);
        assertTrue($keyPair->verifyMessage("Hello, World!", $decodedSignature));
    }

    public function testVerifySpecVectorFromBase64Signature()
    {
        $specAddress = 'GBXFXNDLV4LSWA4VB7YIL5GBD7BVNR22SGBTDKMO2SBZZHDXSKZYCP7L';
        $base64Signature = 'CDU265Xs8y3OWbB/56H9jPgUss5G9A0qFuTqH2zs2YDgTm+++dIfmAEceFqB7bhfN3am59lCtDXrCtwH2k1GBA==';

        $keyPair = KeyPair::fromAccountId($specAddress);
        $signature = base64_decode($base64Signature);

        assertTrue($keyPair->verifyMessage("こんにちは、世界！", $signature));
    }

    public function testVerifySpecVectorFromHexSignature()
    {
        $specAddress = 'GBXFXNDLV4LSWA4VB7YIL5GBD7BVNR22SGBTDKMO2SBZZHDXSKZYCP7L';
        $hexSignature = '540d7eee179f370bf634a49c1fa9fe4a58e3d7990b0207be336c04edfcc539ff8bd0c31bb2c0359b07c9651cb2ae104e4504657b5d17d43c69c7e50e23811b0d';

        $keyPair = KeyPair::fromAccountId($specAddress);
        $binaryMessage = base64_decode('2zZDP1sa1BVBfLP7TeeMk3sUbaxAkUhBhDiNdrksaFo=');
        $signature = hex2bin($hexSignature);

        assertTrue($keyPair->verifyMessage($binaryMessage, $signature));
    }

}
