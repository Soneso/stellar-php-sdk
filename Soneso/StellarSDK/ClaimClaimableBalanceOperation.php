<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrClaimableBalanceID;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceIDType;
use Soneso\StellarSDK\Xdr\XdrClaimClaimableBalanceOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

class ClaimClaimableBalanceOperation extends AbstractOperation
{
    private string $balanceId;

    public function __construct(string $balanceId) {
        $this->balanceId = $balanceId;
    }

    /**
     * @return string
     */
    public function getBalanceId(): string
    {
        return $this->balanceId;
    }

    public static function fromXdrOperation(XdrClaimClaimableBalanceOperation $xdrOp): ClaimClaimableBalanceOperation {
        $balanceId = $xdrOp->getBalanceID()->getHash();
        return new ClaimClaimableBalanceOperation($balanceId);
    }

    public function toOperationBody(): XdrOperationBody
    {
        $type = new XdrClaimableBalanceIDType(XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0);
        $bId = new XdrClaimableBalanceID($type, $this->balanceId);
        $op = new XdrClaimClaimableBalanceOperation($bId);
        $type = new XdrOperationType(XdrOperationType::CLAIM_CLAIMABLE_BALANCE);
        $result = new XdrOperationBody($type);
        $result->setClaimClaimableBalanceOperation($op);
        return $result;
    }
}