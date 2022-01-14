<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrAllowTrustOperation;
use Soneso\StellarSDK\Xdr\XdrAllowTrustOperationAsset;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrTrustLineFlags;

class AllowTrustOperation extends AbstractOperation
{
    private string $trustor;
    private string $assetCode;
    private bool $authorize;
    private bool $authorizeToMaintainLiabilities;

    public function __construct(string $trustor, string $assetCode, bool $authorize, bool $authorizeToMaintainLiabilities)
    {
        $this->trustor = $trustor;
        $this->assetCode = $assetCode;
        $this->authorize = $authorize;
        $this->authorizeToMaintainLiabilities = $authorizeToMaintainLiabilities;
    }

    /**
     * @return string
     */
    public function getTrustor(): string
    {
        return $this->trustor;
    }

    /**
     * @return string
     */
    public function getAssetCode(): string
    {
        return $this->assetCode;
    }

    /**
     * @return bool
     */
    public function isAuthorize(): bool
    {
        return $this->authorize;
    }

    /**
     * @return bool
     */
    public function isAuthorizeToMaintainLiabilities(): bool
    {
        return $this->authorizeToMaintainLiabilities;
    }

    public static function fromXdrOperation(XdrAllowTrustOperation $xdrOp): AllowTrustOperation {
        $trustor = $xdrOp->getTrustor()->getAccountId();
        $assetCode = $xdrOp->getAsset()->getAssetCode4();
        if (!$assetCode) {
            $assetCode = $xdrOp->getAsset()->getAssetCode12();
        }
        $flag = $xdrOp->getAuthorized();
        $authorize = $flag == XdrTrustLineFlags::AUTHORIZED_FLAG;
        $authorizeToMaintainLiabilities = $flag == XdrTrustLineFlags::AUTHORIZED_TO_MAINTAIN_LIABILITIES_FLAG;
        return new AllowTrustOperation($trustor, $assetCode, $authorize, $authorizeToMaintainLiabilities);
    }

    public function toOperationBody(): XdrOperationBody
    {
        $xdrTrustor = XdrAccountID::fromAccountId($this->trustor);
        $xdrAsset = XdrAllowTrustOperationAsset::fromAlphaNumAssetCode($this->assetCode);
        $authorize = 0;

        if ($this->authorizeToMaintainLiabilities) {
            $authorize = XdrTrustLineFlags::AUTHORIZED_TO_MAINTAIN_LIABILITIES_FLAG;
        } else if ($this->authorize) {
            $authorize = XdrTrustLineFlags::AUTHORIZED_FLAG;
        }
        $op = new XdrAllowTrustOperation($xdrTrustor, $xdrAsset, $authorize);
        $type = new XdrOperationType(XdrOperationType::ALLOW_TRUST);
        $result = new XdrOperationBody($type);
        $result->setAllowTrustOperation($op);
        return $result;
    }
}