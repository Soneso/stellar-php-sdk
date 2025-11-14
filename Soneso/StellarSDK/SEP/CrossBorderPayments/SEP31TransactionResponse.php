<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\CrossBorderPayments;

/**
 * Details of a cross-border payment transaction retrieved via SEP-31.
 *
 * This class represents the full transaction state including status, amounts,
 * fees, refunds, and payment details. It is returned when querying a specific
 * transaction via GET /transactions/:id.
 *
 * Amount Formulas (when amount_in_asset equals amount_out_asset):
 * - amount_out = amount_in - amount_fee - refunds.amount_refunded - refunds.amount_fee
 * - When using quote_id: amount_in = quote.sell_amount and amount_out = quote.buy_amount
 * - Price calculation uses quote.price (not quote.total_price)
 *
 * @package Soneso\StellarSDK\SEP\CrossBorderPayments
 * @see https://github.com/stellar/stellar-protocol/blob/v3.1.0/ecosystem/sep-0031.md#transaction
 * @see CrossBorderPaymentsService::getTransaction()
 * @see SEP31FeeDetails
 * @see SEP31Refunds
 */
class SEP31TransactionResponse
{
    /**
     * @var string $id The ID returned from the POST /transactions request that created this transaction record.
     */
    public string $id;

    /**
     * @var string $status The status of the transaction.
     *
     * Possible status values:
     * - pending_sender: Awaiting payment from Sending Anchor
     * - pending_stellar: Transaction submitted to Stellar network but not confirmed
     * - pending_customer_info_update: KYC information needs updating (see SEP-12)
     * - pending_transaction_info_update: Transaction fields need updating (deprecated, use SEP-12)
     * - pending_receiver: Payment being processed by Receiving Anchor
     * - pending_external: Payment submitted to external network but not confirmed
     * - completed: Funds successfully delivered to Receiving Client
     * - refunded: Funds refunded to Sending Anchor (see refunds object for details)
     * - expired: Transaction abandoned or quote expired
     * - error: Catch-all for unspecified errors (check status_message for details)
     *
     * @see https://github.com/stellar/stellar-protocol/blob/v3.1.0/ecosystem/sep-0031.md#transaction-object
     * @see SEP31Refunds
     */
    public string $status;

    /**
     * @var int|null $statusEta (optional) The estimated number of seconds until a status change is expected.
     */
    public ?int $statusEta = null;

    /**
     * @var string|null $statusMessage (optional) A human-readable message describing the status of the transaction.
     */
    public ?string $statusMessage = null;

    /**
     * @var string|null $amountIn (optional) The amount of the Stellar asset received or to be received by the Receiving Anchor.
     * Excludes any fees charged after Receiving Anchor receives the funds. If a quote_id was used, the amount_in
     * should be equals to both: (i) the amount value used in the POST /transactions request; and (ii) the quote's
     * sell_amount.
     */
    public ?string $amountIn = null;

    /**
     * @var string|null $amountInAsset (optional) The asset received or to be received by the Receiving Anchor.
     * Must be present if quote_id or destination_asset was included in the POST /transactions request.
     * The value must be in SEP-38 Asset Identification Format.
     */
    public ?string $amountInAsset = null;

    /**
     * @var string|null $amountOut (optional) The amount sent or to be sent by the Receiving Anchor to the
     * Receiving Client. When using a destination_asset in the POST /transactions request, it's expected
     * that this value is only populated after the Receiving Anchor receives the incoming payment.
     * Should be equal to quote.buy_amount if a quote_id was used.
     */
    public ?string $amountOut = null;

    /**
     * @var string|null $amountOutAsset (optional) The asset delivered to the Receiving Client.
     * Must be present if quote_id or destination_asset was included in the POST /transactions request.
     * The value must be in SEP-38 Asset Identification Format.
     */
    public ?string $amountOutAsset = null;

