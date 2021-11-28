<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

/**
 * Builds CreateAccount operation.
 * @see CreateAccountOperation
 */
class CreateAccountOperationBuilder
{
    private string $destination;
    private string $startingBalance;
    private ?MuxedAccount $sourceAccount = null;

    /**
     * Creates a new CreateAccount builder.
     * @param string $destination The destination account id
     * @param string $startingBalance The initial balance to start with in lumens.
     */
    public function __construct(string $destination, string $startingBalance) {
        $this->destination = $destination;
        $this->startingBalance = $startingBalance;
    }

    /**
     * Sets the source account for this operation.
     * @param string $accountId The operation's source account.
     * @return CreateAccountOperationBuilder Builder object so you can chain methods.
     */
    public function setSourceAccount(string $accountId) : CreateAccountOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     * @param MuxedAccount $sourceAccount The operation's muxed source account.
     * @return CreateAccountOperationBuilder Builder object so you can chain methods.
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : CreateAccountOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds an operation.
     * @return CreateAccountOperation the build operation.
     */
    public function build(): CreateAccountOperation {
        $result = new CreateAccountOperation($this->destination, $this->startingBalance);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}