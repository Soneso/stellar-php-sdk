<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use phpseclib3\Math\BigInteger;

interface TransactionBuilderAccount
{
    public function getAccountId() : String;
    public function getSequenceNumber() : BigInteger;
    public function getIncrementedSequenceNumber() : BigInteger;
    public function incrementSequenceNumber() : void;
    public function getMuxedAccount() : MuxedAccount;
}