<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use InvalidArgumentException;

class FeeBumpTransactionBuilder
{
    private Transaction $inner;
    private ?int $baseFee = null;
    private ?MuxedAccount $feeAccount = null;

    public function __construct(Transaction $inner) {
        $this->inner = $inner;
    }

    public function setFeeAccount(string $feeAccountId) : FeeBumpTransactionBuilder {
        $this->feeAccount = MuxedAccount::fromAccountId($feeAccountId);
        return $this;
    }

    public function setMuxedFeeAccount(MuxedAccount $feeAccount) : FeeBumpTransactionBuilder {
        $this->feeAccount = $feeAccount;
        return $this;
    }

    public function setBaseFee(int $baseFee) : FeeBumpTransactionBuilder {
        if ($baseFee < AbstractTransaction::MIN_BASE_FEE) {
            throw new InvalidArgumentException("base fee can not be smaller than ".AbstractTransaction::MIN_BASE_FEE);
        }
        $innerBaseFee = $this->inner->getFee();
        $nrOfOperations = count($this->inner->getOperations());
        if ($nrOfOperations > 0) {
            $innerBaseFee = round($innerBaseFee / $nrOfOperations);
        }
        if ($baseFee < $innerBaseFee) {
            throw new InvalidArgumentException("base fee cannot be lower than provided inner transaction base fee");
        }
        $maxFee = $baseFee * ($nrOfOperations + 1);
        if ($maxFee < 0) {
            throw new InvalidArgumentException("fee overflows 64 bit int");
        }
        $this->baseFee = $maxFee;
        return $this;
    }

    public function build() : FeeBumpTransaction
    {
        if (!$this->feeAccount) {
            throw new \RuntimeException("fee account has to be set. you must call setFeeAccount().");
        }
        if (!$this->baseFee) {
            throw new \RuntimeException("base fee has to be set. you must call setBaseFee().");
        }
        return new FeeBumpTransaction($this->feeAccount, $this->baseFee, $this->inner);
    }
}