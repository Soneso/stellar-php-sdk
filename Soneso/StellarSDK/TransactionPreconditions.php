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

class TransactionPreconditions
{

    private ?LedgerBounds $ledgerBounds = null;
    private ?BigInteger $minSeqNumber = null;
    private int $minSeqAge = 0;
    private int $minSeqLedgerGap = 0;
    private array $extraSigners = []; //[XdrSignerKey]
    private ?TimeBounds $timeBounds = null;

    /**
     * @return LedgerBounds|null
     */
    public function getLedgerBounds(): ?LedgerBounds
    {
        return $this->ledgerBounds;
    }

    /**
     * @param LedgerBounds|null $ledgerBounds
     */
    public function setLedgerBounds(?LedgerBounds $ledgerBounds): void
    {
        $this->ledgerBounds = $ledgerBounds;
    }

    /**
     * @return BigInteger|null
     */
    public function getMinSeqNumber(): ?BigInteger
    {
        return $this->minSeqNumber;
    }

    /**
     * @param BigInteger|null $minSeqNumber
     */
    public function setMinSeqNumber(?BigInteger $minSeqNumber): void
    {
        $this->minSeqNumber = $minSeqNumber;
    }

    /**
     * @return TimeBounds|null
     */
    public function getTimeBounds(): ?TimeBounds
    {
        return $this->timeBounds;
    }

    /**
     * @param TimeBounds|null $timeBounds
     */
    public function setTimeBounds(?TimeBounds $timeBounds): void
    {
        $this->timeBounds = $timeBounds;
    }

    /**
     * @return int
     */
    public function getMinSeqAge(): int
    {
        return $this->minSeqAge;
    }

    /**
     * @param int $minSeqAge
     */
    public function setMinSeqAge(int $minSeqAge): void
    {
        $this->minSeqAge = $minSeqAge;
    }

    /**
     * @return int
     */
    public function getMinSeqLedgerGap(): int
    {
        return $this->minSeqLedgerGap;
    }

    /**
     * @param int $minSeqLedgerGap
     */
    public function setMinSeqLedgerGap(int $minSeqLedgerGap): void
    {
        $this->minSeqLedgerGap = $minSeqLedgerGap;
    }

    /**
     * @return array
     */
    public function getExtraSigners(): array
    {
        return $this->extraSigners;
    }

    /**
     * @param array $extraSigners
     */
    public function setExtraSigners(array $extraSigners): void
    {
        $this->extraSigners = $extraSigners;
    }


    public function hasV2(): bool {
        return $this->ledgerBounds != null ||
            $this->minSeqNumber != null ||
            $this->minSeqAge > 0 ||
            $this->minSeqLedgerGap > 0 ||
            count($this->extraSigners) > 0;
    }

    public function toXdr() : XdrPreconditions {

        if ($this->hasV2()) {
            $precond = new XdrPreconditions(new XdrPreconditionType(XdrPreconditionType::V2));
            $precondV2 = new XdrPreconditionsV2();
            if ($this->timeBounds != null) {
                $precondV2->setTimeBounds($this->timeBounds->toXdr());
            }
            if ($this->ledgerBounds != null) {
                $precondV2->setLedgerBounds($this->ledgerBounds->toXdr());
            }
            if ($this->minSeqNumber != null) {
                $precondV2->setMinSeqNum(new XdrSequenceNumber($this->minSeqNumber));
            }
            $precondV2->setMinSeqAge($this->minSeqAge);
            $precondV2->setMinSeqLedgerGap($this->minSeqLedgerGap);
            $precondV2->setExtraSigners($this->extraSigners);
            $precond->setV2($precondV2);
            return $precond;
        } else if ($this->timeBounds != null) {
            $precond = new XdrPreconditions(new XdrPreconditionType(XdrPreconditionType::TIME));
            $precond->setTimeBounds($this->timeBounds->toXdr());
            return $precond;
        } else {
            return new XdrPreconditions(new XdrPreconditionType(XdrPreconditionType::NONE));
        }
    }

    public static function fromXdr(XdrPreconditions $xdr) : TransactionPreconditions
    {
        $cond = new TransactionPreconditions();
        if ($xdr->getType()->getValue() == XdrPreconditionType::V2) {
            $xdrV2 = $xdr->getV2();
            if ($xdrV2 != null) {
                if ($xdrV2->getTimeBounds() != null) {
                    $cond->setTimeBounds(TimeBounds::fromXdr($xdrV2->getTimeBounds()));
                }
                if ($xdrV2->getLedgerBounds() != null) {
                    $cond->setLedgerBounds(LedgerBounds::fromXdr($xdrV2->getLedgerBounds()));
                }
                if ($xdrV2->getMinSeqNum() != null) {
                    $cond->setMinSeqNumber($xdrV2->getMinSeqNum()->getValue());
                }
                $cond->setMinSeqAge($xdrV2->getMinSeqAge());
                $cond->setMinSeqLedgerGap($xdrV2->getMinSeqLedgerGap());
                $cond->setExtraSigners($xdrV2->getExtraSigners());
            }
        } else if ($xdr->getType()->getValue() == XdrPreconditionType::TIME) {
            if ($xdr->getTimeBounds() != null) {
                $cond->setTimeBounds(TimeBounds::fromXdr($xdr->getTimeBounds()));
            }
        }
        return $cond;
    }

}