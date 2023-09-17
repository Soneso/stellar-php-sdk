<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\Responses\Response;

class FeeEndpointInfo extends Response
{
    /// true if the endpoint is available.
    public bool $enabled;
    /// true if client must be authenticated before accessing the fee endpoint.
    public bool $authenticationRequired;

    protected function loadFromJson(array $json) : void {
        if (isset($json['enabled'])) $this->enabled = $json['enabled'];
        if (isset($json['authentication_required'])) $this->authenticationRequired = $json['authentication_required'];
    }

    public static function fromJson(array $json) : FeeEndpointInfo
    {
        $result = new FeeEndpointInfo();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * @return bool
     */
    public function isAuthenticationRequired(): bool
    {
        return $this->authenticationRequired;
    }

    /**
     * @param bool $authenticationRequired
     */
    public function setAuthenticationRequired(bool $authenticationRequired): void
    {
        $this->authenticationRequired = $authenticationRequired;
    }
}