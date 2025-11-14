<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

/**
 * Response containing multiple accounts from SEP-0030 recovery server.
 *
 * This class represents a collection of registered accounts that the JWT
 * token allows access to via the GET /accounts endpoint.
 *
 * @package Soneso\StellarSDK\SEP\Recovery
 * @see https://github.com/stellar/stellar-protocol/blob/v0.8.1/ecosystem/sep-0030.md#get-accounts
 * @see RecoveryService::accounts()
 * @see SEP30AccountResponse
 */
class SEP30AccountsResponse
{
    /**
     * @var array<SEP30AccountResponse> $accounts
     */
    public array $accounts;

    /**
     * Constructor.
     *
     * @param array<SEP30AccountResponse> $accounts Array of account responses.
     */
    public function __construct(array $accounts)
    {
        $this->accounts = $accounts;
    }

    /**
     * Constructs a SEP30AccountsResponse from JSON data.
     *
     * @param array<array-key, mixed> $json The JSON data to parse.
     * @return SEP30AccountsResponse The constructed response.
     */
    public static function fromJson(array $json) : SEP30AccountsResponse
    {

        $accounts = array();
        foreach ($json['accounts'] as $account) {
            $accounts[] = SEP30AccountResponse::fromJson($account);
        }

        return new SEP30AccountsResponse($accounts);
    }

    /**
     * Gets the array of accounts.
     *
     * @return array<SEP30AccountResponse> The accounts list.
     */
    public function getAccounts(): array
    {
        return $this->accounts;
    }

    /**
     * Sets the array of accounts.
     *
     * @param array<SEP30AccountResponse> $accounts The accounts list.
     */
    public function setAccounts(array $accounts): void
    {
        $this->accounts = $accounts;
    }

}