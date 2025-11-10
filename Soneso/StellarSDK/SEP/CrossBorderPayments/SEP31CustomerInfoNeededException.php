<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\CrossBorderPayments;

use Exception;

/**
 * Exception thrown when additional KYC information is required via SEP-12.
 *
 * This exception is raised during POST /transactions when the Sending Anchor
 * has not provided all required KYC information, or when additional KYC is
 * needed after the transaction amount is known. The type field indicates which
 * customer type requires additional information. Returns HTTP 400 Bad Request.
 *
 * Resolution workflow:
 * 1. Extract the type field from this exception
 * 2. Call SEP-12 PUT /customer with the missing KYC data
 * 3. Use the returned customer_id as sender_id or receiver_id
 * 4. Retry POST /transactions with the updated IDs
 *
 * @package Soneso\StellarSDK\SEP\CrossBorderPayments
 * @see https://github.com/stellar/stellar-protocol/blob/v3.1.0/ecosystem/sep-0031.md#post-transactions
 * @see https://github.com/stellar/stellar-protocol/blob/v3.1.0/ecosystem/sep-0012.md
 * @see CrossBorderPaymentsService::postTransactions()
 */
class SEP31CustomerInfoNeededException extends Exception
{
    /**
     * @var string $error customer_info_needed
     */
    public string $error = 'customer_info_needed';

    /**
     * @var string|null (optional) A string for the type URL argument the Sending Anchor should use when making
     * the SEP-12 GET /customer request. The value should be included in the sender.types or
     * receiver.types object from GET /info.
     */
    public ?string $type = null;

    /**
     * @param string|null $type
     */
    public function __construct(?string $type = null)
    {

        $this->type = $type;
        $message = "The Sending Anchor didn't provide all the KYC information requested in SEP-12 GET /customer, or where the Receiving Anchor requires additional KYC information after amount";
        parent::__construct($message);
    }

}