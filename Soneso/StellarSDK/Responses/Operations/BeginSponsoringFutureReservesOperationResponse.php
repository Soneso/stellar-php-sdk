<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

class BeginSponsoringFutureReservesOperationResponse extends OperationResponse
{

    private string $sponsoredId;

    /**
     * @return string
     */
    public function getSponsoredId(): string
    {
        return $this->sponsoredId;
    }

    protected function loadFromJson(array $json): void
    {
        if (isset($json['sponsored_id'])) $this->sponsoredId = $json['sponsored_id'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData): BeginSponsoringFutureReservesOperationResponse
    {
        $result = new BeginSponsoringFutureReservesOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}