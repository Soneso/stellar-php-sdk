<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;


use Soneso\StellarSDK\Responses\Response;

class SEP24FeeResponse extends Response
{
    /// The total fee (in units of the asset involved) that would be charged to deposit/withdraw the specified amount of asset_code.
    public ?float $fee = null;

    protected function loadFromJson(array $json) : void {
        if (isset($json['fee'])) $this->fee = floatval($json['fee']);
    }

    public static function fromJson(array $json) : SEP24FeeResponse
    {
        $result = new SEP24FeeResponse();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * @return float|null
     */
    public function getFee(): ?float
    {
        return $this->fee;
    }

    /**
     * @param float|null $fee
     */
    public function setFee(?float $fee): void
    {
        $this->fee = $fee;
    }

}