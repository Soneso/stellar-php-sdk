<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use InvalidArgumentException;
use Soneso\StellarSDK\Constants\StellarConstants;
use Soneso\StellarSDK\Xdr\XdrSignerKey;

/**
 * Builder for creating SetOptions operations.
 *
 * This builder implements the builder pattern to construct SetOptionsOperation
 * instances with a fluent interface. SetOptions operations allow an account to
 * configure various settings including thresholds, signers, home domain, and flags.
 *
 * @package Soneso\StellarSDK
 * @see SetOptionsOperation
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 *
 * @example
 * $operation = (new SetOptionsOperationBuilder())
 *     ->setHomeDomain('example.com')
 *     ->setMediumThreshold(2)
 *     ->setSourceAccount($sourceId)
 *     ->build();
 */
class SetOptionsOperationBuilder
{
    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * @var string|null The inflation destination account
     */
    private ?string $inflationDestination = null;

    /**
     * @var int|null Flags to clear on the account
     */
    private ?int $clearFlags = null;

    /**
     * @var int|null Flags to set on the account
     */
    private ?int $setFlags = null;

    /**
     * @var int|null The weight of the master key
     */
    private ?int $masterKeyWeight = null;

    /**
     * @var int|null The low threshold for the account
     */
    private ?int $lowThreshold = null;

    /**
     * @var int|null The medium threshold for the account
     */
    private ?int $mediumThreshold = null;

    /**
     * @var int|null The high threshold for the account
     */
    private ?int $highThreshold = null;

    /**
     * @var string|null The home domain for the account
     */
    private ?string $homeDomain = null;

    /**
     * @var XdrSignerKey|null The signer key to add, update, or remove
     */
    private ?XdrSignerKey $signerKey = null;

    /**
     * @var int|null The weight for the signer
     */
    private ?int $signerWeight = null;

    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : SetOptionsOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : SetOptionsOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Sets the inflation destination for the account.
     * @param string $inflationDestination The inflation destination account.
     * @return SetOptionsOperationBuilder Builder object so you can chain methods
     */
    public function setInflationDestination(string $inflationDestination) : SetOptionsOperationBuilder {
        $this->inflationDestination = $inflationDestination;
        return $this;
    }

    /**
     * Clears the given flags from the account.
     * @param int $clearFlags For details about the flags, please refer to the <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a>.
     * @return SetOptionsOperationBuilder Builder object so you can chain methods
     */
    public function setClearFlags(int $clearFlags) : SetOptionsOperationBuilder {
        $this->clearFlags = $clearFlags;
        return $this;
    }

    /**
     * Sets the given flags on the account.
     * @param int $setFlags For details about the flags, please refer to the <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a>.
     * @return SetOptionsOperationBuilder Builder object so you can chain methods
     */
    public function setSetFlags(int $setFlags) : SetOptionsOperationBuilder {
        $this->setFlags = $setFlags;
        return $this;
    }

    /**
     * Sets the weight of the master key.
     * @param int $masterKeyWeight Number between StellarConstants::THRESHOLD_MIN and StellarConstants::THRESHOLD_MAX
     * @return SetOptionsOperationBuilder Builder object so you can chain methods
     */
    public function setMasterKeyWeight(int $masterKeyWeight) : SetOptionsOperationBuilder {
        $this->masterKeyWeight = $masterKeyWeight;
        return $this;
    }

    /**
     * A number from StellarConstants::THRESHOLD_MIN to StellarConstants::THRESHOLD_MAX representing the threshold this account sets on all operations it performs that have a low threshold.
     * @param int $lowThreshold Number between StellarConstants::THRESHOLD_MIN and StellarConstants::THRESHOLD_MAX
     * @return SetOptionsOperationBuilder Builder object so you can chain methods
     */
    public function setLowThreshold(int $lowThreshold) : SetOptionsOperationBuilder {
        $this->lowThreshold = $lowThreshold;
        return $this;
    }

    /**
     * A number from StellarConstants::THRESHOLD_MIN to StellarConstants::THRESHOLD_MAX representing the threshold this account sets on all operations it performs that have a medium threshold.
     * @param int $mediumThreshold Number between StellarConstants::THRESHOLD_MIN and StellarConstants::THRESHOLD_MAX
     * @return SetOptionsOperationBuilder Builder object so you can chain methods
     */
    public function setMediumThreshold(int $mediumThreshold) : SetOptionsOperationBuilder {
        $this->mediumThreshold = $mediumThreshold;
        return $this;
    }

    /**
     * A number from StellarConstants::THRESHOLD_MIN to StellarConstants::THRESHOLD_MAX representing the threshold this account sets on all operations it performs that have a high threshold.
     * @param int $highThreshold Number between StellarConstants::THRESHOLD_MIN and StellarConstants::THRESHOLD_MAX
     * @return SetOptionsOperationBuilder Builder object so you can chain methods
     */
    public function setHighThreshold(int $highThreshold) : SetOptionsOperationBuilder {
        $this->highThreshold = $highThreshold;
        return $this;
    }

    /**
     * Sets the account's home domain address used in <a href="https://www.stellar.org/developers/learn/concepts/federation.html" target="_blank">Federation</a>.
     * @param string $homeDomain A string of the address which can be up to StellarConstants::HOME_DOMAIN_MAX_LENGTH characters.
     * @return SetOptionsOperationBuilder Builder object so you can chain methods
     */
    public function setHomeDomain(string $homeDomain) : SetOptionsOperationBuilder {
        if(strlen($homeDomain) > StellarConstants::HOME_DOMAIN_MAX_LENGTH) {
            throw new InvalidArgumentException("Home domain must be <= " . StellarConstants::HOME_DOMAIN_MAX_LENGTH . " characters");
        }
        $this->homeDomain = $homeDomain;
        return $this;
    }

    /**
     * Add, update, or remove a signer from the account. Signer is deleted if the weight = 0;
     * @param XdrSignerKey $signerKey The signer key. Use {@link Signer} helper to create this object.
     * @param int $weight The weight to attach to the signer (StellarConstants::THRESHOLD_MIN to StellarConstants::THRESHOLD_MAX).
     * @return SetOptionsOperationBuilder Builder object so you can chain methods
     */
    public function setSigner(XdrSignerKey $signerKey, int $weight) : SetOptionsOperationBuilder {
        $this->signerKey = $signerKey;
        $this->signerWeight = $weight & StellarConstants::SIGNER_WEIGHT_MASK;
        return $this;
    }

    /**
     * Builds the SetOptions operation.
     *
     * @return SetOptionsOperation The constructed operation
     */
    public function build(): SetOptionsOperation {
        $result = new SetOptionsOperation($this->inflationDestination, $this->clearFlags, $this->setFlags,
            $this->masterKeyWeight, $this->lowThreshold, $this->mediumThreshold, $this->highThreshold,
            $this->homeDomain, $this->signerKey, $this->signerWeight);
        if ($this->sourceAccount !== null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}