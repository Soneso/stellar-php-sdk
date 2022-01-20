<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\SEP\KYCService;

class PutCustomerCallbackRequest
{
    /// A callback URL that the SEP-12 server will POST to when the state of the account changes.
    public ?string $url = null;

    /// (optional) The ID of the customer as returned in the response of a previous PUT request.
    /// If the customer has not been registered, they do not yet have an id.
    public ?string $id = null;

    /// (optional) The Stellar account ID used to identify this customer.
    /// If many customers share the same Stellar account, the memo and memoType parameters should be included as well.
    public ?string $account = null;

    /// (optional) The memo used to create the customer record.
    /// (optional) the client-generated memo that uniquely identifies the customer. If a memo is present in the decoded SEP-10 JWT's sub value, it must match this parameter value. If a muxed account is used as the JWT's sub value, memos sent in requests must match the 64-bit integer subaccount ID of the muxed account.
    /// see: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#shared-omnibus-or-pooled-accounts
    public ?string $memo = null;

    /// (optional) The type of memo used to create the customer record.
    /// (deprecated, optional) type of memo. One of text, id or hash. Deprecated because memos should always be of type id, although anchors should continue to support this parameter for outdated clients. If hash, memo should be base64-encoded. If a memo is present in the decoded SEP-10 JWT's sub value, this parameter can be ignored.
    /// see: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#shared-omnibus-or-pooled-accounts
    public ?string $memoType = null;

    /// jwt previously received from the anchor via the SEP-10 authentication flow
    public ?string $jwt = null;
}