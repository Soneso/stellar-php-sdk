<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

/**
 * Represents an account merge operation response from Horizon API
 *
 * This operation merges one account into another, transferring all lumens from the
 * source account to the destination account and removing the source account from the ledger.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org/api/resources/operations/object/account-merge Horizon Account Merge
 * @since 1.0.0
 */
class AccountMergeOperationResponse extends OperationResponse
{
    private string $account;
    private ?string $accountMuxed = null;
    private ?string $accountMuxedId = null;
    private string $into;
    private ?string $intoMuxed = null;
    private ?string $intoMuxedId = null;

    /**
     * Gets the account being merged and removed
     *
     * @return string The source account ID being merged
     */
    public function getAccount(): string
    {
        return $this->account;
    }

    /**
     * Gets the multiplexed source account if applicable
     *
     * @return string|null The muxed source account address or null
     */
    public function getAccountMuxed(): ?string
    {
        return $this->accountMuxed;
    }

    /**
     * Gets the multiplexed source account ID if applicable
     *
     * @return string|null The muxed source account ID or null
     */
    public function getAccountMuxedId(): ?string
    {
        return $this->accountMuxedId;
    }

    /**
     * Gets the destination account receiving the funds
     *
     * @return string The destination account ID
     */
    public function getInto(): string
    {
        return $this->into;
    }

    /**
     * Gets the multiplexed destination account if applicable
     *
     * @return string|null The muxed destination account address or null
     */
    public function getIntoMuxed(): ?string
    {
        return $this->intoMuxed;
    }

    /**
     * Gets the multiplexed destination account ID if applicable
     *
     * @return string|null The muxed destination account ID or null
     */
    public function getIntoMuxedId(): ?string
    {
        return $this->intoMuxedId;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['account'])) $this->account = $json['account'];
        if (isset($json['account_muxed'])) $this->accountMuxed = $json['account_muxed'];
        if (isset($json['account_muxed_id'])) $this->accountMuxedId = $json['account_muxed_id'];
        if (isset($json['into'])) $this->into = $json['into'];
        if (isset($json['into_muxed'])) $this->intoMuxed = $json['into_muxed'];
        if (isset($json['into_muxed_id'])) $this->intoMuxedId = $json['into_muxed_id'];

        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : AccountMergeOperationResponse {
        $result = new AccountMergeOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}