<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrAccountMergeOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

/**
 * Represents an account merge operation.
 *
 * Transfers the native balance (the amount of XLM an account holds) to another account and removes the source account from the ledger.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a>
 * @since 1.0.0
 */
class AccountMergeOperation extends AbstractOperation
{
    /**
     * @var MuxedAccount The account that receives the remaining XLM balance of the source account
     */
    private MuxedAccount $destination;

    /**
     * Creates a new AccountMerge operation
     * @param MuxedAccount $destination The account that receives the remaining XLM balance of the source account.
     */
    public function __construct(MuxedAccount $destination) {
        $this->destination = $destination;
    }

    /**
     * Gets the destination account that receives the remaining XLM balance.
     *
     * @return MuxedAccount The destination account
     */
    public function getDestination(): MuxedAccount
    {
        return $this->destination;
    }

    /**
     * Creates an AccountMergeOperation from its XDR representation.
     *
     * @param XdrAccountMergeOperation $xdrOp The XDR account merge operation to convert
     * @return AccountMergeOperation The resulting AccountMergeOperation instance
     */
    public static function fromXdrOperation(XdrAccountMergeOperation $xdrOp): AccountMergeOperation {
        $destination = MuxedAccount::fromXdr($xdrOp->getDestination());
        return new AccountMergeOperation($destination);
    }

    /**
     * Converts this operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body
     */
    public function toOperationBody(): XdrOperationBody {
        $xdrDestination = $this->destination->toXdr();
        $op = new XdrAccountMergeOperation($xdrDestination);
        $type = new XdrOperationType(XdrOperationType::ACCOUNT_MERGE);
        $result = new XdrOperationBody($type);
        $result->setAccountMergeOp($op);
        return $result;
    }
}