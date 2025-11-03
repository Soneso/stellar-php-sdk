<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Asset;

/**
 * Represents authorization flags set on an asset
 *
 * Contains boolean flags indicating the authorization requirements and capabilities
 * set by the asset issuer. These flags control trustline authorization requirements,
 * revocability, mutability, and clawback capabilities.
 *
 * @package Soneso\StellarSDK\Responses\Asset
 * @see AssetResponse For the parent asset details
 * @see https://developers.stellar.org/api/resources/assets Horizon Assets API
 * @since 1.0.0
 */
class AssetFlagsResponse
{

    private bool $authRequired;
    private bool $authRevocable;
    private bool $authImmutable;
    private bool $authClawbackEnabled;

    /**
     * Checks if authorization is required for accounts to hold this asset
     *
     * @return bool True if authorization is required
     */
    public function isAuthRequired() : bool {
        return $this->authRequired;
    }

    /**
     * Checks if the issuer can revoke authorization for this asset
     *
     * @return bool True if authorization is revocable
     */
    public function isAuthRevocable() : bool {
        return $this->authRevocable;
    }

    /**
     * Checks if these authorization flags are immutable
     *
     * When true, the flags cannot be changed by the issuer.
     *
     * @return bool True if authorization flags are immutable
     */
    public function isAuthImmutable() : bool {
        return $this->authImmutable;
    }

    /**
     * Checks if clawback is enabled for this asset
     *
     * When true, the issuer can clawback this asset from accounts.
     *
     * @return bool True if clawback is enabled
     */
    public function isAuthClawbackEnabled() : bool {
        return $this->authClawbackEnabled;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['auth_required'])) $this->authRequired = $json['auth_required'];
        if (isset($json['auth_revocable'])) $this->authRevocable = $json['auth_revocable'];
        if (isset($json['auth_immutable'])) $this->authImmutable = $json['auth_immutable'];
        if (isset($json['auth_clawback_enabled'])) $this->authClawbackEnabled = $json['auth_clawback_enabled'];
    }

    public static function fromJson(array $json) : AssetFlagsResponse {
        $result = new AssetFlagsResponse();
        $result->loadFromJson($json);
        return $result;
    }
}