<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Account;

use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Page\PageResponse;

class AccountsPageResponse extends PageResponse
{
    private AccountsResponse $accounts;

    /**
     * @return AccountsResponse
     */
    public function getAccounts(): AccountsResponse {
        return $this->accounts;
    }

    protected function loadFromJson(array $json) : void {
        parent::loadFromJson($json);
        if (isset($json['_embedded']['records'])) {
            $this->accounts = new AccountsResponse();
            foreach ($json['_embedded']['records'] as $jsonAccount) {
                $account = AccountResponse::fromJson($jsonAccount);
                $this->accounts->add($account);
            }
        }
    }

    public static function fromJson(array $json) : AccountsPageResponse {
        $result = new AccountsPageResponse();
        $result->loadFromJson($json);
        return $result;
    }

    public function getNextPage(): AccountsPageResponse | null {
        return $this->executeRequest(RequestType::ACCOUNTS_PAGE, $this->getNextPageUrl());
    }

    public function getPreviousPage(): AccountsPageResponse | null {
        return $this->executeRequest(RequestType::ACCOUNTS_PAGE, $this->getPrevPageUrl());
    }
}