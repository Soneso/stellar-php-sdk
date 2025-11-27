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
 * Represents a Path Payment Strict Send operation.
 *
 * Sends a payment from one account to another through a path, where you specify the exact amount
 * to send. This operation finds the best path for the payment that delivers the most destination asset.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a>
 * @see PathPaymentStrictSendOperationBuilder For building this operation
 * @since 1.0.0
 */
class PathPaymentStrictSendOperation extends AbstractOperation
{
    /**
     * Constructs a new PathPaymentStrictSendOperation object.
     *
     * @param Asset $sendAsset The asset to be deducted from the sender's account
     * @param string $sendAmount The exact amount of the send asset to deduct (as a decimal string, excluding fees)
     * @param MuxedAccount $destination The account that receives the payment
     * @param Asset $destAsset The asset that the destination account receives
     * @param string $destMin The minimum amount of the destination asset that must be received (as a decimal string)
     * @param array<Asset>|null $path The intermediate assets in the payment path. For example, if the path is USD to EUR through XLM and BTC, the path would be USD -> XLM -> BTC -> EUR and this parameter would contain [XLM, BTC].
     */
    public function __construct(
        private Asset $sendAsset,
        private string $sendAmount,
        private MuxedAccount $destination,
        private Asset $destAsset,
        private string $destMin,
        private ?array $path = null,
    ) {
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
     * Returns the exact amount of send asset to deduct (excluding fees).
     *
     * @return string The send amount.
     */
    public function getSendAmount(): string {
        return $this->sendAmount;
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
     * Returns the minimum amount of destination asset that must be received.
     *
     * @return string The minimum destination amount.
     */
    public function getDestMin(): string {
        return $this->destMin;
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
     * Creates a PathPaymentStrictSendOperation from XDR operation object.
     *
     * @param XdrPathPaymentStrictSendOperation $xdrOp The XDR operation object to convert.
     * @return PathPaymentStrictSendOperation The created operation instance.
     */
    public static function fromXdrOperation(XdrPathPaymentStrictSendOperation $xdrOp): PathPaymentStrictSendOperation {
        $sendAmount = AbstractOperation::fromXdrAmount($xdrOp->getSendAmount());
        $sendAsset = Asset::fromXdr($xdrOp->getSendAsset());
        $destination = MuxedAccount::fromXdr($xdrOp->getDestination());
        $destAsset = Asset::fromXdr($xdrOp->getDestAsset());
        $destMin = AbstractOperation::fromXdrAmount($xdrOp->getDestMin());
        $path = array();
        foreach ($xdrOp->getPath() as $pathAsset) {
            if ($pathAsset instanceof XdrAsset) {
                array_push($path, Asset::fromXdr($pathAsset));
            }
        }
        return new PathPaymentStrictSendOperation($sendAsset, $sendAmount, $destination, $destAsset, $destMin, $path);
    }

    /**
     * Converts the operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body.
     */
    public function toOperationBody(): XdrOperationBody
    {
        $xdrSendAsset = $this->sendAsset->toXdr();
        $xdrSendAmount = AbstractOperation::toXdrAmount($this->sendAmount);
        $xdrDestination = $this->destination->toXdr();
        $xdrDestAsset = $this->destAsset->toXdr();
        $xdrDestMin = AbstractOperation::toXdrAmount($this->destMin);
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
        $op = new XdrPathPaymentStrictSendOperation($xdrSendAsset, $xdrSendAmount, $xdrDestination, $xdrDestAsset, $xdrDestMin, $xdrPath);
        $type = new XdrOperationType(XdrOperationType::PATH_PAYMENT_STRICT_SEND);
        $result = new XdrOperationBody($type);
        $result->setPathPaymentStrictSendOp($op);
        return $result;
    }
}