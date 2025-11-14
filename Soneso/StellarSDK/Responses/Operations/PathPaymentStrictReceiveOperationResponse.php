<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

/**
 * Represents a path payment strict receive operation response from Horizon API
 *
 * This operation guarantees the destination receives exactly the specified amount
 * while the source may send up to sourceMax. Extends PathPaymentOperationResponse
 * with the maximum amount willing to be sent from source.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see PathPaymentOperationResponse Base path payment response
 * @see https://developers.stellar.org Stellar developer docs Horizon Path Payment Strict Receive
 * @since 1.0.0
 */
class PathPaymentStrictReceiveOperationResponse extends PathPaymentOperationResponse
{
    private string $sourceMax;

    /**
     * Gets the maximum amount willing to be sent from source
     *
     * @return string The maximum source amount
     */
    public function getSourceMax() : string
    {
        return $this->sourceMax;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['source_max'])) $this->sourceMax = $json['source_max'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : PathPaymentStrictReceiveOperationResponse {
        $result = new PathPaymentStrictReceiveOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}