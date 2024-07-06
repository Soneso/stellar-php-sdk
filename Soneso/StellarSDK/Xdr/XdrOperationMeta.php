<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrOperationMeta
{

    /**
     * @var array<XdrLedgerEntryChange>
     */
    public array $ledgerEntryChanges;

    /**
     * @param array<XdrLedgerEntryChange> $ledgerEntryChanges
     */
    public function __construct(array $ledgerEntryChanges)
    {
        $this->ledgerEntryChanges = $ledgerEntryChanges;
    }


    public function encode(): string {
        $bytes = XdrEncoder::integer32(count($this->ledgerEntryChanges));
        foreach($this->ledgerEntryChanges as $val) {
            if ($val instanceof XdrLedgerEntryChange) {
                $bytes .= $val->encode();
            }
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrOperationMeta {
        $valCount = $xdr->readInteger32();
        $arr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($arr, XdrLedgerEntryChange::decode($xdr));
        }

        return new XdrOperationMeta($arr);
    }

    /**
     * @return array<XdrLedgerEntryChange>
     */
    public function getLedgerEntryChanges(): array
    {
        return $this->ledgerEntryChanges;
    }

    /**
     * @param array<XdrLedgerEntryChange> $ledgerEntryChanges
     */
    public function setLedgerEntryChanges(array $ledgerEntryChanges): void
    {
        $this->ledgerEntryChanges = $ledgerEntryChanges;
    }

}