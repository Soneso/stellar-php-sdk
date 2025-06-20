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
    public int $liveSorobanStateSizeWindowSampleSize; // uint32
    public int $liveSorobanStateSizeWindowSamplePeriod; // uint32
    public int $evictionScanSize; // uint32
    public int $startingEvictionScanLevel; // uint32

    /**
     * @param int $maxEntryTTL
     * @param int $minTemporaryTTL
     * @param int $minPersistentTTL
     * @param int $persistentRentRateDenominator
     * @param int $tempRentRateDenominator
     * @param int $maxEntriesToArchive
     * @param int $liveSorobanStateSizeWindowSampleSize
     * @param int $liveSorobanStateSizeWindowSamplePeriod
     * @param int $evictionScanSize
     * @param int $startingEvictionScanLevel
     */
    public function __construct(
        int $maxEntryTTL,
        int $minTemporaryTTL,
        int $minPersistentTTL,
        int $persistentRentRateDenominator,
        int $tempRentRateDenominator,
        int $maxEntriesToArchive,
        int $liveSorobanStateSizeWindowSampleSize,
        int $liveSorobanStateSizeWindowSamplePeriod,
        int $evictionScanSize,
        int $startingEvictionScanLevel,
    )
    {
        $this->maxEntryTTL = $maxEntryTTL;
        $this->minTemporaryTTL = $minTemporaryTTL;
        $this->minPersistentTTL = $minPersistentTTL;
        $this->persistentRentRateDenominator = $persistentRentRateDenominator;
        $this->tempRentRateDenominator = $tempRentRateDenominator;
        $this->maxEntriesToArchive = $maxEntriesToArchive;
        $this->liveSorobanStateSizeWindowSampleSize = $liveSorobanStateSizeWindowSampleSize;
        $this->liveSorobanStateSizeWindowSamplePeriod = $liveSorobanStateSizeWindowSamplePeriod;
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
        $body .= XdrEncoder::unsignedInteger32($this->liveSorobanStateSizeWindowSampleSize);
        $body .= XdrEncoder::unsignedInteger32($this->liveSorobanStateSizeWindowSamplePeriod);
        $body .= XdrEncoder::unsignedInteger32($this->evictionScanSize);
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
        $bucketListWindowSamplePeriod = $xdr->readUnsignedInteger32();
        $evictionScanSize = $xdr->readUnsignedInteger32();
        $startingEvictionScanLevel = $xdr->readUnsignedInteger32();
        return new XdrStateArchivalSettings(
            $maxEntryTTL,
            $minTemporaryTTL,
            $minPersistentTTL,
            $persistentRentRateDenominator,
            $tempRentRateDenominator,
            $maxEntriesToArchive,
            $bucketListSizeWindowSampleSize,
            $bucketListWindowSamplePeriod,
            $evictionScanSize,
            $startingEvictionScanLevel,
        );
    }

}