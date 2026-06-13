<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Soroban;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\SorobanAddressCredentials;
use Soneso\StellarSDK\Soroban\SorobanAddressCredentialsWithDelegates;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Soroban\SorobanAuthorizedFunction;
use Soneso\StellarSDK\Soroban\SorobanAuthorizedInvocation;
use Soneso\StellarSDK\Soroban\SorobanCredentials;
use Soneso\StellarSDK\Soroban\SorobanDelegateDescriptor;
use Soneso\StellarSDK\Soroban\SorobanDelegateSignature;
use Soneso\StellarSDK\Util\Hash;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentials;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType;

/**
 * Protocol 27 (CAP-71) authorization tests.
 *
 * Covers wrapper round-trips, golden preimage vectors, signing across all three address arms,
 * delegate tree construction, forAddress routing, depth guards, and edge cases.
 */
class P27AuthorizationTest extends TestCase
{
    // ---------------------------------------------------------------------------
    // Golden test vectors (Protocol 27 cross-SDK, TESTNET)
    //
    // Network: "Test SDF Network ; September 2015"
    // Seed:    SDJHRQF4GCMIIKAAAQ6IHY42X73FQFLHUULAPSKKD4DFDM7UXWWCRHBE
    // Account: GCZHXL5HXQX5ABDM26LHYRCQZ5OJFHLOPLZX47WEBP3V2PF5AVFK2A5D
    // Contract: CA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAXE
    // Invocation: contractFn hello(u64 1234), no subInvocations
    // Nonce: 123456789101112
    // signatureExpirationLedger: 4242
    // ---------------------------------------------------------------------------

    private const GOLDEN_SEED      = 'SDJHRQF4GCMIIKAAAQ6IHY42X73FQFLHUULAPSKKD4DFDM7UXWWCRHBE';
    private const GOLDEN_ACCOUNT   = 'GCZHXL5HXQX5ABDM26LHYRCQZ5OJFHLOPLZX47WEBP3V2PF5AVFK2A5D';
    private const GOLDEN_CONTRACT  = 'CA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAXE';
    private const GOLDEN_NONCE     = 123456789101112;
    private const GOLDEN_EXPIRY    = 4242;

    // Legacy ADDRESS preimage (ENVELOPE_TYPE_SOROBAN_AUTHORIZATION)
    private const GOLDEN_LEGACY_PREIMAGE_B64  =
        'AAAACc7gMC1ZhE0yvcqRXIID3USzP7t+3BkFHqN6vt8o7NRyAABwSIYPOjgAABCSAAAAAAAAAAE2Pqo4Z4QfutD07YjHeeT+ZuVqJHDcmMDsnAc9BcexAwAAAAVoZWxsbwAAAAAAAAEAAAAFAAAAAAAABNIAAAAA';
    private const GOLDEN_LEGACY_PAYLOAD_HEX   = '120c429d4333e12e0ca2c5ac10630e728fdd33240bf7066f4c62f6a2d6fa3cbe';
    private const GOLDEN_LEGACY_SIG_HEX       = '3c69ceefc532f97e1d0e0eb9f204c9aa85cb2b68cf293bce832590b01455e060e89900ea3ba2c45257908769a1a71f25b6d3befbadffd220f896dc0058699008';

    // ADDRESS_V2 preimage (ENVELOPE_TYPE_SOROBAN_AUTHORIZATION_WITH_ADDRESS)
    private const GOLDEN_V2_PREIMAGE_B64      =
        'AAAACs7gMC1ZhE0yvcqRXIID3USzP7t+3BkFHqN6vt8o7NRyAABwSIYPOjgAABCSAAAAAAAAAACye6+nvC/QBGzXlnxEUM9ckp1uevN+fsQL9108vQVKrQAAAAAAAAABNj6qOGeEH7rQ9O2Ix3nk/mblaiRw3JjA7JwHPQXHsQMAAAAFaGVsbG8AAAAAAAABAAAABQAAAAAAAATSAAAAAA==';
    private const GOLDEN_V2_PAYLOAD_HEX       = '252a0d6117840dff37b765839810fb6ecc446198e73062e01bc961e49355b7b9';

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    private function makeGoldenInvocation(): SorobanAuthorizedInvocation
    {
        $contractAddress = Address::fromContractId(StrKey::decodeContractIdHex(self::GOLDEN_CONTRACT));
        $args = [XdrSCVal::forU64(1234)];
        $fn   = SorobanAuthorizedFunction::forContractFunction($contractAddress, 'hello', $args);
        return new SorobanAuthorizedInvocation($fn, []);
    }

    private function makeGoldenLegacyEntry(): SorobanAuthorizationEntry
    {
        $address = Address::fromAccountId(self::GOLDEN_ACCOUNT);
        $creds = SorobanCredentials::forAddress($address, self::GOLDEN_NONCE, self::GOLDEN_EXPIRY, XdrSCVal::forVoid());
        return new SorobanAuthorizationEntry($creds, $this->makeGoldenInvocation());
    }

