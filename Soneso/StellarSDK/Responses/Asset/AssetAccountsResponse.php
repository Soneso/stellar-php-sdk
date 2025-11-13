<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Asset;

/**
 * Represents account counts by authorization status for an asset
 *
 * Contains statistics about the number of accounts holding an asset, broken down by
 * authorization status (authorized, authorized to maintain liabilities, or unauthorized).
 * This response is part of AssetResponse and provides insights into asset distribution
 * and trustline authorization states.
 *
 * @package Soneso\StellarSDK\Responses\Asset
 * @see AssetResponse For the parent asset details
 * @see https://developers.stellar.org Stellar developer docs Horizon Assets API
 * @since 1.0.0
 */
class AssetAccountsResponse
{
    private int $authorized;
    private int $authorizedToMaintainLiabilities;
    private int $unauthorized;

    /**
     * Gets the number of fully authorized accounts holding this asset
     *
     * @return int The count of authorized accounts
     */
    public function getAuthorized(): int
    {
        return $this->authorized;
    }

    /**
     * Gets the number of accounts authorized to maintain liabilities
     *
     * These accounts can maintain existing offers but cannot receive new funds.
     *
     * @return int The count of accounts authorized to maintain liabilities
     */
    public function getAuthorizedToMaintainLiabilities(): int
    {
        return $this->authorizedToMaintainLiabilities;
    }

    /**
     * Gets the number of unauthorized accounts
     *
     * @return int The count of unauthorized accounts
     */
    public function getUnauthorized(): int
    {
        return $this->unauthorized;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['authorized'])) $this->authorized = $json['authorized'];
        if (isset($json['authorized_to_maintain_liabilities'])) $this->authorizedToMaintainLiabilities = $json['authorized_to_maintain_liabilities'];
        if (isset($json['unauthorized'])) $this->unauthorized = $json['unauthorized'];
    }

    public static function fromJson(array $json) : AssetAccountsResponse {
        $result = new AssetAccountsResponse();
        $result->loadFromJson($json);
        return $result;
    }
}