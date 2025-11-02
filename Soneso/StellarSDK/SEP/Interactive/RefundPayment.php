<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\Responses\Response;

/**
 * Represents an individual refund payment within a SEP-24 transaction refund
 *
 * This class encapsulates details of a single refund payment that is part of a
 * larger transaction refund. When an anchor refunds a transaction, they may split
 * the refund into multiple payments. Each payment is tracked separately with its
 * own identifier, amount, and fee.
 *
 * The payment ID can be either a Stellar transaction hash (for on-chain refunds)
 * or an external payment reference (for off-chain refunds like bank transfers).
 * This allows tracking of refunds regardless of the payment method used.
 *
 * @package Soneso\StellarSDK\SEP\Interactive
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md SEP-24 Specification
 * @see Refund For the parent refund object containing payment list
 * @see SEP24Transaction For the parent transaction
 */
class RefundPayment extends Response
{

    /**
     * @var string $id The payment ID that can be used to identify the refund payment.
     * This is either a Stellar transaction hash or an off-chain payment identifier,
     * such as a reference number provided to the user when the refund was initiated.
     * This id is not guaranteed to be unique.
     */
    public string $id;

    /**
     * @var string $idType possible values: 'stellar' or 'external'.
     */
    public string $idType;

    /**
     * @var string $amount The amount sent back to the user for the payment identified by id, in units of amount_in_asset.
     */
    public string $amount;

    /**
     * @var string $fee The amount charged as a fee for processing the refund, in units of amount_in_asset.
     */
    public string $fee;

    /**
     * Loads the needed data from the given data array.
     * @param array<array-key, mixed> $json the array containing the data to read from.
     * @return void
     */
    protected function loadFromJson(array $json) : void {
        if (isset($json['id'])) $this->id = $json['id'];
        if (isset($json['id_type'])) $this->idType = $json['id_type'];
        if (isset($json['amount'])) $this->amount = $json['amount'];
        if (isset($json['fee'])) $this->fee = $json['fee'];
    }

    /**
     * Constructs a new instance of RefundPayment by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return RefundPayment the object containing the parsed data.
     */
    public static function fromJson(array $json) : RefundPayment
    {
        $result = new RefundPayment();
        $result->loadFromJson($json);

        return $result;
    }

    /**
     * @return string The payment ID that can be used to identify the refund payment.
     *  This is either a Stellar transaction hash or an off-chain payment identifier,
     *  such as a reference number provided to the user when the refund was initiated.
     *  This id is not guaranteed to be unique.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id The payment ID that can be used to identify the refund payment.
     *   This is either a Stellar transaction hash or an off-chain payment identifier,
     *   such as a reference number provided to the user when the refund was initiated.
     *   This id is not guaranteed to be unique.
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string possible values: 'stellar' or 'external'.
     */
    public function getIdType(): string
    {
        return $this->idType;
    }

    /**
     * @param string $idType possible values: 'stellar' or 'external'.
     */
    public function setIdType(string $idType): void
    {
        $this->idType = $idType;
    }

    /**
     * @return string The amount sent back to the user for the payment identified by id, in units of amount_in_asset.
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @param string $amount The amount sent back to the user for the payment identified by id, in units of amount_in_asset.
     */
    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return string The amount charged as a fee for processing the refund, in units of amount_in_asset.
     */
    public function getFee(): string
    {
        return $this->fee;
    }

    /**
     * @param string $fee The amount charged as a fee for processing the refund, in units of amount_in_asset.
     */
    public function setFee(string $fee): void
    {
        $this->fee = $fee;
    }
}