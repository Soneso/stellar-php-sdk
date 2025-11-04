<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Federation;

use Soneso\StellarSDK\Responses\Response;

/**
 * Response from a SEP-0002 federation query.
 *
 * This class represents the response from a federation server containing
 * the resolved Stellar account ID, optional memo type and value, and the
 * original Stellar address if a reverse lookup was performed.
 *
 * @package Soneso\StellarSDK\SEP\Federation
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0002.md
 * @see Federation
 * @see FederationRequestBuilder
 */
class FederationResponse extends Response
{
    private ?string $stellarAddress = null;
    private ?string $accountId = null;
    private ?string $memoType = null;
    private ?string $memo = null;

    /**
     * Gets the Stellar address.
     *
     * @return string|null The Stellar address in format "user*domain.com".
     */
    public function getStellarAddress(): ?string
    {
        return $this->stellarAddress;
    }

    /**
     * Sets the Stellar address.
     *
     * @param string|null $stellarAddress The Stellar address.
     */
    public function setStellarAddress(?string $stellarAddress): void
    {
        $this->stellarAddress = $stellarAddress;
    }

    /**
     * Gets the resolved Stellar account ID.
     *
     * @return string|null The account ID (G-address).
     */
    public function getAccountId(): ?string
    {
        return $this->accountId;
    }

    /**
     * Sets the Stellar account ID.
     *
     * @param string|null $accountId The account ID.
     */
    public function setAccountId(?string $accountId): void
    {
        $this->accountId = $accountId;
    }

    /**
     * Gets the memo type.
     *
     * @return string|null The memo type ("text", "id", or "hash").
     */
    public function getMemoType(): ?string
    {
        return $this->memoType;
    }

    /**
     * Sets the memo type.
     *
     * @param string|null $memoType The memo type.
     */
    public function setMemoType(?string $memoType): void
    {
        $this->memoType = $memoType;
    }

    /**
     * Gets the memo value.
     *
     * @return string|null The memo value.
     */
    public function getMemo(): ?string
    {
        return $this->memo;
    }

    /**
     * Sets the memo value.
     *
     * @param string|null $memo The memo value.
     */
    public function setMemo(?string $memo): void
    {
        $this->memo = $memo;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['stellar_address'])) $this->stellarAddress = $json['stellar_address'];
        if (isset($json['account_id'])) $this->accountId = $json['account_id'];
        if (isset($json['memo_type'])) $this->memoType = $json['memo_type'];
        if (isset($json['memo'])) $this->memo = $json['memo'];
    }

    /**
     * Constructs a FederationResponse from JSON data.
     *
     * @param array<array-key, mixed> $json The JSON data to parse.
     * @return FederationResponse The constructed response.
     */
    public static function fromJson(array $json) : FederationResponse
    {
        $result = new FederationResponse();
        $result->loadFromJson($json);
        return $result;
    }
}