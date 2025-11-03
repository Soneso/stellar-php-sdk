<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Contract;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Soneso\StellarSDK\CreateContractWithConstructorHostFunction;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\UploadContractWasmHostFunction;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntry;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntryKind;
use Soneso\StellarSDK\Xdr\XdrSCVal;

/**
 * High-level client for interacting with deployed Soroban smart contracts
 *
 * This class provides a convenient interface for calling contract methods, deploying contracts,
 * and installing contract code. It automatically handles contract spec parsing, type conversion,
 * transaction building, simulation, and signing.
 *
 * The client is initialized with contract specification entries that define the available methods
 * and types. These specs are extracted from the contract's WASM bytecode and enable type-safe
 * method invocation with automatic conversion between PHP and Soroban types.
 *
 * @package Soneso\StellarSDK\Soroban\Contract
 * @see AssembledTransaction For low-level transaction control
 * @see ContractSpec For type conversion and spec introspection
 * @see https://developers.stellar.org/docs/smart-contracts Soroban Smart Contracts Documentation
 * @since 1.0.0
 */
class SorobanClient
{

    private const CONSTRUCTOR_FUNC = "__constructor";
    /**
     * @var array<XdrSCSpecEntry> Contract specification entries defining functions and types
     */
    private array $specEntries = array();

    /**
     * @var ClientOptions Client configuration including contract ID, network, and RPC URL
     */
    private ClientOptions $options;

    /**
     * @var array<string> Cached list of contract method names
     */
    private array $methodNames = array();

