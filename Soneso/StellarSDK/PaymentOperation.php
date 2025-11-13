<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrPaymentOperation;

/**
 * Represents a Payment operation.
 *
 * Sends a specified amount of an asset to a destination account. This is the most basic operation
 * for transferring assets between accounts on the Stellar network.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a>
 * @see PaymentOperationBuilder For building this operation
 * @since 1.0.0
 */
class PaymentOperation extends AbstractOperation
{
    /**
     * @var MuxedAccount The account that receives the payment.
     */
    private MuxedAccount $destination;

    /**
     * @var Asset The asset to send to the destination account.
     */
    private Asset $asset;

    /**
     * @var string The amount of the asset to send.
     */
    private string $amount;

    /**
     * Constructs a new PaymentOperation object.
     *
     * @param MuxedAccount $destination The account that receives the payment.
     * @param Asset $asset The asset to send to the destination account.
     * @param string $amount The amount of the asset to send.
     */
    public function __construct(MuxedAccount $destination, Asset $asset, string $amount) {
        $this->destination = $destination;
        $this->asset = $asset;
        $this->amount = $amount;
    }

    /**
     * Returns the account that receives the payment.
     *
     * @return MuxedAccount The destination account.
     */
    public function getDestination(): MuxedAccount {
        return $this->destination;
    }

    /**
     * Returns the asset to send to the destination account.
     *
     * @return Asset The payment asset.
     */
    public function getAsset(): Asset {
        return $this->asset;
    }

    /**
     * Returns the amount of the asset to send.
     *
     * @return string The payment amount.
     */
    public function getAmount(): string {
        return $this->amount;
    }

    /**
     * Creates a PaymentOperation from XDR operation object.
     *
     * @param XdrPaymentOperation $xdrOp The XDR operation object to convert.
     * @return PaymentOperation The created operation instance.
     */
    public static function fromXdrOperation(XdrPaymentOperation $xdrOp): PaymentOperation {
        $destination = MuxedAccount::fromXdr($xdrOp->getDestination());
        $amount = AbstractOperation::fromXdrAmount($xdrOp->getAmount());
        $asset = Asset::fromXdr($xdrOp->getAsset());
        return new PaymentOperation($destination, $asset, $amount);
    }

    /**
     * Converts the operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body.
     */
    public function toOperationBody(): XdrOperationBody {
        $xdrDestination = $this->destination->toXdr();
        $xdrAsset = $this->asset->toXdr();
        $xdrAmount = AbstractOperation::toXdrAmount($this->amount);
        $op = new XdrPaymentOperation($xdrDestination, $xdrAsset, $xdrAmount);
        $type = new XdrOperationType(XdrOperationType::PAYMENT);
        $result = new XdrOperationBody($type);
        $result->setPaymentOp($op);
        return $result;
    }
}