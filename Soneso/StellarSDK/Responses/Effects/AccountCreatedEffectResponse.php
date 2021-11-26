<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

class AccountCreatedEffectResponse extends EffectResponse
{
    private string $startingBalance;

    /**
     * @return string
     */
    public function getStartingBalance(): string {
        return $this->startingBalance;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['starting_balance'])) $this->startingBalance = $json['starting_balance'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : AccountCreatedEffectResponse {
        $result = new AccountCreatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}