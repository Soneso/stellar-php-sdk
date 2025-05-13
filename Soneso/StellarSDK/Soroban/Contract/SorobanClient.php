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
 * Represents a Soroban contract and helps you to interact with the contract, such as by invoking a contract method.
 */
class SorobanClient
{

    private const CONSTRUCTOR_FUNC = "__constructor";
    /**
     * Can be obtained by parsing the contract byte code using SorobanContractParser or SorobanServer->loadContractInfoForContractId
     * @var array<XdrSCSpecEntry> $specEntries
     */
    private array $specEntries = array();
    /**
     * @var ClientOptions $options client options for interacting with soroban.
     */
    private ClientOptions $options;

    /**
     * @var array<string> $methodNames contract method names extracted from the spec entries
     */
    private array $methodNames = array();

    /**
     * Private constructor. Use `SorobanClient::forClientOptions` or `SorobanClient::deploy` to construct a SorobanClient.
     *
     * @param array<XdrSCSpecEntry> $specEntries of the contract. Can be obtained by parsing the contract byte code
     * using 'SorobanContractParser' or 'SorobanServer->loadContractInfoForContractId'
     * @param ClientOptions $options client options to be used for interacting with soroban.
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
     * Loads the contract info for the contractId provided by the options, and the constructs a SorobanClient
     * by using the loaded contract info.
     *
     * @throws GuzzleException
     * @throws Exception
     */
    public static function forClientOptions(ClientOptions $options) : SorobanClient {
        $server = new SorobanServer($options->rpcUrl);
        $info = $server->loadContractInfoForContractId($options->contractId);
        return new SorobanClient($info->specEntries, $options);
    }


    /**
     * After deploying the contract it creates and returns a new SorobanClient for the deployed contract.
     * The contract must be installed before calling this method. You can use `SorobanClient::install`
     * to install the contract.
     *
     * @param DeployRequest $deployRequest deploy request data
     * @return SorobanClient the created soroban client representing the deployed contract.
     *
     * @throws Exception
     * @throws GuzzleException
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
     * Installs (uploads) the given contract code to soroban.
     * If successfully it returns the wasm hash of the installed contract as a hex string.
     *
     * @param InstallRequest $installRequest request parameters.
     * @param bool $force force singing and sending the transaction even if it is a read call. Default false.
     * @return string The wasm hash of the installed contract as a hex string.
     * @throws GuzzleException
     * @throws Exception
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
     * @return string contract id of the contract represented by this client.
     */
    public function getContractId() : string {
        return $this->options->contractId;
    }

    /**
     * @return array<XdrSCSpecEntry> spec entries of the contract represented by this client.
     */
    public function getSpecEntries(): array
    {
        return $this->specEntries;
    }

    /**
     * @return ClientOptions client options for interacting with soroban.
     */
    public function getOptions(): ClientOptions
    {
        return $this->options;
    }

    /**
     * Invokes a contract method. It can be used for read only calls and for read/write calls.
     * If it is read only call it will return the result from the simulation.
     * If you want to force signing and submission even if it is a read only call set `force`to true.
     *
     *
     * @param string $name the name of the method to invoke. Will throw an exception if the method does not exist.
     * @param array<XdrSCVal>|null $args the arguments to pass to the method call.
     * @param bool $force forces signing and sending even if it is a read call. Default: false.
     * @param MethodOptions|null $methodOptions method options for fine-tuning the call
     * @return XdrSCVal the result of the invocation.
     *
     * @throws GuzzleException
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
     * Creates an {@link AssembledTransaction} for invoking the given method.
     * This is usefully if you need to manipulate the transaction before signing and sending.
     *
     * @param string $name name of the method to invoke.
     * @param array|null $args arguments to use for the call.
     * @param MethodOptions|null $methodOptions options for fine-tuning the invocation.
     *
     * @return AssembledTransaction the transaction, that can be manipulated (e.g. sign auth entries) before sending to soroban.
     *
     * @throws GuzzleException
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
     * @return array<string> method names of the represented contract.
     */
    public function getMethodNames(): array
    {
        return $this->methodNames;
    }

}