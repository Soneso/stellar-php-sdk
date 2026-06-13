<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Soroban;

use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\Constants\NetworkConstants;
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
use Soneso\StellarSDK\Soroban\Requests\SimulateTransactionRequest;
use Soneso\StellarSDK\Soroban\Responses\SimulateTransactionResponse;
use Soneso\StellarSDK\Soroban\SorobanAddressCredentials;
use Soneso\StellarSDK\Soroban\SorobanAddressCredentialsWithDelegates;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Soroban\SorobanAuthorizedFunction;
use Soneso\StellarSDK\Soroban\SorobanAuthorizedInvocation;
use Soneso\StellarSDK\Soroban\SorobanCredentials;
use Soneso\StellarSDK\Soroban\SorobanDelegateDescriptor;
use Soneso\StellarSDK\Soroban\SorobanDelegateSignature;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\TimeBounds;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Xdr\XdrExtensionPoint;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrSorobanResources;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionDataExt;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType;

/**
 * Protocol 27 (CAP-71) simulation and AssembledTransaction tests.
 *
 * Covers SimulateTransactionRequest.authV2 wire flag, MethodOptions.authV2 thread-through,
 * signAuthEntries and needsNonInvokerSigningBy across all three address arms, the
 * delegates-only send-precheck reconciliation, and arm preservation.
 */
class P27AssembledTransactionTest extends TestCase
{
    // ---------------------------------------------------------------------------
    // Constants shared by multiple tests
    // ---------------------------------------------------------------------------

    private const TEST_ACCOUNT_ID  = 'GD56FXQWEQ34GBKJLU52QD3YB4CJSJCVPLOKISGZDRCYVIWZK5TMVDT3';
    private const TEST_CONTRACT_ID = 'CA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUWDA';
    private const TEST_SECRET_KEY  = 'SAMKI63THJER2XVJA5LQXIPBWIV6FEFSS5ILURYGSCHFKZVDE5YVQWC7';
    // A second contract used as an auxiliary address for top-level / delegate tests.
    private const AUX_CONTRACT_ID  = 'CA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAXE';
    private const TEST_RPC_URL     = 'http://localhost:1';

    private KeyPair $invokerKp;
    private Network $network;

    public function setUp(): void
    {
        error_reporting(E_ALL);
        $this->invokerKp = KeyPair::fromSeed(self::TEST_SECRET_KEY);
        $this->network   = Network::testnet();
    }

    // =========================================================================
    // TASK 1 — SimulateTransactionRequest.authV2 wire flag
    // =========================================================================

    /**
     * The "authV2" key must be ABSENT from request params when $authV2 is false (default).
     */
    public function testAuthV2KeyAbsentByDefault(): void
    {
        $tx      = $this->buildMockTx();
        $request = new SimulateTransactionRequest(transaction: $tx);

        $params = $request->getRequestParams();

        $this->assertArrayNotHasKey('authV2', $params, '"authV2" key must not appear when flag is false (default)');
        $this->assertArrayHasKey('transaction', $params);
    }

    /**
     * The "authV2" key must be ABSENT when explicitly set to false.
     */
    public function testAuthV2KeyAbsentWhenExplicitFalse(): void
    {
        $tx      = $this->buildMockTx();
        $request = new SimulateTransactionRequest(transaction: $tx, authV2: false);

        $params = $request->getRequestParams();

        $this->assertArrayNotHasKey('authV2', $params, '"authV2" key must not appear when explicitly false');
    }

    /**
     * The "authV2" key must be present and equal to boolean true when opted in.
     */
    public function testAuthV2KeyPresentAsBooleanTrueWhenOptedIn(): void
    {
        $tx      = $this->buildMockTx();
        $request = new SimulateTransactionRequest(transaction: $tx, authV2: true);

        $params = $request->getRequestParams();

        $this->assertArrayHasKey('authV2', $params, '"authV2" key must appear when flag is true');
        $this->assertSame(true, $params['authV2'], '"authV2" must be boolean true (not a string or int)');
    }

