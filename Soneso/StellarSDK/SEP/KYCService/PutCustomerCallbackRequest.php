<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\KYCService;

/**
 * Request object for PUT /customer/callback endpoint.
 *
 * This endpoint allows wallets to register a callback URL with the anchor. The anchor will
 * POST to this URL when the customer's verification status changes. The callback URL replaces
 * any previously registered callback URL for the identified customer.
 *
 * Callbacks enable real-time status updates without requiring the wallet to poll the GET /customer
 * endpoint repeatedly. This is particularly useful for long-running verification processes.
 *
 * @package Soneso\StellarSDK\SEP\KYCService
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#customer-callback-put SEP-12 v1.15.0
 */
class PutCustomerCallbackRequest
{
    /**
     * @var string|null $url A callback URL that the SEP-12 server will POST to when the state of the account changes.
     */
    public ?string $url = null;

    /**
     * @var string|null $id The ID of the customer as returned in the response of a previous PUT request.
     * If the customer has not been registered, they do not yet have an id.
     */
    public ?string $id = null;

    /**
     * @var string|null $account The Stellar account ID used to identify this customer.
     * If many customers share the same Stellar account, the memo and memoType parameters should be included as well.
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
     * @var string|null $jwt jwt token previously received from the anchor via the SEP-10 authentication flow
     */
    public ?string $jwt = null;
}