<?php  declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\DeployRequest;
use Soneso\StellarSDK\Soroban\Contract\InstallRequest;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;

class SorobanClientTest  extends TestCase
{
    const TESTNET_SERVER_URL = "https://soroban-testnet.stellar.org";
    const HELLO_CONTRACT_PATH = './wasm/soroban_hello_world_contract.wasm';
    const AUTH_CONTRACT_PATH = './wasm/soroban_auth_contract.wasm';
    const SWAP_CONTRACT_PATH = './wasm/soroban_atomic_swap_contract.wasm';
    const TOKEN_CONTRACT_PATH = './wasm/soroban_token_contract.wasm';

    private Network $network;
    private KeyPair $sourceAccountKeyPair;

    public function setUp(): void
    {
        // Turn on error reporting
        error_reporting(E_ALL);
        $this->network = Network::testnet();
        $this->sourceAccountKeyPair = KeyPair::random();
        print("Signer seed: " . $this->sourceAccountKeyPair->getSecretSeed() . PHP_EOL);
        FriendBot::fundTestAccount($this->sourceAccountKeyPair->getAccountId());
    }

    public function testHelloContract() {

        $helloContractWasmHash = $this->installContract(self::HELLO_CONTRACT_PATH);
        print("Installed hello contract wasm hash: {$helloContractWasmHash}" . PHP_EOL);

        $client = $this->deployContract($helloContractWasmHash);
        print("Deployed hello contract contract id: {$client->getContractId()}" . PHP_EOL);

        $methodNames = $client->getMethodNames();
        self::assertCount(1, $methodNames);
        assertEquals("hello", $methodNames[0]);

        $result = $client->invokeMethod(name: "hello", args: [XdrSCVal::forSymbol("John")]);
        assertNotNull($result->vec);
        self::assertCount(2, $result->vec);
        $resultVal = $result->vec[0]->sym . ", " . $result->vec[1]->sym;
        self::assertEquals("Hello, John", $resultVal);
    }

    public function testAuthContract()
    {
        $authContractWasmHash = $this->installContract(self::AUTH_CONTRACT_PATH);
        print("Installed auth contract wasm hash: {$authContractWasmHash}" . PHP_EOL);

        $deployedClient = $this->deployContract($authContractWasmHash);
        print("Deployed auth contract contract id: {$deployedClient->getContractId()}" . PHP_EOL);

        // just a small test to check if it can load by contract id
        $client = SorobanClient::forClientOptions(new ClientOptions(
            sourceAccountKeyPair: $this->sourceAccountKeyPair,
            contractId: $deployedClient->getContractId(),
            network: $this->network,
            rpcUrl: self::TESTNET_SERVER_URL,
            enableServerLogging: true)
        );
        assertEquals($deployedClient->getContractId(), $client->getContractId());

        $methodName = "increment";

        $methodNames = $client->getMethodNames();
        self::assertCount(1, $methodNames);
        assertEquals($methodName, $methodNames[0]);

        // submitter and invoker use are the same
        // no need to sign auth

        $invokerAddress = Address::fromAccountId($this->sourceAccountKeyPair->getAccountId());
        $args = [$invokerAddress->toXdrSCVal(), XdrSCVal::forU32(3)];
        $result = $client->invokeMethod(name: $methodName, args: $args);
        assertEquals(3, $result->u32);

        // submitter and invoker use are NOT the same
        // we need to sign the auth entry

        $invokerKeyPair = KeyPair::random();
        FriendBot::fundTestAccount($invokerKeyPair->getAccountId());

        $invokerAddress = Address::fromAccountId($invokerKeyPair->getAccountId());
        $args = [$invokerAddress->toXdrSCVal(), XdrSCVal::forU32(4)];
        try {
            $client->invokeMethod(name: $methodName, args: $args);
            self::fail("should not reach here!");
        } catch (Exception $e) {
            print($e->getMessage() . PHP_EOL);
        }

        $tx = $client->buildInvokeMethodTx(name: $methodName, args: $args);
        $tx->signAuthEntries(signerKeyPair: $invokerKeyPair);
        $response = $tx->signAndSend();

        $result = $response->getResultValue();
        assertNotNull($result);
        assertEquals(4, $result->u32);
    }

