<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use InvalidArgumentException;

/**
 * Builder for creating AllowTrust operations.
 *
 * This builder implements the builder pattern to construct AllowTrustOperation
 * instances with a fluent interface. AllowTrust operations allow an issuing account
 * to authorize or revoke authorization for another account to hold its assets.
 *
 * @package Soneso\StellarSDK
 * @see AllowTrustOperation
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 *
 * @example
 * $operation = (new AllowTrustOperationBuilder($trustorId, 'USD', true, false))
 *     ->setSourceAccount($issuerId)
 *     ->build();
 */
class AllowTrustOperationBuilder
{
    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * @var string The account ID of the trustor being authorized or deauthorized
     */
    private string $trustor;

    /**
     * @var string The asset code (1-12 characters)
     */
    private string $assetCode;

    /**
     * @var bool Whether to authorize the trustline
     */
    private bool $authorized;

    /**
     * @var bool Whether to authorize only maintaining liabilities
     */
    private bool $authorizedToMaintainLiabilities;

    /**
     * Creates a new AllowTrust operation builder.
     *
     * @param string $trustor The account ID being authorized or deauthorized
     * @param string $assetCode The asset code (1-12 characters)
     * @param bool $authorized Whether to authorize the trustline
     * @param bool $authorizedToMaintainLiabilities Whether to authorize only maintaining liabilities
     * @throws InvalidArgumentException If asset code length is invalid (must be 1-12 characters)
     */
    public function __construct(string $trustor, string $assetCode, bool $authorized, bool $authorizedToMaintainLiabilities) {
        $len = strlen($assetCode);
        if ($len <= 0 || $len > 12) {
            throw new InvalidArgumentException("invalid asset code: ". $assetCode);
        }
        $this->trustor = $trustor;
        $this->assetCode = $assetCode;
        $this->authorized = $authorized;
        $this->authorizedToMaintainLiabilities = $authorizedToMaintainLiabilities;
    }

    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : AllowTrustOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : AllowTrustOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the AllowTrust operation.
     *
     * @return AllowTrustOperation The constructed operation
     */
    public function build(): AllowTrustOperation {
        $result = new AllowTrustOperation($this->trustor, $this->assetCode, $this->authorized, $this->authorizedToMaintainLiabilities);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}