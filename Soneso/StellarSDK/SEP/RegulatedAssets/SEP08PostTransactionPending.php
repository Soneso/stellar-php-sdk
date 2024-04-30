<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\RegulatedAssets;

/**
 * This response means that the issuer could not determine whether to approve
 * the transaction at the time of receiving it. Wallet can re-submit the same transaction at a later point in time.
 */
class SEP08PostTransactionPending extends SEP08PostTransactionResponse
{
    /**
     * @var int $timeout Number of milliseconds to wait before submitting the same transaction again.
     */
    public int $timeout = 0;

    /**
     * @var string|null $message (optional) A human-readable string containing information to pass on to the user.
     */
    public ?string $message = null;

    /**
     * Constructor.
     * @param int|null $timeout Number of milliseconds to wait before submitting the same transaction again.
     * @param string|null $message (optional) A human-readable string containing information to pass on to the user.
     */
    public function __construct(?int $timeout = null, ?string $message = null)
    {
        if ($timeout !== null) {
            $this->timeout = $timeout;
        }

        $this->message = $message;
    }

}