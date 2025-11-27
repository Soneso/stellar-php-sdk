<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * Represents a single transaction in the getTransactions response
 *
 * @package Soneso\StellarSDK\Soroban\Responses
 * @see https://developers.stellar.org/docs/data/rpc/api-reference/methods/getTransactions
 */
class TransactionInfo
{
    const STATUS_SUCCESS = "SUCCESS";
    const STATUS_NOT_FOUND = "NOT_FOUND";
    const STATUS_FAILED = "FAILED";

    /**
     * @var array<string>|null Base64-encoded slice of xdr.DiagnosticEvent (deprecated, only present if ENABLE_SOROBAN_DIAGNOSTIC_EVENTS is enabled in stellar-core config)
     */
    public ?array $diagnosticEventsXdr = null;

    /**
     * @var TransactionEvents|null Events for the transaction (only available for protocol version >= 23)
     */
    public ?TransactionEvents $events = null;

    /**
     * @param string $status Indicates whether the transaction was successful or not
     * @param int $applicationOrder The 1-based index of the transaction among all transactions included in the ledger
     * @param bool $feeBump Indicates whether the transaction was fee bumped
     * @param string $envelopeXdr Base64-encoded TransactionEnvelope XDR struct for this transaction
     * @param string $resultXdr Base64-encoded TransactionResult XDR struct for this transaction
     * @param string $resultMetaXdr Base64-encoded TransactionMeta XDR struct for this transaction
     * @param int $ledger The sequence number of the ledger which included the transaction
     * @param int $createdAt Unix timestamp of when the transaction was included in the ledger
     * @param string|null $txHash Hex-encoded transaction hash string (only available for protocol version >= 22)
     * @param array<string>|null $diagnosticEventsXdr Base64-encoded slice of xdr.DiagnosticEvent (deprecated)
     * @param TransactionEvents|null $events Events for the transaction (only available for protocol version >= 23)
     */
    public function __construct(
        public string $status,
        public int $applicationOrder,
        public bool $feeBump,
        public string $envelopeXdr,
        public string $resultXdr,
        public string $resultMetaXdr,
        public int $ledger,
        public int $createdAt,
        public ?string $txHash = null,
        ?array $diagnosticEventsXdr = null,
        ?TransactionEvents $events = null,
    )
    {
        $this->diagnosticEventsXdr = $diagnosticEventsXdr;
        $this->events = $events;
    }

    /**
     * Creates an instance from JSON-RPC response data
     *
     * @param array<string,mixed> $json The JSON response data
     * @return static The created instance
     */
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

        $events = null;
        if (isset($json["events"])) {
            $events = TransactionEvents::fromJson($json["events"]); // protocol version >= 23
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
            events: $events,
        );
    }
}