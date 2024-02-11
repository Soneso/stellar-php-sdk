<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\Responses\Response;

class FeatureFlags extends Response
{
    /**
     * @var bool $accountCreation Whether the anchor supports creating accounts for users requesting deposits.
     * Defaults to true.
     */
    public bool $accountCreation = true;


    /**
     * @var bool $claimableBalances Whether the anchor supports sending deposit funds as claimable balances.
     * This is relevant for users of Stellar accounts without a trustline to the requested asset. Defaults to false.
     */
    public bool $claimableBalances = false;

    /**
     * Loads the needed data from a json array.
     * @param array<array-key, mixed> $json the data array to read from.
     * @return void
     */
    protected function loadFromJson(array $json) : void {
        if (isset($json['account_creation'])) $this->accountCreation = $json['account_creation'];
        if (isset($json['claimable_balances'])) $this->claimableBalances = $json['claimable_balances'];
    }

    /**
     * Constructs a new FeatureFlags object from the given data array.
     * @param array<array-key, mixed> $json the data array to extract the needed values from.
     * @return FeatureFlags the constructed FeatureFlags object.
     */
    public static function fromJson(array $json) : FeatureFlags
    {
        $result = new FeatureFlags();
        $result->loadFromJson($json);

        return $result;
    }

    /**
     * @return bool Whether the anchor supports creating accounts for users requesting deposits.
     *  Defaults to true.
     */
    public function isAccountCreation(): bool
    {
        return $this->accountCreation;
    }

    /**
     * @param bool $accountCreation Whether the anchor supports creating accounts for users requesting deposits.
     *  Defaults to true.
     */
    public function setAccountCreation(bool $accountCreation): void
    {
        $this->accountCreation = $accountCreation;
    }

    /**
     * @return bool Whether the anchor supports sending deposit funds as claimable balances.
     *  This is relevant for users of Stellar accounts without a trustline to the requested asset. Defaults to false.
     */
    public function isClaimableBalances(): bool
    {
        return $this->claimableBalances;
    }

    /**
     * @param bool $claimableBalances Whether the anchor supports sending deposit funds as claimable balances.
     *  This is relevant for users of Stellar accounts without a trustline to the requested asset. Defaults to false.
     */
    public function setClaimableBalances(bool $claimableBalances): void
    {
        $this->claimableBalances = $claimableBalances;
    }

}