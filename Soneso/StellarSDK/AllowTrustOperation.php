<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use InvalidArgumentException;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrAllowTrustOperation;
use Soneso\StellarSDK\Xdr\XdrAllowTrustOperationAsset;
use Soneso\StellarSDK\Xdr\XdrAssetType;
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
     * Creates a new AllowTrustOperation.
     *
     * @param string $trustor The account ID of the trustor (the account that created the trust line)
     * @param string $assetCode The asset code being authorized
     * @param bool $authorize Whether to authorize the trustor to transact the asset
     * @param bool $authorizeToMaintainLiabilities Whether to authorize the trustor to maintain liabilities but not receive new assets
     */
    public function __construct(
        private string $trustor,
        private string $assetCode,
        private bool $authorize,
        private bool $authorizeToMaintainLiabilities,
    ) {
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
     * The asset type selects the asset code: ASSET_TYPE_CREDIT_ALPHANUM4 carries a code of up to
     * 4 characters, ASSET_TYPE_CREDIT_ALPHANUM12 one of up to 12 characters.
     *
     * @param XdrAllowTrustOperation $xdrOp The XDR allow trust operation to convert
     * @return AllowTrustOperation The resulting AllowTrustOperation instance
     * @throws InvalidArgumentException If the asset type is not an alphanumeric credit asset type or the asset carries no code
     */
    public static function fromXdrOperation(XdrAllowTrustOperation $xdrOp): AllowTrustOperation {
        $trustor = $xdrOp->getTrustor()->getAccountId();
        $asset = $xdrOp->getAsset();
        $assetType = $asset->getType()->getValue();
        $assetCode = match ($assetType) {
            XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4 => $asset->getAssetCode4(),
            XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12 => $asset->getAssetCode12(),
            default => throw new InvalidArgumentException("unknown allow trust asset type: " . $assetType),
        };
        if ($assetCode === null) {
            throw new InvalidArgumentException("allow trust asset of type " . $assetType . " carries no asset code");
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