<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrSetOptionsOperation;
use Soneso\StellarSDK\Xdr\XdrSigner;
use Soneso\StellarSDK\Xdr\XdrSignerKey;

class SetOptionsOperation extends AbstractOperation
{
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


    public function __construct(?string $inflationDestination = null, ?int $clearFlags = null, ?int $setFlags = null, ?int $masterKeyWeight = null, ?int $lowThreshold = null,?int $mediumThreshold = null,
                                ?int $highThreshold = null, ?string $homeDomain = null, ?XdrSignerKey $signerKey = null, ?int $signerWeight = null) {
        $this->inflationDestination = $inflationDestination;
        $this->clearFlags = $clearFlags;
        $this->setFlags = $setFlags;
        $this->masterKeyWeight = $masterKeyWeight;
        $this->lowThreshold = $lowThreshold;
        $this->mediumThreshold = $mediumThreshold;
        $this->highThreshold = $highThreshold;
        $this->homeDomain = $homeDomain;
        $this->signerKey = $signerKey;
        $this->signerWeight = $signerWeight;
    }

    /**
     * @return string|null
     */
    public function getInflationDestination(): ?string
    {
        return $this->inflationDestination;
    }

    /**
     * @return int|null
     */
    public function getClearFlags(): ?int
    {
        return $this->clearFlags;
    }

    /**
     * @return int|null
     */
    public function getSetFlags(): ?int
    {
        return $this->setFlags;
    }

    /**
     * @return int|null
     */
    public function getMasterKeyWeight(): ?int
    {
        return $this->masterKeyWeight;
    }

    /**
     * @return int|null
     */
    public function getLowThreshold(): ?int
    {
        return $this->lowThreshold;
    }

    /**
     * @return int|null
     */
    public function getMediumThreshold(): ?int
    {
        return $this->mediumThreshold;
    }

    /**
     * @return int|null
     */
    public function getHighThreshold(): ?int
    {
        return $this->highThreshold;
    }

    /**
     * @return string|null
     */
    public function getHomeDomain(): ?string
    {
        return $this->homeDomain;
    }

    /**
     * @return XdrSignerKey|null
     */
    public function getSignerKey(): ?XdrSignerKey
    {
        return $this->signerKey;
    }

    /**
     * @return int|null
     */
    public function getSignerWeight(): ?int
    {
        return $this->signerWeight;
    }

    public function toOperationBody(): XdrOperationBody
    {
        $result = new XdrSetOptionsOperation();
        if ($this->inflationDestination) {
            $accId = new XdrAccountID($this->inflationDestination);
            $result->setInflationDest($accId);
        }
        if ($this->clearFlags) {
            $result->setClearFlags($this->clearFlags);
        }
        if ($this->setFlags) {
            $result->setSetFlags($this->setFlags);
        }
        if ($this->masterKeyWeight) {
            $result->setMasterWeight($this->masterKeyWeight);
        }
        if ($this->lowThreshold) {
            $result->setLowThreshold($this->lowThreshold);
        }
        if ($this->mediumThreshold) {
            $result->setMedThreshold($this->mediumThreshold);
        }
        if ($this->highThreshold) {
            $result->setHighThreshold($this->highThreshold);
        }
        if ($this->homeDomain) {
            $result->setHomeDomain($this->homeDomain);
        }
        if ($this->signerKey) {
            $weight = $this->signerWeight ?? 0;
            $signer = new XdrSigner($this->signer, $weight);
            $result->setSigner($signer);
        }
        $type = new XdrOperationType(XdrOperationType::SET_OPTIONS);
        $body = new XdrOperationBody($type);
        $body->setSetOptionsOp($result);
        return $body;
    }
}