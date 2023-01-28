<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrLedgerEntryChange
{

    public XdrLedgerEntryChangeType $type;
    public ?XdrLedgerEntry $created = null;
    public ?XdrLedgerEntry $updated = null;
    public ?XdrLedgerKey $removed = null;
    public ?XdrLedgerEntry $state = null;

    /**
     * @param XdrLedgerEntryChangeType $type
     */
    public function __construct(XdrLedgerEntryChangeType $type)
    {
        $this->type = $type;
    }


    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->value) {
            case XdrLedgerEntryChangeType::LEDGER_ENTRY_CREATED:
                $bytes .= $this->created->encode();
                break;
            case XdrLedgerEntryChangeType::LEDGER_ENTRY_UPDATED:
                $bytes .= $this->updated->encode();
                break;
            case XdrLedgerEntryChangeType::LEDGER_ENTRY_REMOVED:
                $bytes .= $this->removed->encode();
                break;
            case XdrLedgerEntryChangeType::LEDGER_ENTRY_STATE:
                $bytes .= $this->state->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrLedgerEntryChange {
        $result = new XdrLedgerEntryChange(XdrLedgerEntryChangeType::decode($xdr));
        switch ($result->type->value) {
            case XdrLedgerEntryChangeType::LEDGER_ENTRY_CREATED:
                $result->created = XdrLedgerEntry::decode($xdr);
                break;
            case XdrLedgerEntryChangeType::LEDGER_ENTRY_UPDATED:
                $result->updated = XdrLedgerEntry::decode($xdr);
                break;
            case XdrLedgerEntryChangeType::LEDGER_ENTRY_REMOVED:
                $result->removed = XdrLedgerKey::decode($xdr);
                break;
            case XdrLedgerEntryChangeType::LEDGER_ENTRY_STATE:
                $result->state = XdrLedgerEntry::decode($xdr);
                break;
        }
        return $result;
    }

    /**
     * @return XdrLedgerEntryChangeType
     */
    public function getType(): XdrLedgerEntryChangeType
    {
        return $this->type;
    }

    /**
     * @param XdrLedgerEntryChangeType $type
     */
    public function setType(XdrLedgerEntryChangeType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return XdrLedgerEntry|null
     */
    public function getCreated(): ?XdrLedgerEntry
    {
        return $this->created;
    }

    /**
     * @param XdrLedgerEntry|null $created
     */
    public function setCreated(?XdrLedgerEntry $created): void
    {
        $this->created = $created;
    }

    /**
     * @return XdrLedgerEntry|null
     */
    public function getUpdated(): ?XdrLedgerEntry
    {
        return $this->updated;
    }

    /**
     * @param XdrLedgerEntry|null $updated
     */
    public function setUpdated(?XdrLedgerEntry $updated): void
    {
        $this->updated = $updated;
    }

    /**
     * @return XdrLedgerKey|null
     */
    public function getRemoved(): ?XdrLedgerKey
    {
        return $this->removed;
    }

    /**
     * @param XdrLedgerKey|null $removed
     */
    public function setRemoved(?XdrLedgerKey $removed): void
    {
        $this->removed = $removed;
    }


    /**
     * @return XdrLedgerEntry|null
     */
    public function getState(): ?XdrLedgerEntry
    {
        return $this->state;
    }

    /**
     * @param XdrLedgerEntry|null $state
     */
    public function setState(?XdrLedgerEntry $state): void
    {
        $this->state = $state;
    }
}