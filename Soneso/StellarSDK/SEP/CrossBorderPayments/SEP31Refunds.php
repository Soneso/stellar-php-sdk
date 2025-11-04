<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\CrossBorderPayments;

/**
 * Refund information for a cross-border payment transaction via SEP-31.
 *
 * This class aggregates all refund payments made back to the Sending Anchor,
 * including the total refunded amount, associated fees, and individual payment
 * details. It is used when a transaction is partially or fully refunded.
 *
 * @package Soneso\StellarSDK\SEP\CrossBorderPayments
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0031.md#refunds-object-schema
 * @see SEP31TransactionResponse
 * @see SEP31RefundPayment
 */
class SEP31Refunds
{

    /**
     * @var string $amountRefunded The total amount refunded to the Sending Anchor, in units of amount_in_asset.
     * If a full refund was issued, this amount should match amount_in.
     */
    public string $amountRefunded;

    /**
     * @var string $amountFee The total amount charged in fees for processing all refund payments, in units of amount_in_asset.
     * The sum of all fee values in the payments object list should equal this value.
     */
    public string $amountFee;

    /**
     * @var array<SEP31RefundPayment> $payments A list of objects containing information on the individual payments
     * made back to the Sending Anchor as refunds. The schema for these objects is defined in the section below.
     */
    public array $payments = array();

    /**
     * @param string $amountRefunded The total amount refunded to the Sending Anchor, in units of amount_in_asset.
     *  If a full refund was issued, this amount should match amount_in.
     * @param string $amountFee The total amount charged in fees for processing all refund payments, in units of amount_in_asset.
     *  The sum of all fee values in the payments object list should equal this value.
     * @param array<SEP31RefundPayment> $payments A list of objects containing information on the individual payments
     *  made back to the Sending Anchor as refunds. The schema for these objects is defined in the section below.
     */
    public function __construct(string $amountRefunded, string $amountFee, array $payments)
    {
        $this->amountRefunded = $amountRefunded;
        $this->amountFee = $amountFee;
        $this->payments = $payments;
    }

    /**
     * Constructs a new instance of SEP31Refunds by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP31Refunds the object containing the parsed data.
     */
    public static function fromJson(array $json): SEP31Refunds
    {
        $payments = array();
        foreach ($json['payments'] as $payment) {
            $payments[] = SEP31RefundPayment::fromJson($payment);
        }

        return new SEP31Refunds($json['amount_refunded'], $json['amount_fee'], $payments);

    }
}