<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

class DepositRequest
{
    /// jwt token previously received from the anchor via the SEP-10 authentication flow
    public string $jwt;

    /// The code of the asset the user is wanting to deposit with the anchor. Ex BTC,ETH,USD,INR,etc
    public string $assetCode;

    /// The stellar account ID of the user that wants to deposit. This is where the asset token will be sent.
    public string $account;

    /// (optional) type of memo that anchor should attach to the Stellar payment transaction, one of text, id or hash
    public ?string $memoType = null;

    /// (optional) value of memo to attach to transaction, for hash this should be base64-encoded
    public ?string $memo = null;

    /// (optional) Email address of depositor. If desired, an anchor can use this to send email updates to the user about the deposit.
    public ?string $emailAddress = null;

    /// (optional) Type of deposit. If the anchor supports multiple deposit methods (e.g. SEPA or SWIFT), the wallet should specify type. This field may be necessary for the anchor to determine which KYC fields to collect.
    public ?string $type = null;

    /// (optional) In communications / pages about the deposit, anchor should display the wallet name to the user to explain where funds are going.
    public ?string $walletName = null;

    /// (optional) Anchor should link to this when notifying the user that the transaction has completed.
    public ?string $walletUrl = null;

    /// (optional) Defaults to en. Language code specified using ISO 639-1. error fields in the response should be in this language.
    public ?string $lang = null;

    /// (optional) A URL that the anchor should POST a JSON message to when the status property of the transaction created as a result of this request changes. The JSON message should be identical to the response format for the /transaction endpoint.
    public ?string $onChangeCallback = null;

    /// (optional) The amount of the asset the user would like to deposit with the anchor. This field may be necessary for the anchor to determine what KYC information is necessary to collect.
    public ?string $amount= null;

    ///  (optional) The ISO 3166-1 alpha-3 code of the user's current address. This field may be necessary for the anchor to determine what KYC information is necessary to collect.
    public ?string $countryCode = null;

    /// (optional) true if the client supports receiving deposit transactions as a claimable balance, false otherwise
    public ?string $claimableBalanceSupported = null;
}