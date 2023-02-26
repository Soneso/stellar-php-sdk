<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;

class Footprint
{
    public XdrLedgerFootprint $xdrFootprint;

    /**
     * Constructor.
     * @param XdrLedgerFootprint $xdrFootprint
     */
    public function __construct(XdrLedgerFootprint $xdrFootprint)
    {
        $this->xdrFootprint = $xdrFootprint;
    }

    /**
     * Base64 encoded xdr string of the ledger footprint.
     * @return string
     */
    public function toBase64Xdr() : string
    {
        return $this->xdrFootprint->toBase64Xdr();
    }

    /**
     * Creates a new Footprint object from the given base64 encoded xdr string (if valid).
     * @param string $footprint the base64 encoded xdr footprint to create the Footprint object for.
     * @return Footprint the created Footprint object.
     */
    public static function fromBase64Xdr(string $footprint) : Footprint {
        return new Footprint(XdrLedgerFootprint::fromBase64Xdr($footprint));
    }

    /**
     * Creates an empty footprint. Can be used i.e. for simulating transactions on soroban rpc server.
     * @return Footprint the created footprint.
     */
    public static function emptyFootprint() : Footprint {
        return new Footprint(new XdrLedgerFootprint([],[]));
    }

    /**
     * If found, returns the contract code ledger key as base64 encoded xdr string
     * @return String|null base64 encoded contract code xdr ledger key
     */
    public function getContractCodeLedgerKey() : ?String {
        $key = $this->findFirstKeyOfType(XdrLedgerEntryType::CONTRACT_CODE());
        if ($key != null) {
            return $key->toBase64Xdr();
        }
        return null;
    }

    /**
     * If found, returns the contract data ledger key as base64 encoded xdr string
     * @return String|null base64 encoded contract data xdr ledger key
     */
    public function getContractDataLedgerKey() : ?String {
        $key = $this->findFirstKeyOfType(XdrLedgerEntryType::CONTRACT_DATA());
        if ($key != null) {
            return $key->toBase64Xdr();
        }
        return null;
    }

    /**
     * If found, returns the contract code ledger key as XdrLedgerKey
     * @return XdrLedgerKey|null contract code ledger key
     */
    public function getContractCodeXdrLedgerKey() : ?XdrLedgerKey {
        return $this->findFirstKeyOfType(XdrLedgerEntryType::CONTRACT_CODE());
    }

    /**
     * If found, returns the contract data ledger key as XdrLedgerKey
     * @return XdrLedgerKey|null contract data ledger key
     */
    public function getContractDataXdrLedgerKey() : ?XdrLedgerKey {
        return $this->findFirstKeyOfType(XdrLedgerEntryType::CONTRACT_DATA());
    }

    /**
     * Searches the first ledger key of the given type.
     * @param XdrLedgerEntryType $type type to search for.
     * @return XdrLedgerKey|null the ledger key if found.
     */
    private function findFirstKeyOfType(XdrLedgerEntryType $type) : ?XdrLedgerKey {
        foreach ($this->xdrFootprint->readOnly as $key) {
            if ($key instanceof  XdrLedgerKey && $key->type->value == $type->value) {
                return $key;
            }
        }
        foreach ($this->xdrFootprint->readWrite as $key) {
            if ($key instanceof  XdrLedgerKey && $key->type->value == $type->value) {
                return $key;
            }
        }
        return null;
    }
}