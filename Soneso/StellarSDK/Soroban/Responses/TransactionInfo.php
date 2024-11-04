<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Soroban\Responses;

class TransactionInfo
{
    const STATUS_SUCCESS = "SUCCESS";
    const STATUS_NOT_FOUND = "NOT_FOUND";
    const STATUS_FAILED = "FAILED";

    /**
     * @var string $status Indicates whether the transaction was successful or not.
     */
    public string $status;
    /**
     * @var int $applicationOrder The 1-based index of the transaction among all transactions included in the ledger.
     */
    public int $applicationOrder;
    /**
     * @var bool $feeBump Indicates whether the transaction was fee bumped.
     */
    public bool $feeBump;
    /**
     * @var string $envelopeXdr A base64 encoded string of the raw TransactionEnvelope XDR struct for this transaction.
     */
    public string $envelopeXdr;
    /**
     * @var string $resultXdr A base64 encoded string of the raw TransactionResult XDR struct for this transaction.
     */
    public string $resultXdr;
    /**
     * @var string $resultMetaXdr A base64 encoded string of the raw TransactionMeta XDR struct for this transaction.
     */
    public string $resultMetaXdr;
    /**
     * @var int $ledger The sequence number of the ledger which included the transaction.
     */
    public int $ledger;
    /**
     * @var int $createdAt The unix timestamp of when the transaction was included in the ledger.
     */
    public int $createdAt;
    /**
     * @var array<string>|null (optional) A base64 encoded slice of xdr.DiagnosticEvent.
     * This is only present if the ENABLE_SOROBAN_DIAGNOSTIC_EVENTS has been enabled in the stellar-core config.
     */
    public ?array $diagnosticEventsXdr = null;

    /**
     * @var ?string $txHash hex-encoded transaction hash string. Only available for protocol version >= 22
    */
    public ?string $txHash;

    /**
     * @param string $status Indicates whether the transaction was successful or not.
     * @param int $applicationOrder The 1-based index of the transaction among all transactions included in the ledger.
     * @param bool $feeBump Indicates whether the transaction was fee bumped.
     * @param string $envelopeXdr A base64 encoded string of the raw TransactionEnvelope XDR struct for this transaction.
     * @param string $resultXdr A base64 encoded string of the raw TransactionResult XDR struct for this transaction.
     * @param string $resultMetaXdr A base64 encoded string of the raw TransactionMeta XDR struct for this transaction.
     * @param int $ledger The sequence number of the ledger which included the transaction.
     * @param int $createdAt The unix timestamp of when the transaction was included in the ledger.
     * @param string|null $txHash hex-encoded transaction hash string. Only available for protocol version >= 22
     * @param array<string>|null $diagnosticEventsXdr (optional) A base64 encoded slice of xdr.DiagnosticEvent.
     * This is only present if the ENABLE_SOROBAN_DIAGNOSTIC_EVENTS has been enabled in the stellar-core config.
     */
    public function __construct(
        string $status,
        int $applicationOrder,
        bool $feeBump,
        string $envelopeXdr,
        string $resultXdr,
        string $resultMetaXdr,
        int $ledger,
        int $createdAt,
        ?string $txHash = null,
        ?array $diagnosticEventsXdr = null,
    )
    {
        $this->status = $status;
        $this->applicationOrder = $applicationOrder;
        $this->feeBump = $feeBump;
        $this->envelopeXdr = $envelopeXdr;
        $this->resultXdr = $resultXdr;
        $this->resultMetaXdr = $resultMetaXdr;
        $this->ledger = $ledger;
        $this->createdAt = $createdAt;
        $this->txHash = $txHash;
        $this->diagnosticEventsXdr = $diagnosticEventsXdr;
    }

    public static function fromJson(array $json): TransactionInfo
    {

        /**
         * @var array<string>|null $diagnosticEventsXdr
         */
        $diagnosticEventsXdr = null;
        if (isset($json["diagnostic_events"])) {
            $diagnosticEventsXdr = array();
            foreach ($json["diagnostic_events"] as $val) {
                $diagnosticEventsXdr[] = $val;
            }
        }

        $txHash = null;
        if (isset($json["txHash"])) {
            $txHash = $json["txHash"]; // protocol version >= 22
        }

        return new TransactionInfo(
            $json['status'],
            $json['applicationOrder'],
            $json['feeBump'],
            $json['envelopeXdr'],
            $json['resultXdr'],
            $json['resultMetaXdr'],
            $json['ledger'],
            (int)$json['createdAt'],
            txHash: $txHash,
            diagnosticEventsXdr: $diagnosticEventsXdr,
        );
    }
}