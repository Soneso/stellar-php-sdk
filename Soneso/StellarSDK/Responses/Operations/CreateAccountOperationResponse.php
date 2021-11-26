<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

class CreateAccountOperationResponse extends OperationResponse
{
    private string $startingBalance;
    private string $funder;
    private ?string $funderMuxed = null;
    private ?string $funderMuxedId = null;
    private string $account;

    /**
     * @return string
     */
    public function getAccount(): string
    {
        return $this->account;
    }

    /**
     * @return string
     */
    public function getStartingBalance(): string
    {
        return $this->startingBalance;
    }

    /**
     * @return string
     */
    public function getFunder(): string
    {
        return $this->funder;
    }

    /**
     * @return string|null
     */
    public function getFunderMuxed(): ?string
    {
        return $this->funderMuxed;
    }

    /**
     * @return string|null
     */
    public function getFunderMuxedId(): ?string
    {
        return $this->funderMuxedId;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['starting_balance'])) $this->startingBalance = $json['starting_balance'];
        if (isset($json['funder'])) $this->funder = $json['funder'];
        if (isset($json['funder_muxed'])) $this->funderMuxed = $json['funder_muxed'];
        if (isset($json['funder_muxed_id'])) $this->funderMuxedId = $json['funder_muxed_id'];
        if (isset($json['account'])) $this->account = $json['account'];

        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : CreateAccountOperationResponse {
        $result = new CreateAccountOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}