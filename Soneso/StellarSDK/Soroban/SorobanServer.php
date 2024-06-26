<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\Soroban\Requests\GetEventsRequest;
use Soneso\StellarSDK\Soroban\Requests\SimulateTransactionRequest;
use Soneso\StellarSDK\Soroban\Responses\GetEventsResponse;
use Soneso\StellarSDK\Soroban\Responses\GetHealthResponse;
use Soneso\StellarSDK\Soroban\Responses\GetLatestLedgerResponse;
use Soneso\StellarSDK\Soroban\Responses\GetLedgerEntriesResponse;
use Soneso\StellarSDK\Soroban\Responses\GetNetworkResponse;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\LedgerEntry;
use Soneso\StellarSDK\Soroban\Responses\SendTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SimulateTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SorobanRpcResponse;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrContractCodeEntry;
use Soneso\StellarSDK\Xdr\XdrContractDataDurability;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyAccount;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyContractCode;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyContractData;
use Soneso\StellarSDK\Xdr\XdrSCVal;

/**
 * This class helps you to connect to a local or remote soroban rpc server
 * and send requests to the server. It parses the results and provides
 * corresponding response objects.
 */
class SorobanServer
{
    private string $endpoint;
    private array $headers = array();
    private Client $httpClient;

    private const GET_HEALTH = "getHealth";
    private const GET_NETWORK = "getNetwork";
    private const SIMULATE_TRANSACTION = "simulateTransaction";
    private const SEND_TRANSACTION = "sendTransaction";
    private const GET_TRANSACTION = "getTransaction";
    private const GET_LEDGER_ENTRIES = "getLedgerEntries";
    private const GET_LATEST_LEDGER = "getLatestLedger";
    private const GET_EVENTS = "getEvents";

    public bool $enableLogging = false;

    /**
     * Helps you to communicate with a remote soroban rpc server.
     * @param string $endpoint remote soroban rpc server endpoint
     */
    public function __construct(string $endpoint)
    {
        $this->endpoint = $endpoint;
        $this->httpClient = new Client([
            'base_uri' => $this->endpoint,
            'exceptions' => false,
        ]);
        $this->headers = array_merge($this->headers, RequestBuilder::HEADERS);
        $this->headers  = array_merge($this->headers, ['Content-Type' => "application/json"]);
    }

    /**
     * General node health check request.
     * @throws GuzzleException
     */
    public function getHealth() : GetHealthResponse {
        $body = $this->prepareRequest(self::GET_HEALTH);
        return $this->request($body, self::GET_HEALTH);
    }


    /**
     * Fetch information about the network.
     * @return GetNetworkResponse
     * @throws GuzzleException
     */
    public function getNetwork() : GetNetworkResponse {
        $body = $this->prepareRequest(self::GET_NETWORK);
        return $this->request($body, self::GET_NETWORK);
    }

    /**
     * Submit a trial contract invocation to get back return values, expected ledger footprint, and expected costs.
     * @param SimulateTransactionRequest $request request containing the transaction to submit and the resource config.
     * @return SimulateTransactionResponse response.
     * @throws GuzzleException
     */
    public function simulateTransaction(SimulateTransactionRequest $request) : SimulateTransactionResponse {
        $body = $this->prepareRequest(self::SIMULATE_TRANSACTION, $request->getRequestParams());
        return $this->request($body, self::SIMULATE_TRANSACTION);
    }

    /**
     * Submit a real transaction to the stellar network. This is the only way to make changes “on-chain”.
     * Unlike Horizon, this does not wait for transaction completion. It simply validates and enqueues the transaction.
     * Clients should call getTransactionStatus to learn about transaction success/failure.
     * @param Transaction $transaction to submit.
     * @return SendTransactionResponse response.
     * @throws GuzzleException
     */
    public function sendTransaction(Transaction $transaction) : SendTransactionResponse {
        $body = $this->prepareRequest(self::SEND_TRANSACTION, ['transaction' => $transaction->toEnvelopeXdrBase64()]);
        return $this->request($body, self::SEND_TRANSACTION);
    }

    /**
     * Clients will poll this to tell when the transaction has been completed.
     * @param String $transactionId of the transaction to be checked.
     * @return GetTransactionResponse response.
     * @throws GuzzleException
     */
    public function getTransaction(String $transactionId) : GetTransactionResponse {
        $body = $this->prepareRequest(self::GET_TRANSACTION, ['hash' => $transactionId]);
        return $this->request($body, self::GET_TRANSACTION);
    }

