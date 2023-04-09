<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * Response when submitting a real transaction to the stellar
 * network by using the soroban rpc server.
 * See: https://soroban.stellar.org/api/methods/sendTransaction
 */
class SendTransactionResponse extends SorobanRpcResponse
{

    /// The transaction has been accepted by stellar-core.
    public const STATUS_PENDING = "PENDING";

    /// The transaction has already been submitted to stellar-core.
    public const STATUS_DUPLICATE = "DUPLICATE";

    /// The transaction was not included in the previous 4 ledgers and is banned from the next few ledgers.
    public const STATUS_TRY_AGAIN_LATER = "TRY_AGAIN_LATER";

    /// An error occurred from submitting the transaction to stellar-core.
    public const STATUS_ERROR = "ERROR";

    /// The transaction hash (in an hex-encoded string)
    public ?string $hash = null;

    /// The current status of the transaction by hash, one of: PENDING, DUPLICATE, TRY_AGAIN_LATER, ERROR
    public ?string $status = null;

    /// The latest ledger known to Soroban-RPC at the time it handled the sendTransaction() request.
    public ?string $latestLedger = null;

    /// The unix timestamp of the close time of the latest ledger known to Soroban-RPC at the time it handled the sendTransaction() request.
    public ?string $latestLedgerCloseTime = null;

    /// (optional) If the transaction status is ERROR, this will be a base64 encoded string of the raw TransactionResult XDR (XdrTransactionResult) struct containing details on why stellar-core rejected the transaction.
    public ?string $errorResultXdr = null;

    public static function fromJson(array $json) : SendTransactionResponse {
        $result = new SendTransactionResponse($json);
        if (isset($json['result'])) {
            $result->hash = $json['result']['hash'];
            $result->status = $json['result']['status'];
            $result->latestLedger = $json['result']['latestLedger'];
            $result->latestLedgerCloseTime = $json['result']['latestLedgerCloseTime'];
            if (isset($json['result']['errorResultXdr'])) {
                $result->errorResultXdr = $json['result']['errorResultXdr'];
            }
        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }

    /**
     * @return string|null
     */
    public function getHash(): ?string
    {
        return $this->hash;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function getLatestLedger(): ?string
    {
        return $this->latestLedger;
    }

    /**
     * @return string|null
     */
    public function getLatestLedgerCloseTime(): ?string
    {
        return $this->latestLedgerCloseTime;
    }

    /**
     * @return string|null
     */
    public function getErrorResultXdr(): ?string
    {
        return $this->errorResultXdr;
    }

}