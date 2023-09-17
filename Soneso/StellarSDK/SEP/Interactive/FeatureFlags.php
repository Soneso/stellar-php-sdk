<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\Responses\Response;

class FeatureFlags extends Response
{
    /// Whether or not the anchor supports creating accounts for users requesting deposits. Defaults to true.
    public bool $accountCreation = true;
    /// Whether or not the anchor supports sending deposit funds as claimable balances. This is relevant for users of Stellar accounts without a trustline to the requested asset. Defaults to false.
    public bool $claimableBalances = false;

    protected function loadFromJson(array $json) : void {
        if (isset($json['account_creation'])) $this->accountCreation = $json['account_creation'];
        if (isset($json['claimable_balances'])) $this->claimableBalances = $json['claimable_balances'];
    }

    public static function fromJson(array $json) : FeatureFlags
    {
        $result = new FeatureFlags();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * @return bool
     */
    public function isAccountCreation(): bool
    {
        return $this->accountCreation;
    }

    /**
     * @param bool $accountCreation
     */
    public function setAccountCreation(bool $accountCreation): void
    {
        $this->accountCreation = $accountCreation;
    }

    /**
     * @return bool
     */
    public function isClaimableBalances(): bool
    {
        return $this->claimableBalances;
    }

    /**
     * @param bool $claimableBalances
     */
    public function setClaimableBalances(bool $claimableBalances): void
    {
        $this->claimableBalances = $claimableBalances;
    }

}