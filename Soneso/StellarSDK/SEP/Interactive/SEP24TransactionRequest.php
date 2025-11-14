<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

/**
 * Request parameters for querying a specific SEP-24 transaction
 *
 * This class contains the parameters needed to query for a specific
 * transaction using the /transaction endpoint. At least one identifier
 * (id, stellarTransactionId, or externalTransactionId) must be provided.
 *
 * @package Soneso\StellarSDK\SEP\Interactive
 * @see https://github.com/stellar/stellar-protocol/blob/v3.8.0/ecosystem/sep-0024.md SEP-24 Specification
 * @see InteractiveService::transaction() For executing transaction queries
 * @see SEP24TransactionResponse For the response structure
 */
class SEP24TransactionRequest {

    /**
     * @var string $jwt jwt token previously received from the anchor via the SEP-10 authentication flow
     */
    public string $jwt;

    /**
     * @var string|null $id The id of the sep-24 transaction as obtained from the anchor.
     */
    public ?string $id = null;

    /**
     * @var string|null $stellarTransactionId The stellar transaction id of the transaction.
     */
    public ?string $stellarTransactionId = null;

    /**
     * @var string|null $externalTransactionId (optional) The external transaction id of the transaction.
     */
    public ?string $externalTransactionId = null;

    /**
     * @var string|null $lang (optional) Defaults to en if not specified or if the specified language is not supported.
     * Language code specified using RFC 4646 which means it can also accept locale in the format en-US.
     */
    public ?string $lang = null;
}