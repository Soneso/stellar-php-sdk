<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

/**
 * Configuration information for the SEP-24 fee calculation endpoint
 *
 * This class indicates whether the anchor provides a fee calculation endpoint
 * and if authentication is required to access it. The fee endpoint allows
 * precise fee calculations for complex fee structures that cannot be expressed
 * using the static fee fields in the /info response.
 *
 * @deprecated The fee endpoint is deprecated in favor of SEP-38 GET /price
 * @package Soneso\StellarSDK\SEP\Interactive
 * @see https://github.com/stellar/stellar-protocol/blob/v3.8.0/ecosystem/sep-0024.md SEP-24 Specification
 * @see https://github.com/stellar/stellar-protocol/blob/v2.5.0/ecosystem/sep-0038.md SEP-38 for replacement
 */
class FeeEndpointInfo
{
    /**
     * @var bool $enabled true if the anchor offers a fee endpoint
     */
    public bool $enabled;

    /**
     * @var bool $authenticationRequired true if the anchor requests sep-10 authentication for calling the fee endpoint
     */
    public bool $authenticationRequired;

    /**
     * Loads the needed data from a json array.
     * @param array<array-key, mixed> $json the data array to read from.
     * @return void
     */
    protected function loadFromJson(array $json) : void {
        if (isset($json['enabled'])) $this->enabled = $json['enabled'];
        if (isset($json['authentication_required'])) {
            $this->authenticationRequired = $json['authentication_required'];
        } else {
            $this->authenticationRequired = false;
        }
    }

    /**
     * Constructs a new FeeEndpointInfo object from the given data array.
     * @param array<array-key, mixed> $json the data array to extract the needed values from.
     * @return FeeEndpointInfo the constructed FeeEndpointInfo object.
     */
    public static function fromJson(array $json) : FeeEndpointInfo
    {
        $result = new FeeEndpointInfo();
        $result->loadFromJson($json);

        return $result;
    }

    /**
     * @return bool true if the anchor offers a fee endpoint.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled true if the anchor offers a fee endpoint.
     * @return void
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * @return bool true if the anchor requests sep-10 authentication for calling the fee endpoint.
     */
    public function isAuthenticationRequired(): bool
    {
        return $this->authenticationRequired;
    }

    /**
     * @param bool $authenticationRequired true if the anchor requests sep-10 authentication for calling the fee endpoint.
     * @return void
     */
    public function setAuthenticationRequired(bool $authenticationRequired): void
    {
        $this->authenticationRequired = $authenticationRequired;
    }
}