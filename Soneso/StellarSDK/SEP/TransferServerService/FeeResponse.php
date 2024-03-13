<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Soneso\StellarSDK\Responses\Response;

class FeeResponse extends Response
{
    /**
     * @var float $fee The total fee (in units of the asset involved) that would be charged
     * to deposit/withdraw the specified amount of asset_code.
     */
    public float $fee;

    /**
     * @param float $fee The total fee (in units of the asset involved) that would be charged
     *  to deposit/withdraw the specified amount of asset_code.
     */
    public function __construct(float $fee)
    {
        $this->fee = $fee;
    }

    /**
     * Constructs a new instance of FeeResponse by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return FeeResponse the object containing the parsed data.
     */
    public static function fromJson(array $json) : FeeResponse
    {
        return new FeeResponse($json['fee']);
    }
}