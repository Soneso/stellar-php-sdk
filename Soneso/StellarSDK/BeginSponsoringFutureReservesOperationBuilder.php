<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

/**
 * Builder for creating BeginSponsoringFutureReserves operations.
 *
 * This builder implements the builder pattern to construct BeginSponsoringFutureReservesOperation
 * instances with a fluent interface. This operation begins sponsoring future reserve requirements
 * for another account, allowing the source account to pay for the sponsored account's base reserves.
 *
 * @package Soneso\StellarSDK
 * @see BeginSponsoringFutureReservesOperation
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 *
 * @example
 * $operation = (new BeginSponsoringFutureReservesOperationBuilder($sponsoredAccountId))
 *     ->setSourceAccount($sponsorAccountId)
 *     ->build();
 */
class BeginSponsoringFutureReservesOperationBuilder
{
    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * Creates a new BeginSponsoringFutureReserves operation builder.
     *
     * @param string $sponsoredId The account ID that will be sponsored
     */
    public function __construct(
        private string $sponsoredId,
    ) {
    }

    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : BeginSponsoringFutureReservesOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : BeginSponsoringFutureReservesOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the BeginSponsoringFutureReserves operation.
     *
     * @return BeginSponsoringFutureReservesOperation The constructed operation
     */
    public function build(): BeginSponsoringFutureReservesOperation {
        $result = new BeginSponsoringFutureReservesOperation($this->sponsoredId);
        if ($this->sourceAccount !== null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}