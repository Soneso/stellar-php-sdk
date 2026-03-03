<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrMuxedAccount extends XdrMuxedAccountBase
{
    /**
     * Constructor. Provide $ed25519 or $med25519.
     * @param string|null $ed25519
     * @param XdrMuxedAccountMed25519|null $med25519
     */
    public function __construct(?string $ed25519 = null, ?XdrMuxedAccountMed25519 $med25519 = null) {

        if (!$ed25519 && !$med25519) {
            throw new \InvalidArgumentException("ed25519 or med25519 must be provided");
        }
        if ($ed25519 && $med25519) {
            throw new \InvalidArgumentException("can not accept both ed25519 and med25519");
        }

        parent::__construct($ed25519, $med25519);

    }
}