    /**
     * Existing params (transaction, resourceConfig, authMode) must be unaffected by authV2.
     */
    public function testExistingParamsUnaffectedByAuthV2(): void
    {
        $tx             = $this->buildMockTx();
        $resourceConfig = new \Soneso\StellarSDK\Soroban\Requests\ResourceConfig(5000000);
        $request        = new SimulateTransactionRequest(
            transaction:    $tx,
            resourceConfig: $resourceConfig,
            authMode:       'record',
            authV2:         true,
        );

        $params = $request->getRequestParams();

        $this->assertArrayHasKey('transaction', $params);
        $this->assertArrayHasKey('resourceConfig', $params);
        $this->assertEquals('record', $params['authMode']);
        $this->assertSame(true, $params['authV2']);
    }

    /**
     * Setter/getter round-trip for authV2.
     */
    public function testAuthV2SetterGetterRoundTrip(): void
    {
        $tx      = $this->buildMockTx();
        $request = new SimulateTransactionRequest(transaction: $tx);

        $this->assertFalse($request->getAuthV2());

        $request->setAuthV2(true);
        $this->assertTrue($request->getAuthV2());
        $this->assertArrayHasKey('authV2', $request->getRequestParams());

        $request->setAuthV2(false);
        $this->assertFalse($request->getAuthV2());
        $this->assertArrayNotHasKey('authV2', $request->getRequestParams());
    }

    // =========================================================================
    // TASK 2 — MethodOptions.authV2 threads into the simulate() request
    // =========================================================================

    /**
     * When MethodOptions.authV2 = true, the simulate() call must send "authV2": true in the
     * RPC request body. Verified by intercepting the mock HTTP request body.
     */
    public function testMethodOptionsAuthV2TrueThreadsIntoSimulateRequest(): void
    {
        $capturedBodies = [];
        $tx = $this->buildAssembledTransactionWithMock(
            methodOptions: new MethodOptions(simulate: false, restore: false, authV2: true),
            mockResponses: [$this->createSimulateResponse()],
            capturedBodies: $capturedBodies,
        );

        $tx->simulate();

        $this->assertCount(1, $capturedBodies, 'Expected exactly one RPC request');
        $body = json_decode($capturedBodies[0], true);
        $this->assertIsArray($body);
        // The SorobanServer prepareRequest() places getRequestParams() directly under 'params'.
        $params = $body['params'] ?? [];
        $this->assertArrayHasKey('authV2', $params, '"authV2" must appear in RPC params when MethodOptions.authV2 = true');
        $this->assertSame(true, $params['authV2']);
    }

    /**
     * Mirror: default MethodOptions must NOT include "authV2" in the RPC request body.
     */
    public function testMethodOptionsDefaultOmitsAuthV2FromSimulateRequest(): void
    {
        $capturedBodies = [];
        $tx = $this->buildAssembledTransactionWithMock(
            methodOptions: new MethodOptions(simulate: false, restore: false),
            mockResponses: [$this->createSimulateResponse()],
            capturedBodies: $capturedBodies,
        );

        $tx->simulate();

        $this->assertCount(1, $capturedBodies, 'Expected exactly one RPC request');
        $body = json_decode($capturedBodies[0], true);
        $this->assertIsArray($body);
        $params = $body['params'] ?? [];
        $this->assertArrayNotHasKey('authV2', $params, '"authV2" must NOT appear in RPC params by default');
    }

    /**
     * MethodOptions default values include authV2 = false.
     */
    public function testMethodOptionsAuthV2DefaultIsFalse(): void
    {
        $options = new MethodOptions();
        $this->assertFalse($options->authV2);
    }

    // =========================================================================
    // TASK 3 — Arm preservation: V2 entry stays V2 after signAuthEntries
    // =========================================================================

