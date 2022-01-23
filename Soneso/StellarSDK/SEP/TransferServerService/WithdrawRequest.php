<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

class WithdrawRequest
{
    /// jwt token previously received from the anchor via the SEP-10 authentication flow
    public string $jwt;

    /// Type of withdrawal. Can be: crypto, bank_account, cash, mobile, bill_payment or other custom values
    public string $type;

    /// Code of the asset the user wants to withdraw. The value passed must match one of the codes listed in the /info response's withdraw object.
    public string $assetCode;

    /// The account that the user wants to withdraw their funds to. This can be a crypto account, a bank account number, IBAN, mobile number, or email address.
    public string $dest;

    /// (optional) Extra information to specify withdrawal location. For crypto it may be a memo in addition to the dest address. It can also be a routing number for a bank, a BIC, or the name of a partner handling the withdrawal.
    public ?string $destExtra = null;

    /// (optional) The stellar account ID of the user that wants to do the withdrawal. This is only needed if the anchor requires KYC information for withdrawal. The anchor can use account to look up the user's KYC information.
    public ?string $account = null;

    /// (optional) A wallet will send this to uniquely identify a user if the wallet has multiple users sharing one Stellar account. The anchor can use this along with account to look up the user's KYC info.
    public ?string $memo = null;

    /// (optional) Type of memo. One of text, id or hash.
    public ?string $memoType = null;

    /// (optional) In communications / pages about the withdrawal, anchor should display the wallet name to the user to explain where funds are coming from.
    public ?string $walletName = null;

    /// (optional) Anchor can show this to the user when referencing the wallet involved in the withdrawal (ex. in the anchor's transaction history).
    public ?string $walletUrl = null;

    /// (optional) Defaults to en. Language code specified using ISO 639-1. error fields in the response should be in this language.
    public ?string $lang = null;

    /// (optional) A URL that the anchor should POST a JSON message to when the status property of the transaction created as a result of this request changes. The JSON message should be identical to the response format for the /transaction endpoint.
    public ?string $onChangeCallback = null;

    /// (optional) The amount of the asset the user would like to deposit with the anchor. This field may be necessary for the anchor to determine what KYC information is necessary to collect.
    public ?string $amount = null;

    /// (optional) The ISO 3166-1 alpha-3 code of the user's current address. This field may be necessary for the anchor to determine what KYC information is necessary to collect.
    public ?string $countryCode = null;
}