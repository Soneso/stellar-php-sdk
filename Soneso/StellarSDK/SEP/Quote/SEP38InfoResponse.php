<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

class SEP38InfoResponse
{
    /**
     * @var array<SEP38Asset>
     */
    public array $assets;

    /**
     * @param array<SEP38Asset> $assets
     */
    public function __construct(array $assets)
    {
        $this->assets = $assets;
    }

    public static function fromJson(array $json) : SEP38InfoResponse
    {
        /**
         * @var array<SEP38Asset> $assets
         */
        $assets = array();
        if (isset($json['assets'])) {
            foreach ($json['assets'] as $asset) {
                $assets[] = SEP38Asset::fromJson($asset);
            }
        }

        return new SEP38InfoResponse($assets);
    }
}