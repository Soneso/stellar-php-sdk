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

/**
 * Represents an allow trust operation.
 *
 * Updates the authorized flag of an existing trust line. This operation is deprecated in favor of SetTrustLineFlags.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a>
 * @see SetTrustLineFlagsOperation For the newer operation
 * @since 1.0.0
 * @deprecated Use SetTrustLineFlagsOperation instead
 */
class AllowTrustOperation extends AbstractOperation
{
    /**
     * @var string The account ID of the trustor (the account that created the trust line)
     */
    private string $trustor;

    /**
     * @var string The asset code being authorized
     */
    private string $assetCode;

    /**
     * @var bool Whether to authorize the trustor to transact the asset
     */
    private bool $authorize;

    /**
     * @var bool Whether to authorize the trustor to maintain liabilities but not receive new assets
     */
    private bool $authorizeToMaintainLiabilities;

    /**
     * Creates a new AllowTrustOperation.
     *
     * @param string $trustor The account ID of the trustor
     * @param string $assetCode The asset code to authorize
     * @param bool $authorize Whether to fully authorize the trust line
     * @param bool $authorizeToMaintainLiabilities Whether to authorize only maintaining liabilities
     */
    public function __construct(string $trustor, string $assetCode, bool $authorize, bool $authorizeToMaintainLiabilities)
    {
        $this->trustor = $trustor;
        $this->assetCode = $assetCode;
        $this->authorize = $authorize;
        $this->authorizeToMaintainLiabilities = $authorizeToMaintainLiabilities;
    }

    /**
     * Gets the trustor account ID.
     *
     * @return string The trustor account ID
     */
    public function getTrustor(): string
    {
        return $this->trustor;
    }

    /**
     * Gets the asset code being authorized.
     *
     * @return string The asset code
     */
    public function getAssetCode(): string
    {
        return $this->assetCode;
    }

    /**
     * Checks if the trust line is fully authorized.
     *
     * @return bool True if fully authorized, false otherwise
     */
    public function isAuthorize(): bool
    {
        return $this->authorize;
    }

    /**
     * Checks if the trust line is authorized to maintain liabilities only.
     *
     * @return bool True if authorized to maintain liabilities, false otherwise
     */
    public function isAuthorizeToMaintainLiabilities(): bool
    {
        return $this->authorizeToMaintainLiabilities;
    }

    /**
     * Creates an AllowTrustOperation from its XDR representation.
     *
     * @param XdrAllowTrustOperation $xdrOp The XDR allow trust operation to convert
     * @return AllowTrustOperation The resulting AllowTrustOperation instance
     */
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

    /**
     * Converts this operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body
     */
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