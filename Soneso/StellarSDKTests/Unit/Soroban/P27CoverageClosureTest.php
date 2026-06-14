<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Soroban;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperation;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Contract\AssembledTransaction;
use Soneso\StellarSDK\Soroban\Contract\AssembledTransactionOptions;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\MethodOptions;
use Soneso\StellarSDK\Soroban\Contract\SimulateHostFunctionResult;
use Soneso\StellarSDK\Soroban\SorobanAddressCredentials;
use Soneso\StellarSDK\Soroban\SorobanAddressCredentialsWithDelegates;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Soroban\SorobanAuthorizedFunction;
use Soneso\StellarSDK\Soroban\SorobanAuthorizedInvocation;
use Soneso\StellarSDK\Soroban\SorobanCredentials;
use Soneso\StellarSDK\Soroban\SorobanDelegateDescriptor;
use Soneso\StellarSDK\Soroban\SorobanDelegateSignature;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Soroban\Responses\SimulateTransactionResponse;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentials;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType;
use Soneso\StellarSDK\Xdr\XdrSorobanDelegateSignature;
use Soneso\StellarSDK\Xdr\XdrSorobanResources;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionDataExt;

/**
 * Protocol 27 (CAP-71) coverage closure tests.
 *
 * Targets executable lines missed by Batches B, C, and D unit tests:
 * error-guard paths in SorobanCredentials / SorobanAuthorizationEntry, getters/setters
 * on the new wrapper types, depth-limit signing, deep delegate-tree XDR round-trip,
 * getBlockingNonInvokerSigners, and the authV2 MethodOptions constructor.
 *
 * No test here duplicates an assertion already present in P27AuthorizationTest,
 * P27AssembledTransactionTest, or P27WebAuthForContractsTest.
 */
class P27CoverageClosureTest extends TestCase
{
    private const GOLDEN_ACCOUNT  = 'GCZHXL5HXQX5ABDM26LHYRCQZ5OJFHLOPLZX47WEBP3V2PF5AVFK2A5D';
    private const GOLDEN_CONTRACT = 'CA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAXE';
    private const GOLDEN_NONCE    = 123456789101112;
    private const GOLDEN_EXPIRY   = 4242;
    private const TEST_CONTRACT   = 'CA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUWDA';
    private const TEST_SECRET     = 'SAMKI63THJER2XVJA5LQXIPBWIV6FEFSS5ILURYGSCHFKZVDE5YVQWC7';
    private const TEST_RPC_URL    = 'http://localhost:1';

    // =========================================================================
    // SorobanCredentials — error paths and new helpers
    // =========================================================================

    /**
     * fromXdr ADDRESS arm with a null address payload must throw.
     *
     * This guard exists because XdrSorobanCredentials could theoretically carry a
     * type=ADDRESS value but no address field (a corrupt or hand-crafted XDR object).
     */
    public function testFromXdrAddressArmMissingPayloadThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/ADDRESS is missing address payload/');

