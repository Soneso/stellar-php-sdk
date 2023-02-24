<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Soroban\Responses;

class EventInfoValue
{
    public string $xdr;

    /**
     * @param string $xdr
     */
    public function __construct(string $xdr)
    {
        $this->xdr = $xdr;
    }

    public static function fromJson(array $json): EventInfoValue
    {
        return new EventInfoValue($json['xdr']);
    }
    /**
     * @return string
     */
    public function getXdr(): string
    {
        return $this->xdr;
    }

    /**
     * @param string $xdr
     */
    public function setXdr(string $xdr): void
    {
        $this->xdr = $xdr;
    }

}