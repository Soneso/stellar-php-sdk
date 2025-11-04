<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

/**
 * Represents a revoke sponsorship operation response from Horizon API
 *
 * This operation removes reserve sponsorship from a ledger entry, transferring the reserve
 * responsibility back to the entry owner or to a new sponsor. It can revoke sponsorship for
 * accounts, trustlines, offers, data entries, claimable balances, liquidity pool entries,
 * and signers. Only the current sponsor can revoke their sponsorship of an entry.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org/api/resources/operations/object/revoke-sponsorship Horizon Revoke Sponsorship Operation
 */
class RevokeSponsorshipOperationResponse extends OperationResponse
{
    private ?string $accountId= null;
    private ?string $claimableBalanceId = null;
    private ?string $dataAccountId = null;
    private ?string $dataName = null;
    private ?string $offerId = null;
    private ?string $trustlineAccountId = null;
    private ?string $trustlineAsset = null;
    private ?string $signerAccountId = null;
    private ?string $signerKey = null;

    /**
     * Gets the account ID if revoking account sponsorship
     *
     * @return string|null The account ID or null if not an account revocation
     */
    public function getAccountId(): ?string
    {
        return $this->accountId;
    }

    /**
     * Gets the claimable balance ID if revoking claimable balance sponsorship
     *
     * @return string|null The claimable balance ID or null if not a balance revocation
     */
    public function getClaimableBalanceId(): ?string
    {
        return $this->claimableBalanceId;
    }

    /**
     * Gets the account ID if revoking data entry sponsorship
     *
     * @return string|null The data account ID or null if not a data revocation
     */
    public function getDataAccountId(): ?string
    {
        return $this->dataAccountId;
    }

    /**
     * Gets the data entry name if revoking data entry sponsorship
     *
     * @return string|null The data entry name or null if not a data revocation
     */
    public function getDataName(): ?string
    {
        return $this->dataName;
    }

    /**
     * Gets the offer ID if revoking offer sponsorship
     *
     * @return string|null The offer ID or null if not an offer revocation
     */
    public function getOfferId(): ?string
    {
        return $this->offerId;
    }

    /**
     * Gets the trustline account ID if revoking trustline sponsorship
     *
     * @return string|null The trustline account ID or null if not a trustline revocation
     */
    public function getTrustlineAccountId(): ?string
    {
        return $this->trustlineAccountId;
    }

    /**
     * Gets the trustline asset if revoking trustline sponsorship
     *
     * @return string|null The trustline asset or null if not a trustline revocation
     */
    public function getTrustlineAsset(): ?string
    {
        return $this->trustlineAsset;
    }

    /**
     * Gets the signer account ID if revoking signer sponsorship
     *
     * @return string|null The signer account ID or null if not a signer revocation
     */
    public function getSignerAccountId(): ?string
    {
        return $this->signerAccountId;
    }

    /**
     * Gets the signer key if revoking signer sponsorship
     *
     * @return string|null The signer key or null if not a signer revocation
     */
    public function getSignerKey(): ?string
    {
        return $this->signerKey;
    }


    protected function loadFromJson(array $json) : void {

        if (isset($json['account_id'])) $this->accountId = $json['account_id'];
        if (isset($json['claimable_balance_id'])) $this->claimableBalanceId = $json['claimable_balance_id'];
        if (isset($json['data_account_id'])) $this->dataAccountId = $json['data_account_id'];
        if (isset($json['data_name'])) $this->dataName = $json['data_name'];
        if (isset($json['offer_id'])) $this->offerId = $json['offer_id'];
        if (isset($json['trustline_account_id'])) $this->trustlineAccountId = $json['trustline_account_id'];
        if (isset($json['trustline_asset'])) $this->trustlineAsset = $json['trustline_asset'];
        if (isset($json['signer_account_id'])) $this->signerAccountId = $json['signer_account_id'];
        if (isset($json['signer_key'])) $this->signerKey = $json['signer_key'];

        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : RevokeSponsorshipOperationResponse {
        $result = new RevokeSponsorshipOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}