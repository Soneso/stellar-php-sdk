<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Asset;

/**
 * Represents total balances by authorization status for an asset
 *
 * Contains aggregated balance amounts for an asset, broken down by authorization status
 * (authorized, authorized to maintain liabilities, or unauthorized). This response is
 * part of AssetResponse and provides insights into total asset distribution across
 * different authorization levels.
 *
 * @package Soneso\StellarSDK\Responses\Asset
 * @see AssetResponse For the parent asset details
 * @see https://developers.stellar.org/api/resources/assets Horizon Assets API
 * @since 1.0.0
 */
class AssetBalancesResponse {
    private string $authorized;
    private string $authorizedToMaintainLiabilities;
    private string $unauthorized;

    /**
     * Gets the total balance held in fully authorized accounts
     *
     * @return string The total authorized balance as a string to preserve precision
     */
    public function getAuthorized(): string
    {
        return $this->authorized;
    }

    /**
     * Gets the total balance held in accounts authorized to maintain liabilities
     *
     * @return string The total balance in accounts authorized to maintain liabilities
     */
    public function getAuthorizedToMaintainLiabilities(): string
    {
        return $this->authorizedToMaintainLiabilities;
    }

    /**
     * Gets the total balance held in unauthorized accounts
     *
     * @return string The total unauthorized balance
     */
    public function getUnauthorized(): string
    {
        return $this->unauthorized;
    }


    protected function loadFromJson(array $json) : void {
        if (isset($json['authorized'])) $this->authorized = $json['authorized'];
        if (isset($json['authorized_to_maintain_liabilities'])) $this->authorizedToMaintainLiabilities = $json['authorized_to_maintain_liabilities'];
        if (isset($json['unauthorized'])) $this->unauthorized = $json['unauthorized'];
    }

    public static function fromJson(array $json) : AssetBalancesResponse {
        $result = new AssetBalancesResponse();
        $result->loadFromJson($json);
        return $result;
    }
}