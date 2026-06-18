<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Integration;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Contract\DeployRequest;
use Soneso\StellarSDK\Soroban\Contract\InstallRequest;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;
use Soneso\StellarSDK\Soroban\Requests\SimulateTransactionRequest;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Soroban\SorobanDelegateDescriptor;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDKTests\PrintLogger;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType;

/**
 * Protocol 27 (CAP-71) ADDRESS_WITH_DELEGATES round-trip integration test.
 *
 * Deploys a modular custom account whose `__check_auth` carries no signature of its own and forwards
 * authorization to its registered delegate signers, plus the standard auth (increment) contract. The
 * increment call is invoked with the modular account as the authorizing address, so the host calls
 * the modular account's `__check_auth`, which delegates to a registered G-account signer.
 *
 * Simulation returns a legacy ADDRESS entry for the modular account; it is converted to
 * ADDRESS_WITH_DELEGATES client-side (preserving the simulated nonce), the delegate node is signed,
 * and the transaction is re-simulated in enforcing mode so the modular account's `__check_auth` runs
 * and its footprint is captured before submission. Both contracts are deployed with the high-level
 * SorobanClient; the delegated-auth invocation is driven at the SorobanServer level.
 *
 * The test requires network access to the testnet RPC and a testnet running Protocol 27, and is gated
 * by $testOn.
 */
class P27WithDelegatesRoundTripTest extends TestCase
{
    const MODULAR_ACCOUNT_PATH = './../wasm/soroban_modular_account_contract.wasm';
    const AUTH_CONTRACT_PATH = './../wasm/soroban_auth_contract.wasm';
    const TESTNET_SERVER_URL = "https://soroban-testnet.stellar.org";

    /**
     * Set to 'testnet' to run; any other value skips the test.
     *
     * This gate follows the same convention used throughout the integration suite.
     */
    private string $testOn = 'testnet';

    private Network $network;
    private SorobanServer $server;

    public function setUp(): void
    {
        error_reporting(E_ALL);
        if ($this->testOn === 'testnet') {
            $this->network = Network::testnet();
            $this->server  = new SorobanServer(self::TESTNET_SERVER_URL);
            $this->server->setLogger(new PrintLogger());
        }
    }

