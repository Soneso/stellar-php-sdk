<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

use Soneso\StellarSDK\Xdr\XdrDataValueMandatory;
use Soneso\StellarSDK\Xdr\XdrSCAddressType;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrTransactionMeta;
use Soneso\StellarSDK\Xdr\XdrTransactionResult;

/**
 * Response when polling the rpc server to find out if a transaction has been completed.
 * See: https://soroban.stellar.org/api/methods/getTransaction
 */
class GetTransactionResponse extends SorobanRpcResponse
{

    public const STATUS_SUCCESS = "SUCCESS";
    public const STATUS_NOT_FOUND = "NOT_FOUND";
    public const STATUS_FAILED = "FAILED";

    /**
     * @var string|null $status The current status of the transaction by hash, one of: SUCCESS, NOT_FOUND, FAILED
     */
    public ?string $status = null;

    /**
     * @var int|null $latestLedger The sequence number of the latest ledger known to Soroban RPC at the
     * time it handled the request.
     */
    public ?int $latestLedger = null;

    /**
     * @var string|null $latestLedgerCloseTime The unix timestamp of the close time of the latest ledger known
     * to Soroban RPC at the time it handled the request.
     */
    public ?string $latestLedgerCloseTime = null;

    /**
     * @var int|null $oldestLedger The sequence number of the oldest ledger ingested by Soroban RPC at the
     * time it handled the request.
     */
    public ?int $oldestLedger = null;

    /**
     * @var string|null $oldestLedgerCloseTime The unix timestamp of the close time of the oldest ledger ingested by Soroban
     * RPC at the time it handled the request.
     */
    public ?string $oldestLedgerCloseTime = null;

    /**
     * @var int|null $ledger (optional) The sequence number of the ledger which included the transaction.
     * This field is only present if status is SUCCESS or FAILED.
     */
    public ?int $ledger = null;

    /**
     * @var string|null $createdAt (optional) The unix timestamp of when the transaction was included in the ledger.
     * This field is only present if status is SUCCESS or FAILED.
     */
    public ?string $createdAt = null;

    /**
     * @var int|null $applicationOrder (optional) The index of the transaction among all transactions included in the
     * ledger. This field is only present if status is SUCCESS or FAILED.
     */
    public ?int $applicationOrder = null;

    /**
     * @var bool|null $feeBump (optional) Indicates whether the transaction was fee bumped. This field is only present
     * if status is SUCCESS or FAILED.
     */
    public ?bool $feeBump = null;

    /**
     * @var string|null $envelopeXdr (optional) A base64 encoded string of the raw TransactionEnvelope XDR struct
     * for this transaction.
     */
    public ?string $envelopeXdr = null;

    /**
     * @var string|null $resultXdr (optional) A base64 encoded string of the raw TransactionResult XDR struct for this
     * transaction. This field is only present if status is SUCCESS or FAILED.
     */
    public ?string $resultXdr = null;

    /**
     * @var string|null $resultMetaXdr (optional) A base64 encoded string of the raw TransactionMeta XDR struct
     * for this transaction.
     */
    public ?string $resultMetaXdr = null;

    /**
     * @var string|null $txHash hex-encoded transaction hash string. Only available for protocol version >= 22
    */
    public ?string $txHash = null;

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
            if (isset($json['result']['createdAt'])) {
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
            if (isset($json['result']['txHash'])) {
                $result->txHash = $json['result']['txHash']; // protocol version >= 22
            }
        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }

    /**
     * Extracts the wasm id from the response if the transaction installed a contract.
     * @return string|null the wasm id if available.
     */
    public function getWasmId() : ?string {
        return $this->getBinHex();
    }

    /**
     * Extracts the contract id from the response if the transaction created a contract
     * @return string|null the contract id if available.
     */
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

