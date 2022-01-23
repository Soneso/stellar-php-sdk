<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Soneso\StellarSDK\Responses\Response;

class FeeResponse extends Response
{
    private float $fee;

    /**
     * The total fee (in units of the asset involved) that would be charged to deposit/withdraw the specified amount of asset_code.
     * @return float
     */
    public function getFee(): float
    {
        return $this->fee;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['fee'])) $this->fee = $json['fee'];
    }

    public static function fromJson(array $json) : FeeResponse
    {
        $result = new FeeResponse();
        $result->loadFromJson($json);
        return $result;
    }
}