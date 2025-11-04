<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\CrossBorderPayments;

/**
 * Response from initiating a cross-border payment transaction via SEP-31.
 *
 * This class represents the response received from a POST /transactions request,
 * containing the transaction ID and Stellar payment details required to send
 * the payment to the Receiving Anchor.
 *
 * @package Soneso\StellarSDK\SEP\CrossBorderPayments
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0031.md#post-transactions
 * @see CrossBorderPaymentsService::postTransactions()
 * @see SEP31PostTransactionsRequest
 */
class SEP31PostTransactionsResponse
{
    /**
     * @var string $id The persistent identifier to check the status of this payment transaction.
     */
    public string $id;

    /**
     * @var string|null $stellarAccountId (optional) The Stellar account to send payment to.
     */
    public ?string $stellarAccountId = null;

    /**
     * @var string|null $stellarMemoType (optional) The type of memo to attach to the Stellar payment (text, hash, or id).
     */
    public ?string $stellarMemoType = null;

    /**
     * @var string|null $stellarMemo (optional) The memo to attach to the Stellar payment.
     */
    public ?string $stellarMemo = null;

    /**
     * @param string $id The persistent identifier to check the status of this payment transaction.
     * @param string|null $stellarAccountId (optional) The Stellar account to send payment to.
     * @param string|null $stellarMemoType (optional) The type of memo to attach to the Stellar payment (text, hash, or id).
     * @param string|null $stellarMemo (optional) The memo to attach to the Stellar payment.
     */
    public function __construct(
        string $id,
        ?string $stellarAccountId = null,
        ?string $stellarMemoType = null,
        ?string $stellarMemo = null)
    {
        $this->id = $id;
        $this->stellarAccountId = $stellarAccountId;
        $this->stellarMemoType = $stellarMemoType;
        $this->stellarMemo = $stellarMemo;
    }

    /**
     * Constructs a new instance of SEP31PostTransactionsResponse by using the given data.
     *
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP31PostTransactionsResponse the object containing the parsed data.
     */
    public static function fromJson(array $json) : SEP31PostTransactionsResponse
    {

        $result = new SEP31PostTransactionsResponse($json['id']);

        if (isset($json['stellar_account_id'])) $result->stellarAccountId = $json['stellar_account_id'];
        if (isset($json['stellar_memo_type'])) $result->stellarMemoType = $json['stellar_memo_type'];
        if (isset($json['stellar_memo'])) $result->stellarMemo = $json['stellar_memo'];

        return $result;

    }

}