<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;


class ClaimClaimableBalanceOperationResponse extends OperationResponse
{
    private string $balanceId;
    private string $claimant;
    private ?string $claimantMuxed = null;
    private ?string $claimantMuxedId = null;

    /**
     * @return string
     */
    public function getBalanceId(): string
    {
        return $this->balanceId;
    }

    /**
     * @return string
     */
    public function getClaimant(): string
    {
        return $this->claimant;
    }

    /**
     * @return string|null
     */
    public function getClaimantMuxed(): ?string
    {
        return $this->claimantMuxed;
    }

    /**
     * @return string|null
     */
    public function getClaimantMuxedId(): ?string
    {
        return $this->claimantMuxedId;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['balance_id'])) $this->balanceId = $json['balance_id'];
        if (isset($json['claimant'])) $this->claimant = $json['claimant'];
        if (isset($json['claimant_muxed'])) $this->claimantMuxed = $json['claimant_muxed'];
        if (isset($json['claimant_muxed_id'])) $this->claimantMuxedId = $json['claimant_muxed_id'];

        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : ClaimClaimableBalanceOperationResponse {
        $result = new ClaimClaimableBalanceOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}