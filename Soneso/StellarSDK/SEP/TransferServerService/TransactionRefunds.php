<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

/**
 * Describes the overall refund information for a transaction.
 *
 * Contains the total refunded amount, total fees charged for refunds,
 * and a list of individual refund payments.
 *
 * @package Soneso\StellarSDK\SEP\TransferServerService
 * @see https://github.com/stellar/stellar-protocol/blob/v4.3.0/ecosystem/sep-0006.md SEP-06 Specification
 * @see TransactionRefundPayment
 */
class TransactionRefunds
{

    /**
     * @var string $amountRefunded The total amount refunded to the user, in units of amount_in_asset.
     * If a full refund was issued, this amount should match amount_in.
     */
    public string $amountRefunded;

    /**
     * @var string $amountFee The total amount charged in fees for processing all refund payments,
     * in units of amount_in_asset. The sum of all fee values in the payments object list should equal this value.
     */
    public string $amountFee;

    /**
     * @var array<TransactionRefundPayment> $payments A list of objects containing information on the individual
     * payments made back to the user as refunds.
     */
    public array $payments;

    /**
     * @param string $amountRefunded The total amount refunded to the user, in units of amount_in_asset.
     *  If a full refund was issued, this amount should match amount_in.
     * @param string $amountFee The total amount charged in fees for processing all refund payments,
     *  in units of amount_in_asset. The sum of all fee values in the payments object list should equal this value.
     * @param array<TransactionRefundPayment> $payments A list of objects containing information on the individual
     *  payments made back to the user as refunds.
     */
    public function __construct(string $amountRefunded, string $amountFee, array $payments)
    {
        $this->amountRefunded = $amountRefunded;
        $this->amountFee = $amountFee;
        $this->payments = $payments;
    }


    /**
     * Constructs a new instance of TransactionRefunds by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return TransactionRefunds the object containing the parsed data.
     */
    public static function fromJson(array $json) : TransactionRefunds
    {
        /**
         * @var array<TransactionRefundPayment> $payments
         */
        $payments = array();
        foreach ($json['payments'] as $detail) {
            $payments[] = TransactionRefundPayment::fromJson($detail);
        }

        return new TransactionRefunds($json['amount_refunded'], $json['amount_fee'], $payments);
    }
}