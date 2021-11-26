<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Account;

class AccountFlagsResponse
{

    private bool $authRequired;
    private bool $authRevocable;
    private bool $authImmutable;
    private bool $authClawbackEnabled;
    
    public function isAuthRequired() : bool {
        return $this->authRequired;
    }

    public function isAuthRevocable() : bool {
        return $this->authRevocable;
    }

    public function isAuthImmutable() : bool {
        return $this->authImmutable;
    }

    public function isAuthClawbackEnabled() : bool {
        return $this->authClawbackEnabled;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['auth_required'])) $this->authRequired = $json['auth_required'];
        if (isset($json['auth_revocable'])) $this->authRevocable = $json['auth_revocable'];
        if (isset($json['auth_immutable'])) $this->authImmutable = $json['auth_immutable'];
        if (isset($json['auth_clawback_enabled'])) $this->authClawbackEnabled = $json['auth_clawback_enabled'];
    }
    
    public static function fromJson(array $json) : AccountFlagsResponse {
        $result = new AccountFlagsResponse();
        $result->loadFromJson($json);
        return $result;
    }
}

