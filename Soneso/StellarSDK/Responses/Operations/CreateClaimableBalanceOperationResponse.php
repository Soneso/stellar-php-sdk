<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimantResponse;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimantsResponse;

/**
 * Represents a create claimable balance operation response from Horizon API
 *
 * This operation creates a claimable balance entry on the ledger with a specified amount of an asset
 * and a list of authorized claimants. The claimable balance can later be claimed by any of the specified
 * claimants who meet the predicate conditions. The creating account sponsors the base reserve for the
 * balance entry until it is claimed or clawed back.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org/api/resources/operations/object/create-claimable-balance Horizon Create Claimable Balance Operation
 */
class CreateClaimableBalanceOperationResponse extends OperationResponse
{
    private string $sponsor;
    private string $amount;
    private Asset $asset;
    private ClaimantsResponse $claimants;

    /**
     * Gets the account sponsoring the claimable balance reserve
     *
     * @return string The sponsor account ID
     */
    public function getSponsor(): string
    {
        return $this->sponsor;
    }

    /**
     * Gets the amount of the asset in the claimable balance
     *
     * @return string The amount as a string to preserve precision
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Gets the asset held in the claimable balance
     *
     * @return Asset The asset details (type, code, issuer)
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * Gets the list of authorized claimants for this balance
     *
     * @return ClaimantsResponse Collection of claimants with their predicates
     */
    public function getClaimants(): ClaimantsResponse
    {
        return $this->claimants;
    }

    protected function loadFromJson(array $json): void
    {
        if (isset($json['sponsor'])) $this->sponsor = $json['sponsor'];
        if (isset($json['amount'])) $this->amount = $json['amount'];
        if (isset($json['asset'])) {
            $parsedAsset = Asset::createFromCanonicalForm($json['asset']);
            if ($parsedAsset != null) {
                $this->asset = $parsedAsset;
            }
        }

        if (isset($json['claimants'])) {
            $this->claimants = new ClaimantsResponse();
            foreach ($json['claimants'] as $jsonClaimants) {
                $claimant = ClaimantResponse::fromJson($jsonClaimants);
                $this->claimants->add($claimant);
            }
        }

        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData): CreateClaimableBalanceOperationResponse
    {
        $result = new CreateClaimableBalanceOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}