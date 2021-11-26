<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use InvalidArgumentException;
use Soneso\StellarSDK\Xdr\XdrSignerKey;

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


    public function setSourceAccount(string $accountId) {
        $this->sourceAccount = new MuxedAccount($accountId);
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) {
        $this->sourceAccount = $sourceAccount;
    }

    /**
     * @param string $inflationDestination
     */
    public function setInflationDestination(string $inflationDestination): void
    {
        $this->inflationDestination = $inflationDestination;
    }

    /**
     * @param int $clearFlags
     */
    public function setClearFlags(int $clearFlags): void
    {
        $this->clearFlags = $clearFlags;
    }

    /**
     * @param int $setFlags
     */
    public function setSetFlags(int $setFlags): void
    {
        $this->setFlags = $setFlags;
    }

    /**
     * @param int $masterKeyWeight
     */
    public function setMasterKeyWeight(int $masterKeyWeight): void
    {
        $this->masterKeyWeight = $masterKeyWeight;
    }

    /**
     * @param int $lowThreshold
     */
    public function setLowThreshold(int $lowThreshold): void
    {
        $this->lowThreshold = $lowThreshold;
    }

    /**
     * @param int $mediumThreshold
     */
    public function setMediumThreshold(int $mediumThreshold): void
    {
        $this->mediumThreshold = $mediumThreshold;
    }

    /**
     * @param int $highThreshold
     */
    public function setHighThreshold(int $highThreshold): void
    {
        $this->highThreshold = $highThreshold;
    }

    /**
     * @param string $homeDomain
     */
    public function setHomeDomain(string $homeDomain): void
    {
        if(strlen($homeDomain) > 32) {
            throw new InvalidArgumentException("Home domain must be <= 32 characters");
        }
        $this->homeDomain = $homeDomain;
    }

    /**
     * @param XdrSignerKey $signerKey
     */
    public function setSignerKey(XdrSignerKey $signerKey): void
    {
        $this->signerKey = $signerKey;
    }

    /**
     * @param int $signerWeight
     */
    public function setSignerWeight(int $signerWeight): void
    {
        $this->signerWeight = $signerWeight;
    }

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