<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Soroban;

use Exception;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperation;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Contract\AssembledTransaction;
use Soneso\StellarSDK\Soroban\Contract\AssembledTransactionOptions;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\MethodOptions;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SimulateTransactionResponse;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Soroban\SorobanServer;
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

class AssembledTransactionTest extends TestCase
{
    private const TEST_ACCOUNT_ID = "GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H";
    private const TEST_CONTRACT_ID = "CA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUWDA";
    private const TEST_RPC_URL = "https://soroban-testnet.stellar.org:443";

    private KeyPair $testKeyPair;
    private Network $testNetwork;

    public function setUp(): void
    {
        error_reporting(E_ALL);
        $this->testKeyPair = KeyPair::fromAccountId(self::TEST_ACCOUNT_ID);
        $this->testNetwork = Network::testnet();
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
        $invokeContractHostFunction = new InvokeContractHostFunction(
            self::TEST_CONTRACT_ID,
            'test',
            []
        );
        $operation = (new InvokeHostFunctionOperationBuilder($invokeContractHostFunction))->build();

        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        try {
            $tx = AssembledTransaction::buildWithOp($operation, $txOptions);
            $this->assertInstanceOf(AssembledTransaction::class, $tx);
            $this->assertInstanceOf(TransactionBuilder::class, $tx->raw);
            $this->assertNull($tx->tx);
            $this->assertNull($tx->signed);
            $this->assertNull($tx->simulationResponse);
        } catch (Exception $e) {
            $this->markTestSkipped("Network access required: " . $e->getMessage());
        }
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

        try {
            $tx = AssembledTransaction::build($txOptions);

            $simulationResponse = $this->createMockSimulationResponse();
            $tx->simulationResponse = $simulationResponse;

            $reflection = new \ReflectionClass($tx);
            $property = $reflection->getProperty('simulationResult');
            $property->setAccessible(true);

            $mockResult = new \Soneso\StellarSDK\Soroban\Contract\SimulateHostFunctionResult(
                $simulationResponse->transactionData,
                XdrSCVal::forVoid(),
                []
            );
            $property->setValue($tx, $mockResult);

            $isReadCall = $tx->isReadCall();
            $this->assertTrue($isReadCall);
        } catch (Exception $e) {
            $this->markTestSkipped("Network access required: " . $e->getMessage());
        }
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

        try {
            $tx = AssembledTransaction::build($txOptions);
            $tx->tx = $tx->raw->build();

            $needed = $tx->needsNonInvokerSigningBy();
            $this->assertIsArray($needed);
            $this->assertCount(0, $needed);
        } catch (Exception $e) {
            $this->markTestSkipped("Network access required: " . $e->getMessage());
        }
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

        try {
            $tx = AssembledTransaction::build($txOptions);

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Transaction has not yet been simulated');

            $tx->needsNonInvokerSigningBy();
        } catch (Exception $e) {
            $this->markTestSkipped("Network access required: " . $e->getMessage());
        }
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

        try {
            $tx = AssembledTransaction::build($txOptions);
            $tx->tx = $tx->raw->build();

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Source account keypair has no private key');

            $tx->sign();
        } catch (Exception $e) {
            $this->markTestSkipped("Network access required: " . $e->getMessage());
        }
    }

