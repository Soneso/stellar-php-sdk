<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\CrossBorderPayments;

use Exception;

/**
 * Exception thrown when additional transaction information is required.
 *
 * This exception is raised during POST /transactions when the Sending Anchor
 * has not provided all required transaction fields. The fields property contains
 * the list of missing or invalid fields that need to be supplied.
 *
 * @package Soneso\StellarSDK\SEP\CrossBorderPayments
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0031.md#post-transactions
 * @see CrossBorderPaymentsService::postTransactions()
 */
class SEP31TransactionInfoNeededException extends Exception
{

    /**
     * @var string $error transaction_info_needed
     */
    public string $error = 'transaction_info_needed';
    /**
     * @var array<array-key, mixed>|null A key-value pair of missing fields in the same format as fields
     * described in GET /info
     */
    public ?array $fields = null;

    /**
     * @param array<array-key, mixed>|null $fields A key-value pair of missing fields in the same format as fields
     *  described in GET /info
     */
    public function __construct(?array $fields = null)
    {
        $this->fields = $fields;
        $message = "The Sending Anchor didn't provide all the information requested";
        parent::__construct($message);
    }

}