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
 * Represents a create account operation.
 *
 * Creates and funds a new account with the specified starting balance of XLM.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a>
 * @since 1.0.0
 */
class CreateAccountOperation extends AbstractOperation
{
    /**
     * @var string The account ID of the account being created and funded
     */
    private string $destination;

    /**
     * @var string The amount of XLM to send to the newly created account (as a decimal string)
     */
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
     * Gets the destination account ID.
     *
     * @return string The destination account ID
     */
    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * Gets the starting balance amount.
     *
     * @return string The starting balance as a decimal string
     */
    public function getStartingBalance(): string
    {
        return $this->startingBalance;
    }

    /**
     * Creates a CreateAccountOperation from its XDR representation.
     *
     * @param XdrCreateAccountOperation $xdrOp The XDR create account operation to convert
     * @return CreateAccountOperation The resulting CreateAccountOperation instance
     */
    public static function fromXdrOperation(XdrCreateAccountOperation $xdrOp): CreateAccountOperation
    {
        $destination = $xdrOp->getDestination()->getAccountId();
        $startingBalance = AbstractOperation::fromXdrAmount($xdrOp->getStartingBalance());
        return new CreateAccountOperation($destination, $startingBalance);
    }

    /**
     * Converts this operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body
     */
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