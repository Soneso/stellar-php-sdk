<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrStateExpirationSettings
{
    public int $maxEntryExpiration; // uint32
    public int $minTempEntryExpiration; // uint32
    public int $minPersistentEntryExpiration; // uint32
    public int $autoBumpLedgers; // uint32
    public int $persistentRentRateDenominator; // int64
    public int $tempRentRateDenominator; // int64
    public int $maxEntriesToExpire; // uint32
    public int $bucketListSizeWindowSampleSize; // uint32
    public int $evictionScanSize; // uint64

    /**
     * @param int $maxEntryExpiration
     * @param int $minTempEntryExpiration
     * @param int $minPersistentEntryExpiration
     * @param int $autoBumpLedgers
     * @param int $persistentRentRateDenominator
     * @param int $tempRentRateDenominator
     * @param int $maxEntriesToExpire
     * @param int $bucketListSizeWindowSampleSize
     * @param int $evictionScanSize
     */
    public function __construct(int $maxEntryExpiration, int $minTempEntryExpiration,
                                int $minPersistentEntryExpiration, int $autoBumpLedgers,
                                int $persistentRentRateDenominator, int $tempRentRateDenominator,
                                int $maxEntriesToExpire, int $bucketListSizeWindowSampleSize,
                                int $evictionScanSize)
    {
        $this->maxEntryExpiration = $maxEntryExpiration;
        $this->minTempEntryExpiration = $minTempEntryExpiration;
        $this->minPersistentEntryExpiration = $minPersistentEntryExpiration;
        $this->autoBumpLedgers = $autoBumpLedgers;
        $this->persistentRentRateDenominator = $persistentRentRateDenominator;
        $this->tempRentRateDenominator = $tempRentRateDenominator;
        $this->maxEntriesToExpire = $maxEntriesToExpire;
        $this->bucketListSizeWindowSampleSize = $bucketListSizeWindowSampleSize;
        $this->evictionScanSize = $evictionScanSize;
    }


    public function encode(): string {
        $body = XdrEncoder::unsignedInteger32($this->maxEntryExpiration);
        $body .= XdrEncoder::unsignedInteger32($this->minTempEntryExpiration);
        $body .= XdrEncoder::unsignedInteger32($this->minPersistentEntryExpiration);
        $body .= XdrEncoder::unsignedInteger32($this->autoBumpLedgers);
        $body .= XdrEncoder::integer64($this->persistentRentRateDenominator);
        $body .= XdrEncoder::integer64($this->tempRentRateDenominator);
        $body .= XdrEncoder::unsignedInteger32($this->maxEntriesToExpire);
        $body .= XdrEncoder::unsignedInteger32($this->bucketListSizeWindowSampleSize);
        $body .= XdrEncoder::unsignedInteger64($this->evictionScanSize);

        return $body;
    }

    public static function decode(XdrBuffer $xdr) : XdrStateExpirationSettings {
        $maxEntryExpiration = $xdr->readUnsignedInteger32();
        $minTempEntryExpiration = $xdr->readUnsignedInteger32();
        $minPersistentEntryExpiration = $xdr->readUnsignedInteger32();
        $autoBumpLedgers = $xdr->readUnsignedInteger32();
        $persistentRentRateDenominator = $xdr->readInteger64();
        $tempRentRateDenominator = $xdr->readInteger64();
        $maxEntriesToExpire = $xdr->readUnsignedInteger32();
        $bucketListSizeWindowSampleSize = $xdr->readUnsignedInteger32();
        $evictionScanSize = $xdr->readUnsignedInteger64();

        return new XdrStateExpirationSettings($maxEntryExpiration, $minTempEntryExpiration,
            $minPersistentEntryExpiration, $autoBumpLedgers, $persistentRentRateDenominator,
            $tempRentRateDenominator, $maxEntriesToExpire,$bucketListSizeWindowSampleSize,
            $evictionScanSize);
    }

    /**
     * @return int
     */
    public function getMaxEntryExpiration(): int
    {
        return $this->maxEntryExpiration;
    }

    /**
     * @param int $maxEntryExpiration
     */
    public function setMaxEntryExpiration(int $maxEntryExpiration): void
    {
        $this->maxEntryExpiration = $maxEntryExpiration;
    }

    /**
     * @return int
     */
    public function getMinTempEntryExpiration(): int
    {
        return $this->minTempEntryExpiration;
    }

    /**
     * @param int $minTempEntryExpiration
     */
    public function setMinTempEntryExpiration(int $minTempEntryExpiration): void
    {
        $this->minTempEntryExpiration = $minTempEntryExpiration;
    }

    /**
     * @return int
     */
    public function getMinPersistentEntryExpiration(): int
    {
        return $this->minPersistentEntryExpiration;
    }

    /**
     * @param int $minPersistentEntryExpiration
     */
    public function setMinPersistentEntryExpiration(int $minPersistentEntryExpiration): void
    {
        $this->minPersistentEntryExpiration = $minPersistentEntryExpiration;
    }

    /**
     * @return int
     */
    public function getAutoBumpLedgers(): int
    {
        return $this->autoBumpLedgers;
    }

    /**
     * @param int $autoBumpLedgers
     */
    public function setAutoBumpLedgers(int $autoBumpLedgers): void
    {
        $this->autoBumpLedgers = $autoBumpLedgers;
    }

    /**
     * @return int
     */
    public function getPersistentRentRateDenominator(): int
    {
        return $this->persistentRentRateDenominator;
    }

    /**
     * @param int $persistentRentRateDenominator
     */
    public function setPersistentRentRateDenominator(int $persistentRentRateDenominator): void
    {
        $this->persistentRentRateDenominator = $persistentRentRateDenominator;
    }

    /**
     * @return int
     */
    public function getTempRentRateDenominator(): int
    {
        return $this->tempRentRateDenominator;
    }

    /**
     * @param int $tempRentRateDenominator
     */
    public function setTempRentRateDenominator(int $tempRentRateDenominator): void
    {
        $this->tempRentRateDenominator = $tempRentRateDenominator;
    }

    /**
     * @return int
     */
    public function getMaxEntriesToExpire(): int
    {
        return $this->maxEntriesToExpire;
    }

    /**
     * @param int $maxEntriesToExpire
     */
    public function setMaxEntriesToExpire(int $maxEntriesToExpire): void
    {
        $this->maxEntriesToExpire = $maxEntriesToExpire;
    }

    /**
     * @return int
     */
    public function getBucketListSizeWindowSampleSize(): int
    {
        return $this->bucketListSizeWindowSampleSize;
    }

    /**
     * @param int $bucketListSizeWindowSampleSize
     */
    public function setBucketListSizeWindowSampleSize(int $bucketListSizeWindowSampleSize): void
    {
        $this->bucketListSizeWindowSampleSize = $bucketListSizeWindowSampleSize;
    }

    /**
     * @return int
     */
    public function getEvictionScanSize(): int
    {
        return $this->evictionScanSize;
    }

    /**
     * @param int $evictionScanSize
     */
    public function setEvictionScanSize(int $evictionScanSize): void
    {
        $this->evictionScanSize = $evictionScanSize;
    }

}