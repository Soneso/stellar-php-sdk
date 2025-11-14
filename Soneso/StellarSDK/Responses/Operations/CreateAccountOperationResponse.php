<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

/**
 * Represents a create account operation response from Horizon API
 *
 * This response is returned when a create account operation creates and funds a new account.
 * Contains the starting balance, the new account address, and the funder account that created
 * it including optional multiplexed account information.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org Stellar developer docs Horizon Create Account Operation
 * @since 1.0.0
 */
class CreateAccountOperationResponse extends OperationResponse
{
    private string $startingBalance;
    private string $funder;
    private ?string $funderMuxed = null;
    private ?string $funderMuxedId = null;
    private string $account;

    /**
     * Gets the newly created account address
     *
     * @return string The account ID that was created
     */
    public function getAccount(): string
    {
        return $this->account;
    }

    /**
     * Gets the initial balance provided to the new account
     *
     * @return string The starting balance in lumens
     */
    public function getStartingBalance(): string
    {
        return $this->startingBalance;
    }

    /**
     * Gets the funder account address that created the new account
     *
     * @return string The account ID that funded the creation
     */
    public function getFunder(): string
    {
        return $this->funder;
    }

    /**
     * Gets the multiplexed funder account if applicable
     *
     * @return string|null The muxed funder account address or null
     */
    public function getFunderMuxed(): ?string
    {
        return $this->funderMuxed;
    }

    /**
     * Gets the multiplexed funder account ID if applicable
     *
     * @return string|null The muxed funder account ID or null
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