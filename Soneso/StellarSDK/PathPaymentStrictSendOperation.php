<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrPathPaymentStrictSendOperation;

class PathPaymentStrictSendOperation extends AbstractOperation
{
    private Asset $sendAsset;
    private string $sendAmount;
    private MuxedAccount $destination;
    private Asset $destAsset;
    private String $destMin;
    private ?array $path = null; // [Asset]


    public function __construct(Asset $sendAsset, string $sendAmount, MuxedAccount $destination, Asset $destAsset, string $destMin, ?array $path = null) {
        $this->sendAsset = $sendAsset;
        $this->sendAmount = $sendAmount;
        $this->destination = $destination;
        $this->destAsset = $destAsset;
        $this->destMin = $destMin;
        $this->path = $path;
    }

    /**
     * @return Asset
     */
    public function getSendAsset(): Asset
    {
        return $this->sendAsset;
    }

    /**
     * @return string
     */
    public function getSendAmount(): string
    {
        return $this->sendAmount;
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
    public function getDestAsset(): Asset
    {
        return $this->destAsset;
    }

    /**
     * @return string
     */
    public function getDestMin(): string
    {
        return $this->destMin;
    }

    /**
     * @return array|null
     */
    public function getPath(): ?array
    {
        return $this->path;
    }

    public function toOperationBody(): XdrOperationBody
    {
        $xdrSendAsset = $this->sendAsset->toXdr();
        $xdrSendAmount = AbstractOperation::toXdrAmount($this->sendAmount);
        $xdrDestination = $this->destination->toXdr();
        $xdrDestAsset = $this->destAsset->toXdr();
        $xdrDestAmount = AbstractOperation::toXdrAmount($this->destMin);
        $xdrPath = array();
        if ($this->path) {
            $count = count($this->path);
            for ($i = 0; $i<$count; $i++) {
                $asset = $this->path[$i];
                if ($asset instanceof Asset) {
                    array_push($xdrPath, $asset->toXdr());
                }
            }
        }
        $op = new XdrPathPaymentStrictSendOperation($xdrSendAsset, $xdrSendAmount, $xdrDestination, $xdrDestAsset, $xdrDestAmount, $xdrPath);
        $type = new XdrOperationType(XdrOperationType::PATH_PAYMENT_STRICT_SEND);
        $result = new XdrOperationBody($type);
        $result->setPathPaymentStrictSendOp($op);
        return $result;
    }
}