    private function makeGoldenV2Entry(): SorobanAuthorizationEntry
    {
        $address = Address::fromAccountId(self::GOLDEN_ACCOUNT);
        $addressCreds = new SorobanAddressCredentials($address, self::GOLDEN_NONCE, self::GOLDEN_EXPIRY, XdrSCVal::forVoid());
        $creds = SorobanCredentials::forAddressCredentialsV2($addressCreds);
        return new SorobanAuthorizationEntry($creds, $this->makeGoldenInvocation());
    }

    // ---------------------------------------------------------------------------
    // TASK 1: Wrapper round-trip fidelity for all four arms
    // ---------------------------------------------------------------------------

    public function testRoundTripSourceAccount(): void
    {
        $original = SorobanCredentials::forSourceAccount();
        $xdr      = $original->toXdr();

        $this->assertEquals(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT, $xdr->type->value);

        $decoded = SorobanCredentials::fromXdr($xdr);
        $this->assertEquals(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT, $decoded->credentialType);
        $this->assertNull($decoded->addressCredentials);
        $this->assertNull($decoded->addressWithDelegates);

        // Re-encode must produce the same XDR bytes.
        $this->assertEquals($xdr->encode(), $decoded->toXdr()->encode());
    }

    public function testRoundTripAddressLegacy(): void
    {
        $address  = Address::fromAccountId(self::GOLDEN_ACCOUNT);
        $creds    = SorobanCredentials::forAddress($address, 42, 100, XdrSCVal::forVoid());
        $xdr      = $creds->toXdr();

        $this->assertEquals(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS, $xdr->type->value);

        $decoded = SorobanCredentials::fromXdr($xdr);
        $this->assertEquals(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS, $decoded->credentialType);
        $this->assertNotNull($decoded->addressCredentials);
        $this->assertEquals(42, $decoded->addressCredentials->nonce);
        $this->assertEquals(100, $decoded->addressCredentials->signatureExpirationLedger);
        $this->assertEquals($xdr->encode(), $decoded->toXdr()->encode());
    }

    public function testRoundTripAddressV2(): void
    {
        $address      = Address::fromAccountId(self::GOLDEN_ACCOUNT);
        $addressCreds = new SorobanAddressCredentials($address, 77, 200, XdrSCVal::forVoid());
        $creds        = SorobanCredentials::forAddressCredentialsV2($addressCreds);
        $xdr          = $creds->toXdr();

        $this->assertEquals(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2, $xdr->type->value);
        $this->assertNotNull($xdr->addressV2);
        $this->assertNull($xdr->address);

        $decoded = SorobanCredentials::fromXdr($xdr);
        $this->assertEquals(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2, $decoded->credentialType);
        $this->assertNotNull($decoded->addressCredentials);
        $this->assertEquals(77, $decoded->addressCredentials->nonce);
        $this->assertEquals($xdr->encode(), $decoded->toXdr()->encode());
    }

    public function testRoundTripAddressWithDelegates(): void
    {
        $address      = Address::fromAccountId(self::GOLDEN_ACCOUNT);
        $addressCreds = new SorobanAddressCredentials($address, 55, 300, XdrSCVal::forVoid());

        $delegateAddr = Address::fromContractId(StrKey::decodeContractIdHex(self::GOLDEN_CONTRACT));
        $delegateXdr  = new SorobanDelegateSignature($delegateAddr->toXdr(), XdrSCVal::forVoid(), []);
        $withDelegates = new SorobanAddressCredentialsWithDelegates($addressCreds, [$delegateXdr]);

        $creds = SorobanCredentials::forAddressWithDelegates($withDelegates);
        $xdr   = $creds->toXdr();

        $this->assertEquals(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES, $xdr->type->value);
        $this->assertNotNull($xdr->addressWithDelegates);
        $this->assertNull($xdr->address);
        $this->assertNull($xdr->addressV2);
        $this->assertCount(1, $xdr->addressWithDelegates->delegates);

        $decoded = SorobanCredentials::fromXdr($xdr);
        $this->assertEquals(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES, $decoded->credentialType);
        $this->assertNull($decoded->addressCredentials);
        $this->assertNotNull($decoded->addressWithDelegates);
        $this->assertCount(1, $decoded->addressWithDelegates->delegates);
        $this->assertEquals($xdr->encode(), $decoded->toXdr()->encode());
    }

    /**
     * Regression: fromXdr previously mapped V2/WITH_DELEGATES to source-account, losing data.
     */
    public function testFromXdrDoesNotSilentlyDropV2Arm(): void
    {
        $address      = Address::fromAccountId(self::GOLDEN_ACCOUNT);
        $addressCreds = new SorobanAddressCredentials($address, 99, 500, XdrSCVal::forVoid());
        $xdrCreds     = XdrSorobanCredentials::forAddressCredentialsV2($addressCreds->toXdr());

        $decoded = SorobanCredentials::fromXdr($xdrCreds);

        // Must not silently become source-account.
        $this->assertNotEquals(
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT,
            $decoded->credentialType,
            'fromXdr must not reclassify ADDRESS_V2 as source-account',
        );
        $this->assertEquals(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2, $decoded->credentialType);
        $this->assertNotNull($decoded->addressCredentials);
    }

    public function testFromXdrUnknownArmThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unknown XdrSorobanCredentialsType value/');

        $xdrCreds = new XdrSorobanCredentials(new \Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType(99));
        SorobanCredentials::fromXdr($xdrCreds);
    }

    // ---------------------------------------------------------------------------
    // XdrSorobanCredentials factory wrappers
    // ---------------------------------------------------------------------------

    public function testXdrFactoryForAddressCredentialsV2(): void
    {
        $address      = Address::fromAccountId(self::GOLDEN_ACCOUNT);
        $addressCreds = new SorobanAddressCredentials($address, 1, 1, XdrSCVal::forVoid());
        $xdrCreds     = XdrSorobanCredentials::forAddressCredentialsV2($addressCreds->toXdr());

        $this->assertEquals(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2, $xdrCreds->type->value);
        $this->assertNotNull($xdrCreds->addressV2);
        $this->assertNull($xdrCreds->address);
    }

    public function testXdrFactoryForAddressWithDelegates(): void
    {
        $address      = Address::fromAccountId(self::GOLDEN_ACCOUNT);
        $addressCreds = new SorobanAddressCredentials($address, 1, 1, XdrSCVal::forVoid());
        $withDelegates = new \Soneso\StellarSDK\Xdr\XdrSorobanAddressCredentialsWithDelegates(
            $addressCreds->toXdr(), []
        );
        $xdrCreds = XdrSorobanCredentials::forAddressWithDelegates($withDelegates);

        $this->assertEquals(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES, $xdrCreds->type->value);
        $this->assertNotNull($xdrCreds->addressWithDelegates);
    }

    // ---------------------------------------------------------------------------
    // TASK 3: Preimage builder — golden vectors
    // ---------------------------------------------------------------------------

    public function testLegacyPreimageMatchesGoldenVector(): void
    {
        $entry   = $this->makeGoldenLegacyEntry();
        $preimage = $entry->buildPreimage(Network::testnet());

        $b64 = base64_encode($preimage->encode());
        $this->assertEquals(self::GOLDEN_LEGACY_PREIMAGE_B64, $b64, 'Legacy preimage bytes must match golden vector');
    }

    public function testLegacyPayloadHashMatchesGoldenVector(): void
    {
        $entry   = $this->makeGoldenLegacyEntry();
        $preimage = $entry->buildPreimage(Network::testnet());
        $payload  = Hash::generate($preimage->encode());

        $this->assertEquals(self::GOLDEN_LEGACY_PAYLOAD_HEX, bin2hex($payload));
    }

    public function testV2PreimageMatchesGoldenVector(): void
    {
        $entry   = $this->makeGoldenV2Entry();
        $preimage = $entry->buildPreimage(Network::testnet());

        $b64 = base64_encode($preimage->encode());
        $this->assertEquals(self::GOLDEN_V2_PREIMAGE_B64, $b64, 'V2 preimage bytes must match golden vector');
    }

    public function testV2PayloadHashMatchesGoldenVector(): void
    {
        $entry   = $this->makeGoldenV2Entry();
        $preimage = $entry->buildPreimage(Network::testnet());
        $payload  = Hash::generate($preimage->encode());

        $this->assertEquals(self::GOLDEN_V2_PAYLOAD_HEX, bin2hex($payload));
    }

    /**
     * Preimage discriminant: legacy ADDRESS uses ENVELOPE_TYPE_SOROBAN_AUTHORIZATION (9),
     * V2 and WITH_DELEGATES use ENVELOPE_TYPE_SOROBAN_AUTHORIZATION_WITH_ADDRESS (10).
     */
    public function testPreimageDiscriminantPerArm(): void
    {
        $legacyEntry = $this->makeGoldenLegacyEntry();
        $v2Entry     = $this->makeGoldenV2Entry();

        $legacyPreimage = $legacyEntry->buildPreimage(Network::testnet());
        $v2Preimage     = $v2Entry->buildPreimage(Network::testnet());

        $this->assertEquals(
            \Soneso\StellarSDK\Xdr\XdrEnvelopeType::ENVELOPE_TYPE_SOROBAN_AUTHORIZATION,
            $legacyPreimage->type->value,
        );
        $this->assertEquals(
            \Soneso\StellarSDK\Xdr\XdrEnvelopeType::ENVELOPE_TYPE_SOROBAN_AUTHORIZATION_WITH_ADDRESS,
            $v2Preimage->type->value,
        );
    }

    /**
     * Legacy and V2 preimages for identical fields must differ (different envelope type discriminant).
     */
    public function testLegacyAndV2PreimagesDifferForIdenticalFields(): void
    {
        $legacyEntry = $this->makeGoldenLegacyEntry();
        $v2Entry     = $this->makeGoldenV2Entry();

        $legacyBytes = $legacyEntry->buildPreimage(Network::testnet())->encode();
        $v2Bytes     = $v2Entry->buildPreimage(Network::testnet())->encode();

        $this->assertNotEquals(bin2hex($legacyBytes), bin2hex($v2Bytes));
    }

    /**
     * WITH_DELEGATES preimage address is the TOP-LEVEL credential address, never a delegate's.
     */
    public function testWithDelegatesPreimageAddressIsTopLevel(): void
    {
        $topAddress   = Address::fromAccountId(self::GOLDEN_ACCOUNT);
        $addressCreds = new SorobanAddressCredentials($topAddress, self::GOLDEN_NONCE, self::GOLDEN_EXPIRY, XdrSCVal::forVoid());

        $delegateAddr = Address::fromContractId(StrKey::decodeContractIdHex(self::GOLDEN_CONTRACT));
        $delegateSig  = new SorobanDelegateSignature($delegateAddr->toXdr(), XdrSCVal::forVoid(), []);
        $withDelegates = new SorobanAddressCredentialsWithDelegates($addressCreds, [$delegateSig]);
        $creds = SorobanCredentials::forAddressWithDelegates($withDelegates);

        $entry    = new SorobanAuthorizationEntry($creds, $this->makeGoldenInvocation());
        $preimage = $entry->buildPreimage(Network::testnet());

        $this->assertNotNull($preimage->sorobanAuthorizationWithAddress);
        $preimageAddressStrkey = $preimage->sorobanAuthorizationWithAddress->address->toStrKey();
        $this->assertEquals(self::GOLDEN_ACCOUNT, $preimageAddressStrkey, 'Preimage address must be top-level');
        $this->assertNotEquals(self::GOLDEN_CONTRACT, $preimageAddressStrkey);

        // Must match V2 preimage for same top-level creds.
        $v2Entry      = $this->makeGoldenV2Entry();
        $v2Preimage   = $v2Entry->buildPreimage(Network::testnet());
        $this->assertEquals($preimage->encode(), $v2Preimage->encode());
    }

    public function testBuildPreimageThrowsForSourceAccount(): void
    {
        $this->expectException(RuntimeException::class);
        $entry = new SorobanAuthorizationEntry(
            SorobanCredentials::forSourceAccount(),
            $this->makeGoldenInvocation(),
        );
        $entry->buildPreimage(Network::testnet());
    }

    // ---------------------------------------------------------------------------
    // TASK 4: sign() — golden signature + all arms + expiration-before-hash
    // ---------------------------------------------------------------------------

    public function testLegacySignProducesGoldenSignature(): void
    {
        $signer = KeyPair::fromSeed(self::GOLDEN_SEED);
        $entry  = $this->makeGoldenLegacyEntry();

        $entry->sign($signer, Network::testnet());

        $sig = $entry->credentials->addressCredentials?->signature;
        $this->assertNotNull($sig);
        $this->assertNotNull($sig->vec);
        $this->assertCount(1, $sig->vec);

        // Extract the signature bytes from the map entry.
        $sigMap   = $sig->vec[0]->map;
        $this->assertNotNull($sigMap);
        $sigBytes = null;
        foreach ($sigMap as $entry2) {
            if ($entry2->key->sym === 'signature') {
                $sigBytes = $entry2->val->bytes?->getValue();
                break;
            }
        }
        $this->assertNotNull($sigBytes, 'signature field not found in map');
        $this->assertEquals(self::GOLDEN_LEGACY_SIG_HEX, bin2hex($sigBytes));
    }

    public function testV2SignWorks(): void
    {
        $signer = KeyPair::fromSeed(self::GOLDEN_SEED);
        $entry  = $this->makeGoldenV2Entry();

        $entry->sign($signer, Network::testnet());

        $sig = $entry->credentials->addressCredentials?->signature;
        $this->assertNotNull($sig);
        $this->assertNotNull($sig->vec);
        $this->assertCount(1, $sig->vec);
    }

    /**
     * Expiration-before-hash: if signatureExpirationLedger is passed to sign(), it must be
     * applied before the preimage is built. Changing expiration changes the payload hash.
     */
    public function testExpirationSetBeforeHash(): void
    {
        $signer = KeyPair::fromSeed(self::GOLDEN_SEED);

        // Entry with expiry 0 initially; sign sets it to GOLDEN_EXPIRY.
        $address = Address::fromAccountId(self::GOLDEN_ACCOUNT);
        $creds   = SorobanCredentials::forAddress($address, self::GOLDEN_NONCE, 0, XdrSCVal::forVoid());
        $entry   = new SorobanAuthorizationEntry($creds, $this->makeGoldenInvocation());

        $entry->sign($signer, Network::testnet(), self::GOLDEN_EXPIRY);

        // The resulting signed entry should match the golden signature (expiry set correctly).
        $sig = $entry->credentials->addressCredentials?->signature;
        $this->assertNotNull($sig?->vec);
        $sigMap   = $sig->vec[0]->map;
        $sigBytes = null;
        foreach ($sigMap as $entry2) {
            if ($entry2->key->sym === 'signature') {
                $sigBytes = $entry2->val->bytes?->getValue();
                break;
            }
        }
        $this->assertEquals(self::GOLDEN_LEGACY_SIG_HEX, bin2hex((string)$sigBytes));
    }

    /**
     * Regression: signing without setting expiration before building preimage produces a
     * DIFFERENT hash. This test verifies that a different expiry produces a different signature.
     */
    public function testDifferentExpiryProducesDifferentHash(): void
    {
        $signer = KeyPair::fromSeed(self::GOLDEN_SEED);

        $address  = Address::fromAccountId(self::GOLDEN_ACCOUNT);
        $creds1   = SorobanCredentials::forAddress($address, self::GOLDEN_NONCE, self::GOLDEN_EXPIRY, XdrSCVal::forVoid());
        $creds2   = SorobanCredentials::forAddress($address, self::GOLDEN_NONCE, self::GOLDEN_EXPIRY + 1, XdrSCVal::forVoid());

        $entry1 = new SorobanAuthorizationEntry($creds1, $this->makeGoldenInvocation());
        $entry2 = new SorobanAuthorizationEntry($creds2, $this->makeGoldenInvocation());

        $entry1->sign($signer, Network::testnet());
        $entry2->sign($signer, Network::testnet());

        $sig1 = $this->extractSignatureHex($entry1);
        $sig2 = $this->extractSignatureHex($entry2);
        $this->assertNotEquals($sig1, $sig2);
    }

    /**
     * Sign throws for source-account credentials.
     */
    public function testSignThrowsForSourceAccountCredentials(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no soroban address credentials found');

        $entry = new SorobanAuthorizationEntry(
            SorobanCredentials::forSourceAccount(),
            $this->makeGoldenInvocation(),
        );
        $entry->sign(KeyPair::random(), Network::testnet());
    }

    // ---------------------------------------------------------------------------
    // Signature append semantics
    // ---------------------------------------------------------------------------

    /**
     * Void top-level signature is preserved; not filled in by the SDK.
     */
    public function testVoidTopLevelSignaturePreserved(): void
    {
        // An entry with void top-level (delegates-only scenario):
        // just verify that buildPreimage works and sign(forAddress=topLevel) works.
        $address      = Address::fromAccountId(self::GOLDEN_ACCOUNT);
        $addressCreds = new SorobanAddressCredentials($address, self::GOLDEN_NONCE, self::GOLDEN_EXPIRY, XdrSCVal::forVoid());

        // Explicitly keep top-level void and only sign a delegate.
        $delegateKp   = KeyPair::random();
        $delegateAddr = Address::fromAccountId($delegateKp->getAccountId());
        $delegateSig  = new SorobanDelegateSignature($delegateAddr->toXdr(), XdrSCVal::forVoid(), []);
        $withDelegates = new SorobanAddressCredentialsWithDelegates($addressCreds, [$delegateSig]);
        $creds = SorobanCredentials::forAddressWithDelegates($withDelegates);
        $entry = new SorobanAuthorizationEntry($creds, $this->makeGoldenInvocation());

        // Sign only the delegate; top-level stays void.
        $entry->sign($delegateKp, Network::testnet(), null, $delegateKp->getAccountId());

        // Top-level signature is still void.
        $topCreds = $entry->credentials->addressWithDelegates?->addressCredentials;
        $this->assertNotNull($topCreds);
        $this->assertNull($topCreds->signature->vec, 'Top-level must remain void (no vec)');

        // Delegate node now has a signature.
        $delegateNode = $entry->credentials->addressWithDelegates?->delegates[0];
        $this->assertNotNull($delegateNode);
        $this->assertNotNull($delegateNode->signature->vec);
        $this->assertCount(1, $delegateNode->signature->vec);
    }

    /**
     * Appending to a void signature replaces it with a one-element vec.
     */
    public function testAppendToVoidProducesOneElementVec(): void
    {
        $signer = KeyPair::random();
        $address = Address::fromAccountId($signer->getAccountId());
        $creds   = SorobanCredentials::forAddress($address, 1, 100, XdrSCVal::forVoid());
        $entry   = new SorobanAuthorizationEntry($creds, $this->makeGoldenInvocation());

        $entry->sign($signer, Network::testnet());

        $sig = $entry->credentials->addressCredentials?->signature;
        $this->assertNotNull($sig?->vec);
        $this->assertCount(1, $sig->vec);
    }

    /**
     * Appending to an existing vec grows it without reordering.
     */
    public function testAppendToExistingVecGrows(): void
    {
        $signer1 = KeyPair::random();
        $signer2 = KeyPair::random();
        $address = Address::fromAccountId($signer1->getAccountId());
        $creds   = SorobanCredentials::forAddress($address, 1, 100, XdrSCVal::forVoid());
        $entry   = new SorobanAuthorizationEntry($creds, $this->makeGoldenInvocation());

        $entry->sign($signer1, Network::testnet());
        $entry->sign($signer2, Network::testnet());

        $sig = $entry->credentials->addressCredentials?->signature;
        $this->assertNotNull($sig?->vec);
        $this->assertCount(2, $sig->vec);
    }

    // ---------------------------------------------------------------------------
    // TASK 4: forAddress routing — distinct top-level and delegate addresses
    // ---------------------------------------------------------------------------

    /**
     * Critical: sign(forAddress=delegateB) must write to the delegate node and leave
     * the top-level credential (address A) void. Tests that use the SAME address for
     * both cannot prove correct routing.
     */
    public function testForAddressRoutesOnlyToDelegate(): void
    {
        $topKp      = KeyPair::fromSeed(self::GOLDEN_SEED);
        $delegateKp = KeyPair::random();

        $topAddress   = Address::fromAccountId($topKp->getAccountId());
        $delegateAddress = Address::fromAccountId($delegateKp->getAccountId());

        $addressCreds = new SorobanAddressCredentials($topAddress, self::GOLDEN_NONCE, self::GOLDEN_EXPIRY, XdrSCVal::forVoid());
        $delegateSig  = new SorobanDelegateSignature($delegateAddress->toXdr(), XdrSCVal::forVoid(), []);
        $withDelegates = new SorobanAddressCredentialsWithDelegates($addressCreds, [$delegateSig]);
        $creds  = SorobanCredentials::forAddressWithDelegates($withDelegates);
        $entry  = new SorobanAuthorizationEntry($creds, $this->makeGoldenInvocation());

        // Sign delegate only.
        $entry->sign($delegateKp, Network::testnet(), null, $delegateKp->getAccountId());

        // Top-level must still be void.
        $topCreds = $entry->credentials->addressWithDelegates?->addressCredentials;
        $this->assertNull($topCreds?->signature->vec, 'Top-level must be void after signing only delegate');

        // Delegate must have a signature.
        $delegateNode = $entry->credentials->addressWithDelegates?->delegates[0];
        $this->assertNotNull($delegateNode?->signature->vec);
        $this->assertCount(1, $delegateNode->signature->vec);
    }

    /**
     * Both top-level and delegate sign the SAME payload hash (verified by re-deriving the hash).
     */
    public function testTopLevelAndDelegateBothSignSameHash(): void
    {
        $topKp      = KeyPair::fromSeed(self::GOLDEN_SEED);
        $delegateKp = KeyPair::random();

        $topAddress      = Address::fromAccountId($topKp->getAccountId());
        $delegateAddress = Address::fromAccountId($delegateKp->getAccountId());

        $addressCreds = new SorobanAddressCredentials($topAddress, self::GOLDEN_NONCE, self::GOLDEN_EXPIRY, XdrSCVal::forVoid());
        $delegateSig  = new SorobanDelegateSignature($delegateAddress->toXdr(), XdrSCVal::forVoid(), []);
        $withDelegates = new SorobanAddressCredentialsWithDelegates($addressCreds, [$delegateSig]);
        $creds  = SorobanCredentials::forAddressWithDelegates($withDelegates);
        $entry  = new SorobanAuthorizationEntry($creds, $this->makeGoldenInvocation());

        // Compute expected hash before signing.
        $preimage     = $entry->buildPreimage(Network::testnet());
        $expectedHash = Hash::generate($preimage->encode());

        // Sign top-level.
        $entry->sign($topKp, Network::testnet());
        // Sign delegate.
        $entry->sign($delegateKp, Network::testnet(), null, $delegateKp->getAccountId());

        // Verify top-level signature against expectedHash.
        $topSig = $entry->credentials->addressWithDelegates?->addressCredentials?->signature;
        $this->assertNotNull($topSig?->vec);
        $topSigBytes = $this->extractSigBytesFromVecEntry($topSig->vec[0]);
        $this->assertTrue(
            $topKp->verifySignature($topSigBytes, $expectedHash),
            'Top-level signature must verify against the same hash',
        );

        // Verify delegate signature against the SAME hash.
        $delegateNode    = $entry->credentials->addressWithDelegates?->delegates[0];
        $delegateSigVec  = $delegateNode?->signature;
        $this->assertNotNull($delegateSigVec?->vec);
        $delegateSigBytes = $this->extractSigBytesFromVecEntry($delegateSigVec->vec[0]);
        $this->assertTrue(
            $delegateKp->verifySignature($delegateSigBytes, $expectedHash),
            'Delegate signature must verify against the same hash as top-level',
        );
    }

    public function testForAddressNoMatchThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/matched no node/');

        $signer  = KeyPair::random();
        $other   = KeyPair::random();
        $address = Address::fromAccountId($signer->getAccountId());
        $creds   = SorobanCredentials::forAddressCredentialsV2(
            new SorobanAddressCredentials($address, 1, 100, XdrSCVal::forVoid())
        );
        $entry = new SorobanAuthorizationEntry($creds, $this->makeGoldenInvocation());

        $entry->sign($signer, Network::testnet(), null, $other->getAccountId());
    }

    /**
     * Muxed M-addresses are rejected as forAddress targets.
     */
    public function testForAddressMuxedRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/muxed.*not valid|M-prefixed/i');

        $signer  = KeyPair::random();
        $address = Address::fromAccountId($signer->getAccountId());
        $creds   = SorobanCredentials::forAddressCredentialsV2(
            new SorobanAddressCredentials($address, 1, 100, XdrSCVal::forVoid())
        );
        $entry = new SorobanAuthorizationEntry($creds, $this->makeGoldenInvocation());

        // Fake an M-address (muxed).
        $entry->sign($signer, Network::testnet(), null, 'MAQAA5L65LSYH7CQ3VTJ7F3HHLGCL3DSLAR2Y47263D56MNNGHSQSAAAAAAAAAPCIBVZA');
    }

    // ---------------------------------------------------------------------------
    // TASK 5: Delegate tree building
    // ---------------------------------------------------------------------------

    public function testWithDelegatesBuildsTree(): void
    {
        $entry  = $this->makeGoldenLegacyEntry();
        $delegateKp = KeyPair::random();

        $result = SorobanAuthorizationEntry::withDelegates(
            $entry,
            self::GOLDEN_EXPIRY,
            [new SorobanDelegateDescriptor($delegateKp->getAccountId())],
        );

        $this->assertEquals(
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES,
            $result->credentials->credentialType,
        );
        $this->assertNotNull($result->credentials->addressWithDelegates);
        $this->assertCount(1, $result->credentials->addressWithDelegates->delegates);
        $this->assertEquals(self::GOLDEN_EXPIRY, $result->credentials->addressWithDelegates->addressCredentials->signatureExpirationLedger);
        $this->assertNull($result->credentials->addressWithDelegates->addressCredentials->signature->vec, 'Top-level signature must default to void');
    }

    public function testWithDelegatesPreservesNonceAndAddress(): void
    {
        $entry  = $this->makeGoldenLegacyEntry();
        $delegateKp = KeyPair::random();

        $result = SorobanAuthorizationEntry::withDelegates($entry, self::GOLDEN_EXPIRY, [
            new SorobanDelegateDescriptor($delegateKp->getAccountId()),
        ]);

        $topCreds = $result->credentials->addressWithDelegates?->addressCredentials;
        $this->assertEquals(self::GOLDEN_NONCE, $topCreds?->nonce);
        $this->assertEquals(self::GOLDEN_ACCOUNT, $topCreds?->address->accountId);
    }

    public function testWithDelegatesRejectsAlreadyWithDelegates(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/already ADDRESS_WITH_DELEGATES/');

        $entry      = $this->makeGoldenLegacyEntry();
        $delegateKp = KeyPair::random();
        $result = SorobanAuthorizationEntry::withDelegates($entry, self::GOLDEN_EXPIRY, [
            new SorobanDelegateDescriptor($delegateKp->getAccountId()),
        ]);
        // Second call should throw.
        SorobanAuthorizationEntry::withDelegates($result, self::GOLDEN_EXPIRY, []);
    }

    public function testWithDelegatesRejectsSourceAccount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $entry = new SorobanAuthorizationEntry(
            SorobanCredentials::forSourceAccount(),
            $this->makeGoldenInvocation(),
        );
        SorobanAuthorizationEntry::withDelegates($entry, 100, []);
    }

    // ---------------------------------------------------------------------------
    // Delegate sorting (XDR bytes, not strkey) and duplicate rejection
    // ---------------------------------------------------------------------------

    /**
     * An account address (type 0) sorts BEFORE a contract address (type 1) in XDR encoding.
     * But strkey ordering is "C" < "G", which is the inverse. This test verifies XDR-byte
     * ordering is used, not strkey ordering.
     */
    public function testDelegateSortingByXdrBytesNotStrkey(): void
    {
        $accountKp      = KeyPair::random();
        $contractStrkey = self::GOLDEN_CONTRACT;
        $contractHex    = StrKey::decodeContractIdHex($contractStrkey);

        // Create entries in "wrong" order (contract first, account second) — they must be sorted.
        $entry  = $this->makeGoldenLegacyEntry();
        $result = SorobanAuthorizationEntry::withDelegates($entry, self::GOLDEN_EXPIRY, [
            new SorobanDelegateDescriptor($contractStrkey), // "C..." — lexicographically before "G..."
            new SorobanDelegateDescriptor($accountKp->getAccountId()), // "G..." — lexicographically after "C..."
        ]);

        $delegates = $result->credentials->addressWithDelegates?->delegates;
        $this->assertNotNull($delegates);
        $this->assertCount(2, $delegates);

        // In XDR encoding, SC_ADDRESS_TYPE_ACCOUNT (0x00000000) < SC_ADDRESS_TYPE_CONTRACT (0x00000001),
        // so the ACCOUNT address must be FIRST after sorting by XDR bytes.
        $firstAddrStrkey = $delegates[0]->address->toStrKey();
        $this->assertEquals(
            $accountKp->getAccountId(),
            $firstAddrStrkey,
            'Account address must sort FIRST by XDR bytes (type 0), even though "G" > "C" as a string',
        );
    }

    public function testDuplicateDelegateInSameArrayThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Duplicate delegate address/');

        $delegateKp = KeyPair::random();
        $entry = $this->makeGoldenLegacyEntry();

        SorobanAuthorizationEntry::withDelegates($entry, self::GOLDEN_EXPIRY, [
            new SorobanDelegateDescriptor($delegateKp->getAccountId()),
            new SorobanDelegateDescriptor($delegateKp->getAccountId()), // Same address — must throw.
        ]);
    }

    /**
     * The same address at different nesting levels is legal (not a duplicate).
     */
    public function testSameAddressAtDifferentLevelsIsAllowed(): void
    {
        $delegateKp = KeyPair::random();
        $entry = $this->makeGoldenLegacyEntry();

        // Same address at top-level delegate and as its own nested delegate.
        $nested = new SorobanDelegateDescriptor($delegateKp->getAccountId());
        $result = SorobanAuthorizationEntry::withDelegates($entry, self::GOLDEN_EXPIRY, [
            new SorobanDelegateDescriptor($delegateKp->getAccountId(), null, [$nested]),
        ]);

        $this->assertCount(1, $result->credentials->addressWithDelegates?->delegates ?? []);
        $this->assertCount(1, $result->credentials->addressWithDelegates?->delegates[0]->nestedDelegates ?? []);
    }

    // ---------------------------------------------------------------------------
    // TASK 6: Decode depth guard
    // ---------------------------------------------------------------------------

    /**
     * A tree deeper than RECURSION_LIMIT (128) must throw InvalidArgumentException when decoded.
     */
    public function testDepthGuardRejectsDeepTree(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/recursion limit|depth/i');

        $deepXdr = $this->buildDeepDelegateXdr(130);
        $buffer  = new XdrBuffer($deepXdr);
        \Soneso\StellarSDK\Xdr\XdrSorobanDelegateSignature::decode($buffer);
    }

    /**
     * A shallow tree (depth <= RECURSION_LIMIT) must decode without throwing.
     */
    public function testDepthGuardAcceptsShallowTree(): void
    {
        $shallowXdr = $this->buildDeepDelegateXdr(3);
        $buffer     = new XdrBuffer($shallowXdr);
        $decoded    = \Soneso\StellarSDK\Xdr\XdrSorobanDelegateSignature::decode($buffer);
        $this->assertInstanceOf(\Soneso\StellarSDK\Xdr\XdrSorobanDelegateSignature::class, $decoded);
    }

    /**
     * Builds XDR bytes for a linear delegate chain of $depth levels.
     *
     * Structure: node -> nestedDelegates[node -> nestedDelegates[...]]
     * Uses the golden account address so the bytes are valid.
     */
    private function buildDeepDelegateXdr(int $depth): string
    {
        // Build innermost to outermost.
        $accountAddr = XdrSCAddress::forAccountId(self::GOLDEN_ACCOUNT);
        $voidSig     = XdrSCVal::forVoid();
        $node        = new \Soneso\StellarSDK\Xdr\XdrSorobanDelegateSignature($accountAddr, $voidSig, []);

        // Wrap $depth times.
        for ($i = 1; $i < $depth; $i++) {
            $node = new \Soneso\StellarSDK\Xdr\XdrSorobanDelegateSignature($accountAddr, $voidSig, [$node]);
        }
        return $node->encode();
    }

    // ---------------------------------------------------------------------------
    // Entry-level XDR round-trips including WITH_DELEGATES
    // ---------------------------------------------------------------------------

    public function testAuthorizationEntryXdrRoundTripV2(): void
    {
        $entry  = $this->makeGoldenV2Entry();
        $b64    = $entry->toBase64Xdr();
        $decoded = SorobanAuthorizationEntry::fromBase64Xdr($b64);

        $this->assertEquals(
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2,
            $decoded->credentials->credentialType,
        );
        $this->assertNotNull($decoded->credentials->addressCredentials);
        $this->assertEquals(self::GOLDEN_NONCE, $decoded->credentials->addressCredentials->nonce);
        $this->assertEquals($b64, $decoded->toBase64Xdr());
    }

    public function testAuthorizationEntryXdrRoundTripWithDelegates(): void
    {
        $entry   = $this->makeGoldenLegacyEntry();
        $delegKp = KeyPair::random();
        $result  = SorobanAuthorizationEntry::withDelegates($entry, self::GOLDEN_EXPIRY, [
            new SorobanDelegateDescriptor($delegKp->getAccountId()),
        ]);

        $b64     = $result->toBase64Xdr();
        $decoded = SorobanAuthorizationEntry::fromBase64Xdr($b64);

        $this->assertEquals(
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES,
            $decoded->credentials->credentialType,
        );
        $this->assertCount(1, $decoded->credentials->addressWithDelegates?->delegates ?? []);
        $this->assertEquals($b64, $decoded->toBase64Xdr());
    }

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    private function extractSignatureHex(SorobanAuthorizationEntry $entry): string
    {
        $sig = $entry->credentials->addressCredentials?->signature;
        if ($sig?->vec) {
            foreach ($sig->vec[0]->map ?? [] as $mapEntry) {
                if ($mapEntry->key->sym === 'signature') {
                    return bin2hex($mapEntry->val->bytes?->getValue() ?? '');
                }
            }
        }
        return '';
    }

    private function extractSigBytesFromVecEntry(XdrSCVal $vecEntry): string
    {
        foreach ($vecEntry->map ?? [] as $mapEntry) {
            if ($mapEntry->key->sym === 'signature') {
                return $mapEntry->val->bytes?->getValue() ?? '';
            }
        }
        return '';
    }
}
