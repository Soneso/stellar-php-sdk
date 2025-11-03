<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Contract;

use GuzzleHttp\Exception\GuzzleException;
use Exception;
use DateTime;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\Constants\NetworkConstants;
use Soneso\StellarSDK\Constants\StellarConstants;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperation;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\RestoreFootprintOperationBuilder;
use Soneso\StellarSDK\Soroban\Requests\SimulateTransactionRequest;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\RestorePreamble;
use Soneso\StellarSDK\Soroban\Responses\SendTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SimulateTransactionResponse;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\TimeBounds;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;

/**
 * High-level transaction builder for Soroban smart contract interactions
 *
 * This class wraps a transaction-under-construction and provides convenient methods for the most
 * common Soroban workflows: simulating transactions, signing them, and sending them to the network.
 * It handles resource fees, footprint management, authorization entries, and automatic retries
 * for transactions that require state restoration.
 *
 * Most of the time, you will not construct an AssembledTransaction directly,
 * but instead receive one as the return value of a SorobanClient method.
 *
 * @package Soneso\StellarSDK\Soroban\Contract
 * @see SorobanClient For the high-level contract client that creates these transactions
 * @see https://developers.stellar.org/docs/smart-contracts Soroban Smart Contracts Documentation
 * @since 1.0.0
 *
 * Let's look at examples of how to use `AssembledTransaction` for a variety of
 * use-cases:
 *
 * #### 1. Simple read call
 *
 *  Since these only require simulation, you can get the `result` of the call
 *  right after constructing your `AssembledTransaction`:
 *
 * ```php
 *
 * $clientOptions = new ClientOptions(
 *      sourceAccountKeyPair: $sourceAccountKeyPair,
 *      contractId: 'C123…',
 *      network: Network::testnet(),
 *      rpcUrl: 'https://…',
 * );
 *
 * $txOptions = new AssembledTransactionOptions(
 *                  clientOptions: $clientOptions,
 *                  methodOptions: new MethodOptions(),
 *                  method: 'myReadMethod',
 *                  arguments: $args);
 *
 * $tx = AssembledTransaction::build($options);
 * $result = $tx->getSimulationData()->returnedValue;
 * ```
 *
 *
 * While that looks pretty complicated, most of the time you will use this in
 * conjunction with {@link SorobanClient}, which simplifies it to:
 *
 * ```php
 * $result = $client->invokeMethod(name: 'myReadMethod', args: $args);
 * ```
 *
 * #### 2. Simple write call
 *
 * For write calls that will be simulated and then sent to the network without
 * further manipulation, only one more step is needed:
 *
 * ```php
 * $tx = AssembledTransaction::build($options);
 * $response = $tx->signAndSend();
 *
 * if ($response->getStatus() === GetTransactionResponse::STATUS_SUCCESS) {
 *
 *     $result = $response->getResultValue();
 * }
 * ```
 *
 * If you are using it in conjunction with {@link SorobanClient}:
 *
 * ```php
 *  $result = $client->invokeMethod(name: 'myWriteMethod', args: $args);
 *  ```
 *
 * #### 3. More fine-grained control over transaction construction
 *
 * If you need more control over the transaction before simulating it, you can
 *  set various {@link MethodOptions} when constructing your
 *  `AssembledTransaction`. With a {@link SorobanClient}, this can be passed as an
 *  argument when calling `invokeMethod` or `buildInvokeMethodTx` :
 *
 * ```php
 * $methodOptions = new MethodOptions(
 *          fee: 10000,
 *          timeoutInSeconds: 20,
 *          simulate: false,
 * );
 *
 * $tx = $client->buildInvokeMethodTx(name: 'myWriteMethod', args: $args, methodOptions: $methodOptions);
 * ```
 *
 * Since we've skipped simulation, we can now edit the `raw` transaction builder and
 * then manually call `simulate`:
 *
 * ```php
 *  $tx->raw->addMemo(Memo::text("Hello!"));
 *  $tx->simulate();
 * ```
 *  If you need to inspect the simulation later, you can access it with
 *  `$tx->getSimulationData()`.
 *
 * #### 4. Multi-auth workflows
 *
 * Soroban, and Stellar in general, allows multiple parties to sign a
 * transaction.
 *
 * Let's consider an Atomic Swap contract. Alice wants to give some of her Token
 * A tokens to Bob for some of his Token B tokens.
 *
 * ```php
 * $swapMethodName = "swap";
 *
 * $amountA = XdrSCVal::forI128(new XdrInt128Parts(0,1000));
 * $minBForA = XdrSCVal::forI128(new XdrInt128Parts(0,4500));
 *
 * $amountB = XdrSCVal::forI128(new XdrInt128Parts(0,5000));
 * $minAForB = XdrSCVal::forI128(new XdrInt128Parts(0,950));
 *
 * $args = [
 *      Address::fromAccountId($aliceAccountId)->toXdrSCVal(),
 *      Address::fromAccountId($bobAccountId)->toXdrSCVal(),
 *      Address::fromContractId($tokenAContractId)->toXdrSCVal(),
 *      Address::fromContractId($tokenBContractId)->toXdrSCVal(),
 *      $amountA,
 *      $minBForA,
 *      $amountB,
 *      $minAForB
 * ];
 * ```
 *
 * Let's say Alice is also going to be the one signing the final transaction
 *  envelope, meaning she is the invoker. So your app, she
 *  simulates the `swap` call:
 *
 * ```php
 *  $tx = $atomicSwapClient->buildInvokeMethodTx(name: $swapMethodName, args: $args);
 *  ```
 * But your app can't `signAndSend` this right away, because Bob needs to sign
 *  it first. You can check this:
 *
 * ```php
 *  $whoElseNeedsToSign = tx->needsNonInvokerSigningBy()
 *  ```
 *
 * You can verify that `$whoElseNeedsToSign` is an array of length `1`,
 *  containing only Bob's public key.
 *
 * If you have Bob's secret key, you can sign it right away with:
 *
 * ```php
 * $bobsKeyPair = KeyPair::fromSeed('S...')
 * $tx->signAuthEntries(signerKeyPair: $bobsKeyPair);
 * ```
 * But if you don't have Bob's private key, and e.g. need to send it to another server for signing,
 * you can provide a callback function for signing the auth entry:
 *
 *  ```php
 *  $bobsPublicKeyKeyPair = KeyPair::fromAccountId($bobsAccountId);
 *  $tx->signAuthEntries(signerKeyPair: $bobPublicKeyKeyPair,
 *                      authorizeEntryCallback: function (SorobanAuthorizationEntry $entry,
 *                                                        Network $network) : SorobanAuthorizationEntry  {
 *
 *              // You can send it to some other server for signing by encoding it as a base64xdr string
 *              $base64Entry = $entry->toBase64Xdr();
 *              // send for signing ...
 *              // and on the other server you can decode it:
 *              $entryToSign = SorobanAuthorizationEntry::fromBase64Xdr($base64Entry);
 *              // sign it
 *              $entryToSign->sign($bobsSecretKeyPair, $network);
 *              // encode as a base64xdr string and send it back
 *              $signedBase64Entry = $entryToSign->toBase64Xdr();
 *              // here you can now decode it and return it
 *              return SorobanAuthorizationEntry::fromBase64Xdr($signedBase64Entry);
 *       },
 * );
 *  ```
 * To see an even more complicated example, where Alice swaps with Bob but the
 *  transaction is invoked by yet another party, check out in the SorobanClientTest.testAtomicSwap()
 */
