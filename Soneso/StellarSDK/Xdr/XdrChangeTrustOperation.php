<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;

class XdrChangeTrustOperation
{
    const MAX_LIMIT = "9223372036854775807";

    private XdrChangeTrustAsset $line;
    private BigInteger $limit; // in stoops

    public function __construct(XdrChangeTrustAsset $line, ?BigInteger $limit = null) {
        $this->line = $line;
        if ($limit != null) {
            $this->limit = $limit;
        } else {
            $this->limit = new BigInteger(self::MAX_LIMIT);
        }
    }

    /**
     * @return XdrChangeTrustAsset
     */
    public function getLine(): XdrChangeTrustAsset
    {
        return $this->line;
    }

    /**
     * @return BigInteger
     */
    public function getLimit(): BigInteger
    {
        return $this->limit;
    }

    public function encode(): string {
        $bytes = $this->line->encode();
        $bytes .= XdrEncoder::bigInteger64($this->limit);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrChangeTrustOperation {
        $line = XdrChangeTrustAsset::decode($xdr);
        $limit = $xdr->readBigInteger64();//new BigInteger($xdr->readInteger64());
        return new XdrChangeTrustOperation($line, $limit);
    }
}