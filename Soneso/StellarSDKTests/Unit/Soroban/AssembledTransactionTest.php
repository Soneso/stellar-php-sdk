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
use InvalidArgumentException;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\Constants\NetworkConstants;
use Soneso\StellarSDK\CreateContractWithConstructorHostFunction;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\ExtendFootprintTTLOperation;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperation;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\LedgerBounds;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\RestoreFootprintOperation;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Contract\AssembledTransaction;
use Soneso\StellarSDK\Soroban\Contract\AssembledTransactionOptions;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\MethodOptions;
use Soneso\StellarSDK\Soroban\Contract\SimulateHostFunctionResult;
use Soneso\StellarSDK\Soroban\Responses\ContractErrorProvenance;
use Soneso\StellarSDK\Soroban\Responses\RestorePreamble;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SimulateTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SorobanRpcErrorResponse;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Soroban\SorobanAuthorizedFunction;
use Soneso\StellarSDK\Soroban\SorobanAuthorizedInvocation;
use Soneso\StellarSDK\Soroban\SorobanCredentials;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\TimeBounds;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\TransactionPreconditions;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrInvokeContractArgs;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
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
use Soneso\StellarSDK\Xdr\XdrLedgerKeyAccount;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrAccountEntry;
use Soneso\StellarSDK\Xdr\XdrAccountEntryExt;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryData;
use Soneso\StellarSDK\Xdr\XdrSequenceNumber;
use Psr\Log\NullLogger;

class AssembledTransactionTest extends TestCase
{
    private const TEST_ACCOUNT_ID = "GD56FXQWEQ34GBKJLU52QD3YB4CJSJCVPLOKISGZDRCYVIWZK5TMVDT3";
    private const TEST_CONTRACT_ID = "CA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUWDA";
    // Unroutable on purpose: all HTTP is served by injected mock handlers, and
    // if a test ever issues an unmocked request it errors out locally instead
    // of reaching a live server.
    private const TEST_RPC_URL = "http://localhost:1";

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
     * Creates a mock HTTP response for getTransaction with SUCCESS status.
     */
    private function createGetTransactionSuccessResponse(): Response
    {
        return new Response(200, [], json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'status' => GetTransactionResponse::STATUS_SUCCESS,
                'latestLedger' => 1001,
                'latestLedgerCloseTime' => '1700000001',
                'oldestLedger' => 990,
                'oldestLedgerCloseTime' => '1699999000',
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
        $optionsProperty->setValue($tx, $txOptions);

        // Tests that need RPC responses replace this server with a mocked server.
        $server = new SorobanServer($txOptions->clientOptions->rpcUrl);
        $serverProperty = $reflection->getProperty('server');
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
        $rawProperty->setValue($tx, $txBuilder);

        return $tx;
    }

    /**
     * Gives an AssembledTransaction a SorobanServer that serves its RPC requests from the
     * given responses.
     *
     * Returns the mock handler so tests can assert the queue was fully consumed.
     */
    private function injectMockedServer(AssembledTransaction $tx, array $responses, array &$history = [], array $clientOptions = []): MockHandler
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $stack->push(\GuzzleHttp\Middleware::history($history));
        $client = new Client(['handler' => $stack] + $clientOptions);
        $server = new SorobanServer($tx->options->clientOptions->rpcUrl, $client);

        $serverProperty = (new \ReflectionClass($tx))->getProperty('server');
        $serverProperty->setValue($tx, $server);

        return $mock;
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
        );

