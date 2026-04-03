<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrAccountMergeOperation
{
    private XdrMuxedAccount $destination;

    public function __construct(XdrMuxedAccount $destination) {
        $this->destination = $destination;
    }

    /**
     * @return XdrMuxedAccount
     */
    public function getDestination(): XdrMuxedAccount
    {
        return $this->destination;
    }

    public function encode() : string {
        return $this->destination->encode();
    }

    public static function decode(XdrBuffer $xdr) : XdrAccountMergeOperation {
        $destination = XdrMuxedAccount::decode($xdr);
        return new XdrAccountMergeOperation($destination);
    }

    /**
     * Emit the destination address as a TxRep value at the given key.
     *
     * ACCOUNT_MERGE in SEP-0011 serialises as a bare destination key
     * (e.g. `tx.operations[0].body.destination: G...`).  The caller in
     * XdrOperationBody already appends `.destination` to the prefix before
     * passing it here, so this method writes the value directly at $prefix.
     *
     * @param string               $prefix Full TxRep key including `.destination`.
     * @param array<string,string> $lines  Output map.
     */
    public function toTxRep(string $prefix, array &$lines): void {
        $lines[$prefix] = TxRepHelper::formatMuxedAccount($this->destination);
    }

    /**
     * Reconstruct from a TxRep map.
     *
     * The caller passes the full key (e.g. `tx.operations[0].body.destination`)
     * as $prefix; we read the value directly at that key.
     *
     * @param array<string,string> $map    Parsed TxRep key/value map.
     * @param string               $prefix Full TxRep key including `.destination`.
     * @return XdrAccountMergeOperation
     */
    public static function fromTxRep(array $map, string $prefix): XdrAccountMergeOperation {
        $destination = TxRepHelper::parseMuxedAccount(TxRepHelper::getValue($map, $prefix) ?? '');
        return new XdrAccountMergeOperation($destination);
    }
}