    /**
     * Extracts the result value on success
     * @return XdrSCVal|null the result value if available.
     */
    public function getResultValue(): ?XdrSCVal {
        if ($this->error != null || $this->status != self::STATUS_SUCCESS || $this->resultMetaXdr == null) {
            return null;
        }

        $meta = XdrTransactionMeta::fromBase64Xdr($this->resultMetaXdr);
        if ($meta->v3 != null) {
            return $meta->v3->sorobanMeta?->returnValue;
        }
        return $meta->v4?->sorobanMeta?->returnValue;
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
     * @return string|null The current status of the transaction by hash, one of: SUCCESS, NOT_FOUND, FAILED
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @return int|null The sequence number of the latest ledger known to Soroban RPC at the
     *  time it handled the request.
     */
    public function getLatestLedger(): ?int
    {
        return $this->latestLedger;
    }

    /**
     * @return string|null The unix timestamp of the close time of the latest ledger known
     *  to Soroban RPC at the time it handled the request.
     */
    public function getLatestLedgerCloseTime(): ?string
    {
        return $this->latestLedgerCloseTime;
    }

    /**
     * @return int|null The sequence number of the oldest ledger ingested by Soroban RPC at the
     *  time it handled the request.
     */
    public function getOldestLedger(): ?int
    {
        return $this->oldestLedger;
    }

    /**
     * @return string|null The unix timestamp of the close time of the oldest ledger ingested by Soroban
     *  RPC at the time it handled the request.
     */
    public function getOldestLedgerCloseTime(): ?string
    {
        return $this->oldestLedgerCloseTime;
    }

    /**
     * @return int|null (optional) The sequence number of the ledger which included the transaction.
     *  This field is only present if status is SUCCESS or FAILED.
     */
    public function getLedger(): ?int
    {
        return $this->ledger;
    }

    /**
     * @return string|null (optional) The unix timestamp of when the transaction was included in the ledger.
     *  This field is only present if status is SUCCESS or FAILED.
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * @return int|null (optional) The index of the transaction among all transactions included in the
     *  ledger. This field is only present if status is SUCCESS or FAILED.
     */
    public function getApplicationOrder(): ?int
    {
        return $this->applicationOrder;
    }

    /**
     * @return bool|null (optional) Indicates whether the transaction was fee bumped. This field is only present
     *  if status is SUCCESS or FAILED.
     */
    public function getFeeBump(): ?bool
    {
        return $this->feeBump;
    }

    /**
     * @return string|null (optional) A base64 encoded string of the raw TransactionEnvelope XDR struct
     *  for this transaction.
     */
    public function getEnvelopeXdr(): ?string
    {
        return $this->envelopeXdr;
    }

    /**
     * @return XdrTransactionEnvelope|null (optional) the TransactionEnvelope XDR struct for this transaction.
     */
    public function getXdrTransactionEnvelope(): ?XdrTransactionEnvelope {
        if ($this->envelopeXdr !== null) {
            return XdrTransactionEnvelope::fromEnvelopeBase64XdrString($this->envelopeXdr);
        }
        return null;
    }

    /**
     * @return string|null (optional) A base64 encoded string of the raw TransactionResult XDR object for this
     * transaction. This field is only present if status is SUCCESS or FAILED.
     */
    public function getResultXdr(): ?string
    {
        return $this->resultXdr;
    }

    /**
     * @return XdrTransactionResult|null (optional) the TransactionResult XDR object for this
     * transaction. This field is only present if status is SUCCESS or FAILED.
     */
    public function getXdrTransactionResult(): ?XdrTransactionResult {
        if ($this->resultXdr !== null) {
            return XdrTransactionResult::fromBase64Xdr($this->resultXdr);
        }
        return null;
    }

    /**
     * @return string|null (optional) A base64 encoded string of the raw TransactionMeta XDR struct
     *  for this transaction.
     */
    public function getResultMetaXdr(): ?string
    {
        return $this->resultMetaXdr;
    }

    /**
     * @return XdrTransactionMeta|null (optional) TransactionMeta XDR object
     *   for this transaction.
     */
    public function getXdrTransactionMeta(): ? XdrTransactionMeta
    {
        if($this->resultXdr !== null) {
            return XdrTransactionMeta::fromBase64Xdr($this->resultXdr);
        }
        return null;
    }

    /**
     * @return string|null hex-encoded transaction hash string. Only available for protocol version >= 22
    */
    public function getTxHash(): ?string
    {
        return $this->txHash;
    }

    /**
     * @param string|null $txHash hex-encoded transaction hash string. Only for protocol version >= 22
     */
    public function setTxHash(?string $txHash): void
    {
        $this->txHash = $txHash;
    }
}