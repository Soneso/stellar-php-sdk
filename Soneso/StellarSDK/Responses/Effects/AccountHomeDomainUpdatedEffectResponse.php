<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

class AccountHomeDomainUpdatedEffectResponse extends EffectResponse
{
    private string $homeDomain;

    /**
     * @return string
     */
    public function getHomeDomain(): string {
        return $this->homeDomain;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['home_domain'])) $this->homeDomain = $json['home_domain'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : AccountHomeDomainUpdatedEffectResponse {
        $result = new AccountHomeDomainUpdatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}