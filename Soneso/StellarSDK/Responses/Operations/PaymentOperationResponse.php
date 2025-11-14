<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Asset;

/**
 * Represents a payment operation response from Horizon API
 *
 * This response is returned when a payment operation sends an amount of a specific asset
 * from one account to another. Contains the payment amount, asset details, and the source
 * and destination accounts including optional multiplexed account information.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org Stellar developer docs Horizon Payment Operation
 * @since 1.0.0
 */
class PaymentOperationResponse extends OperationResponse
{

    private string $amount;
    private Asset $asset;
    private string $from;
    private ?string $fromMuxed = null;
    private ?string $fromMuxedId = null;
    private string $to;
    private ?string $toMuxed = null;
    private ?string $toMuxedId = null;

    /**
     * Gets the amount of the asset being sent
     *
     * @return string The payment amount as a string to preserve precision
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Gets the asset being sent in this payment
     *
     * @return Asset The asset details (type, code, issuer)
     */
    public function getAsset(): Asset
    {
        return $this->asset;
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
     * @return string|null The muxed account address or null
     */
    public function getFromMuxed(): ?string
    {
        return $this->fromMuxed;
    }

    /**
     * Gets the multiplexed sender account ID if applicable
     *
     * @return string|null The muxed account ID or null
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
     * @return string|null The muxed account address or null
     */
    public function getToMuxed(): ?string
    {
        return $this->toMuxed;
    }

    /**
     * Gets the multiplexed recipient account ID if applicable
     *
     * @return string|null The muxed account ID or null
     */
    public function getToMuxedId(): ?string
    {
        return $this->toMuxedId;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['amount'])) $this->amount = $json['amount'];
        if (isset($json['asset_type'])) {
            $assetCode = $json['asset_code'] ?? null;
            $assetIssuer = $json['asset_issuer'] ?? null;
            $this->asset = Asset::create($json['asset_type'], $assetCode, $assetIssuer);
        }
        if (isset($json['from'])) $this->from = $json['from'];
        if (isset($json['from_muxed'])) $this->fromMuxed = $json['from_muxed'];
        if (isset($json['from_muxed_id'])) $this->fromMuxedId = $json['from_muxed_id'];

        if (isset($json['to'])) $this->to = $json['to'];
        if (isset($json['to_muxed'])) $this->toMuxed = $json['to_muxed'];
        if (isset($json['to_muxed_id'])) $this->toMuxedId = $json['to_muxed_id'];

        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : PaymentOperationResponse {
        $result = new PaymentOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}