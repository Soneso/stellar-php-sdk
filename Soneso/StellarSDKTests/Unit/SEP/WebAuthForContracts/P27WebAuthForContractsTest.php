<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\SEP\WebAuthForContracts;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationError;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidServerSignature;
use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\SorobanAddressCredentials;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Soroban\SorobanAuthorizedFunction;
use Soneso\StellarSDK\Soroban\SorobanAuthorizedInvocation;
use Soneso\StellarSDK\Soroban\SorobanCredentials;
use Soneso\StellarSDK\Soroban\SorobanDelegateDescriptor;
use Soneso\StellarSDK\Util\Hash;
use Soneso\StellarSDK\Xdr\XdrEncoder;
use Soneso\StellarSDK\Xdr\XdrInvokeContractArgs;
use Soneso\StellarSDK\Xdr\XdrSCMapEntry;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType;

/**
 * Protocol 27 (CAP-71) tests for the SEP-45 WebAuthForContracts flow.
 *
 * Verifies that all three address credential arms (ADDRESS, ADDRESS_V2, ADDRESS_WITH_DELEGATES)
 * are handled correctly in validateChallenge, signAuthorizationEntries, and verifyServerSignature.
 * Also confirms that source-account credentials are rejected with a descriptive error and that the
 * legacy ADDRESS path produces byte-identical hashes to the golden cross-SDK vector.
 */
class P27WebAuthForContractsTest extends TestCase
{
    // ---------------------------------------------------------------------------
    // Golden test vectors (cross-SDK, TESTNET)
    //
    // Network:  "Test SDF Network ; September 2015"
    // Seed:     SDJHRQF4GCMIIKAAAQ6IHY42X73FQFLHUULAPSKKD4DFDM7UXWWCRHBE
    // Account:  GCZHXL5HXQX5ABDM26LHYRCQZ5OJFHLOPLZX47WEBP3V2PF5AVFK2A5D
    // Contract: CA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAXE
    // Invocation: contractFn hello(u64 1234), no subInvocations
    // Nonce: 123456789101112
    // signatureExpirationLedger: 4242
    // ---------------------------------------------------------------------------

    private const GOLDEN_SEED     = 'SDJHRQF4GCMIIKAAAQ6IHY42X73FQFLHUULAPSKKD4DFDM7UXWWCRHBE';
    private const GOLDEN_ACCOUNT  = 'GCZHXL5HXQX5ABDM26LHYRCQZ5OJFHLOPLZX47WEBP3V2PF5AVFK2A5D';
    private const GOLDEN_CONTRACT = 'CA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAXE';
    private const GOLDEN_NONCE    = 123456789101112;
    private const GOLDEN_EXPIRY   = 4242;

    // Legacy ADDRESS preimage sha256 (ENVELOPE_TYPE_SOROBAN_AUTHORIZATION)
    private const GOLDEN_LEGACY_PAYLOAD_HEX =
        '120c429d4333e12e0ca2c5ac10630e728fdd33240bf7066f4c62f6a2d6fa3cbe';

    // ADDRESS_V2 preimage sha256 (ENVELOPE_TYPE_SOROBAN_AUTHORIZATION_WITH_ADDRESS)
    private const GOLDEN_V2_PAYLOAD_HEX =
        '252a0d6117840dff37b765839810fb6ecc446198e73062e01bc961e49355b7b9';

