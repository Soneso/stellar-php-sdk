<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use InvalidArgumentException;
use Soneso\StellarSDK\Xdr\XdrSignerKey;

/**
 * Builds SetOptions operation.
 * @see SetOptionsOperation
 */
class SetOptionsOperationBuilder
{
    private ?MuxedAccount $sourceAccount = null;
    private ?string $inflationDestination = null;
    private ?int $clearFlags = null;
    private ?int $setFlags = null;
    private ?int $masterKeyWeight = null;
    private ?int $lowThreshold = null;
    private ?int $mediumThreshold = null;
    private ?int $highThreshold = null;
    private ?string $homeDomain = null;
    private ?XdrSignerKey $signerKey = null;
    private ?int $signerWeight = null;

    /**
     * Sets the source account for this operation. G...
     * @param string $accountId The operation's source account.
     * @return SetOptionsOperationBuilder Builder object so you can chain methods
     */
    public function setSourceAccount(string $accountId) : SetOptionsOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     * @param MuxedAccount $sourceAccount The operation's source account.
     * @return SetOptionsOperationBuilder Builder object so you can chain methods
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
     * @param int $clearFlags For details about the flags, please refer to the <a href="https://developers.stellar.org/docs/glossary/accounts/" target="_blank">accounts doc</a>.
     * @return SetOptionsOperationBuilder Builder object so you can chain methods
     */
    public function setClearFlags(int $clearFlags) : SetOptionsOperationBuilder {
        $this->clearFlags = $clearFlags;
        return $this;
    }

    /**
     * Sets the given flags on the account.
     * @param int $setFlags For details about the flags, please refer to the <a href="https://developers.stellar.org/docs/glossary/accounts/" target="_blank">accounts doc</a>.
     * @return SetOptionsOperationBuilder Builder object so you can chain methods
     */
    public function setSetFlags(int $setFlags) : SetOptionsOperationBuilder {
        $this->setFlags = $setFlags;
        return $this;
    }

    /**
     * Sets the weight of the master key.
     * @param int $masterKeyWeight Number between 0 and 255
     * @return SetOptionsOperationBuilder Builder object so you can chain methods
     */
    public function setMasterKeyWeight(int $masterKeyWeight) : SetOptionsOperationBuilder {
        $this->masterKeyWeight = $masterKeyWeight;
        return $this;
    }

    /**
     * A number from 0-255 representing the threshold this account sets on all operations it performs that have a low threshold.
     * @param int $lowThreshold Number between 0 and 255
     * @return SetOptionsOperationBuilder Builder object so you can chain methods
     */
    public function setLowThreshold(int $lowThreshold) : SetOptionsOperationBuilder {
        $this->lowThreshold = $lowThreshold;
        return $this;
    }

    /**
     * A number from 0-255 representing the threshold this account sets on all operations it performs that have a medium threshold.
     * @param int $mediumThreshold Number between 0 and 255
     * @return SetOptionsOperationBuilder Builder object so you can chain methods
     */
    public function setMediumThreshold(int $mediumThreshold) : SetOptionsOperationBuilder {
        $this->mediumThreshold = $mediumThreshold;
        return $this;
    }

    /**
     * A number from 0-255 representing the threshold this account sets on all operations it performs that have a high threshold.
     * @param int $highThreshold Number between 0 and 255
     * @return SetOptionsOperationBuilder Builder object so you can chain methods
     */
    public function setHighThreshold(int $highThreshold) : SetOptionsOperationBuilder {
        $this->highThreshold = $highThreshold;
        return $this;
    }

    /**
     * Sets the account's home domain address used in <a href="https://www.stellar.org/developers/learn/concepts/federation.html" target="_blank">Federation</a>.
     * @param string $homeDomain A string of the address which can be up to 32 characters.
     * @return SetOptionsOperationBuilder Builder object so you can chain methods
     */
    public function setHomeDomain(string $homeDomain) : SetOptionsOperationBuilder {
        if(strlen($homeDomain) > 32) {
            throw new InvalidArgumentException("Home domain must be <= 32 characters");
        }
        $this->homeDomain = $homeDomain;
        return $this;
    }

    /**
     * Add, update, or remove a signer from the account. Signer is deleted if the weight = 0;
     * @param XdrSignerKey $signerKey The signer key. Use {@link Signer} helper to create this object.
     * @param int $weight The weight to attach to the signer (0-255).
     * @return SetOptionsOperationBuilder Builder object so you can chain methods
     */
    public function setSigner(XdrSignerKey $signerKey, int $weight) : SetOptionsOperationBuilder {
        $this->signerKey = $signerKey;
        $this->signerWeight = $weight & 0xFF;
        return $this;
    }

    /**
     * Builds a SetOptionsOperation.
     * @return SetOptionsOperation The operation build.
     */
    public function build(): SetOptionsOperation {
        $result = new SetOptionsOperation($this->inflationDestination, $this->clearFlags, $this->setFlags,
            $this->masterKeyWeight, $this->lowThreshold, $this->mediumThreshold, $this->highThreshold,
            $this->homeDomain, $this->signerKey, $this->signerWeight);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}