    /**
     * @var string|null $amountFee (deprecated, optional) The amount of fee charged by the Receiving Anchor.
     * Should be equals quote.fee.total if a quote_id was used.
     */
    public ?string $amountFee = null;

    /**
     * @var string|null $amountFeeAsset (deprecated, optional) The asset in which fees are calculated in.
     * Must be present if quote_id or destination_asset was included in the POST /transactions request.
     * The value must be in SEP-38 Asset Identification Format. Should be equals quote.fee.asset if a quote_id was used.
     */
    public ?string $amountFeeAsset = null;

    /**
     * @var SEP31FeeDetails|null $feeDetails Description of fee charged by the anchor.
     * If quote_id is present, it should match the referenced quote's fee object.
     */
    public ?SEP31FeeDetails $feeDetails = null;

    /**
     * @var string|null $quoteId (optional) The ID of the quote used to create this transaction.
     * Should be present if a quote_id was included in the POST /transactions request.
     * Clients should be aware though that the quote_id may not be present in older implementations.
     */
    public ?string $quoteId = null;

    /**
     * @var string|null $stellarAccountId (optional) The Receiving Anchor's Stellar account that the Sending Anchor
     * will be making the payment to.
     */
    public ?string $stellarAccountId = null;

    /**
     * @var string|null $stellarMemoType (optional) The type of memo to attach to the Stellar payment: text, hash, or id.
     */
    public ?string $stellarMemoType = null;

    /**
     * @var string|null $stellarMemo (optional) The memo to attach to the Stellar payment.
     */
    public ?string $stellarMemo = null;

    /**
     * @var string|null $startedAt (optional) Start date and time of transaction. UTC ISO 8601 string
     */
    public ?string $startedAt = null;

    /**
     * @var string|null $updatedAt (optional) The date and time of transaction reaching the current status.
     * UTC ISO 8601 string
     */
    public ?string $updatedAt = null;

    /**
     * @var string|null $completedAt (optional) Completion date and time of transaction.
     */
    public ?string $completedAt = null;

    /**
     * @var string|null $stellarTransactionId (optional) The transaction_id on Stellar network of the transfer
     * that initiated the payment.
     */
    public ?string $stellarTransactionId = null;

    /**
     * @var string|null $externalTransactionId (optional) The ID of transaction on external network that completes
     * the payment into the receivers account.
     */
    public ?string $externalTransactionId = null;

    /**
     * @var bool|null $refunded (deprecated, optional) This field is deprecated in favor of the refunds object.
     * True if the transaction was refunded in full. False if the transaction was partially refunded or not refunded.
     * For more details about any refunds, see the refunds object.
     */
    public ?bool $refunded = null;

    /**
     * @var SEP31Refunds|null $refunds (optional) An object describing any on-chain refund
     * associated with this transaction.
     */
    public ?SEP31Refunds $refunds = null;

    /**
     * @var string|null $requiredInfoMessage (optional) A human-readable message indicating any errors that require
     * updated information from the sender.
     */
    public ?string $requiredInfoMessage = null;

    /**
     * @var array<array-key,mixed>|null $requiredInfoUpdates (optional) A set of fields that require update values from the
     * Sending Anchor, in the same format as described in GET /info. This field is only relevant when status
     * is pending_transaction_info_update.
     */
    public ?array $requiredInfoUpdates = null;

