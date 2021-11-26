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

class ChangeTrustOperation extends AbstractOperation
{

    private Asset $asset;
    private BigInteger $limit;

    public function __construct(Asset $asset, ?string $limit = null) {
        $this->asset = $asset;
        if ($limit != null) {
            $this->limit = AbstractOperation::toXdrAmount($limit);
        } else {
            $this->limit = StellarAmount::maximum()->getStroops();
        }
    }

    /**
     * @return Asset
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * @return BigInteger
     */
    public function getLimit(): BigInteger
    {
        return $this->limit;
    }

    public function toOperationBody(): XdrOperationBody
    {
        $changeTrustAsset = $this->asset->toXdrChangeTrustAsset();
        $op = new XdrChangeTrustOperation($changeTrustAsset,$this->limit);
        $type = new XdrOperationType(XdrOperationType::CHANGE_TRUST);
        $result = new XdrOperationBody($type);
        $result->setChangeTrustOp($op);
        return $result;
    }
}