    /**
     * For reading the current value of ledger entries directly.
     * Allows you to directly inspect the current state of a contract, a contract’s code, or any other ledger entry.
     * This is a backup way to access your contract data which may not be available via events or simulateTransaction.
     * To fetch contract wasm byte-code, use the ContractCode ledger entry key.
     * @param array $base64EncodedKeys to request the ledger entry for.
     * @return GetLedgerEntriesResponse response.
     * @throws GuzzleException
     */
    public function getLedgerEntries(array $base64EncodedKeys) : GetLedgerEntriesResponse {
        $body = $this->prepareRequest(self::GET_LEDGER_ENTRIES, ['keys' => $base64EncodedKeys]);
        return $this->request($body, self::GET_LEDGER_ENTRIES);
    }

    public function getLatestLedger() : GetLatestLedgerResponse {
        $body = $this->prepareRequest(self::GET_LATEST_LEDGER);
        return $this->request($body, self::GET_LATEST_LEDGER);
    }

    public function getEvents(GetEventsRequest $request) : GetEventsResponse {
        $body = $this->prepareRequest(self::GET_EVENTS, $request->getRequestParams());
        return $this->request($body, self::GET_EVENTS);
    }

    /**
     * Loads the contract source code (including source code - wasm bytes) for a given wasm id.
     * @param string $wasmId
     * @return XdrContractCodeEntry|null The contract code entry if found
     * @throws GuzzleException
     */
    public function loadContractCodeForWasmId(string $wasmId) : ?XdrContractCodeEntry {
        $ledgerKey = new XdrLedgerKey(XdrLedgerEntryType::CONTRACT_CODE());
        $ledgerKey->contractCode = new XdrLedgerKeyContractCode(hex2bin($wasmId));
        $ledgerEntries = $this->getLedgerEntries([$ledgerKey->toBase64Xdr()]);
        if ($ledgerEntries->entries !== null && count($ledgerEntries->entries) > 0) {
            $ledgerEntry = $ledgerEntries->entries[0];
            if ($ledgerEntry instanceof LedgerEntry) {
                return $ledgerEntry->getLedgerEntryDataXdr()->contractCode;
            }
        }
        return null;
    }

    /**
     * Loads the contract code entry (including source code - wasm bytes) for a given contract id.
     * @param string $contractId
     * @return XdrContractCodeEntry|null The contract code entry if found
     * @throws GuzzleException
     */
    public function loadContractCodeForContractId(string $contractId) : ?XdrContractCodeEntry {
        $ledgerKey = new XdrLedgerKey(XdrLedgerEntryType::CONTRACT_DATA());
        $ledgerKey->contractData = new XdrLedgerKeyContractData(
            Address::fromContractId($contractId)->toXdr(),
            XdrSCVal::forLedgerKeyContractInstance(),
            XdrContractDataDurability::PERSISTENT());

        $ledgerEntries = $this->getLedgerEntries([$ledgerKey->toBase64Xdr()]);
        if ($ledgerEntries->entries !== null && count($ledgerEntries->entries) > 0) {
            $ledgerEntryData = $ledgerEntries->entries[0]->getLedgerEntryDataXdr();
            if ($ledgerEntryData->contractData != null && $ledgerEntryData->contractData->val->instance?->executable->wasmIdHex != null) {
                $wasmId = $ledgerEntryData->contractData->val->instance->executable->wasmIdHex;
                return $this->loadContractCodeForWasmId($wasmId);
            }
        }
        return null;
    }

    /**
     * Fetches a minimal set of current info about a Stellar account. Needed to get the current sequence
     * number for the account, so you can build a successful transaction.
     * Returns null if account was not found.
     * @param string $accountId th account id to request the data for ("G...")
     * @return Account|null The account object or null if not found.
     * @throws GuzzleException
     */
    public function getAccount(string $accountId): ?Account {
        $ledgerKey = new XdrLedgerKey(XdrLedgerEntryType::ACCOUNT());
        $ledgerKey->account = new XdrLedgerKeyAccount(
            new XdrAccountID($accountId)
        );
        $ledgerEntries = $this->getLedgerEntries([$ledgerKey->toBase64Xdr()]);
        if ($ledgerEntries->entries !== null && count($ledgerEntries->entries) > 0) {
            $accountEntry = $ledgerEntries->entries[0]->getLedgerEntryDataXdr()->account;
            if ($accountEntry !== null) {
                $accountId = $accountEntry->getAccountID()->getAccountId();
                $seqNr = $accountEntry->seqNum->getValue();
                return new Account($accountId, $seqNr);
            }
        }
        return null;
    }