class AssembledTransaction
{
    /**
     * @var TransactionBuilder|null The TransactionBuilder as constructed in `AssembledTransaction::build`
     * Feel free set `simulate: false` in the method options to modify
     *  this object before calling `tx->simulate()` manually. Example:
     *
     * ```php
     *  $methodOptions = new MethodOptions(
     *           simulate: false,
     *  );
     *
     *  $tx = $client->buildInvokeMethodTx(name: 'myWriteMethod', args: $args, methodOptions: $methodOptions);
     *  $tx->raw->addMemo(Memo::text("Hello!"));
     *  $tx->simulate();
     *  ```
     */
    public ?TransactionBuilder $raw = null;

    /**
     * The Transaction as it was built with `$raw->build()` right before
     * simulation. Once this is set, modifying `$raw` will have no effect unless
     * you call `$tx->simulate()` again.
     */
    public ?Transaction $tx = null;

    /**
     * The response of the transaction simulation. This is set after the first call
     * to `simulate`.
     */
    public ?SimulateTransactionResponse $simulationResponse = null;

    /**
     * @var SimulateHostFunctionResult|null The result extracted from the simulation response if it was successfull.
     * To receive this you can call `$tx->getSimulationData()`
     */
    private ?SimulateHostFunctionResult $simulationResult = null;

