<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * Response when submitting a real transaction to the stellar
 * network by using the soroban rpc server.
 */
class SendTransactionResponse extends SorobanRpcResponse
{

    /// The transaction hash (in an hex-encoded string), and the initial
    /// transaction status, ("pending" or something)
    public ?string $transactionId = null;

    /// The current status of the transaction by hash, one of: pending, success, error
    public ?string $status = null;

    /// (optional) If the transaction was rejected immediately,
    /// this will be an error object.
    public ?TransactionStatusError $resultError = null;

    public static function fromJson(array $json) : SendTransactionResponse {
        $result = new SendTransactionResponse($json);
        if (isset($json['result'])) {
            $result->transactionId = $json['result']['id'];
            $result->status = $json['result']['status'];
            if (isset($json['result']['error'])) {
                $result->resultError = TransactionStatusError::fromJson($json['result']['error']);
            }
        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }

    /**
     * @return string|null The transaction hash (in an hex-encoded string),
     * and the initial network by using the soroban rpc server.
     */
    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    /**
     * @param string|null $transactionId
     */
    public function setTransactionId(?string $transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @return string|null The current status of the transaction by hash, one of: pending, success, error
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string|null $status
     */
    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return TransactionStatusError|null (optional) If the transaction was rejected immediately,
     * this will be an error object.
     */
    public function getResultError(): ?TransactionStatusError
    {
        return $this->resultError;
    }

    /**
     * @param TransactionStatusError|null $resultError
     */
    public function setResultError(?TransactionStatusError $resultError): void
    {
        $this->resultError = $resultError;
    }

}