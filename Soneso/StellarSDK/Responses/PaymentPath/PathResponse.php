<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\PaymentPath;

use Soneso\StellarSDK\Asset;

/**
 * Represents a payment path for asset conversion on Stellar network
 *
 * This response contains details about a possible path for converting one asset to another
 * through the Stellar network. Path payments enable sending one asset while the recipient
 * receives a different asset, with automatic conversion through intermediate asset pairs.
 *
 * Key fields:
 * - Source asset and amount required to send
 * - Destination asset and amount that will be received
 * - Ordered sequence of intermediate assets forming the conversion path
 * - Navigation links to related resources
 *
 * The path represents the most efficient route found by Horizon for the desired conversion,
 * considering available offers and liquidity pools. Multiple paths may be returned for the
 * same conversion, allowing clients to choose based on amounts or other criteria.
 *
 * Returned by Horizon endpoints:
 * - GET /paths/strict-receive - Find paths for receiving a specific amount
 * - GET /paths/strict-send - Find paths for sending a specific amount
 *
 * @package Soneso\StellarSDK\Responses\PaymentPath
 * @see PathAssetsResponse For the intermediate assets in the path
 * @see PathLinksResponse For related navigation links
 * @see https://developers.stellar.org/api/aggregations/paths Horizon Path Finding API
 */
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
     * Gets the amount of source asset required for this path
     *
     * @return string The source amount
     */
    public function getSourceAmount(): string
    {
        return $this->sourceAmount;
    }

    /**
     * Gets the source asset type
     *
     * @return string The asset type (native, credit_alphanum4, or credit_alphanum12)
     */
    public function getSourceAssetType(): string
    {
        return $this->sourceAssetType;
    }

    /**
     * Gets the source asset code
     *
     * @return string|null The asset code, or null for native XLM
     */
    public function getSourceAssetCode(): ?string
    {
        return $this->sourceAssetCode;
    }

    /**
     * Gets the source asset issuer account
     *
     * @return string|null The issuer account ID, or null for native XLM
     */
    public function getSourceAssetIssuer(): ?string
    {
        return $this->sourceAssetIssuer;
    }

    /**
     * Gets the amount of destination asset that will be received
     *
     * @return string The destination amount
     */
    public function getDestinationAmount(): string
    {
        return $this->destinationAmount;
    }

    /**
     * Gets the destination asset type
     *
     * @return string The asset type (native, credit_alphanum4, or credit_alphanum12)
     */
    public function getDestinationAssetType(): string
    {
        return $this->destinationAssetType;
    }

    /**
     * Gets the destination asset code
     *
     * @return string|null The asset code, or null for native XLM
     */
    public function getDestinationAssetCode(): ?string
    {
        return $this->destinationAssetCode;
    }

    /**
     * Gets the destination asset issuer account
     *
     * @return string|null The issuer account ID, or null for native XLM
     */
    public function getDestinationAssetIssuer(): ?string
    {
        return $this->destinationAssetIssuer;
    }

    /**
     * Gets the ordered sequence of intermediate assets in the conversion path
     *
     * @return PathAssetsResponse The collection of intermediate assets
     */
    public function getPath(): PathAssetsResponse
    {
        return $this->path;
    }

    /**
     * Gets the navigation links for this payment path
     *
     * @return PathLinksResponse The HAL links
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

    /**
     * Creates a PathResponse from JSON data
     *
     * @param array $json Associative array of parsed JSON response
     * @return PathResponse The populated path response
     */
    public static function fromJson(array $json) : PathResponse {
        $result = new PathResponse();
        $result->loadFromJson($json);
        return $result;
    }

}