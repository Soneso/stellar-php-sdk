<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents an account flags updated effect from the Stellar network
 *
 * This effect occurs when an account's authorization flags are modified.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse Base effect class
 * @see https://developers.stellar.org Stellar developer docs Horizon Effects API
 * @since 1.0.0
 */
class AccountFlagsUpdatedEffectResponse extends EffectResponse
{
    private bool $authRequired;
    private bool $authRevocable;
    private bool $authImmutable;

    /**
     * Checks if authorization is required for this account
     *
     * @return bool True if authorization is required
     */
    public function isAuthRequired(): bool
    {
        return $this->authRequired;
    }

    /**
     * Checks if authorization can be revoked for this account
     *
     * @return bool True if authorization is revocable
     */
    public function isAuthRevocable(): bool
    {
        return $this->authRevocable;
    }

    /**
     * Checks if this account's authorization flags are immutable
     *
     * @return bool True if flags cannot be changed
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