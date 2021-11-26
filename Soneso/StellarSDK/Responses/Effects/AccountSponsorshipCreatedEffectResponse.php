<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

class AccountSponsorshipCreatedEffectResponse extends EffectResponse
{
    private string $sponsor;

    /**
     * @return string
     */
    public function getSponsor(): string
    {
        return $this->sponsor;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['sponsor'])) $this->sponsor = $json['sponsor'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : AccountSponsorshipCreatedEffectResponse {
        $result = new AccountSponsorshipCreatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}