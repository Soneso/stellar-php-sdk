<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Soneso\StellarSDK\Responses\Response;

/**
 * Represents a single refund payment made to the user.
 *
 * Contains details about an individual refund payment including the payment ID,
 * type, amount, and associated fee.
 *
 * @package Soneso\StellarSDK\SEP\TransferServerService
 * @see https://github.com/stellar/stellar-protocol/blob/v4.3.0/ecosystem/sep-0006.md SEP-06 Specification
 * @see TransactionRefunds
 */
class TransactionRefundPayment
{

    /**
     * @param string $id The payment ID that can be used to identify the refund payment.
     * This is either a Stellar transaction hash or an off-chain payment identifier,
     * such as a reference number provided to the user when the refund was initiated.
     * This id is not guaranteed to be unique.
     * @param string $idType stellar or external.
     * @param string $amount The amount sent back to the user for the payment identified by id,
     * in units of amount_in_asset.
     * @param string $fee The amount charged as a fee for processing the refund, in units of amount_in_asset.
     */
    public function __construct(
        public string $id,
        public string $idType,
        public string $amount,
        public string $fee,
    ) {
    }


    /**
     * Constructs a new instance of TransactionRefundPayment by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return TransactionRefundPayment the object containing the parsed data.
     */
    public static function fromJson(array $json) : TransactionRefundPayment
    {
        return new TransactionRefundPayment(
            $json['id'],
            $json['id_type'],
            $json['amount'],
            $json['fee']
        );
    }
}