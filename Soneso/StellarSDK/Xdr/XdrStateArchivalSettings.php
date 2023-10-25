<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrStateArchivalSettings
{
    public int $maxEntryTTL; // uint32
    public int $minTemporaryTTL; // uint32
    public int $minPersistentTTL; // uint32
    public int $persistentRentRateDenominator; // int64
    public int $tempRentRateDenominator; // int64
    public int $maxEntriesToArchive; // uint32
    public int $bucketListSizeWindowSampleSize; // uint32
    public int $evictionScanSize; // uint64
    public int $startingEvictionScanLevel; // uint32

    /**
     * @param int $maxEntryTTL
     * @param int $minTemporaryTTL
     * @param int $minPersistentTTL
     * @param int $persistentRentRateDenominator
     * @param int $tempRentRateDenominator
     * @param int $maxEntriesToArchive
     * @param int $bucketListSizeWindowSampleSize
     * @param int $evictionScanSize
     * @param int $startingEvictionScanLevel
     */
    public function __construct(int $maxEntryTTL, int $minTemporaryTTL,
                                int $minPersistentTTL,
                                int $persistentRentRateDenominator, int $tempRentRateDenominator,
                                int $maxEntriesToArchive, int $bucketListSizeWindowSampleSize,
                                int $evictionScanSize, int $startingEvictionScanLevel)
    {
        $this->maxEntryTTL = $maxEntryTTL;
        $this->minTemporaryTTL = $minTemporaryTTL;
        $this->minPersistentTTL = $minPersistentTTL;
        $this->persistentRentRateDenominator = $persistentRentRateDenominator;
        $this->tempRentRateDenominator = $tempRentRateDenominator;
        $this->maxEntriesToArchive = $maxEntriesToArchive;
        $this->bucketListSizeWindowSampleSize = $bucketListSizeWindowSampleSize;
        $this->evictionScanSize = $evictionScanSize;
        $this->startingEvictionScanLevel = $startingEvictionScanLevel;
    }


    public function encode(): string {
        $body = XdrEncoder::unsignedInteger32($this->maxEntryTTL);
        $body .= XdrEncoder::unsignedInteger32($this->minTemporaryTTL);
        $body .= XdrEncoder::unsignedInteger32($this->minPersistentTTL);
        $body .= XdrEncoder::integer64($this->persistentRentRateDenominator);
        $body .= XdrEncoder::integer64($this->tempRentRateDenominator);
        $body .= XdrEncoder::unsignedInteger32($this->maxEntriesToArchive);
        $body .= XdrEncoder::unsignedInteger32($this->bucketListSizeWindowSampleSize);
        $body .= XdrEncoder::unsignedInteger64($this->evictionScanSize);
        $body .= XdrEncoder::unsignedInteger32($this->startingEvictionScanLevel);
        return $body;
    }

    public static function decode(XdrBuffer $xdr) : XdrStateArchivalSettings {
        $maxEntryTTL = $xdr->readUnsignedInteger32();
        $minTemporaryTTL = $xdr->readUnsignedInteger32();
        $minPersistentTTL = $xdr->readUnsignedInteger32();
        $persistentRentRateDenominator = $xdr->readInteger64();
        $tempRentRateDenominator = $xdr->readInteger64();
        $maxEntriesToArchive = $xdr->readUnsignedInteger32();
        $bucketListSizeWindowSampleSize = $xdr->readUnsignedInteger32();
        $evictionScanSize = $xdr->readUnsignedInteger64();
        $startingEvictionScanLevel = $xdr->readUnsignedInteger32();
        return new XdrStateArchivalSettings($maxEntryTTL, $minTemporaryTTL,
            $minPersistentTTL, $persistentRentRateDenominator,
            $tempRentRateDenominator, $maxEntriesToArchive,$bucketListSizeWindowSampleSize,
            $evictionScanSize, $startingEvictionScanLevel);
    }

    /**
     * @return int
     */
    public function getStartingEvictionScanLevel(): int
    {
        return $this->startingEvictionScanLevel;
    }

    /**
     * @param int $startingEvictionScanLevel
     */
    public function setStartingEvictionScanLevel(int $startingEvictionScanLevel): void
    {
        $this->startingEvictionScanLevel = $startingEvictionScanLevel;
    }

    /**
     * @return int
     */
    public function getMaxEntryTTL(): int
    {
        return $this->maxEntryTTL;
    }

    /**
     * @param int $maxEntryTTL
     */
    public function setMaxEntryTTL(int $maxEntryTTL): void
    {
        $this->maxEntryTTL = $maxEntryTTL;
    }

    /**
     * @return int
     */
    public function getMinTemporaryTTL(): int
    {
        return $this->minTemporaryTTL;
    }

    /**
     * @param int $minTemporaryTTL
     */
    public function setMinTemporaryTTL(int $minTemporaryTTL): void
    {
        $this->minTemporaryTTL = $minTemporaryTTL;
    }

    /**
     * @return int
     */
    public function getMinPersistentTTL(): int
    {
        return $this->minPersistentTTL;
    }

    /**
     * @param int $minPersistentTTL
     */
    public function setMinPersistentTTL(int $minPersistentTTL): void
    {
        $this->minPersistentTTL = $minPersistentTTL;
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
    public function getMaxEntriesToArchive(): int
    {
        return $this->maxEntriesToArchive;
    }

    /**
     * @param int $maxEntriesToArchive
     */
    public function setMaxEntriesToArchive(int $maxEntriesToArchive): void
    {
        $this->maxEntriesToArchive = $maxEntriesToArchive;
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