<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\RegulatedAssets;

/**
 * Response indicating the approval decision is delayed and should be retried.
 *
 * This response means the issuer could not determine whether to approve the transaction
 * at the time of receiving it. The wallet should wait for the specified timeout period
 * and then resubmit the same transaction.
 *
 * Common reasons for pending responses:
 * - Manual review required by compliance team
 * - External service dependency (KYC provider, sanctions screening)
 * - Rate limiting or temporary service overload
 * - Awaiting additional information from other systems
 *
 * The timeout field indicates how long to wait before resubmitting. If timeout is 0 or
 * not provided, the wallet should implement its own retry strategy (e.g., exponential
 * backoff starting at 5 seconds).
 *
 * HTTP Status Code: 200
 *
 * @package Soneso\StellarSDK\SEP\RegulatedAssets
 * @see https://github.com/stellar/stellar-protocol/blob/v1.7.4/ecosystem/sep-0008.md#pending SEP-0008 v1.7.4
 */
class SEP08PostTransactionPending extends SEP08PostTransactionResponse
{
    /**
     * Number of milliseconds to wait before resubmitting the transaction.
     *
     * A value of 0 indicates the wait time cannot be determined. Wallets should implement
     * their own retry strategy (e.g., exponential backoff starting at 5 seconds).
     *
     * Per SEP-0008, the server should provide this value to prevent excessive polling.
     *
     * @var int Default: 0 (wait time unknown)
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