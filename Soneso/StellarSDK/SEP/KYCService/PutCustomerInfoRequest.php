<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\KYCService;

use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;

class PutCustomerInfoRequest
{
    /// (optional) The id value returned from a previous call to this endpoint. If specified, no other parameter is required.
    public ?string $id = null;

    /// (optional) The Stellar account ID to upload KYC data for. If specified, id should not be specified.
    /// (deprecated, optional) This field should match the sub value of the decoded SEP-10 JWT.
    public ?string $account = null;

    /// (optional) Uniquely identifies individual customers in schemes where multiple customers share one Stellar address (ex. SEP-31). If included, the KYC data will only apply to all requests that include this memo.
    /// (optional) the client-generated memo that uniquely identifies the customer. If a memo is present in the decoded SEP-10 JWT's sub value, it must match this parameter value. If a muxed account is used as the JWT's sub value, memos sent in requests must match the 64-bit integer subaccount ID of the muxed account.
    /// see: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#shared-omnibus-or-pooled-accounts
    public ?string $memo = null;

    /// (optional) type of memo. One of text, id or hash. If hash, memo should be base64-encoded.
    /// (deprecated, optional) type of memo. One of text, id or hash. Deprecated because memos should always be of type id, although anchors should continue to support this parameter for outdated clients. If hash, memo should be base64-encoded. If a memo is present in the decoded SEP-10 JWT's sub value, this parameter can be ignored.
    /// see: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#shared-omnibus-or-pooled-accounts
    public ?string $memoType = null;

    /// (optional) the type of action the customer is being KYCd for. See the Type Specification here:
    /// https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#type-specifica
    public ?string $type = null;

    public ?StandardKYCFields $KYCFields = null;

    /// Custom fields that you can use for transmission (fieldname,value)
    public ?array $customFields = null; // [key (string) => value (string)]

    /// Custom files that you can use for transmission (fieldname, value)
    public ?array $customFiles = null; // [key (string) => value (string - bytes)]

    /// jwt previously received from the anchor via the SEP-10 authentication flow
    public ?string $jwt = null;

}