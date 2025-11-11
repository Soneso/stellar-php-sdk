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
 * Represents <a href="https://developers.stellar.org/docs/start/list-of-operations/#set-options" target="_blank">SetOptions</a> operation.
 *
 * Sets various configuration options for an account, including thresholds, signers, home domain,
 * and account flags. This operation allows comprehensive account configuration.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org/docs/start/list-of-operations/" target="_blank">List of Operations</a>
 * @see SetOptionsOperationBuilder For building this operation
 * @since 1.0.0
 */
class SetOptionsOperation extends AbstractOperation
{
    /**
     * @var string|null Account ID to receive inflation proceeds.
     */
    private ?string $inflationDestination = null;

    /**
     * @var int|null Flags to clear on the account.
     */
    private ?int $clearFlags = null;

    /**
     * @var int|null Flags to set on the account.
     */
    private ?int $setFlags = null;

    /**
     * @var int|null Weight of the master key (0-255).
     */
    private ?int $masterKeyWeight = null;

    /**
     * @var int|null Threshold for low-security operations (0-255).
     */
    private ?int $lowThreshold = null;

    /**
     * @var int|null Threshold for medium-security operations (0-255).
     */
    private ?int $mediumThreshold = null;

    /**
     * @var int|null Threshold for high-security operations (0-255).
     */
    private ?int $highThreshold = null;

    /**
     * @var string|null The home domain of the account.
     */
    private ?string $homeDomain = null;

    /**
     * @var XdrSignerKey|null Additional signer key to add/remove.
     */
    private ?XdrSignerKey $signerKey = null;

    /**
     * @var int|null Weight of the additional signer (0 to remove).
     */
    private ?int $signerWeight = null;


    /**
     * Constructs a new SetOptionsOperation object.
     *
     * @param string|null $inflationDestination Account ID to receive inflation proceeds.
     * @param int|null $clearFlags Flags to clear on the account.
     * @param int|null $setFlags Flags to set on the account.
     * @param int|null $masterKeyWeight Weight of the master key (0-255).
     * @param int|null $lowThreshold Threshold for low-security operations (0-255).
     * @param int|null $mediumThreshold Threshold for medium-security operations (0-255).
     * @param int|null $highThreshold Threshold for high-security operations (0-255).
     * @param string|null $homeDomain The home domain of the account.
     * @param XdrSignerKey|null $signerKey Additional signer key to add/remove.
     * @param int|null $signerWeight Weight of the additional signer (0 to remove).
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
     * Returns the account ID to receive inflation proceeds.
     *
     * @return string|null The inflation destination account ID.
     */
    public function getInflationDestination(): ?string
    {
        return $this->inflationDestination;
    }

    /**
     * Returns flags to clear on the account.
     *
     * For details about the flags, see <a href="https://developers.stellar.org/docs/glossary/accounts/" target="_blank">accounts documentation</a>.
     *
     * @return int|null The flags to clear.
     */
    public function getClearFlags(): ?int
    {
        return $this->clearFlags;
    }

    /**
     * Returns flags to set on the account.
     *
     * For details about the flags, see <a href="https://developers.stellar.org/docs/glossary/accounts/" target="_blank">accounts documentation</a>.
     *
     * @return int|null The flags to set.
     */
    public function getSetFlags(): ?int
    {
        return $this->setFlags;
    }

    /**
     * Returns the weight of the master key.
     *
     * @return int|null The master key weight (0-255).
     */
    public function getMasterKeyWeight(): ?int
    {
        return $this->masterKeyWeight;
    }

    /**
     * Returns the threshold for low-security operations.
     *
     * A number from 0-255. See <a href="https://developers.stellar.org/docs/glossary/multisig/" target="_blank">multisig documentation</a>.
     *
     * @return int|null The low threshold.
     */
    public function getLowThreshold(): ?int
    {
        return $this->lowThreshold;
    }

    /**
     * Returns the threshold for medium-security operations.
     *
     * A number from 0-255. See <a href="https://developers.stellar.org/docs/glossary/multisig/" target="_blank">multisig documentation</a>.
     *
     * @return int|null The medium threshold.
     */
    public function getMediumThreshold(): ?int
    {
        return $this->mediumThreshold;
    }

    /**
     * Returns the threshold for high-security operations.
     *
     * A number from 0-255. See <a href="https://developers.stellar.org/docs/glossary/multisig/" target="_blank">multisig documentation</a>.
     *
     * @return int|null The high threshold.
     */
    public function getHighThreshold(): ?int
    {
        return $this->highThreshold;
    }

    /**
     * Returns the home domain of the account.
     *
     * @return string|null The home domain.
     */
    public function getHomeDomain(): ?string
    {
        return $this->homeDomain;
    }

    /**
     * Returns the additional signer key added or removed in this operation.
     *
     * @return XdrSignerKey|null The signer key.
     */
    public function getSignerKey(): ?XdrSignerKey
    {
        return $this->signerKey;
    }

    /**
     * Returns the weight of the additional signer.
     *
     * The signer is deleted if the weight is 0.
     *
     * @return int|null The signer weight.
     */
    public function getSignerWeight(): ?int
    {
        return $this->signerWeight;
    }

    /**
     * Creates a SetOptionsOperation from XDR operation object.
     *
     * @param XdrSetOptionsOperation $xdrOp The XDR operation object to convert.
     * @return SetOptionsOperation The created operation instance.
     */
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

    /**
     * Converts the operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body.
     */
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