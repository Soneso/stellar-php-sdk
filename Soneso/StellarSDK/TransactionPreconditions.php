<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Xdr\XdrPreconditions;
use Soneso\StellarSDK\Xdr\XdrPreconditionsV2;
use Soneso\StellarSDK\Xdr\XdrPreconditionType;
use Soneso\StellarSDK\Xdr\XdrSequenceNumber;
use Soneso\StellarSDK\Xdr\XdrSignerKey;

/**
 * Transaction preconditions define validation rules for transaction execution.
 *
 * Preconditions allow transactions to specify constraints that must be satisfied
 * before they can be included in a ledger. This includes time bounds, ledger bounds,
 * minimum sequence number requirements, sequence age and ledger gap constraints,
 * and required additional signers.
 *
 * V2 preconditions (Protocol 19+) support all constraint types, while earlier
 * versions only support time bounds. The hasV2() method determines which format
 * to use when converting to XDR.
 *
 * @package Soneso\StellarSDK
 * @see Transaction
 * @see TimeBounds
 * @see LedgerBounds
 * @link https://developers.stellar.org Stellar developer docs
 * @since Protocol 19
 */
class TransactionPreconditions
{
    /**
     * Ledger number constraints for transaction validity.
     *
     * Restricts the range of ledgers within which the transaction can be included.
     *
     * @var LedgerBounds|null
     */
    private ?LedgerBounds $ledgerBounds = null;

    /**
     * Minimum sequence number required for the source account.
     *
     * Transaction will only be valid if the account's sequence number is at least this value.
     *
     * @var BigInteger|null
     */
    private ?BigInteger $minSeqNumber = null;

    /**
     * Minimum age in seconds since the source account's sequence number was set.
     *
     * Transaction will only be valid if this many seconds have passed since the
     * account's sequence number last changed.
     *
     * @var int
     */
    private int $minSeqAge = 0;

    /**
     * Minimum number of ledgers since the source account's sequence number was set.
     *
     * Transaction will only be valid if this many ledgers have closed since the
     * account's sequence number last changed.
     *
     * @var int
     */
    private int $minSeqLedgerGap = 0;

    /**
     * Additional signers required for transaction authorization.
     *
     * Specifies extra signature requirements beyond the normal transaction signers.
     *
     * @var array<XdrSignerKey>
     */
    private array $extraSigners = [];

    /**
     * Time constraints for transaction validity.
     *
     * Restricts the time range within which the transaction can be included.
     *
     * @var TimeBounds|null
     */
    private ?TimeBounds $timeBounds = null;

    /**
     * Gets the ledger bounds constraint.
     *
     * @return LedgerBounds|null The ledger bounds, or null if not set
     */
    public function getLedgerBounds(): ?LedgerBounds
    {
        return $this->ledgerBounds;
    }

    /**
     * Sets the ledger bounds constraint.
     *
     * Restricts the transaction to only be valid within a specific range of ledgers.
     *
     * @param LedgerBounds|null $ledgerBounds The ledger bounds to set, or null to clear
     *
     * @return void
     */
    public function setLedgerBounds(?LedgerBounds $ledgerBounds): void
    {
        $this->ledgerBounds = $ledgerBounds;
    }

    /**
     * Gets the minimum sequence number constraint.
     *
     * @return BigInteger|null The minimum sequence number, or null if not set
     */
    public function getMinSeqNumber(): ?BigInteger
    {
        return $this->minSeqNumber;
    }

    /**
     * Sets the minimum sequence number constraint.
     *
     * Transaction will only be valid if the source account's sequence number
     * is at least this value.
     *
     * @param BigInteger|null $minSeqNumber The minimum sequence number to require, or null to clear
     *
     * @return void
     */
    public function setMinSeqNumber(?BigInteger $minSeqNumber): void
    {
        $this->minSeqNumber = $minSeqNumber;
    }

    /**
     * Gets the time bounds constraint.
     *
     * @return TimeBounds|null The time bounds, or null if not set
     */
    public function getTimeBounds(): ?TimeBounds
    {
        return $this->timeBounds;
    }

    /**
     * Sets the time bounds constraint.
     *
     * Restricts the transaction to only be valid within a specific time range.
     *
     * @param TimeBounds|null $timeBounds The time bounds to set, or null to clear
     *
     * @return void
     */
    public function setTimeBounds(?TimeBounds $timeBounds): void
    {
        $this->timeBounds = $timeBounds;
    }

    /**
     * Gets the minimum sequence age constraint in seconds.
     *
     * @return int The minimum sequence age in seconds (0 if not set)
     */
    public function getMinSeqAge(): int
    {
        return $this->minSeqAge;
    }

    /**
     * Sets the minimum sequence age constraint.
     *
     * Transaction will only be valid if this many seconds have passed since
     * the source account's sequence number last changed.
     *
     * @param int $minSeqAge The minimum age in seconds (0 to disable)
     *
     * @return void
     */
    public function setMinSeqAge(int $minSeqAge): void
    {
        $this->minSeqAge = $minSeqAge;
    }

