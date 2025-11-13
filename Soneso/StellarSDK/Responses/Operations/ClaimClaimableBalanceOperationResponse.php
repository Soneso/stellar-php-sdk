<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

/**
 * Represents a claim claimable balance operation response from Horizon API
 *
 * This operation claims a claimable balance, transferring the balance to the claimant's account.
 * The claimant must be one of the authorized claimants specified when the balance was created.
 * Once claimed, the claimable balance entry is removed from the ledger and the asset is transferred
 * to the claiming account.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org Stellar developer docs Horizon Claim Claimable Balance Operation
 */
class ClaimClaimableBalanceOperationResponse extends OperationResponse
{
    private string $balanceId;
    private string $claimant;
    private ?string $claimantMuxed = null;
    private ?string $claimantMuxedId = null;

    /**
     * Gets the ID of the claimable balance being claimed
     *
     * @return string The unique claimable balance identifier
     */
    public function getBalanceId(): string
    {
        return $this->balanceId;
    }

    /**
     * Gets the account address claiming the balance
     *
     * @return string The claimant account ID
     */
    public function getClaimant(): string
    {
        return $this->claimant;
    }

    /**
     * Gets the multiplexed claimant account if applicable
     *
     * @return string|null The muxed claimant address or null
     */
    public function getClaimantMuxed(): ?string
    {
        return $this->claimantMuxed;
    }

    /**
     * Gets the multiplexed claimant account ID if applicable
     *
     * @return string|null The muxed claimant ID or null
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