    /**
     * A V2 credential entry must remain ADDRESS_V2 after signing via signAuthEntries.
     * The arm must not be downgraded to legacy ADDRESS.
     */
    public function testArmPreservationV2EntryRemainsV2AfterSigning(): void
    {
        $signerKp = KeyPair::fromSeed(self::TEST_SECRET_KEY);

        $address      = Address::fromAccountId($signerKp->getAccountId());
        $addressCreds = new SorobanAddressCredentials($address, 42, 9999, XdrSCVal::forVoid());
        $creds        = SorobanCredentials::forAddressCredentialsV2($addressCreds);
        $invocation   = $this->makeInvocation();
        $entry        = new SorobanAuthorizationEntry($creds, $invocation);

        $tx = $this->buildAssembledTransactionWithAuthEntries([$entry], $signerKp);

        $latestLedgerResponse = $this->makeLatestLedgerResponse(1000);
        $this->injectMockedServerResponses($tx, [$latestLedgerResponse]);

        $tx->signAuthEntries(signerKeyPair: $signerKp, validUntilLedgerSeq: 9999);

        $ops   = $tx->tx?->getOperations();
        $this->assertNotNull($ops);
        $op    = $ops[0];
        $this->assertInstanceOf(InvokeHostFunctionOperation::class, $op);
        $auth  = $op->auth;
        $this->assertCount(1, $auth);

        $this->assertEquals(
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2,
            $auth[0]->credentials->credentialType,
            'ADDRESS_V2 arm must be preserved after signAuthEntries',
        );
        // Signature must be written.
        $sig = $auth[0]->credentials->getAddressCredentials()?->signature;
        $this->assertNotNull($sig?->vec, 'Signature must be written after signing');
        $this->assertCount(1, $sig->vec);
    }

    // =========================================================================
    // TASK 3 — needsNonInvokerSigningBy: all arms, delegates, delegates-only pattern
    // =========================================================================

    /**
     * needsNonInvokerSigningBy reports the top-level address of a legacy ADDRESS entry
     * with a void signature.
     */
    public function testNeedsNonInvokerSigningByReportsLegacyAddressEntry(): void
    {
        $signerKp     = KeyPair::random();
        $address      = Address::fromAccountId($signerKp->getAccountId());
        $addressCreds = new SorobanAddressCredentials($address, 1, 100, XdrSCVal::forVoid());
        $creds        = SorobanCredentials::forAddressCredentials($addressCreds);
        $entry        = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        $tx = $this->buildAssembledTransactionWithAuthEntries([$entry], $this->invokerKp);
        $needed = $tx->needsNonInvokerSigningBy();

        $this->assertContains($signerKp->getAccountId(), $needed);
    }

    /**
     * needsNonInvokerSigningBy reports the top-level address AND unsigned delegate nodes
     * for a WITH_DELEGATES entry. Does NOT report signed delegate nodes.
     */
    public function testNeedsNonInvokerSigningByReportsAllNodesForWithDelegates(): void
    {
        $topKp        = KeyPair::random();
        $delegateKp1  = KeyPair::random();
        $delegateKp2  = KeyPair::random();

        $topAddress   = Address::fromAccountId($topKp->getAccountId());
        $topCreds     = new SorobanAddressCredentials($topAddress, 1, 100, XdrSCVal::forVoid());

        $delegate1Addr = XdrSCAddress::forAccountId($delegateKp1->getAccountId());
        $delegate2Addr = XdrSCAddress::forAccountId($delegateKp2->getAccountId());

        // delegate2 is already signed (non-void).
        $delegate1 = new SorobanDelegateSignature($delegate1Addr, XdrSCVal::forVoid(), []);
        $delegate2 = new SorobanDelegateSignature($delegate2Addr, XdrSCVal::forVec([XdrSCVal::forVoid()]), []);

        // Sort by XDR bytes to avoid constructor ordering issues.
        $delegates = [$delegate1, $delegate2];
        usort($delegates, static fn ($a, $b) => strcmp($a->address->encode(), $b->address->encode()));

        $withDelegates = new SorobanAddressCredentialsWithDelegates($topCreds, $delegates);
        $creds         = SorobanCredentials::forAddressWithDelegates($withDelegates);
        $entry         = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        $tx = $this->buildAssembledTransactionWithAuthEntries([$entry], $this->invokerKp);
        $needed = $tx->needsNonInvokerSigningBy();

        // Top-level (void) must appear.
        $this->assertContains($topKp->getAccountId(), $needed, 'Void top-level must be reported');
        // Unsigned delegate (delegate1) must appear.
        $this->assertContains($delegateKp1->getAccountId(), $needed, 'Unsigned delegate must be reported');
        // Signed delegate (delegate2) must NOT appear (default $includeAlreadySigned = false).
        $this->assertNotContains($delegateKp2->getAccountId(), $needed, 'Signed delegate must NOT be reported by default');
    }

