<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrPathPaymentStrictSendOperation;

/**
 * Represents <a href="https://developers.stellar.org/docs/start/list-of-operations/#path-payment-strict-send" target="_blank">PathPaymentStrictSend</a> operation.
 * @see <a href="https://developers.stellar.org/docs/start/list-of-operations/" target="_blank">List of Operations</a>
 */
class PathPaymentStrictSendOperation extends AbstractOperation
{
    private Asset $sendAsset;
    private string $sendAmount;
    private MuxedAccount $destination;
    private Asset $destAsset;
    private String $destMin;
    private ?array $path = null; // [Asset]

    /**
     * Constructs a new PathPaymentStrictSendOperation object.
     * @param Asset $sendAsset The asset deducted from the sender's account.
     * @param string $sendAmount The amount of send asset to deduct (excluding fees).
     * @param MuxedAccount $destination Account that receives the payment.
     * @param Asset $destAsset The asset the destination account receives.
     * @param string $destMin The minimum amount of destination asset the destination account receives.
     * @param array|null $path The assets (other than send asset and destination asset) involved in the offers the path takes. For example, if you can only find a path from USD to EUR through XLM and BTC, the path would be USD -&raquo; XLM -&raquo; BTC -&raquo; EUR and the path would contain XLM and BTC.
     */
    public function __construct(Asset $sendAsset, string $sendAmount, MuxedAccount $destination, Asset $destAsset, string $destMin, ?array $path = null) {
        $this->sendAsset = $sendAsset;
        $this->sendAmount = $sendAmount;
        $this->destination = $destination;
        $this->destAsset = $destAsset;
        $this->destMin = $destMin;
        $this->path = $path;
    }

    /**
     * The asset deducted from the sender's account.
     * @return Asset
     */
    public function getSendAsset(): Asset {
        return $this->sendAsset;
    }

    /**
     * The amount of send asset to deduct (excluding fees)
     * @return string
     */
    public function getSendAmount(): string {
        return $this->sendAmount;
    }

    /**
     * Account that receives the payment.
     * @return MuxedAccount
     */
    public function getDestination(): MuxedAccount {
        return $this->destination;
    }

    /**
     * The asset the destination account receives.
     * @return Asset
     */
    public function getDestAsset(): Asset {
        return $this->destAsset;
    }

    /**
     * The minimum amount of destination asset the destination account receives.
     * @return string
     */
    public function getDestMin(): string {
        return $this->destMin;
    }

    /**
     * The assets (other than send asset and destination asset) involved in the offers the path takes. For example, if you can only find a path from USD to EUR through XLM and BTC, the path would be USD -&raquo; XLM -&raquo; BTC -&raquo; EUR and the path would contain XLM and BTC.
     * @return array|null
     */
    public function getPath(): ?array {
        return $this->path;
    }

    public static function fromXdrOperation(XdrPathPaymentStrictSendOperation $xdrOp): PathPaymentStrictSendOperation {
        $sendAmount = AbstractOperation::fromXdrAmount($xdrOp->getSendAmount());
        $sendAsset = Asset::fromXdr($xdrOp->getSendAsset());
        $destination = MuxedAccount::fromXdr($xdrOp->getDestination());
        $destAsset = Asset::fromXdr($xdrOp->getDestAsset());
        $destAmount = AbstractOperation::fromXdrAmount($xdrOp->getDestAmount());
        $path = array();
        foreach ($xdrOp->getPath() as $pathAsset) {
            if ($pathAsset instanceof XdrAsset) {
                array_push($path, Asset::fromXdr($pathAsset));
            }
        }
        return new PathPaymentStrictSendOperation($sendAsset, $sendAmount, $destination, $destAsset, $destAmount, $path);
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