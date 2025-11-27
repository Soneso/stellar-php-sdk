<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use DateTime;

/**
 * Request parameters for querying transaction history via SEP-06.
 *
 * Encapsulates parameters for retrieving a list of deposit and withdrawal
 * transactions processed by the anchor. Supports filtering by asset, account,
 * transaction kind, and time range, plus pagination for large result sets.
 *
 * Required fields are assetCode and account. Optional filters enable precise
 * querying of transaction subsets. Results can be paginated using pagingId.
 *
 * @package Soneso\StellarSDK\SEP\TransferServerService
 * @see https://github.com/stellar/stellar-protocol/blob/v4.3.0/ecosystem/sep-0006.md SEP-06 Specification
 * @see TransferServerService::transactions()
 * @see AnchorTransactionsResponse
 */
class AnchorTransactionsRequest
{

    /**
     * @param string $assetCode The code of the asset of interest. E.g. BTC, ETH, USD, INR, etc.
     * @param string $account The stellar account ID involved in the transactions. If the service
     * requires SEP-10 authentication, this parameter must match the authenticated account.
     * @param DateTime|null $noOlderThan The response should contain transactions starting on or
     * after this date & time.
     * @param int|null $limit The response should contain at most limit transactions.
     * @param string|null $kind A list containing the desired transaction kinds. The possible values are
     * deposit, deposit-exchange, withdrawal and withdrawal-exchange.
     * @param string|null $pagingId The response should contain transactions starting prior to
     * this ID (exclusive).
     * @param string|null $lang Defaults to en if not specified or if the specified language
     * is not supported. Language code specified using RFC 4646. Error fields and other human readable messages in
     * the response should be in this language.
     * @param string|null $jwt jwt previously received from the anchor via the SEP-10 authentication flow
     */
    public function __construct(
        public string $assetCode,
        public string $account,
        public ?DateTime $noOlderThan = null,
        public ?int $limit = null,
        public ?string $kind = null,
        public ?string $pagingId = null,
        public ?string $lang = null,
        public ?string $jwt = null,
    ) {
    }
}