    /**
     * The no-blocking rule: a WITH_DELEGATES entry where the top-level signature is void
     * but ALL delegate nodes are signed must NOT cause sign() to throw.
     *
     * This is the "delegates-only" pattern — the void top-level is legitimate.
     */
    public function testSendPrecheckAllowsDelegatesOnlyPatternDespiteVoidTopLevel(): void
    {
        $topKp       = KeyPair::random();
        $delegateKp  = KeyPair::random();

        $topAddress   = Address::fromAccountId($topKp->getAccountId());
        $topCreds     = new SorobanAddressCredentials($topAddress, 1, 100, XdrSCVal::forVoid());

        $delegateAddr = XdrSCAddress::forAccountId($delegateKp->getAccountId());
        // Delegate IS already signed (non-void signature vec).
        $delegate     = new SorobanDelegateSignature(
            $delegateAddr,
            XdrSCVal::forVec([XdrSCVal::forVoid()]),
            [],
        );

        $withDelegates = new SorobanAddressCredentialsWithDelegates($topCreds, [$delegate]);
        $creds         = SorobanCredentials::forAddressWithDelegates($withDelegates);
        $entry         = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        // The invoker is signing the envelope; the entry is a delegates-only pattern.
        $tx = $this->buildAssembledTransactionWithAuthEntries([$entry], $this->invokerKp);

        // sign() must NOT throw even though top-level is void — all delegates are signed.
        try {
            $tx->sign(force: true);
        } catch (Exception $e) {
            $this->fail('sign() must not throw for delegates-only pattern: ' . $e->getMessage());
        }

        $this->assertNotNull($tx->signed, 'Signed transaction must be set after successful sign()');
    }

    /**
     * Contrast: a WITH_DELEGATES entry where a DELEGATE is unsigned DOES block the send.
     */
    public function testSendPrecheckBlocksWhenDelegateIsUnsigned(): void
    {
        $topKp      = KeyPair::random();
        $delegateKp = KeyPair::random();

        $topAddress   = Address::fromAccountId($topKp->getAccountId());
        $topCreds     = new SorobanAddressCredentials($topAddress, 1, 100, XdrSCVal::forVoid());

        // Delegate is unsigned (void).
        $delegateAddr = XdrSCAddress::forAccountId($delegateKp->getAccountId());
        $delegate     = new SorobanDelegateSignature($delegateAddr, XdrSCVal::forVoid(), []);

        $withDelegates = new SorobanAddressCredentialsWithDelegates($topCreds, [$delegate]);
        $creds         = SorobanCredentials::forAddressWithDelegates($withDelegates);
        $entry         = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        $tx = $this->buildAssembledTransactionWithAuthEntries([$entry], $this->invokerKp);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/multiple signers/');

        $tx->sign(force: true);
    }

    // =========================================================================
    // TASK 3 — signAuthEntries signs delegate node when signer matches a delegate address
    // =========================================================================

