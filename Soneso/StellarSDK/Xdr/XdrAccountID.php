<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrAccountID extends XdrAccountIDBase
{
    /**
     * Creates a new XdrAccountID from the passed stellar account id.
     * @param string $accountId Base32 encoded public key/account id starting with G
     * @return XdrAccountID
     */
    public static function fromAccountId(string $accountId) : XdrAccountID {
        return new XdrAccountID($accountId);
    }
}
