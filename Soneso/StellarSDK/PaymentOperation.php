<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrPaymentOperation;

class PaymentOperation extends AbstractOperation
{

    private MuxedAccount $destination;
    private Asset $asset;
    private string $amount;

    public function __construct(MuxedAccount $destination, Asset $asset, string $amount) {
        $this->destination = $destination;
        $this->asset = $asset;
        $this->amount = $amount;
    }

    /**
     * @return MuxedAccount
     */
    public function getDestination(): MuxedAccount
    {
        return $this->destination;
    }

    /**
     * @return Asset
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
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