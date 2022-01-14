<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrBeginSponsoringFutureReservesOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

class BeginSponsoringFutureReservesOperation extends AbstractOperation
{
    private string $sponsoredId;

    public function __construct(string $sponsoredId) {
        $this->sponsoredId = $sponsoredId;
    }

    /**
     * @return string
     */
    public function getSponsoredId(): string
    {
        return $this->sponsoredId;
    }

    public static function fromXdrOperation(XdrBeginSponsoringFutureReservesOperation $xdrOp): BeginSponsoringFutureReservesOperation {
        $sponsoredId = $xdrOp->getSponsoredID()->getAccountId();
        return new BeginSponsoringFutureReservesOperation($sponsoredId);
    }

    public function toOperationBody(): XdrOperationBody
    {
        $xdrSponsoredId = new XdrAccountID($this->sponsoredId);
        $op = new XdrBeginSponsoringFutureReservesOperation($xdrSponsoredId);
        $type = new XdrOperationType(XdrOperationType::BEGIN_SPONSORING_FUTURE_RESERVES);
        $result = new XdrOperationBody($type);
        $result->setBeginSponsoringFutureReservesOperation($op);
        return $result;
    }
}