    /**
     * @param string $id The ID returned from the POST /transactions request that created this transaction record.
     * @param string $status The status of the transaction.
     * @param int|null $statusEta (optional) The estimated number of seconds until a status change is expected.
     * @param string|null $statusMessage (optional) A human-readable message describing the status of the transaction.
     * @param string|null $amountIn (optional) The amount of the Stellar asset received or to be received by the Receiving Anchor.
     * Excludes any fees charged after Receiving Anchor receives the funds. If a quote_id was used, the amount_in
     * should be equals to both: (i) the amount value used in the POST /transactions request; and (ii) the quote's sell_amount.
     * @param string|null $amountInAsset (optional) The asset received or to be received by the Receiving Anchor.
     * Must be present if quote_id or destination_asset was included in the POST /transactions request.
     * The value must be in SEP-38 Asset Identification Format.
     * @param string|null $amountOut (optional) The amount sent or to be sent by the Receiving Anchor to the
     * Receiving Client. When using a destination_asset in the POST /transactions request, it's expected that this
     * value is only populated after the Receiving Anchor receives the incoming payment. Should be equals to
     * quote.buy_amount if a quote_id was used.
     * @param string|null $amountOutAsset (optional) The asset delivered to the Receiving Client. Must be present if
     * quote_id or destination_asset was included in the POST /transactions request. The value must be in
     * SEP-38 Asset Identification Format.
     * @param string|null $amountFee (deprecated, optional) The amount of fee charged by the Receiving Anchor.
     * Should be equals quote.fee.total if a quote_id was used.
     * @param string|null $amountFeeAsset (deprecated, optional) The asset in which fees are calculated in.
     * Must be present if quote_id or destination_asset was included in the POST /transactions request.
     * The value must be in SEP-38 Asset Identification Format. Should be equals quote.fee.asset if a quote_id was used.
     * @param SEP31FeeDetails|null $feeDetails Description of fee charged by the anchor. The schema for this object is
     * defined in the Fee Details Object Schema section below. If quote_id is present, it should match the
     * referenced quote's fee object.
     * @param string|null $quoteId (optional) The ID of the quote used to create this transaction. Should be present
     * if a quote_id was included in the POST /transactions request. Clients should be aware though that the quote_id
     * may not be present in older implementations.
     * @param string|null $stellarAccountId (optional) The Receiving Anchor's Stellar account that the Sending Anchor
     * will be making the payment to.
     * @param string|null $stellarMemoType (optional) The type of memo to attach to the Stellar payment: text, hash, or id.
     * @param string|null $stellarMemo (optional) The memo to attach to the Stellar payment.
     * @param string|null $startedAt (optional) Start date and time of transaction. UTC ISO 8601 string
     * @param string|null $updatedAt (optional) The date and time of transaction reaching the current status. UTC ISO 8601 string
     * @param string|null $completedAt (optional) Completion date and time of transaction. UTC ISO 8601 string
     * @param string|null $stellarTransactionId (optional) The transaction_id on Stellar network of the transfer
     * that initiated the payment.
     * @param string|null $externalTransactionId (optional) The ID of transaction on external network that completes
     * the payment into the receivers account.
     * @param bool|null $refunded (deprecated, optional) This field is deprecated in favor of the refunds object.
     * True if the transaction was refunded in full. False if the transaction was partially refunded or not refunded.
     * For more details about any refunds, see refunds.
     * @param SEP31Refunds|null $refunds (optional) An object describing any on-chain refund associated with this transaction.
     * @param string|null $requiredInfoMessage (optional) A human-readable message indicating any errors that require
     * updated information from the sender.
     * @param array<array-key,mixed>|null $requiredInfoUpdates (optional) A set of fields that require update values from the
     * Sending Anchor, in the same format as described in GET /info. This field is only relevant when status
     * is pending_transaction_info_update.
     */
    public function __construct(
        string $id,
        string $status,
        ?int $statusEta = null,
        ?string $statusMessage = null,
        ?string $amountIn = null,
        ?string $amountInAsset = null,
        ?string $amountOut = null,
        ?string $amountOutAsset = null,
        ?string $amountFee = null,
        ?string $amountFeeAsset = null,
        ?SEP31FeeDetails $feeDetails = null,
        ?string $quoteId = null,
        ?string $stellarAccountId = null,
        ?string $stellarMemoType = null,
        ?string $stellarMemo = null,
        ?string $startedAt = null,
        ?string $updatedAt = null,
        ?string $completedAt = null,
        ?string $stellarTransactionId = null,
        ?string $externalTransactionId = null,
        ?bool $refunded = null,
        ?SEP31Refunds $refunds = null,
        ?string $requiredInfoMessage = null,
        ?array $requiredInfoUpdates = null,
    )
    {
        $this->id = $id;
        $this->status = $status;
        $this->statusEta = $statusEta;
        $this->statusMessage = $statusMessage;
        $this->amountIn = $amountIn;
        $this->amountInAsset = $amountInAsset;
        $this->amountOut = $amountOut;
        $this->amountOutAsset = $amountOutAsset;
        $this->amountFee = $amountFee;
        $this->amountFeeAsset = $amountFeeAsset;
        $this->feeDetails = $feeDetails;
        $this->quoteId = $quoteId;
        $this->stellarAccountId = $stellarAccountId;
        $this->stellarMemoType = $stellarMemoType;
        $this->stellarMemo = $stellarMemo;
        $this->startedAt = $startedAt;
        $this->updatedAt = $updatedAt;
        $this->completedAt = $completedAt;
        $this->stellarTransactionId = $stellarTransactionId;
        $this->externalTransactionId = $externalTransactionId;
        $this->refunded = $refunded;
        $this->refunds = $refunds;
        $this->requiredInfoMessage = $requiredInfoMessage;
        $this->requiredInfoUpdates = $requiredInfoUpdates;
    }


