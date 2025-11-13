<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

/**
 * Represents a path payment strict send operation response from Horizon API
 *
 * This operation guarantees exactly the specified amount is sent from source
 * while the destination must receive at least destinationMin. Extends PathPaymentOperationResponse
 * with the minimum amount to be received at destination.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see PathPaymentOperationResponse Base path payment response
 * @see https://developers.stellar.org Stellar developer docs Horizon Path Payment Strict Send
 * @since 1.0.0
 */
class PathPaymentStrictSendOperationResponse extends PathPaymentOperationResponse
{
    private string $destinationMin;

    /**
     * Gets the minimum amount to be received at destination
     *
     * @return string The minimum destination amount
     */
    public function getDestinationMin(): string
    {
        return $this->destinationMin;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['destination_min'])) $this->destinationMin = $json['destination_min'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : PathPaymentStrictSendOperationResponse {
        $result = new PathPaymentStrictSendOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}