    /**
     * When the signer address matches a DELEGATE node (not the top-level), the signature
     * must land in the delegate node and the top-level must remain void.
     *
     * Uses DISTINCT top-level and delegate addresses — this is required to prove correct routing.
     */
    public function testSignAuthEntriesSignsDelegateNodeWithDistinctAddresses(): void
    {
        $topKp       = KeyPair::random();
        $delegateKp  = KeyPair::fromSeed(self::TEST_SECRET_KEY);  // distinct from top

        $topAddress      = Address::fromAccountId($topKp->getAccountId());
        $delegateAddress = Address::fromAccountId($delegateKp->getAccountId());

        // Build top-level address credentials (void signature).
        $topCreds = new SorobanAddressCredentials($topAddress, 1, 100, XdrSCVal::forVoid());

        // Build delegate node (void signature).
        $delegateXdrAddr = XdrSCAddress::forAccountId($delegateKp->getAccountId());
        $delegateNode    = new SorobanDelegateSignature($delegateXdrAddr, XdrSCVal::forVoid(), []);

        $withDelegates = new SorobanAddressCredentialsWithDelegates($topCreds, [$delegateNode]);
        $creds         = SorobanCredentials::forAddressWithDelegates($withDelegates);
        $entry         = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        $tx = $this->buildAssembledTransactionWithAuthEntries([$entry], $this->invokerKp);
        $this->injectMockedServerResponses($tx, [$this->makeLatestLedgerResponse(1000)]);

        // Sign with the DELEGATE keypair — must route to the delegate node.
        $tx->signAuthEntries(signerKeyPair: $delegateKp, validUntilLedgerSeq: 9999);

        $ops = $tx->tx?->getOperations();
        $this->assertNotNull($ops);
        $op  = $ops[0];
        $this->assertInstanceOf(InvokeHostFunctionOperation::class, $op);
        $auth = $op->auth;
        $this->assertCount(1, $auth);

        $withDelegatesResult = $auth[0]->credentials->addressWithDelegates;
        $this->assertNotNull($withDelegatesResult);

        // Top-level must remain void.
        $topSig = $withDelegatesResult->addressCredentials->signature;
        $this->assertNull($topSig->vec, 'Top-level signature must remain void after signing only the delegate');

        // Delegate must carry a signature.
        $delegateResult = $withDelegatesResult->delegates[0];
        $this->assertNotNull($delegateResult->signature->vec, 'Delegate node must have a non-void signature');
        $this->assertCount(1, $delegateResult->signature->vec);
    }

    // =========================================================================
    // TASK 3 — needsNonInvokerSigningBy includeAlreadySigned = true
    // =========================================================================

    /**
     * When $includeAlreadySigned = true, already-signed delegate nodes are included.
     */
    public function testNeedsNonInvokerSigningByIncludesSignedWhenFlagSet(): void
    {
        $topKp       = KeyPair::random();
        $delegateKp  = KeyPair::random();

        $topAddress   = Address::fromAccountId($topKp->getAccountId());
        $topCreds     = new SorobanAddressCredentials($topAddress, 1, 100, XdrSCVal::forVoid());

        $delegateAddr = XdrSCAddress::forAccountId($delegateKp->getAccountId());
        // Delegate is already signed.
        $delegate     = new SorobanDelegateSignature(
            $delegateAddr,
            XdrSCVal::forVec([XdrSCVal::forVoid()]),
            [],
        );

        $withDelegates = new SorobanAddressCredentialsWithDelegates($topCreds, [$delegate]);
        $creds         = SorobanCredentials::forAddressWithDelegates($withDelegates);
        $entry         = new SorobanAuthorizationEntry($creds, $this->makeInvocation());

        $tx = $this->buildAssembledTransactionWithAuthEntries([$entry], $this->invokerKp);

        $needed = $tx->needsNonInvokerSigningBy(includeAlreadySigned: true);

        $this->assertContains($delegateKp->getAccountId(), $needed, 'Signed delegate must appear when includeAlreadySigned = true');
        $this->assertContains($topKp->getAccountId(), $needed);
    }

    // =========================================================================
    // TASK 3 — Unknown arm fails fast in signAuthEntries
    // =========================================================================

