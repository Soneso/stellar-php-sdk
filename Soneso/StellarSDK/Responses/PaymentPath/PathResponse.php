<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\PaymentPath;

use Soneso\StellarSDK\Asset;

class PathResponse {

    private string $sourceAmount;
    private string $sourceAssetType;
    private ?string $sourceAssetCode = null;
    private ?string $sourceAssetIssuer = null;

    private string $destinationAmount;
    private string $destinationAssetType;
    private ?string $destinationAssetCode = null;
    private ?string $destinationAssetIssuer = null;

    private PathAssetsResponse $path;
    private PathLinksResponse $links;

    /**
     * @return string
     */
    public function getSourceAmount(): string
    {
        return $this->sourceAmount;
    }

    /**
     * @return string
     */
    public function getSourceAssetType(): string
    {
        return $this->sourceAssetType;
    }

    /**
     * @return string|null
     */
    public function getSourceAssetCode(): ?string
    {
        return $this->sourceAssetCode;
    }

    /**
     * @return string|null
     */
    public function getSourceAssetIssuer(): ?string
    {
        return $this->sourceAssetIssuer;
    }

    /**
     * @return string
     */
    public function getDestinationAmount(): string
    {
        return $this->destinationAmount;
    }

    /**
     * @return string
     */
    public function getDestinationAssetType(): string
    {
        return $this->destinationAssetType;
    }

    /**
     * @return string|null
     */
    public function getDestinationAssetCode(): ?string
    {
        return $this->destinationAssetCode;
    }

    /**
     * @return string|null
     */
    public function getDestinationAssetIssuer(): ?string
    {
        return $this->destinationAssetIssuer;
    }

    /**
     * @return PathAssetsResponse
     */
    public function getPath(): PathAssetsResponse
    {
        return $this->path;
    }

    /**
     * @return PathLinksResponse
     */
    public function getLinks(): PathLinksResponse
    {
        return $this->links;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['_links'])) $this->links = PathLinksResponse::fromJson($json['_links']);
        if (isset($json['source_amount'])) $this->sourceAmount = $json['source_amount'];
        if (isset($json['source_asset_type'])) $this->sourceAssetType = $json['source_asset_type'];
        if (isset($json['source_asset_code'])) $this->sourceAssetCode = $json['source_asset_code'];
        if (isset($json['source_asset_issuer'])) $this->sourceAssetIssuer = $json['source_asset_issuer'];
        if (isset($json['destination_amount'])) $this->destinationAmount = $json['destination_amount'];
        if (isset($json['destination_asset_type'])) $this->destinationAssetType = $json['destination_asset_type'];
        if (isset($json['destination_asset_code'])) $this->destinationAssetCode = $json['destination_asset_code'];
        if (isset($json['destination_asset_issuer'])) $this->destinationAssetIssuer = $json['destination_asset_issuer'];

        if (isset($json['path'])) {
            $this->path = new PathAssetsResponse();
            foreach ($json['path'] as $jsonValue) {
                $value = Asset::fromJson($jsonValue);
                $this->path->add($value);
            }
        }
    }

    public static function fromJson(array $json) : PathResponse {
        $result = new PathResponse();
        $result->loadFromJson($json);
        return $result;
    }

}