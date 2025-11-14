<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Account;

use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Page\PageResponse;

/**
 * Represents a paginated list of accounts from Horizon
 *
 * This response contains a page of account entries along with pagination links to navigate
 * through large result sets. The response follows HAL format with embedded account records
 * and provides methods to fetch next and previous pages.
 *
 * Returned by Horizon endpoint:
 * - GET /accounts - List all accounts with filtering options
 *
 * @package Soneso\StellarSDK\Responses\Account
 * @see AccountResponse For individual account details
 * @see AccountsResponse For the collection of accounts in this page
 * @see PageResponse For pagination functionality
 * @see https://developers.stellar.org Stellar developer docs Horizon Accounts API
 * @since 1.0.0
 */
class AccountsPageResponse extends PageResponse
{
    private AccountsResponse $accounts;

    /**
     * Gets the collection of accounts in this page
     *
     * @return AccountsResponse The collection of account entries
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

    /**
     * Creates an AccountsPageResponse instance from JSON data
     *
     * @param array $json The JSON array containing page data from Horizon
     * @return AccountsPageResponse The parsed page response
     */
    public static function fromJson(array $json) : AccountsPageResponse {
        $result = new AccountsPageResponse();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * Fetches the next page of accounts
     *
     * @return AccountsPageResponse|null The next page, or null if no next page exists
     */
    public function getNextPage(): AccountsPageResponse | null {
        return $this->executeRequest(RequestType::ACCOUNTS_PAGE, $this->getNextPageUrl());
    }

    /**
     * Fetches the previous page of accounts
     *
     * @return AccountsPageResponse|null The previous page, or null if no previous page exists
     */
    public function getPreviousPage(): AccountsPageResponse | null {
        return $this->executeRequest(RequestType::ACCOUNTS_PAGE, $this->getPrevPageUrl());
    }
}