<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

class RestoreFootprintOperationResponse extends OperationResponse
{

    public static function fromJson(array $jsonData) : InflationOperationResponse {
        $result = new InflationOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}