<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;

/**
 * Soroban transaction footprint defining ledger entries that will be accessed
 *
 * A footprint specifies which ledger entries a Soroban transaction will read or write,
 * enabling the network to determine resource requirements before execution. The footprint
 * contains two lists: read-only entries and read-write entries.
 *
 * Footprints are typically computed during transaction simulation and are required for
 * all Soroban transactions to ensure predictable resource usage and fee calculation.
 *
 * @package Soneso\StellarSDK\Soroban
 * @see https://developers.stellar.org Stellar developer docs Stellar Ledgers
 * @since 1.0.0
 */
class Footprint
{
    /**
     * @var XdrLedgerFootprint The XDR representation of the footprint
     */
    public XdrLedgerFootprint $xdrFootprint;

    /**
     * Creates a new Footprint from an XDR ledger footprint
     *
     * @param XdrLedgerFootprint $xdrFootprint The XDR footprint data
     */
    public function __construct(XdrLedgerFootprint $xdrFootprint)
    {
        $this->xdrFootprint = $xdrFootprint;
    }

    /**
     * Encodes the footprint as a base64 XDR string
     *
     * @return string The base64-encoded XDR representation
     */
    public function toBase64Xdr() : string
    {
        return $this->xdrFootprint->toBase64Xdr();
    }

    /**
     * Decodes a Footprint from a base64 XDR string
     *
     * @param string $footprint The base64-encoded XDR footprint data
     * @return Footprint The decoded Footprint object
     */
    public static function fromBase64Xdr(string $footprint) : Footprint {
        return new Footprint(XdrLedgerFootprint::fromBase64Xdr($footprint));
    }

    /**
     * Creates an empty footprint with no ledger entries
     *
     * Empty footprints are used for initial transaction simulation where the actual
     * required footprint is not yet known and will be determined by the simulation.
     *
     * @return Footprint The empty footprint object
     */
    public static function emptyFootprint() : Footprint {
        return new Footprint(new XdrLedgerFootprint([],[]));
    }

    /**
     * Searches the footprint for a contract code ledger entry and returns its key as base64 encoded XDR.
     *
     * @return string|null base64 encoded contract code XDR ledger key or null if not found
     */
    public function getContractCodeLedgerKey() : ?string {
        $key = $this->findFirstKeyOfType(XdrLedgerEntryType::CONTRACT_CODE());
        if ($key != null) {
            return $key->toBase64Xdr();
        }
        return null;
    }

    /**
     * Searches the footprint for a contract data ledger entry and returns its key as base64 encoded XDR.
     *
     * @return string|null base64 encoded contract data XDR ledger key or null if not found
     */
    public function getContractDataLedgerKey() : ?string {
        $key = $this->findFirstKeyOfType(XdrLedgerEntryType::CONTRACT_DATA());
        if ($key != null) {
            return $key->toBase64Xdr();
        }
        return null;
    }

    /**
     * Searches the footprint for a contract code ledger entry and returns its key as XdrLedgerKey.
     *
     * @return XdrLedgerKey|null contract code ledger key or null if not found
     */
    public function getContractCodeXdrLedgerKey() : ?XdrLedgerKey {
        return $this->findFirstKeyOfType(XdrLedgerEntryType::CONTRACT_CODE());
    }

    /**
     * Searches the footprint for a contract data ledger entry and returns its key as XdrLedgerKey.
     *
     * @return XdrLedgerKey|null contract data ledger key or null if not found
     */
    public function getContractDataXdrLedgerKey() : ?XdrLedgerKey {
        return $this->findFirstKeyOfType(XdrLedgerEntryType::CONTRACT_DATA());
    }

    /**
     * Searches for the first ledger key of the given type in the footprint.
     *
     * @param XdrLedgerEntryType $type the ledger entry type to search for
     * @return XdrLedgerKey|null the first matching ledger key or null if not found
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