<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\ClaimableBalances;

/**
 * Represents a claimant eligible to claim a claimable balance
 *
 * Contains the destination account ID and the predicate conditions that must be satisfied
 * before the claimant can successfully claim the balance.
 *
 * @package Soneso\StellarSDK\Responses\ClaimableBalances
 * @see ClaimableBalanceResponse For the parent claimable balance details
 * @see ClaimantPredicateResponse For the claim conditions
 * @since 1.0.0
 */
class ClaimantResponse
{
    private string $destination;
    private ClaimantPredicateResponse $predicate;

    /**
     * Gets the destination account ID that can claim this balance
     *
     * @return string The claimant account ID
     */
    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * Gets the predicate conditions for claiming this balance
     *
     * @return ClaimantPredicateResponse The claim predicate
     */
    public function getPredicate(): ClaimantPredicateResponse
    {
        return $this->predicate;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['destination'])) $this->destination = $json['destination'];
        if (isset($json['predicate'])) $this->predicate = ClaimantPredicateResponse::fromJson($json['predicate']);
    }

    public static function fromJson(array $json) : ClaimantResponse {
        $result = new ClaimantResponse();
        $result->loadFromJson($json);
        return $result;
    }
}