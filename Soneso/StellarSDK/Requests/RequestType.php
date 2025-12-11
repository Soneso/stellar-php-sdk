<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Requests;

/**
 * Request type constants for internal request handling and response parsing.
 *
 * This class defines string constants that identify the type of HTTP request being made to
 * Horizon or SEP service endpoints. The RequestType is used internally by the ResponseHandler
 * to determine which response class to instantiate and how to parse the JSON response data.
 *
 * Request types are organized by category:
 * - Horizon Core: ROOT, HEALTH, FEE_STATS
 * - Horizon Resources: Single resource and paginated list endpoints
 * - SEP-10: Authentication challenge
 * - SEP-12: Customer KYC operations
 * - SEP-06: Transfer server operations
 * - SEP-24: Interactive anchor operations
 * - Federation: Federation protocol lookups
 *
 * @package Soneso\StellarSDK\Requests
 * @see ResponseHandler For usage of these constants in response parsing
 * @since 1.0.0
 */
class RequestType
{
    /** @var string Root endpoint for Horizon instance information */
    public const ROOT = "root";

    /** @var string Health check endpoint for Horizon instance status */
    public const HEALTH = "health";

    /** @var string Fee statistics endpoint for network fee data */
    public const FEE_STATS = "fee_stats";

    /** @var string Order book endpoint for trading pair data */
    public const ORDER_BOOK = "order_book";

    /** @var string Single account detail endpoint */
    public const SINGLE_ACCOUNT = "account_single";

    /** @var string Account data value endpoint for specific data entries */
    public const ACCOUNT_DATA_VALUE = "account_data_value";

    /** @var string Paginated accounts list endpoint */
    public const ACCOUNTS_PAGE = "accounts_page";

    /** @var string Paginated assets list endpoint */
    public const ASSETS_PAGE = "assets_page";

    /** @var string Single ledger detail endpoint */
    public const SINGLE_LEDGER = "ledger_single";

    /** @var string Paginated ledgers list endpoint */
    public const LEDGERS_PAGE = "ledgers_page";

    /** @var string Single transaction detail endpoint */
    public const SINGLE_TRANSACTION = "transaction_single";

    /** @var string Paginated transactions list endpoint */
    public const TRANSACTIONS_PAGE = "transactions_page";

    /** @var string Single trade detail endpoint */
    public const SINGLE_TRADE = "trade_single";

    /** @var string Paginated trades list endpoint */
    public const TRADES_PAGE = "trades_page";

    /** @var string Paginated trade aggregations endpoint */
    public const TRADE_AGGREGATIONS_PAGE = "trade_aggregation_page";

    /** @var string Single claimable balance detail endpoint */
    public const SINGLE_CLAIMABLE_BALANCE = "claimable_balance_single";

    /** @var string Paginated claimable balances list endpoint */
    public const CLAIMABLE_BALANCES_PAGE = "claimable_balances_page";

    /** @var string Single offer detail endpoint */
    public const SINGLE_OFFER = "offer_single";

    /** @var string Paginated offers list endpoint */
    public const OFFERS_PAGE = "offers_page";

    /** @var string Single liquidity pool detail endpoint */
    public const SINGLE_LIQUIDITY_POOL= "liquidity_pool_single";

    /** @var string Paginated liquidity pools list endpoint */
    public const LIQUIDITY_POOLS_PAGE = "liquidity_pools_page";

    /** @var string Paginated payment paths endpoint */
    public const PATHS_PAGE = "paths_page";

    /** @var string Single operation detail endpoint */
    public const SINGLE_OPERATION = "operation_single";

    /** @var string Paginated operations list endpoint */
    public const OPERATIONS_PAGE = "operations_page";

    /** @var string Paginated effects list endpoint */
    public const EFFECTS_PAGE = "effects_page";

    /** @var string Transaction submission endpoint */
    public const SUBMIT_TRANSACTION = "submit_transaction";

    /** @var string Async transaction submission endpoint */
    public const SUBMIT_ASYNC_TRANSACTION = "submit_async_transaction";

    /** @var string Federation protocol lookup endpoint */
    public const FEDERATION = "federation";

    /** @var string SEP-10 authentication challenge endpoint */
    public const CHALLENGE = "challenge";

    /** @var string SEP-45 contract authentication challenge endpoint */
    public const CONTRACT_CHALLENGE = "contract_challenge";

    /** @var string SEP-12 GET customer info endpoint */
    public const GET_CUSTOMER_INFO = "get_customer_info";

    /** @var string SEP-12 PUT customer info endpoint */
    public const PUT_CUSTOMER_INFO = "put_customer_info";

    /** @var string SEP-12 POST customer file upload endpoint */
    public const POST_CUSTOMER_FILE = "post_customer_file";

    /** @var string SEP-12 GET customer files endpoint */
    public const GET_CUSTOMER_FILES = "get_customer_files";

    /** @var string SEP-12 PUT customer verification endpoint */
    public const PUT_CUSTOMER_VERIFICATION = "put_customer_verification";

    /** @var string SEP-06 transfer server info endpoint */
    public const ANCHOR_INFO = "anchor_info";

    /** @var string SEP-06 deposit initiation endpoint */
    public const ANCHOR_DEPOSIT = "anchor_deposit";

    /** @var string SEP-06 withdrawal initiation endpoint */
    public const ANCHOR_WITHDRAW = "anchor_withdraw";

    /** @var string SEP-06 fee query endpoint */
    public const ANCHOR_FEE = "anchor_fee";

    /** @var string SEP-06 transactions list endpoint */
    public const ANCHOR_TRANSACTIONS = "anchor_transactions";

    /** @var string SEP-06 single transaction detail endpoint */
    public const ANCHOR_TRANSACTION = "anchor_transaction";

    /** @var string SEP-24 interactive anchor info endpoint */
    public const SEP24_INFO = "sep24_info";

    /** @var string SEP-24 interactive anchor fee endpoint */
    public const SEP24_FEE = "sep24_fee";

    /** @var string SEP-24 interactive anchor deposit/withdraw initiation endpoint */
    public const SEP24_POST = "sep24_post";

    /** @var string SEP-24 transactions list endpoint */
    public const SEP24_TRANSACTIONS = "sep24_transactions";

    /** @var string SEP-24 single transaction detail endpoint */
    public const SEP24_TRANSACTION = "sep24_transaction";
}