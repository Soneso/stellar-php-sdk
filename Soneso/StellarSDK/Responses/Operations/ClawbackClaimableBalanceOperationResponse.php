<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

/**
 * Represents a clawback claimable balance operation response from Horizon API
 *
 * This operation claws back a claimable balance, permanently removing it from the ledger.
 * Only the sponsor or asset issuer can perform this operation. This destroys the claimable
 * balance entry and prevents it from being claimed by any of the authorized claimants.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org Stellar developer docs Horizon Clawback Claimable Balance Operation
 */
class ClawbackClaimableBalanceOperationResponse extends OperationResponse
{
    private string $balanceId;

    /**
     * Gets the ID of the claimable balance being clawed back
     *
     * @return string The unique claimable balance identifier
     */
    public function getBalanceId(): string
    {
        return $this->balanceId;
    }

    protected function loadFromJson(array $json): void {
        if (isset($json['balance_id'])) $this->balanceId = $json['balance_id'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData): ClawbackClaimableBalanceOperationResponse {
        $result = new ClawbackClaimableBalanceOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}