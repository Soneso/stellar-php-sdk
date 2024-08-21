<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

class DepositExchangeRequest
{
    /**
     * @var string $destinationAsset The code of the on-chain asset the user wants to get from the Anchor
     * after doing an off-chain deposit. The value passed must match one of the
     * codes listed in the /info response's deposit-exchange object.
     */
    public string $destinationAsset;

    /**
     * @var string $sourceAsset The off-chain asset the Anchor will receive from the user. The value must
     * match one of the asset values included in a SEP-38
     * GET /prices?buy_asset=stellar:<destination_asset>:<asset_issuer> response
     * using SEP-38 Asset Identification Format.
     */
    public string $sourceAsset;

    /**
     * @var string $amount The amount of the source_asset the user would like to deposit to the
     * anchor's off-chain account. This field may be necessary for the anchor
     * to determine what KYC information is necessary to collect. Should be
     * equals to quote.sell_amount if a quote_id was used.
     */
    public string $amount;

    /**
     * @var string $account The stellar or muxed account ID of the user that wants to deposit.
     * This is where the asset token will be sent. Note that the account
     * specified in this request could differ from the account authenticated
     * via SEP-10.
     */
    public string $account;

    /**
     * @var string|null $quoteId (optional) The id returned from a SEP-38 POST /quote response.
     * If this parameter is provided and the user delivers the deposit funds
     * to the Anchor before the quote expiration, the Anchor should respect the
     * conversion rate agreed in that quote. If the values of destination_asset,
     * source_asset and amount conflict with the ones used to create the
     * SEP-38 quote, this request should be rejected with a 400.
     */
    public ?string $quoteId = null;


    /**
     * @var string|null $memoType (optional) Type of memo that the anchor should attach to the Stellar
     * payment transaction, one of text, id or hash.
     */
    public ?string $memoType = null;

    /**
     * @var string|null $memo (optional) (optional) Value of memo to attach to transaction, for hash
     * this should be base64-encoded. Because a memo can be specified in the
     * SEP-10 JWT for Shared Accounts, this field as well as memo_type can
     * be different than the values included in the SEP-10 JWT. For example,
     * a client application could use the value passed for this parameter
     * as a reference number used to match payments made to account.
     */
    public ?string $memo = null;


    /**
     * @var string|null $emailAddress (optional) Email address of depositor. If desired, an anchor can use
     * this to send email updates to the user about the deposit.
     */
    public ?string $emailAddress = null;


    /**
     * @var string|null $type (optional) Type of deposit. If the anchor supports multiple deposit
     * methods (e.g. SEPA or SWIFT), the wallet should specify type. This field
     * may be necessary for the anchor to determine which KYC fields to collect.
     */
    public ?string $type = null;

    /**
     * @var string|null $walletName (deprecated, optional) In communications / pages about the deposit,
     * anchor should display the wallet name to the user to explain where funds
     * are going. However, anchors should use client_domain (for non-custodial)
     * and sub value of JWT (for custodial) to determine wallet information.
     */
    public ?string $walletName = null;

    /**
     * @var string|null $walletUrl (deprecated,optional) Anchor should link to this when notifying the user
     * that the transaction has completed. However, anchors should use
     * client_domain (for non-custodial) and sub value of JWT (for custodial)
     * to determine wallet information.
     */
    public ?string $walletUrl = null;

    /**
     * @var string|null $lang (optional) Defaults to en. Language code specified using ISO 639-1.
     * error fields in the response should be in this language.
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
     * @var string|null $countryCode (optional) The ISO 3166-1 alpha-3 code of the user's current address.
     * This field may be necessary for the anchor to determine what KYC
     * information is necessary to collect.
     */
    public ?string $countryCode = null;

    /**
     * @var string|null $claimableBalanceSupported (optional) id of an off-chain account (managed by the anchor) associated
     * with this user's Stellar account (identified by the JWT's sub field).
     * If the anchor supports SEP-12, the customerId field should match the
     * SEP-12 customer's id. customerId should be passed only when the off-chain
     * id is know to the client, but the relationship between this id and the
     * user's Stellar account is not known to the Anchor.
     */
    public ?string $claimableBalanceSupported = null;

    /**
     * @var string|null $customerId (optional) id of an off-chain account (managed by the anchor) associated
     * with this user's Stellar account (identified by the JWT's sub field).
     * If the anchor supports SEP-12, the customerId field should match the
     * SEP-12 customer's id. customerId should be passed only when the off-chain
     * id is know to the client, but the relationship between this id and the
     * user's Stellar account is not known to the Anchor.
     */
    public ?string $customerId = null;

    /**
     * @var string|null $locationId (optional) id of the chosen location to drop off cash.
     */
    public ?string $locationId = null;

    /**
     * @var array<string,string>|null  (optional) can be used to provide extra fields for the request.
     * E.g. required fields from the /info endpoint that are not covered by the standard parameters.
     */
    public ?array $extraFields = null;

    /**
     * @var string|null $jwt jwt token previously received from the anchor via the SEP-10 authentication flow
     */
    public ?string $jwt = null;

