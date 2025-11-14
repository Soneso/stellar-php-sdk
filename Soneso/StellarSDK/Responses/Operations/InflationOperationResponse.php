<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

/**
 * Represents an inflation operation response from Horizon API
 *
 * This deprecated operation ran the weekly inflation protocol on the Stellar network, distributing
 * newly created lumens based on voting. The inflation mechanism was disabled by network vote and
 * this operation is no longer functional. It remains for historical compatibility with older ledgers.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org Stellar developer docs Horizon Inflation Operation
 * @deprecated Inflation mechanism disabled by network vote
 */
class InflationOperationResponse extends OperationResponse
{

    public static function fromJson(array $jsonData) : InflationOperationResponse {
        $result = new InflationOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}