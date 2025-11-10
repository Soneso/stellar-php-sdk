<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\Responses\Response;

/**
 * Represents refund information for a SEP-24 transaction
 *
 * This class encapsulates refund details for deposit or withdrawal transactions
 * that have been partially or fully refunded. It includes the total refunded amount,
 * associated fees, and a breakdown of individual refund payments.
 *
 * Refunds may occur when a transaction fails, is cancelled, or when the anchor
 * needs to return funds to the user. The refund object provides full transparency
 * about the refund process including any fees charged for processing the refunds.
 *
 * @package Soneso\StellarSDK\SEP\Interactive
 * @see https://github.com/stellar/stellar-protocol/blob/v3.8.0/ecosystem/sep-0024.md SEP-24 Specification
 * @see SEP24Transaction For the parent transaction containing refunds
 * @see RefundPayment For individual refund payment details
 */
class Refund extends Response
{

    /**
     * @var string  $amountRefunded The total amount refunded to the user, in units of amount_in_asset.
     *  If a full refund was issued, this amount should match amount_in.
     */
    public string $amountRefunded;

    /**
     * @var string $amountFee The total amount charged in fees for processing all refund payments, in units of amount_in_asset.
     * The sum of all fee values in the payments object list should equal this value.
     */
    public string $amountFee;

    /**
     * @var array<RefundPayment> $payments A list of objects containing information on the individual payments made back to the user as refunds.
     */
    public array $payments = array();

    /**
     * Loads the needed data from the given data array.
     * @param array<array-key, mixed> $json the array containing the data to read from.
     * @return void
     */
    protected function loadFromJson(array $json) : void {
        if (isset($json['amount_refunded'])) $this->amountRefunded = $json['amount_refunded'];
        if (isset($json['amount_fee'])) $this->amountFee = $json['amount_fee'];
        if (isset($json['payments'])) {
            foreach ($json['payments'] as $payment) {
                $this->payments[] = RefundPayment::fromJson($payment);
            }
        }
    }

    /**
     * Constructs a new instance of Refund by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return Refund the object containing the parsed data.
     */
    public static function fromJson(array $json) : Refund
    {
        $result = new Refund();
        $result->loadFromJson($json);

        return $result;
    }

    /**
     * @return string The total amount refunded to the user, in units of amount_in_asset.
     *   If a full refund was issued, this amount should match amount_in.
     */
    public function getAmountRefunded(): string
    {
        return $this->amountRefunded;
    }

    /**
     * @param string $amountRefunded The total amount refunded to the user, in units of amount_in_asset.
     *    If a full refund was issued, this amount should match amount_in.
     */
    public function setAmountRefunded(string $amountRefunded): void
    {
        $this->amountRefunded = $amountRefunded;
    }

    /**
     * @return string The total amount charged in fees for processing all refund payments, in units of amount_in_asset.
     *  The sum of all fee values in the payments object list should equal this value.
     */
    public function getAmountFee(): string
    {
        return $this->amountFee;
    }

    /**
     * @param string $amountFee The total amount charged in fees for processing all refund payments, in units of amount_in_asset.
     *   The sum of all fee values in the payments object list should equal this value.
     */
    public function setAmountFee(string $amountFee): void
    {
        $this->amountFee = $amountFee;
    }

    /**
     * @return array<RefundPayment> A list of objects containing information on the individual payments made back to the user as refunds.
     */
    public function getPayments(): array
    {
        return $this->payments;
    }

    /**
     * @param array<RefundPayment> $payments A list of objects containing information on the individual payments made back to the user as refunds.
     */
    public function setPayments(array $payments): void
    {
        $this->payments = $payments;
    }

}