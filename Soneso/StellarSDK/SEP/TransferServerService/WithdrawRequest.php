<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

class WithdrawRequest
{
    /**
     * @var string $assetCode Code of the on-chain asset the user wants to withdraw.
     * The value passed must match one of the codes listed in the /info response's withdraw object.
     */
    public string $assetCode;

    /**
     * @var string $type Type of withdrawal. Can be: crypto, bank_account, cash, mobile,
     * bill_payment or other custom values. This field may be necessary
     * for the anchor to determine what KYC information is necessary to collect.
     */
    public string $type;

    /**
     * @var string|null $dest (Deprecated) The account that the user wants to withdraw their funds to.
     * This can be a crypto account, a bank account number, IBAN, mobile number,
     * or email address.
     */
    public ?string $dest = null;

    /**
     * @var string|null $destExtra (Deprecated, optional) Extra information to specify withdrawal location.
     * For crypto it may be a memo in addition to the dest address.
     * It can also be a routing number for a bank, a BIC, or the name of a
     * partner handling the withdrawal.
     */
    public ?string $destExtra = null;

    /**
     * @var string|null $account (optional) The Stellar or muxed account the client will use as the source
     * of the withdrawal payment to the anchor. If SEP-10 authentication is not
     * used, the anchor can use account to look up the user's KYC information.
     * Note that the account specified in this request could differ from the
     * account authenticated via SEP-10.
     */
    public ?string $account = null;

    /**
     * @var string|null $memo (optional) This field should only be used if SEP-10 authentication is not.
     * It was originally intended to distinguish users of the same Stellar account.
     * However if SEP-10 is supported, the anchor should use the sub value
     * included in the decoded SEP-10 JWT instead.
     */
    public ?string $memo = null;

    /**
     * @var string|null $memoType (deprecated, optional) Type of memo. One of text, id or hash.
     * Deprecated because memos used to identify users of the same
     * Stellar account should always be of type of id.
     */
    public ?string $memoType = null;

    /**
     * @var string|null $walletName (deprecated, optional) In communications / pages about the withdrawal,
     * anchor should display the wallet name to the user to explain where funds
     * are coming from. However, anchors should use client_domain
     * (for non-custodial) and sub value of JWT (for custodial) to determine
     * wallet information.
     */
    public ?string $walletName = null;

    /**
     * @var string|null $walletUrl (deprecated, optional) Anchor can show this to the user when referencing
     * the wallet involved in the withdrawal (ex. in the anchor's transaction
     * history). However, anchors should use client_domain (for non-custodial)
     * and sub value of JWT (for custodial) to determine wallet information.
     */
    public ?string $walletUrl = null;

    /**
     * @var string|null $lang (optional) (optional) Defaults to en if not specified or if the
     * specified language is not supported. Language code specified using
     * RFC 4646. error fields and other human readable messages in the
     * response should be in this language.
     */
    public ?string $lang = null;

    /**
     * @var string|null $onChangeCallback (optional) A URL that the anchor should POST a JSON message to when the
     * status property of the transaction created as a result of this request
     * changes. The JSON message should be identical to the response format
     * for the /transaction endpoint.
     */
    public ?string $onChangeCallback = null;

    /**
     * @var string|null $amount (optional) The amount of the asset the user would like to withdraw.
     * This field may be necessary for the anchor to determine what KYC
     * information is necessary to collect.
     */
    public ?string $amount = null;

    /**
     * @var string|null $countryCode (optional) The ISO 3166-1 alpha-3 code of the user's current address.
     * This field may be necessary for the anchor to determine what KYC
     * information is necessary to collect.
     */
    public ?string $countryCode = null;

    /**
     * @var string|null $refundMemo (optional) The memo the anchor must use when sending refund payments back
     * to the user. If not specified, the anchor should use the same memo used
     * by the user to send the original payment. If specified, refundMemoType
     * must also be specified.
     */
    public ?string $refundMemo = null;

    /**
     * @var string|null $refundMemoType (optional) The type of the refund_memo. Can be id, text, or hash.
     * If specified, refundMemo must also be specified.
     */
    public ?string $refundMemoType = null;

    /**
     * @var string|null $customerId (optional) id of an off-chain account (managed by the anchor) associated
     * with this user's Stellar account (identified by the JWT's sub field).
     * If the anchor supports SEP-12, the customer_id field should match the
     * SEP-12 customer's id. customer_id should be passed only when the
     * off-chain id is know to the client, but the relationship between this id
     * and the user's Stellar account is not known to the Anchor.
     */
    public ?string $customerId = null;

    /**
     * @var string|null $locationId (optional) id of the chosen location to pick up cash.
     */
    public ?string $locationId = null;

    /**
     * @var array<string,string>|null  (optional) can be used to provide extra fields for the request.
     * E.g. required fields from the /info endpoint that are not covered by the standard parameters.
     */
    public ?array $extraFields = null;

    /**
     * @var string|null $jwt jwt token previously received from the anchor via the SEP-10 authentication flow.
     */
    public ?string $jwt = null;

