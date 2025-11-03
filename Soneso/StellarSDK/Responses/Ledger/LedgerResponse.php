<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Ledger;

use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Responses\Response;

/**
 * Represents a closed ledger in the Stellar network
 *
 * This response contains comprehensive ledger details including sequence number, timestamps,
 * transaction and operation counts, fee pool information, base fees, reserves, protocol version,
 * and the ledger header XDR. Each ledger represents a snapshot of the network state at a specific
 * point in time, containing all successful and failed transactions processed in that ledger.
 *
 * Key fields:
 * - Ledger sequence number and hash
 * - Transaction counts (successful and failed)
 * - Operation counts and fee pool balances
 * - Base fee and reserve requirements
 * - Protocol version and network configuration
 * - Timestamps for ledger closure
 *
 * Returned by Horizon endpoints:
 * - GET /ledgers/{sequence} - Single ledger details
 * - GET /ledgers - List of ledgers
 *
 * @package Soneso\StellarSDK\Responses\Ledger
 * @see LedgerLinksResponse For related navigation links
 * @see https://developers.stellar.org/api/resources/ledgers Horizon Ledgers API
 * @since 1.0.0
 */
class LedgerResponse extends Response
{
    private string $id;
    private string $pagingToken;
    private string $hash;
    private ?string $previousHash = null;
    private BigInteger $sequence;
    private ?int $successfulTransactionCount = null;
    private ?int $failedTransactionCount = null;
    private int $operationCount;
    private ?int $txSetOperationCount = null;
    private string $closedAt;
    private string $totalCoins;
    private string $feePool;
    private int $baseFeeInStroops;
    private int $baseReserveInStroops;
    private int $maxTxSetSize;
    private int $protocolVersion;
    private string $headerXdr;
    private LedgerLinksResponse $links;

    /**
     * Gets the unique identifier for this ledger
     *
     * @return string The ledger ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Gets the paging token for this ledger in list results
     *
     * @return string The paging token used for cursor-based pagination
     */
    public function getPagingToken(): string
    {
        return $this->pagingToken;
    }

    /**
     * Gets the hash of this ledger
     *
     * @return string The ledger hash
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Gets the hash of the previous ledger in the blockchain
     *
     * @return string|null The previous ledger hash, or null if this is the genesis ledger
     */
    public function getPreviousHash(): ?string
    {
        return $this->previousHash;
    }

    /**
     * Gets the sequence number of this ledger
     *
     * @return BigInteger The ledger sequence number
     */
    public function getSequence(): BigInteger
    {
        return $this->sequence;
    }

    /**
     * Gets the number of successful transactions in this ledger
     *
     * @return int|null The count of successful transactions
     */
    public function getSuccessfulTransactionCount(): ?int
    {
        return $this->successfulTransactionCount;
    }

    /**
     * Gets the number of failed transactions in this ledger
     *
     * @return int|null The count of failed transactions
     */
    public function getFailedTransactionCount(): ?int
    {
        return $this->failedTransactionCount;
    }

    /**
     * Gets the total number of operations in this ledger
     *
     * @return int The operation count
     */
    public function getOperationCount(): int
    {
        return $this->operationCount;
    }

    /**
     * Gets the total number of operations in the transaction set
     *
     * @return int|null The transaction set operation count
     */
    public function getTxSetOperationCount(): ?int
    {
        return $this->txSetOperationCount;
    }

    /**
     * Gets the timestamp when this ledger was closed
     *
     * @return string The ledger close time in ISO 8601 format
     */
    public function getClosedAt(): string
    {
        return $this->closedAt;
    }

    /**
     * Gets the total number of lumens in existence
     *
     * @return string The total coins in the network
     */
    public function getTotalCoins(): string
    {
        return $this->totalCoins;
    }

    /**
     * Gets the sum of all transaction fees in the fee pool
     *
     * @return string The fee pool balance in stroops
     */
    public function getFeePool(): string
    {
        return $this->feePool;
    }

    /**
     * Gets the base fee charged per operation in this ledger
     *
     * @return int The base fee in stroops
     */
    public function getBaseFeeInStroops(): int
    {
        return $this->baseFeeInStroops;
    }

    /**
     * Gets the base reserve required per account
     *
     * @return int The base reserve in stroops
     */
    public function getBaseReserveInStroops(): int
    {
        return $this->baseReserveInStroops;
    }

    /**
     * Gets the maximum number of transactions this ledger can hold
     *
     * @return int The maximum transaction set size
     */
    public function getMaxTxSetSize(): int
    {
        return $this->maxTxSetSize;
    }

    /**
     * Gets the protocol version this ledger was running
     *
     * @return int The protocol version number
     */
    public function getProtocolVersion(): int
    {
        return $this->protocolVersion;
    }

    /**
     * Gets the base64-encoded XDR representation of the ledger header
     *
     * @return string The ledger header XDR
     */
    public function getHeaderXdr(): string
    {
        return $this->headerXdr;
    }

    /**
     * Gets the links to related resources for this ledger
     *
     * @return LedgerLinksResponse The navigation links
     */
    public function getLinks(): LedgerLinksResponse
    {
        return $this->links;
    }


    protected function loadFromJson(array $json) : void {

        if (isset($json['id'])) $this->id = $json['id'];
        if (isset($json['paging_token'])) $this->pagingToken = $json['paging_token'];
        if (isset($json['hash'])) $this->hash = $json['hash'];
        if (isset($json['prev_hash'])) $this->previousHash = $json['prev_hash'];
        if (isset($json['sequence'])) $this->sequence = new BigInteger($json['sequence']);
        if (isset($json['successful_transaction_count'])) $this->successfulTransactionCount = $json['successful_transaction_count'];
        if (isset($json['failed_transaction_count'])) $this->failedTransactionCount = $json['failed_transaction_count'];
        if (isset($json['operation_count'])) $this->operationCount = $json['operation_count'];
        if (isset($json['tx_set_operation_count'])) $this->txSetOperationCount = $json['tx_set_operation_count'];
        if (isset($json['closed_at'])) $this->closedAt = $json['closed_at'];
        if (isset($json['total_coins'])) $this->totalCoins = $json['total_coins'];
        if (isset($json['fee_pool'])) $this->feePool = $json['fee_pool'];
        if (isset($json['base_fee_in_stroops'])) $this->baseFeeInStroops = $json['base_fee_in_stroops'];
        if (isset($json['base_reserve_in_stroops'])) $this->baseReserveInStroops = $json['base_reserve_in_stroops'];
        if (isset($json['max_tx_set_size'])) $this->maxTxSetSize = $json['max_tx_set_size'];
        if (isset($json['protocol_version'])) $this->protocolVersion = $json['protocol_version'];
        if (isset($json['header_xdr'])) $this->headerXdr = $json['header_xdr'];
        if (isset($json['_links'])) $this->links = LedgerLinksResponse::fromJson($json['_links']);
    }

    public static function fromJson(array $json) : LedgerResponse
    {
        $result = new LedgerResponse();
        $result->loadFromJson($json);
        return $result;
    }
}