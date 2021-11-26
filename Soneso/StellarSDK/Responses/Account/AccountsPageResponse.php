<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Account;

use Soneso\StellarSDK\Responses\Page\PageResponse;
use Soneso\StellarSDK\Responses\Page\PagingLinksResponse;

class AccountsPageResponse extends PageResponse
{
    private PagingLinksResponse $links;
    private AccountsResponse $accounts;

    /**
     * @return PagingLinksResponse
     */
    public function getLinks(): PagingLinksResponse
    {
        return $this->links;
    }

    /**
     * @return AccountsResponse
     */
    public function getAccounts(): AccountsResponse
    {
        return $this->accounts;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['_links'])) $this->links = PagingLinksResponse::fromJson($json['_links']);
        if (isset($json['_embedded']['records'])) {
            $this->accounts = new AccountsResponse();
            foreach ($json['_embedded']['records'] as $jsonAccount) {
                $account = AccountResponse::fromJson($jsonAccount);
                $this->accounts->add($account);
            }
        }
    }

    public static function fromJson(array $json) : AccountsPageResponse
    {
        $result = new AccountsPageResponse();
        $result->loadFromJson($json);
        return $result;
    }
}