<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrLedgerKeyAccount extends XdrLedgerKeyAccountBase
{
    public static function forAccountId(string $accountId): XdrLedgerKeyAccount {
        return new XdrLedgerKeyAccount(XdrAccountID::fromAccountId($accountId));
    }
}
