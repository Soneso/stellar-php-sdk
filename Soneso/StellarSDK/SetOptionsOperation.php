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

/**
 * Represents <a href="https://developers.stellar.org/docs/start/list-of-operations/#set-options">SetOptions</a> operation.
 * @see <a href="https://developers.stellar.org/docs/start/list-of-operations/">List of Operations</a>
 */
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


    /**
     * Creates a new SetOptionsOperation object.
     * @param string|null $inflationDestination
     * @param int|null $clearFlags
     * @param int|null $setFlags
     * @param int|null $masterKeyWeight
     * @param int|null $lowThreshold
     * @param int|null $mediumThreshold
     * @param int|null $highThreshold
     * @param string|null $homeDomain
     * @param XdrSignerKey|null $signerKey
     * @param int|null $signerWeight
     */
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
     * Account of the inflation destination.
     * @return string|null
     */
    public function getInflationDestination(): ?string
    {
        return $this->inflationDestination;
    }

    /**
     * Indicates which flags to clear. For details about the flags, please refer to the <a href="https://developers.stellar.org/docs/glossary/accounts/" target="_blank">accounts doc</a>.
     * @return int|null
     */
    public function getClearFlags(): ?int
    {
        return $this->clearFlags;
    }

    /**
     * Indicates which flags to set. For details about the flags, please refer to the <a href="https://developers.stellar.org/docs/glossary/accounts/" target="_blank">accounts doc</a>.
     * @return int|null
     */
    public function getSetFlags(): ?int
    {
        return $this->setFlags;
    }

    /**
     * Weight of the master key.
     * @return int|null
     */
    public function getMasterKeyWeight(): ?int
    {
        return $this->masterKeyWeight;
    }

    /**
     * A number from 0-255 representing the threshold this account sets on all operations it performs that have <a href="https://developers.stellar.org/docs/glossary/multisig/" target="_blank">a low threshold</a>.
     * @return int|null
     */
    public function getLowThreshold(): ?int
    {
        return $this->lowThreshold;
    }

    /**
     * A number from 0-255 representing the threshold this account sets on all operations it performs that have <a href="https://developers.stellar.org/docs/glossary/multisig/" target="_blank">a medium threshold</a>.
     * @return int|null
     */
    public function getMediumThreshold(): ?int
    {
        return $this->mediumThreshold;
    }

    /**
     * A number from 0-255 representing the threshold this account sets on all operations it performs that have <a href="https://developers.stellar.org/docs/glossary/multisig/" target="_blank">a high threshold</a>.
     * @return int|null
     */
    public function getHighThreshold(): ?int
    {
        return $this->highThreshold;
    }

    /**
     * The home domain of an account.
     * @return string|null
     */
    public function getHomeDomain(): ?string
    {
        return $this->homeDomain;
    }

    /**
     * Additional signer added/removed in this operation.
     * @return XdrSignerKey|null
     */
    public function getSignerKey(): ?XdrSignerKey
    {
        return $this->signerKey;
    }

    /**
     * Additional signer weight. The signer is deleted if the weight is 0.
     * @return int|null
     */
    public function getSignerWeight(): ?int
    {
        return $this->signerWeight;
    }

    public static function fromXdrOperation(XdrSetOptionsOperation $xdrOp): SetOptionsOperation {

        $inflationDestination = $xdrOp->getInflationDest()?->getAccountId();
        $clearFlags = $xdrOp->getClearFlags();
        $setFlags = $xdrOp->getSetFlags();
        $masterKeyWeight = $xdrOp->getMasterWeight();
        $lowThreshold = $xdrOp->getLowThreshold();
        $mediumThreshold = $xdrOp->getMedThreshold();
        $highThreshold = $xdrOp->getHighThreshold();
        $homeDomain = $xdrOp->getHomeDomain();
        $signerKey = $xdrOp->getSigner()?->getKey();
        $signerWeight = $xdrOp->getSigner()?->getWeight();

        return new SetOptionsOperation($inflationDestination, $clearFlags, $setFlags, $masterKeyWeight, $lowThreshold, $mediumThreshold,
            $highThreshold, $homeDomain, $signerKey, $signerWeight);
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
        if ($this->masterKeyWeight !== null) {
            $result->setMasterWeight($this->masterKeyWeight);
        }
        if ($this->lowThreshold !== null) {
            $result->setLowThreshold($this->lowThreshold);
        }
        if ($this->mediumThreshold !== null) {
            $result->setMedThreshold($this->mediumThreshold);
        }
        if ($this->highThreshold !== null) {
            $result->setHighThreshold($this->highThreshold);
        }
        if ($this->homeDomain) {
            $result->setHomeDomain($this->homeDomain);
        }
        if ($this->signerKey) {
            $weight = $this->signerWeight ?? 0;
            $signer = new XdrSigner($this->signerKey, $weight);
            $result->setSigner($signer);
        }
        $type = new XdrOperationType(XdrOperationType::SET_OPTIONS);
        $body = new XdrOperationBody($type);
        $body->setSetOptionsOp($result);
        return $body;
    }
}