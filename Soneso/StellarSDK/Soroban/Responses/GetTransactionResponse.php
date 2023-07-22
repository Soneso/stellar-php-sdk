<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

use Soneso\StellarSDK\Xdr\XdrDataValueMandatory;
use Soneso\StellarSDK\Xdr\XdrSCAddressType;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrTransactionMeta;

/**
 * Response when polling the rpc server to find out if a transaction has been completed.
 * See: https://soroban.stellar.org/api/methods/getTransaction
 */
class GetTransactionResponse extends SorobanRpcResponse
{

    public const STATUS_SUCCESS = "SUCCESS";
    public const STATUS_NOT_FOUND = "NOT_FOUND";
    public const STATUS_FAILED = "FAILED";

    /// the current status of the transaction by hash, one of: SUCCESS, NOT_FOUND, FAILED
    public ?string $status = null;

    /// The latest ledger known to Soroban-RPC at the time it handled the getTransaction() request.
    public ?string $latestLedger = null;

    /// The unix timestamp of the close time of the latest ledger known to Soroban-RPC at the time it handled the getTransaction() request.
    public ?string $latestLedgerCloseTime = null;

    /// The oldest ledger ingested by Soroban-RPC at the time it handled the getTransaction() request.
    public ?string $oldestLedger = null;

    /// The unix timestamp of the close time of the oldest ledger ingested by Soroban-RPC at the time it handled the getTransaction() request.
    public ?string $oldestLedgerCloseTime = null;

    /// (optional) The sequence of the ledger which included the transaction. This field is only present if status is SUCCESS or FAILED.
    public ?string $ledger = null;

    /// (optional) The unix timestamp of when the transaction was included in the ledger. This field is only present if status is SUCCESS or FAILED.
    public ?string $createdAt = null;

    /// (optional) The index of the transaction among all transactions included in the ledger. This field is only present if status is SUCCESS or FAILED.
    public ?int $applicationOrder = null;

    /// (optional) Indicates whether the transaction was fee bumped. This field is only present if status is SUCCESS or FAILED.
    public ?bool $feeBump = null;

    /// (optional) A base64 encoded string of the raw TransactionEnvelope XDR (XdrTransactionEnvelope) struct for this transaction.
    public ?string $envelopeXdr = null;

    /// (optional) A base64 encoded string of the raw TransactionResult XDR (XdrTransactionResult) struct for this transaction. This field is only present if status is SUCCESS or FAILED.
    public ?string $resultXdr = null;

    /// (optional) A base64 encoded string of the raw TransactionResultMeta XDR (XdrTransactionMeta) struct for this transaction.
    public ?string $resultMetaXdr = null;

    public static function fromJson(array $json) : GetTransactionResponse {
        $result = new GetTransactionResponse($json);
        if (isset($json['result'])) {

            if (isset($json['result']['status'])) {
                $result->status = $json['result']['status'];
            }
            if (isset($json['result']['latestLedger'])) {
                $result->latestLedger = $json['result']['latestLedger'];
            }
            if (isset($json['result']['latestLedgerCloseTime'])) {
                $result->latestLedgerCloseTime = $json['result']['latestLedgerCloseTime'];
            }
            if (isset($json['result']['oldestLedger'])) {
                $result->oldestLedger = $json['result']['oldestLedger'];
            }
            if (isset($json['result']['oldestLedgerCloseTime'])) {
                $result->oldestLedgerCloseTime = $json['result']['oldestLedgerCloseTime'];
            }
            if (isset($json['result']['ledger'])) {
                $result->ledger = $json['result']['ledger'];
            }
            if (isset($json['result']['ledger'])) {
                $result->createdAt = $json['result']['createdAt'];
            }
            if (isset($json['result']['applicationOrder'])) {
                $result->applicationOrder = $json['result']['applicationOrder'];
            }
            if (isset($json['result']['feeBump'])) {
                $result->feeBump = $json['result']['feeBump'];
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

    /// Extracts the contract id from the response if the transaction created a contract
    public function getCreatedContractId(): ?string {
        $resultValue = $this->getResultValue();
        if ($resultValue != null && $resultValue->type->value == XdrSCValType::SCV_ADDRESS && $resultValue->address != null) {
            $address = $resultValue->address;
            if ($address->type->value == XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT) {
                return $address->contractId;
            }
        }
        return null;
    }

    /// Extracts the result value on success
    public function getResultValue(): ?XdrSCVal {
        if ($this->error != null || $this->status != self::STATUS_SUCCESS || $this->resultMetaXdr == null) {
            return null;
        }

        $meta = XdrTransactionMeta::fromBase64Xdr($this->resultMetaXdr);
        return $meta->v3?->sorobanMeta?->returnValue;
    }

    private function getBinHex(): ?string {
        $bin = $this->getBin();
        if ($bin != null) {
            return bin2hex($bin->getValue());
        }
        return null;
    }
    private function getBin(): ?XdrDataValueMandatory {
        $val = $this->getResultValue();
        if ($val != null) {
            return $val->bytes;
        }
        return null;
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
    public function getOldestLedger(): ?string
    {
        return $this->oldestLedger;
    }

    /**
     * @return string|null
     */
    public function getOldestLedgerCloseTime(): ?string
    {
        return $this->oldestLedgerCloseTime;
    }

    /**
     * @return string|null
     */
    public function getLedger(): ?string
    {
        return $this->ledger;
    }

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * @return int|null
     */
    public function getApplicationOrder(): ?int
    {
        return $this->applicationOrder;
    }

    /**
     * @return bool|null
     */
    public function getFeeBump(): ?bool
    {
        return $this->feeBump;
    }

    /**
     * @return string|null
     */
    public function getEnvelopeXdr(): ?string
    {
        return $this->envelopeXdr;
    }

    /**
     * @return string|null
     */
    public function getResultXdr(): ?string
    {
        return $this->resultXdr;
    }

    /**
     * @return string|null
     */
    public function getResultMetaXdr(): ?string
    {
        return $this->resultMetaXdr;
    }

}