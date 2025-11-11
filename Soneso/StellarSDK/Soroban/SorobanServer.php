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
use Soneso\StellarSDK\Soroban\Exceptions\SorobanContractParserException;
use Soneso\StellarSDK\Soroban\Requests\GetEventsRequest;
use Soneso\StellarSDK\Soroban\Requests\GetLedgersRequest;
use Soneso\StellarSDK\Soroban\Requests\GetTransactionsRequest;
use Soneso\StellarSDK\Soroban\Requests\SimulateTransactionRequest;
use Soneso\StellarSDK\Soroban\Responses\GetEventsResponse;
use Soneso\StellarSDK\Soroban\Responses\GetFeeStatsResponse;
use Soneso\StellarSDK\Soroban\Responses\GetHealthResponse;
use Soneso\StellarSDK\Soroban\Responses\GetLatestLedgerResponse;
use Soneso\StellarSDK\Soroban\Responses\GetLedgerEntriesResponse;
use Soneso\StellarSDK\Soroban\Responses\GetLedgersResponse;
use Soneso\StellarSDK\Soroban\Responses\GetNetworkResponse;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionsResponse;
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
 * Main entry point for interacting with Soroban RPC servers
 *
 * This class provides methods for communicating with a Soroban RPC server to interact with
 * smart contracts on the Stellar network. It handles transaction simulation, submission,
 * ledger queries, event retrieval, and contract code loading. All RPC methods follow the
 * JSON-RPC 2.0 specification and return strongly-typed response objects.
 *
 * The server handles automatic JSON-RPC request construction and response parsing,
 * converting raw JSON responses into typed PHP objects for type-safe contract interaction.
 *
 * @package Soneso\StellarSDK\Soroban
 * @see https://developers.stellar.org/docs/data/rpc/api-reference Soroban RPC API Reference
 * @see https://developers.stellar.org/docs/smart-contracts Soroban Smart Contracts Documentation
 * @since 1.0.0
 */
class SorobanServer
{
    /**
     * @var string the RPC server endpoint URL
     */
    private string $endpoint;

    /**
     * @var array<string,string> HTTP headers for RPC requests
     */
    private array $headers = array();

    /**
     * @var Client Guzzle HTTP client for making requests
     */
    private Client $httpClient;

    /**
     * @var string RPC method name for health checks
     */
    private const GET_HEALTH = "getHealth";

    /**
     * @var string RPC method name for network information
     */
    private const GET_NETWORK = "getNetwork";

    /**
     * @var string RPC method name for transaction simulation
     */
    private const SIMULATE_TRANSACTION = "simulateTransaction";

    /**
     * @var string RPC method name for transaction submission
     */
    private const SEND_TRANSACTION = "sendTransaction";

    /**
     * @var string RPC method name for transaction status queries
     */
    private const GET_TRANSACTION = "getTransaction";

    /**
     * @var string RPC method name for transaction list queries
     */
    private const GET_TRANSACTIONS = "getTransactions";

    /**
     * @var string RPC method name for ledger queries
     */
    private const GET_LEDGERS = "getLedgers";

    /**
     * @var string RPC method name for ledger entry queries
     */
    private const GET_LEDGER_ENTRIES = "getLedgerEntries";

    /**
     * @var string RPC method name for latest ledger queries
     */
    private const GET_LATEST_LEDGER = "getLatestLedger";

    /**
     * @var string RPC method name for event queries
     */
    private const GET_EVENTS = "getEvents";

    /**
     * @var string RPC method name for fee statistics
     */
    private const GET_FEE_STATS = "getFeeStats";

    /**
     * @var string RPC method name for version information
     */
    private const GET_VERSION_INFO = "getVersionInfo";

    public bool $enableLogging = false;

    /**
     * Creates a new Soroban RPC server client
     *
     * Initializes the HTTP client and sets up default headers for JSON-RPC 2.0 communication.
     * The endpoint should be a fully qualified URL to a Soroban RPC server.
     *
     * @param string $endpoint The URL of the Soroban RPC server (e.g., "https://soroban-testnet.stellar.org")
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
     * See: https://developers.stellar.org/docs/data/rpc/api-reference/methods/getFeeStats
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
     * @param string $transactionId of the transaction to be checked.
     * @return GetTransactionResponse response in case of success.
     * @throws GuzzleException if any request problem occurs.
     */
    public function getTransaction(string $transactionId) : GetTransactionResponse {
        $body = $this->prepareRequest(self::GET_TRANSACTION, ['hash' => $transactionId]);
        $result = $this->request($body, self::GET_TRANSACTION);
        assert($result instanceof GetTransactionResponse);
        return $result;
    }

    /**
     * The getTransactions method returns a detailed list of transactions starting from the user specified starting
     * point that you can paginate as long as the pages fall within the history retention of their corresponding RPC provider.
     * @param GetTransactionsRequest $request request data.
     * @return GetTransactionsResponse response in case of success.
     * @throws GuzzleException if any request problem occurs.
     */
    public function getTransactions(GetTransactionsRequest $request) : GetTransactionsResponse {
        $body = $this->prepareRequest(self::GET_TRANSACTIONS, $request->getRequestParams());
        $result = $this->request($body, self::GET_TRANSACTIONS);
        assert($result instanceof GetTransactionsResponse);
        return $result;
    }

