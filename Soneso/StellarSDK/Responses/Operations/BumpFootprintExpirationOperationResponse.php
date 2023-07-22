<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;


class BumpFootprintExpirationOperationResponse extends OperationResponse
{
    public string $ledgersToExpire;

    protected function loadFromJson(array $json) : void {
        $this->ledgersToExpire = $json['ledgers_to_expire'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : BumpFootprintExpirationOperationResponse {
        $result = new BumpFootprintExpirationOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }

    /**
     * @return string
     */
    public function getLedgersToExpire(): string
    {
        return $this->ledgersToExpire;
    }

    /**
     * @param string $ledgersToExpire
     */
    public function setLedgersToExpire(string $ledgersToExpire): void
    {
        $this->ledgersToExpire = $ledgersToExpire;
    }

}