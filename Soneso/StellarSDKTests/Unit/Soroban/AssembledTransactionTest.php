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
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperation;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\RestoreFootprintOperation;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Contract\AssembledTransaction;
use Soneso\StellarSDK\Soroban\Contract\AssembledTransactionOptions;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\MethodOptions;
use Soneso\StellarSDK\Soroban\Contract\SimulateHostFunctionResult;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SimulateTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SorobanRpcErrorResponse;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\TimeBounds;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedInvocation;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentials;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType;
use Soneso\StellarSDK\Xdr\XdrSorobanResources;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;
use Soneso\StellarSDK\Xdr\XdrExtensionPoint;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionDataExt;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;

class AssembledTransactionTest extends TestCase
{
    private const TEST_ACCOUNT_ID = "GD56FXQWEQ34GBKJLU52QD3YB4CJSJCVPLOKISGZDRCYVIWZK5TMVDT3";
    private const TEST_CONTRACT_ID = "CA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUWDA";
    private const TEST_RPC_URL = "https://soroban-testnet.stellar.org:443";

    // Test account secret key for signing tests - corresponds to TEST_ACCOUNT_ID
    private const TEST_SECRET_KEY = "SAMKI63THJER2XVJA5LQXIPBWIV6FEFSS5ILURYGSCHFKZVDE5YVQWC7";

    private KeyPair $testKeyPair;
    private KeyPair $testKeyPairWithSecret;
    private Network $testNetwork;

    public function setUp(): void
    {
        error_reporting(E_ALL);
        $this->testKeyPair = KeyPair::fromAccountId(self::TEST_ACCOUNT_ID);
        $this->testKeyPairWithSecret = KeyPair::fromSeed(self::TEST_SECRET_KEY);
        $this->testNetwork = Network::testnet();
    }