    /**
     * The getLedgers method returns a detailed list of ledgers starting from the user-specified startLedger.
     * Fetches ledger metadata and header information for a range of ledgers. Supports pagination.
     * See: https://developers.stellar.org/docs/data/rpc/api-reference/methods/getLedgers
     *
     * @param GetLedgersRequest $request The request parameters.
     * @return GetLedgersResponse The response containing ledger information.
     * @throws GuzzleException if any request problem occurs.
     */
    public function getLedgers(GetLedgersRequest $request) : GetLedgersResponse {
        $body = $this->prepareRequest(self::GET_LEDGERS, $request->getRequestParams());
        $result = $this->request($body, self::GET_LEDGERS);
        assert($result instanceof GetLedgersResponse);
        return $result;
    }

    /**
     * For reading the current value of ledger entries directly.
     * Allows you to directly inspect the current state of a contract, a contract’s code, or any other ledger entry.
     * This is a backup way to access your contract data which may not be available via events or simulateTransaction.
     * To fetch contract wasm byte-code, use the ContractCode ledger entry key.
     * See: https://developers.stellar.org/docs/data/rpc/api-reference/methods/getLedgerEntries
     *
     * @param array<string> $base64EncodedKeys base64-encoded XDR ledger keys to retrieve
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
     * Loads the contract source code for a given wasm id
     *
     * Retrieves the contract code entry from the ledger, including the compiled WASM bytecode.
     * The wasm id is a hex-encoded hash of the contract bytecode.
     *
     * @param string $wasmId The hex-encoded wasm id (hash) of the contract code
     * @return XdrContractCodeEntry|null The contract code entry if found, null if not found
     * @throws GuzzleException If the RPC request fails
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
     * Loads the contract source code for a given contract id
     *
     * First retrieves the contract instance data to determine the wasm id, then loads
     * the contract code entry containing the compiled WASM bytecode.
     *
     * @param string $contractId The contract id (C-prefixed address) of the deployed contract
     * @return XdrContractCodeEntry|null The contract code entry if found, null if not found
     * @throws GuzzleException If the RPC request fails
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
     * Loads and parses contract information for a given contract id
     *
     * Retrieves the contract bytecode from the ledger and parses it to extract metadata
     * including environment meta, contract spec entries, and contract meta information.
     * The contract spec can be used to introspect available functions and types.
     *
     * @param string $contractId The contract id (C-prefixed address) of the deployed contract
     * @return SorobanContractInfo|null The parsed contract information or null if contract not found
     * @throws SorobanContractParserException If parsing the WASM bytecode fails
     * @throws GuzzleException If the RPC request fails
     */
    public function loadContractInfoForContractId(string $contractId) :?SorobanContractInfo {
        $contractCodeEntry =  self::loadContractCodeForContractId($contractId);
        if ($contractCodeEntry === null) {
            return null;
        }
        $byteCode = $contractCodeEntry->code->value;
        return SorobanContractParser::parseContractByteCode($byteCode);
    }

    /**
     * Loads and parses contract information for a given wasm id
     *
     * Retrieves the contract bytecode from the ledger and parses it to extract metadata
     * including environment meta, contract spec entries, and contract meta information.
     * The contract spec can be used to introspect available functions and types.
     *
     * @param string $wasmId The hex-encoded wasm id (hash) of the contract code
     * @return SorobanContractInfo|null The parsed contract information or null if contract not found
     * @throws SorobanContractParserException If parsing the WASM bytecode fails
     * @throws GuzzleException If the RPC request fails
     */
    public function loadContractInfoForWasmId(string $wasmId) :?SorobanContractInfo {
        $contractCodeEntry =  self::loadContractCodeForWasmId($wasmId);
        if ($contractCodeEntry === null) {
            return null;
        }
        $byteCode = $contractCodeEntry->code->value;
        return SorobanContractParser::parseContractByteCode($byteCode);
    }

    /**
     * Retrieves current account information from the ledger
     *
     * Fetches a minimal set of account data including the current sequence number,
     * which is required for building and submitting transactions. This is more efficient
     * than using Horizon for simple account lookups when working with Soroban.
     *
     * @param string $accountId The account id (G-prefixed address) to query
     * @return Account|null The account object with current sequence number, or null if not found
     * @throws GuzzleException If the RPC request fails
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
     * Sends a JSON-RPC 2.0 request to the Soroban RPC server
     *
     * Executes the HTTP POST request and handles the response by parsing it
     * into the appropriate typed response object based on the request type.
     *
     * @param string $body The JSON-RPC 2.0 request body as JSON string
     * @param string $requestType The RPC method name (e.g., "simulateTransaction", "sendTransaction")
     * @return SorobanRpcResponse The parsed response object
     * @throws GuzzleException If the HTTP request fails
     */
    private function request(string $body, string $requestType) : SorobanRpcResponse {
        $request = new Request("POST", $this->endpoint, $this->headers, $body);
        $response = $this->httpClient->send($request);
        return $this->handleRpcResponse($response, $requestType);
    }

    /**
     * Handles and parses the HTTP response from the Soroban RPC server
     *
     * Converts the raw JSON response into a strongly-typed response object based on
     * the request type. Handles error responses and validates JSON parsing.
     *
     * @param ResponseInterface $response The PSR-7 HTTP response from the server
     * @param string $requestType The RPC method name used to determine response type
     * @return SorobanRpcResponse The parsed and typed response object
     * @throws \RuntimeException If the HTTP status indicates an error
     * @throws \InvalidArgumentException If JSON parsing fails
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
            self::GET_TRANSACTIONS => GetTransactionsResponse::fromJson($jsonData),
            self::GET_LEDGERS => GetLedgersResponse::fromJson($jsonData),
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
     * Prepares a JSON-RPC 2.0 request body
     *
     * Constructs a properly formatted JSON-RPC 2.0 request with method name,
     * parameters, and a random request id.
     *
     * @param string $procedure The RPC method name to call
     * @param array $params The parameters to pass to the method (optional)
     * @return string The JSON-encoded request body
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