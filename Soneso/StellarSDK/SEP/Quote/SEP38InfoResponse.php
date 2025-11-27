<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

/**
 * Response containing supported assets and delivery methods from SEP-38 info endpoint.
 *
 * This class represents the response from GET /info, listing all Stellar and
 * off-chain assets available for trading, along with their supported delivery
 * methods and country restrictions.
 *
 * @package Soneso\StellarSDK\SEP\Quote
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#get-info
 * @see QuoteService::info()
 * @see SEP38Asset
 */
class SEP38InfoResponse
{
    /**
     * @param array<SEP38Asset> $assets Array of supported assets with delivery methods and country restrictions.
     */
    public function __construct(
        public array $assets,
    ) {
    }

    /**
     * Constructs a new instance of SEP38InfoResponse by using the given data.
     *
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP38InfoResponse the object containing the parsed data.
     */
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