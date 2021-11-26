<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\ClaimableBalances;

class ClaimantResponse
{
    private string $destination;
    private ClaimantPredicateResponse $predicate;

    /**
     * @return string
     */
    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * @return ClaimantPredicateResponse
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