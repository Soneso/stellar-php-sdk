<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrLedgerKeyContractCode
{
    public string $hash;
    public XdrContractEntryBodyType $bodyType;

    /**
     * @param string $hash
     * @param XdrContractEntryBodyType $bodyType
     */
    public function __construct(string $hash, XdrContractEntryBodyType $bodyType)
    {
        $this->hash = $hash;
        $this->bodyType = $bodyType;
    }


    public function encode(): string {
        $body = XdrEncoder::opaqueFixed($this->hash, 32);
        $body .= $this->bodyType->encode();
        return $body;
    }

    public static function decode(XdrBuffer $xdr) : XdrLedgerKeyContractCode {
        $hash = $xdr->readOpaqueFixed(32);
        $bodyType = XdrContractEntryBodyType::decode($xdr);
        return new XdrLedgerKeyContractCode($hash, $bodyType);
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     */
    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }

    /**
     * @return XdrContractEntryBodyType
     */
    public function getBodyType(): XdrContractEntryBodyType
    {
        return $this->bodyType;
    }

    /**
     * @param XdrContractEntryBodyType $bodyType
     */
    public function setBodyType(XdrContractEntryBodyType $bodyType): void
    {
        $this->bodyType = $bodyType;
    }

}