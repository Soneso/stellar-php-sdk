<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\CrossBorderPayments;

/**
 * Response containing supported receiving assets and KYC requirements from SEP-31 info endpoint.
 *
 * This class represents the response from GET /info, listing all assets that the
 * Receiving Anchor supports for cross-border payments, along with their transaction
 * limits, fees, and required SEP-12 KYC types for senders and receivers.
 *
 * @package Soneso\StellarSDK\SEP\CrossBorderPayments
 * @see https://github.com/stellar/stellar-protocol/blob/v3.1.0/ecosystem/sep-0031.md#get-info
 * @see CrossBorderPaymentsService::info()
 * @see SEP31ReceiveAssetInfo
 * @see SEP12TypesInfo
 */
class SEP31InfoResponse
{
    /**
     * @var array<string, SEP31ReceiveAssetInfo> (asset code => info) about the assets that the Receiving Anchor
     * supports receiving from the Sending Anchor.
     */
    public array $receiveAssets;

    /**
     * @param array<string, SEP31ReceiveAssetInfo> $receiveAssets (asset code => info) about the assets that the Receiving Anchor
     *  supports receiving from the Sending Anchor.
     */
    public function __construct(array $receiveAssets)
    {
        $this->receiveAssets = $receiveAssets;
    }

    /**
     * Constructs a new instance of SEP31InfoResponse by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP31InfoResponse the object containing the parsed data.
     */
    public static function fromJson(array $json) : SEP31InfoResponse
    {
        /**
         * @var array<string, SEP31ReceiveAssetInfo> $assets
         */
        $assets = array();
        if (isset($json['receive'])) {
            $keys = array_keys($json['receive']);
            foreach ($keys as $key) {
                $assets[$key] = SEP31ReceiveAssetInfo::fromJson($json['receive'][$key]);
            }
        }

        return new SEP31InfoResponse($assets);
    }
}