<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\Responses\Response;

/**
 * Represents additional features supported by the anchor for SEP-24 operations
 *
 * This class contains flags indicating which optional features the anchor supports
 * for deposit and withdrawal operations. These features enhance the flexibility of
 * the anchor's service but are not required by the SEP-24 specification.
 *
 * Account creation support allows the anchor to create new Stellar accounts for users
 * who don't yet have one. Claimable balance support enables the anchor to send deposit
 * funds as claimable balances, which is useful for recipients without established
 * trustlines to the deposited asset.
 *
 * @package Soneso\StellarSDK\SEP\Interactive
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md SEP-24 Specification
 * @see SEP24InfoResponse For the parent info response
 */
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