        $this->assertSame($clientOptions, $options->clientOptions);
        $this->assertSame($methodOptions, $options->methodOptions);
        $this->assertEquals('test', $options->method);
        $this->assertNull($options->arguments);
        $this->assertNull($options->logger);
    }

    public function testClientOptionsConstruction(): void
    {
        $options = $this->createClientOptions();

        $this->assertEquals(self::TEST_ACCOUNT_ID, $options->sourceAccountKeyPair->getAccountId());
        $this->assertEquals(self::TEST_CONTRACT_ID, $options->contractId);
        $this->assertEquals("Test SDF Network ; September 2015", $options->network->getNetworkPassphrase());
        $this->assertEquals(self::TEST_RPC_URL, $options->rpcUrl);
        $this->assertNull($options->logger);
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

    #[\PHPUnit\Framework\Attributes\DataProvider('capturedSimulationFailures')]
    public function testSimulationFailureReportsCapturedContractErrors(array $json, int $count, ?array $expected, string $suffix): void
    {
        $tx = $this->buildSimulationResponse($json);
        $this->assertCount($count, $json['result']['events']);
        SimulationFailureFixtures::assertFailure($this->simulationFailure($tx), $json['result']['events'], $expected,
            'Transaction simulation failed: ' . $json['result']['error'] . $suffix);
    }

    public static function capturedSimulationFailures(): iterable
    {
        return SimulationFailureFixtures::captures();
    }

    public function testSimulationErrorSkipsMalformedEntriesIndividually(): void
    {
        $valid = SimulationFailureFixtures::event(3114, SimulationFailureFixtures::VERIFIER);
        $events = ['%%%', $valid, 'AAAA', SimulationFailureFixtures::event(3016, SimulationFailureFixtures::ACCOUNT), $valid];
        $tx = $this->buildSimulationResponse(['result' => ['error' => SimulationFailureFixtures::AUTH, 'events' => $events]]);
        SimulationFailureFixtures::assertFailure($this->simulationFailure($tx), $events,
            [3114, ContractErrorProvenance::DIAGNOSTIC_EVENTS, SimulationFailureFixtures::VERIFIER],
            'Transaction simulation failed: HostError: Error(Auth, InvalidAction)' . SimulationFailureFixtures::VERIFIER_SUFFIX);
    }

    public function testSimulationFailureReportsAnEventWithoutContractId(): void
    {
        $events = [SimulationFailureFixtures::event(id: null)];
        $tx = $this->buildSimulationResponse(['result' => ['error' => SimulationFailureFixtures::CONTEXT, 'events' => $events]]);
        SimulationFailureFixtures::assertFailure($this->simulationFailure($tx), $events,
            [3201, ContractErrorProvenance::DIAGNOSTIC_EVENTS, null],
            'Transaction simulation failed: HostError: Error(Context, InvalidAction) (contract error in diagnostic events: Error(Contract, #3201))');
    }

    public function testSimulationExceptionKeepsAnIndependentSnapshot(): void
    {
        $json = SimulationFailureFixtures::capture('case-a');
        $tx = $this->buildSimulationResponse($json);
        $error = $this->simulationFailure($tx);
        $tx->simulationResponse->events = SimulationFailureFixtures::capture('case-b2-unregistered')['result']['events'];
        $tx->simulationResponse->resultError = SimulationFailureFixtures::AUTH;
        SimulationFailureFixtures::assertReport([3016, ContractErrorProvenance::DIAGNOSTIC_EVENTS, SimulationFailureFixtures::ACCOUNT],
            $tx->simulationResponse->reportedContractError());
        SimulationFailureFixtures::assertFailure($error, $json['result']['events'],
            [3201, ContractErrorProvenance::DIAGNOSTIC_EVENTS, SimulationFailureFixtures::POLICY],
            'Transaction simulation failed: HostError: Error(Context, InvalidAction)' . SimulationFailureFixtures::POLICY_SUFFIX);
    }

    public function testSimulationFailureCarriesTheRpcErrorAndKeepsOtherFailures(): void
    {
        $json = ['jsonrpc' => '2.0', 'id' => 1, 'error' => ['code' => -32602, 'message' => 'Invalid params', 'data' => ['field' => 'transaction']]];
        $tx = $this->buildSimulationResponse($json);
        SimulationFailureFixtures::assertFailure($this->simulationFailure($tx), [], null,
            'Transaction simulation failed: JSON-RPC error -32602: Invalid params', $tx->simulationResponse->error);
        $this->assertSame($json, $tx->simulationResponse->error->getJsonResponse());
        $this->assertSame(['field' => 'transaction'], $tx->simulationResponse->error->data);
        $tx->simulationResponse->error = new SorobanRpcErrorResponse([]);
        SimulationFailureFixtures::assertFailure($this->simulationFailure($tx), [], null,
            'Transaction simulation failed: JSON-RPC error null: null', $tx->simulationResponse->error);
        $tx = $this->buildSimulationResponse(['result' => ['events' => [SimulationFailureFixtures::event()]]]);
        SimulationFailureFixtures::assertFailure($this->simulationFailure($tx), [], null, 'Transaction simulation failed: ');
        $cause = new \RuntimeException('connection closed');
        $transport = new \GuzzleHttp\Exception\RequestException('transport failed', new \GuzzleHttp\Psr7\Request('POST', self::TEST_RPC_URL), null, $cause);
        foreach ([$transport, '{'] as $input) {
            try {
                $this->buildSimulationResponse($input);
                $this->fail('Expected an RPC failure');
            } catch (Exception $error) {
                if ($input === $transport) {
                    $this->assertSame($transport, $error);
                    $this->assertSame($cause, $error->getPrevious());
                } else {
                    $this->assertSame(InvalidArgumentException::class, get_class($error));
                    $this->assertSame('Error in json_decode: Syntax error', $error->getMessage());
                }
            }
        }
        $tx->simulationResponse = null;
        $error = $this->simulationFailure($tx);
        $this->assertSame(Exception::class, get_class($error));
        $this->assertSame('Transaction has not yet been simulated', $error->getMessage());
        $tx = $this->buildSimulationResponse(['result' => ['minResourceFee' => '100',
            'transactionData' => $this->createMockTransactionData()->toBase64Xdr()]]);
        $this->assertSame(XdrSCValType::SCV_VOID, $tx->getSimulationData()->returnedValue->type->getValue());
    }

    private function buildSimulationResponse(array|string|\Throwable $response): AssembledTransaction
    {
        $reply = $response instanceof \Throwable ? $response : new Response(200, [],
            is_array($response) ? json_encode($response, JSON_THROW_ON_ERROR) : $response);
        $mock = new MockHandler([$this->accountResponse(self::TEST_ACCOUNT_ID, 123), $reply]);
        $history = [];
        try {
            return AssembledTransaction::build($this->readOnlyTestOptions(self::TEST_ACCOUNT_ID, $mock, $history, false));
        } finally {
            $this->assertSame(['getLedgerEntries', 'simulateTransaction'], $this->readOnlyRequestMethods($history));
            $this->assertSame(0, $mock->count());
        }
    }

    private function simulationFailure(AssembledTransaction $tx): Exception
    {
        try {
            $tx->getSimulationData();
        } catch (Exception $error) {
            return $error;
        }
        $this->fail('Expected a simulation failure');
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
        );
    }

    private function createClientOptionsWithSecret(): ClientOptions
    {
        return new ClientOptions(
            sourceAccountKeyPair: $this->testKeyPairWithSecret,
            contractId: self::TEST_CONTRACT_ID,
            network: $this->testNetwork,
            rpcUrl: self::TEST_RPC_URL,
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
        $tx = $this->createWriteReadyAssembledTransaction();

        $mock = $this->injectMockedServer($tx, [
            $this->createSendTransactionStatusResponse('PENDING'),
            $this->createGetTransactionSuccessResponse(),
        ]);

        $response = $tx->signAndSend();

        $this->assertSame(GetTransactionResponse::STATUS_SUCCESS, $response->status);
        $this->assertNotNull($tx->signed);
        $this->assertCount(1, $tx->signed->getSignatures());
        $this->assertSame(0, $mock->count());
    }

    /**
     * Builds a signable write-type AssembledTransaction whose simulation state
     * is prefilled, ready for signAndSend against an injected mock server.
     */
    private function createWriteReadyAssembledTransaction(): AssembledTransaction
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
        $tx->simulationResponse = $this->createMockSimulationResponse();

        $reflection = new \ReflectionClass($tx);
        $property = $reflection->getProperty('simulationResult');
        $footprint = new XdrLedgerFootprint([], [new XdrLedgerKey(XdrLedgerEntryType::ACCOUNT())]);
        $resources = new XdrSorobanResources($footprint, 100, 100, 100);
        $ext = new XdrSorobanTransactionDataExt(0);
        $txData = new XdrSorobanTransactionData($ext, $resources, 100);
        $property->setValue($tx, new SimulateHostFunctionResult($txData, XdrSCVal::forVoid(), []));

        return $tx;
    }

    /**
     * Creates a mock sendTransaction response with the given status.
     */
    private function createSendTransactionStatusResponse(
        string $status,
        ?string $errorResultXdr = null,
        ?array $diagnosticEventsXdr = null,
    ): Response
    {
        $result = [
            'status' => $status,
            'hash' => str_repeat('ab', 32),
            'latestLedger' => 1000,
            'latestLedgerCloseTime' => '1700000000',
        ];
        if ($errorResultXdr !== null) {
            $result['errorResultXdr'] = $errorResultXdr;
        }
        if ($diagnosticEventsXdr !== null) {
            $result['diagnosticEventsXdr'] = $diagnosticEventsXdr;
        }
        return new Response(200, [], json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => $result,
        ]));
    }

    public function testSignAndSendPollsDuplicateSubmission(): void
    {
        // A DUPLICATE response names a transaction the network already knows,
        // so the submission polls to its true outcome instead of failing.
        $tx = $this->createWriteReadyAssembledTransaction();
        $mock = $this->injectMockedServer($tx, [
            $this->createSendTransactionStatusResponse('DUPLICATE'),
            $this->createGetTransactionSuccessResponse(),
        ]);

        $started = hrtime(true);
        $response = $tx->signAndSend();
        $this->assertLessThan(1.0, (hrtime(true) - $started) / 1e9);

        $this->assertSame(GetTransactionResponse::STATUS_SUCCESS, $response->status);
        $this->assertSame(0, $mock->count());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pollErrorKinds')]
    public function testPollRecoversFromStatusError(bool $transport): void
    {
        $tx = $this->createWriteReadyAssembledTransaction();
        $tx->options->methodOptions->timeoutInSeconds = 1;
        $error = $transport
            ? new \GuzzleHttp\Exception\ConnectException('status connection lost', new \GuzzleHttp\Psr7\Request('POST', self::TEST_RPC_URL))
            : new Response(200, [], '{"error":{"code":-32000,"message":"status unavailable"}}');
        $mock = $this->injectMockedServer($tx, [
            $this->createSendTransactionStatusResponse('PENDING'), $error,
            $this->createGetTransactionSuccessResponse(),
        ]);
        $this->assertSame(GetTransactionResponse::STATUS_SUCCESS, $tx->signAndSend()->status);
        $this->assertSame(0, $mock->count());
    }

    public static function pollErrorKinds(): array
    {
        return ['transport' => [true], 'RPC' => [false]];
    }

    public function testPollDeadlineNamesLastErrorAndBoundsRequests(): void
    {
        $tx = $this->createWriteReadyAssembledTransaction();
        $tx->options->methodOptions->timeoutInSeconds = 4;
        $history = [];
        $mock = $this->injectMockedServer($tx, [
            $this->createSendTransactionStatusResponse('PENDING'),
            new \GuzzleHttp\Exception\ConnectException('first error', new \GuzzleHttp\Psr7\Request('POST', self::TEST_RPC_URL)),
            new Response(200, [], '{"error":{"code":-32001,"message":"last RPC error"}}'),
            new Response(200, [], '{"result":{"status":"NOT_FOUND"}}'),
        ], $history);
        $started = hrtime(true);
        try {
            $tx->signAndSend();
            $this->fail('Expected the polling deadline to expire');
        } catch (Exception $e) {
            $elapsed = (hrtime(true) - $started) / 1e9;
            $this->assertGreaterThanOrEqual(4.0, $elapsed);
            $this->assertLessThan(6.0, $elapsed);
            $this->assertStringContainsString('Interrupted after waiting 4 seconds', $e->getMessage());
            $this->assertStringContainsString('options->methodOptions->timeoutInSeconds', $e->getMessage());
            $this->assertStringContainsString(str_repeat('ab', 32), $e->getMessage());
            $this->assertStringContainsString('Last status request error: RPC error code: -32001, message: last RPC error', $e->getMessage());
            $this->assertStringNotContainsString('first error', $e->getMessage());
        }
        $this->assertSame(0, $mock->count());
        $this->assertCount(4, $history);
        $timeouts = [];
        foreach (array_slice($history, 1) as $entry) {
            $options = $entry['options'];
            $this->assertArrayHasKey('timeout', $options);
            $this->assertArrayHasKey('connect_timeout', $options);
            $this->assertGreaterThan(0, $options['timeout']);
            $this->assertSame($options['timeout'], $options['connect_timeout']);
            $timeouts[] = $options['timeout'];
        }
        $this->assertLessThan(4.0, $timeouts[0]);
        $this->assertGreaterThan(3.0, $timeouts[0]);
        $this->assertLessThan($timeouts[0], $timeouts[1]);
        $this->assertSame(3.0, $timeouts[2]);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pollRequestExceptions')]
    public function testPollDeadlineRetainsRequestError(Response|\Throwable $error, string $message): void
    {
        $tx = $this->createWriteReadyAssembledTransaction();
        $tx->options->methodOptions->timeoutInSeconds = 0;
        $history = [];
        $this->injectMockedServer($tx, [
            $this->createSendTransactionStatusResponse('PENDING'), $error,
        ], $history, ['http_errors' => false]);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Last status request error: ' . $message);
        $tx->signAndSend();
    }

    public static function pollRequestExceptions(): array
    {
        return [
            'GuzzleException' => [new class('status connection lost') extends Exception implements \GuzzleHttp\Exception\GuzzleException {}, 'status connection lost'],
            'RuntimeException' => [new Response(503, [], 'status unavailable'), 'status unavailable'],
            'InvalidArgumentException' => [new Response(200, [], '<html>unavailable</html>'), 'Error in json_decode: Syntax error'],
        ];
    }

    public function testPollExpiredDeadlineWithoutError(): void
    {
        $tx = $this->createWriteReadyAssembledTransaction();
        $tx->options->methodOptions->timeoutInSeconds = 0;
        $history = [];
        $this->injectMockedServer($tx, [
            $this->createSendTransactionStatusResponse('PENDING'),
            new Response(200, [], '{"result":{"status":"NOT_FOUND"}}'),
        ], $history);
        try {
            $tx->signAndSend();
            $this->fail('Expected the polling deadline to expire');
        } catch (Exception $e) {
            $this->assertStringContainsString('Interrupted after waiting 0 seconds', $e->getMessage());
            $this->assertStringNotContainsString('Last status request error', $e->getMessage());
        }
        $options = $history[1]['options'];
        $this->assertArrayHasKey('timeout', $options);
        $this->assertArrayHasKey('connect_timeout', $options);
        $this->assertSame(3.0, $options['timeout']);
        $this->assertSame(3.0, $options['connect_timeout']);
    }

    public function testPollReturnsFailedStatus(): void
    {
        $tx = $this->createWriteReadyAssembledTransaction();
        $tx->options->methodOptions->timeoutInSeconds = 0;
        $mock = $this->injectMockedServer($tx, [
            $this->createSendTransactionStatusResponse('PENDING'),
            new Response(200, [], '{"result":{"status":"FAILED"}}'),
        ]);
        $this->assertSame(GetTransactionResponse::STATUS_FAILED, $tx->signAndSend()->status);
        $this->assertSame(0, $mock->count());
    }

    public function testSignAndSendThrowsOnTryAgainLater(): void
    {
        // TRY_AGAIN_LATER means the network did not accept the transaction, so
        // polling its hash cannot find a result.
        $tx = $this->createWriteReadyAssembledTransaction();
        $this->injectMockedServer($tx, [
            $this->createSendTransactionStatusResponse('TRY_AGAIN_LATER'),
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Send transaction failed with status: TRY_AGAIN_LATER');
        $tx->signAndSend();
    }

    public function testSignAndSendThrowsOnErrorNamingResultXdr(): void
    {
        // A diagnostic event with an "error" topic and a string payload,
        // generated through the SDK's own XDR types.
        $diagnosticEventXdr =
            'AAAAAAAAAAAAAAAAAAAAAgAAAAAAAAABAAAADwAAAAVlcnJvcgAAAAAAAA4AAAAUaW5zdWZmaWNpZW50IGJhbGFuY2U=';
        $tx = $this->createWriteReadyAssembledTransaction();
        $this->injectMockedServer($tx, [
            $this->createSendTransactionStatusResponse(
                'ERROR',
                'AAAAAAAAAGT////7AAAAAA==',
                [$diagnosticEventXdr],
            ),
        ]);

        try {
            $tx->signAndSend();
        } catch (Exception $e) {
            $this->assertStringContainsString(
                'Send transaction failed with status: ERROR, error result xdr: AAAAAAAAAGT////7AAAAAA==',
                $e->getMessage()
            );
            $this->assertStringContainsString(
                'diagnostic events: ' . $diagnosticEventXdr,
                $e->getMessage()
            );
            return;
        }
        $this->fail('An ERROR submission must not be polled');
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

    public function testBuildAndBuildWithOpRejectNonContractTargetBeforeAnyRequest(): void
    {
        $mock = new MockHandler([]);
        $server = new SorobanServer(self::TEST_RPC_URL, new Client(['handler' => HandlerStack::create($mock)]));
        $muxed = 'WA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAAAAAAAAAPCIA6IG';
        $muxedAccount = 'MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUQ';
        $account = 'GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ';
        $prefix = 'The contract to invoke must be a contract (C...) address';
        $cases = [
            $muxed => "$prefix, got a muxed contract (W...) address: $muxed; use its base contract (C...) address",
            $muxedAccount => "$prefix, got a muxed account (M...) address: $muxedAccount",
            $account => "$prefix, got an account (G...) address: $account",
        ];
        foreach ($cases as $id => $message) {
            $clientOptions = new ClientOptions(sourceAccountKeyPair: $this->testKeyPair, contractId: $id,
                network: $this->testNetwork, rpcUrl: self::TEST_RPC_URL, server: $server);
            try {
                AssembledTransaction::build(new AssembledTransactionOptions(clientOptions: $clientOptions,
                    methodOptions: new MethodOptions(simulate: false), method: 'test_method'));
                $this->fail('Accepted ' . $id);
            } catch (InvalidArgumentException $e) {
                $this->assertSame($message, $e->getMessage());
            }
        }

        // A host function decoded from XDR keeps a muxed target, which buildWithOp refuses.
        $decoded = InvokeContractHostFunction::fromXdr(XdrHostFunction::forInvokingContractWithArgs(
            new XdrInvokeContractArgs(XdrSCAddress::forMuxedContractId($muxed), 'test_method', [])));
        $clientOptions = new ClientOptions(sourceAccountKeyPair: $this->testKeyPair, contractId: self::TEST_CONTRACT_ID,
            network: $this->testNetwork, rpcUrl: self::TEST_RPC_URL, server: $server);
        try {
            AssembledTransaction::buildWithOp(new InvokeHostFunctionOperation($decoded), new AssembledTransactionOptions(
                clientOptions: $clientOptions, methodOptions: new MethodOptions(simulate: false), method: 'test_method'));
            $this->fail('buildWithOp accepted ' . $muxed);
        } catch (InvalidArgumentException $e) {
            $this->assertSame($cases[$muxed], $e->getMessage());
        }
        $this->assertNull($mock->getLastRequest());
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

        $logger = new NullLogger();
        $options = new ClientOptions(
            sourceAccountKeyPair: $keyPair,
            contractId: $contractId,
            network: $network,
            rpcUrl: $rpcUrl,
            logger: $logger
        );

        $this->assertSame($keyPair, $options->sourceAccountKeyPair);
        $this->assertEquals($contractId, $options->contractId);
        $this->assertSame($network, $options->network);
        $this->assertEquals($rpcUrl, $options->rpcUrl);
        $this->assertSame($logger, $options->logger);
    }

    // ---------------------------------------------------------------------
    // read-only transactions
    // ---------------------------------------------------------------------

    private const ZERO_ACCOUNT_ID = 'GAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWHF';

    public function testAssembledTransactionOptionsReadOnlyDefaultsToFalse(): void
    {
        $options = new AssembledTransactionOptions(
            clientOptions: $this->createClientOptions(),
            methodOptions: new MethodOptions(),
            method: 'test',
        );
        $this->assertFalse($options->readOnly);
    }

    public function testReadOnlyBuildSendsOnlySimulateWithSourceAtSequenceOne(): void
    {
        $accountId = KeyPair::random()->getAccountId();
        $history = [];
        $mock = new MockHandler([$this->readOnlySimulateResponse(false, false)]);

        $tx = AssembledTransaction::build($this->readOnlyTestOptions($accountId, $mock, $history, true));

        $this->assertSame(['simulateTransaction'], $this->readOnlyRequestMethods($history));
        $this->assertSame(0, $mock->count());
        $this->assertNotNull($tx->tx);
        $this->assertSame($accountId, $tx->tx->getSourceAccount()->getAccountId());
        $this->assertSame('1', $tx->tx->getSequenceNumber()->toString());
        $body = json_decode((string)$history[0]['request']->getBody(), true);
        $sent = Transaction::fromEnvelopeBase64XdrString($body['params']['transaction']);
        $this->assertInstanceOf(Transaction::class, $sent);
        $this->assertSame($accountId, $sent->getSourceAccount()->getAccountId());
        $this->assertSame('1', $sent->getSequenceNumber()->toString());
    }

    public function testReadOnlySignRefusesWriteTransaction(): void
    {
        $accountId = KeyPair::random()->getAccountId();
        $history = [];
        $mock = new MockHandler([$this->readOnlySimulateResponse(true, false)]);
        $tx = AssembledTransaction::build($this->readOnlyTestOptions($accountId, $mock, $history, true));
        $this->assertFalse($tx->isReadCall());

        $attempts = [
            'sign()' => fn () => $tx->sign(),
            'sign(force: true)' => fn () => $tx->sign(force: true),
            'sign(keypair)' => fn () => $tx->sign(KeyPair::random()),
            'signAndSend()' => fn () => $tx->signAndSend(),
            'signAndSend(keypair, force)' => fn () => $tx->signAndSend(KeyPair::random(), true),
        ];
        foreach ($attempts as $label => $attempt) {
            try {
                $attempt();
                $this->fail("$label did not throw");
            } catch (Exception $e) {
                $this->assertSame($this->readOnlyRefusalMessage($accountId), $e->getMessage(), $label);
            }
            $this->assertNull($tx->signed, $label);
        }
        $this->assertSame(['simulateTransaction'], $this->readOnlyRequestMethods($history));
    }

    public function testReadOnlySendRefusesHandSignedTransactionBeforeAnyRequest(): void
    {
        $accountId = KeyPair::random()->getAccountId();
        $history = [];
        $mock = new MockHandler([$this->readOnlySimulateResponse(true, false)]);
        $tx = AssembledTransaction::build($this->readOnlyTestOptions($accountId, $mock, $history, true));
        $this->assertNotNull($tx->tx);
        $signed = clone $tx->tx;
        $signed->sign(KeyPair::random(), $this->testNetwork);
        $tx->signed = $signed;

        $attempts = [
            'send()' => fn () => $tx->send(),
            'signAndSend()' => fn () => $tx->signAndSend(),
        ];
        foreach ($attempts as $label => $attempt) {
            try {
                $attempt();
                $this->fail("$label did not throw");
            } catch (Exception $e) {
                $this->assertSame($this->readOnlyRefusalMessage($accountId), $e->getMessage(), $label);
            }
            $this->assertSame($signed, $tx->signed, $label);
        }
        $this->assertSame(['simulateTransaction'], $this->readOnlyRequestMethods($history));
        $this->assertSame(0, $mock->count());
    }

    public function testReadOnlyRestoreFootprintRefusesWithoutRequest(): void
    {
        $accountId = KeyPair::random()->getAccountId();
        $history = [];
        $mock = new MockHandler([]);
        $tx = AssembledTransaction::build($this->readOnlyTestOptions($accountId, $mock, $history, true,
            new MethodOptions(simulate: false)));

        try {
            $tx->restoreFootprint(new RestorePreamble($this->createMockTransactionData(), 50));
            $this->fail('restoreFootprint did not throw');
        } catch (Exception $e) {
            $this->assertSame($this->readOnlyRefusalMessage($accountId), $e->getMessage());
        }
        $this->assertSame([], $history);
    }

    public function testReadOnlySimulateWithRestorePreambleRefusesBeforePrivateKeyCheck(): void
    {
        $accountId = KeyPair::random()->getAccountId();
        $history = [];
        $mock = new MockHandler([$this->readOnlySimulateResponse(true, true)]);

        try {
            AssembledTransaction::build($this->readOnlyTestOptions($accountId, $mock, $history, true));
            $this->fail('build did not throw');
        } catch (Exception $e) {
            $this->assertSame($this->readOnlyRefusalMessage($accountId), $e->getMessage());
        }
        $this->assertSame(['simulateTransaction'], $this->readOnlyRequestMethods($history));
    }

    public function testReadOnlySimulateWithRestoreOffKeepsPreamble(): void
    {
        $accountId = KeyPair::random()->getAccountId();
        $history = [];
        $mock = new MockHandler([$this->readOnlySimulateResponse(false, true)]);

        $tx = AssembledTransaction::build($this->readOnlyTestOptions($accountId, $mock, $history, true,
            new MethodOptions(restore: false)));

        $this->assertNotNull($tx->simulationResponse);
        $this->assertNotNull($tx->simulationResponse->restorePreamble);
        $this->assertSame(['simulateTransaction'], $this->readOnlyRequestMethods($history));
    }

    public function testReadOnlySignAfterReadSimulationKeepsReadCallMessage(): void
    {
        $history = [];
        $mock = new MockHandler([$this->readOnlySimulateResponse(false, false)]);
        $tx = AssembledTransaction::build($this->readOnlyTestOptions(KeyPair::random()->getAccountId(), $mock,
            $history, true));

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote('This is a read call. It requires no signature or sending. '
            . 'Use `force: true` to sign and send anyway.', '/') . '\z/');
        $tx->sign();
    }

    public function testZeroKeySourceWithoutReadOnlyLoadsAccountFirst(): void
    {
        $history = [];
        $mock = new MockHandler([
            $this->accountResponse(self::ZERO_ACCOUNT_ID, 123),
            $this->readOnlySimulateResponse(false, false),
        ]);

        $tx = AssembledTransaction::build($this->readOnlyTestOptions(self::ZERO_ACCOUNT_ID, $mock, $history, false));

        $this->assertSame(['getLedgerEntries', 'simulateTransaction'], $this->readOnlyRequestMethods($history));
        $body = json_decode((string)$history[0]['request']->getBody(), true);
        $key = XdrLedgerKey::fromBase64Xdr($body['params']['keys'][0]);
        $this->assertNotNull($key->account);
        $this->assertSame(self::ZERO_ACCOUNT_ID, $key->account->accountID->getAccountId());
        $this->assertNotNull($tx->tx);
        $this->assertSame('124', $tx->tx->getSequenceNumber()->toString());
    }

    private function readOnlyRefusalMessage(string $accountId): string
    {
        return "This transaction was built by a read-only client for source account {$accountId}, which has no "
            . "keypair to sign with; build it from a client opened with the submitting account's keypair";
    }

    private function readOnlyTestOptions(string $accountId, MockHandler $mock, array &$history, bool $readOnly,
                                         ?MethodOptions $methodOptions = null): AssembledTransactionOptions
    {
        $stack = HandlerStack::create($mock);
        $stack->push(\GuzzleHttp\Middleware::history($history));
        $server = new SorobanServer(self::TEST_RPC_URL, new Client(['handler' => $stack]));
        $clientOptions = new ClientOptions(
            sourceAccountKeyPair: KeyPair::fromAccountId($accountId),
            contractId: self::TEST_CONTRACT_ID,
            network: $this->testNetwork,
            rpcUrl: self::TEST_RPC_URL,
            server: $server,
        );
        return new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions ?? new MethodOptions(),
            method: 'transfer',
            arguments: [XdrSCVal::forU32(1)],
            readOnly: $readOnly,
        );
    }

    /**
     * @return array<string>
     */
    private function readOnlyRequestMethods(array $history): array
    {
        return array_map(
            fn (array $entry) => json_decode((string)$entry['request']->getBody(), true)['method'],
            $history,
        );
    }

    private function readOnlySimulateResponse(bool $writeCall, bool $restorePreamble): Response
    {
        $readWrite = [];
        if ($writeCall) {
            $ledgerKey = new XdrLedgerKey(XdrLedgerEntryType::ACCOUNT());
            $ledgerKey->account = new XdrLedgerKeyAccount(new XdrAccountID(self::TEST_ACCOUNT_ID));
            $readWrite[] = $ledgerKey;
        }
        $transactionData = new XdrSorobanTransactionData(new XdrSorobanTransactionDataExt(0),
            new XdrSorobanResources(new XdrLedgerFootprint([], $readWrite), 0, 0, 0), 0);
        $result = [
            'minResourceFee' => '100',
            'latestLedger' => 1000,
            'transactionData' => $transactionData->toBase64Xdr(),
            'results' => [['auth' => [], 'xdr' => XdrSCVal::forVoid()->toBase64Xdr()]],
        ];
        if ($restorePreamble) {
            $result['restorePreamble'] = [
                'transactionData' => $this->createMockTransactionData()->toBase64Xdr(),
                'minResourceFee' => '50',
            ];
        }
        return new Response(200, [], json_encode(['jsonrpc' => '2.0', 'id' => 1, 'result' => $result]));
    }

    private function accountResponse(string $accountId, int $sequence): Response
    {
        $entryData = new XdrLedgerEntryData(XdrLedgerEntryType::ACCOUNT());
        $entryData->account = new XdrAccountEntry(
            accountID: new XdrAccountID($accountId),
            balance: new BigInteger(100000000),
            seqNum: new XdrSequenceNumber(new BigInteger($sequence)),
            numSubEntries: 0,
            flags: 0,
            homeDomain: '',
            thresholds: "\x01\x00\x00\x00",
            signers: [],
            ext: new XdrAccountEntryExt(0),
        );
        return new Response(200, [], json_encode(['jsonrpc' => '2.0', 'id' => 1, 'result' => [
            'entries' => [['key' => 'AAAAAA==', 'xdr' => $entryData->toBase64Xdr(), 'lastModifiedLedgerSeq' => 1000]],
            'latestLedger' => 1000,
        ]]));
    }

    private const FAILED_RESTORE_RESULT_XDR = 'AAAAAAAAE+z/////AAAAAQAAAAAAAAAa/////gAAAAA=';
    private const CALLER_SOURCE_ID = 'GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ';
    private const RESTORE_REQUESTS = ['getLedgerEntries', 'simulateTransaction', 'sendTransaction', 'getTransaction',
        'getLedgerEntries'];

    public function testSuccessfulRestoreReSimulatesOnTheCurrentSequenceNumber(): void
    {
        $history = [];
        $mock = new MockHandler([
            $this->accountResponse(self::TEST_ACCOUNT_ID, 123456789),
            $this->simulation($this->sorobanData(11000, [self::ZERO_ACCOUNT_ID]), [$this->authEntry('first')], true),
            ...$this->restoreResponses(123456789),
            $this->simulation($this->sorobanData(12000, [self::TEST_ACCOUNT_ID]), [$this->authEntry('again')]),
        ]);

        $tx = AssembledTransaction::build($this->restoreOptions($mock, $history, new MethodOptions()));

        $this->assertSame(['getLedgerEntries', 'simulateTransaction', ...self::RESTORE_REQUESTS, 'simulateTransaction'],
            $this->readOnlyRequestMethods($history));
        $this->assertSame(0, $mock->count());
        $this->assertInstanceOf(RestoreFootprintOperation::class,
            $this->requestedTransaction($history, 4)->getOperations()[0]);
        $this->assertSame('123456791', $this->requestedTransaction($history, 7)->getSequenceNumber()->toString());
        $this->assertNotNull($tx->tx);
        $this->assertSame('123456791', $tx->tx->getSequenceNumber()->toString());
        $this->assertSame(12100, $tx->tx->getFee());
        $this->assertSame($this->sorobanData(12000, [self::TEST_ACCOUNT_ID])->toBase64Xdr(),
            $tx->tx->getSorobanTransactionData()?->toBase64Xdr());
        $this->assertSame([$this->authEntry('again')->toBase64Xdr()], $this->authOf($tx->tx));
        $this->assertSame(12000, $tx->simulationResponse?->minResourceFee);
        $this->assertNull($tx->simulationResponse?->restorePreamble);
    }

    public function testSuccessfulRestoreReSimulatesTheBuildWithOpOperation(): void
    {
        $history = [];
        $mock = new MockHandler([
            $this->accountResponse(self::TEST_ACCOUNT_ID, 123456789),
            $this->simulation($this->sorobanData(11000, []), [], true),
            ...$this->restoreResponses(123456789),
            $this->simulation($this->sorobanData(12000, [self::TEST_ACCOUNT_ID])),
        ]);
        $deploy = new CreateContractWithConstructorHostFunction(Address::fromAccountId(self::TEST_ACCOUNT_ID),
            str_repeat('ab', 32), [XdrSCVal::forU32(7)], str_repeat("\x05", 32));

        // The options name no contract, as for a deployment.
        $tx = AssembledTransaction::buildWithOp(new InvokeHostFunctionOperation($deploy),
            $this->restoreOptions($mock, $history, new MethodOptions(), 'ignored', '__constructor'));

        $this->assertSame(0, $mock->count());
        foreach (['request' => $this->requestedTransaction($history, 7), 'tx' => $tx->tx] as $label => $transaction) {
            $this->assertSame('123456791', $transaction?->getSequenceNumber()->toString(), $label);
            $function = $transaction?->getOperations()[0]->function;
            $this->assertInstanceOf(CreateContractWithConstructorHostFunction::class, $function, $label);
            $this->assertSame(str_repeat('ab', 32), $function->wasmId, $label);
            $this->assertSame(str_repeat("\x05", 32), $function->salt, $label);
            $this->assertSame(7, $function->constructorArgs[0]->u32, $label);
        }
    }

    public function testSuccessfulRestoreKeepsTheCallerBuiltRawWithItsMuxedSourceAndAuth(): void
    {
        $history = [];
        $mock = new MockHandler([
            $this->accountResponse(self::TEST_ACCOUNT_ID, 123456789),
            $this->simulation($this->sorobanData(11000, []), [], true),
            ...$this->restoreResponses(123456789, sourceAfter: self::CALLER_SOURCE_ID),
            $this->simulation($this->sorobanData(12000, [self::TEST_ACCOUNT_ID]), [$this->authEntry('again')]),
        ]);
        $tx = AssembledTransaction::build($this->restoreOptions($mock, $history, new MethodOptions(simulate: false)));
        $caller = $this->authEntry('caller');
        $operation = (new InvokeHostFunctionOperationBuilder(new InvokeContractHostFunction(self::TEST_CONTRACT_ID,
            'custom_call', [XdrSCVal::forU32(7)]), [$caller]))->build();
        $tx->raw = (new TransactionBuilder(new Account(self::CALLER_SOURCE_ID, new BigInteger(555), 42)))
            ->addOperation($operation);

        $tx->simulate();

        $this->assertSame(0, $mock->count());
        $accountRequest = json_decode((string)$history[6]['request']->getBody(), true);
        $this->assertSame(self::CALLER_SOURCE_ID,
            XdrLedgerKey::fromBase64Xdr($accountRequest['params']['keys'][0])->account?->accountID->getAccountId());
        foreach (['request' => $this->requestedTransaction($history, 7), 'tx' => $tx->tx] as $label => $transaction) {
            $this->assertSame('123456791', $transaction?->getSequenceNumber()->toString(), $label);
            $this->assertSame(42, $transaction?->getSourceAccount()->getId(), $label);
            $this->assertSame(self::CALLER_SOURCE_ID, $transaction?->getSourceAccount()->getEd25519AccountId(), $label);
            $function = $transaction?->getOperations()[0]->function;
            $this->assertInstanceOf(InvokeContractHostFunction::class, $function, $label);
            $this->assertSame('custom_call', $function->functionName, $label);
            $this->assertSame(7, $function->arguments[0]->u32, $label);
        }
        $this->assertSame([$caller->toBase64Xdr()], $this->authOf($this->requestedTransaction($history, 7)));
        $this->assertSame([$this->authEntry('again')->toBase64Xdr()], $this->authOf($tx->tx));
        $this->assertNotSame($operation, $tx->tx?->getOperations()[0]);
        $this->assertSame([$caller], $operation->auth);
    }

    public function testSuccessfulRestoreNamesTheMissingSourceAccountOfTheTransaction(): void
    {
        $history = [];
        $restore = $this->restoreResponses(123456789);
        $restore[4] = new Response(200, [], json_encode(['jsonrpc' => '2.0', 'id' => 1,
            'result' => ['entries' => [], 'latestLedger' => 1000]]));
        $mock = new MockHandler([
            $this->accountResponse(self::TEST_ACCOUNT_ID, 123456789),
            $this->simulation($this->sorobanData(11000, []), [], true),
            ...$restore,
        ]);
        $tx = AssembledTransaction::build($this->restoreOptions($mock, $history, new MethodOptions(simulate: false)));
        $tx->raw = (new TransactionBuilder(new Account(self::CALLER_SOURCE_ID, new BigInteger(555))))
            ->addOperation((new InvokeHostFunctionOperationBuilder(new InvokeContractHostFunction(
                self::TEST_CONTRACT_ID, 'custom_call')))->build());

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote('Account ' . self::CALLER_SOURCE_ID . ' not found', '/')
            . '\z/');
        $tx->simulate();
    }

    public function testSimulateWithRestoreTrueRestoresEachPreambleWhenTheOptionIsFalse(): void
    {
        $history = [];
        $mock = new MockHandler([
            $this->accountResponse(self::TEST_ACCOUNT_ID, 100),
            $this->simulation($this->sorobanData(11000, []), [], true),
            ...$this->restoreResponses(100),
            $this->simulation($this->sorobanData(11000, []), [], true),
            ...$this->restoreResponses(101),
            $this->simulation($this->sorobanData(12000, [self::TEST_ACCOUNT_ID])),
        ]);
        $tx = AssembledTransaction::build($this->restoreOptions($mock, $history,
            new MethodOptions(simulate: false, restore: false)));

        $tx->simulate(true);

        $this->assertSame(['getLedgerEntries', 'simulateTransaction', ...self::RESTORE_REQUESTS, 'simulateTransaction',
            ...self::RESTORE_REQUESTS, 'simulateTransaction'], $this->readOnlyRequestMethods($history));
        $this->assertSame(0, $mock->count());
        $this->assertSame('103', $tx->tx?->getSequenceNumber()->toString());
        $this->assertNull($tx->simulationResponse?->restorePreamble);
    }

    public function testSuccessfulRestoreKeepsTheMemoPreconditionsAndInclusionFee(): void
    {
        $history = [];
        $mock = new MockHandler([
            $this->accountResponse(self::TEST_ACCOUNT_ID, 123456789),
            $this->simulation($this->sorobanData(11000, []), [], true),
            ...$this->restoreResponses(123456789),
            $this->simulation($this->sorobanData(12000, [self::TEST_ACCOUNT_ID])),
        ]);
        $tx = AssembledTransaction::build($this->restoreOptions($mock, $history, new MethodOptions(simulate: false)));
        $preconditions = new TransactionPreconditions();
        $preconditions->setTimeBounds(new TimeBounds(new DateTime('@1700000000'), new DateTime('@1900000000')));
        $preconditions->setLedgerBounds(new LedgerBounds(1000, 2000));
        $tx->raw?->addMemo(Memo::text('order 7'))->setPreconditions($preconditions)->setMaxOperationFee(200000);

        $tx->simulate();

        $this->assertSame(0, $mock->count());
        $requested = $this->requestedTransaction($history, 7);
        $this->assertSame(200000, $requested->getFee());
        foreach (['request' => $requested, 'tx' => $tx->tx] as $label => $transaction) {
            $this->assertSame('123456791', $transaction?->getSequenceNumber()->toString(), $label);
            $this->assertSame('order 7', $transaction?->getMemo()->getValue(), $label);
            $this->assertSame(1000, $transaction?->getPreconditions()?->getLedgerBounds()?->getMinLedger(), $label);
            $this->assertSame(2000, $transaction?->getPreconditions()?->getLedgerBounds()?->getMaxLedger(), $label);
            $this->assertSame(1700000000, $transaction?->getTimeBounds()?->getMinTime()->getTimestamp(), $label);
            $this->assertSame(1900000000, $transaction?->getTimeBounds()?->getMaxTime()->getTimestamp(), $label);
        }
        $this->assertSame(212000, $tx->tx?->getFee());
    }

    public function testSuccessfulRestoreAfterAnEarlierSimulationStartsFromItsInclusionFee(): void
    {
        $history = [];
        $first = $this->sorobanData(11000, [self::ZERO_ACCOUNT_ID]);
        $mock = new MockHandler([
            $this->accountResponse(self::TEST_ACCOUNT_ID, 123456789),
            $this->simulation($first, [$this->authEntry('first')]),
            $this->simulation($first, [$this->authEntry('first')], true),
            ...$this->restoreResponses(123456789),
            $this->simulation($this->sorobanData(12000, [self::TEST_ACCOUNT_ID]), [$this->authEntry('again')]),
        ]);
        $tx = AssembledTransaction::build($this->restoreOptions($mock, $history, new MethodOptions()));
        $this->assertSame(11100, $tx->tx?->getFee());
        $this->assertSame($first->toBase64Xdr(), $tx->getSimulationData()->transactionData->toBase64Xdr());

        $tx->simulate();

        $this->assertSame(0, $mock->count());
        $requested = $this->requestedTransaction($history, 8);
        $this->assertSame('123456791', $requested->getSequenceNumber()->toString());
        $this->assertSame(100, $requested->getFee());
        $this->assertSame($this->sorobanData(0, [self::ZERO_ACCOUNT_ID])->toBase64Xdr(),
            $requested->getSorobanTransactionData()?->toBase64Xdr());
        $this->assertSame([], $this->authOf($requested));
        $again = $this->sorobanData(12000, [self::TEST_ACCOUNT_ID])->toBase64Xdr();
        $this->assertSame(12100, $tx->tx?->getFee());
        $this->assertSame($again, $tx->tx?->getSorobanTransactionData()?->toBase64Xdr());
        $this->assertSame([$this->authEntry('again')->toBase64Xdr()], $this->authOf($tx->tx));
        $this->assertSame($again, $tx->getSimulationData()->transactionData->toBase64Xdr());
    }

    public function testSuccessfulRestoresInARowKeepTheInclusionFeeOfAnEarlierSimulation(): void
    {
        $history = [];
        $mock = new MockHandler([
            $this->accountResponse(self::TEST_ACCOUNT_ID, 123456789),
            $this->simulation($this->contractCodeData(11000)),
            $this->simulation($this->contractCodeData(11000), [], true),
            ...$this->restoreResponses(123456789),
            $this->simulation($this->contractCodeData(11000), [], true),
            ...$this->restoreResponses(123456790),
            $this->simulation($this->contractCodeData(12000)),
        ]);
        $tx = AssembledTransaction::build($this->restoreOptions($mock, $history, new MethodOptions(simulate: false)));
        $tx->tx = new Transaction(MuxedAccount::fromAccountId(self::TEST_ACCOUNT_ID), new BigInteger(555),
            [new ExtendFootprintTTLOperation(10000)], null, null, 200000, $this->contractCodeData(0));
        $tx->simulate();
        $this->assertSame(211000, $tx->tx->getFee());
        $carried = $tx->tx->getSorobanTransactionData();

        $tx->simulate();

        $this->assertSame(0, $mock->count());
        $requests = [8 => $this->requestedTransaction($history, 8), 14 => $this->requestedTransaction($history, 14)];
        foreach ($requests as $index => $requested) {
            $this->assertSame(200000, $requested->getFee(), "request $index");
        }
        $this->assertSame(212000, $tx->tx->getFee());
        $this->assertSame('123456791', $requests[8]->getSequenceNumber()->toString());
        $this->assertSame('123456792', $requests[14]->getSequenceNumber()->toString());
        foreach ($requests as $index => $requested) {
            $this->assertSame($this->contractCodeData(0)->toBase64Xdr(),
                $requested->getSorobanTransactionData()?->toBase64Xdr(), "request $index");
        }
        $this->assertSame($this->contractCodeData(12000)->toBase64Xdr(),
            $tx->tx->getSorobanTransactionData()?->toBase64Xdr());
        $this->assertSame($this->contractCodeData(11000)->toBase64Xdr(), $carried?->toBase64Xdr());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('carriedResourceFees')]
    public function testSuccessfulRestoreSubtractsOnlyACoveredResourceFee(int $fee, int $resourceFee, int $requestFee,
                                                                         int $finalFee): void
    {
        $history = [];
        $mock = new MockHandler([
            $this->accountResponse(self::TEST_ACCOUNT_ID, 123456789),
            $this->simulation($this->sorobanData(11000, []), [], true),
            ...$this->restoreResponses(123456789),
            $this->simulation($this->sorobanData(12000, [self::TEST_ACCOUNT_ID])),
        ]);
        $tx = AssembledTransaction::build($this->restoreOptions($mock, $history, new MethodOptions(simulate: false)));
        $tx->tx = $tx->raw?->build();
        $tx->tx?->setFee($fee);
        $tx->tx?->setSorobanTransactionData($this->sorobanData($resourceFee, []));

        $tx->simulate();

        $this->assertSame($requestFee, $this->requestedTransaction($history, 7)->getFee());
        $this->assertSame($finalFee, $tx->tx?->getFee());
    }

    public static function carriedResourceFees(): array
    {
        return ['fee below the resource fee' => [100, 5000, 100, 12100],
            'fee equal to the resource fee' => [5000, 5000, 0, 12000]];
    }

    public function testSuccessfulRestoreReSimulatesAnExtendFootprintTTLWithItsFootprint(): void
    {
        $history = [];
        $footprint = $this->contractCodeData(0);
        $mock = new MockHandler([
            $this->accountResponse(self::TEST_ACCOUNT_ID, 123456789),
            $this->simulation($this->contractCodeData(11000), [], true),
            ...$this->restoreResponses(123456789),
            $this->simulation($this->contractCodeData(12000)),
        ]);
        $tx = AssembledTransaction::build($this->restoreOptions($mock, $history, new MethodOptions(simulate: false)));
        $tx->tx = new Transaction(MuxedAccount::fromAccountId(self::TEST_ACCOUNT_ID), new BigInteger(555),
            [new ExtendFootprintTTLOperation(10000)], null, null, 100, $footprint);

        $tx->simulate();

        $requested = $this->requestedTransaction($history, 7);
        $this->assertSame('123456791', $requested->getSequenceNumber()->toString());
        $extend = $requested->getOperations()[0];
        $this->assertInstanceOf(ExtendFootprintTTLOperation::class, $extend);
        $this->assertSame(10000, $extend->getExtendTo());
        $this->assertSame($footprint->toBase64Xdr(), $requested->getSorobanTransactionData()?->toBase64Xdr());
        $this->assertSame($this->contractCodeData(12000)->toBase64Xdr(),
            $tx->tx?->getSorobanTransactionData()?->toBase64Xdr());
        $this->assertSame(12100, $tx->tx?->getFee());
    }

    public function testFailedRestoreThrowsTheAutomaticRestoreFailedMessageWithoutReSimulation(): void
    {
        $history = [];
        $mock = new MockHandler([
            $this->accountResponse(self::TEST_ACCOUNT_ID, 123456789),
            $this->simulation($this->sorobanData(11000, []), [], true),
            ...$this->restoreResponses(123456789, false),
        ]);

        try {
            AssembledTransaction::build($this->restoreOptions($mock, $history, new MethodOptions()));
            $this->fail('A failed restore was accepted');
        } catch (Exception $e) {
            $this->assertSame("Automatic restore failed! You set 'restore: true' but the \n" . str_repeat(' ', 12)
                . 'attempted restore did not work. Status: FAILED , transaction result xdr: '
                . self::FAILED_RESTORE_RESULT_XDR, $e->getMessage());
        }
        $this->assertSame(['getLedgerEntries', 'simulateTransaction', 'getLedgerEntries', 'simulateTransaction',
            'sendTransaction', 'getTransaction'], $this->readOnlyRequestMethods($history));
        $this->assertSame(0, $mock->count());
    }

    private function restoreOptions(MockHandler $mock, array &$history, MethodOptions $methodOptions,
                                    string $contractId = self::TEST_CONTRACT_ID,
                                    string $method = 'test'): AssembledTransactionOptions
    {
        $stack = HandlerStack::create($mock);
        $stack->push(\GuzzleHttp\Middleware::history($history));
        $clientOptions = new ClientOptions(sourceAccountKeyPair: $this->testKeyPairWithSecret, contractId: $contractId,
            network: $this->testNetwork, rpcUrl: self::TEST_RPC_URL,
            server: new SorobanServer(self::TEST_RPC_URL, new Client(['handler' => $stack])));
        return new AssembledTransactionOptions(clientOptions: $clientOptions, methodOptions: $methodOptions,
            method: $method, arguments: []);
    }

    /**
     * The responses of one restore of the source account at the given sequence number: the account, the
     * restore simulation, its submission and status, and for a successful restore the account of the
     * transaction's source after it.
     *
     * @return array<Response>
     */
    private function restoreResponses(int $sequence, bool $succeeds = true,
                                      string $sourceAfter = self::TEST_ACCOUNT_ID): array
    {
        $responses = [
            $this->accountResponse(self::TEST_ACCOUNT_ID, $sequence),
            $this->simulation($this->sorobanData(50, [], [self::TEST_ACCOUNT_ID])),
            $this->createSendTransactionStatusResponse('PENDING'),
        ];
        if (!$succeeds) {
            return [...$responses, new Response(200, [], json_encode(['jsonrpc' => '2.0', 'id' => 1,
                'result' => ['status' => 'FAILED', 'resultXdr' => self::FAILED_RESTORE_RESULT_XDR]]))];
        }
        return [...$responses, $this->createGetTransactionSuccessResponse(),
            $this->accountResponse($sourceAfter, $sequence + 1)];
    }

    /**
     * A successful simulation whose minimum resource fee is the resource fee of its data.
     *
     * @param array<SorobanAuthorizationEntry> $auth
     */
    private function simulation(XdrSorobanTransactionData $data, array $auth = [], bool $restorePreamble = false): Response
    {
        $result = [
            'minResourceFee' => (string)$data->resourceFee,
            'latestLedger' => 1000,
            'transactionData' => $data->toBase64Xdr(),
            'results' => [['auth' => array_map(fn (SorobanAuthorizationEntry $entry) => $entry->toBase64Xdr(), $auth),
                'xdr' => XdrSCVal::forVoid()->toBase64Xdr()]],
        ];
        if ($restorePreamble) {
            $result['restorePreamble'] = ['transactionData' => $this->createMockTransactionData()->toBase64Xdr(),
                'minResourceFee' => '5000'];
        }
        return new Response(200, [], json_encode(['jsonrpc' => '2.0', 'id' => 1, 'result' => $result]));
    }

    /**
     * @param array<string> $readOnly account ids of the read-only footprint
     * @param array<string> $readWrite account ids of the read-write footprint
     */
    private function sorobanData(int $resourceFee, array $readOnly, array $readWrite = []): XdrSorobanTransactionData
    {
        $keys = fn (array $accountIds) => array_map(function (string $accountId): XdrLedgerKey {
            $key = new XdrLedgerKey(XdrLedgerEntryType::ACCOUNT());
            $key->account = new XdrLedgerKeyAccount(new XdrAccountID($accountId));
            return $key;
        }, $accountIds);
        return $this->sorobanDataForKeys($resourceFee, $keys($readOnly), $keys($readWrite));
    }

    /**
     * @param array<XdrLedgerKey> $readOnly
     * @param array<XdrLedgerKey> $readWrite
     */
    private function sorobanDataForKeys(int $resourceFee, array $readOnly, array $readWrite = []): XdrSorobanTransactionData
    {
        return new XdrSorobanTransactionData(new XdrSorobanTransactionDataExt(0),
            new XdrSorobanResources(new XdrLedgerFootprint($readOnly, $readWrite), 0, 0, 0), $resourceFee);
    }

    /**
     * Soroban data whose read-only footprint holds one contract code entry.
     */
    private function contractCodeData(int $resourceFee): XdrSorobanTransactionData
    {
        return $this->sorobanDataForKeys($resourceFee, [XdrLedgerKey::forContractCode(str_repeat("\x01", 32))]);
    }

    private function authEntry(string $function): SorobanAuthorizationEntry
    {
        return new SorobanAuthorizationEntry(SorobanCredentials::forSourceAccount(), new SorobanAuthorizedInvocation(
            SorobanAuthorizedFunction::forContractFunction(Address::fromContractId(self::TEST_CONTRACT_ID), $function)));
    }

    private function requestedTransaction(array $history, int $index): Transaction
    {
        $body = json_decode((string)$history[$index]['request']->getBody(), true);
        $transaction = Transaction::fromEnvelopeBase64XdrString($body['params']['transaction']);
        $this->assertInstanceOf(Transaction::class, $transaction);
        return $transaction;
    }

    /**
     * @return array<string> the auth entries of the transaction's invoke host function operation, as base64 XDR
     */
    private function authOf(?Transaction $transaction): array
    {
        $operation = $transaction?->getOperations()[0];
        $this->assertInstanceOf(InvokeHostFunctionOperation::class, $operation);
        return array_map(fn (SorobanAuthorizationEntry $entry) => $entry->toBase64Xdr(), $operation->auth);
    }
}
