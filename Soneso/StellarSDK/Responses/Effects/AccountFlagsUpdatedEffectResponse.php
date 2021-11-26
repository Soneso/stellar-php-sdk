<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

class AccountFlagsUpdatedEffectResponse extends EffectResponse
{
    private bool $authRequired;
    private bool $authRevocable;
    private bool $authImmutable;

    /**
     * @return bool
     */
    public function isAuthRequired(): bool
    {
        return $this->authRequired;
    }

    /**
     * @return bool
     */
    public function isAuthRevocable(): bool
    {
        return $this->authRevocable;
    }

    /**
     * @return bool
     */
    public function isAuthImmutable(): bool
    {
        return $this->authImmutable;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['auth_required'])) $this->authRequired = $json['auth_required'];
        if (isset($json['auth_revocable'])) $this->authRevocable = $json['auth_revocable'];
        if (isset($json['auth_immutable'])) $this->authImmutable = $json['auth_immutable'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : AccountFlagsUpdatedEffectResponse {
        $result = new AccountFlagsUpdatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}