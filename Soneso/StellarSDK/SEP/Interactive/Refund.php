<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\Responses\Response;

class Refund extends Response
{

    /// The total amount refunded to the user, in units of amount_in_asset.
    /// If a full refund was issued, this amount should match amount_in.
    public string $amountRefunded;

    /// The total amount charged in fees for processing all refund payments, in units of amount_in_asset.
    /// The sum of all fee values in the payments object list should equal this value.
    public string $amountFee;

    /// A list of objects containing information on the individual payments made back to the user as refunds.
    public array $payments = array();

    protected function loadFromJson(array $json) : void {
        if (isset($json['amount_refunded'])) $this->amountRefunded = $json['amount_refunded'];
        if (isset($json['amount_fee'])) $this->amountFee = $json['amount_fee'];
        if (isset($json['payments'])) {
            foreach ($json['payments'] as $payment) {
                array_push($this->payments, RefundPayment::fromJson($payment));
            }
        }
    }

    public static function fromJson(array $json) : Refund
    {
        $result = new Refund();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * @return string
     */
    public function getAmountRefunded(): string
    {
        return $this->amountRefunded;
    }

    /**
     * @param string $amountRefunded
     */
    public function setAmountRefunded(string $amountRefunded): void
    {
        $this->amountRefunded = $amountRefunded;
    }

    /**
     * @return string
     */
    public function getAmountFee(): string
    {
        return $this->amountFee;
    }

    /**
     * @param string $amountFee
     */
    public function setAmountFee(string $amountFee): void
    {
        $this->amountFee = $amountFee;
    }

    /**
     * @return array
     */
    public function getPayments(): array
    {
        return $this->payments;
    }

    /**
     * @param array $payments
     */
    public function setPayments(array $payments): void
    {
        $this->payments = $payments;
    }

}