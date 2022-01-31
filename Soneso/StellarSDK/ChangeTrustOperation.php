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
 * Represents <a href="https://developers.stellar.org/docs/start/list-of-operations/#change-trust" target="_blank">ChangeTrust</a> operation.
 * @see <a href="https://developers.stellar.org/docs/start/list-of-operations/" target="_blank">List of Operations</a>
 */
class ChangeTrustOperation extends AbstractOperation
{

    private Asset $asset;
    private BigInteger $limit;

    /**
     * Creates a new ChangeTrustOperation object.
     * @param Asset $asset The asset of the trustline.
     * @param string|null $limit The limit of the trustline. If null => max. Set to 0 to remove the trust line.
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
     * The asset of the trustline. For example, if a gateway extends a trustline of up to 200 USD to a user, the line is USD.
     * @return Asset
     */
    public function getAsset(): Asset {
        return $this->asset;
    }

    /**
     * The limit of the trustline. For example, if a gateway extends a trustline of up to 200 USD to a user, the limit is 200.
     * @return string
     */
    public function getLimit() : string {
        $res = new StellarAmount($this->limit);
        return $res->getDecimalValueAsString();
    }

    public static function fromXdrOperation(XdrChangeTrustOperation $xdrOp): ChangeTrustOperation {
        $asset = Asset::fromXdr($xdrOp->getLine());
        $amount = AbstractOperation::fromXdrAmount($xdrOp->getLimit());
        return new ChangeTrustOperation($asset, $amount);
    }

    public function toOperationBody(): XdrOperationBody {
        $changeTrustAsset = $this->asset->toXdrChangeTrustAsset();
        $op = new XdrChangeTrustOperation($changeTrustAsset, $this->limit);
        $type = new XdrOperationType(XdrOperationType::CHANGE_TRUST);
        $result = new XdrOperationBody($type);
        $result->setChangeTrustOp($op);
        return $result;
    }
}