    /**
     * Gets the minimum sequence ledger gap constraint.
     *
     * @return int The minimum ledger gap (0 if not set)
     */
    public function getMinSeqLedgerGap(): int
    {
        return $this->minSeqLedgerGap;
    }

    /**
     * Sets the minimum sequence ledger gap constraint.
     *
     * Transaction will only be valid if this many ledgers have closed since
     * the source account's sequence number last changed.
     *
     * @param int $minSeqLedgerGap The minimum ledger gap (0 to disable)
     *
     * @return void
     */
    public function setMinSeqLedgerGap(int $minSeqLedgerGap): void
    {
        $this->minSeqLedgerGap = $minSeqLedgerGap;
    }

    /**
     * Gets the list of extra signers required for authorization.
     *
     * @return array<XdrSignerKey> Array of additional required signers
     */
    public function getExtraSigners(): array
    {
        return $this->extraSigners;
    }

    /**
     * Sets the list of extra signers required for authorization.
     *
     * Specifies additional signers that must sign the transaction beyond
     * the normal signature requirements.
     *
     * @param array<XdrSignerKey> $extraSigners Array of required signer keys
     *
     * @return void
     */
    public function setExtraSigners(array $extraSigners): void
    {
        $this->extraSigners = $extraSigners;
    }

    /**
     * Determines if V2 preconditions are needed.
     *
     * Returns true if any V2-only preconditions are set (ledger bounds, minimum
     * sequence number, sequence age, ledger gap, or extra signers). When true,
     * the preconditions must be encoded using the V2 XDR format.
     *
     * @return bool True if V2 preconditions are required, false otherwise
     */
    public function hasV2(): bool {
        return $this->ledgerBounds !== null ||
            $this->minSeqNumber !== null ||
            $this->minSeqAge > 0 ||
            $this->minSeqLedgerGap > 0 ||
            count($this->extraSigners) > 0;
    }

    /**
     * Converts the preconditions to XDR format.
     *
     * Creates an XDR representation of the preconditions. The format used depends
     * on which constraints are set:
     * - V2 format: If any V2-only preconditions are set
     * - TIME format: If only time bounds are set
     * - NONE format: If no preconditions are set
     *
     * @return XdrPreconditions The XDR representation of these preconditions
     */
    public function toXdr() : XdrPreconditions {

        if ($this->hasV2()) {
            $precond = new XdrPreconditions(new XdrPreconditionType(XdrPreconditionType::V2));
            $precondV2 = new XdrPreconditionsV2();
            if ($this->timeBounds !== null) {
                $precondV2->setTimeBounds($this->timeBounds->toXdr());
            }
            if ($this->ledgerBounds !== null) {
                $precondV2->setLedgerBounds($this->ledgerBounds->toXdr());
            }
            if ($this->minSeqNumber !== null) {
                $precondV2->setMinSeqNum(new XdrSequenceNumber($this->minSeqNumber));
            }
            $precondV2->setMinSeqAge($this->minSeqAge);
            $precondV2->setMinSeqLedgerGap($this->minSeqLedgerGap);
            $precondV2->setExtraSigners($this->extraSigners);
            $precond->setV2($precondV2);
            return $precond;
        } else if ($this->timeBounds !== null) {
            $precond = new XdrPreconditions(new XdrPreconditionType(XdrPreconditionType::TIME));
            $precond->setTimeBounds($this->timeBounds->toXdr());
            return $precond;
        } else {
            return new XdrPreconditions(new XdrPreconditionType(XdrPreconditionType::NONE));
        }
    }

    /**
     * Creates a TransactionPreconditions instance from XDR.
     *
     * Decodes an XDR preconditions object and extracts all constraint values
     * based on the precondition type (NONE, TIME, or V2).
     *
     * @param XdrPreconditions $xdr The XDR preconditions to decode
     *
     * @return TransactionPreconditions The decoded preconditions object
     */
    public static function fromXdr(XdrPreconditions $xdr) : TransactionPreconditions
    {
        $cond = new TransactionPreconditions();
        if ($xdr->getType()->getValue() == XdrPreconditionType::V2) {
            $xdrV2 = $xdr->getV2();
            if ($xdrV2 !== null) {
                if ($xdrV2->getTimeBounds() !== null) {
                    $cond->setTimeBounds(TimeBounds::fromXdr($xdrV2->getTimeBounds()));
                }
                if ($xdrV2->getLedgerBounds() !== null) {
                    $cond->setLedgerBounds(LedgerBounds::fromXdr($xdrV2->getLedgerBounds()));
                }
                if ($xdrV2->getMinSeqNum() !== null) {
                    $cond->setMinSeqNumber($xdrV2->getMinSeqNum()->getValue());
                }
                $cond->setMinSeqAge($xdrV2->getMinSeqAge());
                $cond->setMinSeqLedgerGap($xdrV2->getMinSeqLedgerGap());
                $cond->setExtraSigners($xdrV2->getExtraSigners());
            }
        } else if ($xdr->getType()->getValue() == XdrPreconditionType::TIME) {
            if ($xdr->getTimeBounds() !== null) {
                $cond->setTimeBounds(TimeBounds::fromXdr($xdr->getTimeBounds()));
            }
        }
        return $cond;
    }

}