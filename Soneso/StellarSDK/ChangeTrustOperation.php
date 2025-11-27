<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Util\StellarAmount;
use Soneso\StellarSDK\Xdr\XdrChangeTrustOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

/**
 * Represents a change trust operation.
 *
 * Creates, updates, or deletes a trust line between the source account and an asset.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a>
 * @since 1.0.0
 */
class ChangeTrustOperation extends AbstractOperation
{
    private Asset $asset;
    private BigInteger $limit;

    /**
     * Creates a new ChangeTrustOperation object.
     *
     * @param Asset $asset The asset for the trust line
     * @param string|null $limit The trust line limit (as a decimal string). If null, defaults to maximum. Set to "0" to remove the trust line.
     */
    public function __construct(Asset $asset, ?string $limit = null) {
        $this->asset = $asset;
        if ($limit != null) {
            $this->limit = AbstractOperation::toXdrAmount($limit);
        } else {
            $this->limit = StellarAmount::maximum()->getStroops();
        }
    }

    /**
     * Gets the asset of the trust line.
     *
     * @return Asset The asset
     */
    public function getAsset(): Asset {
        return $this->asset;
    }

    /**
     * Gets the trust line limit.
     *
     * @return string The limit as a decimal string
     */
    public function getLimit() : string {
        $res = new StellarAmount($this->limit);
        return $res->getDecimalValueAsString();
    }

    /**
     * Creates a ChangeTrustOperation from its XDR representation.
     *
     * @param XdrChangeTrustOperation $xdrOp The XDR change trust operation to convert
     * @return ChangeTrustOperation The resulting ChangeTrustOperation instance
     */
    public static function fromXdrOperation(XdrChangeTrustOperation $xdrOp): ChangeTrustOperation {
        $asset = Asset::fromXdr($xdrOp->getLine());
        $amount = AbstractOperation::fromXdrAmount($xdrOp->getLimit());
        return new ChangeTrustOperation($asset, $amount);
    }

    /**
     * Converts this operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body
     */
    public function toOperationBody(): XdrOperationBody {
        $changeTrustAsset = $this->asset->toXdrChangeTrustAsset();
        $op = new XdrChangeTrustOperation($changeTrustAsset, $this->limit);
        $type = new XdrOperationType(XdrOperationType::CHANGE_TRUST);
        $result = new XdrOperationBody($type);
        $result->setChangeTrustOp($op);
        return $result;
    }
}