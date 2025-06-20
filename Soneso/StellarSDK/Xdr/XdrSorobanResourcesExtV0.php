<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSorobanResourcesExtV0
{

    /**
     * @var array<int> $archivedSorobanEntries [uint32] Vector of indices representing what Soroban
     * entries in the footprint are archived, based on the order of keys provided in the readWrite footprint.
     */
    public array $archivedSorobanEntries;

    /**
     * @param array<int> $archivedSorobanEntries [uint32] Vector of indices representing what Soroban
     *  entries in the footprint are archived, based on the order of keys provided in the readWrite footprint.
     */
    public function __construct(array $archivedSorobanEntries)
    {
        $this->archivedSorobanEntries = $archivedSorobanEntries;
    }


    public function encode(): string {
        $bytes = XdrEncoder::integer32(count($this->archivedSorobanEntries));
        foreach($this->archivedSorobanEntries as $val) {
            $bytes .= XdrEncoder::unsignedInteger32($val);
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSorobanResourcesExtV0 {
        /**
         * @var array<int> $archivedSorobanEntries
         */
        $archivedSorobanEntries = array();
        $valCount = $xdr->readInteger32();
        for ($i = 0; $i < $valCount; $i++) {
            $archivedSorobanEntries[] = $xdr->readUnsignedInteger32();
        }

        return new XdrSorobanResourcesExtV0($archivedSorobanEntries);
    }
}