<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrConfigSettingSCPTiming
{
    public int $ledgerTargetCloseTimeMilliseconds; // uint32
    public int $nominationTimeoutInitialMilliseconds; // uint32
    public int $nominationTimeoutIncrementMilliseconds; // uint32
    public int $ballotTimeoutInitialMilliseconds; // uint32
    public int $ballotTimeoutIncrementMilliseconds; // uint32

    /**
     * @param int $ledgerTargetCloseTimeMilliseconds (uint32)
     * @param int $nominationTimeoutInitialMilliseconds (uint32)
     * @param int $nominationTimeoutIncrementMilliseconds (uint32)
     * @param int $ballotTimeoutInitialMilliseconds (uint32)
     * @param int $ballotTimeoutIncrementMilliseconds (uint32)
     */
    public function __construct(int $ledgerTargetCloseTimeMilliseconds,
                                int $nominationTimeoutInitialMilliseconds,
                                int $nominationTimeoutIncrementMilliseconds,
                                int $ballotTimeoutInitialMilliseconds,
                                int $ballotTimeoutIncrementMilliseconds)
    {
        $this->ledgerTargetCloseTimeMilliseconds = $ledgerTargetCloseTimeMilliseconds;
        $this->nominationTimeoutInitialMilliseconds = $nominationTimeoutInitialMilliseconds;
        $this->nominationTimeoutIncrementMilliseconds = $nominationTimeoutIncrementMilliseconds;
        $this->ballotTimeoutInitialMilliseconds = $ballotTimeoutInitialMilliseconds;
        $this->ballotTimeoutIncrementMilliseconds = $ballotTimeoutIncrementMilliseconds;
    }


    public function encode(): string {
        $body = XdrEncoder::unsignedInteger32($this->ledgerTargetCloseTimeMilliseconds);
        $body .= XdrEncoder::unsignedInteger32($this->nominationTimeoutInitialMilliseconds);
        $body .= XdrEncoder::unsignedInteger32($this->nominationTimeoutIncrementMilliseconds);
        $body .= XdrEncoder::unsignedInteger32($this->ballotTimeoutInitialMilliseconds);
        $body .= XdrEncoder::unsignedInteger32($this->ballotTimeoutIncrementMilliseconds);
        return $body;
    }

    public static function decode(XdrBuffer $xdr) : XdrConfigSettingSCPTiming {
        $ledgerTargetCloseTimeMilliseconds = $xdr->readUnsignedInteger32();
        $nominationTimeoutInitialMilliseconds = $xdr->readUnsignedInteger32();
        $nominationTimeoutIncrementMilliseconds = $xdr->readUnsignedInteger32();
        $ballotTimeoutInitialMilliseconds = $xdr->readUnsignedInteger32();
        $ballotTimeoutIncrementMilliseconds = $xdr->readUnsignedInteger32();

        return new XdrConfigSettingSCPTiming(
            $ledgerTargetCloseTimeMilliseconds,
            $nominationTimeoutInitialMilliseconds,
            $nominationTimeoutIncrementMilliseconds,
            $ballotTimeoutInitialMilliseconds,
            $ballotTimeoutIncrementMilliseconds
        );
    }

    /**
     * @return int
     */
    public function getLedgerTargetCloseTimeMilliseconds(): int
    {
        return $this->ledgerTargetCloseTimeMilliseconds;
    }

    /**
     * @param int $ledgerTargetCloseTimeMilliseconds
     */
    public function setLedgerTargetCloseTimeMilliseconds(int $ledgerTargetCloseTimeMilliseconds): void
    {
        $this->ledgerTargetCloseTimeMilliseconds = $ledgerTargetCloseTimeMilliseconds;
    }

    /**
     * @return int
     */
    public function getNominationTimeoutInitialMilliseconds(): int
    {
        return $this->nominationTimeoutInitialMilliseconds;
    }

    /**
     * @param int $nominationTimeoutInitialMilliseconds
     */
    public function setNominationTimeoutInitialMilliseconds(int $nominationTimeoutInitialMilliseconds): void
    {
        $this->nominationTimeoutInitialMilliseconds = $nominationTimeoutInitialMilliseconds;
    }

    /**
     * @return int
     */
    public function getNominationTimeoutIncrementMilliseconds(): int
    {
        return $this->nominationTimeoutIncrementMilliseconds;
    }

    /**
     * @param int $nominationTimeoutIncrementMilliseconds
     */
    public function setNominationTimeoutIncrementMilliseconds(int $nominationTimeoutIncrementMilliseconds): void
    {
        $this->nominationTimeoutIncrementMilliseconds = $nominationTimeoutIncrementMilliseconds;
    }

    /**
     * @return int
     */
    public function getBallotTimeoutInitialMilliseconds(): int
    {
        return $this->ballotTimeoutInitialMilliseconds;
    }

    /**
     * @param int $ballotTimeoutInitialMilliseconds
     */
    public function setBallotTimeoutInitialMilliseconds(int $ballotTimeoutInitialMilliseconds): void
    {
        $this->ballotTimeoutInitialMilliseconds = $ballotTimeoutInitialMilliseconds;
    }

    /**
     * @return int
     */
    public function getBallotTimeoutIncrementMilliseconds(): int
    {
        return $this->ballotTimeoutIncrementMilliseconds;
    }

    /**
     * @param int $ballotTimeoutIncrementMilliseconds
     */
    public function setBallotTimeoutIncrementMilliseconds(int $ballotTimeoutIncrementMilliseconds): void
    {
        $this->ballotTimeoutIncrementMilliseconds = $ballotTimeoutIncrementMilliseconds;
    }

}