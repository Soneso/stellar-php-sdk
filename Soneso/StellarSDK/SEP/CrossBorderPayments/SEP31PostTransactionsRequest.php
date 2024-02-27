<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\CrossBorderPayments;

class SEP31PostTransactionsRequest
{
    /**
     * @var float $amount Amount of the Stellar asset to send to the Receiving Anchor.
     */
    public float $amount;

    /**
     * @var string $assetCode Code of the asset the Sending Anchor intends to send. This must match one of the
     * entries listed in the receiving anchor's GET /info endpoint.
     */
    public string $assetCode;

    /**
     * @var string|null $assetIssuer (optional) The issuer of the Stellar asset the Sending Anchor intends to send.
     * If not specified, the asset sent must be issued by the Receiving Anchor.
     */
    public ?string $assetIssuer = null;

    /**
     * @var string|null $destinationAsset (optional) The off-chain asset the Receiving Anchor will deliver to the
     * Receiving Client. The value must match one of the asset values included in a SEP-38
     * GET /prices?sell_asset=stellar:<asset_code>:<asset_issuer> response using SEP-38 Asset Identification Format.
     * If neither this field nor quote_id are set, it's assumed that Sending Anchor Asset Conversions was used.
     */
    public ?string $destinationAsset = null;

    /**
     * @var string|null $quoteId (optional) The id returned from a SEP-38 POST /quote response.
     * If this attribute is specified, the values for the fields defined above must match the values
     * associated with the quote.
     */
    public ?string $quoteId = null;

    /**
     * @var string|null $senderId (optional) The ID included in the SEP-12 PUT /customer response for the
     * Sending Client. Required if the Receiving Anchor requires SEP-12 KYC on the Sending Client.
     */
    public ?string $senderId = null;

    /**
     * @var string|null $receiverId (optional) The ID included in the SEP-12 PUT /customer response for
     * the Receiving Client. Required if the Receiving Anchor requires SEP-12 KYC on the Receiving Client.
     */
    public ?string $receiverId = null;

    /**
     * @var array<array-key, mixed>|null $fields (deprecated, optional) An object containing the values requested
     * by the Receiving Anchor in the GET /info endpoint. Pass SEP-9 fields via SEP-12 PUT /customer instead.
     */
    public ?array $fields = null;

    /**
     * @var string|null $lang (optional) Defaults to en. Language code specified using ISO 639-1. Any human-readable
     * error codes or field descriptions should be returned in this language.
     */
    public ?string $lang = null;

    /**
     * @var string|null $refundMemo (optional) The memo the Receiving Anchor must use when sending refund payments
     * back to the Sending Anchor. If not specified, the Receiving Anchor should use the same memo the Sending Anchor
     * used to send the original payment. If specified, refund_memo_type must also be specified.
     */
    public ?string $refundMemo = null;

    /**
     * @var string|null $refundMemoType (optional) The type of the refund_memo. Can be id, text, or hash. See the
     * memos documentation for more information. If specified, refund_memo must also be specified.
     */
    public ?string $refundMemoType = null;

    /**
     * @param float $amount Amount of the Stellar asset to send to the Receiving Anchor.
     * @param string $assetCode Code of the asset the Sending Anchor intends to send. This must match one of the
     *  entries listed in the receiving anchor's GET /info endpoint.
     * @param string|null $assetIssuer The issuer of the Stellar asset the Sending Anchor intends to send.
     *  If not specified, the asset sent must be issued by the Receiving Anchor.
     * @param string|null $destinationAsset The off-chain asset the Receiving Anchor will deliver to the
     *  Receiving Client. The value must match one of the asset values included in a SEP-38
     *  GET /prices?sell_asset=stellar:<asset_code>:<asset_issuer> response using SEP-38 Asset Identification Format.
     *  If neither this field nor quote_id are set, it's assumed that Sending Anchor Asset Conversions was used.
     * @param string|null $quoteId The id returned from a SEP-38 POST /quote response.
     *  If this attribute is specified, the values for the fields defined above must match the values
     *  associated with the quote.
     * @param string|null $senderId The ID included in the SEP-12 PUT /customer response for the
     *  Sending Client. Required if the Receiving Anchor requires SEP-12 KYC on the Sending Client.
     * @param string|null $receiverId The ID included in the SEP-12 PUT /customer response for
     *  the Receiving Client. Required if the Receiving Anchor requires SEP-12 KYC on the Receiving Client.
     * @param array<array-key, mixed>|null $fields (deprecated, optional) An object containing the values requested by
     * the Receiving Anchor in the GET /info endpoint. Pass SEP-9 fields via SEP-12 PUT /customer instead.
     * @param string|null $lang defaults to en. Language code specified using ISO 639-1. Any human-readable
     *  error codes or field descriptions should be returned in this language.
     * @param string|null $refundMemo The memo the Receiving Anchor must use when sending refund payments
     *  back to the Sending Anchor. If not specified, the Receiving Anchor should use the same memo the Sending Anchor
     *  used to send the original payment. If specified, refund_memo_type must also be specified.
     * @param string|null $refundMemoType The type of the refund_memo. Can be id, text, or hash. See the
     *  memos documentation for more information. If specified, refund_memo must also be specified.
     */
    public function __construct(
        float $amount,
        string $assetCode,
        ?string $assetIssuer = null,
        ?string $destinationAsset = null,
        ?string $quoteId = null,
        ?string $senderId = null,
        ?string $receiverId = null,
        ?array $fields = null,
        ?string $lang = null,
        ?string $refundMemo = null,
        ?string $refundMemoType = null
    )
    {
        $this->amount = $amount;
        $this->assetCode = $assetCode;
        $this->assetIssuer = $assetIssuer;
        $this->destinationAsset = $destinationAsset;
        $this->quoteId = $quoteId;
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
        $this->lang = $lang;
        $this->refundMemo = $refundMemo;
        $this->refundMemoType = $refundMemoType;
    }

    /**
     * @return array<array-key, mixed> json array containing the request data.
     */
    public function toJson() : array {

        /**
         * @var array<array-key, mixed> $result
         */
        $result = [
            'amount' => $this->amount,
            'asset_code' => $this->assetCode,
        ];

        if ($this->assetIssuer !== null) {
            $result['asset_issuer'] = $this->assetIssuer;
        }
        if ($this->destinationAsset !== null) {
            $result['destination_asset'] = $this->destinationAsset;
        }
        if ($this->quoteId !== null) {
            $result['quote_id'] = $this->quoteId;
        }
        if ($this->senderId !== null) {
            $result['sender_id'] = $this->senderId;
        }
        if ($this->receiverId !== null) {
            $result['receiver_id'] = $this->receiverId;
        }
        if ($this->fields !== null) {
            $result['fields'] = $this->fields;
        }
        if ($this->lang !== null) {
            $result['lang'] = $this->lang;
        }
        if ($this->refundMemo !== null) {
            $result['refund_memo'] = $this->refundMemo;
        }
        if ($this->refundMemoType !== null) {
            $result['refund_memo_type'] = $this->refundMemoType;
        }

        return $result;
    }
}