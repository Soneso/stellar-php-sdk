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
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperation;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Contract\AssembledTransaction;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\ContractSpec;
use Soneso\StellarSDK\Soroban\Contract\DeployRequest;
use Soneso\StellarSDK\Soroban\Contract\InstallRequest;
use Soneso\StellarSDK\Soroban\Contract\MethodOptions;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SorobanRpcErrorResponse;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Xdr\XdrAccountEntry;
use Soneso\StellarSDK\Xdr\XdrAccountEntryExt;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrExtensionPoint;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryData;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyAccount;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntry;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntryKind;
use Soneso\StellarSDK\Xdr\XdrSCSpecFunctionV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeDef;
use Soneso\StellarSDK\Xdr\XdrSCSpecType;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTStructV0;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSequenceNumber;
use Soneso\StellarSDK\Xdr\XdrSorobanResources;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionDataExt;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionMeta;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionMetaExt;
use Soneso\StellarSDK\Xdr\XdrTransactionMeta;
use Soneso\StellarSDK\Xdr\XdrTransactionMetaV3;

/**
 * Test keypair that injects a mocked Guzzle HTTP client into internally created SorobanServer instances.
 *
 * SorobanClient and AssembledTransaction construct their SorobanServer internally from the rpcUrl,
 * so there is no public seam for replacing the HTTP client before the first RPC call is made inside
 * static factory flows such as SorobanClient::install(), SorobanClient::deploy() and
 * AssembledTransaction::build(). All of those flows call sourceAccountKeyPair->getAccountId() from
 * AssembledTransaction::getSourceAccount() right before the first RPC request. This subclass uses
 * that deterministic call to locate the AssembledTransaction on the call stack and replace the
 * private httpClient of its SorobanServer with the prepared mock client. The injection is idempotent;
 * repeated calls simply re-assign the same client.
 */
class HttpInjectingKeyPair extends KeyPair
{
    private ?Client $httpClientToInject = null;

    public static function fromBaseKeyPair(KeyPair $base): HttpInjectingKeyPair
    {
        return new HttpInjectingKeyPair($base->getPublicKey(), $base->getPrivateKey());
    }

    public function setHttpClientToInject(Client $client): void
    {
        $this->httpClientToInject = $client;
    }

    public function getAccountId(): string
    {
        if ($this->httpClientToInject !== null) {
            foreach (debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 20) as $frame) {
                $object = $frame['object'] ?? null;
                if ($object instanceof AssembledTransaction) {
                    $serverProperty = new ReflectionProperty(AssembledTransaction::class, 'server');
                    $serverProperty->setAccessible(true);
                    $server = $serverProperty->getValue($object);
                    $httpClientProperty = new ReflectionProperty(SorobanServer::class, 'httpClient');
                    $httpClientProperty->setAccessible(true);
                    $httpClientProperty->setValue($server, $this->httpClientToInject);
                }
            }
        }
        return parent::getAccountId();
    }
}

/**
 * Unit tests for the SorobanClient high-level contract client.
 *
 * All RPC interactions are mocked with Guzzle MockHandler responses; no network access is required.
 * The MockHandler queue doubles as a guard: any HTTP request beyond the queued responses fails the
 * test, and an exhausted queue (count 0) proves that exactly the expected requests were made.
 *
 * Not covered here (no unit-test seam, requires a live RPC server):
 * - SorobanClient::forClientOptions() constructs a SorobanServer directly and immediately performs
 *   RPC calls, with no hook between construction and the first request.
 * - SorobanClient::deploy() success path, because it ends with a forClientOptions() call.
 */
class SorobanClientTest extends TestCase
{
    private const TEST_ACCOUNT_ID = "GD56FXQWEQ34GBKJLU52QD3YB4CJSJCVPLOKISGZDRCYVIWZK5TMVDT3";
    private const TEST_SECRET_SEED = "SAMKI63THJER2XVJA5LQXIPBWIV6FEFSS5ILURYGSCHFKZVDE5YVQWC7";
    private const TEST_CONTRACT_ID = "CA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUWDA";
    // Unroutable on purpose: all HTTP is served by the injected mock handler, and
    // if injection ever fails the request errors out locally instead of reaching
    // a live server.
    private const TEST_RPC_URL = "http://localhost:1";
    private const TEST_TX_HASH = "a4721e2a61e9a6b3f97c6b06427a2f8aacbdcbdbf30bbf52a4ce2c8fcbd2fc10";
    private const TEST_WASM_HASH = "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855";