    public function testAtomicSwap()
    {
        $swapContractWasmHash = $this->installContract(self::SWAP_CONTRACT_PATH);
        print("Installed swap contract wasm hash: {$swapContractWasmHash}" . PHP_EOL);

        $tokenContractWasmHash = $this->installContract(self::TOKEN_CONTRACT_PATH);
        print("Installed token contract wasm hash: {$tokenContractWasmHash}" . PHP_EOL);

        $adminKeyPair = KeyPair::random();
        $aliceKeyPair = KeyPair::random();
        $aliceId = $aliceKeyPair->getAccountId();
        $bobKeyPair = KeyPair::random();
        $bobId = $bobKeyPair->getAccountId();

        FriendBot::fundTestAccount($adminKeyPair->getAccountId());
        FriendBot::fundTestAccount($aliceId);
        FriendBot::fundTestAccount($bobId);

        print("admin: " . $adminKeyPair->getSecretSeed() .  " : " . $adminKeyPair->getAccountId(). PHP_EOL);
        print("alice: " . $aliceKeyPair->getSecretSeed() .  " : " . $aliceKeyPair->getAccountId(). PHP_EOL);
        print("bob: " . $bobKeyPair->getSecretSeed() .  " : " . $bobKeyPair->getAccountId(). PHP_EOL);

        $atomicSwapClient = $this->deployContract($swapContractWasmHash);
        print("Deployed atomic swap contract contract id: {$atomicSwapClient->getContractId()}" . PHP_EOL);

        $tokenAClient = $this->deployContract($tokenContractWasmHash);
        $tokenAContractId = $tokenAClient->getContractId();
        print("Deployed token A contract contract id: {$tokenAContractId}" . PHP_EOL);

        $tokenBClient = $this->deployContract($tokenContractWasmHash);
        $tokenBContractId = $tokenBClient->getContractId();
        print("Deployed token B contract contract id: {$tokenBContractId}" . PHP_EOL);

        $this->createToken($tokenAClient, $adminKeyPair,"TokenA", "TokenA");
        $this->createToken($tokenBClient, $adminKeyPair, "TokenB", "TokenB");
        print("Tokens created");

        $this->mint($tokenAClient, $adminKeyPair, $aliceId, 10000000000000);
        $this->mint($tokenBClient, $adminKeyPair, $bobId, 10000000000000);
        print("Alice and Bob funded");

        $aliceTokenABalance = $this->readBalance($aliceId, $tokenAClient);
        $this->assertEquals(10000000000000, $aliceTokenABalance);

        $bobTokenBBalance = $this->readBalance($bobId, $tokenBClient);
        $this->assertEquals(10000000000000, $bobTokenBBalance);


        $amountA = XdrSCVal::forI128(new XdrInt128Parts(0,1000));
        $minBForA = XdrSCVal::forI128(new XdrInt128Parts(0,4500));

        $amountB = XdrSCVal::forI128(new XdrInt128Parts(0,5000));
        $minAForB = XdrSCVal::forI128(new XdrInt128Parts(0,950));

        $swapMethodName = "swap";

        $args = [
            Address::fromAccountId($aliceId)->toXdrSCVal(),
            Address::fromAccountId($bobId)->toXdrSCVal(),
            Address::fromContractId($tokenAContractId)->toXdrSCVal(),
            Address::fromContractId($tokenBContractId)->toXdrSCVal(),
            $amountA,
            $minBForA,
            $amountB,
            $minAForB
        ];

        $tx = $atomicSwapClient->buildInvokeMethodTx(name: $swapMethodName, args: $args);

        $whoElseNeedsToSign = $tx->needsNonInvokerSigningBy();
        self::assertCount(2, $whoElseNeedsToSign);
        self::assertContains($aliceId, $whoElseNeedsToSign);
        self::assertContains($bobId, $whoElseNeedsToSign);

        $tx->signAuthEntries(signerKeyPair: $aliceKeyPair);
        print("Signed by Alice");

        // test signing via callback
        $bobPublicKeyKeyPair = KeyPair::fromAccountId($bobId);
        $tx->signAuthEntries(signerKeyPair: $bobPublicKeyKeyPair, authorizeEntryCallback: function (SorobanAuthorizationEntry $entry, Network $network) use ($bobKeyPair): SorobanAuthorizationEntry  {
                print("Bob is signing");
                // You can send it to some other server for signing by encoding it as a base64xdr string
                $base64Entry = $entry->toBase64Xdr();
                // send for signing ...
                // and on the other server you can decode it:
                $entryToSign = SorobanAuthorizationEntry::fromBase64Xdr($base64Entry);
                // sign it
                $entryToSign->sign($bobKeyPair, $network);
                // encode as a base64xdr string and send it back
                $signedBase64Entry = $entryToSign->toBase64Xdr();
                print("Bob signed");
                // here you can now decode it and return it
                return SorobanAuthorizationEntry::fromBase64Xdr($signedBase64Entry);
            },
        );
        print("Signed by Bob");
        $response = $tx->signAndSend();
        $result = $response->getResultValue();
        $this->assertEquals(XdrSCValType::SCV_VOID, $result->type->value);
        print("Swap done");
    }


