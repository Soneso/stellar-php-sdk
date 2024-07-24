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
use Soneso\StellarSDK\Soroban\Responses\GetFeeStatsResponse;
use Soneso\StellarSDK\Soroban\Responses\GetHealthResponse;
use Soneso\StellarSDK\Soroban\Responses\GetLatestLedgerResponse;
use Soneso\StellarSDK\Soroban\Responses\GetLedgerEntriesResponse;
use Soneso\StellarSDK\Soroban\Responses\GetNetworkResponse;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\GetVersionInfoResponse;
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
 *
 * See: https://developers.stellar.org/docs/data/rpc/api-reference
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
    private const GET_FEE_STATS = "getFeeStats";
    private const GET_VERSION_INFO = "getVersionInfo";

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
        ]);
        $this->headers = array_merge($this->headers, RequestBuilder::HEADERS);
        $this->headers  = array_merge($this->headers, ['Content-Type' => "application/json"]);
    }

    /**
     * General node health check request.
     * See: https://developers.stellar.org/docs/data/rpc/api-reference/methods/getHealth
     *
     * @return GetHealthResponse in case of success.
     * @throws GuzzleException if any request problem occurs.
     */
    public function getHealth() : GetHealthResponse {
        $body = $this->prepareRequest(self::GET_HEALTH);
        $result = $this->request($body, self::GET_HEALTH);
        assert($result instanceof GetHealthResponse);
        return $result;
    }


    /**
     * General information about the currently configured network.
     * The response will contain all the information needed to successfully submit transactions to the network this node serves.
     * See: https://developers.stellar.org/docs/data/rpc/api-reference/methods/getNetwork
     *
     * @return GetNetworkResponse in case of success.
     * @throws GuzzleException if any request problem occurs.
     */
    public function getNetwork() : GetNetworkResponse {
        $body = $this->prepareRequest(self::GET_NETWORK);
        $result = $this->request($body, self::GET_NETWORK);
        assert($result instanceof GetNetworkResponse);
        return $result;
    }


    /**
     * Statistics for charged inclusion fees. The inclusion fee statistics are calculated from the inclusion fees
     * that were paid for the transactions to be included onto the ledger. For Soroban transactions and Stellar transactions,
     * they each have their own inclusion fees and own surge pricing. Inclusion fees are used to prevent spam and
     * prioritize transactions during network traffic surge.
     * See: https://developers.stellar.org/docs/data/rpc/api-reference/methods/getFeeStatsh
     *
     * @return GetFeeStatsResponse in case of success.
     * @throws GuzzleException if any request problem occurs.
     */
    public function getFeeStats() : GetFeeStatsResponse {
        $body = $this->prepareRequest(self::GET_FEE_STATS);
        $result = $this->request($body, self::GET_FEE_STATS);
        assert($result instanceof GetFeeStatsResponse);
        return $result;
    }

    /**
     * Version information about the RPC and Captive core. RPC manages its own, pared-down version of Stellar Core
     * optimized for its own subset of needs.
     * See: https://developers.stellar.org/docs/data/rpc/api-reference/methods/getVersionInfo
     *
     * @return GetVersionInfoResponse in case of success.
     * @throws GuzzleException if any request problem occurs.
     */
    public function getVersionInfo() : GetVersionInfoResponse {
        $body = $this->prepareRequest(self::GET_VERSION_INFO);
        $result = $this->request($body, self::GET_VERSION_INFO);
        assert($result instanceof GetVersionInfoResponse);
        return $result;
    }

    /**
     * Submit a trial contract invocation to get back return values, expected ledger footprint, and expected costs.
     * See: https://developers.stellar.org/docs/data/rpc/api-reference/methods/simulateTransaction
     *
     * @param SimulateTransactionRequest $request request containing the transaction to submit and the resource config.
     * @return SimulateTransactionResponse response in case of success.
     * @throws GuzzleException if any request problem occurs.
     */
    public function simulateTransaction(SimulateTransactionRequest $request) : SimulateTransactionResponse {
        $body = $this->prepareRequest(self::SIMULATE_TRANSACTION, $request->getRequestParams());
        $result = $this->request($body, self::SIMULATE_TRANSACTION);
        assert($result instanceof SimulateTransactionResponse);
        return $result;
    }

    /**
     * Submit a real transaction to the stellar network. This is the only way to make changes “on-chain”.
     * Unlike Horizon, this does not wait for transaction completion. It simply validates and enqueues the transaction.
     * Clients should call getTransactionStatus to learn about transaction success/failure.
     * See: https://developers.stellar.org/docs/data/rpc/api-reference/methods/sendTransaction
     *
     * @param Transaction $transaction to submit.
     * @return SendTransactionResponse response in case of success.
     * @throws GuzzleException if any request problem occurs.
     */
    public function sendTransaction(Transaction $transaction) : SendTransactionResponse {
        $body = $this->prepareRequest(self::SEND_TRANSACTION, ['transaction' => $transaction->toEnvelopeXdrBase64()]);
        $result = $this->request($body, self::SEND_TRANSACTION);
        assert($result instanceof SendTransactionResponse);
        return $result;
    }

    /**
     * The getTransaction method provides details about the specified transaction.
     * Clients are expected to periodically query this method to ascertain when a transaction has been
     * successfully recorded on the blockchain.
     * See: https://developers.stellar.org/docs/data/rpc/api-reference/methods/getTransaction
     *
     * @param String $transactionId of the transaction to be checked.
     * @return GetTransactionResponse response in case of success.
     * @throws GuzzleException if any request problem occurs.
     */
    public function getTransaction(String $transactionId) : GetTransactionResponse {
        $body = $this->prepareRequest(self::GET_TRANSACTION, ['hash' => $transactionId]);
        $result = $this->request($body, self::GET_TRANSACTION);
        assert($result instanceof GetTransactionResponse);
        return $result;
    }

    /**
     * For reading the current value of ledger entries directly.
     * Allows you to directly inspect the current state of a contract, a contract’s code, or any other ledger entry.
     * This is a backup way to access your contract data which may not be available via events or simulateTransaction.
     * To fetch contract wasm byte-code, use the ContractCode ledger entry key.
     * See: https://developers.stellar.org/docs/data/rpc/api-reference/methods/getLedgerEntries
     *
     * @param array $base64EncodedKeys to request the ledger entry for.
     * @return GetLedgerEntriesResponse response in case of success.
     * @throws GuzzleException if any request problem occurs.
     */
    public function getLedgerEntries(array $base64EncodedKeys) : GetLedgerEntriesResponse {
        $body = $this->prepareRequest(self::GET_LEDGER_ENTRIES, ['keys' => $base64EncodedKeys]);
        $result = $this->request($body, self::GET_LEDGER_ENTRIES);
        assert($result instanceof GetLedgerEntriesResponse);
        return $result;
    }

    /**
     * For finding out the current latest known ledger of this node. This is a subset of the ledger info from Horizon.
     * See: https://developers.stellar.org/docs/data/rpc/api-reference/methods/getLatestLedger
     *
     * @return GetLatestLedgerResponse response in case of success.
     * @throws GuzzleException if any request problem occurs.
     */
    public function getLatestLedger() : GetLatestLedgerResponse {
        $body = $this->prepareRequest(self::GET_LATEST_LEDGER);
        $result = $this->request($body, self::GET_LATEST_LEDGER);
        assert($result instanceof GetLatestLedgerResponse);
        return $result;
    }

    /**
     * Clients can request a filtered list of events emitted by a given ledger range.
     * Soroban-RPC will support querying within a maximum 24 hours of recent ledgers.
     * See: https://developers.stellar.org/docs/data/rpc/api-reference/methods/getEvents
     *
     * @param GetEventsRequest $request containing the request parameters
     * @return GetEventsResponse response in case of success.
     * @throws GuzzleException if any request problem occurs.
     */
    public function getEvents(GetEventsRequest $request) : GetEventsResponse {
        $body = $this->prepareRequest(self::GET_EVENTS, $request->getRequestParams());
        $result = $this->request($body, self::GET_EVENTS);
        assert($result instanceof GetEventsResponse);
        return $result;
    }

    /**
     * Loads the contract source code (including source code - wasm bytes) for a given wasm id.
     * @param string $wasmId
     * @return XdrContractCodeEntry|null The contract code entry if found
     * @throws GuzzleException if any request problem occurs.
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
     * @throws GuzzleException if any request problem occurs.
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
     * @throws GuzzleException if any request problem occurs.
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
     * @throws GuzzleException if any request problem occurs.
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
     * @throws GuzzleException if any request problem occurs.
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
            self::GET_FEE_STATS => GetFeeStatsResponse::fromJson($jsonData),
            self::GET_VERSION_INFO => GetVersionInfoResponse::fromJson($jsonData),
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