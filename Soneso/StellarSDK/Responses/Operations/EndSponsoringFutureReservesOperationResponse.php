<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

class EndSponsoringFutureReservesOperationResponse extends OperationResponse
{
    private string $beginSponsor;
    private ?string $beginSponsorMuxed= null;
    private ?string $beginSponsorMuxedId = null;

    /**
     * @return string
     */
    public function getBeginSponsor(): string
    {
        return $this->beginSponsor;
    }

    /**
     * @return string|null
     */
    public function getBeginSponsorMuxed(): ?string
    {
        return $this->beginSponsorMuxed;
    }

    /**
     * @return string|null
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