<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;


class ExtendFootprintTTLOperationResponse extends OperationResponse
{
    public string $extendTo;

    protected function loadFromJson(array $json) : void {
        if (isset($json['extend_to'])) {
            $this->extendTo = $json['extend_to'];
        } else if (isset($json['ledgers_to_expire'])) {
            $this->extendTo = $json['ledgers_to_expire'];
        }
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : ExtendFootprintTTLOperationResponse {
        $result = new ExtendFootprintTTLOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }

    /**
     * @return string
     */
    public function getExtendTo(): string
    {
        return $this->extendTo;
    }

    /**
     * @param string $extendTo
     */
    public function setExtendTo(string $extendTo): void
    {
        $this->extendTo = $extendTo;
    }

}