    /**
     * @var SorobanServer The Soroban server to use for all RPC calls. This is constructed from the
     *  `rpcUrl` in the constructor arguments.
     */
    private SorobanServer $server;

    /**
     * @var Transaction|null $signed The signed transaction. Null if not yet signed.
     */
    public ?Transaction $signed = null;

    /**
     * @var AssembledTransactionOptions the options for constructing and managing this AssembledTransaction.
     */
    public AssembledTransactionOptions $options;

    /**
     * Private constructor for AssembledTransaction
     *
     * Use the static factory methods AssembledTransaction::build or AssembledTransaction::buildWithOp
     * to create instances. The constructor initializes the Soroban RPC server connection.
     *
     * @param AssembledTransactionOptions $options Configuration options for the transaction
     */
    private function __construct(AssembledTransactionOptions $options)
    {
        $this->options= $options;
        $this->server = new SorobanServer(endpoint: $options->clientOptions->rpcUrl);
        $this->server->enableLogging = $options->enableServerLogging;

    }

    /**
     * Constructs a new AssembledTransaction for invoking a contract method
     *
     * This is the main factory method for creating AssembledTransactions. It fetches the source
     * account from the network to get the current sequence number, builds a transaction with an
     * InvokeContractHostFunction operation, and optionally simulates it to get resource fees.
     *
     * If you need to use a different host function type (e.g., CreateContractWithConstructorHostFunction),
     * use AssembledTransaction::buildWithOp instead.
     *
     * @param AssembledTransactionOptions $options Configuration including contract id, method name, and arguments
     * @return AssembledTransaction The assembled and optionally simulated transaction
     * @throws GuzzleException If fetching the account or simulation fails
     * @throws Exception If transaction construction fails
     */
    public static function build(AssembledTransactionOptions $options): AssembledTransaction {
        $invokeContractHostFunction = new InvokeContractHostFunction($options->clientOptions->contractId,
            $options->method, $options->arguments);
        $builder = new InvokeHostFunctionOperationBuilder($invokeContractHostFunction);
        return self::buildWithOp($builder->build(), $options);
    }

    /**
     * Constructs an AssembledTransaction with a custom host function operation
     *
     * Use this factory method when you need to use a host function other than InvokeContractHostFunction,
     * such as CreateContractWithConstructorHostFunction for deploying contracts or
     * UploadContractWasmHostFunction for installing contract code.
     *
     * @param InvokeHostFunctionOperation $operation The host function operation to include in the transaction
     * @param AssembledTransactionOptions $options Configuration for the transaction
     * @return AssembledTransaction The assembled and optionally simulated transaction
     * @throws GuzzleException If fetching the account or simulation fails
     * @throws Exception If transaction construction fails
     */
    public static function buildWithOp(InvokeHostFunctionOperation $operation,
                                       AssembledTransactionOptions $options): AssembledTransaction {
        $tx = new AssembledTransaction(options: $options);
        $account = $tx->getSourceAccount();
        $tx->raw = new TransactionBuilder(sourceAccount: $account);
        $tx->raw->setTimeBounds(new TimeBounds((new DateTime())->modify("- " . NetworkConstants::DEFAULT_TIME_BOUNDS_OFFSET_SECONDS . " seconds"),
            (new DateTime())->modify("+ " . $tx->options->methodOptions->timeoutInSeconds ." seconds")));
        $tx->raw->addOperation($operation);
        $tx->raw->setMaxOperationFee($tx->options->methodOptions->fee);
        if ($options->methodOptions->simulate) {
            $tx->simulate();
        }
        return $tx;
    }

