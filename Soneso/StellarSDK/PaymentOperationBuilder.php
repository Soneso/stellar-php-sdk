<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

/**
 * Builds Payment operation.
 * @see PaymentOperation
 */
class PaymentOperationBuilder
{
    private ?MuxedAccount $sourceAccount = null;
    private MuxedAccount $destination;
    private Asset $asset;
    private string $amount;

    /**
     * Creates a new PaymentOperation builder.
     * @param string $destinationAccountId The destination account id.
     * @param Asset $asset The asset to send.
     * @param string $amount The amount to send in lumens.
     */
    public function __construct(string $destinationAccountId, Asset $asset, string $amount) {
        $this->destination = MuxedAccount::fromAccountId($destinationAccountId);
        $this->asset = $asset;
        $this->amount = $amount;
    }

    public static function forMuxedDestinationAccount(MuxedAccount $destination, Asset $asset, string $amount) : PaymentOperationBuilder {
        return  new PaymentOperationBuilder($destination->getAccountId(), $asset, $amount);
    }

    /**
     * Sets the source account for this operation.
     * @param string $accountId The operation's source account.
     * @return PaymentOperationBuilder Builder object so you can chain methods.
     */
    public function setSourceAccount(string $accountId) : PaymentOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     * @param MuxedAccount $sourceAccount The operation's muxed source account.
     * @return PaymentOperationBuilder Builder object so you can chain methods.
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : PaymentOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds an operation
     * @return PaymentOperation
     */
    public function build(): PaymentOperation {
        $result = new PaymentOperation($this->destination, $this->asset, $this->amount);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}