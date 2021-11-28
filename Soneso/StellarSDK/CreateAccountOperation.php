<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrCreateAccountOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

/**
 * Represents <a href="https://developers.stellar.org/docs/start/list-of-operations/#create-account" target="_blank">CreateAccount</a> operation.
 * @see <a href="https://developers.stellar.org/docs/start/list-of-operations/" target="_blank">List of Operations</a>
 */
class CreateAccountOperation extends AbstractOperation
{
    private string $destination;
    private string $startingBalance;

    /**
     * Creates a new CreateAccountOperation object.
     * @param string $destination Account that is created and funded.
     * @param string $startingBalance Amount of XLM to send to the newly created account.
     */
    public function __construct(string $destination, string $startingBalance) {
        $this->startingBalance = $startingBalance;
        $this->destination = $destination;
    }

    /**
     * Account that is created and funded.
     * @return string
     */
    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * Amount of XLM to send to the newly created account.
     * @return string
     */
    public function getStartingBalance(): string
    {
        return $this->startingBalance;
    }

    public static function fromXdrOperation(XdrCreateAccountOperation $xdrOp): CreateAccountOperation
    {
        $destination = $xdrOp->getDestination()->getAccountId();
        $startingBalance = AbstractOperation::fromXdrAmount($xdrOp->getStartingBalance());
        return new CreateAccountOperation($destination, $startingBalance);
    }

    public function toOperationBody(): XdrOperationBody
    {
        $accountID = XdrAccountID::fromAccountId($this->destination);
        $startingBalance = AbstractOperation::toXdrAmount($this->startingBalance);
        $op = new XdrCreateAccountOperation($accountID, $startingBalance);
        $type = new XdrOperationType(XdrOperationType::CREATE_ACCOUNT);
        $result = new XdrOperationBody($type);
        $result->setCreateAccountOp($op);
        return $result;
    }
}