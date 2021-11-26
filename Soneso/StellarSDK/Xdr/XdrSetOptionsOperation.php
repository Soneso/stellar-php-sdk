<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrSetOptionsOperation
{
    private ?XdrAccountID $inflationDest = null;
    private ?int $clearFlags = null; // uint32
    private ?int $setFlags = null; // uint32
    private ?int $masterWeight = null; // uint32
    private ?int $lowThreshold = null; // uint32
    private ?int $medThreshold = null; // uint32
    private ?int $highThreshold = null; // uint32
    private ?string $homeDomain = null;
    private ?XdrSigner $signer = null;

    /**
     * @return XdrAccountID|null
     */
    public function getInflationDest(): ?XdrAccountID
    {
        return $this->inflationDest;
    }

    /**
     * @param XdrAccountID|null $inflationDest
     */
    public function setInflationDest(?XdrAccountID $inflationDest): void
    {
        $this->inflationDest = $inflationDest;
    }

    /**
     * @return int|null
     */
    public function getClearFlags(): ?int
    {
        return $this->clearFlags;
    }

    /**
     * @param int|null $clearFlags
     */
    public function setClearFlags(?int $clearFlags): void
    {
        $this->clearFlags = $clearFlags;
    }

    /**
     * @return int|null
     */
    public function getSetFlags(): ?int
    {
        return $this->setFlags;
    }

    /**
     * @param int|null $setFlags
     */
    public function setSetFlags(?int $setFlags): void
    {
        $this->setFlags = $setFlags;
    }

    /**
     * @return int|null
     */
    public function getMasterWeight(): ?int
    {
        return $this->masterWeight;
    }

    /**
     * @param int|null $masterWeight
     */
    public function setMasterWeight(?int $masterWeight): void
    {
        $this->masterWeight = $masterWeight;
    }

    /**
     * @return int|null
     */
    public function getLowThreshold(): ?int
    {
        return $this->lowThreshold;
    }

    /**
     * @param int|null $lowThreshold
     */
    public function setLowThreshold(?int $lowThreshold): void
    {
        $this->lowThreshold = $lowThreshold;
    }

    /**
     * @return int|null
     */
    public function getMedThreshold(): ?int
    {
        return $this->medThreshold;
    }

    /**
     * @param int|null $medThreshold
     */
    public function setMedThreshold(?int $medThreshold): void
    {
        $this->medThreshold = $medThreshold;
    }

    /**
     * @return int|null
     */
    public function getHighThreshold(): ?int
    {
        return $this->highThreshold;
    }

    /**
     * @param int|null $highThreshold
     */
    public function setHighThreshold(?int $highThreshold): void
    {
        $this->highThreshold = $highThreshold;
    }

    /**
     * @return string|null
     */
    public function getHomeDomain(): ?string
    {
        return $this->homeDomain;
    }

    /**
     * @param string|null $homeDomain
     */
    public function setHomeDomain(?string $homeDomain): void
    {
        $this->homeDomain = $homeDomain;
    }

    /**
     * @return XdrSigner|null
     */
    public function getSigner(): ?XdrSigner
    {
        return $this->signer;
    }

    /**
     * @param XdrSigner|null $signer
     */
    public function setSigner(?XdrSigner $signer): void
    {
        $this->signer = $signer;
    }

    public function encode(): string {
        $bytes = $this->inflationDest ? XdrEncoder::integer32(1) : XdrEncoder::integer32(0);
        if ($this->inflationDest) {
            $bytes .= $this->inflationDest->encode();
        }
        $bytes .= XdrEncoder::optionalUnsignedInteger($this->clearFlags);
        $bytes .= XdrEncoder::optionalUnsignedInteger($this->setFlags);
        $bytes .= XdrEncoder::optionalUnsignedInteger($this->masterWeight);
        $bytes .= XdrEncoder::optionalUnsignedInteger($this->lowThreshold);
        $bytes .= XdrEncoder::optionalUnsignedInteger($this->medThreshold);
        $bytes .= XdrEncoder::optionalUnsignedInteger($this->highThreshold);
        $bytes .= XdrEncoder::optionalString($this->homeDomain, 32);
        $bytes .= $this->signer ? XdrEncoder::integer32(1) : XdrEncoder::integer32(0);
        if ($this->signer) {
            $bytes .= $this->signer->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrSetOptionsOperation {
        $result =  new XdrSetOptionsOperation();
        if ($xdr->readBoolean()) {
            $result->inflationDest = XdrAccountID::decode($xdr);
        }
        if ($xdr->readBoolean()) {
            $result->clearFlags = $xdr->readUnsignedInteger32();
        }
        if ($xdr->readBoolean()) {
            $result->setFlags = $xdr->readUnsignedInteger32();
        }
        if ($xdr->readBoolean()) {
            $result->masterWeight = $xdr->readUnsignedInteger32();
        }
        if ($xdr->readBoolean()) {
            $result->lowThreshold = $xdr->readUnsignedInteger32();
        }
        if ($xdr->readBoolean()) {
            $result->medThreshold = $xdr->readUnsignedInteger32();
        }
        if ($xdr->readBoolean()) {
            $result->highThreshold = $xdr->readUnsignedInteger32();
        }
        if ($xdr->readBoolean()) {
            $result->homeDomain = $xdr->readString(32);
        }
        if ($xdr->readBoolean()) {
            $result->signer = XdrSigner::decode($xdr);
        }
        return $result;
    }
}