    /**
     * Private constructor for SorobanClient
     *
     * Use the static factory methods SorobanClient::forClientOptions or SorobanClient::deploy
     * to create instances. The constructor extracts method names from the spec entries.
     *
     * @param array<XdrSCSpecEntry> $specEntries Contract specification from parsed WASM bytecode
     * @param ClientOptions $options Client configuration
     */
    private function __construct(array $specEntries, ClientOptions $options)
    {
        $this->specEntries = $specEntries;
        $this->options = $options;

        foreach ($this->specEntries as $entry) {
            switch ($entry->type->value) {
                case XdrSCSpecEntryKind::SC_SPEC_ENTRY_FUNCTION_V0:
                    $function = $entry->functionV0;
                    if ($function === null || $function->name === self::CONSTRUCTOR_FUNC) {
                        break;
                    }
                    array_push($this->methodNames, $function->name);
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Creates a SorobanClient for an existing deployed contract
     *
     * Loads the contract specification from the network by fetching and parsing the contract's
     * WASM bytecode. The spec is used for type-safe method invocation.
     *
     * @param ClientOptions $options Client configuration including contract ID, network, and RPC URL
     * @return SorobanClient The initialized client ready to invoke contract methods
     * @throws GuzzleException If the RPC request fails
     * @throws Exception If the contract is not found or spec parsing fails
     */
    public static function forClientOptions(ClientOptions $options) : SorobanClient {
        $server = new SorobanServer($options->rpcUrl);
        $info = $server->loadContractInfoForContractId($options->contractId);
        if ($info != null) {
            return new SorobanClient($info->specEntries, $options);
        } else {
            throw new Exception("Could not load contract info for the contract: {$options->contractId}");
        }
    }


    /**
     * Deploys a new contract instance and creates a SorobanClient for it
     *
     * Creates a new contract instance from previously installed WASM code, optionally calling
     * the contract's constructor with the provided arguments. Returns a client for the newly
     * deployed contract.
     *
     * The contract WASM must be installed first using SorobanClient::install.
     *
     * @param DeployRequest $deployRequest Deployment parameters including WASM hash, constructor args, and salt
     * @return SorobanClient The client for the newly deployed contract
     * @throws Exception If deployment fails or contract ID cannot be extracted
     * @throws GuzzleException If the RPC request fails
     */
    public static function deploy(DeployRequest $deployRequest) : SorobanClient {
        $sourceAddress = Address::fromAccountId($deployRequest->sourceAccountKeyPair->getAccountId());
        $createContractHostFunction = new CreateContractWithConstructorHostFunction(
            address: $sourceAddress,
            wasmId: $deployRequest->wasmHash,
            constructorArgs: $deployRequest->constructorArgs ?? [],
            salt: $deployRequest->salt);

        $builder = new InvokeHostFunctionOperationBuilder($createContractHostFunction);
        $op = $builder->build();
        $clientOptions = new ClientOptions(
            sourceAccountKeyPair: $deployRequest->sourceAccountKeyPair,
            contractId: "ignored",
            network: $deployRequest->network,
            rpcUrl: $deployRequest->rpcUrl,
            enableServerLogging: $deployRequest->enableServerLogging
        );
        $options = new AssembledTransactionOptions(
            clientOptions:$clientOptions,
            methodOptions: $deployRequest->methodOptions,
            method: self::CONSTRUCTOR_FUNC,
            arguments: $deployRequest->constructorArgs,
            enableServerLogging: $deployRequest->enableServerLogging);

        $tx = AssembledTransaction::buildWithOp(operation: $op, options: $options);
        $response = $tx->signAndSend();
        $contractId = $response->getCreatedContractId();
        if ($contractId === null) {
            throw new Exception("Could not get contract id for deployed contract");
        }
        $clientOptions->contractId = StrKey::encodeContractIdHex($contractId);
        return SorobanClient::forClientOptions(options: $clientOptions);
    }

    /**
     * Installs contract WASM bytecode to the network
     *
     * Uploads the compiled contract WASM code to the ledger, making it available for deployment.
     * If the code is already installed (detected via simulation), the existing WASM hash is returned
     * without submitting a transaction unless force is true.
     *
     * @param InstallRequest $installRequest Installation parameters including WASM bytes
     * @param bool $force Force transaction submission even if code is already installed (default: false)
     * @return string The WASM hash (hex-encoded) of the installed contract code
     * @throws GuzzleException If the RPC request fails
     * @throws Exception If installation fails or WASM hash cannot be extracted
     */
    public static function install(InstallRequest $installRequest, bool $force = false) : string {

        $uploadContractHostFunction = new UploadContractWasmHostFunction($installRequest->wasmBytes);
        $op = (new InvokeHostFunctionOperationBuilder($uploadContractHostFunction))->build();
        $clientOptions = new ClientOptions(
            sourceAccountKeyPair: $installRequest->sourceAccountKeyPair,
            contractId: "ignored",
            network: $installRequest->network,
            rpcUrl: $installRequest->rpcUrl,
            enableServerLogging: $installRequest->enableServerLogging
        );
        $options = new AssembledTransactionOptions(
            clientOptions:$clientOptions,
            methodOptions: new MethodOptions(),
            method: "ignored",
            enableServerLogging: $installRequest->enableServerLogging);

        $tx = AssembledTransaction::buildWithOp(operation: $op, options: $options);
        if (!$force && $tx->isReadCall()) {
            $simulationData = $tx->getSimulationData();
            $returnedValue = $simulationData->returnedValue;
            if ($returnedValue->bytes === null) {
                throw new Exception("Could not extract wasm hash from simulation result");
            } else {
                return bin2hex($returnedValue->bytes->value);
            }
        }
        $response = $tx->signAndSend(force: $force);
        $wasmHash = $response->getWasmId();

        if ($wasmHash === null) {
            throw new Exception("Could not get wasm hash for installed contract");
        }
        return $wasmHash;
    }

    /**
     * Retrieves the contract ID of the contract this client represents
     *
     * @return string The C-prefixed contract ID address
     */
    public function getContractId() : string {
        return $this->options->contractId;
    }

    /**
     * Retrieves the contract specification entries
     *
     * @return array<XdrSCSpecEntry> Array of parsed spec entries from the contract WASM
     */
    public function getSpecEntries(): array
    {
        return $this->specEntries;
    }

    /**
     * Gets the contract specification utility for type conversion
     *
     * Returns a ContractSpec instance that can be used for advanced type conversion,
     * function argument parsing, and contract introspection.
     *
     * @return ContractSpec The contract spec utility
     */
    public function getContractSpec(): ContractSpec
    {
        return new ContractSpec($this->specEntries);
    }

    /**
     * Retrieves the client options configuration
     *
     * @return ClientOptions The client configuration
     */
    public function getOptions(): ClientOptions
    {
        return $this->options;
    }

    /**
     * Invokes a contract method with automatic read/write detection
     *
     * This is the main method for calling contract functions. It builds and simulates the transaction,
     * and for read-only calls returns the simulation result immediately. For state-changing calls,
     * it signs and sends the transaction, then waits for completion and returns the result.
     *
     * Read-only calls are detected automatically based on empty authorization and read-write footprint.
     *
     * @param string $name The name of the contract method to invoke
     * @param array<XdrSCVal>|null $args Array of XDR SCVal arguments for the method (optional)
     * @param bool $force Force signing and sending even for read-only calls (default: false)
     * @param MethodOptions|null $methodOptions Options for simulation, fees, and timeouts (optional)
     * @return XdrSCVal The method return value as an XDR SCVal
     * @throws GuzzleException If the RPC request fails
     * @throws Exception If the method does not exist or invocation fails
     */
    public function invokeMethod(string $name, ?array $args = null, bool $force = false, ?MethodOptions $methodOptions = null) : XdrSCVal {
        $tx = $this->buildInvokeMethodTx($name, $args, $methodOptions);

        if(!$force && $tx->isReadCall()) {
            return $tx->getSimulationData()->returnedValue;
        }

        $response = $tx->signAndSend(force:$force);
        if ($response->error !== null) {
            throw new Exception("invoke {$name} failed with message: {$response->error->message} and code: {$response->error->code}");
        }

        if ($response->getStatus() !== GetTransactionResponse::STATUS_SUCCESS) {
            throw new Exception("invoke {$name} failed with result: {$response->resultXdr}");
        }

        $result = $response->getResultValue();

        if ($result === null) {
            throw new Exception("could not extract return value from {$name} invocation");
        }
        return $result;
    }

    /**
     * Builds a transaction for invoking a contract method without sending it
     *
     * Creates and simulates an AssembledTransaction for the specified method. This is useful
     * when you need to inspect the transaction, sign authorization entries for multi-signature
     * workflows, or manually control the signing and sending process.
     *
     * @param string $name The name of the contract method to invoke
     * @param array|null $args Array of XDR SCVal arguments for the method (optional)
     * @param MethodOptions|null $methodOptions Options for simulation, fees, and timeouts (optional)
     * @return AssembledTransaction The assembled transaction ready for signing and sending
     * @throws GuzzleException If the RPC request fails
     * @throws Exception If the method does not exist or transaction building fails
     */
    public function buildInvokeMethodTx(string $name, ?array $args = null, ?MethodOptions $methodOptions = null) : AssembledTransaction {
        if(!in_array($name, $this->methodNames)) {
            throw new Exception("Method '$name' does not exist");
        }
        $options = new AssembledTransactionOptions(
            clientOptions: $this->options,
            methodOptions: $methodOptions ?? new MethodOptions(),
            method: $name,
            arguments: $args,
            enableServerLogging: $this->options->enableServerLogging);
        return AssembledTransaction::build($options);
    }

    /**
     * Retrieves all available method names from the contract
     *
     * Returns the list of callable function names extracted from the contract specification,
     * excluding the constructor function.
     *
     * @return array<string> Array of method names available for invocation
     */
    public function getMethodNames(): array
    {
        return $this->methodNames;
    }

}