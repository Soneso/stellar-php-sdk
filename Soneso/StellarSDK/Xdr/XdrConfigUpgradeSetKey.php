<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrConfigUpgradeSetKey
{
    public string $contractID; // hex
    public string $contentHash; // hex

    /**
     * @param string $contractID
     * @param string $contentHash
     */
    public function __construct(string $contractID, string $contentHash)
    {
        $this->contractID = $contractID;
        $this->contentHash = $contentHash;
    }


    public function encode(): string {
        $bytes = XdrEncoder::opaqueFixed(hex2bin($this->contractID),32);
        $bytes .= XdrEncoder::opaqueFixed(hex2bin($this->contentHash),32);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrConfigUpgradeSetKey {
        $contractID = bin2hex($xdr->readOpaqueFixed(32));
        $contentHash = bin2hex($xdr->readOpaqueFixed(32));

        return new XdrConfigUpgradeSetKey($contractID, $contentHash);
    }

    /**
     * @return string
     */
    public function getContractID(): string
    {
        return $this->contractID;
    }

    /**
     * @param string $contractID
     */
    public function setContractID(string $contractID): void
    {
        $this->contractID = $contractID;
    }

    /**
     * @return string
     */
    public function getContentHash(): string
    {
        return $this->contentHash;
    }

    /**
     * @param string $contentHash
     */
    public function setContentHash(string $contentHash): void
    {
        $this->contentHash = $contentHash;
    }

}