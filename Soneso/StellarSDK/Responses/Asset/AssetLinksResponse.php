<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Asset;

use Soneso\StellarSDK\Responses\Link\LinkResponse;

/**
 * Represents HAL links for an asset response
 *
 * Contains hypermedia links related to an asset, primarily the TOML file link.
 * The TOML file contains additional information about the asset issuer and asset details.
 *
 * @package Soneso\StellarSDK\Responses\Asset
 * @see AssetResponse For the parent asset details
 * @see LinkResponse For the link structure
 * @since 1.0.0
 */
class AssetLinksResponse
{
    private LinkResponse $toml;

    /**
     * Gets the link to the asset's TOML file
     *
     * The stellar.toml file contains additional asset and issuer information.
     *
     * @return LinkResponse The TOML file link
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