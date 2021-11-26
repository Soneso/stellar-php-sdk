<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\PaymentPath\PathAssetsResponse;

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
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

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
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * @return string|null
     */
    public function getFromMuxed(): ?string
    {
        return $this->fromMuxed;
    }

    /**
     * @return string|null
     */
    public function getFromMuxedId(): ?string
    {
        return $this->fromMuxedId;
    }

    /**
     * @return string
     */
    public function getTo(): string
    {
        return $this->to;
    }

    /**
     * @return string|null
     */
    public function getToMuxed(): ?string
    {
        return $this->toMuxed;
    }

    /**
     * @return string|null
     */
    public function getToMuxedId(): ?string
    {
        return $this->toMuxedId;
    }

    /**
     * @return Asset
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * @return Asset
     */
    public function getSourceAsset(): Asset
    {
        return $this->sourceAsset;
    }

    /**
     * @return PathAssetsResponse
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