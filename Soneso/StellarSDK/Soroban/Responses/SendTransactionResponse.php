<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

use Soneso\StellarSDK\Xdr\XdrDiagnosticEvent;
use Soneso\StellarSDK\Xdr\XdrTransactionResult;

/**
 * Response when submitting a real transaction to the stellar
 * network by using the soroban rpc server.
 * See: https://developers.stellar.org/network/soroban-rpc/api-reference/methods/sendTransaction
 */
class SendTransactionResponse extends SorobanRpcResponse
{

    /**
     * The transaction has been accepted by stellar-core.
     */
    public const STATUS_PENDING = "PENDING";

    /**
     * The transaction has already been submitted to stellar-core.
     */
    public const STATUS_DUPLICATE = "DUPLICATE";

    /**
     * The transaction was not included in the previous 4 ledgers and is banned from the next few ledgers.
     */
    public const STATUS_TRY_AGAIN_LATER = "TRY_AGAIN_LATER";

    /**
     * An error occurred from submitting the transaction to stellar-core.
     */
    public const STATUS_ERROR = "ERROR";

    /**
     * @var string|null $hash Transaction hash (as a hex-encoded string).
     */
    public ?string $hash = null;

    /**
     * @var string|null $status The current status of the transaction by hash, one of: PENDING, DUPLICATE,
     * TRY_AGAIN_LATER, ERROR
     */
    public ?string $status = null;

    /**
     * @var int|null $latestLedger The sequence number of the latest ledger known to Soroban RPC at the time it
     * handled the request.
     */
    public ?int $latestLedger = null;

    /**
     * @var string|null $latestLedgerCloseTime The unix timestamp of the close time of the latest ledger known to
     * Soroban RPC at the time it handled the request.
     */
    public ?string $latestLedgerCloseTime = null;

    /**
     * @var string|null $errorResultXdr (optional) If the transaction status is ERROR, this will be a base64 encoded
     * string of the raw TransactionResult XDR struct containing details on why stellar-core rejected the transaction.
     */
    public ?string $errorResultXdr = null;

    /**
     * @var array<XdrDiagnosticEvent>|null $diagnosticEvents (optional) If the transaction status is "ERROR", this
     * list of xdr diagnostic events may be present containing details on why stellar-core rejected the transaction.
     */
    public ?array $diagnosticEvents = null;

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
            if (isset($json['result']['diagnosticEventsXdr'])) {
                $result->diagnosticEvents = array();
                foreach ($json['result']['diagnosticEventsXdr'] as $jsonEntry) {
                    $entry = XdrDiagnosticEvent::fromBase64Xdr($jsonEntry);
                    $result->diagnosticEvents[] = $entry;
                }
            }
        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }

    /**
     * @return string|null Transaction hash (as a hex-encoded string).
     */
    public function getHash(): ?string
    {
        return $this->hash;
    }

    /**
     * @return string|null The current status of the transaction by hash, one of: PENDING, DUPLICATE,
     *  TRY_AGAIN_LATER, ERROR
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @return int|null The sequence number of the latest ledger known to Soroban RPC at the time it
     *  handled the request.
     */
    public function getLatestLedger(): ?int
    {
        return $this->latestLedger;
    }

    /**
     * @return string|null The unix timestamp of the close time of the latest ledger known to
     *  Soroban RPC at the time it handled the request.
     */
    public function getLatestLedgerCloseTime(): ?string
    {
        return $this->latestLedgerCloseTime;
    }

    /**
     * @return string|null (optional) If the transaction status is ERROR, this will be a base64 encoded
     *  string of the raw TransactionResult XDR struct containing details on why stellar-core rejected the transaction.
     */
    public function getErrorResultXdr(): ?string
    {
        return $this->errorResultXdr;
    }

    /**
     * @return XdrTransactionResult|null (optional) If the transaction status is ERROR, this will be a
     * XdrTransactionResult object containing details on why stellar-core rejected the transaction.
     */
    public function getErrorXdrTransactionResult(): ?XdrTransactionResult {
        if ($this->errorResultXdr !== null) {
            return XdrTransactionResult::fromBase64Xdr($this->errorResultXdr);
        }
        return null;
    }

    /**
     * @return array<XdrDiagnosticEvent>|null (optional) If the transaction status is "ERROR", this
     *  list of xdr diagnostic events may be present containing details on why stellar-core rejected the transaction.
     */
    public function getDiagnosticEvents(): ?array
    {
        return $this->diagnosticEvents;
    }

}