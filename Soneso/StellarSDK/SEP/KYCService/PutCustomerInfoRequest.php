<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\KYCService;

use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;

class PutCustomerInfoRequest
{
    /**
     * @var string|null $id The id value returned from a previous call to this endpoint. If specified, no other parameter is required.
     */
    public ?string $id = null;

    /**
     * @var string|null $account (deprecated) This field should match the sub value of the decoded SEP-10 JWT.
     */
    public ?string $account = null;

    /**
     * @var string|null $memo the client-generated memo that uniquely identifies the customer. If a memo is present in the decoded SEP-10 JWT's sub value, it must match this parameter value. If a muxed account is used as the JWT's sub value, memos sent in requests must match the 64-bit integer subaccount ID of the muxed account.
     * see: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#shared-omnibus-or-pooled-accounts
     */
    public ?string $memo = null;

    /**
     * @var string|null $memoType (deprecated) type of memo. One of text, id or hash. Deprecated because memos should always be of type id, although anchors should continue to support this parameter for outdated clients. If hash, memo should be base64-encoded. If a memo is present in the decoded SEP-10 JWT's sub value, this parameter can be ignored.
     * see: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#shared-omnibus-or-pooled-accounts
     */
    public ?string $memoType = null;

    /**
     * @var string|null $type the type of action the customer is being KYCd for. See the Type Specification here:
     * https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#type-specification
     */
    public ?string $type = null;

    /**
     * @var StandardKYCFields|null kyc data.
     */
    public ?StandardKYCFields $KYCFields = null;

    /**
     * @var array<array-key,mixed>|null $customFiles Custom fields that you can use for transmission.
     */
    public ?array $customFields = null; // [key (string) => value (string)]

    /**
     * @var array<array-key,string> |null $customFiles Custom files that you can use for transmission (fieldname, value-bytes)
     */
    public ?array $customFiles = null;

    /**
     * @var string|null $jwt jwt token previously received from the anchor via the SEP-10 authentication flow
     */
    public ?string $jwt = null;

}