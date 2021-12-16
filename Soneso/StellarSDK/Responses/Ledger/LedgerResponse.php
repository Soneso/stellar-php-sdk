<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Ledger;

use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Responses\Response;

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
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPagingToken(): string
    {
        return $this->pagingToken;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @return string|null
     */
    public function getPreviousHash(): ?string
    {
        return $this->previousHash;
    }

    /**
     * @return BigInteger
     */
    public function getSequence(): BigInteger
    {
        return $this->sequence;
    }

    /**
     * @return int|null
     */
    public function getSuccessfulTransactionCount(): ?int
    {
        return $this->successfulTransactionCount;
    }

    /**
     * @return int|null
     */
    public function getFailedTransactionCount(): ?int
    {
        return $this->failedTransactionCount;
    }

    /**
     * @return int
     */
    public function getOperationCount(): int
    {
        return $this->operationCount;
    }

    /**
     * @return int|null
     */
    public function getTxSetOperationCount(): ?int
    {
        return $this->txSetOperationCount;
    }

    /**
     * @return string
     */
    public function getClosedAt(): string
    {
        return $this->closedAt;
    }

    /**
     * @return string
     */
    public function getTotalCoins(): string
    {
        return $this->totalCoins;
    }

    /**
     * @return string
     */
    public function getFeePool(): string
    {
        return $this->feePool;
    }

    /**
     * @return int
     */
    public function getBaseFeeInStroops(): int
    {
        return $this->baseFeeInStroops;
    }

    /**
     * @return int
     */
    public function getBaseReserveInStroops(): int
    {
        return $this->baseReserveInStroops;
    }

    /**
     * @return int
     */
    public function getMaxTxSetSize(): int
    {
        return $this->maxTxSetSize;
    }

    /**
     * @return int
     */
    public function getProtocolVersion(): int
    {
        return $this->protocolVersion;
    }

    /**
     * @return string
     */
    public function getHeaderXdr(): string
    {
        return $this->headerXdr;
    }

    /**
     * @return LedgerLinksResponse
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