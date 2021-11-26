<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Asset;

use Soneso\StellarSDK\Responses\Link\LinkResponse;

class AssetLinksResponse
{
    private LinkResponse $toml;

    /**
     * @return LinkResponse
     */
    public function getToml(): LinkResponse {
        return $this->toml;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['toml'])) $this->toml = LinkResponse::fromJson($json['toml']);
    }

    public static function fromJson(array $json) : AssetLinksResponse {
        $result = new AssetLinksResponse();
        $result->loadFromJson($json);
        return $result;
    }
}