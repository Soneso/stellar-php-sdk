<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;


use DateTime;

class SEP24TransactionsRequest
{
    /// jwt token previously received from the anchor via the SEP-10 authentication flow
    public string $jwt;

    /// The code of the asset of interest. E.g. BTC, ETH, USD, INR, etc.
    public string $assetCode;

    /// (optional) The response should contain transactions starting on or after this date & time. UTC ISO 8601 string.
    public ?DateTime $noOlderThan = null;

    /// (optional) The response should contain at most limit transactions.
    public ?int $limit = null;

    /// (optional) The kind of transaction that is desired. Should be either deposit or withdrawal.
    public ?string $kind = null;

    /// (optional) The response should contain transactions starting prior to this ID (exclusive).
    public ?string $pagingId = null;

    /// (optional) Defaults to en if not specified or if the specified language is not supported.
    /// Language code specified using RFC 4646 which means it can also accept locale in the format en-US.
    public ?string $lang = null;

    /**
     * @return string
     */
    public function getJwt(): string
    {
        return $this->jwt;
    }

    /**
     * @param string $jwt
     */
    public function setJwt(string $jwt): void
    {
        $this->jwt = $jwt;
    }

    /**
     * @return string
     */
    public function getAssetCode(): string
    {
        return $this->assetCode;
    }

    /**
     * @param string $assetCode
     */
    public function setAssetCode(string $assetCode): void
    {
        $this->assetCode = $assetCode;
    }

    /**
     * @return DateTime|null
     */
    public function getNoOlderThan(): ?DateTime
    {
        return $this->noOlderThan;
    }

    /**
     * @param DateTime|null $noOlderThan
     */
    public function setNoOlderThan(?DateTime $noOlderThan): void
    {
        $this->noOlderThan = $noOlderThan;
    }


    /**
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @param int|null $limit
     */
    public function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * @return string|null
     */
    public function getKind(): ?string
    {
        return $this->kind;
    }

    /**
     * @param string|null $kind
     */
    public function setKind(?string $kind): void
    {
        $this->kind = $kind;
    }

    /**
     * @return string|null
     */
    public function getPagingId(): ?string
    {
        return $this->pagingId;
    }

    /**
     * @param string|null $pagingId
     */
    public function setPagingId(?string $pagingId): void
    {
        $this->pagingId = $pagingId;
    }

    /**
     * @return string|null
     */
    public function getLang(): ?string
    {
        return $this->lang;
    }

    /**
     * @param string|null $lang
     */
    public function setLang(?string $lang): void
    {
        $this->lang = $lang;
    }
}