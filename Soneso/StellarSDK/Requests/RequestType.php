<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Requests;

class RequestType
{
    public const ROOT = "root";
    public const FEE_STATS = "fee_stats";
    public const ORDER_BOOK = "order_book";
    public const SINGLE_ACCOUNT = "account_single";
    public const ACCOUNTS_PAGE = "accounts_page";
    public const ASSETS_PAGE = "assets_page";
    public const SINGLE_LEDGER = "ledger_single";
    public const LEDGERS_PAGE = "ledgers_page";
    public const SINGLE_TRANSACTION = "transaction_single";
    public const TRANSACTIONS_PAGE = "transactions_page";
    public const SINGLE_TRADE = "trade_single";
    public const TRADES_PAGE = "trades_page";
    public const TRADE_AGGREGATIONS_PAGE = "trade_aggregation_page";
    public const SINGLE_CLAIMABLE_BALANCE = "claimable_balance_single";
    public const CLAIMABLE_BALANCES_PAGE = "claimable_balances_page";
    public const SINGLE_OFFER = "offer_single";
    public const OFFERS_PAGE = "offers_page";
    public const SINGLE_LIQUIDITY_POOL= "liquidity_pool_single";
    public const LIQUIDITY_POOLS_PAGE = "liquidity_pools_page";
    public const PATHS_PAGE = "paths_page";
    public const SINGLE_OPERATION = "operation_single";
    public const OPERATIONS_PAGE = "operations_page";
    public const EFFECTS_PAGE = "effects_page";
    public const SUBMIT_TRANSACTION = "submit_transaction";
    public const FEDERATION = "federation";
    public const CHALLENGE = "challenge";
    public const GET_CUSTOMER_INFO = "get_customer_info";
    public const PUT_CUSTOMER_INFO = "put_customer_info";
    public const PUT_CUSTOMER_VERIFICATION = "put_customer_verification";
    public const ANCHOR_INFO = "anchor_info";
    public const ANCHOR_DEPOSIT = "anchor_deposit";
    public const ANCHOR_WITHDRAW = "anchor_withdraw";
    public const ANCHOR_FEE = "anchor_fee";
    public const ANCHOR_TRANSACTIONS = "anchor_transactions";
    public const ANCHOR_TRANSACTION = "anchor_transaction";
}