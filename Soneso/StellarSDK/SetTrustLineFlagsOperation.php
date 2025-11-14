<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrSetTrustLineFlagsOperation;

/**
 * Represents a Set Trustline Flags operation.
 *
 * Allows the issuer of an asset to set flags on a trustline. This enables control over whether accounts
 * can hold the asset, whether it can be clawed back, and other authorization settings.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a>
 * @see SetTrustLineFlagsOperationBuilder For building this operation
 * @since 1.0.0
 */
class SetTrustLineFlagsOperation extends AbstractOperation
{
    /**
     * @var string The account ID of the trustline holder.
     */
    private string $trustorId;

    /**
     * @var Asset The asset of the trustline.
     */
    private Asset $asset;

    /**
     * @var int Flags to clear on the trustline.
     */
    private int $clearFlags;

    /**
     * @var int Flags to set on the trustline.
     */
    private int $setFlags;

    /**
     * Constructs a new SetTrustLineFlagsOperation object.
     *
     * @param string $trustorId The account ID of the trustline holder.
     * @param Asset $asset The asset of the trustline.
     * @param int $clearFlags Flags to clear on the trustline.
     * @param int $setFlags Flags to set on the trustline.
     */
    public function __construct(string $trustorId, Asset $asset, int $clearFlags, int $setFlags)
    {
        $this->trustorId = $trustorId;
        $this->asset = $asset;
        $this->clearFlags = $clearFlags;
        $this->setFlags = $setFlags;
    }

    /**
     * Returns the account ID of the trustline holder.
     *
     * @return string The trustor account ID.
     */
    public function getTrustorId(): string
    {
        return $this->trustorId;
    }

    /**
     * Returns the asset of the trustline.
     *
     * @return Asset The trustline asset.
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * Returns flags to clear on the trustline.
     *
     * @return int The flags to clear.
     */
    public function getClearFlags(): int
    {
        return $this->clearFlags;
    }

    /**
     * Returns flags to set on the trustline.
     *
     * @return int The flags to set.
     */
    public function getSetFlags(): int
    {
        return $this->setFlags;
    }

    /**
     * Creates a SetTrustLineFlagsOperation from XDR operation object.
     *
     * @param XdrSetTrustLineFlagsOperation $xdrOp The XDR operation object to convert.
     * @return SetTrustLineFlagsOperation The created operation instance.
     */
    public static function fromXdrOperation(XdrSetTrustLineFlagsOperation $xdrOp): SetTrustLineFlagsOperation {
        $trustorId = $xdrOp->getAccountID()->getAccountId();
        $asset = Asset::fromXdr($xdrOp->getAsset());
        $clearFlags = $xdrOp->getClearFlags();
        $setFlags = $xdrOp->getSetFlags();
        return new SetTrustLineFlagsOperation($trustorId, $asset, $clearFlags, $setFlags);
    }

    /**
     * Converts the operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body.
     */
    public function toOperationBody(): XdrOperationBody
    {
        $trustorId = XdrAccountID::fromAccountId($this->trustorId);
        $asset = $this->asset->toXdr();
        $op = new XdrSetTrustLineFlagsOperation($trustorId, $asset, $this->clearFlags, $this->setFlags);
        $type = new XdrOperationType(XdrOperationType::SET_TRUST_LINE_FLAGS);
        $result = new XdrOperationBody($type);
        $result->setSetTrustLineFlagsOperation($op);
        return $result;
    }
}