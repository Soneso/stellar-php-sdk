<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;


use Soneso\StellarSDK\Responses\Response;

class SEP24FeeResponse extends Response
{
    /**
     * @var float|null $fee The total fee (in units of the asset involved) that would be charged to deposit/withdraw the specified amount of asset_code.
     */
    public ?float $fee = null;

    /**
     * Loads the needed data from a json array.
     * @param array<array-key, mixed> $json the data array to read from.
     * @return void
     */
    protected function loadFromJson(array $json) : void {
        if (isset($json['fee'])) $this->fee = floatval($json['fee']);
    }

    /**
     * Constructs a new instance of SEP24FeeResponse by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP24FeeResponse the object containing the parsed data.
     */
    public static function fromJson(array $json) : SEP24FeeResponse
    {
        $result = new SEP24FeeResponse();
        $result->loadFromJson($json);

        return $result;
    }

    /**
     * @return float|null The total fee (in units of the asset involved) that would be charged to deposit/withdraw the specified amount of asset_code.
     */
    public function getFee(): ?float
    {
        return $this->fee;
    }

    /**
     * @param float|null $fee The total fee (in units of the asset involved) that would be charged to deposit/withdraw the specified amount of asset_code.
     */
    public function setFee(?float $fee): void
    {
        $this->fee = $fee;
    }

}