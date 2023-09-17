<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\Responses\Response;

class RefundPayment extends Response
{

    /// The payment ID that can be used to identify the refund payment.
    /// This is either a Stellar transaction hash or an off-chain payment identifier,
    /// such as a reference number provided to the user when the refund was initiated.
    /// This id is not guaranteed to be unique.
    public string $id;

    /// stellar or external.
    public string $idType;

    /// The amount sent back to the user for the payment identified by id, in units of amount_in_asset.
    public string $amount;

    /// The amount charged as a fee for processing the refund, in units of amount_in_asset.
    public string $fee;

    protected function loadFromJson(array $json) : void {
        if (isset($json['id'])) $this->id = $json['id'];
        if (isset($json['id_type'])) $this->idType = $json['id_type'];
        if (isset($json['amount'])) $this->amount = $json['amount'];
        if (isset($json['fee'])) $this->fee = $json['fee'];
    }

    public static function fromJson(array $json) : RefundPayment
    {
        $result = new RefundPayment();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getIdType(): string
    {
        return $this->idType;
    }

    /**
     * @param string $idType
     */
    public function setIdType(string $idType): void
    {
        $this->idType = $idType;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @param string $amount
     */
    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getFee(): string
    {
        return $this->fee;
    }

    /**
     * @param string $fee
     */
    public function setFee(string $fee): void
    {
        $this->fee = $fee;
    }
}