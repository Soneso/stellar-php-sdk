<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\CrossBorderPayments;

use Exception;

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