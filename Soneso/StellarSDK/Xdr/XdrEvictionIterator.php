<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrEvictionIterator
{
    public int $bucketListLevel; // uint32
    public bool $isCurrBucket;
    public int $bucketFileOffset; // uint64

    /**
     * @param int $bucketListLevel
     * @param bool $isCurrBucket
     * @param int $bucketFileOffset
     */
    public function __construct(int $bucketListLevel, bool $isCurrBucket, int $bucketFileOffset)
    {
        $this->bucketListLevel = $bucketListLevel;
        $this->isCurrBucket = $isCurrBucket;
        $this->bucketFileOffset = $bucketFileOffset;
    }


    public function encode(): string {
        $body = XdrEncoder::unsignedInteger32($this->bucketListLevel);
        $body .= XdrEncoder::boolean($this->isCurrBucket);
        $body .= XdrEncoder::unsignedInteger64($this->bucketFileOffset);
        return $body;
    }

    public static function decode(XdrBuffer $xdr) : XdrEvictionIterator {
        $bucketListLevel = $xdr->readUnsignedInteger32();
        $isCurrBucket = $xdr->readBoolean();
        $bucketFileOffset = $xdr->readUnsignedInteger64();
        return new XdrEvictionIterator($bucketListLevel, $isCurrBucket, $bucketFileOffset);
    }

    /**
     * @return int
     */
    public function getBucketListLevel(): int
    {
        return $this->bucketListLevel;
    }

    /**
     * @param int $bucketListLevel
     */
    public function setBucketListLevel(int $bucketListLevel): void
    {
        $this->bucketListLevel = $bucketListLevel;
    }

    /**
     * @return bool
     */
    public function isCurrBucket(): bool
    {
        return $this->isCurrBucket;
    }

    /**
     * @param bool $isCurrBucket
     */
    public function setIsCurrBucket(bool $isCurrBucket): void
    {
        $this->isCurrBucket = $isCurrBucket;
    }

    /**
     * @return int
     */
    public function getBucketFileOffset(): int
    {
        return $this->bucketFileOffset;
    }

    /**
     * @param int $bucketFileOffset
     */
    public function setBucketFileOffset(int $bucketFileOffset): void
    {
        $this->bucketFileOffset = $bucketFileOffset;
    }

}