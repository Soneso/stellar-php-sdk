<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;


class ExtendFootprintTTLOperationResponse extends OperationResponse
{
    public int $extendTo;

    protected function loadFromJson(array $json) : void {
        if (isset($json['extend_to'])) {
            $this->extendTo = $json['extend_to'];
        }
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : ExtendFootprintTTLOperationResponse {
        $result = new ExtendFootprintTTLOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }

    /**
     * @return int
     */
    public function getExtendTo(): int
    {
        return $this->extendTo;
    }

    /**
     * @param int $extendTo
     */
    public function setExtendTo(int $extendTo): void
    {
        $this->extendTo = $extendTo;
    }

}