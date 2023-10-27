<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

class SEP30AccountsResponse
{
    public array $accounts; // [SEP30AccountResponse]

    /**
     * @param array $accounts
     */
    public function __construct(array $accounts)
    {
        $this->accounts = $accounts;
    }

    public static function fromJson(array $json) : SEP30AccountsResponse
    {

        $accounts = array();
        foreach ($json['accounts'] as $account) {
            array_push($accounts, SEP30AccountResponse::fromJson($account));
        }

        return new SEP30AccountsResponse($accounts);
    }

    /**
     * @return array of SEP30AccountResponse
     */
    public function getAccounts(): array
    {
        return $this->accounts;
    }

    /**
     * @param array $accounts of SEP30AccountResponse
     */
    public function setAccounts(array $accounts): void
    {
        $this->accounts = $accounts;
    }

}