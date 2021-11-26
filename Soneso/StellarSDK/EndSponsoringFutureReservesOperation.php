<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

class EndSponsoringFutureReservesOperation extends AbstractOperation
{

    public function toOperationBody(): XdrOperationBody
    {
        $type = new XdrOperationType(XdrOperationType::END_SPONSORING_FUTURE_RESERVES);
        return new XdrOperationBody($type);
    }
}