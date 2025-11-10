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
 * the list of missing or invalid fields that need to be supplied. Returns HTTP 400 Bad Request.
 *
 * DEPRECATED: This approach is deprecated in favor of using SEP-12 PUT /customer
 * to provide customer information. New implementations should use SEP-12 exclusively.
 *
 * Resolution workflow (legacy):
 * 1. Extract the fields array from this exception
 * 2. Retry POST /transactions with the required fields populated
 *
 * @package Soneso\StellarSDK\SEP\CrossBorderPayments
 * @see https://github.com/stellar/stellar-protocol/blob/v3.1.0/ecosystem/sep-0031.md#post-transactions
 * @see CrossBorderPaymentsService::postTransactions()
 * @deprecated since SEP-31 v2.5.0, use SEP-12 PUT /customer instead
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