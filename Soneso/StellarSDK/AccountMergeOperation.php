<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrAccountMergeOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

class AccountMergeOperation extends AbstractOperation
{

    private MuxedAccount $destination;

    public function __construct(MuxedAccount $destination) {
        $this->destination = $destination;
    }

    /**
     * @return MuxedAccount
     */
    public function getDestination(): MuxedAccount
    {
        return $this->destination;
    }

    public function toOperationBody(): XdrOperationBody {
        $xdrDestination = $this->destination->toXdr();
        $op = new XdrAccountMergeOperation($xdrDestination);
        $type = new XdrOperationType(XdrOperationType::ACCOUNT_MERGE);
        $result = new XdrOperationBody($type);
        $result->setAccountMergeOp($op);
        return $result;
    }
}