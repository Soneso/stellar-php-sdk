<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

/**
 * Represents a begin sponsoring future reserves operation response from Horizon API
 *
 * This operation initiates reserve sponsorship, allowing one account to pay the base reserves for
 * ledger entries created by another account. The sponsor covers the reserve costs for subsequent
 * operations until an end sponsoring operation is encountered. This enables creating accounts and
 * ledger entries for users without requiring them to hold XLM for reserves.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org Stellar developer docs Horizon Begin Sponsoring Future Reserves Operation
 */
class BeginSponsoringFutureReservesOperationResponse extends OperationResponse
{

    private string $sponsoredId;

    /**
     * Gets the account ID receiving sponsorship
     *
     * @return string The sponsored account ID
     */
    public function getSponsoredId(): string
    {
        return $this->sponsoredId;
    }

    protected function loadFromJson(array $json): void
    {
        if (isset($json['sponsored_id'])) $this->sponsoredId = $json['sponsored_id'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData): BeginSponsoringFutureReservesOperationResponse
    {
        $result = new BeginSponsoringFutureReservesOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}