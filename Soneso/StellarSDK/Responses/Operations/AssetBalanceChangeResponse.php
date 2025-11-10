<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

/**
 * Represents an asset balance change from a Soroban contract invocation
 *
 * Tracks a single asset balance change that occurred during smart contract execution.
 * This includes the asset details, the type of change (transfer, mint, burn, clawback),
 * the accounts involved, and the amount transferred. Used to monitor asset flows
 * within Soroban contract operations.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 */
class AssetBalanceChangeResponse
{
    public string $assetType;
    public ?string $assetCode = null;
    public ?string $assetIssuer = null;
    public string $type;
    public ?string $from = null;
    public string $to;
    public string $amount;
    public ?string $destinationMuxedId = null; // a uint64

    protected function loadFromJson(array $json) : void {
        $this->assetType = $json['asset_type'];

        if (isset($json['asset_code'])) {
            $this->assetCode = $json['asset_code'];
        }
        if (isset($json['asset_issuer'])) {
            $this->assetIssuer = $json['asset_issuer'];
        }
        $this->type = $json['type'];
        if (isset($json['from'])) {
            $this->from = $json['from'];
        }
        $this->to = $json['to'];
        $this->amount = $json['amount'];
        if (isset($json['destination_muxed_id'])) {
            $this->destinationMuxedId = $json['destination_muxed_id'];
        }
    }

    public static function fromJson(array $json) : AssetBalanceChangeResponse {
        $result = new AssetBalanceChangeResponse();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * Gets the asset type
     *
     * @return string The asset type (native, credit_alphanum4, or credit_alphanum12)
     */
    public function getAssetType(): string
    {
        return $this->assetType;
    }

    /**
     * Sets the asset type
     *
     * @param string $assetType The asset type
     * @return void
     */
    public function setAssetType(string $assetType): void
    {
        $this->assetType = $assetType;
    }

    /**
     * Gets the asset code
     *
     * @return string|null The asset code or null for native assets
     */
    public function getAssetCode(): ?string
    {
        return $this->assetCode;
    }

    /**
     * Sets the asset code
     *
     * @param string|null $assetCode The asset code
     * @return void
     */
    public function setAssetCode(?string $assetCode): void
    {
        $this->assetCode = $assetCode;
    }

    /**
     * Gets the asset issuer
     *
     * @return string|null The asset issuer account ID or null for native assets
     */
    public function getAssetIssuer(): ?string
    {
        return $this->assetIssuer;
    }

    /**
     * Sets the asset issuer
     *
     * @param string|null $assetIssuer The asset issuer account ID
     * @return void
     */
    public function setAssetIssuer(?string $assetIssuer): void
    {
        $this->assetIssuer = $assetIssuer;
    }

    /**
     * Gets the type of balance change
     *
     * @return string Change type (transfer, mint, burn, or clawback)
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Sets the type of balance change
     *
     * @param string $type Change type
     * @return void
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Gets the source account of the transfer
     *
     * @return string|null The source account ID or null for mint operations
     */
    public function getFrom(): ?string
    {
        return $this->from;
    }

    /**
     * Sets the source account of the transfer
     *
     * @param string|null $from The source account ID
     * @return void
     */
    public function setFrom(?string $from): void
    {
        $this->from = $from;
    }

    /**
     * Gets the destination account of the transfer
     *
     * @return string The destination account ID
     */
    public function getTo(): string
    {
        return $this->to;
    }

    /**
     * Sets the destination account of the transfer
     *
     * @param string $to The destination account ID
     * @return void
     */
    public function setTo(string $to): void
    {
        $this->to = $to;
    }

    /**
     * Gets the amount of the balance change
     *
     * @return string The amount as a string to preserve precision
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Sets the amount of the balance change
     *
     * @param string $amount The amount
     * @return void
     */
    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * Gets the multiplexed destination account ID
     *
     * @return string|null The muxed destination account ID as uint64 string or null
     */
    public function getDestinationMuxedId(): ?string
    {
        return $this->destinationMuxedId;
    }

    /**
     * Sets the multiplexed destination account ID
     *
     * @param string|null $destinationMuxedId The muxed destination account ID
     * @return void
     */
    public function setDestinationMuxedId(?string $destinationMuxedId): void
    {
        $this->destinationMuxedId = $destinationMuxedId;
    }

}