<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrAccountMergeOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

/**
 * Represents <a href="https://developers.stellar.org/docs/start/list-of-operations/#account-merge" target="_blank">AccountMerge</a> operation.
 * @see <a href="https://developers.stellar.org/docs/start/list-of-operations/" target="_blank">List of Operations</a>
 */
class AccountMergeOperation extends AbstractOperation
{

    private MuxedAccount $destination;

    /**
     * Creates a new AccountMerge operation
     * @param MuxedAccount $destination The account that receives the remaining XLM balance of the source account.
     */
    public function __construct(MuxedAccount $destination) {
        $this->destination = $destination;
    }

    /**
     * The account that receives the remaining XLM balance of the source account.
     * @return MuxedAccount
     */
    public function getDestination(): MuxedAccount
    {
        return $this->destination;
    }

    public static function fromXdrOperation(XdrAccountMergeOperation $xdrOp): AccountMergeOperation {
        $destination = MuxedAccount::fromXdr($xdrOp->getDestination());
        return new AccountMergeOperation($destination);
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