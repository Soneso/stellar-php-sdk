<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Asset;

class AssetBalancesResponse {
    private string $authorized;
    private string $authorizedToMaintainLiabilities;
    private string $unauthorized;

    /**
     * @return string
     */
    public function getAuthorized(): string
    {
        return $this->authorized;
    }

    /**
     * @return string
     */
    public function getAuthorizedToMaintainLiabilities(): string
    {
        return $this->authorizedToMaintainLiabilities;
    }

    /**
     * @return string
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