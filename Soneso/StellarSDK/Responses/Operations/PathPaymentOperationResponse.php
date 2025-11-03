<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\PaymentPath\PathAssetsResponse;

/**
 * Represents a path payment operation response from Horizon API
 *
 * This response is returned for path payment operations that send an asset along a payment path,
 * converting through one or more intermediate assets. Contains source and destination amounts,
 * assets, accounts, and the path of assets used for conversion.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see PathPaymentStrictReceiveOperationResponse For strict receive variant
 * @see PathPaymentStrictSendOperationResponse For strict send variant
 * @see https://developers.stellar.org/api/resources/operations/object/path-payment Horizon Path Payment Operation
 * @since 1.0.0
 */
class PathPaymentOperationResponse extends OperationResponse
{
    private string $amount;
    private string $sourceAmount;
    private string $from;
    private ?string $fromMuxed = null;
    private ?string $fromMuxedId = null;
    private string $to;
    private ?string $toMuxed = null;
    private ?string $toMuxedId = null;
    private Asset $asset;
    private Asset $sourceAsset;
    private PathAssetsResponse $path;

    /**
     * Gets the destination amount received
     *
     * @return string The amount received at destination
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Gets the source amount sent
     *
     * @return string The amount sent from source
     */
    public function getSourceAmount(): string
    {
        return $this->sourceAmount;
    }

    /**
     * Gets the sender account address
     *
     * @return string The account ID sending the payment
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * Gets the multiplexed sender account if applicable
     *
     * @return string|null The muxed sender account address or null
     */
    public function getFromMuxed(): ?string
    {
        return $this->fromMuxed;
    }

    /**
     * Gets the multiplexed sender account ID if applicable
     *
     * @return string|null The muxed sender account ID or null
     */
    public function getFromMuxedId(): ?string
    {
        return $this->fromMuxedId;
    }

    /**
     * Gets the recipient account address
     *
     * @return string The account ID receiving the payment
     */
    public function getTo(): string
    {
        return $this->to;
    }

    /**
     * Gets the multiplexed recipient account if applicable
     *
     * @return string|null The muxed recipient account address or null
     */
    public function getToMuxed(): ?string
    {
        return $this->toMuxed;
    }

    /**
     * Gets the multiplexed recipient account ID if applicable
     *
     * @return string|null The muxed recipient account ID or null
     */
    public function getToMuxedId(): ?string
    {
        return $this->toMuxedId;
    }

    /**
     * Gets the destination asset received
     *
     * @return Asset The asset type received at destination
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * Gets the source asset sent
     *
     * @return Asset The asset type sent from source
     */
    public function getSourceAsset(): Asset
    {
        return $this->sourceAsset;
    }

    /**
     * Gets the payment path of intermediate assets
     *
     * @return PathAssetsResponse The ordered list of assets in the payment path
     */
    public function getPath(): PathAssetsResponse
    {
        return $this->path;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['amount'])) $this->amount = $json['amount'];
        if (isset($json['source_amount'])) $this->sourceAmount = $json['source_amount'];
        if (isset($json['asset_type'])) {
            $assetCode = $json['asset_code'] ?? null;
            $assetIssuer = $json['asset_issuer'] ?? null;
            $this->asset = Asset::create($json['asset_type'], $assetCode, $assetIssuer);
        }
        if (isset($json['source_asset_type'])) {
            $assetCode = $json['source_asset_code'] ?? null;
            $assetIssuer = $json['source_asset_issuer'] ?? null;
            $this->sourceAsset = Asset::create($json['source_asset_type'], $assetCode, $assetIssuer);
        }
        if (isset($json['from'])) $this->from = $json['from'];
        if (isset($json['from_muxed'])) $this->fromMuxed = $json['from_muxed'];
        if (isset($json['from_muxed_id'])) $this->fromMuxedId = $json['from_muxed_id'];

        if (isset($json['to'])) $this->to = $json['to'];
        if (isset($json['to_muxed'])) $this->toMuxed = $json['to_muxed'];
        if (isset($json['to_muxed_id'])) $this->toMuxedId = $json['to_muxed_id'];

        if (isset($json['path'])) {
            $this->path = new PathAssetsResponse();
            foreach ($json['path'] as $jsonValue) {
                $value = Asset::fromJson($jsonValue);
                $this->path->add($value);
            }
        }

        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : PathPaymentOperationResponse {
        $result = new PathPaymentOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}