    private Network $testNetwork;

    public function setUp(): void
    {
        error_reporting(E_ALL);
        $this->testNetwork = Network::testnet();
    }

    // ---------------------------------------------------------------------
    // Constructor and getters
    // ---------------------------------------------------------------------

    public function testConstructorExtractsMethodNamesAndSkipsConstructorAndNonFunctionEntries(): void
    {
        $client = $this->createClient();

        $this->assertSame(['hello', 'add'], $client->getMethodNames());
    }

    public function testGetters(): void
    {
        $specEntries = $this->createSpecEntries();
        $options = $this->createClientOptions();
        $client = $this->createClient($options, $specEntries);

        $this->assertSame(self::TEST_CONTRACT_ID, $client->getContractId());
        $this->assertSame($options, $client->getOptions());
        $this->assertSame($specEntries, $client->getSpecEntries());

        $contractSpec = $client->getContractSpec();
        $this->assertInstanceOf(ContractSpec::class, $contractSpec);
        $funcNames = array_map(fn($func) => $func->name, $contractSpec->funcs());
        $this->assertSame(['hello', 'add', '__constructor'], $funcNames);
    }

    // ---------------------------------------------------------------------
    // buildInvokeMethodTx
    // ---------------------------------------------------------------------

    public function testBuildInvokeMethodTxThrowsForUnknownMethod(): void
    {
        $client = $this->createClient();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Method 'transfer' does not exist");

        $client->buildInvokeMethodTx('transfer');
    }

    public function testInvokeMethodThrowsForUnknownMethod(): void
    {
        $client = $this->createClient();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Method 'burn' does not exist");

        $client->invokeMethod('burn');
    }

    public function testBuildInvokeMethodTxBuildsAndSimulatesTransaction(): void
    {
        $keyPair = HttpInjectingKeyPair::fromBaseKeyPair(KeyPair::fromAccountId(self::TEST_ACCOUNT_ID));
        $client = $this->createClient($this->createClientOptions($keyPair));

        $mock = new MockHandler([
            $this->accountEntryResponse(),
            $this->simulateResponse(XdrSCVal::forSymbol('ok')),
        ]);
        $keyPair->setHttpClientToInject($this->createHttpClient($mock));

        $args = [XdrSCVal::forU32(42)];
        $tx = $client->buildInvokeMethodTx('hello', $args);

        $this->assertSame('hello', $tx->options->method);
        $this->assertSame($args, $tx->options->arguments);

        $this->assertNotNull($tx->tx);
        $operations = $tx->tx->getOperations();
        $this->assertCount(1, $operations);
        $operation = $operations[0];
        $this->assertInstanceOf(InvokeHostFunctionOperation::class, $operation);
        $hostFunction = $operation->function;
        $this->assertInstanceOf(InvokeContractHostFunction::class, $hostFunction);
        $this->assertSame(self::TEST_CONTRACT_ID, $hostFunction->contractId);
        $this->assertSame('hello', $hostFunction->functionName);
        $this->assertNotNull($hostFunction->arguments);
        $this->assertCount(1, $hostFunction->arguments);
        $this->assertSame(42, $hostFunction->arguments[0]->getU32());

        $this->assertNotNull($tx->simulationResponse);
        $this->assertSame('ok', $tx->getSimulationData()->returnedValue->getSym());

        // exactly getAccount + simulateTransaction were requested
        $this->assertSame(0, $mock->count());
    }

    public function testBuildInvokeMethodTxHonorsMethodOptionsWithoutSimulation(): void
    {
        $keyPair = HttpInjectingKeyPair::fromBaseKeyPair(KeyPair::fromAccountId(self::TEST_ACCOUNT_ID));
        $client = $this->createClient($this->createClientOptions($keyPair));

        // only getAccount is expected; with simulate=false no simulateTransaction request may happen
        $mock = new MockHandler([$this->accountEntryResponse()]);
        $keyPair->setHttpClientToInject($this->createHttpClient($mock));

        $methodOptions = new MethodOptions(fee: 500, timeoutInSeconds: 60, simulate: false);
        $tx = $client->buildInvokeMethodTx('add', null, $methodOptions);

        $this->assertSame($methodOptions, $tx->options->methodOptions);
        $this->assertNotNull($tx->raw);
        $this->assertNull($tx->tx);
        $this->assertNull($tx->simulationResponse);
        $this->assertSame(0, $mock->count());
    }

