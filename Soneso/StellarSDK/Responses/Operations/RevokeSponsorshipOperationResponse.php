<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

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
     * @return string|null
     */
    public function getAccountId(): ?string
    {
        return $this->accountId;
    }

    /**
     * @return string|null
     */
    public function getClaimableBalanceId(): ?string
    {
        return $this->claimableBalanceId;
    }

    /**
     * @return string|null
     */
    public function getDataAccountId(): ?string
    {
        return $this->dataAccountId;
    }

    /**
     * @return string|null
     */
    public function getDataName(): ?string
    {
        return $this->dataName;
    }

    /**
     * @return string|null
     */
    public function getOfferId(): ?string
    {
        return $this->offerId;
    }

    /**
     * @return string|null
     */
    public function getTrustlineAccountId(): ?string
    {
        return $this->trustlineAccountId;
    }

    /**
     * @return string|null
     */
    public function getTrustlineAsset(): ?string
    {
        return $this->trustlineAsset;
    }

    /**
     * @return string|null
     */
    public function getSignerAccountId(): ?string
    {
        return $this->signerAccountId;
    }

    /**
     * @return string|null
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