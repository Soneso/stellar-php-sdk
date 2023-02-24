<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

use Soneso\StellarSDK\Xdr\XdrDataValueMandatory;
use Soneso\StellarSDK\Xdr\XdrSCVal;

/**
 * Response when polling the rpc server to find out if a transaction has been completed.
 */
class GetTransactionStatusResponse extends SorobanRpcResponse
{

    public const STATUS_PENDING = "pending";
    public const STATUS_SUCCESS = "success";
    public const STATUS_ERROR = "error";

    /// Hash (id) of the transaction as a hex-encoded string
    public ?string $id = null;

    /// The current status of the transaction by hash, one of: pending, success, error
    public ?string $status = null;

    /// (optional) A base64 encoded string of the raw TransactionEnvelope XDR struct for this transaction.
    public ?string $envelopeXdr = null;

    ///  (optional) A base64 encoded string of the raw TransactionResult XDR struct for this transaction.
    public ?string $resultXdr = null;

    /// (optional) A base64 encoded string of the raw TransactionMeta XDR struct for this transaction.
    public ?string $resultMetaXdr = null;

    public ?TransactionStatusResults $results = null;

    /// (optional) Will be present on failed transactions.
    public ?TransactionStatusError $resultError = null;

    public static function fromJson(array $json) : GetTransactionStatusResponse {
        $result = new GetTransactionStatusResponse($json);
        if (isset($json['result'])) {
            if (isset($json['result']['error'])) {
                $result->resultError = TransactionStatusError::fromJson($json['result']['error']);
            } else if (isset($json['result']['results'])) {
                $result->results = new TransactionStatusResults();
                foreach ($json['result']['results'] as $jsonValue) {
                    $value = TransactionStatusResult::fromJson($jsonValue);
                    $result->results->add($value);
                }
            }
            if (isset($json['result']['id'])) {
                $result->id = $json['result']['id'];
            }
            if (isset($json['result']['status'])) {
                $result->status = $json['result']['status'];
            }
            if (isset($json['result']['envelopeXdr'])) {
                $result->envelopeXdr = $json['result']['envelopeXdr'];
            }
            if (isset($json['result']['resultXdr'])) {
                $result->resultXdr = $json['result']['resultXdr'];
            }
            if (isset($json['result']['resultMetaXdr'])) {
                $result->resultMetaXdr = $json['result']['resultMetaXdr'];
            }
        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }

    /// Extracts the wasm id from the response if the transaction installed a contract
    public function getWasmId() : ?string {
        return $this->getBinHex();
    }

    /// Extracts the contract is from the response if the transaction created a contract
    public function getContractId(): ?string {
        return $this->getBinHex();
    }

    /// Extracts the result value from the first entry on success
    public function getResultValue(): ?XdrSCVal {
        if ($this->error != null || $this->results == null || $this->results->count() == 0) {
            return null;
        }
        $first = $this->results->toArray()[0];
        if ($first instanceof TransactionStatusResult) {
            return XdrSCVal::fromBase64Xdr($first->xdr);
        }
        return null;
    }

    private function getBinHex(): ?string {
        $bin = $this->getBin();
        if ($bin != null) {
            return bin2hex($bin->getValue());
        }
        return null;
    }
    private function getBin(): ?XdrDataValueMandatory {
        if ($this->error != null || $this->results == null || $this->results->count() == 0) {
            return null;
        }
        $first = $this->results->toArray()[0];
        if ($first instanceof TransactionStatusResult) {
            $val = XdrSCVal::fromBase64Xdr($first->xdr);
            return $val->obj?->bin;
        }
        return null;
    }

    /**
     * @return string|null Hash (id) of the transaction as a hex-encoded string.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string|null $id
     */
    public function setId(?string $id): void
    {
        $this->id = $id;
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
     * @return TransactionStatusResults|null
     */
    public function getResults(): ?TransactionStatusResults
    {
        return $this->results;
    }

    /**
     * @param TransactionStatusResults|null $results
     */
    public function setResults(?TransactionStatusResults $results): void
    {
        $this->results = $results;
    }


    /**
     * @return string|null (optional) A base64 encoded string of the raw TransactionEnvelope
     * XDR struct for this transaction.
     */
    public function getEnvelopeXdr(): ?string
    {
        return $this->envelopeXdr;
    }

    /**
     * @param string|null $envelopeXdr
     */
    public function setEnvelopeXdr(?string $envelopeXdr): void
    {
        $this->envelopeXdr = $envelopeXdr;
    }

    /**
     * @return string|null  (optional) A base64 encoded string of the raw TransactionResult XDR
     * struct for this transaction.
     */
    public function getResultXdr(): ?string
    {
        return $this->resultXdr;
    }

    /**
     * @param string|null $resultXdr
     */
    public function setResultXdr(?string $resultXdr): void
    {
        $this->resultXdr = $resultXdr;
    }

    /**
     * @return string|null (optional) A base64 encoded string of the raw TransactionMeta XDR
     * struct for this transaction.
     */
    public function getResultMetaXdr(): ?string
    {
        return $this->resultMetaXdr;
    }

    /**
     * @param string|null $resultMetaXdr
     */
    public function setResultMetaXdr(?string $resultMetaXdr): void
    {
        $this->resultMetaXdr = $resultMetaXdr;
    }

    /**
     * @return TransactionStatusError|null
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