    // ---------------------------------------------------------------------
    // invokeMethod
    // ---------------------------------------------------------------------

    public function testInvokeMethodReadCallReturnsSimulationResultWithoutSending(): void
    {
        $keyPair = HttpInjectingKeyPair::fromBaseKeyPair(KeyPair::fromAccountId(self::TEST_ACCOUNT_ID));
        $client = $this->createClient($this->createClientOptions($keyPair));

        // a read call must trigger exactly getAccount + simulateTransaction, never sendTransaction
        $mock = new MockHandler([
            $this->accountEntryResponse(),
            $this->simulateResponse(XdrSCVal::forU32(1234)),
        ]);
        $keyPair->setHttpClientToInject($this->createHttpClient($mock));

        $result = $client->invokeMethod('hello');

        $this->assertSame(1234, $result->getU32());
        $this->assertSame(0, $mock->count());
    }

    public function testInvokeMethodWriteCallSignsAndSends(): void
    {
        $args = [XdrSCVal::forU32(7)];
        $response = $this->getTransactionResponse(
            GetTransactionResponse::STATUS_SUCCESS,
            XdrSCVal::forU32(99),
        );

        $tx = $this->createMock(AssembledTransaction::class);
        $tx->method('isReadCall')->willReturn(false);
        $tx->expects($this->once())->method('signAndSend')->with(null, false)->willReturn($response);

        $client = $this->createClientWithStubbedBuild('hello', $args, null, $tx);

        $result = $client->invokeMethod('hello', $args);

        $this->assertSame(99, $result->getU32());
    }

    public function testInvokeMethodForceSkipsReadCallCheck(): void
    {
        $response = $this->getTransactionResponse(
            GetTransactionResponse::STATUS_SUCCESS,
            XdrSCVal::forSymbol('forced'),
        );

        $tx = $this->createMock(AssembledTransaction::class);
        $tx->expects($this->never())->method('isReadCall');
        $tx->expects($this->once())->method('signAndSend')->with(null, true)->willReturn($response);

        $client = $this->createClientWithStubbedBuild('hello', null, null, $tx);

        $result = $client->invokeMethod('hello', null, true);

        $this->assertSame('forced', $result->getSym());
    }

    public function testInvokeMethodThrowsOnErrorResponse(): void
    {
        $response = new GetTransactionResponse([]);
        $error = new SorobanRpcErrorResponse([]);
        $error->code = -32600;
        $error->message = 'simulated failure';
        $response->error = $error;

        $tx = $this->createMock(AssembledTransaction::class);
        $tx->method('isReadCall')->willReturn(false);
        $tx->method('signAndSend')->willReturn($response);

        $client = $this->createClientWithStubbedBuild('hello', null, null, $tx);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('invoke hello failed with message: simulated failure and code: -32600');

        $client->invokeMethod('hello');
    }

    public function testInvokeMethodThrowsOnNonSuccessStatus(): void
    {
        $response = $this->getTransactionResponse(GetTransactionResponse::STATUS_FAILED, null);
        $response->resultXdr = 'AAAAFAILEDRESULT';

        $tx = $this->createMock(AssembledTransaction::class);
        $tx->method('isReadCall')->willReturn(false);
        $tx->method('signAndSend')->willReturn($response);

        $client = $this->createClientWithStubbedBuild('hello', null, null, $tx);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('invoke hello failed with result: AAAAFAILEDRESULT');

        $client->invokeMethod('hello');
    }

    public function testInvokeMethodThrowsWhenResultValueMissing(): void
    {
        // status SUCCESS but no resultMetaXdr, so no return value can be extracted
        $response = $this->getTransactionResponse(GetTransactionResponse::STATUS_SUCCESS, null);

        $tx = $this->createMock(AssembledTransaction::class);
        $tx->method('isReadCall')->willReturn(false);
        $tx->method('signAndSend')->willReturn($response);

        $client = $this->createClientWithStubbedBuild('hello', null, null, $tx);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('could not extract return value from hello invocation');

        $client->invokeMethod('hello');
    }

