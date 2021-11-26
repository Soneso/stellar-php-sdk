<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Asset;

class AssetAccountsResponse
{
    private int $authorized;
    private int $authorizedToMaintainLiabilities;
    private int $unauthorized;

    /**
     * @return int
     */
    public function getAuthorized(): int
    {
        return $this->authorized;
    }

    /**
     * @return int
     */
    public function getAuthorizedToMaintainLiabilities(): int
    {
        return $this->authorizedToMaintainLiabilities;
    }

    /**
     * @return int
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