    /**
     * signAuthEntries must throw when an auth entry carries an unknown credential arm.
     * Source-account entries are silently skipped (not an error).
     *
     * Strategy: include one valid ADDRESS entry (so needsNonInvokerSigningBy returns non-empty
     * and the callback path is taken), plus an unknown-arm entry. The loop hits the unknown-arm
     * entry after the valid one and throws.
     */
    public function testSignAuthEntriesFailsFastOnUnknownArm(): void
    {
        $signerKp = KeyPair::fromSeed(self::TEST_SECRET_KEY);

        // Valid ADDRESS entry with signerKp's address.
        $address      = Address::fromAccountId($signerKp->getAccountId());
        $addressCreds = new SorobanAddressCredentials($address, 1, 100, XdrSCVal::forVoid());
        $validEntry   = new SorobanAuthorizationEntry(
            SorobanCredentials::forAddressCredentials($addressCreds),
            $this->makeInvocation(),
        );

        // Unknown-arm entry: credentialType = 99, but a non-SOURCE_ACCOUNT type so it reaches
        // the unknown-arm check inside the loop.
        $addressCreds2 = new SorobanAddressCredentials($address, 2, 100, XdrSCVal::forVoid());
        $badCreds      = SorobanCredentials::forAddressCredentials($addressCreds2);
        $badCreds->credentialType = 99;
        $badEntry = new SorobanAuthorizationEntry($badCreds, $this->makeInvocation());

        // Use callback path so we bypass the needsNonInvokerSigningBy check.
        $tx = $this->buildAssembledTransactionWithAuthEntries([$validEntry, $badEntry], $this->invokerKp);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Unsupported SorobanCredentials arm/');

        // Provide validUntilLedgerSeq to avoid a real network call for getLatestLedger.
        $tx->signAuthEntries(
            signerKeyPair:           $signerKp,
            authorizeEntryCallback:  static function (SorobanAuthorizationEntry $e, Network $n): SorobanAuthorizationEntry {
                return $e;
            },
            validUntilLedgerSeq:     9999,
        );
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Builds a minimal Transaction object (no network calls).
     */
    private function buildMockTx(): \Soneso\StellarSDK\Transaction
    {
        $account    = new Account(self::TEST_ACCOUNT_ID, new BigInteger(1));
        $hostFn     = new InvokeContractHostFunction(self::TEST_CONTRACT_ID, 'test', []);
        $op         = (new InvokeHostFunctionOperationBuilder($hostFn))->build();
        return (new TransactionBuilder($account))->addOperation($op)->build();
    }

    /**
     * Builds an AssembledTransaction pre-loaded with given auth entries.
     * $tx->tx is set but not simulated against the network.
     *
     * The simulation result is set up so isReadCall() returns false (auth entries are
     * in the simulation result, not just in the tx), allowing sign() to proceed.
     *
     * @param array<SorobanAuthorizationEntry> $entries
     */
    private function buildAssembledTransactionWithAuthEntries(
        array   $entries,
        KeyPair $invokerKp,
    ): AssembledTransaction {
        $clientOptions = new ClientOptions(
            sourceAccountKeyPair: $invokerKp,
            contractId:           self::TEST_CONTRACT_ID,
            network:              $this->network,
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
        $hostFn    = new InvokeContractHostFunction(self::TEST_CONTRACT_ID, 'test', []);
        $op        = (new InvokeHostFunctionOperationBuilder($hostFn))->build();
        $txBuilder = new TransactionBuilder(sourceAccount: $account);
        $txBuilder->addOperation($op);
        $built     = $txBuilder->build();
        $built->setSorobanAuth($entries);

        // Use an empty footprint (no ledger keys) to avoid XdrLedgerKey encoding issues.
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

        // Set auth in the sim result so isReadCall() returns false (authsCount > 0).
        $simResultProp = $reflection->getProperty('simulationResult');
        $simResultProp->setAccessible(true);
        $simResultProp->setValue($tx, new SimulateHostFunctionResult($txData, XdrSCVal::forVoid(), $entries));

        return $tx;
    }

    /**
     * Builds an AssembledTransaction with a mocked HTTP server and request-body capture.
     *
     * @param MethodOptions $methodOptions
     * @param array<Response> $mockResponses
     * @param array<string> $capturedBodies output: bodies of HTTP POST requests
     */
    private function buildAssembledTransactionWithMock(
        MethodOptions $methodOptions,
        array         $mockResponses,
        array         &$capturedBodies,
    ): AssembledTransaction {
        $capturedBodiesRef = &$capturedBodies;
        $mock = new MockHandler($mockResponses);
        $stack = HandlerStack::create($mock);
        // Middleware to capture request bodies.
        $stack->push(static function (callable $handler) use (&$capturedBodiesRef): callable {
            return static function ($request, $options) use ($handler, &$capturedBodiesRef) {
                $capturedBodiesRef[] = (string) $request->getBody();
                return $handler($request, $options);
            };
        });
        $client = new Client(['handler' => $stack]);

        $invokerKp     = KeyPair::fromSeed(self::TEST_SECRET_KEY);
        $clientOptions = new ClientOptions(
            sourceAccountKeyPair: $invokerKp,
            contractId:           self::TEST_CONTRACT_ID,
            network:              $this->network,
            rpcUrl:               self::TEST_RPC_URL,
        );
        $txOptions = new AssembledTransactionOptions(
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

        $server = new SorobanServer($txOptions->clientOptions->rpcUrl);
        $serverReflection = new \ReflectionClass($server);
        $httpClientProp   = $serverReflection->getProperty('httpClient');
        $httpClientProp->setAccessible(true);
        $httpClientProp->setValue($server, $client);

        $serverProp = $reflection->getProperty('server');
        $serverProp->setAccessible(true);
        $serverProp->setValue($tx, $server);

        // Build the raw transaction builder (no network).
        $account   = new Account($invokerKp->getAccountId(), new BigInteger(123456789));
        $hostFn    = new InvokeContractHostFunction(self::TEST_CONTRACT_ID, 'test', []);
        $op        = (new InvokeHostFunctionOperationBuilder($hostFn))->build();
        $txBuilder = new TransactionBuilder(sourceAccount: $account);
        $txBuilder->setTimeBounds(new TimeBounds(
            (new DateTime())->modify('- ' . NetworkConstants::DEFAULT_TIME_BOUNDS_OFFSET_SECONDS . ' seconds'),
            (new DateTime())->modify('+ ' . $methodOptions->timeoutInSeconds . ' seconds')
        ));
        $txBuilder->addOperation($op);
        $txBuilder->setMaxOperationFee($methodOptions->fee);

        $rawProp = $reflection->getProperty('raw');
        $rawProp->setAccessible(true);
        $rawProp->setValue($tx, $txBuilder);

        return $tx;
    }

    /**
     * Injects mock responses into the SorobanServer inside an AssembledTransaction.
     *
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

    /**
     * Creates a mock simulateTransaction response (success, no auth).
     */
    private function createSimulateResponse(): Response
    {
        $footprint  = new XdrLedgerFootprint([], []);
        $resources  = new XdrSorobanResources($footprint, 0, 0, 0);
        $ext        = new XdrSorobanTransactionDataExt(0);
        $txData     = new XdrSorobanTransactionData($ext, $resources, 0);

        return new Response(200, [], json_encode([
            'jsonrpc' => '2.0',
            'id'      => 1,
            'result'  => [
                'minResourceFee'  => '100',
                'latestLedger'    => 1000,
                'transactionData' => $txData->toBase64Xdr(),
                'results'         => [
                    [
                        'auth' => [],
                        'xdr'  => XdrSCVal::forVoid()->toBase64Xdr(),
                    ],
                ],
            ],
        ]));
    }

    /**
     * Creates a mock getLatestLedger response.
     */
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

    /**
     * Creates a minimal SorobanAuthorizedInvocation for testing.
     */
    private function makeInvocation(): SorobanAuthorizedInvocation
    {
        $contractAddress = Address::fromContractId(StrKey::decodeContractIdHex(self::AUX_CONTRACT_ID));
        $fn = SorobanAuthorizedFunction::forContractFunction($contractAddress, 'test', []);
        return new SorobanAuthorizedInvocation($fn, []);
    }
}