    /**
     * Reads the current value of contract data ledger entries directly.
     *
     * @param string $contractId id of the contract containing the data to load.
     * @param XdrSCVal $key of the contract data to load.
     * @param XdrContractDataDurability $durability keyspace that this ledger key belongs to, which is either
     * XdrContractDataDurability::PERSISTENT() or XdrContractDataDurability::TEMPORARY().
     * @return LedgerEntry|null Ledger Entry if found otherwise null.
     * @throws GuzzleException
     */
    public function getContractData(
        string $contractId,
        XdrSCVal $key,
        XdrContractDataDurability $durability,
    ) : ?LedgerEntry {

        $ledgerKey = new XdrLedgerKey(XdrLedgerEntryType::CONTRACT_DATA());
        $ledgerKey->contractData = new XdrLedgerKeyContractData(
            Address::fromContractId($contractId)->toXdr(),
            $key,
            $durability,
        );

        $ledgerEntries = $this->getLedgerEntries([$ledgerKey->toBase64Xdr()]);
        if ($ledgerEntries->entries !== null && count($ledgerEntries->entries) > 0) {
            return $ledgerEntries->entries[0];
        }
        return null;
    }

    /**
     * Sends request to remote Soroban RPC Server.
     * @param string $body jsonrpc 2.0 body
     * @param string $requestType the request type such as SIMULATE_TRANSACTION
     * @return SorobanRpcResponse response.
     * @throws GuzzleException
     */
    private function request(string $body, string $requestType) : SorobanRpcResponse {
        $request = new Request("POST", $this->endpoint, $this->headers, $body);
        $response = $this->httpClient->send($request);
        return $this->handleRpcResponse($response, $requestType);
    }

    /** Handles the response obtained from the remote Soroban RPC Server.
     *  Converts received data into the corresponding response object.
     * @param ResponseInterface $response the general http response
     * @param string $requestType the request type such as SIMULATE_TRANSACTION
     * @return SorobanRpcResponse
     */
    private function handleRpcResponse(ResponseInterface $response, string $requestType) : SorobanRpcResponse
    {
        $content = $response->getBody()->__toString();
        if ($this->enableLogging) {
            print($requestType." response: ".$content . PHP_EOL);
        }
        // not success
        // this should normally not happen since it will be handled by gruzzle (throwing corresponding gruzzle exception)
        if (300 <= $response->getStatusCode()) {
            throw new \RuntimeException($content);
        }

        // success
        $jsonData = @json_decode($content, true);

        if (null === $jsonData && json_last_error() != JSON_ERROR_NONE) {
            throw new \InvalidArgumentException(sprintf("Error in json_decode: %s", json_last_error_msg()));
        }

        $rpcResponse = match ($requestType) {
            self::GET_HEALTH => GetHealthResponse::fromJson($jsonData),
            self::GET_NETWORK => GetNetworkResponse::fromJson($jsonData),
            self::SIMULATE_TRANSACTION => SimulateTransactionResponse::fromJson($jsonData),
            self::SEND_TRANSACTION => SendTransactionResponse::fromJson($jsonData),
            self::GET_TRANSACTION => GetTransactionResponse::fromJson($jsonData),
            self::GET_LEDGER_ENTRIES => GetLedgerEntriesResponse::fromJson($jsonData),
            self::GET_LATEST_LEDGER => GetLatestLedgerResponse::fromJson($jsonData),
            self::GET_EVENTS => GetEventsResponse::fromJson($jsonData),
            default => throw new \InvalidArgumentException(sprintf("Unknown request type: %s", $requestType)),
        };

        return $rpcResponse;
    }

    /**
     * Prepares jsonrpc 2.0 request body for the given values.
     * @param string $procedure method name
     * @param array $params parameters
     * @return string the prepared json encoded body
     */
    private function prepareRequest(string $procedure, array $params = array()) : string
    {
        $payload = array(
            'jsonrpc' => '2.0',
            'method' => $procedure,
            'id' => mt_rand()
        );

        if (!empty($params)) {
            $payload['params'] = $params;
        }

        return json_encode($payload);
    }
}