    public function testInvokeMethodPassesMethodOptionsToBuild(): void
    {
        $methodOptions = new MethodOptions(fee: 1000);
        $response = $this->getTransactionResponse(
            GetTransactionResponse::STATUS_SUCCESS,
            XdrSCVal::forU32(1),
        );

        $tx = $this->createMock(AssembledTransaction::class);
        $tx->method('isReadCall')->willReturn(false);
        $tx->method('signAndSend')->willReturn($response);

        $client = $this->createClientWithStubbedBuild('add', null, $methodOptions, $tx);

        $result = $client->invokeMethod('add', null, false, $methodOptions);

        $this->assertSame(1, $result->getU32());
    }

    // ---------------------------------------------------------------------
    // install
    // ---------------------------------------------------------------------

    public function testInstallReturnsWasmHashFromSimulationWhenAlreadyInstalled(): void
    {
        $keyPair = HttpInjectingKeyPair::fromBaseKeyPair(KeyPair::fromAccountId(self::TEST_ACCOUNT_ID));

        // upload simulation of already installed code is a read call returning the wasm hash;
        // no transaction may be submitted, so only getAccount + simulateTransaction are queued
        $mock = new MockHandler([
            $this->accountEntryResponse(),
            $this->simulateResponse(XdrSCVal::forBytes(hex2bin(self::TEST_WASM_HASH))),
        ]);
        $keyPair->setHttpClientToInject($this->createHttpClient($mock));

        $wasmHash = SorobanClient::install($this->createInstallRequest($keyPair));

        $this->assertSame(self::TEST_WASM_HASH, $wasmHash);
        $this->assertSame(0, $mock->count());
    }

    public function testInstallThrowsWhenSimulationReturnsNoBytes(): void
    {
        $keyPair = HttpInjectingKeyPair::fromBaseKeyPair(KeyPair::fromAccountId(self::TEST_ACCOUNT_ID));

        $mock = new MockHandler([
            $this->accountEntryResponse(),
            $this->simulateResponse(XdrSCVal::forU32(5)),
        ]);
        $keyPair->setHttpClientToInject($this->createHttpClient($mock));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not extract wasm hash from simulation result');

        SorobanClient::install($this->createInstallRequest($keyPair));
    }

    public function testInstallForceSubmitsTransaction(): void
    {
        $keyPair = HttpInjectingKeyPair::fromBaseKeyPair(KeyPair::fromSeed(self::TEST_SECRET_SEED));

        $mock = new MockHandler([
            $this->accountEntryResponse(),
            $this->simulateResponse(XdrSCVal::forBytes(hex2bin(self::TEST_WASM_HASH))),
            $this->sendTransactionResponse(),
            $this->getTransactionRpcResponse(
                GetTransactionResponse::STATUS_SUCCESS,
                XdrSCVal::forBytes(hex2bin(self::TEST_WASM_HASH)),
            ),
        ]);
        $keyPair->setHttpClientToInject($this->createHttpClient($mock));

        $wasmHash = SorobanClient::install($this->createInstallRequest($keyPair), true);

        $this->assertSame(self::TEST_WASM_HASH, $wasmHash);
        $this->assertSame(0, $mock->count());
    }

    public function testInstallForceThrowsWhenWasmHashMissing(): void
    {
        $keyPair = HttpInjectingKeyPair::fromBaseKeyPair(KeyPair::fromSeed(self::TEST_SECRET_SEED));

        // transaction succeeds but contains no result meta, so no wasm hash can be extracted
        $mock = new MockHandler([
            $this->accountEntryResponse(),
            $this->simulateResponse(XdrSCVal::forBytes(hex2bin(self::TEST_WASM_HASH))),
            $this->sendTransactionResponse(),
            $this->getTransactionRpcResponse(GetTransactionResponse::STATUS_SUCCESS, null),
        ]);
        $keyPair->setHttpClientToInject($this->createHttpClient($mock));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not get wasm hash for installed contract');

        SorobanClient::install($this->createInstallRequest($keyPair), true);
    }

    // ---------------------------------------------------------------------
    // deploy
    // ---------------------------------------------------------------------

