<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrTransactionMetaV1
{

    /**
     * @var array<XdrLedgerEntryChange>
     */
    public array $ledgerEntryChanges;
    /**
     * @var array<XdrOperationMeta>
     */
    public array $operations;

    /**
     * @param array<XdrLedgerEntryChange> $ledgerEntryChanges
     * @param array<XdrOperationMeta> $operations
     */
    public function __construct(array $ledgerEntryChanges, array $operations)
    {
        $this->ledgerEntryChanges = $ledgerEntryChanges;
        $this->operations = $operations;
    }


    public function encode(): string {
        $bytes = XdrEncoder::integer32(count($this->ledgerEntryChanges));
        foreach($this->ledgerEntryChanges as $val) {
            if ($val instanceof XdrLedgerEntryChange) {
                $bytes .= $val->encode();
            }
        }
        $bytes .= XdrEncoder::integer32(count($this->operations));
        foreach($this->operations as $val) {
            if ($val instanceof XdrOperationMeta) {
                $bytes .= $val->encode();
            }
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrTransactionMetaV1 {
        $valCount = $xdr->readInteger32();
        $arr1 = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($arr1, XdrLedgerEntryChange::decode($xdr));
        }
        $valCount = $xdr->readInteger32();
        $arr2 = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($arr2, XdrOperationMeta::decode($xdr));
        }

        return new XdrTransactionMetaV1($arr1, $arr2);
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

    /**
     * @return array<XdrOperationMeta>
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * @param array<XdrOperationMeta> $operations
     */
    public function setOperations(array $operations): void
    {
        $this->operations = $operations;
    }
}