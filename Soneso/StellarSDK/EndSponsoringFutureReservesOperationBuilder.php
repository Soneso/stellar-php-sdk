<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

/**
 * Builder for creating EndSponsoringFutureReserves operations.
 *
 * This builder implements the builder pattern to construct EndSponsoringFutureReservesOperation
 * instances with a fluent interface. This operation terminates the current sponsoring relationship,
 * signaling that the sponsor will no longer cover future reserve requirements.
 *
 * @package Soneso\StellarSDK
 * @see EndSponsoringFutureReservesOperation
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 *
 * @example
 * $operation = (new EndSponsoringFutureReservesOperationBuilder())
 *     ->setSourceAccount($sponsoredAccountId)
 *     ->build();
 */
class EndSponsoringFutureReservesOperationBuilder
{
    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : EndSponsoringFutureReservesOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : EndSponsoringFutureReservesOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the EndSponsoringFutureReserves operation.
     *
     * @return EndSponsoringFutureReservesOperation The constructed operation
     */
    public function build(): EndSponsoringFutureReservesOperation {
        $result = new EndSponsoringFutureReservesOperation();
        if ($this->sourceAccount !== null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}