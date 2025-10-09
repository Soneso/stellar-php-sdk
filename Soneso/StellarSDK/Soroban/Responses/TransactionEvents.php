<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

class TransactionEvents
{
    /**
     * @var array<string>|null $transactionEventsXdr (optional) A base64 encoded slice of xdr.TransactionEvent.
     */
    public ?array $transactionEventsXdr = null;

    /**
     * @var array<array<string>>|null $contractEventsXdr (optional) A base64 encoded slice of array [xdr.ContractEvent] for each operation.
     */
    public ?array $contractEventsXdr = null;

    /**
     * @param array<string>|null $transactionEventsXdr (optional) A base64 encoded slice of xdr.TransactionEvent.
     * @param array<array<string>>|null $contractEventsXdr (optional) A base64 encoded slice of array [xdr.ContractEvent] for each operation.
     */
    public function __construct(?array $transactionEventsXdr = null,
                                ?array $contractEventsXdr = null)
    {
        $this->transactionEventsXdr = $transactionEventsXdr;
        $this->contractEventsXdr = $contractEventsXdr;
    }

    public static function fromJson(array $json): TransactionEvents
    {
        /**
         * @var array<string>|null $transactionEventsXdr
         */
        $transactionEventsXdr = null;
        if (isset($json["transactionEventsXdr"])) {
            $transactionEventsXdr = array();
            foreach ($json["transactionEventsXdr"] as $val) {
                $transactionEventsXdr[] = $val;
            }
        }
        /**
         * @var array<array<string>>|null $contractEventsXdr
         */
        $contractEventsXdr = null;
        if (isset($json["contractEventsXdr"])) {
            $contractEventsXdr = array();
            foreach ($json["contractEventsXdr"] as $val) {
                if (is_array($val)) {
                    /**
                     * @var array<string> $nextOperationEvents
                     */
                    $nextOperationEvents = array();
                    foreach($val as $event) {
                        $nextOperationEvents[] = $event;
                    }
                    $contractEventsXdr[] = $nextOperationEvents;
                }
            }
        }

        return new TransactionEvents(
            transactionEventsXdr: $transactionEventsXdr,
            contractEventsXdr: $contractEventsXdr,
        );
    }

}