<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrPathPaymentStrictReceiveOperation;

/**
 * Represents <a href="https://developers.stellar.org/docs/start/list-of-operations/#path-payment-strict-receive" target="_blank">PathPaymentStrictReceive</a> operation.
 * @see <a href="https://developers.stellar.org/docs/start/list-of-operations/" target="_blank">List of Operations</a>
 */
class PathPaymentStrictReceiveOperation extends AbstractOperation
{
    private Asset $sendAsset;
    private string $sendMax;
    private MuxedAccount $destination;
    private Asset $destAsset;
    private String $destAmount;
    private ?array $path = null; // [Asset]

    /**
     * Creates a new PathPaymentStrictReceiveOperation object.
     * @param Asset $sendAsset The asset deducted from the sender's account.
     * @param string $sendMax The maximum amount of send asset to deduct (excluding fees).
     * @param MuxedAccount $destination Account that receives the payment.
     * @param Asset $destAsset The asset the destination account receives.
     * @param string $destAmount The amount of destination asset the destination account receives.
     * @param array|null $path The assets (other than send asset and destination asset) involved in the offers the path takes. For example, if you can only find a path from USD to EUR through XLM and BTC, the path would be USD -&raquo; XLM -&raquo; BTC -&raquo; EUR and the path would contain XLM and BTC.
     */
    public function __construct(Asset $sendAsset, string $sendMax, MuxedAccount $destination, Asset $destAsset, string $destAmount, ?array $path = null) {
        $this->sendAsset = $sendAsset;
        $this->sendMax = $sendMax;
        $this->destination = $destination;
        $this->destAsset = $destAsset;
        $this->destAmount = $destAmount;
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
     * The maximum amount of send asset to deduct (excluding fees).
     * @return string
     */
    public function getSendMax(): string {
        return $this->sendMax;
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
     * The amount of destination asset the destination account receives.
     * @return string
     */
    public function getDestAmount(): string {
        return $this->destAmount;
    }

    /**
     * The assets (other than send asset and destination asset) involved in the offers the path takes. For example, if you can only find a path from USD to EUR through XLM and BTC, the path would be USD -&raquo; XLM -&raquo; BTC -&raquo; EUR and the path would contain XLM and BTC.
     * @return array|null
     */
    public function getPath(): ?array {
        return $this->path;
    }

    public static function fromXdrOperation(XdrPathPaymentStrictReceiveOperation $xdrOp): PathPaymentStrictReceiveOperation {
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
        return new PathPaymentStrictReceiveOperation($sendAsset, $sendAmount, $destination, $destAsset, $destAmount, $path);
    }

    public function toOperationBody(): XdrOperationBody {
        $xdrSendAsset = $this->sendAsset->toXdr();
        $xdrSendAmount = AbstractOperation::toXdrAmount($this->sendMax);
        $xdrDestination = $this->destination->toXdr();
        $xdrDestAsset = $this->destAsset->toXdr();
        $xdrDestAmount = AbstractOperation::toXdrAmount($this->destAmount);
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
        $op = new XdrPathPaymentStrictReceiveOperation($xdrSendAsset, $xdrSendAmount, $xdrDestination, $xdrDestAsset, $xdrDestAmount, $xdrPath);
        $type = new XdrOperationType(XdrOperationType::PATH_PAYMENT_STRICT_RECEIVE);
        $result = new XdrOperationBody($type);
        $result->setPathPaymentStrictReceiveOp($op);
        return $result;
    }
}