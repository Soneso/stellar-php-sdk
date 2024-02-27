<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\CrossBorderPayments;

use Exception;

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