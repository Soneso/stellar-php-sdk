<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Asset;

/**
 * Represents an account credited effect from the Stellar network
 *
 * This effect occurs when an account receives a payment or asset transfer.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse Base effect class
 * @see https://developers.stellar.org Stellar developer docs Horizon Effects API
 * @since 1.0.0
 */
class AccountCreditedEffectResponse extends EffectResponse
{
    private string $amount;
    private Asset $asset;

    /**
     * Gets the amount credited to the account
     *
     * @return string The amount as a string to preserve precision
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Gets the asset that was credited
     *
     * @return Asset The asset credited to the account
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['amount'])) $this->amount = $json['amount'];
        if (isset($json['asset_type'])) {
            $assetCode = $json['asset_code'] ?? null;
            $assetIssuer = $json['asset_issuer'] ?? null;
            $this->asset = Asset::create($json['asset_type'], $assetCode, $assetIssuer);
        }
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : AccountCreditedEffectResponse {
        $result = new AccountCreditedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}