    /**
     * ADDRESS_WITH_DELEGATES round-trip: deploy a modular custom account that authorizes through a
     * delegate, attach and sign the delegate tree, re-simulate in enforcing mode, and submit.
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function testWithDelegatesSimulateSignRoundTrip(): void
    {
        if ($this->testOn !== 'testnet') {
            $this->markTestSkipped(
                'P27 ADDRESS_WITH_DELEGATES integration test requires testnet access. '
                . 'Set $testOn = "testnet" to enable. '
                . 'Submission succeeds only once the network runs Protocol 27.'
            );
        }

        // Fund the submitter (transaction source) and a distinct delegate (a G-account that
        // authorizes on behalf of the modular account).
        $submitterKeyPair = KeyPair::random();
        $delegateKeyPair  = KeyPair::random();
        $submitterId      = $submitterKeyPair->getAccountId();
        $delegateId       = $delegateKeyPair->getAccountId();

        FriendBot::fundTestAccount($submitterId);
        FriendBot::fundTestAccount($delegateId);
        sleep(5);

        // Deploy the modular custom account (registering the delegate as an allowed signer) and the
        // auth (increment) business contract, both via the high-level client.
        $modularWasmHash = SorobanClient::install(new InstallRequest(
            wasmBytes: file_get_contents(self::MODULAR_ACCOUNT_PATH),
            rpcUrl: self::TESTNET_SERVER_URL,
            network: $this->network,
            sourceAccountKeyPair: $submitterKeyPair,
        ));
        $signersArg = XdrSCVal::forVec([Address::fromAccountId($delegateId)->toXdrSCVal()]);
        $modularClient = SorobanClient::deploy(new DeployRequest(
            rpcUrl: self::TESTNET_SERVER_URL,
            network: $this->network,
            sourceAccountKeyPair: $submitterKeyPair,
            wasmHash: $modularWasmHash,
            constructorArgs: [$signersArg],
        ));
        $modularAccountId = $modularClient->getContractId();

        $authWasmHash = SorobanClient::install(new InstallRequest(
            wasmBytes: file_get_contents(self::AUTH_CONTRACT_PATH),
            rpcUrl: self::TESTNET_SERVER_URL,
            network: $this->network,
            sourceAccountKeyPair: $submitterKeyPair,
        ));
        $authClient = SorobanClient::deploy(new DeployRequest(
            rpcUrl: self::TESTNET_SERVER_URL,
            network: $this->network,
            sourceAccountKeyPair: $submitterKeyPair,
            wasmHash: $authWasmHash,
        ));
        $authContractId = $authClient->getContractId();
        sleep(5);

        // increment(user = modular account, value = 1) requires the modular account's authorization,
        // so the host invokes its __check_auth, which delegates to the registered G-account.
        $args = [Address::fromContractId($modularAccountId)->toXdrSCVal(), XdrSCVal::forU32(1)];
        $invokeHostFunction = new InvokeContractHostFunction($authContractId, 'increment', $args);
        $op = (new InvokeHostFunctionOperationBuilder($invokeHostFunction))->build();

        $submitterAccount = $this->server->getAccount($submitterId);
        $this->assertNotNull($submitterAccount);
        $transaction = (new TransactionBuilder($submitterAccount))->addOperation($op)->build();

        // Recording-mode simulation: returns the legacy ADDRESS authorization entry for the modular
        // account (with the RPC-assigned nonce). __check_auth is not executed in this pass.
        $request = new SimulateTransactionRequest(transaction: $transaction);
        $simulateResponse = $this->server->simulateTransaction($request);

        $this->assertNull($simulateResponse->error);
        $this->assertNotNull($simulateResponse->results);
        $auth = $simulateResponse->getSorobanAuth();
        $this->assertNotNull($auth);
        $this->assertCount(1, $auth, 'increment should require exactly one authorization (the modular account)');

        $latestLedgerResponse = $this->server->getLatestLedger();
        $this->assertNotNull($latestLedgerResponse->sequence);
        $signatureExpirationLedger = $latestLedgerResponse->sequence + 100;

        // Convert each address-based entry to the ADDRESS_WITH_DELEGATES arm (preserving the simulated
        // nonce), attach the delegate, and sign only the delegate node. The top-level signature stays
        // void: the modular account verifies no signature of its own and authorizes through its delegate.
        $signedAuth = [];
        foreach ($auth as $entry) {
            $credType = $entry->credentials->credentialType;
            if ($credType === XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS
                || $credType === XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2) {
                $withDelegates = SorobanAuthorizationEntry::withDelegates(
                    $entry,
                    $signatureExpirationLedger,
                    [new SorobanDelegateDescriptor($delegateId)],
                );
                $withDelegates->sign(
                    $delegateKeyPair,
                    $this->network,
                    signatureExpirationLedger: $signatureExpirationLedger,
                    forAddress: $delegateId,
                );
                $signedAuth[] = $withDelegates;
            } else {
                $signedAuth[] = $entry;
            }
        }

        // A WITH_DELEGATES entry must be present with a void top-level signature and a signed delegate node.
        $withDelegatesEntry = null;
        foreach ($signedAuth as $entry) {
            if ($entry->credentials->credentialType === XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES) {
                $withDelegatesEntry = $entry;
            }
        }
        $this->assertNotNull($withDelegatesEntry, 'Expected an ADDRESS_WITH_DELEGATES auth entry');
        $delegatesWrapper = $withDelegatesEntry->credentials->addressWithDelegates;
        $this->assertNotNull($delegatesWrapper);
        $this->assertNull(
            $delegatesWrapper->addressCredentials->signature->vec,
            'The top-level signature must remain void (the modular account signs nothing itself)',
        );
        $this->assertCount(1, $delegatesWrapper->delegates, 'Exactly one delegate node should be attached');
        $this->assertNotNull(
            $delegatesWrapper->delegates[0]->signature->vec,
            'The delegate node must carry a signature after signing',
        );

        // Attach the signed auth and re-simulate in enforcing mode so the modular account's
        // __check_auth runs and its footprint reads (plus the delegate's account entry) are captured.
        // The recording-mode simulation above could not have captured them.
        $transaction->setSorobanAuth($signedAuth);
        $enforceRequest = new SimulateTransactionRequest(transaction: $transaction, authMode: 'enforce');
        $reSimulateResponse = $this->server->simulateTransaction($enforceRequest);
        $this->assertNull($reSimulateResponse->error, 'Enforcing re-simulation should not error');
        $this->assertNotNull($reSimulateResponse->getTransactionData());
        $this->assertNotNull($reSimulateResponse->minResourceFee);

        // Apply the enforcing simulation's footprint and resource fee; the already-signed auth is kept.
        $transaction->setSorobanTransactionData($reSimulateResponse->getTransactionData());
        $transaction->addResourceFee($reSimulateResponse->minResourceFee);
        $transaction->sign($submitterKeyPair, $this->network);

        $sendResponse = $this->server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);

        $statusResponse = $this->pollStatus($this->server, $sendResponse->hash);
        $resVal = $statusResponse->getResultValue();
        $this->assertNotNull($resVal);
        // increment returns the modular account's accumulated counter; a fresh account starts at 0,
        // so a single increment by 1 returns 1, proving the delegated authorization succeeded.
        $this->assertEquals(1, $resVal->u32);
    }

    private function pollStatus(SorobanServer $server, string $transactionId): GetTransactionResponse
    {
        $statusResponse = $server->getTransaction($transactionId);
        $count = 15;
        while ($count-- > 0 && $statusResponse->status === GetTransactionResponse::STATUS_NOT_FOUND) {
            sleep(3);
            $statusResponse = $server->getTransaction($transactionId);
        }
        return $statusResponse;
    }
}