    public function testDeployThrowsWhenNoContractIdReturned(): void
    {
        $keyPair = HttpInjectingKeyPair::fromBaseKeyPair(KeyPair::fromSeed(self::TEST_SECRET_SEED));

        $mock = new MockHandler([
            $this->accountEntryResponse(),
            $this->simulateResponse(XdrSCVal::forVoid(), true),
            $this->sendTransactionResponse(),
            $this->getTransactionRpcResponse(GetTransactionResponse::STATUS_FAILED, null),
        ]);
        $keyPair->setHttpClientToInject($this->createHttpClient($mock));

        $deployRequest = new DeployRequest(
            rpcUrl: self::TEST_RPC_URL,
            network: $this->testNetwork,
            sourceAccountKeyPair: $keyPair,
            wasmHash: self::TEST_WASM_HASH,
            constructorArgs: [XdrSCVal::forU32(1)],
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not get contract id for deployed contract');

        SorobanClient::deploy($deployRequest);
    }

    // ---------------------------------------------------------------------
    // helpers
    // ---------------------------------------------------------------------

    /**
     * Creates a SorobanClient through its private constructor.
     *
     * @param ClientOptions|null $options client options, defaults to createClientOptions()
     * @param array<XdrSCSpecEntry>|null $specEntries spec entries, defaults to createSpecEntries()
     */
    private function createClient(?ClientOptions $options = null, ?array $specEntries = null): SorobanClient
    {
        $reflection = new ReflectionClass(SorobanClient::class);
        $client = $reflection->newInstanceWithoutConstructor();
        $constructor = $reflection->getConstructor();
        $constructor->setAccessible(true);
        $constructor->invoke($client, $specEntries ?? $this->createSpecEntries(), $options ?? $this->createClientOptions());
        return $client;
    }

    /**
     * Creates a partial SorobanClient mock whose buildInvokeMethodTx returns the given transaction,
     * so that invokeMethod can be exercised without any RPC interaction.
     *
     * @param string $method expected method name
     * @param array<XdrSCVal>|null $args expected arguments
     * @param MethodOptions|null $methodOptions expected method options
     * @param AssembledTransaction $tx transaction to return from buildInvokeMethodTx
     */
    private function createClientWithStubbedBuild(
        string $method,
        ?array $args,
        ?MethodOptions $methodOptions,
        AssembledTransaction $tx,
    ): SorobanClient {
        $client = $this->getMockBuilder(SorobanClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['buildInvokeMethodTx'])
            ->getMock();
        $client->expects($this->once())
            ->method('buildInvokeMethodTx')
            ->with($method, $args, $methodOptions)
            ->willReturn($tx);
        return $client;
    }

    private function createClientOptions(?KeyPair $keyPair = null): ClientOptions
    {
        return new ClientOptions(
            sourceAccountKeyPair: $keyPair ?? KeyPair::fromAccountId(self::TEST_ACCOUNT_ID),
            contractId: self::TEST_CONTRACT_ID,
            network: $this->testNetwork,
            rpcUrl: self::TEST_RPC_URL,
        );
    }

    private function createInstallRequest(KeyPair $keyPair): InstallRequest
    {
        return new InstallRequest(
            wasmBytes: "\x00asm\x01\x00\x00\x00",
            rpcUrl: self::TEST_RPC_URL,
            network: $this->testNetwork,
            sourceAccountKeyPair: $keyPair,
        );
    }

    /**
     * Spec entries with the functions hello and add, an UDT struct entry and a __constructor
     * function entry. SorobanClient must expose only hello and add as method names.
     *
     * @return array<XdrSCSpecEntry>
     */
    private function createSpecEntries(): array
    {
        return [
            $this->createFunctionEntry('hello'),
            $this->createStructEntry('TestStruct'),
            $this->createFunctionEntry('add'),
            $this->createFunctionEntry('__constructor'),
        ];
    }

    private function createFunctionEntry(string $name): XdrSCSpecEntry
    {
        $entry = new XdrSCSpecEntry(new XdrSCSpecEntryKind(XdrSCSpecEntryKind::SC_SPEC_ENTRY_FUNCTION_V0));
        $entry->functionV0 = new XdrSCSpecFunctionV0(
            '',
            $name,
            [],
            [new XdrSCSpecTypeDef(XdrSCSpecType::VOID())],
        );
        return $entry;
    }

    private function createStructEntry(string $name): XdrSCSpecEntry
    {
        $entry = new XdrSCSpecEntry(new XdrSCSpecEntryKind(XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_STRUCT_V0));
        $entry->udtStructV0 = new XdrSCSpecUDTStructV0('', '', $name, []);
        return $entry;
    }

    private function createHttpClient(MockHandler $mock): Client
    {
        return new Client(['handler' => HandlerStack::create($mock)]);
    }

    /**
     * getLedgerEntries response containing the source account entry, as fetched by
     * AssembledTransaction::getSourceAccount() via SorobanServer::getAccount().
     */
    private function accountEntryResponse(): Response
    {
        $accountEntry = new XdrAccountEntry(
            accountID: new XdrAccountID(self::TEST_ACCOUNT_ID),
            balance: new BigInteger(100000000),
            seqNum: new XdrSequenceNumber(new BigInteger(123456789)),
            numSubEntries: 0,
            flags: 0,
            homeDomain: '',
            thresholds: "\x01\x00\x00\x00",
            signers: [],
            ext: new XdrAccountEntryExt(0),
        );
        $entryData = new XdrLedgerEntryData(XdrLedgerEntryType::ACCOUNT());
        $entryData->account = $accountEntry;

        return $this->jsonRpcResponse([
            'entries' => [
                [
                    'key' => 'AAAAAA==',
                    'xdr' => $entryData->toBase64Xdr(),
                    'lastModifiedLedgerSeq' => 1000,
                ],
            ],
            'latestLedger' => 1000,
        ]);
    }

    /**
     * simulateTransaction response. With $writeCall=false the footprint is empty, so the
     * transaction is detected as a read call; with $writeCall=true the read-write footprint
     * contains one entry, so signing and sending is required.
     */
    private function simulateResponse(XdrSCVal $returnedValue, bool $writeCall = false): Response
    {
        $readWrite = [];
        if ($writeCall) {
            $ledgerKey = new XdrLedgerKey(XdrLedgerEntryType::ACCOUNT());
            $ledgerKey->account = new XdrLedgerKeyAccount(new XdrAccountID(self::TEST_ACCOUNT_ID));
            $readWrite[] = $ledgerKey;
        }
        $footprint = new XdrLedgerFootprint([], $readWrite);
        $resources = new XdrSorobanResources($footprint, 0, 0, 0);
        $transactionData = new XdrSorobanTransactionData(new XdrSorobanTransactionDataExt(0), $resources, 0);

        return $this->jsonRpcResponse([
            'minResourceFee' => '100',
            'latestLedger' => 1000,
            'transactionData' => $transactionData->toBase64Xdr(),
            'results' => [
                [
                    'auth' => [],
                    'xdr' => $returnedValue->toBase64Xdr(),
                ],
            ],
        ]);
    }

    private function sendTransactionResponse(): Response
    {
        return $this->jsonRpcResponse([
            'status' => 'PENDING',
            'hash' => self::TEST_TX_HASH,
            'latestLedger' => 1000,
            'latestLedgerCloseTime' => '1700000000',
        ]);
    }

    /**
     * getTransaction RPC response with the given status. If a return value is provided, it is
     * wrapped in a TransactionMeta v3 sorobanMeta so that getResultValue() can extract it.
     */
    private function getTransactionRpcResponse(string $status, ?XdrSCVal $returnValue): Response
    {
        $result = [
            'status' => $status,
            'latestLedger' => 1001,
            'latestLedgerCloseTime' => '1700000001',
            'oldestLedger' => 990,
            'oldestLedgerCloseTime' => '1699999000',
        ];
        if ($returnValue !== null) {
            $result['resultMetaXdr'] = $this->transactionMetaXdr($returnValue);
        }
        return $this->jsonRpcResponse($result);
    }

    /**
     * GetTransactionResponse object with the given status, for stubbing AssembledTransaction::signAndSend.
     */
    private function getTransactionResponse(string $status, ?XdrSCVal $returnValue): GetTransactionResponse
    {
        $response = new GetTransactionResponse([]);
        $response->status = $status;
        if ($returnValue !== null) {
            $response->resultMetaXdr = $this->transactionMetaXdr($returnValue);
        }
        return $response;
    }

    private function transactionMetaXdr(XdrSCVal $returnValue): string
    {
        $sorobanMeta = new XdrSorobanTransactionMeta(
            new XdrSorobanTransactionMetaExt(0),
            [],
            $returnValue,
            [],
        );
        $meta = new XdrTransactionMeta(3);
        $meta->v3 = new XdrTransactionMetaV3(new XdrExtensionPoint(0), [], [], [], $sorobanMeta);
        return $meta->toBase64Xdr();
    }

    private function jsonRpcResponse(array $result): Response
    {
        return new Response(200, [], json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => $result,
        ]));
    }
}
