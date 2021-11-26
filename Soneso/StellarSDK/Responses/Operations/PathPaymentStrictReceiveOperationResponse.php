<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

class PathPaymentStrictReceiveOperationResponse extends PathPaymentOperationResponse
{
    private string $sourceMax;

    /**
     * @return string
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