    /**
     * @param string $assetCode Code of the on-chain asset the user wants to withdraw.
     * The value passed must match one of the codes listed in the /info response's withdraw object.
     * @param string $type Type of withdrawal. Can be: crypto, bank_account, cash, mobile,
     * bill_payment or other custom values. This field may be necessary
     * for the anchor to determine what KYC information is necessary to collect.
     * @param string|null $dest (deprecated) The account that the user wants to withdraw their funds to.
     * This can be a crypto account, a bank account number, IBAN, mobile number, or email address.
     * @param string|null $destExtra (deprecated, optional) Extra information to specify withdrawal location.
     * For crypto it may be a memo in addition to the dest address.
     * It can also be a routing number for a bank, a BIC, or the name of a partner handling the withdrawal.
     * @param string|null $account (optional) The Stellar or muxed account the client will use as the source
     * of the withdrawal payment to the anchor. If SEP-10 authentication is not used, the anchor can use account to
     * look up the user's KYC information. Note that the account specified in this request could differ from the
     * account authenticated via SEP-10.
     * @param string|null $memo (optional) This field should only be used if SEP-10 authentication is not.
     * It was originally intended to distinguish users of the same Stellar account. However if SEP-10 is supported,
     * the anchor should use the sub value included in the decoded SEP-10 JWT instead.
     * @param string|null $memoType (deprecated, optional) Type of memo. One of text, id or hash.
     * Deprecated because memos used to identify users of the same Stellar account should always be of type of id.
     * @param string|null $walletName (deprecated, optional) In communications / pages about the withdrawal, anchor
     * should display the wallet name to the user to explain where funds are coming from. However, anchors should use
     * client_domain (for non-custodial) and sub value of JWT (for custodial) to determine wallet information.
     * @param string|null $walletUrl (deprecated, optional) Anchor can show this to the user when referencing
     * the wallet involved in the withdrawal (ex. in the anchor's transaction history). However, anchors should use
     * client_domain (for non-custodial) and sub value of JWT (for custodial) to determine wallet information.
     * @param string|null $lang (optional) Defaults to en if not specified or if the specified language is not
     * supported. Language code specified using RFC 4646. error fields and other human readable messages in the
     * response should be in this language.
     * @param string|null $onChangeCallback (optional) A URL that the anchor should POST a JSON message to when the
     * status property of the transaction created as a result of this request changes. The JSON message should be
     * identical to the response format for the /transaction endpoint.
     * @param string|null $amount (optional) The amount of the asset the user would like to withdraw. This field may be
     * necessary for the anchor to determine what KYC information is necessary to collect.
     * @param string|null $countryCode (optional) The ISO 3166-1 alpha-3 code of the user's current address.
     * This field may be necessary for the anchor to determine what KYC information is necessary to collect.
     * @param string|null $refundMemo (optional) The memo the anchor must use when sending refund payments back
     * to the user. If not specified, the anchor should use the same memo used by the user to send the original payment.
     * If specified, refundMemoType must also be specified.
     * @param string|null $refundMemoType (optional) The type of the refund_memo. Can be id, text, or hash.
     * If specified, refundMemo must also be specified.
     * @param string|null $customerId (optional) id of an off-chain account (managed by the anchor) associated
     * with this user's Stellar account (identified by the JWT's sub field). If the anchor supports SEP-12, the
     * customer_id field should match the SEP-12 customer's id. customer_id should be passed only when the
     * off-chain id is know to the client, but the relationship between this id and the user's Stellar account is
     * not known to the Anchor.
     * @param string|null $locationId (optional) id of the chosen location to pick up cash
     * @param array<string,string>|null $extraFields (optional) can be used to provide extra fields for the request.
     * E.g. required fields from the /info endpoint that are not covered by the standard parameters.
     * @param string|null $jwt jwt previously received from the anchor via the SEP-10 authentication flow
     */
    public function __construct(
        string $assetCode,
        string $type,
        ?string $dest = null,
        ?string $destExtra = null,
        ?string $account = null,
        ?string $memo = null,
        ?string $memoType = null,
        ?string $walletName = null,
        ?string $walletUrl = null,
        ?string $lang = null,
        ?string $onChangeCallback = null,
        ?string $amount = null,
        ?string $countryCode = null,
        ?string $refundMemo = null,
        ?string $refundMemoType = null,
        ?string $customerId = null,
        ?string $locationId = null,
        ?array $extraFields = null,
        ?string $jwt = null)
    {
        $this->assetCode = $assetCode;
        $this->type = $type;
        $this->dest = $dest;
        $this->destExtra = $destExtra;
        $this->account = $account;
        $this->memo = $memo;
        $this->memoType = $memoType;
        $this->walletName = $walletName;
        $this->walletUrl = $walletUrl;
        $this->lang = $lang;
        $this->onChangeCallback = $onChangeCallback;
        $this->amount = $amount;
        $this->countryCode = $countryCode;
        $this->refundMemo = $refundMemo;
        $this->refundMemoType = $refundMemoType;
        $this->customerId = $customerId;
        $this->locationId = $locationId;
        $this->extraFields = $extraFields;
        $this->jwt = $jwt;
    }


}