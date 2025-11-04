<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

/**
 * Represents an end sponsoring future reserves operation response from Horizon API
 *
 * This operation terminates an active reserve sponsorship block initiated by a begin sponsoring
 * operation. It marks the end of the sponsored operations sequence. The account receiving sponsorship
 * must submit this operation to accept the sponsorship. Any ledger entries created between the begin
 * and end operations will have their reserves paid by the sponsoring account.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org/api/resources/operations/object/end-sponsoring-future-reserves Horizon End Sponsoring Future Reserves Operation
 */
class EndSponsoringFutureReservesOperationResponse extends OperationResponse
{
    private string $beginSponsor;
    private ?string $beginSponsorMuxed= null;
    private ?string $beginSponsorMuxedId = null;

    /**
     * Gets the account that initiated the sponsorship
     *
     * @return string The sponsor account ID from the begin operation
     */
    public function getBeginSponsor(): string
    {
        return $this->beginSponsor;
    }

    /**
     * Gets the multiplexed sponsor account if applicable
     *
     * @return string|null The muxed sponsor account address or null
     */
    public function getBeginSponsorMuxed(): ?string
    {
        return $this->beginSponsorMuxed;
    }

    /**
     * Gets the multiplexed sponsor account ID if applicable
     *
     * @return string|null The muxed sponsor account ID or null
     */
    public function getBeginSponsorMuxedId(): ?string
    {
        return $this->beginSponsorMuxedId;
    }


    protected function loadFromJson(array $json) : void {

        if (isset($json['begin_sponsor'])) $this->beginSponsor = $json['begin_sponsor'];
        if (isset($json['begin_sponsor_muxed'])) $this->beginSponsorMuxed = $json['begin_sponsor_muxed'];
        if (isset($json['begin_sponsor_muxed_id'])) $this->beginSponsorMuxedId = $json['begin_sponsor_muxed_id'];

        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : EndSponsoringFutureReservesOperationResponse {
        $result = new EndSponsoringFutureReservesOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}