    /**
     * Constructs a new instance of SEP31TransactionResponse by using the given data.
     * @param array<array-key, mixed> $jsonData the data to construct the object from.
     * @return SEP31TransactionResponse the object containing the parsed data.
     */
    public static function fromJson(array $jsonData) : SEP31TransactionResponse
    {
        $json = $jsonData;
        if (isset($json['transaction'])) {
            $json = $json['transaction'];
        }

        $result = new SEP31TransactionResponse(id: $json['id'], status: $json['status']);

        if (isset($json['status_eta'])) $result->statusEta = $json['status_eta'];
        if (isset($json['status_message'])) $result->statusMessage = $json['status_message'];
        if (isset($json['amount_in'])) $result->amountIn = $json['amount_in'];
        if (isset($json['amount_in_asset'])) $result->amountInAsset = $json['amount_in_asset'];
        if (isset($json['amount_out'])) $result->amountOut = $json['amount_out'];
        if (isset($json['amount_out_asset'])) $result->amountOutAsset = $json['amount_out_asset'];
        if (isset($json['amount_fee'])) $result->amountFee = $json['amount_fee'];
        if (isset($json['amount_fee_asset'])) $result->amountFeeAsset = $json['amount_fee_asset'];
        if (isset($json['fee_details'])) $result->feeDetails = SEP31FeeDetails::fromJson($json['fee_details']);
        if (isset($json['quote_id'])) $result->quoteId = $json['quote_id'];
        if (isset($json['stellar_account_id'])) $result->stellarAccountId = $json['stellar_account_id'];
        if (isset($json['stellar_memo_type'])) $result->stellarMemoType = $json['stellar_memo_type'];
        if (isset($json['stellar_memo'])) $result->stellarMemo = $json['stellar_memo'];
        if (isset($json['started_at'])) $result->startedAt = $json['started_at'];
        if (isset($json['updated_at'])) $result->updatedAt = $json['updated_at'];
        if (isset($json['completed_at'])) $result->completedAt = $json['completed_at'];
        if (isset($json['stellar_transaction_id'])) $result->stellarTransactionId = $json['stellar_transaction_id'];
        if (isset($json['external_transaction_id'])) $result->externalTransactionId = $json['external_transaction_id'];
        if (isset($json['refunded'])) $result->refunded = $json['refunded'];
        if (isset($json['refunds'])) $result->refunds = SEP31Refunds::fromJson($json['refunds']);
        if (isset($json['required_info_message'])) $result->requiredInfoMessage = $json['required_info_message'];
        if (isset($json['required_info_updates'])) $result->requiredInfoUpdates = $json['required_info_updates'];

        return $result;

    }
}