<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\CrossBorderPayments;

/**
 * Individual refund payment details within a cross-border payment refund.
 *
 * This class represents a single refund payment transaction sent back to the
 * Sending Anchor, including the Stellar transaction hash, refunded amount,
 * and associated fee. Multiple refund payments may exist for a single transaction.
 *
 * @package Soneso\StellarSDK\SEP\CrossBorderPayments
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0031.md#refund-payment-object-schema
 * @see SEP31Refunds
 */
class SEP31RefundPayment
{

    /**
     * @var string $id The Stellar transaction hash of the transaction that included the refund payment.
     * This id is not guaranteed to be unique.
     */
    public string $id;

    /**
     * @var string $amount The amount sent back to the Sending Anchor for the payment identified by id,
     * in units of amount_in_asset.
     */
    public string $amount;

    /**
     * @var string $fee The amount charged as a fee for processing the refund, in units of amount_in_asset.
     */
    public string $fee;

    /**
     * @param string $id The Stellar transaction hash of the transaction that included the refund payment.
     *  This id is not guaranteed to be unique.
     * @param string $amount The amount sent back to the Sending Anchor for the payment identified by id,
     *  in units of amount_in_asset.
     * @param string $fee The amount charged as a fee for processing the refund, in units of amount_in_asset.
     */
    public function __construct(string $id, string $amount, string $fee)
    {
        $this->id = $id;
        $this->amount = $amount;
        $this->fee = $fee;
    }

    /**
     * Constructs a new instance of RefundPayment by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP31RefundPayment the object containing the parsed data.
     */
    public static function fromJson(array $json): SEP31RefundPayment
    {
        return new SEP31RefundPayment($json['id'], $json['amount'], $json['fee']);
    }
}