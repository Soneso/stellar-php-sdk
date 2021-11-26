<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Trades;

class TradePriceResponse
{
    private string $n;
    private string $d;

    /**
     * @return string
     */
    public function getN(): string
    {
        return $this->n;
    }

    /**
     * @return string
     */
    public function getD(): string
    {
        return $this->d;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['n'])) $this->n = $json['n'];
        if (isset($json['d'])) $this->d = $json['d'];
    }

    public static function fromJson(array $json) : TradePriceResponse {
        $result = new TradePriceResponse();
        $result->loadFromJson($json);
        return $result;
    }
}