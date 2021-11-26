<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

class PathPaymentStrictSendOperationResponse extends PathPaymentOperationResponse
{
    private string $destinationMin;

    /**
     * @return string
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