    // Fixed server and client IDs for validateChallenge / signAuthorizationEntries tests
    private string $authServer          = 'https://auth.example.stellar.org';
    private string $webAuthContractId   = 'CA7A3N2BB35XMTFPAYWVZEF4TEYXW7DAEWDXJNQGUPR5SWSM2UVZCJM2';
    private string $serverAccountId     = 'GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP';
    private string $serverSecretSeed    = 'SAWDHXQG6ROJSU4QGCW7NSTYFHPTPIVC2NC7QKVTO7PZCSO2WEBGM54W';
    private string $clientContractId    = 'CDZJIDQW5WTPAZ64PGIJGVEIDNK72LL3LKUZWG3G6GWXYQKI2JNIVFNV';
    private string $domain              = 'example.stellar.org';

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
        $creds   = SorobanCredentials::forAddress(
            $address,
            self::GOLDEN_NONCE,
            self::GOLDEN_EXPIRY,
            XdrSCVal::forVoid(),
        );
        return new SorobanAuthorizationEntry($creds, $this->makeGoldenInvocation());
    }

    private function makeGoldenV2Entry(): SorobanAuthorizationEntry
    {
        $address      = Address::fromAccountId(self::GOLDEN_ACCOUNT);
        $addrCreds    = new SorobanAddressCredentials($address, self::GOLDEN_NONCE, self::GOLDEN_EXPIRY, XdrSCVal::forVoid());
        $creds        = SorobanCredentials::forAddressCredentialsV2($addrCreds);
        return new SorobanAuthorizationEntry($creds, $this->makeGoldenInvocation());
    }

    /**
     * Builds an authorization entry using an arbitrary address arm with the SEP-45 challenge shape.
     *
     * @param string $credentialsAddress strkey of the credential address
     * @param string $contractId the web auth contract ID (C... strkey)
     * @param string $functionName contract function name
     * @param XdrSCVal $argsMap pre-built arguments map
     * @param int $nonce credential nonce
     * @param int $expirationLedger credential expiration ledger
     * @param int $credType one of XdrSorobanCredentialsType constants (ADDRESS, ADDRESS_V2)
     * @return SorobanAuthorizationEntry the built entry
     */
    private function buildAuthEntry(
        string   $credentialsAddress,
        string   $contractId,
        string   $functionName,
        XdrSCVal $argsMap,
        int      $nonce,
        int      $expirationLedger,
        int      $credType = XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS,
    ): SorobanAuthorizationEntry {
        $address     = Address::fromAnyId($credentialsAddress);
        $addrCreds   = new SorobanAddressCredentials($address, $nonce, $expirationLedger, XdrSCVal::forVec([]));
        $credentials = match ($credType) {
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2
                => SorobanCredentials::forAddressCredentialsV2($addrCreds),
            default
                => SorobanCredentials::forAddressCredentials($addrCreds),
        };

        $contractAddress = Address::fromContractId(StrKey::decodeContractIdHex($contractId));
        $contractFn      = new XdrInvokeContractArgs($contractAddress->toXdr(), $functionName, [$argsMap]);
        $function        = new SorobanAuthorizedFunction($contractFn);
        $invocation      = new SorobanAuthorizedInvocation($function, []);

        return new SorobanAuthorizationEntry($credentials, $invocation);
    }

    /**
     * Builds the SEP-45 args map as an XdrSCVal map.
     *
     * @param string $account client account strkey
     * @param string $homeDomain home domain string
     * @param string $webAuthDomain web auth domain string
     * @param string $webAuthDomainAccount web auth domain account strkey
     * @param string $nonce nonce string
     * @return XdrSCVal the map value
     */
    private function buildArgsMap(
        string $account,
        string $homeDomain,
        string $webAuthDomain,
        string $webAuthDomainAccount,
        string $nonce,
    ): XdrSCVal {
        return XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forSymbol('account'),               XdrSCVal::forString($account)),
            new XdrSCMapEntry(XdrSCVal::forSymbol('home_domain'),            XdrSCVal::forString($homeDomain)),
            new XdrSCMapEntry(XdrSCVal::forSymbol('web_auth_domain'),        XdrSCVal::forString($webAuthDomain)),
            new XdrSCMapEntry(XdrSCVal::forSymbol('web_auth_domain_account'), XdrSCVal::forString($webAuthDomainAccount)),
            new XdrSCMapEntry(XdrSCVal::forSymbol('nonce'),                  XdrSCVal::forString($nonce)),
        ]);
    }

    /**
     * Encodes an array of SorobanAuthorizationEntry to a base64 XDR array as used by SEP-45.
     *
     * @param array<SorobanAuthorizationEntry> $entries
     */
    private function encodeAuthEntries(array $entries): string
    {
        $bytes = XdrEncoder::unsignedInteger32(count($entries));
        foreach ($entries as $entry) {
            $bytes .= $entry->toXdr()->encode();
        }
        return base64_encode($bytes);
    }

    /**
     * Creates a WebAuthForContracts instance backed by the fixed test server key.
     */
    private function makeWebAuth(): WebAuthForContracts
    {
        return new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet(),
        );
    }

    // ---------------------------------------------------------------------------
    // TASK 1 — verifyServerSignature: ADDRESS_V2 server entry
    // ---------------------------------------------------------------------------

    /**
     * verifyServerSignature accepts an ADDRESS_V2 server entry whose signature was made over
     * the WITH_ADDRESS preimage (ENVELOPE_TYPE_SOROBAN_AUTHORIZATION_WITH_ADDRESS).
     *
     * This is the core P27 requirement: legacy-arm-only acceptance must not remain.
     */
    public function testVerifyServerSignatureAcceptsAddressV2Entry(): void
    {
        $serverKeyPair = KeyPair::fromSeed($this->serverSecretSeed);

        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'test_nonce_v2',
        );

        // Build server entry as ADDRESS_V2
        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId,
            $this->webAuthContractId,
            'web_auth_verify',
            $argsMap,
            99001,
            2000000,
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2,
        );

        // Sign with the WITH_ADDRESS preimage (what sign() does for ADDRESS_V2)
        $serverEntry->sign($serverKeyPair, Network::testnet());

        // Validate that the signature is considered valid by verifyServerSignature
        // (called indirectly via validateChallenge)
        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId,
            $this->webAuthContractId,
            'web_auth_verify',
            $argsMap,
            99002,
            2000000,
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2,
        );

        $webAuth = $this->makeWebAuth();
        // validateChallenge must NOT throw — the V2 server signature is valid
        $webAuth->validateChallenge(
            [$serverEntry, $clientEntry],
            $this->clientContractId,
            $this->domain,
        );

        // If we reach here, the V2 server entry was accepted
        $this->assertTrue(true);
    }

    /**
     * verifyServerSignature rejects an ADDRESS_V2 server entry that was signed over the
     * legacy preimage (ENVELOPE_TYPE_SOROBAN_AUTHORIZATION) instead of the correct
     * WITH_ADDRESS preimage.
     *
     * A V2 entry whose payload was built without the address field must not verify.
     */
    public function testVerifyServerSignatureRejectsV2EntrySignedOverLegacyPreimage(): void
    {
        $this->expectException(ContractChallengeValidationErrorInvalidServerSignature::class);

        $serverKeyPair = KeyPair::fromSeed($this->serverSecretSeed);

        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'mismatch_nonce',
        );

        // Build the entry as ADDRESS_V2 but sign it using the legacy (ADDRESS) preimage.
        // To simulate this, we build the same entry as ADDRESS (legacy) first, sign it
        // to capture the payload, then put those signature bytes into an ADDRESS_V2 entry.
        $legacyEntry = $this->buildAuthEntry(
            $this->serverAccountId,
            $this->webAuthContractId,
            'web_auth_verify',
            $argsMap,
            88001,
            1500000,
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS,
        );
        // Build legacy preimage and sign it
        $legacyPreimage = $legacyEntry->buildPreimage(Network::testnet());
        $legacyPayload  = Hash::generate($legacyPreimage->encode());
        $sigBytes        = $serverKeyPair->sign($legacyPayload);

        // Inject that legacy-preimage signature into an ADDRESS_V2 entry manually
        $serverAddress = Address::fromAccountId($this->serverAccountId);
        $fakeSigEntry  = new \Soneso\StellarSDK\Soroban\AccountEd25519Signature(
            $serverKeyPair->getPublicKey(),
            $sigBytes,
        );
        $fakeSigVec = XdrSCVal::forVec([$fakeSigEntry->toXdrSCVal()]);

        $addrCreds   = new SorobanAddressCredentials($serverAddress, 88001, 1500000, $fakeSigVec);
        $v2Creds     = SorobanCredentials::forAddressCredentialsV2($addrCreds);

        $contractAddress = Address::fromContractId(StrKey::decodeContractIdHex($this->webAuthContractId));
        $contractFn      = new XdrInvokeContractArgs($contractAddress->toXdr(), 'web_auth_verify', [$argsMap]);
        $fn              = new SorobanAuthorizedFunction($contractFn);
        $invocation      = new SorobanAuthorizedInvocation($fn, []);
        $v2ServerEntry   = new SorobanAuthorizationEntry($v2Creds, $invocation);

        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId,
            $this->webAuthContractId,
            'web_auth_verify',
            $argsMap,
            88002,
            1500000,
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2,
        );

        $webAuth = $this->makeWebAuth();
        // validateChallenge must throw because the V2 entry carries a legacy-preimage signature
        $webAuth->validateChallenge(
            [$v2ServerEntry, $clientEntry],
            $this->clientContractId,
            $this->domain,
        );
    }

    // ---------------------------------------------------------------------------
    // TASK 2 — Legacy ADDRESS path: golden payload hash byte-identity
    // ---------------------------------------------------------------------------

    /**
     * The legacy ADDRESS arm must produce the exact golden payload sha256.
     *
     * This ensures byte-identity with the pre-change behavior and cross-SDK compatibility.
     */
    public function testLegacyAddressPayloadMatchesGolden(): void
    {
        $legacyEntry = $this->makeGoldenLegacyEntry();
        $preimage    = $legacyEntry->buildPreimage(Network::testnet());
        $payload     = Hash::generate($preimage->encode());

        $this->assertSame(
            self::GOLDEN_LEGACY_PAYLOAD_HEX,
            bin2hex($payload),
            'Legacy ADDRESS preimage sha256 must match the golden cross-SDK vector',
        );
    }

    /**
     * The ADDRESS_V2 arm must produce the exact golden V2 payload sha256.
     */
    public function testAddressV2PayloadMatchesGolden(): void
    {
        $v2Entry  = $this->makeGoldenV2Entry();
        $preimage = $v2Entry->buildPreimage(Network::testnet());
        $payload  = Hash::generate($preimage->encode());

        $this->assertSame(
            self::GOLDEN_V2_PAYLOAD_HEX,
            bin2hex($payload),
            'ADDRESS_V2 preimage sha256 must match the golden cross-SDK vector',
        );
    }

    /**
     * Legacy and V2 payloads must differ for otherwise-identical fields.
     */
    public function testLegacyAndV2PayloadsDiffer(): void
    {
        $legacyPreimage = $this->makeGoldenLegacyEntry()->buildPreimage(Network::testnet());
        $v2Preimage     = $this->makeGoldenV2Entry()->buildPreimage(Network::testnet());

        $this->assertNotSame(
            base64_encode($legacyPreimage->encode()),
            base64_encode($v2Preimage->encode()),
            'Legacy and V2 preimages must differ',
        );
    }

    // ---------------------------------------------------------------------------
    // TASK 3 — signAuthorizationEntries: arm preservation and expiration stamping
    // ---------------------------------------------------------------------------

    /**
     * signAuthorizationEntries preserves the ADDRESS_V2 arm after signing and applies
     * the expiration ledger before hashing (so the preimage is built with the new expiration).
     */
    public function testSignAuthorizationEntriesPreservesV2Arm(): void
    {
        $serverKeyPair = KeyPair::fromSeed($this->serverSecretSeed);
        $clientKeyPair = KeyPair::random();

        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'nonce_arm_preserve',
        );

        // Server entry as ADDRESS_V2
        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId,
            $this->webAuthContractId,
            'web_auth_verify',
            $argsMap,
            77001,
            900000,
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2,
        );
        $serverEntry->sign($serverKeyPair, Network::testnet());

        // Client entry as ADDRESS_V2 (unsigned, to be signed by signAuthorizationEntries)
        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId,
            $this->webAuthContractId,
            'web_auth_verify',
            $argsMap,
            77002,
            900000,
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2,
        );

        $webAuth = $this->makeWebAuth();
        $signed  = $webAuth->signAuthorizationEntries(
            [$serverEntry, $clientEntry],
            $this->clientContractId,
            [$clientKeyPair],
            999999, // expiration ledger applied before hashing
        );

        $this->assertCount(2, $signed);

        // Both entries must still carry the ADDRESS_V2 arm
        foreach ($signed as $entry) {
            $this->assertSame(
                XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2,
                $entry->credentials->credentialType,
                'Arm must be preserved as ADDRESS_V2 after signing',
            );
        }

        // The client entry must have a non-void signature after signing
        $clientSigned      = $signed[1];
        $innerCreds        = $clientSigned->credentials->getAddressCredentials();
        $this->assertNotNull($innerCreds);
        $this->assertSame(
            999999,
            $innerCreds->signatureExpirationLedger,
            'signatureExpirationLedger must be stamped by signAuthorizationEntries',
        );
        $this->assertNotNull($innerCreds->signature->vec);
        $this->assertCount(1, $innerCreds->signature->vec);
    }

    /**
     * signAuthorizationEntries preserves the legacy ADDRESS arm after signing.
     */
    public function testSignAuthorizationEntriesPreservesLegacyArm(): void
    {
        $serverKeyPair = KeyPair::fromSeed($this->serverSecretSeed);
        $clientKeyPair = KeyPair::random();

        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'nonce_legacy_arm',
        );

        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId,
            $this->webAuthContractId,
            'web_auth_verify',
            $argsMap,
            66001,
            800000,
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS,
        );
        $serverEntry->sign($serverKeyPair, Network::testnet());

        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId,
            $this->webAuthContractId,
            'web_auth_verify',
            $argsMap,
            66002,
            800000,
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS,
        );

        $webAuth = $this->makeWebAuth();
        $signed  = $webAuth->signAuthorizationEntries(
            [$serverEntry, $clientEntry],
            $this->clientContractId,
            [$clientKeyPair],
            888888,
        );

        foreach ($signed as $entry) {
            $this->assertSame(
                XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS,
                $entry->credentials->credentialType,
                'Arm must remain ADDRESS after signing',
            );
        }

        $innerCreds = $signed[1]->credentials->getAddressCredentials();
        $this->assertNotNull($innerCreds);
        $this->assertSame(888888, $innerCreds->signatureExpirationLedger);
    }

    /**
     * signAuthorizationEntries handles ADDRESS_WITH_DELEGATES entries: the arm is preserved
     * and the expiration is written to the top-level credentials.
     */
    public function testSignAuthorizationEntriesPreservesWithDelegatesArm(): void
    {
        $serverKeyPair = KeyPair::fromSeed($this->serverSecretSeed);
        $clientKeyPair = KeyPair::random();
        $delegateKeyPair = KeyPair::random();

        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'nonce_with_delegates',
        );

        // Server entry using legacy ADDRESS (straightforward server signing)
        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId,
            $this->webAuthContractId,
            'web_auth_verify',
            $argsMap,
            55001,
            700000,
        );
        $serverEntry->sign($serverKeyPair, Network::testnet());

        // Build a base V2 entry for the client, then wrap it with a delegate
        $clientV2Entry = $this->buildAuthEntry(
            $this->clientContractId,
            $this->webAuthContractId,
            'web_auth_verify',
            $argsMap,
            55002,
            700000,
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2,
        );
        $delegateDescriptor = new SorobanDelegateDescriptor($delegateKeyPair->getAccountId());
        $withDelegatesEntry = SorobanAuthorizationEntry::withDelegates($clientV2Entry, 700000, [$delegateDescriptor]);

        $webAuth = $this->makeWebAuth();
        $signed  = $webAuth->signAuthorizationEntries(
            [$serverEntry, $withDelegatesEntry],
            $this->clientContractId,
            [$clientKeyPair],
            750000,
        );

        $this->assertCount(2, $signed);
        $signedWithDelegates = $signed[1];

        // Arm must be preserved
        $this->assertSame(
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES,
            $signedWithDelegates->credentials->credentialType,
        );

        // Expiration must be stamped on the top-level credentials
        $innerCreds = $signedWithDelegates->credentials->getAddressCredentials();
        $this->assertNotNull($innerCreds);
        $this->assertSame(750000, $innerCreds->signatureExpirationLedger);

        // Top-level signature must have been written
        $this->assertNotNull($innerCreds->signature->vec);
        $this->assertCount(1, $innerCreds->signature->vec);
    }

    // ---------------------------------------------------------------------------
    // TASK 4 — validateChallenge: all three arms recognized; source-account rejected
    // ---------------------------------------------------------------------------

    /**
     * validateChallenge recognizes an ADDRESS_V2 server entry and accepts it
     * when the signature is valid.
     */
    public function testValidateChallengeAcceptsV2ServerEntry(): void
    {
        $serverKeyPair = KeyPair::fromSeed($this->serverSecretSeed);

        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'validate_v2_nonce',
        );

        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId,
            $this->webAuthContractId,
            'web_auth_verify',
            $argsMap,
            44001,
            600000,
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2,
        );
        $serverEntry->sign($serverKeyPair, Network::testnet());

        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId,
            $this->webAuthContractId,
            'web_auth_verify',
            $argsMap,
            44002,
            600000,
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2,
        );

        $webAuth = $this->makeWebAuth();
        $webAuth->validateChallenge(
            [$serverEntry, $clientEntry],
            $this->clientContractId,
            $this->domain,
        );

        $this->assertTrue(true); // No exception means success
    }

    /**
     * validateChallenge recognizes a client entry using the ADDRESS_V2 arm.
     */
    public function testValidateChallengeAcceptsV2ClientEntry(): void
    {
        $serverKeyPair = KeyPair::fromSeed($this->serverSecretSeed);

        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'validate_v2_client_nonce',
        );

        // Server entry as legacy ADDRESS
        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId,
            $this->webAuthContractId,
            'web_auth_verify',
            $argsMap,
            33001,
            500000,
        );
        $serverEntry->sign($serverKeyPair, Network::testnet());

        // Client entry as ADDRESS_V2
        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId,
            $this->webAuthContractId,
            'web_auth_verify',
            $argsMap,
            33002,
            500000,
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2,
        );

        $webAuth = $this->makeWebAuth();
        $webAuth->validateChallenge(
            [$serverEntry, $clientEntry],
            $this->clientContractId,
            $this->domain,
        );

        $this->assertTrue(true);
    }

    /**
     * validateChallenge throws with a descriptive error when an entry carries
     * source-account credentials (no address arm).
     */
    public function testValidateChallengeRejectsSourceAccountCredentials(): void
    {
        $this->expectException(ContractChallengeValidationError::class);
        $this->expectExceptionMessageMatches('/source-account credentials/i');

        $serverKeyPair = KeyPair::fromSeed($this->serverSecretSeed);

        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'source_account_nonce',
        );

        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId,
            $this->webAuthContractId,
            'web_auth_verify',
            $argsMap,
            22001,
            400000,
        );
        $serverEntry->sign($serverKeyPair, Network::testnet());

        // Build a client entry with source-account credentials (no address)
        $contractAddress = Address::fromContractId(StrKey::decodeContractIdHex($this->webAuthContractId));
        $contractFn      = new XdrInvokeContractArgs($contractAddress->toXdr(), 'web_auth_verify', [$argsMap]);
        $fn              = new SorobanAuthorizedFunction($contractFn);
        $invocation      = new SorobanAuthorizedInvocation($fn, []);
        $sourceAccEntry  = new SorobanAuthorizationEntry(
            SorobanCredentials::forSourceAccount(),
            $invocation,
        );

        $webAuth = $this->makeWebAuth();
        $webAuth->validateChallenge(
            [$serverEntry, $sourceAccEntry],
            $this->clientContractId,
            $this->domain,
        );
    }

    // ---------------------------------------------------------------------------
    // TASK 5 — verifyServerSignature: legacy server entry still verifies
    // ---------------------------------------------------------------------------

    /**
     * The legacy ADDRESS server entry continues to be accepted after the changes.
     * This is the core regression test ensuring backward compatibility.
     */
    public function testVerifyServerSignatureAcceptsLegacyAddressEntry(): void
    {
        $serverKeyPair = KeyPair::fromSeed($this->serverSecretSeed);

        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'legacy_server_nonce',
        );

        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId,
            $this->webAuthContractId,
            'web_auth_verify',
            $argsMap,
            11001,
            300000,
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS,
        );
        $serverEntry->sign($serverKeyPair, Network::testnet());

        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId,
            $this->webAuthContractId,
            'web_auth_verify',
            $argsMap,
            11002,
            300000,
        );

        $webAuth = $this->makeWebAuth();
        $webAuth->validateChallenge(
            [$serverEntry, $clientEntry],
            $this->clientContractId,
            $this->domain,
        );

        $this->assertTrue(true);
    }

    /**
     * A legacy ADDRESS server entry that carries no signature is rejected as having an
     * invalid server signature, which confirms the signature check runs for legacy entries.
     */
    public function testVerifyServerSignatureRejectsUnsignedLegacyEntry(): void
    {
        $this->expectException(ContractChallengeValidationErrorInvalidServerSignature::class);

        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'unsigned_legacy_nonce',
        );

        // Server entry with no signature
        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId,
            $this->webAuthContractId,
            'web_auth_verify',
            $argsMap,
            10001,
            200000,
        );

        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId,
            $this->webAuthContractId,
            'web_auth_verify',
            $argsMap,
            10002,
            200000,
        );

        $webAuth = $this->makeWebAuth();
        $webAuth->validateChallenge(
            [$serverEntry, $clientEntry],
            $this->clientContractId,
            $this->domain,
        );
    }

    // ---------------------------------------------------------------------------
    // TASK 6 — signAuthorizationEntries: ADDRESS_WITH_DELEGATES expiration write-back
    // ---------------------------------------------------------------------------

    /**
     * signAuthorizationEntries writes the expiration to the inner address credentials
     * of an ADDRESS_WITH_DELEGATES entry via writeBackAddressCredentials, not directly
     * through $credentials->addressCredentials (which is null for that arm).
     */
    public function testSignAuthorizationEntriesWritesExpirationViaWriteBack(): void
    {
        $serverKeyPair   = KeyPair::fromSeed($this->serverSecretSeed);
        $clientKeyPair   = KeyPair::random();
        $delegateKeyPair = KeyPair::random();

        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'writeback_expiry_nonce',
        );

        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId,
            $this->webAuthContractId,
            'web_auth_verify',
            $argsMap,
            9001,
            100000,
        );
        $serverEntry->sign($serverKeyPair, Network::testnet());

        // Client entry is ADDRESS_WITH_DELEGATES
        $baseClientEntry = $this->buildAuthEntry(
            $this->clientContractId,
            $this->webAuthContractId,
            'web_auth_verify',
            $argsMap,
            9002,
            100000,
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2,
        );
        $descriptor         = new SorobanDelegateDescriptor($delegateKeyPair->getAccountId());
        $withDelegatesEntry = SorobanAuthorizationEntry::withDelegates($baseClientEntry, 100000, [$descriptor]);

        // Confirm $addressCredentials is null before signing (arm invariant)
        $this->assertNull($withDelegatesEntry->credentials->addressCredentials);

        $webAuth = $this->makeWebAuth();
        $signed  = $webAuth->signAuthorizationEntries(
            [$serverEntry, $withDelegatesEntry],
            $this->clientContractId,
            [$clientKeyPair],
            123456,
        );

        $signedClient = $signed[1];
        $this->assertSame(
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES,
            $signedClient->credentials->credentialType,
        );

        // The expiration must be stamped on the inner credentials
        $innerCreds = $signedClient->credentials->getAddressCredentials();
        $this->assertNotNull($innerCreds);
        $this->assertSame(123456, $innerCreds->signatureExpirationLedger);

        // $addressCredentials is still null (arm is preserved)
        $this->assertNull($signedClient->credentials->addressCredentials);
    }
}
