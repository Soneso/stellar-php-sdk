<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSorobanResources
{
    public XdrLedgerFootprint $footprint; // The ledger footprint of the transaction.

    public int $instructions; // The maximum number of instructions this transaction can use
    public int $readBytes; // The maximum number of bytes this transaction can read from ledger
    public int $writeBytes; // The maximum number of bytes this transaction can write to ledger

    /**
     * @param XdrLedgerFootprint $footprint // The ledger footprint of the transaction.
     * @param int $instructions // The maximum number of instructions this transaction can use
     * @param int $readBytes // The maximum number of bytes this transaction can read from ledger
     * @param int $writeBytes // The maximum number of bytes this transaction can write to ledger
     */
    public function __construct(XdrLedgerFootprint $footprint, int $instructions, int $readBytes, int $writeBytes)
    {
        $this->footprint = $footprint;
        $this->instructions = $instructions;
        $this->readBytes = $readBytes;
        $this->writeBytes = $writeBytes;
    }


    public function encode(): string {
        $bytes = $this->footprint->encode();
        $bytes .= XdrEncoder::unsignedInteger32($this->instructions);
        $bytes .= XdrEncoder::unsignedInteger32($this->readBytes);
        $bytes .= XdrEncoder::unsignedInteger32($this->writeBytes);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrSorobanResources {
        $footprint = XdrLedgerFootprint::decode($xdr);
        $instructions = $xdr->readUnsignedInteger32();
        $readBytes = $xdr->readUnsignedInteger32();
        $writeBytes = $xdr->readUnsignedInteger32();

        return new XdrSorobanResources($footprint, $instructions, $readBytes, $writeBytes);
    }

    /**
     * The ledger footprint of the transaction.
     * @return XdrLedgerFootprint
     */
    public function getFootprint(): XdrLedgerFootprint
    {
        return $this->footprint;
    }

    /**
     * The ledger footprint of the transaction.
     * @param XdrLedgerFootprint $footprint
     */
    public function setFootprint(XdrLedgerFootprint $footprint): void
    {
        $this->footprint = $footprint;
    }

    /**
     * The maximum number of instructions this transaction can use
     * @return int
     */
    public function getInstructions(): int
    {
        return $this->instructions;
    }

    /**
     * The maximum number of instructions this transaction can use
     * @param int $instructions
     */
    public function setInstructions(int $instructions): void
    {
        $this->instructions = $instructions;
    }

    /**
     * The maximum number of bytes this transaction can read from ledger
     * @return int
     */
    public function getReadBytes(): int
    {
        return $this->readBytes;
    }

    /**
     * The maximum number of bytes this transaction can read from ledger
     * @param int $readBytes
     */
    public function setReadBytes(int $readBytes): void
    {
        $this->readBytes = $readBytes;
    }

    /**
     * The maximum number of bytes this transaction can write to ledger
     * @return int
     */
    public function getWriteBytes(): int
    {
        return $this->writeBytes;
    }

    /**
     * The maximum number of bytes this transaction can write to ledger
     * @param int $writeBytes
     */
    public function setWriteBytes(int $writeBytes): void
    {
        $this->writeBytes = $writeBytes;
    }

}