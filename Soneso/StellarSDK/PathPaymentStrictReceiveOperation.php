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
 *
 * Sends a payment from one account to another through a path, where you specify the exact amount
 * the destination receives. This operation finds the cheapest path for the payment using the orderbooks.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org/docs/start/list-of-operations/" target="_blank">List of Operations</a>
 * @see PathPaymentStrictReceiveOperationBuilder For building this operation
 * @since 1.0.0
 */
class PathPaymentStrictReceiveOperation extends AbstractOperation
{
    /**
     * @var Asset The asset to be deducted from the sender's account.
     */
    private Asset $sendAsset;

    /**
     * @var string The maximum amount of the send asset to deduct (excluding fees).
     */
    private string $sendMax;

    /**
     * @var MuxedAccount The account that receives the payment.
     */
    private MuxedAccount $destination;

    /**
     * @var Asset The asset that the destination account receives.
     */
    private Asset $destAsset;

    /**
     * @var string The exact amount of the destination asset that the destination receives.
     */
    private String $destAmount;

    /**
     * @var array<Asset>|null The intermediate assets in the payment path.
     */
    private ?array $path = null;

    /**
     * Constructs a new PathPaymentStrictReceiveOperation object.
     *
     * @param Asset $sendAsset The asset deducted from the sender's account.
     * @param string $sendMax The maximum amount of send asset to deduct (excluding fees).
     * @param MuxedAccount $destination The account that receives the payment.
     * @param Asset $destAsset The asset the destination account receives.
     * @param string $destAmount The exact amount of destination asset the destination receives.
     * @param array<Asset>|null $path The intermediate assets in the payment path. For example, if the path is USD to EUR through XLM and BTC, the path would be USD -> XLM -> BTC -> EUR and this parameter would contain [XLM, BTC].
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
     * Returns the asset deducted from the sender's account.
     *
     * @return Asset The sending asset.
     */
    public function getSendAsset(): Asset {
        return $this->sendAsset;
    }

    /**
     * Returns the maximum amount of send asset to deduct (excluding fees).
     *
     * @return string The maximum send amount.
     */
    public function getSendMax(): string {
        return $this->sendMax;
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
     * Returns the asset the destination account receives.
     *
     * @return Asset The destination asset.
     */
    public function getDestAsset(): Asset {
        return $this->destAsset;
    }

    /**
     * Returns the exact amount of destination asset the destination receives.
     *
     * @return string The destination amount.
     */
    public function getDestAmount(): string {
        return $this->destAmount;
    }

    /**
     * Returns the intermediate assets in the payment path.
     *
     * For example, if the path is USD to EUR through XLM and BTC, the path would be
     * USD -> XLM -> BTC -> EUR and this returns [XLM, BTC].
     *
     * @return array<Asset>|null The payment path, or null if direct payment.
     */
    public function getPath(): ?array {
        return $this->path;
    }

    /**
     * Creates a PathPaymentStrictReceiveOperation from XDR operation object.
     *
     * @param XdrPathPaymentStrictReceiveOperation $xdrOp The XDR operation object to convert.
     * @return PathPaymentStrictReceiveOperation The created operation instance.
     */
    public static function fromXdrOperation(XdrPathPaymentStrictReceiveOperation $xdrOp): PathPaymentStrictReceiveOperation {
        $sendMax = AbstractOperation::fromXdrAmount($xdrOp->getSendMax());
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
        return new PathPaymentStrictReceiveOperation($sendAsset, $sendMax, $destination, $destAsset, $destAmount, $path);
    }

    /**
     * Converts the operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body.
     */
    public function toOperationBody(): XdrOperationBody {
        $xdrSendAsset = $this->sendAsset->toXdr();
        $xdrSendMax = AbstractOperation::toXdrAmount($this->sendMax);
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
        $op = new XdrPathPaymentStrictReceiveOperation($xdrSendAsset, $xdrSendMax, $xdrDestination, $xdrDestAsset, $xdrDestAmount, $xdrPath);
        $type = new XdrOperationType(XdrOperationType::PATH_PAYMENT_STRICT_RECEIVE);
        $result = new XdrOperationBody($type);
        $result->setPathPaymentStrictReceiveOp($op);
        return $result;
    }
}