        $xdrCreds = new XdrSorobanCredentials(new \Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType(
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS
        ));
        // address field intentionally left null (default)
        SorobanCredentials::fromXdr($xdrCreds);
    }

    /**
     * fromXdr ADDRESS_V2 arm with a null addressV2 payload must throw.
     */
    public function testFromXdrAddressV2ArmMissingPayloadThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/ADDRESS_V2 is missing addressV2 payload/');

        $xdrCreds = new XdrSorobanCredentials(new \Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType(
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2
        ));
        SorobanCredentials::fromXdr($xdrCreds);
    }

    /**
     * fromXdr ADDRESS_WITH_DELEGATES arm with a null addressWithDelegates payload must throw.
     */
    public function testFromXdrAddressWithDelegatesArmMissingPayloadThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/ADDRESS_WITH_DELEGATES is missing addressWithDelegates payload/');

        $xdrCreds = new XdrSorobanCredentials(new \Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType(
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES
        ));
        SorobanCredentials::fromXdr($xdrCreds);
    }

    /**
     * toXdr ADDRESS arm with null addressCredentials must throw.
     */
    public function testToXdrAddressArmMissingCredentialsThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/ADDRESS arm requires addressCredentials/');

        $creds = new SorobanCredentials(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS, null);
        $creds->toXdr();
    }

    /**
     * toXdr ADDRESS_V2 arm with null addressCredentials must throw.
     */
    public function testToXdrAddressV2ArmMissingCredentialsThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/ADDRESS_V2 arm requires addressCredentials/');

        $creds = new SorobanCredentials(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2, null);
        $creds->toXdr();
    }

    /**
     * toXdr ADDRESS_WITH_DELEGATES arm with null addressWithDelegates must throw.
     */
    public function testToXdrAddressWithDelegatesArmMissingPayloadThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/ADDRESS_WITH_DELEGATES arm requires addressWithDelegates/');

        $creds = new SorobanCredentials(
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES,
            null,
            null,
        );
        $creds->toXdr();
    }

    /**
     * toXdr unknown credentialType must throw.
     */
    public function testToXdrUnknownTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unknown credential type/');

        $creds = new SorobanCredentials(99);
        $creds->toXdr();
    }

    /**
     * isSourceAccount returns true for SOURCE_ACCOUNT and false for ADDRESS arms.
     */
    public function testIsSourceAccount(): void
    {
        $this->assertTrue(SorobanCredentials::forSourceAccount()->isSourceAccount());
        $address = Address::fromAccountId(self::GOLDEN_ACCOUNT);
        $this->assertFalse(
            SorobanCredentials::forAddress($address, 1, 100, XdrSCVal::forVoid())->isSourceAccount()
        );
    }

    /**
     * isAddressBased returns true for all three address arms.
     */
    public function testIsAddressBased(): void
    {
        $address      = Address::fromAccountId(self::GOLDEN_ACCOUNT);
        $addressCreds = new SorobanAddressCredentials($address, 1, 100, XdrSCVal::forVoid());

        $this->assertFalse(SorobanCredentials::forSourceAccount()->isAddressBased());
        $this->assertTrue(SorobanCredentials::forAddressCredentials($addressCreds)->isAddressBased());
        $this->assertTrue(SorobanCredentials::forAddressCredentialsV2($addressCreds)->isAddressBased());

        $withDel = new SorobanAddressCredentialsWithDelegates($addressCreds, []);
        $this->assertTrue(SorobanCredentials::forAddressWithDelegates($withDel)->isAddressBased());
    }

    /**
     * getAddressWithDelegates / setAddressWithDelegates round-trip.
     */
    public function testGetSetAddressWithDelegates(): void
    {
        $address      = Address::fromAccountId(self::GOLDEN_ACCOUNT);
        $addressCreds = new SorobanAddressCredentials($address, 1, 100, XdrSCVal::forVoid());
        $withDel      = new SorobanAddressCredentialsWithDelegates($addressCreds, []);

        $creds = SorobanCredentials::forSourceAccount();
        $this->assertNull($creds->getAddressWithDelegates());

        $creds->setAddressWithDelegates($withDel);
        $this->assertSame($withDel, $creds->getAddressWithDelegates());

        $creds->setAddressWithDelegates(null);
        $this->assertNull($creds->getAddressWithDelegates());
    }

    /**
     * getCredentialType / setCredentialType round-trip.
     */
    public function testGetSetCredentialType(): void
    {
        $creds = SorobanCredentials::forSourceAccount();
        $this->assertSame(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT, $creds->getCredentialType());

        $creds->setCredentialType(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2);
        $this->assertSame(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2, $creds->getCredentialType());
    }

    /**
     * writeBackAddressCredentials no-ops for SOURCE_ACCOUNT credentials.
     */
    public function testWriteBackAddressCredentialsNoOpForSourceAccount(): void
    {
        $creds = SorobanCredentials::forSourceAccount();
        $address      = Address::fromAccountId(self::GOLDEN_ACCOUNT);
        $addressCreds = new SorobanAddressCredentials($address, 99, 500, XdrSCVal::forVoid());

        // Must not throw; addressCredentials stays null.
        $creds->writeBackAddressCredentials($addressCreds);
        $this->assertNull($creds->addressCredentials);
    }

    /**
     * Backward-compatible constructor: passing SorobanAddressCredentials as first arg
     * sets ADDRESS arm automatically.
     */
    public function testBackwardCompatibleConstructorAcceptsAddressCredentials(): void
    {
        $address      = Address::fromAccountId(self::GOLDEN_ACCOUNT);
        $addressCreds = new SorobanAddressCredentials($address, 7, 77, XdrSCVal::forVoid());

        $creds = new SorobanCredentials($addressCreds);

        $this->assertSame(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS, $creds->credentialType);
        $this->assertSame($addressCreds, $creds->addressCredentials);
    }

    // =========================================================================
    // SorobanAuthorizationEntry — error-guard paths
    // =========================================================================

    /**
     * parseAddressStrkey rejects an invalid (non-G, non-C) strkey.
     *
     * Exercise via withDelegates(), which calls buildDelegateNode() -> parseAddressStrkey().
     */
    public function testWithDelegatesRejectsInvalidDelegateStrkey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/G- or C-prefixed strkey/');

        $address = Address::fromAccountId(self::GOLDEN_ACCOUNT);
        $creds   = SorobanCredentials::forAddress($address, 1, 100, XdrSCVal::forVoid());
        $entry   = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        SorobanAuthorizationEntry::withDelegates($entry, 100, [
            new SorobanDelegateDescriptor('NOT_A_VALID_STRKEY'),
        ]);
    }

    /**
     * buildDelegateNode depth limit is hit when withDelegates is called with a descriptor tree
     * deeper than 128 levels. The descriptors themselves are nested; each buildDelegateNode
     * call increments depth by 1 when recursing into nestedDelegates.
     */
    public function testWithDelegatesRejectsDescriptorTreeDeeperThanLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/depth limit/i');

        $address = Address::fromAccountId(self::GOLDEN_ACCOUNT);
        $creds   = SorobanCredentials::forAddress($address, 1, 100, XdrSCVal::forVoid());
        $entry   = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        // Build a descriptor nested 130 levels deep.
        $delegateKp = KeyPair::random();
        $inner      = new SorobanDelegateDescriptor($delegateKp->getAccountId());
        for ($i = 0; $i < 130; $i++) {
            $inner = new SorobanDelegateDescriptor($delegateKp->getAccountId(), null, [$inner]);
        }

        SorobanAuthorizationEntry::withDelegates($entry, 100, [$inner]);
    }

    /**
     * appendSignatureToMatchingNodes depth-limit: sign(forAddress=...) on a live entry
     * whose delegate tree is artificially set beyond 128 levels raises InvalidArgumentException.
     *
     * We exercise this by calling forAddress on an entry built with a normal (shallow) tree and
     * then directly calling the public sign() method, which internally calls
     * appendSignatureToMatchingNodes. Because the depth limit is on the traversal rather
     * than on construction, we must build an entry whose delegate tree is deeply nested at
     * construction time. We use buildDeepDelegateXdr to round-trip decode, then sign().
     *
     * Note: this exercises the depth-limit guard on the SIGNING traversal
     * (appendSignatureToDelegateNode), which is separate from the XDR decode guard.
     */
    public function testSignForAddressOnDeepDelegateSdkTreeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/depth limit/i');

        // Build a 130-deep nested delegate tree via wrapper objects directly
        // (skipping withDelegates depth guard by using SorobanDelegateSignature directly).
        $topKp      = KeyPair::fromSeed(self::TEST_SECRET);
        $delegateKp = KeyPair::random();

        $topAddress   = Address::fromAccountId($topKp->getAccountId());
        $topCreds     = new SorobanAddressCredentials($topAddress, 1, 100, XdrSCVal::forVoid());
        $delegateAddr = XdrSCAddress::forAccountId($delegateKp->getAccountId());

        // Build a chain 130 levels deep.
        $node = new SorobanDelegateSignature($delegateAddr, XdrSCVal::forVoid(), []);
        for ($i = 0; $i < 130; $i++) {
            $node = new SorobanDelegateSignature($delegateAddr, XdrSCVal::forVoid(), [$node]);
        }

        $withDelegates = new SorobanAddressCredentialsWithDelegates($topCreds, [$node]);
        $creds         = SorobanCredentials::forAddressWithDelegates($withDelegates);
        $entry         = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        // sign(forAddress=delegate) triggers appendSignatureToMatchingNodes/appendSignatureToDelegateNode
        // which hits the depth limit.
        $entry->sign($delegateKp, Network::testnet(), null, $delegateKp->getAccountId());
    }

    /**
     * forAddress routing: when the top-level node has an EXISTING non-void signature vec,
     * the new signature is appended (not replaced).
     *
     * This exercises the `$addressCreds->signature->vec !== null` branch inside
     * appendSignatureToMatchingNodes when matching the top-level address.
     */
    public function testForAddressAppendsToExistingTopLevelVec(): void
    {
        $topKp   = KeyPair::fromSeed(self::TEST_SECRET);
        $signer2 = KeyPair::random();

        $topAddress   = Address::fromAccountId($topKp->getAccountId());
        $addressCreds = new SorobanAddressCredentials($topAddress, self::GOLDEN_NONCE, self::GOLDEN_EXPIRY, XdrSCVal::forVoid());
        $creds        = SorobanCredentials::forAddressCredentialsV2($addressCreds);
        $entry        = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        // First sign: void -> one-element vec.
        $entry->sign($topKp, Network::testnet(), null, $topKp->getAccountId());

        // Second sign with a different key via forAddress: should APPEND to the existing vec.
        $entry->sign($signer2, Network::testnet(), null, $topKp->getAccountId());

        $sig = $entry->credentials->addressCredentials?->signature;
        $this->assertNotNull($sig?->vec);
        $this->assertCount(2, $sig->vec, 'Second forAddress sign must append to existing vec');
    }

    /**
     * appendSignatureToDelegateNode: when a delegate node has an existing non-void signature,
     * sign(forAddress=delegate) appends to it rather than replacing.
     */
    public function testForAddressAppendsToExistingDelegateVec(): void
    {
        $topKp        = KeyPair::random();
        $delegateKp   = KeyPair::fromSeed(self::TEST_SECRET);
        $delegateKp2  = KeyPair::random();

        $topAddress      = Address::fromAccountId($topKp->getAccountId());
        $delegateAddress = Address::fromAccountId($delegateKp->getAccountId());
        $topCreds        = new SorobanAddressCredentials($topAddress, self::GOLDEN_NONCE, self::GOLDEN_EXPIRY, XdrSCVal::forVoid());
        $delegateXdrAddr = XdrSCAddress::forAccountId($delegateKp->getAccountId());
        $delegateNode    = new SorobanDelegateSignature($delegateXdrAddr, XdrSCVal::forVoid(), []);

        $withDelegates = new SorobanAddressCredentialsWithDelegates($topCreds, [$delegateNode]);
        $creds         = SorobanCredentials::forAddressWithDelegates($withDelegates);
        $entry         = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        // First sign: void -> one-element vec on delegate.
        $entry->sign($delegateKp, Network::testnet(), null, $delegateKp->getAccountId());

        // Second sign: non-void vec -> append.
        $entry->sign($delegateKp2, Network::testnet(), null, $delegateKp->getAccountId());

        $delegateResult = $entry->credentials->addressWithDelegates?->delegates[0];
        $this->assertNotNull($delegateResult?->signature->vec);
        $this->assertCount(2, $delegateResult->signature->vec, 'forAddress must append to delegate vec');
    }

    /**
     * sign(forAddress=) on a nested delegate (depth > 1): the signature lands in the nested node.
     *
     * This exercises appendSignatureToDelegateNode -> recursion into nestedDelegates.
     */
    public function testForAddressSignsNestedDelegateNode(): void
    {
        $topKp         = KeyPair::random();
        $outerDelegate = KeyPair::random();
        $innerDelegate = KeyPair::fromSeed(self::TEST_SECRET);

        $topAddress   = Address::fromAccountId($topKp->getAccountId());
        $topCreds     = new SorobanAddressCredentials($topAddress, self::GOLDEN_NONCE, self::GOLDEN_EXPIRY, XdrSCVal::forVoid());
        $outerAddr    = XdrSCAddress::forAccountId($outerDelegate->getAccountId());
        $innerAddr    = XdrSCAddress::forAccountId($innerDelegate->getAccountId());
        $innerNode    = new SorobanDelegateSignature($innerAddr, XdrSCVal::forVoid(), []);
        $outerNode    = new SorobanDelegateSignature($outerAddr, XdrSCVal::forVoid(), [$innerNode]);

        $withDelegates = new SorobanAddressCredentialsWithDelegates($topCreds, [$outerNode]);
        $creds         = SorobanCredentials::forAddressWithDelegates($withDelegates);
        $entry         = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        // Sign the INNER delegate (depth 2).
        $entry->sign($innerDelegate, Network::testnet(), null, $innerDelegate->getAccountId());

        // Outer delegate must remain unsigned.
        $outer = $entry->credentials->addressWithDelegates?->delegates[0];
        $this->assertNull($outer?->signature->vec, 'Outer delegate must remain void');

        // Inner delegate must have a signature.
        $inner = $outer?->nestedDelegates[0];
        $this->assertNotNull($inner?->signature->vec, 'Inner (nested) delegate must be signed');
        $this->assertCount(1, $inner->signature->vec);
    }

    /**
     * Deep (3-level) delegate-tree XDR round-trip.
     *
     * Generated tests use empty nestedDelegates arrays. This test verifies that a tree
     * with real nesting encodes and decodes exactly at the entry level.
     */
    public function testDeepDelegateTreeEntryXdrRoundTrip(): void
    {
        $topKp     = KeyPair::random();
        $level1Kp  = KeyPair::random();
        $level2Kp  = KeyPair::random();
        $level3Kp  = KeyPair::random();

        $topAddress = Address::fromAccountId($topKp->getAccountId());
        $topCreds   = new SorobanAddressCredentials($topAddress, self::GOLDEN_NONCE, self::GOLDEN_EXPIRY, XdrSCVal::forVoid());

        // Build 3-level tree.
        $level3Addr = XdrSCAddress::forAccountId($level3Kp->getAccountId());
        $level3Node = new SorobanDelegateSignature($level3Addr, XdrSCVal::forVoid(), []);

        $level2Addr = XdrSCAddress::forAccountId($level2Kp->getAccountId());
        $level2Node = new SorobanDelegateSignature($level2Addr, XdrSCVal::forVoid(), [$level3Node]);

        $level1Addr = XdrSCAddress::forAccountId($level1Kp->getAccountId());
        $level1Node = new SorobanDelegateSignature($level1Addr, XdrSCVal::forVoid(), [$level2Node]);

        $withDelegates = new SorobanAddressCredentialsWithDelegates($topCreds, [$level1Node]);
        $creds         = SorobanCredentials::forAddressWithDelegates($withDelegates);
        $entry         = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        // Round-trip at the entry level (not just credentials).
        $b64     = $entry->toBase64Xdr();
        $decoded = SorobanAuthorizationEntry::fromBase64Xdr($b64);

        $this->assertSame(
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES,
            $decoded->credentials->credentialType,
        );

        $delegateL1 = $decoded->credentials->addressWithDelegates?->delegates[0];
        $this->assertNotNull($delegateL1);
        $this->assertCount(1, $delegateL1->nestedDelegates, 'Level-1 delegate must have 1 nested child');

        $delegateL2 = $delegateL1->nestedDelegates[0];
        $this->assertCount(1, $delegateL2->nestedDelegates, 'Level-2 delegate must have 1 nested child');

        $delegateL3 = $delegateL2->nestedDelegates[0];
        $this->assertEmpty($delegateL3->nestedDelegates, 'Level-3 delegate must be a leaf');

        // Byte-identity.
        $this->assertSame($b64, $decoded->toBase64Xdr(), '3-level delegate tree must round-trip byte-exactly');
    }

    // =========================================================================
    // SorobanDelegateSignature — getters / setters
    // =========================================================================

    public function testSorobanDelegateSignatureGettersSetters(): void
    {
        $addr   = XdrSCAddress::forAccountId(self::GOLDEN_ACCOUNT);
        $sig    = XdrSCVal::forVoid();
        $nested = [];

        $node = new SorobanDelegateSignature($addr, $sig, $nested);

        // Getters.
        $this->assertSame($addr, $node->getAddress());
        $this->assertSame($sig, $node->getSignature());
        $this->assertSame($nested, $node->getNestedDelegates());

        // Setters.
        $addr2 = XdrSCAddress::forAccountId(self::GOLDEN_ACCOUNT);
        $node->setAddress($addr2);
        $this->assertSame($addr2, $node->getAddress());

        $sig2 = XdrSCVal::forBool(true);
        $node->setSignature($sig2);
        $this->assertSame($sig2, $node->getSignature());

        $innerAddr = XdrSCAddress::forAccountId(self::GOLDEN_ACCOUNT);
        $innerNode = new SorobanDelegateSignature($innerAddr, XdrSCVal::forVoid(), []);
        $node->setNestedDelegates([$innerNode]);
        $this->assertCount(1, $node->getNestedDelegates());
    }

    // =========================================================================
    // SorobanAddressCredentialsWithDelegates — getters / setters
    // =========================================================================

    public function testSorobanAddressCredentialsWithDelegatesGettersSetters(): void
    {
        $address   = Address::fromAccountId(self::GOLDEN_ACCOUNT);
        $addrCreds = new SorobanAddressCredentials($address, 1, 100, XdrSCVal::forVoid());
        $withDel   = new SorobanAddressCredentialsWithDelegates($addrCreds, []);

        // getAddressCredentials.
        $this->assertSame($addrCreds, $withDel->getAddressCredentials());

        // setAddressCredentials.
        $addrCreds2 = new SorobanAddressCredentials($address, 2, 200, XdrSCVal::forVoid());
        $withDel->setAddressCredentials($addrCreds2);
        $this->assertSame($addrCreds2, $withDel->getAddressCredentials());

        // getDelegates / setDelegates.
        $this->assertEmpty($withDel->getDelegates());

        $delegateAddr = XdrSCAddress::forAccountId(self::GOLDEN_ACCOUNT);
        $delegateNode = new SorobanDelegateSignature($delegateAddr, XdrSCVal::forVoid(), []);
        $withDel->setDelegates([$delegateNode]);
        $this->assertCount(1, $withDel->getDelegates());
    }

    // =========================================================================
    // XdrBuffer — getRecursionDepth
    // =========================================================================

    /**
     * XdrBuffer.getRecursionDepth returns 0 initially and increments with enterRecursion.
     */
    public function testXdrBufferGetRecursionDepth(): void
    {
        $buf = new XdrBuffer(str_repeat("\x00", 64));

        $this->assertSame(0, $buf->getRecursionDepth());

        $buf->enterRecursion();
        $this->assertSame(1, $buf->getRecursionDepth());

        $buf->leaveRecursion();
        $this->assertSame(0, $buf->getRecursionDepth());
    }

    /**
     * leaveRecursion is a no-op when depth is already 0 (does not go negative).
     */
    public function testXdrBufferLeaveRecursionNoOpAtZero(): void
    {
        $buf = new XdrBuffer(str_repeat("\x00", 32));

        // Already at 0, leaving must not throw and must stay at 0.
        $buf->leaveRecursion();
        $this->assertSame(0, $buf->getRecursionDepth());
    }

    // =========================================================================
    // MethodOptions — authV2 constructor (covers lines 39-50)
    // =========================================================================

    /**
     * MethodOptions authV2 property defaults to false and can be set via constructor.
     */
    public function testMethodOptionsAuthV2PropertyDefault(): void
    {
        $default = new MethodOptions();
        $this->assertFalse($default->authV2);

        $enabled = new MethodOptions(authV2: true);
        $this->assertTrue($enabled->authV2);
    }

    // =========================================================================
    // AssembledTransaction — getBlockingNonInvokerSigners and helpers
    // =========================================================================

    /**
     * getBlockingNonInvokerSigners returns the top-level G-address for an unsigned ADDRESS entry.
     */
    public function testGetBlockingNonInvokerSignersReportsLegacyAddressEntry(): void
    {
        $topKp    = KeyPair::random();
        $address  = Address::fromAccountId($topKp->getAccountId());
        $addrCreds = new SorobanAddressCredentials($address, 1, 100, XdrSCVal::forVoid());
        $creds     = SorobanCredentials::forAddressCredentials($addrCreds);
        $entry     = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        $tx = $this->buildAssembledTransactionWithAuthEntries([$entry], KeyPair::fromSeed(self::TEST_SECRET));

        // Directly call getBlockingNonInvokerSigners via sign(force:true) which invokes it.
        // We verify the behavior by observing the Exception thrown when blockers exist.
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/multiple signers/');

        $tx->sign(force: true);
    }

    /**
     * getBlockingNonInvokerSigners: SOURCE_ACCOUNT entry is silently skipped (no blocking).
     */
    public function testGetBlockingNonInvokerSignersSkipsSourceAccountEntry(): void
    {
        $sourceEntry = new SorobanAuthorizationEntry(
            SorobanCredentials::forSourceAccount(),
            $this->makeInvocation(),
        );

        $tx = $this->buildAssembledTransactionWithAuthEntries([$sourceEntry], KeyPair::fromSeed(self::TEST_SECRET));

        // No blocking signers → sign() should succeed (no Exception).
        try {
            $tx->sign(force: true);
        } catch (Exception $e) {
            $this->fail('SOURCE_ACCOUNT entry must not block sign(): ' . $e->getMessage());
        }

        $this->assertNotNull($tx->signed);
    }

    /**
     * WITH_DELEGATES entry where all delegates are signed and top-level is void:
     * getBlockingNonInvokerSigners must treat this as the "delegates-only" pattern
     * and NOT block submission.
     */
    public function testGetBlockingNonInvokerSignersAllowsDelegatesOnlyPattern(): void
    {
        $topKp      = KeyPair::random();
        $delegateKp = KeyPair::random();

        $topAddress  = Address::fromAccountId($topKp->getAccountId());
        $topCreds    = new SorobanAddressCredentials($topAddress, 1, 100, XdrSCVal::forVoid());

        // Delegate is SIGNED (non-void).
        $delegateAddr = XdrSCAddress::forAccountId($delegateKp->getAccountId());
        $delegate     = new SorobanDelegateSignature(
            $delegateAddr,
            XdrSCVal::forVec([XdrSCVal::forVoid()]), // non-void => signed
            [],
        );

        $withDelegates = new SorobanAddressCredentialsWithDelegates($topCreds, [$delegate]);
        $creds         = SorobanCredentials::forAddressWithDelegates($withDelegates);
        $entry         = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        $tx = $this->buildAssembledTransactionWithAuthEntries([$entry], KeyPair::fromSeed(self::TEST_SECRET));

        // No blocking signers → sign() must succeed.
        try {
            $tx->sign(force: true);
        } catch (Exception $e) {
            $this->fail('Delegates-only pattern must not block sign(): ' . $e->getMessage());
        }

        $this->assertNotNull($tx->signed);
    }

    /**
     * WITH_DELEGATES entry where top-level is void AND a delegate is also unsigned:
     * getBlockingNonInvokerSigners must report both addresses.
     */
    public function testGetBlockingNonInvokerSignersWithDelegatesBlocksWhenDelegateUnsigned(): void
    {
        $topKp      = KeyPair::random();
        $delegateKp = KeyPair::random();

        $topAddress  = Address::fromAccountId($topKp->getAccountId());
        $topCreds    = new SorobanAddressCredentials($topAddress, 1, 100, XdrSCVal::forVoid());

        // Delegate is UNSIGNED (void).
        $delegateAddr = XdrSCAddress::forAccountId($delegateKp->getAccountId());
        $delegate     = new SorobanDelegateSignature($delegateAddr, XdrSCVal::forVoid(), []);

        $withDelegates = new SorobanAddressCredentialsWithDelegates($topCreds, [$delegate]);
        $creds         = SorobanCredentials::forAddressWithDelegates($withDelegates);
        $entry         = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        $tx = $this->buildAssembledTransactionWithAuthEntries([$entry], KeyPair::fromSeed(self::TEST_SECRET));

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/multiple signers/');

        $tx->sign(force: true);
    }

    /**
     * needsNonInvokerSigningBy with includeAlreadySigned=false skips a signed ADDRESS entry.
     *
     * Verifies the $isVoid=false / !$includeAlreadySigned branch in needsNonInvokerSigningBy.
     */
    public function testNeedsNonInvokerSigningBySkipsSignedAddressEntry(): void
    {
        $signerKp     = KeyPair::random();
        $address      = Address::fromAccountId($signerKp->getAccountId());
        $addressCreds = new SorobanAddressCredentials($address, 1, 100, XdrSCVal::forVec([XdrSCVal::forVoid()]));
        $creds        = SorobanCredentials::forAddressCredentials($addressCreds);
        $entry        = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        $tx = $this->buildAssembledTransactionWithAuthEntries([$entry], KeyPair::fromSeed(self::TEST_SECRET));

        $needed = $tx->needsNonInvokerSigningBy(includeAlreadySigned: false);
        $this->assertNotContains($signerKp->getAccountId(), $needed, 'Signed ADDRESS entry must not appear by default');
    }

    /**
     * needsNonInvokerSigningBy: a delegate with nested delegates reports the nested unsigned ones
     * (exercises collectUnsignedDelegateAddresses recursion).
     */
    public function testNeedsNonInvokerSigningByReportsNestedUnsignedDelegate(): void
    {
        $topKp       = KeyPair::random();
        $outerKp     = KeyPair::random();
        $innerKp     = KeyPair::random();

        $topAddress   = Address::fromAccountId($topKp->getAccountId());
        $topCreds     = new SorobanAddressCredentials($topAddress, 1, 100, XdrSCVal::forVoid());

        $innerAddr = XdrSCAddress::forAccountId($innerKp->getAccountId());
        $innerNode = new SorobanDelegateSignature($innerAddr, XdrSCVal::forVoid(), []);

        $outerAddr = XdrSCAddress::forAccountId($outerKp->getAccountId());
        $outerNode = new SorobanDelegateSignature($outerAddr, XdrSCVal::forVoid(), [$innerNode]);

        $withDelegates = new SorobanAddressCredentialsWithDelegates($topCreds, [$outerNode]);
        $creds         = SorobanCredentials::forAddressWithDelegates($withDelegates);
        $entry         = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        $tx = $this->buildAssembledTransactionWithAuthEntries([$entry], KeyPair::fromSeed(self::TEST_SECRET));
        $needed = $tx->needsNonInvokerSigningBy();

        $this->assertContains($topKp->getAccountId(), $needed);
        $this->assertContains($outerKp->getAccountId(), $needed);
        $this->assertContains($innerKp->getAccountId(), $needed);
    }

    /**
     * signAuthEntries: when the signer address matches both the top-level AND a delegate node
     * (same address at different levels — allowed by the spec), the signature is written to
     * both nodes.
     *
     * Exercises delegateTreeContainsAddress finding a match, plus the signAuthEntries loop
     * setting both $entryMatchesTopLevel and $entryMatchesDelegate.
     */
    public function testSignAuthEntriesSignsBothTopLevelAndDelegateWhenAddressRepeated(): void
    {
        $sharedKp = KeyPair::fromSeed(self::TEST_SECRET);

        $sharedAddress = Address::fromAccountId($sharedKp->getAccountId());
        $topCreds      = new SorobanAddressCredentials($sharedAddress, 1, 100, XdrSCVal::forVoid());

        // Delegate uses the SAME address as the top-level.
        $sharedXdrAddr = XdrSCAddress::forAccountId($sharedKp->getAccountId());
        $delegateNode  = new SorobanDelegateSignature($sharedXdrAddr, XdrSCVal::forVoid(), []);

        $withDelegates = new SorobanAddressCredentialsWithDelegates($topCreds, [$delegateNode]);
        $creds         = SorobanCredentials::forAddressWithDelegates($withDelegates);
        $entry         = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        $invokerKp = KeyPair::fromSeed(self::TEST_SECRET); // same key, different role
        $tx        = $this->buildAssembledTransactionWithAuthEntries([$entry], $invokerKp);
        $this->injectMockedServerResponses($tx, [$this->makeLatestLedgerResponse(1000)]);

        $tx->signAuthEntries(signerKeyPair: $sharedKp, validUntilLedgerSeq: 9999);

        $ops  = $tx->tx?->getOperations();
        $op   = $ops[0] ?? null;
        $this->assertInstanceOf(InvokeHostFunctionOperation::class, $op);
        $auth = $op->auth;
        $this->assertCount(1, $auth);

        $result = $auth[0]->credentials->addressWithDelegates;
        $this->assertNotNull($result);

        // Top-level must be signed.
        $this->assertNotNull($result->addressCredentials->signature->vec,
            'Top-level signature must be written when address matches both top-level and delegate');

        // Delegate must also be signed.
        $this->assertNotNull($result->delegates[0]->signature->vec,
            'Delegate signature must be written when address matches both top-level and delegate');

        // The requested expiration must have been stamped before signing.
        $this->assertSame(9999, $result->addressCredentials->signatureExpirationLedger,
            'signAuthEntries must stamp the requested expiration before signing');
    }

    /**
     * Callback-based signing in signAuthEntries stamps the expiration on the
     * entry BEFORE handing it to the callback, so the callback signs over the
     * intended expiration ledger rather than the entry's original value.
     */
    public function testSignAuthEntriesStampsExpirationBeforeCallback(): void
    {
        $signerKp = KeyPair::fromSeed(self::TEST_SECRET);
        $invokerKp = KeyPair::fromSeed(self::TEST_SECRET);

        // Entry starts with expiration 100; signAuthEntries is called with 9999.
        $address      = Address::fromAccountId($signerKp->getAccountId());
        $addressCreds = new SorobanAddressCredentials($address, 1, 100, XdrSCVal::forVoid());
        $validEntry   = new SorobanAuthorizationEntry(
            SorobanCredentials::forAddressCredentials($addressCreds),
            $this->makeInvocation(),
        );

        $tx = $this->buildAssembledTransactionWithAuthEntries([$validEntry], $invokerKp);
        $this->injectMockedServerResponses($tx, [$this->makeLatestLedgerResponse(1000)]);

        $callbackInvoked = false;
        $expirationSeenByCallback = null;
        $tx->signAuthEntries(
            signerKeyPair: $signerKp,
            // The callback signs WITHOUT setting an expiration itself, so the
            // signature is made over whatever the SDK stamped beforehand.
            authorizeEntryCallback: static function (SorobanAuthorizationEntry $e, Network $n) use (&$callbackInvoked, &$expirationSeenByCallback, $signerKp): SorobanAuthorizationEntry {
                $callbackInvoked = true;
                $expirationSeenByCallback =
                    $e->credentials->getAddressCredentials()?->signatureExpirationLedger;
                $e->sign($signerKp, $n);
                return $e;
            },
            validUntilLedgerSeq: 9999,
        );

        $this->assertTrue($callbackInvoked, 'Authorize entry callback must be invoked for matching entries');
        // If the stamp were skipped on the callback path, the callback would see
        // (and sign over) the original 100 and this would fail.
        $this->assertEquals(9999, $expirationSeenByCallback,
            'signAuthEntries must stamp the expiration before invoking the callback');

        $signedEntry = $tx->tx?->getOperations()[0]->auth[0];
        $this->assertEquals(9999,
            $signedEntry->credentials->getAddressCredentials()->signatureExpirationLedger,
            'the signed entry must carry the stamped expiration');
        $this->assertNotNull(
            $signedEntry->credentials->getAddressCredentials()->signature->vec,
            'the callback-signed entry must carry a signature');
    }

    // =========================================================================
    // Helpers (replicated from P27AssembledTransactionTest for isolation)
    // =========================================================================

    private function makeInvocation(): SorobanAuthorizedInvocation
    {
        $contractAddress = Address::fromContractId(StrKey::decodeContractIdHex(self::TEST_CONTRACT));
        $fn = SorobanAuthorizedFunction::forContractFunction($contractAddress, 'test', []);
        return new SorobanAuthorizedInvocation($fn, []);
    }

    /**
     * @param array<SorobanAuthorizationEntry> $entries
     */
    private function buildAssembledTransactionWithAuthEntries(
        array   $entries,
        KeyPair $invokerKp,
    ): AssembledTransaction {
        $clientOptions = new ClientOptions(
            sourceAccountKeyPair: $invokerKp,
            contractId:           self::TEST_CONTRACT,
            network:              Network::testnet(),
            rpcUrl:               self::TEST_RPC_URL,
        );
        $methodOptions = new MethodOptions(simulate: false, restore: false);
        $txOptions     = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method:        'test',
            arguments:     [],
        );

        $reflection = new \ReflectionClass(AssembledTransaction::class);
        $tx         = $reflection->newInstanceWithoutConstructor();

        $optionsProp = $reflection->getProperty('options');
        $optionsProp->setAccessible(true);
        $optionsProp->setValue($tx, $txOptions);

        $server     = new SorobanServer($txOptions->clientOptions->rpcUrl);
        $serverProp = $reflection->getProperty('server');
        $serverProp->setAccessible(true);
        $serverProp->setValue($tx, $server);

        $account   = new Account($invokerKp->getAccountId(), new BigInteger(123456789));
        $hostFn    = new InvokeContractHostFunction(self::TEST_CONTRACT, 'test', []);
        $op        = (new InvokeHostFunctionOperationBuilder($hostFn))->build();
        $txBuilder = new TransactionBuilder(sourceAccount: $account);
        $txBuilder->addOperation($op);
        $built     = $txBuilder->build();
        $built->setSorobanAuth($entries);

        $footprint = new XdrLedgerFootprint([], []);
        $resources = new XdrSorobanResources($footprint, 100, 100, 100);
        $ext       = new XdrSorobanTransactionDataExt(0);
        $txData    = new XdrSorobanTransactionData($ext, $resources, 100);
        $built->setSorobanTransactionData($txData);

        $txProp = $reflection->getProperty('tx');
        $txProp->setAccessible(true);
        $txProp->setValue($tx, $built);

        $simResponse = new SimulateTransactionResponse([]);
        $simResponse->transactionData = $txData;
        $simResponse->minResourceFee  = 100;
        $simResponse->latestLedger    = 1000;
        $tx->simulationResponse = $simResponse;

        $simResultProp = $reflection->getProperty('simulationResult');
        $simResultProp->setAccessible(true);
        $simResultProp->setValue($tx, new SimulateHostFunctionResult($txData, XdrSCVal::forVoid(), $entries));

        return $tx;
    }

    /**
     * @param array<Response> $responses
     */
    private function injectMockedServerResponses(AssembledTransaction $tx, array $responses): void
    {
        $mock    = new MockHandler($responses);
        $stack   = HandlerStack::create($mock);
        $client  = new Client(['handler' => $stack]);

        $reflection = new \ReflectionClass($tx);
        $serverProp = $reflection->getProperty('server');
        $serverProp->setAccessible(true);
        $server = $serverProp->getValue($tx);

        $serverReflection = new \ReflectionClass($server);
        $httpClientProp   = $serverReflection->getProperty('httpClient');
        $httpClientProp->setAccessible(true);
        $httpClientProp->setValue($server, $client);
    }

    private function makeLatestLedgerResponse(int $sequence): Response
    {
        return new Response(200, [], json_encode([
            'jsonrpc' => '2.0',
            'id'      => 1,
            'result'  => [
                'id'       => 'abc123',
                'sequence' => $sequence,
                'hash'     => str_repeat('a', 64),
            ],
        ]));
    }

    // =========================================================================
    // Additional coverage: AssembledTransaction signAuthEntries SOURCE_ACCOUNT skip,
    // delegateTreeContainsAddress, allDelegateNodesSigned, needsNonInvokerSigningBy
    // SOURCE_ACCOUNT skip, and collectUnsignedDelegateGAddresses
    // =========================================================================

    /**
     * signAuthEntries with a mix of a SOURCE_ACCOUNT entry and a valid ADDRESS entry:
     * the SOURCE_ACCOUNT entry is silently skipped (continue at line 562) and only the
     * ADDRESS entry matching the signer is processed.
     */
    public function testSignAuthEntriesSkipsSourceAccountEntry(): void
    {
        $signerKp = KeyPair::fromSeed(self::TEST_SECRET);

        $address      = Address::fromAccountId($signerKp->getAccountId());
        $addressCreds = new SorobanAddressCredentials($address, 1, 100, XdrSCVal::forVoid());
        $validEntry   = new SorobanAuthorizationEntry(
            SorobanCredentials::forAddressCredentials($addressCreds),
            $this->makeInvocation(),
        );
        $sourceEntry = new SorobanAuthorizationEntry(
            SorobanCredentials::forSourceAccount(),
            $this->makeInvocation(),
        );

        // Put the SOURCE_ACCOUNT entry FIRST so the continue is reached before the valid entry.
        $tx = $this->buildAssembledTransactionWithAuthEntries([$sourceEntry, $validEntry], $signerKp);
        $this->injectMockedServerResponses($tx, [$this->makeLatestLedgerResponse(1000)]);

        // Must not throw; the valid entry is signed and the source-account entry is skipped.
        $tx->signAuthEntries(signerKeyPair: $signerKp, validUntilLedgerSeq: 9999);

        $ops  = $tx->tx?->getOperations();
        $op   = $ops[0] ?? null;
        $this->assertInstanceOf(InvokeHostFunctionOperation::class, $op);
        $auth = $op->auth;
        $this->assertCount(2, $auth);

        // Source-account entry (index 0) must remain unchanged.
        $this->assertSame(
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT,
            $auth[0]->credentials->credentialType,
        );

        // ADDRESS entry (index 1) must be signed.
        $sig = $auth[1]->credentials->addressCredentials?->signature;
        $this->assertNotNull($sig?->vec);
        $this->assertCount(1, $sig->vec);
    }

    /**
     * needsNonInvokerSigningBy skips SOURCE_ACCOUNT entries (line 826 continue).
     */
    public function testNeedsNonInvokerSigningBySkipsSourceAccountEntry(): void
    {
        $sourceEntry = new SorobanAuthorizationEntry(
            SorobanCredentials::forSourceAccount(),
            $this->makeInvocation(),
        );

        $tx = $this->buildAssembledTransactionWithAuthEntries([$sourceEntry], KeyPair::fromSeed(self::TEST_SECRET));
        $needed = $tx->needsNonInvokerSigningBy();

        $this->assertEmpty($needed, 'SOURCE_ACCOUNT entry must not appear in needsNonInvokerSigningBy result');
    }

    /**
     * allDelegateNodesSigned returns false when one delegate is unsigned.
     *
     * Exercises the "return false when delegate has void signature" branch (line 757)
     * inside allDelegateNodesSigned, which is called from getBlockingNonInvokerSigners
     * through sign() force check.
     */
    public function testAllDelegatesSignedReturnsFalseForUnsignedDelegate(): void
    {
        $topKp       = KeyPair::random();
        $delegateKp  = KeyPair::random();

        $topAddress   = Address::fromAccountId($topKp->getAccountId());
        $topCreds     = new SorobanAddressCredentials($topAddress, 1, 100, XdrSCVal::forVoid());

        // Delegate is UNSIGNED (void).
        $delegateAddr = XdrSCAddress::forAccountId($delegateKp->getAccountId());
        $delegate     = new SorobanDelegateSignature($delegateAddr, XdrSCVal::forVoid(), []);

        $withDelegates = new SorobanAddressCredentialsWithDelegates($topCreds, [$delegate]);
        $creds         = SorobanCredentials::forAddressWithDelegates($withDelegates);
        $entry         = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        $tx = $this->buildAssembledTransactionWithAuthEntries([$entry], KeyPair::fromSeed(self::TEST_SECRET));

        // sign() calls getBlockingNonInvokerSigners() which calls allDelegateNodesSigned().
        // Since the delegate is unsigned, allDelegateNodesSigned returns false,
        // getBlockingNonInvokerSigners reports both addresses, and sign() throws.
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/multiple signers/');

        $tx->sign(force: true);
    }

    /**
     * collectUnsignedDelegateGAddresses: a WITH_DELEGATES entry with unsigned top-level AND
     * at least one unsigned delegate causes getBlockingNonInvokerSigners to collect the delegate
     * G-address (exercises lines 778-784 in collectUnsignedDelegateGAddresses).
     */
    public function testCollectUnsignedDelegateGAddressesCollectsAccountAddress(): void
    {
        $topKp       = KeyPair::random();
        $delegateKp  = KeyPair::random();

        $topAddress   = Address::fromAccountId($topKp->getAccountId());
        $topCreds     = new SorobanAddressCredentials($topAddress, 1, 100, XdrSCVal::forVoid());

        $delegateAddr = XdrSCAddress::forAccountId($delegateKp->getAccountId());
        $delegate     = new SorobanDelegateSignature($delegateAddr, XdrSCVal::forVoid(), []);

        $withDelegates = new SorobanAddressCredentialsWithDelegates($topCreds, [$delegate]);
        $creds         = SorobanCredentials::forAddressWithDelegates($withDelegates);
        $entry         = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        $tx = $this->buildAssembledTransactionWithAuthEntries([$entry], KeyPair::fromSeed(self::TEST_SECRET));

        // Confirm the test structure: needsNonInvokerSigningBy includes both.
        $needed = $tx->needsNonInvokerSigningBy();
        $this->assertContains($topKp->getAccountId(), $needed);
        $this->assertContains($delegateKp->getAccountId(), $needed);

        // sign() must block because both top-level and delegate are unsigned.
        $this->expectException(Exception::class);
        $tx->sign(force: true);
    }

    // =========================================================================
    // SorobanAuthorizationEntry — buildPreimage() null-payload guards
    // =========================================================================

    /**
     * buildPreimage() on an ADDRESS entry with null addressCredentials must throw.
     *
     * Constructing SorobanCredentials with the ADDRESS type int but passing null for the
     * credentials payload bypasses the factory invariant and reaches the guard at line 160.
     */
    public function testBuildPreimageAddressArmNullCredentialsThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/ADDRESS arm requires addressCredentials/');

        $creds = new SorobanCredentials(
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS,
            null,
        );
        $entry = new SorobanAuthorizationEntry($creds, $this->makeInvocation());
        $entry->buildPreimage(Network::testnet());
    }

    /**
     * buildPreimage() on an ADDRESS_V2 entry with null addressCredentials must throw.
     *
     * Same mechanism as the ADDRESS guard but for the ADDRESS_V2 arm (line 177).
     */
    public function testBuildPreimageAddressV2ArmNullCredentialsThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/ADDRESS_V2 arm requires addressCredentials/');

        $creds = new SorobanCredentials(
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2,
            null,
        );
        $entry = new SorobanAuthorizationEntry($creds, $this->makeInvocation());
        $entry->buildPreimage(Network::testnet());
    }

    /**
     * buildPreimage() on an ADDRESS_WITH_DELEGATES entry with null addressWithDelegates must throw.
     *
     * Constructing SorobanCredentials with the WITH_DELEGATES type int but passing null for the
     * payload reaches the guard at line 195.
     */
    public function testBuildPreimageAddressWithDelegatesArmNullCredentialsThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/ADDRESS_WITH_DELEGATES arm requires addressWithDelegates/');

        $creds = new SorobanCredentials(
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES,
            null,
            null,
        );
        $entry = new SorobanAuthorizationEntry($creds, $this->makeInvocation());
        $entry->buildPreimage(Network::testnet());
    }

    // =========================================================================
    // AssembledTransaction — allDelegateNodesSigned() nested false path (line 757)
    // =========================================================================

    /**
     * allDelegateNodesSigned() returns false when an OUTER delegate is signed but one of its
     * NESTED delegates is unsigned.
     *
     * The outer delegate carries a non-void signature; the inner (level-2) delegate is void.
     * getBlockingNonInvokerSigners calls allDelegateNodesSigned, which recurses into the outer
     * node's nestedDelegates and returns false (line 757) because the inner node is void.
     * As a result sign() throws because the entry is not fully satisfied.
     */
    public function testAllDelegatesSignedReturnsFalseForUnsignedNestedDelegate(): void
    {
        $topKp        = KeyPair::random();
        $outerKp      = KeyPair::random();
        $innerKp      = KeyPair::random();

        $topAddress  = Address::fromAccountId($topKp->getAccountId());
        $topCreds    = new SorobanAddressCredentials($topAddress, 1, 100, XdrSCVal::forVoid());

        // Inner delegate: UNSIGNED (void).
        $innerAddr = XdrSCAddress::forAccountId($innerKp->getAccountId());
        $innerNode = new SorobanDelegateSignature($innerAddr, XdrSCVal::forVoid(), []);

        // Outer delegate: SIGNED (non-void) but has an unsigned child.
        $outerAddr = XdrSCAddress::forAccountId($outerKp->getAccountId());
        $outerNode = new SorobanDelegateSignature(
            $outerAddr,
            XdrSCVal::forVec([XdrSCVal::forVoid()]), // non-void == "signed"
            [$innerNode],
        );

        $withDelegates = new SorobanAddressCredentialsWithDelegates($topCreds, [$outerNode]);
        $creds         = SorobanCredentials::forAddressWithDelegates($withDelegates);
        $entry         = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        $invokerKp = KeyPair::fromSeed(self::TEST_SECRET);
        $tx        = $this->buildAssembledTransactionWithAuthEntries([$entry], $invokerKp);

        // sign(force:true) calls getBlockingNonInvokerSigners() -> allDelegateNodesSigned().
        // allDelegateNodesSigned sees outer is signed, recurses into nestedDelegates, finds
        // the inner node is void, and returns false (line 757).
        // The entry is therefore still blocking, and sign() throws.
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/multiple signers/');

        $tx->sign(force: true);
    }

    // =========================================================================
    // AssembledTransaction — delegateTreeContainsAddress() returns false (line 657)
    // =========================================================================

    /**
     * delegateTreeContainsAddress() exhausts all nodes without finding a match and returns
     * false (line 657), causing signAuthEntries to skip that entry entirely.
     *
     * Two auth entries are present:
     * - Entry 0: a WITH_DELEGATES entry whose top-level and delegate addresses are both
     *   different from the signer.
     * - Entry 1: a plain ADDRESS entry for the signer, so the signer passes the
     *   needsNonInvokerSigningBy() pre-check.
     *
     * The loop processes entry 0 (delegateTreeContainsAddress returns false, entry skipped)
     * then entry 1 (signer matches, entry is signed). Entry 0 must remain untouched.
     */
    public function testSignAuthEntriesSkipsEntryWhenSignerAddressNotInTree(): void
    {
        $topKp        = KeyPair::random();
        $delegateKp   = KeyPair::random();
        $signerKp     = KeyPair::fromSeed(self::TEST_SECRET);

        // Entry 0: WITH_DELEGATES — signer is NOT in this tree.
        $topAddress = Address::fromAccountId($topKp->getAccountId());
        $topCreds   = new SorobanAddressCredentials($topAddress, 1, 100, XdrSCVal::forVoid());

        $delegateAddr = XdrSCAddress::forAccountId($delegateKp->getAccountId());
        $delegateNode = new SorobanDelegateSignature($delegateAddr, XdrSCVal::forVoid(), []);

        $withDelegates = new SorobanAddressCredentialsWithDelegates($topCreds, [$delegateNode]);
        $withDelegatesEntry = new SorobanAuthorizationEntry(
            SorobanCredentials::forAddressWithDelegates($withDelegates),
            $this->makeInvocation(),
        );

        // Entry 1: ADDRESS — signer IS the top-level address.
        $signerAddress  = Address::fromAccountId($signerKp->getAccountId());
        $signerCreds    = new SorobanAddressCredentials($signerAddress, 2, 100, XdrSCVal::forVoid());
        $signerEntry    = new SorobanAuthorizationEntry(
            SorobanCredentials::forAddressCredentials($signerCreds),
            $this->makeInvocation(),
        );

        $invokerKp = $signerKp;
        $tx        = $this->buildAssembledTransactionWithAuthEntries([$withDelegatesEntry, $signerEntry], $invokerKp);
        $this->injectMockedServerResponses($tx, [$this->makeLatestLedgerResponse(1000)]);

        // signAuthEntries must complete: entry 0 is skipped (delegateTreeContainsAddress returns
        // false at line 657), entry 1 is signed.
        $tx->signAuthEntries(signerKeyPair: $signerKp, validUntilLedgerSeq: 9999);

        $ops = $tx->tx?->getOperations();
        $op  = $ops[0] ?? null;
        $this->assertInstanceOf(InvokeHostFunctionOperation::class, $op);
        $auth = $op->auth;

        // Entry 0 (WITH_DELEGATES): top-level and delegate must remain void.
        $result0 = $auth[0]->credentials->addressWithDelegates;
        $this->assertNotNull($result0);
        $this->assertNull($result0->addressCredentials->signature->vec,
            'WITH_DELEGATES top-level must remain void when signer is not in the tree');
        $this->assertNull($result0->delegates[0]->signature->vec,
            'WITH_DELEGATES delegate must remain void when signer is not in the tree');

        // Entry 1 (ADDRESS): must be signed.
        $sig1 = $auth[1]->credentials->addressCredentials?->signature;
        $this->assertNotNull($sig1?->vec, 'ADDRESS entry for the signer must be signed');
        $this->assertCount(1, $sig1->vec);
    }

    // =========================================================================
    // AssembledTransaction — line 581: contractId fallback in signAuthEntries
    // =========================================================================

    /**
     * signAuthEntries reads the top-level address strkey using the contractId fallback (line 581)
     * when the top-level credential address is a contract (C-prefixed) address.
     *
     * Two entries are present:
     * - Entry 0: a WITH_DELEGATES entry whose top-level is a C-address (accountId is null, so
     *   the `->accountId ?? ->contractId` null-coalescing at line 580-581 evaluates the
     *   `->contractId` branch). The signer's G-address does not match the hex contract id string.
     * - Entry 1: a plain ADDRESS entry for the G-address signer, so the signer passes the
     *   needsNonInvokerSigningBy() pre-check.
     *
     * Entry 0 must remain untouched (contract address cannot match a G-address signer).
     */
    public function testSignAuthEntriesUsesContractIdFallbackForContractTopLevelAddress(): void
    {
        $contractHex = StrKey::decodeContractIdHex(self::TEST_CONTRACT);
        $signerKp    = KeyPair::fromSeed(self::TEST_SECRET);

        // Entry 0: top-level is a CONTRACT address (accountId is null → contractId branch executes).
        $contractAddress = Address::fromContractId($contractHex);
        $topCreds        = new SorobanAddressCredentials($contractAddress, 1, 100, XdrSCVal::forVoid());

        $delegateKp   = KeyPair::random();
        $delegateAddr = XdrSCAddress::forAccountId($delegateKp->getAccountId());
        $delegateNode = new SorobanDelegateSignature($delegateAddr, XdrSCVal::forVoid(), []);

        $withDelegates     = new SorobanAddressCredentialsWithDelegates($topCreds, [$delegateNode]);
        $contractEntry     = new SorobanAuthorizationEntry(
            SorobanCredentials::forAddressWithDelegates($withDelegates),
            $this->makeInvocation(),
        );

        // Entry 1: ADDRESS entry for the G-address signer (satisfies the pre-check).
        $signerAddress = Address::fromAccountId($signerKp->getAccountId());
        $signerCreds   = new SorobanAddressCredentials($signerAddress, 2, 100, XdrSCVal::forVoid());
        $signerEntry   = new SorobanAuthorizationEntry(
            SorobanCredentials::forAddressCredentials($signerCreds),
            $this->makeInvocation(),
        );

        $invokerKp = $signerKp;
        $tx        = $this->buildAssembledTransactionWithAuthEntries([$contractEntry, $signerEntry], $invokerKp);
        $this->injectMockedServerResponses($tx, [$this->makeLatestLedgerResponse(1000)]);

        // Must complete: contractId fallback executes at line 581 for entry 0, signer does not
        // match, entry 0 is skipped. Entry 1 is signed normally.
        $tx->signAuthEntries(signerKeyPair: $signerKp, validUntilLedgerSeq: 9999);

        $ops = $tx->tx?->getOperations();
        $op  = $ops[0] ?? null;
        $this->assertInstanceOf(InvokeHostFunctionOperation::class, $op);
        $auth = $op->auth;

        // Entry 0 (C-address top-level): must remain void.
        $result0 = $auth[0]->credentials->addressWithDelegates;
        $this->assertNotNull($result0);
        $this->assertNull($result0->addressCredentials->signature->vec,
            'Contract top-level signature must remain void when signer is a G-address');

        // Entry 1 (ADDRESS signer): must be signed.
        $sig1 = $auth[1]->credentials->addressCredentials?->signature;
        $this->assertNotNull($sig1?->vec, 'Signer ADDRESS entry must be signed');
        $this->assertCount(1, $sig1->vec);
    }

    /**
     * delegateTreeContainsAddress: when the signer address matches a NESTED delegate (depth 2),
     * the method returns true via the recursive call (lines 653-654).
     */
    public function testSignAuthEntriesFindsNestedDelegateViaContainsAddress(): void
    {
        $topKp         = KeyPair::random();
        $outerDelegate = KeyPair::random();
        $innerDelegate = KeyPair::fromSeed(self::TEST_SECRET); // will be the signer

        $topAddress   = Address::fromAccountId($topKp->getAccountId());
        $topCreds     = new SorobanAddressCredentials($topAddress, 1, 100, XdrSCVal::forVoid());

        $innerAddr = XdrSCAddress::forAccountId($innerDelegate->getAccountId());
        $innerNode = new SorobanDelegateSignature($innerAddr, XdrSCVal::forVoid(), []);

        $outerAddr = XdrSCAddress::forAccountId($outerDelegate->getAccountId());
        $outerNode = new SorobanDelegateSignature($outerAddr, XdrSCVal::forVoid(), [$innerNode]);

        $withDelegates = new SorobanAddressCredentialsWithDelegates($topCreds, [$outerNode]);
        $creds         = SorobanCredentials::forAddressWithDelegates($withDelegates);
        $entry         = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        $invokerKp = KeyPair::fromSeed(self::TEST_SECRET);
        $tx        = $this->buildAssembledTransactionWithAuthEntries([$entry], $invokerKp);
        $this->injectMockedServerResponses($tx, [$this->makeLatestLedgerResponse(1000)]);

        // Sign with the innerDelegate keypair. delegateTreeContainsAddress must find it at depth 2
        // and set $entryMatchesDelegate = true, then route via forAddress.
        $tx->signAuthEntries(signerKeyPair: $innerDelegate, validUntilLedgerSeq: 9999);

        $ops  = $tx->tx?->getOperations();
        $op   = $ops[0] ?? null;
        $this->assertInstanceOf(InvokeHostFunctionOperation::class, $op);
        $auth = $op->auth;
        $withDelegatesResult = $auth[0]->credentials->addressWithDelegates;
        $this->assertNotNull($withDelegatesResult);

        // Top-level must remain void.
        $this->assertNull($withDelegatesResult->addressCredentials->signature->vec);

        // Outer delegate must remain void.
        $outerResult = $withDelegatesResult->delegates[0];
        $this->assertNull($outerResult->signature->vec);

        // Inner delegate must be signed.
        $innerResult = $outerResult->nestedDelegates[0];
        $this->assertNotNull($innerResult->signature->vec);
        $this->assertCount(1, $innerResult->signature->vec);
    }
}
