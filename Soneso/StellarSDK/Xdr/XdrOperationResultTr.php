<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrOperationResultTr extends XdrOperationResultTrBase
{
    public function encode(): string {
        // Sync: MANAGE_BUY_OFFER uses manageOfferResult for backward compat
        if ($this->type->getValue() === XdrOperationType::MANAGE_BUY_OFFER && $this->manageOfferResult !== null) {
            $this->manageBuyOfferResult = $this->manageOfferResult;
        }
        return parent::encode();
    }

    public static function decode(XdrBuffer $xdr): static {
        $result = parent::decode($xdr);
        // Sync: consolidate manageBuyOfferResult → manageOfferResult for backward compat
        if ($result->manageBuyOfferResult !== null) {
            $result->manageOfferResult = $result->manageBuyOfferResult;
        }
        return $result;
    }
}