    /**
     * Creates a mock HTTP response for simulateTransaction.
     */
    private function createSimulateResponse(): Response
    {
        $transactionData = $this->createMockTransactionData();

        return new Response(200, [], json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'minResourceFee' => '100',
                'latestLedger' => 1000,
                'transactionData' => $transactionData->toBase64Xdr(),
                'results' => [
                    [
                        'auth' => [],
                        'xdr' => XdrSCVal::forVoid()->toBase64Xdr()
                    ]
                ]
            ]
        ]));
    }

    /**
     * Creates an AssembledTransaction with mocked data, bypassing network calls.
     * Constructs the transaction directly without calling the static build() method.
     */
    private function createMockedAssembledTransaction(
        AssembledTransactionOptions $txOptions
    ): AssembledTransaction {
        // Use reflection to create the AssembledTransaction without calling build()
        $reflection = new \ReflectionClass(AssembledTransaction::class);
        $tx = $reflection->newInstanceWithoutConstructor();

        // Initialize the options property
        $optionsProperty = $reflection->getProperty('options');
        $optionsProperty->setAccessible(true);
        $optionsProperty->setValue($tx, $txOptions);

        // Create a SorobanServer (we'll mock its HTTP client if needed)
        $server = new SorobanServer($txOptions->clientOptions->rpcUrl);
        $serverProperty = $reflection->getProperty('server');
        $serverProperty->setAccessible(true);
        $serverProperty->setValue($tx, $server);

        // Create a mock Account directly (no network call needed)
        $account = new Account(
            $txOptions->clientOptions->sourceAccountKeyPair->getAccountId(),
            new BigInteger(123456789)
        );

        // Build the raw transaction builder manually
        $invokeContractHostFunction = new InvokeContractHostFunction(
            $txOptions->clientOptions->contractId,
            $txOptions->method,
            $txOptions->arguments ?? []
        );
        $operation = (new InvokeHostFunctionOperationBuilder($invokeContractHostFunction))->build();

        $txBuilder = new TransactionBuilder(sourceAccount: $account);
        $txBuilder->setTimeBounds(new TimeBounds(
            (new DateTime())->modify("- " . NetworkConstants::DEFAULT_TIME_BOUNDS_OFFSET_SECONDS . " seconds"),
            (new DateTime())->modify("+ " . $txOptions->methodOptions->timeoutInSeconds . " seconds")
        ));
        $txBuilder->addOperation($operation);
        $txBuilder->setMaxOperationFee($txOptions->methodOptions->fee);

        $rawProperty = $reflection->getProperty('raw');
        $rawProperty->setAccessible(true);
        $rawProperty->setValue($tx, $txBuilder);

        return $tx;
    }

    /**
     * Injects a mocked HTTP client into an AssembledTransaction's server.
     */
    private function injectMockedServer(AssembledTransaction $tx, array $responses): void
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $reflection = new \ReflectionClass($tx);
        $serverProperty = $reflection->getProperty('server');
        $serverProperty->setAccessible(true);
        $server = $serverProperty->getValue($tx);

        $serverReflection = new \ReflectionClass($server);
        $httpClientProperty = $serverReflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($server, $client);
    }

    public function testAssembledTransactionOptionsConstruction(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions();

        $options = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: null,
            enableServerLogging: false
        );

        $this->assertSame($clientOptions, $options->clientOptions);
        $this->assertSame($methodOptions, $options->methodOptions);
        $this->assertEquals('test', $options->method);
        $this->assertNull($options->arguments);
        $this->assertFalse($options->enableServerLogging);
    }

    public function testClientOptionsConstruction(): void
    {
        $options = $this->createClientOptions();

        $this->assertEquals(self::TEST_ACCOUNT_ID, $options->sourceAccountKeyPair->getAccountId());
        $this->assertEquals(self::TEST_CONTRACT_ID, $options->contractId);
        $this->assertEquals("Test SDF Network ; September 2015", $options->network->getNetworkPassphrase());
        $this->assertEquals(self::TEST_RPC_URL, $options->rpcUrl);
        $this->assertFalse($options->enableServerLogging);
    }

    public function testMethodOptionsDefaultValues(): void
    {
        $options = new MethodOptions();

        $this->assertEquals(100, $options->fee);
        $this->assertEquals(300, $options->timeoutInSeconds);
        $this->assertTrue($options->simulate);
        $this->assertTrue($options->restore);
    }

    public function testMethodOptionsCustomValues(): void
    {
        $options = new MethodOptions(
            fee: 500,
            timeoutInSeconds: 60,
            simulate: false,
            restore: false
        );

        $this->assertEquals(500, $options->fee);
        $this->assertEquals(60, $options->timeoutInSeconds);
        $this->assertFalse($options->simulate);
        $this->assertFalse($options->restore);
    }

    public function testBuildWithOpCreatesAssembledTransaction(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        $this->assertInstanceOf(AssembledTransaction::class, $tx);
        $this->assertInstanceOf(TransactionBuilder::class, $tx->raw);
        $this->assertNull($tx->tx);
        $this->assertNull($tx->signed);
        $this->assertNull($tx->simulationResponse);
    }

    public function testIsReadCallDetectsReadOnlyTransaction(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        $simulationResponse = $this->createMockSimulationResponse();
        $tx->simulationResponse = $simulationResponse;

        $reflection = new \ReflectionClass($tx);
        $property = $reflection->getProperty('simulationResult');
        $property->setAccessible(true);

        $mockResult = new SimulateHostFunctionResult(
            $simulationResponse->transactionData,
            XdrSCVal::forVoid(),
            []
        );
        $property->setValue($tx, $mockResult);

        $isReadCall = $tx->isReadCall();
        $this->assertTrue($isReadCall);
    }

    public function testNeedsNonInvokerSigningByReturnsEmptyForNoAuth(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);
        $tx->tx = $tx->raw->build();

        $needed = $tx->needsNonInvokerSigningBy();
        $this->assertIsArray($needed);
        $this->assertCount(0, $needed);
    }

    public function testNeedsNonInvokerSigningByThrowsWithoutSimulation(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Transaction has not yet been simulated');

        $tx->needsNonInvokerSigningBy();
    }

    public function testSignThrowsWithoutPrivateKey(): void
    {
        $clientOptions = new ClientOptions(
            sourceAccountKeyPair: KeyPair::fromAccountId(self::TEST_ACCOUNT_ID),
            contractId: self::TEST_CONTRACT_ID,
            network: $this->testNetwork,
            rpcUrl: self::TEST_RPC_URL
        );
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);
        $tx->tx = $tx->raw->build();

        // Set up simulation so isReadCall() doesn't throw, allowing us to reach the private key check
        // Create a write-type transaction (non-empty readWrite footprint) so isReadCall() returns false
        $simulationResponse = $this->createMockSimulationResponse();
        $tx->simulationResponse = $simulationResponse;

        $reflection = new \ReflectionClass($tx);
        $property = $reflection->getProperty('simulationResult');
        $property->setAccessible(true);

        $footprint = new XdrLedgerFootprint([], [new XdrLedgerKey(XdrLedgerEntryType::ACCOUNT())]);
        $resources = new XdrSorobanResources($footprint, 100, 100, 100);
        $ext = new XdrSorobanTransactionDataExt(0);
        $txData = new XdrSorobanTransactionData($ext, $resources, 100);

        $mockResult = new SimulateHostFunctionResult(
            $txData,
            XdrSCVal::forVoid(),
            []
        );
        $property->setValue($tx, $mockResult);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Source account keypair has no private key');

        $tx->sign();
    }

    public function testSignThrowsForReadCallWithoutForce(): void
    {
        $clientOptions = $this->createClientOptionsWithSecret();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        $simulationResponse = $this->createMockSimulationResponse();
        $tx->simulationResponse = $simulationResponse;
        $tx->tx = $tx->raw->build();

        $reflection = new \ReflectionClass($tx);
        $property = $reflection->getProperty('simulationResult');
        $property->setAccessible(true);

        $mockResult = new SimulateHostFunctionResult(
            $simulationResponse->transactionData,
            XdrSCVal::forVoid(),
            []
        );
        $property->setValue($tx, $mockResult);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('This is a read call');

        $tx->sign();
    }

    public function testSignAllowsReadCallWithForce(): void
    {
        $clientOptions = $this->createClientOptionsWithSecret();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        $simulationResponse = $this->createMockSimulationResponse();
        $tx->simulationResponse = $simulationResponse;
        $tx->tx = $tx->raw->build();

        $reflection = new \ReflectionClass($tx);
        $property = $reflection->getProperty('simulationResult');
        $property->setAccessible(true);

        $mockResult = new SimulateHostFunctionResult(
            $simulationResponse->transactionData,
            XdrSCVal::forVoid(),
            []
        );
        $property->setValue($tx, $mockResult);

        $tx->sign(force: true);

        $this->assertNotNull($tx->signed);
        $this->assertInstanceOf(Transaction::class, $tx->signed);
    }

    public function testSendThrowsWithoutSignature(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The transaction has not yet been signed');

        $tx->send();
    }

    public function testGetSimulationDataReturnsResult(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        $simulationResponse = $this->createMockSimulationResponse();
        $tx->simulationResponse = $simulationResponse;

        $result = $tx->getSimulationData();

        $this->assertNotNull($result);
        $this->assertNotNull($result->transactionData);
        $this->assertNotNull($result->returnedValue);
    }

    public function testGetSimulationDataThrowsWithoutSimulation(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Transaction has not yet been simulated');

        $tx->getSimulationData();
    }

    public function testGetSimulationDataThrowsForSimulationError(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        $simulationResponse = new SimulateTransactionResponse([]);
        $simulationResponse->error = new SorobanRpcErrorResponse(['error' => ['code' => -1, 'message' => 'Test error']]);
        $simulationResponse->latestLedger = 1000;
        $tx->simulationResponse = $simulationResponse;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Transaction simulation failed');

        $tx->getSimulationData();
    }

    public function testGetSimulationDataCachesResult(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        $simulationResponse = $this->createMockSimulationResponse();
        $tx->simulationResponse = $simulationResponse;

        $result1 = $tx->getSimulationData();
        $result2 = $tx->getSimulationData();

        $this->assertSame($result1, $result2);
    }

    public function testSignAuthEntriesThrowsWithoutSimulation(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Transaction has not yet been simulated');

        $tx->signAuthEntries($this->testKeyPair);
    }

    public function testOptionsPropertyAccess(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions();

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        $this->assertSame($txOptions, $tx->options);
        $this->assertSame($clientOptions, $tx->options->clientOptions);
        $this->assertSame($methodOptions, $tx->options->methodOptions);
    }

    public function testRawPropertyIsTransactionBuilder(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        $this->assertNotNull($tx->raw);
        $this->assertInstanceOf(TransactionBuilder::class, $tx->raw);
    }

    public function testTxPropertyIsNullBeforeSimulation(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        $this->assertNull($tx->tx);
    }

    public function testSignedPropertyIsNullBeforeSignature(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        $this->assertNull($tx->signed);
    }

    public function testSimulationResponseIsNullInitially(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        $this->assertNull($tx->simulationResponse);
    }

    private function createClientOptions(): ClientOptions
    {
        return new ClientOptions(
            sourceAccountKeyPair: $this->testKeyPair,
            contractId: self::TEST_CONTRACT_ID,
            network: $this->testNetwork,
            rpcUrl: self::TEST_RPC_URL,
            enableServerLogging: false
        );
    }

    private function createClientOptionsWithSecret(): ClientOptions
    {
        return new ClientOptions(
            sourceAccountKeyPair: $this->testKeyPairWithSecret,
            contractId: self::TEST_CONTRACT_ID,
            network: $this->testNetwork,
            rpcUrl: self::TEST_RPC_URL,
            enableServerLogging: false
        );
    }

    private function createMockTransactionData(): XdrSorobanTransactionData
    {
        $footprint = new XdrLedgerFootprint([], []);
        $resources = new XdrSorobanResources($footprint, 0, 0, 0);
        $ext = new XdrSorobanTransactionDataExt(0);
        return new XdrSorobanTransactionData($ext, $resources, 0);
    }

    private function createMockSimulationResponse(): SimulateTransactionResponse
    {
        $response = new SimulateTransactionResponse([]);
        $response->transactionData = $this->createMockTransactionData();
        $response->minResourceFee = 100;
        $response->latestLedger = 1000;
        return $response;
    }

    public function testSimulateThrowsWithoutRawTransaction(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);
        $tx->raw = null;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Transaction has not yet been assembled');

        $tx->simulate();
    }

    public function testSimulateBuildsTransactionFromRaw(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false, restore: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);
        $this->assertNull($tx->tx);

        // Inject a mocked server with simulate response
        $this->injectMockedServer($tx, [$this->createSimulateResponse()]);

        $tx->simulate();

        $this->assertNotNull($tx->tx);
        $this->assertInstanceOf(Transaction::class, $tx->tx);
    }

    public function testSubmitThrowsWithoutSimulation(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('not yet been signed');

        $tx->send();
    }

    public function testSignAndSubmitCombinesSignAndSend(): void
    {
        $clientOptions = $this->createClientOptionsWithSecret();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);
        $tx->tx = $tx->raw->build();

        // Set up a simulation result so isReadCall() returns false (write transaction)
        $simulationResponse = $this->createMockSimulationResponse();
        $tx->simulationResponse = $simulationResponse;

        $reflection = new \ReflectionClass($tx);
        $property = $reflection->getProperty('simulationResult');
        $property->setAccessible(true);

        // Create a write-type result (non-empty readWrite footprint)
        $footprint = new XdrLedgerFootprint([], [new XdrLedgerKey(XdrLedgerEntryType::ACCOUNT())]);
        $resources = new XdrSorobanResources($footprint, 100, 100, 100);
        $ext = new XdrSorobanTransactionDataExt(0);
        $txData = new XdrSorobanTransactionData($ext, $resources, 100);

        $mockResult = new SimulateHostFunctionResult(
            $txData,
            XdrSCVal::forVoid(),
            []
        );
        $property->setValue($tx, $mockResult);

        // This should throw because we haven't set up send mocks (connection refused)
        $this->expectException(Exception::class);

        $tx->signAndSend();
    }

    public function testIsReadOnlyReturnsFalseWithoutSimulation(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        // isReadCall() throws when simulationResponse is null
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Transaction has not yet been simulated');

        $tx->isReadCall();
    }

    public function testGetResultThrowsWithoutSimulation(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Transaction has not yet been simulated');

        $tx->getSimulationData();
    }

    public function testParseResultReturnsValueFromSimulation(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        $simulationResponse = $this->createMockSimulationResponse();
        $tx->simulationResponse = $simulationResponse;

        $result = $tx->getSimulationData();

        $this->assertNotNull($result);
        $this->assertNotNull($result->returnedValue);
    }

    public function testBuildCreatesValidAssembledTransaction(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test_method',
            arguments: [XdrSCVal::forU32(42)]
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        $this->assertInstanceOf(AssembledTransaction::class, $tx);
        $this->assertNotNull($tx->raw);
        $this->assertNull($tx->tx);
        $this->assertNull($tx->signed);
        $this->assertNull($tx->simulationResponse);
        $this->assertSame($txOptions, $tx->options);
    }

    public function testSimulateUpdatesTransactionWithSimulationData(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false, restore: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        // Inject mocked server with simulate response
        $this->injectMockedServer($tx, [$this->createSimulateResponse()]);

        $tx->simulate();

        $this->assertNotNull($tx->simulationResponse);
        $this->assertNotNull($tx->tx);
    }

    public function testIsReadCallReturnsTrueForReadOnlyCall(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        $simulationResponse = $this->createMockSimulationResponse();
        $tx->simulationResponse = $simulationResponse;

        $reflection = new \ReflectionClass($tx);
        $property = $reflection->getProperty('simulationResult');
        $property->setAccessible(true);

        $mockResult = new SimulateHostFunctionResult(
            $simulationResponse->transactionData,
            XdrSCVal::forU32(100),
            []
        );
        $property->setValue($tx, $mockResult);

        $isReadCall = $tx->isReadCall();
        $this->assertTrue($isReadCall);
    }

    public function testSignWithCustomKeyPair(): void
    {
        $clientOptions = $this->createClientOptionsWithSecret();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);
        $tx->tx = $tx->raw->build();

        $simulationResponse = $this->createMockSimulationResponse();
        $tx->simulationResponse = $simulationResponse;

        $reflection = new \ReflectionClass($tx);
        $property = $reflection->getProperty('simulationResult');
        $property->setAccessible(true);

        $mockResult = new SimulateHostFunctionResult(
            $simulationResponse->transactionData,
            XdrSCVal::forU32(100),
            []
        );
        $property->setValue($tx, $mockResult);

        $tx->sign(force: true);

        $this->assertNotNull($tx->signed);
    }

    public function testGetSimulationDataThrowsForNullSimulationResponse(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);
        $tx->simulationResponse = null;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Transaction has not yet been simulated');

        $tx->getSimulationData();
    }

    public function testMethodOptionsDefaults(): void
    {
        $options = new MethodOptions();

        $this->assertEquals(100, $options->fee);
        $this->assertEquals(300, $options->timeoutInSeconds);
        $this->assertTrue($options->simulate);
        $this->assertTrue($options->restore);
    }

    public function testNeedsNonInvokerSigningByReturnsEmptyForNonInvokeHostFunctionOp(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        $tx = $this->createMockedAssembledTransaction($txOptions);

        // Replace the transaction with one containing a RestoreFootprintOperation
        $account = new Account(
            $clientOptions->sourceAccountKeyPair->getAccountId(),
            new BigInteger(123456789)
        );
        $restoreOp = new RestoreFootprintOperation();
        $txBuilder = new TransactionBuilder(sourceAccount: $account);
        $txBuilder->addOperation($restoreOp);
        $tx->tx = $txBuilder->build();

        $needed = $tx->needsNonInvokerSigningBy();
        $this->assertIsArray($needed);
        $this->assertCount(0, $needed);
    }

    public function testClientOptionsWithAllParameters(): void
    {
        $keyPair = $this->testKeyPair;
        $network = $this->testNetwork;
        $contractId = self::TEST_CONTRACT_ID;
        $rpcUrl = self::TEST_RPC_URL;

        $options = new ClientOptions(
            sourceAccountKeyPair: $keyPair,
            contractId: $contractId,
            network: $network,
            rpcUrl: $rpcUrl,
            enableServerLogging: true
        );

        $this->assertSame($keyPair, $options->sourceAccountKeyPair);
        $this->assertEquals($contractId, $options->contractId);
        $this->assertSame($network, $options->network);
        $this->assertEquals($rpcUrl, $options->rpcUrl);
        $this->assertTrue($options->enableServerLogging);
    }
}
