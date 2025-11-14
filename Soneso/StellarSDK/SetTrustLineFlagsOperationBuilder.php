<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

/**
 * Builder for creating SetTrustLineFlags operations.
 *
 * This builder implements the builder pattern to construct SetTrustLineFlagsOperation
 * instances with a fluent interface. SetTrustLineFlags operations allow an asset issuer
 * to set or clear flags on a trustline to control authorization and clawback capabilities.
 *
 * @package Soneso\StellarSDK
 * @see SetTrustLineFlagsOperation
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 *
 * @example
 * $operation = (new SetTrustLineFlagsOperationBuilder($trustorId, $asset, $clearFlags, $setFlags))
 *     ->setSourceAccount($issuerId)
 *     ->build();
 */
class SetTrustLineFlagsOperationBuilder
{
    /**
     * @var string The account ID of the trustline holder (trustor)
     */
    private string $trustorId;

    /**
     * @var Asset The asset of the trustline
     */
    private Asset $asset;

    /**
     * @var int The flags to clear on the trustline
     */
    private int $clearFlags;

    /**
     * @var int The flags to set on the trustline
     */
    private int $setFlags;

    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * Creates a new SetTrustLineFlags operation builder.
     *
     * @param string $trustorId The account ID of the trustline holder
     * @param Asset $asset The asset of the trustline
     * @param int $clearFlags The flags to clear on the trustline
     * @param int $setFlags The flags to set on the trustline
     */
    public function __construct(string $trustorId, Asset $asset, int $clearFlags, int $setFlags) {
        $this->trustorId = $trustorId;
        $this->asset = $asset;
        $this->clearFlags = $clearFlags;
        $this->setFlags = $setFlags;
    }

    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : SetTrustLineFlagsOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : SetTrustLineFlagsOperationBuilder  {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the SetTrustLineFlags operation.
     *
     * @return SetTrustLineFlagsOperation The constructed operation
     */
    public function build(): SetTrustLineFlagsOperation {
        $result = new SetTrustLineFlagsOperation($this->trustorId, $this->asset, $this->clearFlags, $this->setFlags);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}