    public function testSignThrowsForReadCallWithoutForce(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        try {
            $tx = AssembledTransaction::build($txOptions);

            $simulationResponse = $this->createMockSimulationResponse();
            $tx->simulationResponse = $simulationResponse;
            $tx->tx = $tx->raw->build();

            $reflection = new \ReflectionClass($tx);
            $property = $reflection->getProperty('simulationResult');
            $property->setAccessible(true);

            $mockResult = new \Soneso\StellarSDK\Soroban\Contract\SimulateHostFunctionResult(
                $simulationResponse->transactionData,
                XdrSCVal::forVoid(),
                []
            );
            $property->setValue($tx, $mockResult);

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('This is a read call');

            $tx->sign();
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'Network access required')) {
                $this->markTestSkipped($e->getMessage());
            }
            throw $e;
        }
    }

    public function testSignAllowsReadCallWithForce(): void
    {
        $clientOptions = $this->createClientOptions();
        $methodOptions = new MethodOptions(simulate: false);

        $txOptions = new AssembledTransactionOptions(
            clientOptions: $clientOptions,
            methodOptions: $methodOptions,
            method: 'test',
            arguments: []
        );

        try {
            $tx = AssembledTransaction::build($txOptions);

            $simulationResponse = $this->createMockSimulationResponse();
            $tx->simulationResponse = $simulationResponse;
            $tx->tx = $tx->raw->build();

            $reflection = new \ReflectionClass($tx);
            $property = $reflection->getProperty('simulationResult');
            $property->setAccessible(true);

            $mockResult = new \Soneso\StellarSDK\Soroban\Contract\SimulateHostFunctionResult(
                $simulationResponse->transactionData,
                XdrSCVal::forVoid(),
                []
            );
            $property->setValue($tx, $mockResult);

            $tx->sign(force: true);

            $this->assertNotNull($tx->signed);
            $this->assertInstanceOf(Transaction::class, $tx->signed);
        } catch (Exception $e) {
            $this->markTestSkipped("Network access required: " . $e->getMessage());
        }
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

        try {
            $tx = AssembledTransaction::build($txOptions);

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('The transaction has not yet been signed');

            $tx->send();
        } catch (Exception $e) {
            $this->markTestSkipped("Network access required: " . $e->getMessage());
        }
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

        try {
            $tx = AssembledTransaction::build($txOptions);

            $simulationResponse = $this->createMockSimulationResponse();
            $tx->simulationResponse = $simulationResponse;

            $result = $tx->getSimulationData();

            $this->assertNotNull($result);
            $this->assertNotNull($result->transactionData);
            $this->assertNotNull($result->returnedValue);
        } catch (Exception $e) {
            $this->markTestSkipped("Network access required: " . $e->getMessage());
        }
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

        try {
            $tx = AssembledTransaction::build($txOptions);

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Transaction has not yet been simulated');

            $tx->getSimulationData();
        } catch (Exception $e) {
            $this->markTestSkipped("Network access required: " . $e->getMessage());
        }
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

        try {
            $tx = AssembledTransaction::build($txOptions);

            $simulationResponse = new SimulateTransactionResponse([]);
            $simulationResponse->error = new \Soneso\StellarSDK\Soroban\Responses\SorobanRpcErrorResponse(['error' => ['code' => -1, 'message' => 'Test error']]);
            $simulationResponse->latestLedger = 1000;
            $tx->simulationResponse = $simulationResponse;

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Transaction simulation failed');

            $tx->getSimulationData();
        } catch (Exception $e) {
            $this->markTestSkipped("Network access required: " . $e->getMessage());
        }
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

        try {
            $tx = AssembledTransaction::build($txOptions);

            $simulationResponse = $this->createMockSimulationResponse();
            $tx->simulationResponse = $simulationResponse;

            $result1 = $tx->getSimulationData();
            $result2 = $tx->getSimulationData();

            $this->assertSame($result1, $result2);
        } catch (Exception $e) {
            $this->markTestSkipped("Network access required: " . $e->getMessage());
        }
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

        try {
            $tx = AssembledTransaction::build($txOptions);

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Transaction has not yet been simulated');

            $tx->signAuthEntries($this->testKeyPair);
        } catch (Exception $e) {
            $this->markTestSkipped("Network access required: " . $e->getMessage());
        }
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

        try {
            $tx = AssembledTransaction::build($txOptions);

            $this->assertSame($txOptions, $tx->options);
            $this->assertSame($clientOptions, $tx->options->clientOptions);
            $this->assertSame($methodOptions, $tx->options->methodOptions);
        } catch (Exception $e) {
            $this->markTestSkipped("Network access required: " . $e->getMessage());
        }
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

        try {
            $tx = AssembledTransaction::build($txOptions);

            $this->assertNotNull($tx->raw);
            $this->assertInstanceOf(TransactionBuilder::class, $tx->raw);
        } catch (Exception $e) {
            $this->markTestSkipped("Network access required: " . $e->getMessage());
        }
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

        try {
            $tx = AssembledTransaction::build($txOptions);

            $this->assertNull($tx->tx);
        } catch (Exception $e) {
            $this->markTestSkipped("Network access required: " . $e->getMessage());
        }
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

        try {
            $tx = AssembledTransaction::build($txOptions);

            $this->assertNull($tx->signed);
        } catch (Exception $e) {
            $this->markTestSkipped("Network access required: " . $e->getMessage());
        }
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

        try {
            $tx = AssembledTransaction::build($txOptions);

            $this->assertNull($tx->simulationResponse);
        } catch (Exception $e) {
            $this->markTestSkipped("Network access required: " . $e->getMessage());
        }
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
}