    /**
     * Simulates the transaction and updates it with the simulation results
     *
     * Calls the Soroban RPC simulateTransaction method to get resource fees and footprint data.
     * The simulation results are applied to the transaction, including soroban transaction data,
     * resource fees, and authorization entries. If restore is enabled and a restore preamble is
     * returned, it will automatically restore the footprint and re-simulate.
     *
     * @param bool|null $restore Whether to automatically restore expired ledger entries (overrides method options)
     * @return void
     * @throws Exception If simulation fails or automatic restore is unsuccessful
     * @throws GuzzleException If the RPC request fails
     */
    public function simulate(?bool $restore = null) : void {
        if ($this->tx === null) {
            if ($this->raw === null) {
                throw new Exception('Transaction has not yet been assembled; call "AssembledTransaction.build" first.');
            }
            $this->tx = $this->raw->build();
        }

        $shouldRestore = $restore ?? $this->options->methodOptions->restore;
        $this->simulationResult = null;
        $this->simulationResponse = $this->server->simulateTransaction(new SimulateTransactionRequest(transaction: $this->tx));
        if ($shouldRestore && $this->simulationResponse->restorePreamble !== null) {
            if ($this->options->clientOptions->sourceAccountKeyPair->getPrivateKey() === null) {
                throw new Exception('Source account keypair has no private key, but needed for automatic restore.');
            }
            $result = $this->restoreFootprint($this->simulationResponse->restorePreamble);
            if ($result->status === GetTransactionResponse::STATUS_SUCCESS) {
                $sourceAccount = $this->getSourceAccount();

                $this->raw = new TransactionBuilder(sourceAccount: $sourceAccount);
                $this->raw->setTimeBounds(new TimeBounds((new DateTime())->modify("- " . NetworkConstants::DEFAULT_TIME_BOUNDS_OFFSET_SECONDS . " seconds"),
                    (new DateTime())->modify("+ " . $this->options->methodOptions->timeoutInSeconds ." seconds")));

                $invokeContractHostFunction = new InvokeContractHostFunction($this->options->clientOptions->contractId,
                    $this->options->method, $this->options->arguments);
                $builder = new InvokeHostFunctionOperationBuilder($invokeContractHostFunction);

                $this->raw->addOperation($builder->build());
                $this->simulate();
            }
            throw new Exception("Automatic restore failed! You set 'restore: true' but the 
            attempted restore did not work. Status: {$result->status} , transaction result xdr: {$result->resultXdr}");
        }
        if ($this->simulationResponse->transactionData !== null) { //success
            $this->tx->setSorobanTransactionData($this->simulationResponse->transactionData);
            $this->tx->addResourceFee($this->simulationResponse->minResourceFee);
            $this->tx->setSorobanAuth($this->simulationResponse->getSorobanAuth());
        }
    }

    /**
     * Signs the transaction and sends it to the network, waiting for completion
     *
     * Signs the transaction with the source account's private key, submits it to the network,
     * and polls for transaction completion up to the configured timeout. Read-only calls will
     * throw an exception unless force is set to true.
     *
     * @param KeyPair|null $sourceAccountKeyPair The keypair with private key to sign with (overrides options)
     * @param bool $force Force signing and sending even if it is a read-only call (default: false)
     * @return GetTransactionResponse The transaction result after completion
     * @throws Exception If signing fails, transaction is read-only without force, or timeout is exceeded
     * @throws GuzzleException If the RPC request fails
     */
    public function signAndSend(?KeyPair $sourceAccountKeyPair = null, bool $force = false) : GetTransactionResponse {
        if ($this->signed === null) {
            $this->sign($sourceAccountKeyPair, $force);
        }
        return $this->send();
    }

    /**
     * Sends the signed transaction to the network and waits for completion
     *
     * Submits the transaction via the sendTransaction RPC method and polls getTransaction
     * until the transaction reaches a terminal state (SUCCESS, FAILED, or timeout).
     *
     * @return GetTransactionResponse The transaction result after completion
     * @throws Exception If the transaction has not been signed, sending fails, or timeout is exceeded
     * @throws GuzzleException If the RPC request fails
     */
    public function send() :GetTransactionResponse {
        if ($this->signed === null) {
            throw new Exception("The transaction has not yet been signed. Run `sign` first, or use `signAndSend` instead.");
        }
        $sendTxResponse = $this->server->sendTransaction($this->signed);
        if ($sendTxResponse->status === SendTransactionResponse::STATUS_ERROR) {
            throw new Exception("Send transaction failed with error transaction result xdr: {$sendTxResponse->errorResultXdr}");
        } else if ($sendTxResponse->status === SendTransactionResponse::STATUS_DUPLICATE) {
            throw new Exception("Send transaction failed with status: DUPLICATE");
        }
        return $this->pollStatus($sendTxResponse->hash);
    }

    /**
     * Signs the transaction with the source account's private key
     *
     * Signs the transaction envelope with the invoker's keypair. This does not submit the transaction.
     * Read-only calls will throw an exception unless force is set to true. Multi-signature transactions
     * should use signAuthEntries for additional signers.
     *
     * @param KeyPair|null $sourceAccountKeyPair The keypair with private key to sign with (overrides options)
     * @param bool $force Force signing even if it is a read-only call (default: false)
     * @return void
     * @throws Exception If the private key is missing, transaction requires multiple signers, or is read-only without force
     */
    public function sign(?KeyPair $sourceAccountKeyPair = null, bool $force = false) : void {
        if($this->tx === null) {
            throw new Exception("Transaction has not yet been simulated");
        }
        if (!$force && $this->isReadCall()) {
            throw new Exception( "This is a read call. It requires no signature or sending. " .
                "Use `force: true` to sign and send anyway.");
        }
        $signerKp = $sourceAccountKeyPair !== null ? $sourceAccountKeyPair: $this->options->clientOptions->sourceAccountKeyPair;
        if ($signerKp->getPrivateKey() === null) {
            throw new Exception('Source account keypair has no private key, but needed for signing.');
        }

        $allNeededSigners = $this->needsNonInvokerSigningBy();
        /**
         * @var array<string> $neededAccountSigners
         */
        $neededAccountSigners = array();
        foreach ($allNeededSigners as $signer) {
            if (!str_starts_with($signer, 'C')) {
                array_push($neededAccountSigners, $signer);
            }
        }
        if (count($neededAccountSigners) > 0) {
            throw new Exception("Transaction requires signatures from multiple signers. " .
                "See `needsNonInvokerSigningBy` for details.");
        }

        $clonedTx = clone $this->tx;
        $clonedTx->sign($signerKp, $this->options->clientOptions->network);
        $this->signed = $clonedTx;
    }

    /**
     * Signs and updates the auth entries related to the public key of the $signerKeyPair provided.
     *
     * @param KeyPair $signerKeyPair The keypair of the signer for the auth entry.
     *  By default, this function will sign all auth entries that are connected to the signerKeyPair public key by using SorobanAuthorizationEntry->sign().
     *  The signerKeyPair must contain the private key for signing for this default case. If you don't have the signer's private key, provide the signers
     *  KeyPair containing only the public key and provide a callback function for signing by using the $authorizeEntryCallback parameter.
     * @param callable|null $authorizeEntryCallback an optional callback used to sign the auth entry.
     *  By default, the function will use SorobanAuthorizationEntry->sign(). If you need to sign on another server or
     *  if you have a pro use-case and need to use your own function rather than the default `SorobanAuthorizationEntry->sign()`
     *  function you can do that by providing a callback function here! Your function needs to take following arguments: (SorobanAuthorizationEntry $entry, Network $network)
     *  and it must return the signed SorobanAuthorizationEntry.
     * @param int|null $validUntilLedgerSeq When to set each auth entry to expire. Could be any number of blocks in
     *  the future. Default: current sequence + 100 blocks (about 8.3 minutes from now).
     *
     * @return void
     * @throws GuzzleException
     * @throws Exception
     */
    public function signAuthEntries(KeyPair $signerKeyPair, ?callable $authorizeEntryCallback = null, ?int $validUntilLedgerSeq = null) : void {

        $signerAddress = $signerKeyPair->getAccountId();

        if ($authorizeEntryCallback === null) {
            $neededSigning = $this->needsNonInvokerSigningBy();
            if (count($neededSigning) === 0) {
                throw new Exception("No unsigned non-invoker auth entries; maybe you already signed?");
            }
            if (!in_array($signerAddress, $neededSigning)) {
                throw new Exception("No auth entries for public key {$signerAddress}");
            }
            if ($signerKeyPair->getPrivateKey() === null) {
                throw new Exception("You must provide a signer keypair containing the private key.");
            }
        }
        if ($this->tx === null) {
            throw new Exception("Transaction has not yet been simulated");
        }

        $expirationLedger = $validUntilLedgerSeq;
        if ($expirationLedger === null) {
            $getLatestLedgerResponse = $this->server->getLatestLedger();
            if ($getLatestLedgerResponse->sequence === null) {
                throw new Exception("Could not fetch latest ledger sequence from server");
            }
            $expirationLedger = $getLatestLedgerResponse->sequence + StellarConstants::DEFAULT_LEDGER_EXPIRATION_OFFSET;
        }

        $ops = $this->tx->getOperations();
        if(count($ops) ===0) {
            throw new Exception("Unexpected Transaction type; no operations found.");
        }
        $invokeHostFuncOp = $ops[0];
        if ($invokeHostFuncOp instanceof InvokeHostFunctionOperation) {
            $authEntries = $invokeHostFuncOp->auth;
            for($i = 0; $i < count($authEntries); $i++) {
                $entry = $authEntries[$i];
                $addressCredentials = $entry->credentials->addressCredentials;
                if ($addressCredentials === null ||
                    $addressCredentials->address->accountId === null ||
                    $addressCredentials->address->accountId !== $signerAddress) {
                    continue;
                }
                $entry->credentials->addressCredentials->signatureExpirationLedger = $expirationLedger;
                if ($authorizeEntryCallback !== null) {
                    $authorized = $authorizeEntryCallback($entry, $this->options->clientOptions->network);
                } else {
                    $entry->sign(signer: $signerKeyPair, network: $this->options->clientOptions->network);
                    $authorized = $entry;
                }
                $authEntries[$i] = $authorized;
            }
            $this->tx->setSorobanAuth($authEntries);
        }  else {
            throw new Exception("Unexpected Transaction type; no invoke host function operations found.");
        }
    }


    /**
     * Get a list of accounts, other than the invoker of the simulation, that
     *  need to sign auth entries in this transaction.
     *
     * Soroban allows multiple people to sign a transaction. Someone needs to
     *  sign the final transaction envelope; this person/account is called the
     *  _invoker_, or _source_. Other accounts might need to sign individual auth
     *  entries in the transaction, if they're not also the invoker.
     *
     *  This function returns a list of accounts that need to sign auth entries,
     *  assuming that the same invoker/source account will sign the final
     *  transaction envelope as signed the initial simulation.
     *
     * @param bool $includeAlreadySigned if the list should include the needed signers that already signed their auth entries.
     * @return array<string> the list of account ids of the accounts that need to sign auth entries.
     *
     * @throws Exception
     */
    public function needsNonInvokerSigningBy(bool $includeAlreadySigned = false) : array {
        if($this->tx === null) {
            throw new Exception("Transaction has not yet been simulated");
        }
        $ops = $this->tx->getOperations();
        if(count($ops) === 0) {
            throw new Exception("Unexpected Transaction type; no operations found.");
        }
        /**
         * @var array<string> $needed
         */
        $needed = array();
        $invokeHostFuncOp = $ops[0];
        if ($invokeHostFuncOp instanceof InvokeHostFunctionOperation) {
            $authEntries = $invokeHostFuncOp->auth;
            foreach ($authEntries as $entry) {
                $addressCredentials = $entry->credentials->addressCredentials;
                if($addressCredentials !== null) {
                    if($includeAlreadySigned || $addressCredentials->signature->type->value === XdrSCValType::SCV_VOID) {
                        array_push($needed, $addressCredentials->getAddress()->accountId ?? $addressCredentials->getAddress()->contractId);
                    }
                }
            }
        } else {
            throw new Exception("Unexpected Transaction type; no invoke host function operations found.");
        }
        return $needed;
    }

    /**
     * Determines if this is a read-only transaction
     *
     * Read-only transactions have no authorization entries and an empty read-write footprint,
     * meaning they don't modify any ledger state. These transactions only need simulation and
     * don't require signing or submission to the network.
     *
     * @return bool True if the transaction is read-only, false if it modifies state
     * @throws Exception If the transaction has not been simulated
     */
    public function isReadCall() : bool {
        $res = $this->getSimulationData();
        $authsCount = 0;
        if ($res->auth !== null && count($res->auth) > 0) {
            $authsCount = count($res->auth);
        }
        $writeLength = count($res->transactionData->resources->footprint->readWrite);
        return ($authsCount === 0 && $writeLength === 0);
    }

    /**
     * Retrieves the simulation results from the transaction
     *
     * Returns the parsed simulation data including the returned value, transaction data,
     * and authorization entries. This method caches the result after first call.
     *
     * @return SimulateHostFunctionResult The simulation result with return value and transaction data
     * @throws Exception If the transaction has not been simulated or simulation failed
     */
    public function getSimulationData() : SimulateHostFunctionResult {
        if ($this->simulationResult !== null) {
            return $this->simulationResult;
        }
        if ($this->simulationResponse === null) {
            throw new Exception("Transaction has not yet been simulated");
        }
        if ($this->simulationResponse->error !== null || $this->simulationResponse->resultError !== null || $this->simulationResponse->transactionData === null) {
            throw new Exception("Transaction simulation failed: {$this->simulationResponse->resultError}");
        }
        if ($this->simulationResponse->restorePreamble !== null) {
            throw new Exception("You need to restore some contract state before you can invoke this method.\n" .
                "You can set `restore` to true in the options in order to " .
                "automatically restore the contract state when needed.");
        }
        $resultValue = XdrSCVal::forVoid();
        if ($this->simulationResponse->results !== null) {
            $resultsArray = $this->simulationResponse->results->toArray();
            if (count($resultsArray) > 0) {
                $xdr = $resultsArray[0]->getXdr();
                $resultValue = XdrSCVal::fromBase64Xdr($xdr);
            }
        }
        $this->simulationResult = new SimulateHostFunctionResult($this->simulationResponse->transactionData, $resultValue, $this->simulationResponse->getSorobanAuth());
        return $this->simulationResult;
    }
    /**
     * Builds a transaction to restore expired ledger entries
     *
     * Creates a RestoreFootprint operation with the necessary transaction data and fees
     * to restore archived contract state back to the ledger.
     *
     * @param AssembledTransactionOptions $options Configuration for the restore transaction
     * @param XdrSorobanTransactionData $transactionData The footprint data identifying entries to restore
     * @param int $fee The minimum resource fee for the restore operation
     * @return AssembledTransaction The restore transaction ready to be signed and sent
     * @throws GuzzleException If fetching the account or simulation fails
     */
    private static function buildFootprintRestoreTransaction(AssembledTransactionOptions $options,
                                                             XdrSorobanTransactionData   $transactionData,
                                                             int                         $fee): AssembledTransaction {

        $restoreTx = new AssembledTransaction(options: $options);
        $restoreOp = (new RestoreFootprintOperationBuilder())->build();
        $sourceAccount = $restoreTx->getSourceAccount();
        $restoreTx->raw = (new TransactionBuilder(sourceAccount: $sourceAccount))->addOperation($restoreOp)
            ->setMaxOperationFee($fee)
            ->setTimeBounds(new TimeBounds((new DateTime())->modify("- " . NetworkConstants::DEFAULT_TIME_BOUNDS_OFFSET_SECONDS . " seconds"),
                (new DateTime())->modify("+ " . $restoreTx->options->methodOptions->timeoutInSeconds ." seconds")));
        $restoreTx->tx = $restoreTx->raw->build();
        $restoreTx->tx->setSorobanTransactionData($transactionData);
        $restoreTx->simulate(restore:false);
        return $restoreTx;
    }
    /**
     * Restores expired ledger entries required by the transaction
     *
     * When contract state entries have been archived, they must be restored before the contract
     * can be invoked. This method builds, signs, and sends a RestoreFootprint transaction to
     * restore the necessary ledger entries back to the active state.
     *
     * The restore preamble is typically received from a simulation response when archived entries
     * are detected. The method requires a source account with private key to sign the restore transaction.
     *
     * @param RestorePreamble $restorePreamble The restore data from simulation including footprint and fees
     * @return GetTransactionResponse The result of the restore transaction
     * @throws GuzzleException If the RPC request fails
     * @throws Exception If the source account has no private key or the restore fails
     */
    public function restoreFootprint(RestorePreamble $restorePreamble) : GetTransactionResponse {

        $restoreTx = self::buildFootprintRestoreTransaction($this->options,
            $restorePreamble->transactionData, $restorePreamble->minResourceFee);

        return $restoreTx->signAndSend();
    }

    /**
     * Polls the transaction status until it reaches a terminal state
     *
     * Repeatedly queries getTransaction until the transaction is found or the configured
     * timeout is exceeded. Uses a 3-second delay between polls.
     *
     * @param string $transactionId The hash of the transaction to poll
     * @return GetTransactionResponse The final transaction status
     * @throws GuzzleException If the RPC request fails
     * @throws Exception If the timeout is exceeded before the transaction completes
     */
    private function pollStatus(string $transactionId) : GetTransactionResponse {
        $statusResponse = null;
        $status = GetTransactionResponse::STATUS_NOT_FOUND;
        $waitTime = 3;
        $waited = 0;
        while ($status === GetTransactionResponse::STATUS_NOT_FOUND) {
            if ($waited > $this->options->methodOptions->timeoutInSeconds) {
                throw new Exception("Interrupted after waiting {$this->options->methodOptions->timeoutInSeconds} 
                seconds (options->timeoutInSeconds) for the transaction {$transactionId} to complete.");
            }
            sleep($waitTime);
            $waited += $waitTime;
            $statusResponse = $this->server->getTransaction($transactionId);
            $status = $statusResponse->status;
        }
        return $statusResponse;
    }
    /**
     * Fetches the source account from the ledger
     *
     * Retrieves the account data including the current sequence number from the RPC server.
     *
     * @return Account The source account object
     * @throws Exception If the account is not found
     * @throws GuzzleException If the RPC request fails
     */
    private function getSourceAccount(): Account {
        $account = $this->server->getAccount($this->options->clientOptions->sourceAccountKeyPair->getAccountId());
        if ($account === null) {
            throw new Exception("Account {$this->options->clientOptions->sourceAccountKeyPair->getAccountId()} not found");
        }
        return $account;
    }

}