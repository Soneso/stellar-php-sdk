<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;


use DateTime;

class SEP24TransactionsRequest
{
    /**
     * @var string $jwt jwt token previously received from the anchor via the SEP-10 authentication flow
     */
    public string $jwt;

    /**
     * @var string $assetCode The code of the asset of interest. E.g. BTC, ETH, USD, INR, etc.
     */
    public string $assetCode;

    /**
     * @var DateTime|null $noOlderThan (optional) The response should contain transactions starting on or after this date & time. UTC ISO 8601 string.
     */
    public ?DateTime $noOlderThan = null;

    /**
     * @var int|null $limit (optional) The response should contain at most limit transactions.
     */
    public ?int $limit = null;

    /**
     * @var string|null $kind (optional) The kind of transaction that is desired. Should be either 'deposit' or 'withdrawal'.
     */
    public ?string $kind = null;

    /**
     * @var string|null $pagingId (optional) The response should contain transactions starting prior to this ID (exclusive).
     */
    public ?string $pagingId = null;

    /**
     * @var string|null $lang (optional) Defaults to en if not specified or if the specified language is not supported.
     * Language code specified using RFC 4646 which means it can also accept locale in the format en-US.
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
     */
    public function setLang(?string $lang): void
    {
        $this->lang = $lang;
    }
}