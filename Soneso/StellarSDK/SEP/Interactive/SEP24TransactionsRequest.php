<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;


use DateTime;

/**
 * Request parameters for querying SEP-24 transaction history
 *
 * This class contains the parameters needed to retrieve a list of transactions
 * from the anchor using the /transactions endpoint. The response can be filtered
 * by asset, transaction type, time range, and paginated using the limit and
 * paging_id parameters.
 *
 * @package Soneso\StellarSDK\SEP\Interactive
 * @see https://github.com/stellar/stellar-protocol/blob/v3.8.0/ecosystem/sep-0024.md SEP-24 Specification
 * @see InteractiveService::transactions() For executing transaction history queries
 * @see SEP24TransactionsResponse For the response structure
 */
class SEP24TransactionsRequest
{
    /**
     * @var string JWT token obtained from SEP-10 authentication flow containing
     *             the authenticated Stellar account and optional memo
     */
    public string $jwt;

    /**
     * @var string The code of the asset to filter transactions.
     *             Examples: 'BTC', 'ETH', 'USD', 'native' for XLM
     */
    public string $assetCode;

    /**
     * @var DateTime|null Filter to include only transactions created on or after this date and time.
     *                    Specified as DateTime object that will be converted to UTC ISO 8601 format
     */
    public ?DateTime $noOlderThan = null;

    /**
     * @var int|null Maximum number of transactions to return in the response.
     *               Used for pagination to control response size
     */
    public ?int $limit = null;

    /**
     * @var string|null Filter by transaction type. Valid values: 'deposit' or 'withdrawal'.
     *                  Omit to retrieve both deposit and withdrawal transactions
     */
    public ?string $kind = null;

    /**
     * @var string|null Pagination cursor. Returns transactions created before this transaction ID.
     *                  Use the oldest transaction ID from the previous response to fetch the next page
     */
    public ?string $pagingId = null;

    /**
     * @var string|null Language code for localized responses following RFC 4646 format.
     *                  Supports locale variants like 'en-US'. Defaults to 'en' if not specified
     *                  or if the specified language is not supported by the anchor
     */
    public ?string $lang = null;

    /**
     * @return string jwt token previously received from the anchor via the SEP-10 authentication flow
     */
    public function getJwt(): string
    {
        return $this->jwt;
    }

    /**
     * @param string $jwt jwt token previously received from the anchor via the SEP-10 authentication flow
     * @return void
     */
    public function setJwt(string $jwt): void
    {
        $this->jwt = $jwt;
    }

    /**
     * @return string The code of the asset of interest. E.g. BTC, ETH, USD, INR, etc.
     */
    public function getAssetCode(): string
    {
        return $this->assetCode;
    }

    /**
     * @param string $assetCode The code of the asset of interest. E.g. BTC, ETH, USD, INR, etc.
     * @return void
     */
    public function setAssetCode(string $assetCode): void
    {
        $this->assetCode = $assetCode;
    }

    /**
     * @return DateTime|null (optional) The response should contain transactions starting on or after this date & time. UTC ISO 8601 string.
     */
    public function getNoOlderThan(): ?DateTime
    {
        return $this->noOlderThan;
    }

    /**
     * @param DateTime|null $noOlderThan (optional) The response should contain transactions starting on or after this date & time. UTC ISO 8601 string.
     * @return void
     */
    public function setNoOlderThan(?DateTime $noOlderThan): void
    {
        $this->noOlderThan = $noOlderThan;
    }


    /**
     * @return int|null (optional) The response should contain at most limit transactions.
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @param int|null $limit (optional) The response should contain at most limit transactions.
     * @return void
     */
    public function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * @return string|null (optional) The kind of transaction that is desired. Should be either 'deposit' or 'withdrawal'.
     */
    public function getKind(): ?string
    {
        return $this->kind;
    }

    /**
     * @param string|null $kind (optional) The kind of transaction that is desired. Should be either 'deposit' or 'withdrawal'.
     * @return void
     */
    public function setKind(?string $kind): void
    {
        $this->kind = $kind;
    }

    /**
     * @return string|null (optional) The response should contain transactions starting prior to this ID (exclusive).
     */
    public function getPagingId(): ?string
    {
        return $this->pagingId;
    }

    /**
     * @param string|null $pagingId (optional) The response should contain transactions starting prior to this ID (exclusive).
     * @return void
     */
    public function setPagingId(?string $pagingId): void
    {
        $this->pagingId = $pagingId;
    }

    /**
     * @return string|null (optional) Defaults to en if not specified or if the specified language is not supported.
     *  Language code specified using RFC 4646 which means it can also accept locale in the format en-US.
     */
    public function getLang(): ?string
    {
        return $this->lang;
    }

    /**
     * @param string|null $lang (optional) Defaults to en if not specified or if the specified language is not supported.
     *  Language code specified using RFC 4646 which means it can also accept locale in the format en-US.
     * @return void
     */
    public function setLang(?string $lang): void
    {
        $this->lang = $lang;
    }
}