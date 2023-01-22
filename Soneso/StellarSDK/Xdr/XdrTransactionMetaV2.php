<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrTransactionMetaV2
{

    public array $txChangesBefore; // [XdrLedgerEntryChange]
    public array $operations; // [XdrOperationMeta]
    public array $txChangesAfter; // [XdrLedgerEntryChange]

    /**
     * @param array $txChangesBefore [XdrLedgerEntryChange]
     * @param array $operations [XdrOperationMeta]
     * @param array $txChangesAfter [XdrLedgerEntryChange]
     */
    public function __construct(array $txChangesBefore, array $operations, array $txChangesAfter)
    {
        $this->txChangesBefore = $txChangesBefore;
        $this->operations = $operations;
        $this->txChangesAfter = $txChangesAfter;
    }


    public function encode(): string {
        $bytes = XdrEncoder::integer32(count($this->txChangesBefore));
        foreach($this->txChangesBefore as $val) {
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
        $bytes .= XdrEncoder::integer32(count($this->txChangesAfter));
        foreach($this->txChangesAfter as $val) {
            if ($val instanceof XdrLedgerEntryChange) {
                $bytes .= $val->encode();
            }
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrTransactionMetaV2 {
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
        $valCount = $xdr->readInteger32();
        $arr3 = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($arr3, XdrLedgerEntryChange::decode($xdr));
        }

        return new XdrTransactionMetaV2($arr1, $arr2, $arr3);
    }

    /**
     * @return array
     */
    public function getTxChangesBefore(): array
    {
        return $this->txChangesBefore;
    }

    /**
     * @param array $txChangesBefore
     */
    public function setTxChangesBefore(array $txChangesBefore): void
    {
        $this->txChangesBefore = $txChangesBefore;
    }

    /**
     * @return array
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * @param array $operations
     */
    public function setOperations(array $operations): void
    {
        $this->operations = $operations;
    }

    /**
     * @return array
     */
    public function getTxChangesAfter(): array
    {
        return $this->txChangesAfter;
    }

    /**
     * @param array $txChangesAfter
     */
    public function setTxChangesAfter(array $txChangesAfter): void
    {
        $this->txChangesAfter = $txChangesAfter;
    }

}