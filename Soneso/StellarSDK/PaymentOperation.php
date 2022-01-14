<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrPaymentOperation;

/**
 * Represents <a href="https://developers.stellar.org/docs/start/list-of-operations/#payment" target="_blank">Payment</a> operation.
 * @see <a href="https://developers.stellar.org/docs/start/list-of-operations/" target="_blank">List of Operations</a>
 */
class PaymentOperation extends AbstractOperation
{

    private MuxedAccount $destination;
    private Asset $asset;
    private string $amount;

    /**
     * Constructs a new PaymentOperation object
     * @param MuxedAccount $destination Account that receives the payment.
     * @param Asset $asset Asset to send to the destination account.
     * @param string $amount Amount of the asset to send.
     */
    public function __construct(MuxedAccount $destination, Asset $asset, string $amount) {
        $this->destination = $destination;
        $this->asset = $asset;
        $this->amount = $amount;
    }

    /**
     * Account that receives the payment.
     * @return MuxedAccount
     */
    public function getDestination(): MuxedAccount {
        return $this->destination;
    }

    /**
     * Asset to send to the destination account.
     * @return Asset
     */
    public function getAsset(): Asset {
        return $this->asset;
    }

    /**
     * Amount of the asset to send.
     * @return string
     */
    public function getAmount(): string {
        return $this->amount;
    }

    public static function fromXdrOperation(XdrPaymentOperation $xdrOp): PaymentOperation {
        $destination = MuxedAccount::fromXdr($xdrOp->getDestination());
        $amount = AbstractOperation::fromXdrAmount($xdrOp->getAmount());
        $asset = Asset::fromXdr($xdrOp->getAsset());
        return new PaymentOperation($destination, $asset, $amount);
    }

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