    /**
     * @param string $destinationAsset The code of the on-chain asset the user wants to get from the Anchor
     * after doing an off-chain deposit. The value passed must match one of the
     * codes listed in the /info response's deposit-exchange object.
     * @param string $sourceAsset The off-chain asset the Anchor will receive from the user. The value must
     * match one of the asset values included in a SEP-38
     * GET /prices?buy_asset=stellar:<destination_asset>:<asset_issuer> response
     * using SEP-38 Asset Identification Format.
     * @param string $amount The amount of the source_asset the user would like to deposit to the
     * anchor's off-chain account. This field may be necessary for the anchor
     * to determine what KYC information is necessary to collect. Should be
     * equals to quote.sell_amount if a quote_id was used.
     * @param string $account The stellar or muxed account ID of the user that wants to deposit.
     * This is where the asset token will be sent. Note that the account
     * specified in this request could differ from the account authenticated
     * via SEP-10.
     * @param string|null $quoteId (optional) The id returned from a SEP-38 POST /quote response.
     * If this parameter is provided and the user delivers the deposit funds
     * to the Anchor before the quote expiration, the Anchor should respect the
     * conversion rate agreed in that quote. If the values of destination_asset,
     * source_asset and amount conflict with the ones used to create the
     * SEP-38 quote, this request should be rejected with a 400.
     * @param string|null $memoType (optional) Type of memo that the anchor should attach to the
     * Stellar payment transaction, one of text, id or hash.
     * @param string|null $memo (optional) (optional) Value of memo to attach to transaction, for hash
     * this should be base64-encoded. Because a memo can be specified in the
     * SEP-10 JWT for Shared Accounts, this field as well as memo_type can
     * be different than the values included in the SEP-10 JWT. For example,
     * a client application could use the value passed for this parameter
     * as a reference number used to match payments made to account.
     * @param string|null $emailAddress (optional) Email address of depositor. If desired, an anchor can use
     *  this to send email updates to the user about the deposit.
     * @param string|null $type (optional) Type of deposit. If the anchor supports multiple deposit
     *  methods (e.g. SEPA or SWIFT), the wallet should specify type. This field
     *  may be necessary for the anchor to determine which KYC fields to collect.
     * @param string|null $walletName (deprecated, optional) In communications / pages about the deposit,
     *  anchor should display the wallet name to the user to explain where funds
     *  are going. However, anchors should use client_domain (for non-custodial)
     *  and sub value of JWT (for custodial) to determine wallet information.
     * @param string|null $walletUrl (deprecated,optional) Anchor should link to this when notifying the user
     *  that the transaction has completed. However, anchors should use
     *  client_domain (for non-custodial) and sub value of JWT (for custodial) to determine wallet information.
     * @param string|null $lang (optional) Defaults to en. Language code specified using ISO 639-1.
     *  error fields in the response should be in this language.
     * @param string|null $onChangeCallback (optional) A URL that the anchor should POST a JSON message to when the
     *  status property of the transaction created as a result of this request
     *  changes. The JSON message should be identical to the response format for the /transaction endpoint.
     * @param string|null $countryCode (optional) The ISO 3166-1 alpha-3 code of the user's current address.
     *  This field may be necessary for the anchor to determine what KYC information is necessary to collect.
     * @param string|null $claimableBalanceSupported (optional) true if the client supports receiving deposit transactions as
     *  a claimable balance, false otherwise.
     * @param string|null $customerId (optional) id of an off-chain account (managed by the anchor) associated
     *  with this user's Stellar account (identified by the JWT's sub field). If the anchor supports SEP-12,
     *  the customerId field should match the SEP-12 customer's id. customerId should be passed only when the off-chain
     *  id is know to the client, but the relationship between this id and the user's Stellar account is not known
     *  to the Anchor.
     * @param string|null $locationId (optional) id of the chosen location to drop off cash.
     * @param array<string,string>|null $extraFields (optional) can be used to provide extra fields for the request.
     * E.g. required fields from the /info endpoint that are not covered by the standard parameters.
     * @param string|null $jwt jwt token previously received from the anchor via the SEP-10 authentication flow.
     */
    public function __construct(
        string $destinationAsset,
        string $sourceAsset,
        string $amount,
        string $account,
        ?string $quoteId = null,
        ?string $memoType = null,
        ?string $memo = null,
        ?string $emailAddress = null,
        ?string $type = null,
        ?string $walletName = null,
        ?string $walletUrl = null,
        ?string $lang = null,
        ?string $onChangeCallback = null,
        ?string $countryCode = null,
        ?string $claimableBalanceSupported = null,
        ?string $customerId = null,
        ?string $locationId = null,
        ?array $extraFields = null,
        ?string $jwt = null)
    {
        $this->destinationAsset = $destinationAsset;
        $this->sourceAsset = $sourceAsset;
        $this->amount = $amount;
        $this->account = $account;
        $this->quoteId = $quoteId;
        $this->memoType = $memoType;
        $this->memo = $memo;
        $this->emailAddress = $emailAddress;
        $this->type = $type;
        $this->walletName = $walletName;
        $this->walletUrl = $walletUrl;
        $this->lang = $lang;
        $this->onChangeCallback = $onChangeCallback;
        $this->countryCode = $countryCode;
        $this->claimableBalanceSupported = $claimableBalanceSupported;
        $this->customerId = $customerId;
        $this->locationId = $locationId;
        $this->extraFields = $extraFields;
        $this->jwt = $jwt;
    }
}