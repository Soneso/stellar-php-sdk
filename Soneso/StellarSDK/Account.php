<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use phpseclib3\Math\BigInteger;

class Account implements TransactionBuilderAccount
{

    protected string $accountId;
    private BigInteger $sequenceNumber;
    private MuxedAccount $muxedAccount;

    public function __construct(string $accountId, BigInteger $sequenceNumber, ?int $muxedAccountMed25519Id = null) {
        $this->accountId = $accountId;
        $this->sequenceNumber = $sequenceNumber;
        $this->muxedAccount = new MuxedAccount($accountId,$muxedAccountMed25519Id);
    }

    public static function fromAccountId(string $accountId, BigInteger $sequenceNumber) : Account {
        $mux = MuxedAccount::fromAccountId($accountId);
        return new Account($mux->getEd25519AccountId(), $sequenceNumber, $mux->getId());
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function getSequenceNumber(): BigInteger
    {
        return $this->sequenceNumber;
    }

    public function getIncrementedSequenceNumber(): BigInteger
    {
        return $this->sequenceNumber->add(new BigInteger(1));
    }

    public function incrementSequenceNumber(): void
    {
        $this->sequenceNumber = $this->getIncrementedSequenceNumber();
    }

    public function getMuxedAccount(): MuxedAccount
    {
        return $this->muxedAccount;
    }
}