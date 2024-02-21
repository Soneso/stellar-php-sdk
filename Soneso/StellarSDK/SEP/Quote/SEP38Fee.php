<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

class SEP38Fee
{
    public string $total;
    public string $asset;
    /**
     * @var array<SEP38FeeDetails>|null $details
     */
    public ?array $details = null;

    /**
     * @param string $total
     * @param string $asset
     * @param array<SEP38FeeDetails>|null $details
     */
    public function __construct(string $total, string $asset, ?array $details = null)
    {
        $this->total = $total;
        $this->asset = $asset;
        $this->details = $details;
    }

    public static function fromJson(array $json) : SEP38Fee
    {
        $total = $json['total'];
        $asset = $json['asset'];

        /**
         * @var array<SEP38FeeDetails> | null $details
         */
        $details = null;
        if (isset($json['details'])) {
            $details = array();
            foreach ($json['details'] as $detail) {
                $details[] = SEP38FeeDetails::fromJson($detail);
            }
        }

        return new SEP38Fee(
            total: $total,
            asset: $asset,
            details: $details,
        );
    }

}