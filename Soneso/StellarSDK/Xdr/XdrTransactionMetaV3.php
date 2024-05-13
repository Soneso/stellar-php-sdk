<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrTransactionMetaV3
{

    public XdrExtensionPoint $ext;
    /**
     * @var array<XdrLedgerEntryChange> $txChangesBefore
     */
    public array $txChangesBefore;

    /**
     * @var array<XdrOperationMeta> $operations
     */
    public array $operations;

    /**
     * @var array<XdrLedgerEntryChange> $txChangesAfter
     */
    public array $txChangesAfter;
    public ?XdrSorobanTransactionMeta $sorobanMeta = null;

    /**
     * @param XdrExtensionPoint $ext
     * @param array<XdrLedgerEntryChange> $txChangesBefore
     * @param array<XdrOperationMeta> $operations
     * @param array<XdrLedgerEntryChange> $txChangesAfter
     * @param XdrSorobanTransactionMeta|null $sorobanMeta
     */
    public function __construct(
        XdrExtensionPoint $ext,
        array $txChangesBefore,
        array $operations,
        array $txChangesAfter,
        ?XdrSorobanTransactionMeta $sorobanMeta,
    )
    {
        $this->ext = $ext;
        $this->txChangesBefore = $txChangesBefore;
        $this->operations = $operations;
        $this->txChangesAfter = $txChangesAfter;
        $this->sorobanMeta = $sorobanMeta;
    }


    public function encode(): string {
        $bytes = $this->ext->encode();
        $bytes .= XdrEncoder::integer32(count($this->txChangesBefore));
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

        if ($this->sorobanMeta !== null) {
            $bytes .= XdrEncoder::integer32(1);
            $bytes .= $this->sorobanMeta->encode();
        } else {
            $bytes .= XdrEncoder::integer32(0);
        }

        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrTransactionMetaV3 {
        $ext = XdrExtensionPoint::decode($xdr);
        $valCount = $xdr->readInteger32();
        /**
         * @var array<XdrLedgerEntryChange> $txChangesBefore
         */
        $txChangesBefore = array();
        for ($i = 0; $i < $valCount; $i++) {
            $txChangesBefore[] = XdrLedgerEntryChange::decode($xdr);
        }
        $valCount = $xdr->readInteger32();
        /**
         * @var array<XdrOperationMeta> $operations
         */
        $operations = array();
        for ($i = 0; $i < $valCount; $i++) {
            $operations[] = XdrOperationMeta::decode($xdr);
        }
        $valCount = $xdr->readInteger32();

        /**
         * @var array<XdrLedgerEntryChange> $txChangesAfter
         */
        $txChangesAfter = array();
        for ($i = 0; $i < $valCount; $i++) {
            $txChangesAfter[] = XdrLedgerEntryChange::decode($xdr);
        }

        $sorobanMeta = null;
        if ($xdr->readInteger32() == 1) {
            $sorobanMeta = XdrSorobanTransactionMeta::decode($xdr);
        }

        return new XdrTransactionMetaV3($ext, $txChangesBefore, $operations, $txChangesAfter, $sorobanMeta);
    }

    /**
     * @return XdrExtensionPoint
     */
    public function getExt(): XdrExtensionPoint
    {
        return $this->ext;
    }

    /**
     * @param XdrExtensionPoint $ext
     */
    public function setExt(XdrExtensionPoint $ext): void
    {
        $this->ext = $ext;
    }

    /**
     * @return array<XdrLedgerEntryChange>
     */
    public function getTxChangesBefore(): array
    {
        return $this->txChangesBefore;
    }

    /**
     * @param array<XdrLedgerEntryChange> $txChangesBefore
     */
    public function setTxChangesBefore(array $txChangesBefore): void
    {
        $this->txChangesBefore = $txChangesBefore;
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

    /**
     * @return array<XdrLedgerEntryChange>
     */
    public function getTxChangesAfter(): array
    {
        return $this->txChangesAfter;
    }

    /**
     * @param array<XdrLedgerEntryChange> $txChangesAfter
     */
    public function setTxChangesAfter(array $txChangesAfter): void
    {
        $this->txChangesAfter = $txChangesAfter;
    }

    /**
     * @return XdrSorobanTransactionMeta|null
     */
    public function getSorobanMeta(): ?XdrSorobanTransactionMeta
    {
        return $this->sorobanMeta;
    }

    /**
     * @param XdrSorobanTransactionMeta|null $sorobanMeta
     */
    public function setSorobanMeta(?XdrSorobanTransactionMeta $sorobanMeta): void
    {
        $this->sorobanMeta = $sorobanMeta;
    }

}