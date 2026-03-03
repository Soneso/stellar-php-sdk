<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

/**
 * Builder for creating ChangeTrust operations.
 *
 * This builder implements the builder pattern to construct ChangeTrustOperation
 * instances with a fluent interface. ChangeTrust operations create, update, or delete
 * a trustline between the source account and an asset issuer.
 *
 * @package Soneso\StellarSDK
 * @see ChangeTrustOperation
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 *
 * @example
 * $operation = (new ChangeTrustOperationBuilder($asset, '1000.00'))
 *     ->setSourceAccount($sourceId)
 *     ->build();
 */
class ChangeTrustOperationBuilder
{
    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * Creates a new ChangeTrust operation builder.
     *
     * @param Asset $asset The asset of the trustline
     * @param string|null $limit The limit of the trustline (null for default maximum)
     */
    public function __construct(
        private Asset $asset,
        private ?string $limit = null,
    ) {
    }

    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : ChangeTrustOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : ChangeTrustOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the ChangeTrust operation.
     *
     * @return ChangeTrustOperation The constructed operation
     */
    public function build(): ChangeTrustOperation {
        $result = new ChangeTrustOperation($this->asset, $this->limit);
        if ($this->sourceAccount !== null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}