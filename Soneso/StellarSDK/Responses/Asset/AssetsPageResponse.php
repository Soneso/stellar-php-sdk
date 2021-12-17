<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Asset;

use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Page\PageResponse;

class AssetsPageResponse extends PageResponse
{
    private AssetsResponse $assets;

    /**
     * @return AssetsResponse
     */
    public function getAssets(): AssetsResponse {
        return $this->assets;
    }

    protected function loadFromJson(array $json) : void {
        parent::loadFromJson($json);
        if (isset($json['_embedded']['records'])) {
            $this->assets = new AssetsResponse();
            foreach ($json['_embedded']['records'] as $jsonAsset) {
                $asset = AssetResponse::fromJson($jsonAsset);
                $this->assets->add($asset);
            }
        }
    }

    public static function fromJson(array $json) : AssetsPageResponse {
        $result = new AssetsPageResponse();
        $result->loadFromJson($json);
        return $result;
    }

    public function getNextPage(): AssetsPageResponse | null {
        return $this->executeRequest(RequestType::ASSETS_PAGE, $this->getNextPageUrl());
    }

    public function getPreviousPage(): AssetsPageResponse | null {
        return $this->executeRequest(RequestType::ASSETS_PAGE, $this->getPrevPageUrl());
    }
}