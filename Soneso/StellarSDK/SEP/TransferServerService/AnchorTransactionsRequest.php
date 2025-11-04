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
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md SEP-06 Specification
 * @see TransferServerService::transactions()
 * @see AnchorTransactionsResponse
 */
class AnchorTransactionsRequest
{

    /**
     * @var string $assetCode The code of the asset of interest. E.g. BTC, ETH, USD, INR, etc.
     */
    public string $assetCode;

    /**
     * @var string $account The stellar account ID involved in the transactions. If the service
     * requires SEP-10 authentication, this parameter must match the authenticated account.
     */
    public string $account;

    /**
     * @var DateTime|null $noOlderThan (optional) The response should contain transactions starting on or
     * after this date & time.
     */
    public ?DateTime $noOlderThan = null;

    /**
     * @var int|null $limit (optional) The response should contain at most limit transactions.
     */
    public ?int $limit = null;

    /**
     * @var string|null (optional) A list containing the desired transaction kinds.
     * The possible values are deposit, deposit-exchange, withdrawal and withdrawal-exchange.
     */
    public ?string $kind = null;

    /**
     * @var string|null $pagingId (optional) The response should contain transactions starting
     * prior to this ID (exclusive).
     */
    public ?string $pagingId = null;

    /**
     * @var string|null $lang (optional) Defaults to en if not specified or if the specified language
     *  is not supported. Language code specified using RFC 4646. Error fields and other human readable messages in
     * the response should be in this language.
     */
    public ?string $lang = null;

    /**
     * @var string|null $jwt jwt previously received from the anchor via the SEP-10 authentication flow
     */
    public ?string $jwt = null;

    /**
     * Constructor.
     *
     * @param string $assetCode The code of the asset of interest. E.g. BTC, ETH, USD, INR, etc.
     * @param string $account The stellar account ID involved in the transactions. If the service
     * requires SEP-10 authentication, this parameter must match the authenticated account.
     * @param DateTime|null $noOlderThan (optional) The response should contain transactions starting on or
     * after this date & time.
     * @param int|null $limit (optional) The response should contain at most limit transactions.
     * @param string|null $kind (optional) A list containing the desired transaction kinds. The possible values are
     * deposit, deposit-exchange, withdrawal and withdrawal-exchange.
     * @param string|null $pagingId (optional) The response should contain transactions starting prior to
     * this ID (exclusive).
     * @param string|null $lang (optional) Defaults to en if not specified or if the specified language
     * is not supported. Language code specified using RFC 4646. Error fields and other human readable messages in
     * the response should be in this language.
     * @param string|null $jwt jwt previously received from the anchor via the SEP-10 authentication flow
     */
    public function __construct(
        string $assetCode,
        string $account,
        ?DateTime $noOlderThan = null,
        ?int $limit = null,
        ?string $kind = null,
        ?string $pagingId = null,
        ?string $lang = null,
        ?string $jwt = null)
    {
        $this->assetCode = $assetCode;
        $this->account = $account;
        $this->noOlderThan = $noOlderThan;
        $this->limit = $limit;
        $this->kind = $kind;
        $this->pagingId = $pagingId;
        $this->lang = $lang;
        $this->jwt = $jwt;
    }
}