    /**
     * @throws GuzzleException
     */
    private function installContract(string $path): string {
        $contractCode = file_get_contents($path, false);

        $installRequest = new InstallRequest(
            wasmBytes: $contractCode,
            rpcUrl: self::TESTNET_SERVER_URL,
            network: $this->network,
            sourceAccountKeyPair: $this->sourceAccountKeyPair,
            enableServerLogging: true
        );

        return SorobanClient::install($installRequest);
    }

    /**
     * @throws GuzzleException
     */
    private function deployContract(string $wasmHash): SorobanClient {
        $deployRequest = new DeployRequest(
            rpcUrl: self::TESTNET_SERVER_URL,
            network: $this->network,
            sourceAccountKeyPair: $this->sourceAccountKeyPair,
            wasmHash: $wasmHash,
            enableServerLogging: true
        );
        return SorobanClient::deploy($deployRequest);
    }

    /**
     * @throws GuzzleException
     */
    private function createToken(SorobanClient $tokenClient, Keypair $submitterKp, String $name, String $symbol) : void
    {
        // see https://soroban.stellar.org/docs/reference/interfaces/token-interface
        $submitterId = $submitterKp->getAccountId();

        $adminAddress = Address::fromAccountId($submitterId)->toXdrSCVal();
        $methodName = "initialize";

        $tokenName = XdrSCVal::forString($name);
        $tokenSymbol = XdrSCVal::forString($symbol);

        $args = [$adminAddress, XdrSCVal::forU32(8), $tokenName, $tokenSymbol];

        $tokenClient->invokeMethod(name: $methodName, args: $args);
    }

    /**
     * @throws GuzzleException
     */
    private function mint(SorobanClient $tokenClient, Keypair $adminKp, String $toAccountId, int $amount) : void
    {
        sleep(5);

        $methodName = "mint";

        $toAddress = Address::fromAccountId($toAccountId)->toXdrSCVal();
        $amountValue = XdrSCVal::forI128(new XdrInt128Parts(0,$amount));
        $args = [$toAddress, $amountValue];
        $tx = $tokenClient->buildInvokeMethodTx(name: $methodName, args: $args);
        $tx->signAuthEntries(signerKeyPair: $adminKp);
        $tx->signAndSend();

    }

    private function readBalance(String $forAccountId, SorobanClient $tokenClient) : int
    {
        sleep(5);

        // see https://soroban.stellar.org/docs/reference/interfaces/token-interface

        $address = Address::fromAccountId($forAccountId)->toXdrSCVal();
        $methodName = "balance";

        $args = [$address];

        $resultVal = $tokenClient->invokeMethod(name: $methodName, args: $args);
        return $resultVal->i128->lo;
    }
}