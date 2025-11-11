<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * Response of the getTransactions request.
 *
 * @package Soneso\StellarSDK\Soroban\Responses
 * @see https://developers.stellar.org/docs/data/rpc/api-reference/methods/getTransactions
 */
class GetTransactionsResponse extends SorobanRpcResponse
{
    /**
     * @var int|null $latestLedger The sequence number of the latest ledger known to Soroban RPC at the time it
     * handled the request.
     */
    public ?int $latestLedger = null;

    /**
     * @var int|null $latestLedgerCloseTimestamp The unix timestamp of the close time of the latest ledger known to
     * Soroban RPC at the time it handled the request.
     */
    public ?int $latestLedgerCloseTimestamp = null;

    /**
     * @var int|null $oldestLedger The sequence number of the oldest ledger ingested by Soroban RPC at the time
     * it handled the request.
     */
    public ?int $oldestLedger = null;

    /**
     * @var int|null $oldestLedgerCloseTimestamp The unix timestamp of the close time of the oldest ledger ingested
     * by Soroban RPC at the time it handled the request.
     */
    public ?int $oldestLedgerCloseTimestamp = null;

    /**
     * @var string|null $cursor for pagination.
     */
    public ?string $cursor = null;

    /**
     * @var array<TransactionInfo>|null transactions.
     */
    public ?array $transactions = null;

    /**
     * Creates an instance from JSON-RPC response data
     *
     * @param array<string,mixed> $json The JSON response data
     * @return static The created instance
     */
    public static function fromJson(array $json): GetTransactionsResponse
    {
        $result = new GetTransactionsResponse($json);
        if (isset($json['result'])) {
            if (isset($json['result']['transactions'])) {
                $result->transactions = array();
                foreach ($json['result']['transactions'] as $jsonValue) {
                    $value = TransactionInfo::fromJson($jsonValue);
                    array_push($result->transactions, $value);
                }
            }
            if (isset($json['result']['latestLedger'])) {
                $result->latestLedger = $json['result']['latestLedger'];
            }
            if (isset($json['result']['latestLedgerCloseTimestamp'])) {
                $result->latestLedgerCloseTimestamp = $json['result']['latestLedgerCloseTimestamp'];
            }
            if (isset($json['result']['oldestLedger'])) {
                $result->oldestLedger = $json['result']['oldestLedger'];
            }
            if (isset($json['result']['oldestLedgerCloseTimestamp'])) {
                $result->oldestLedgerCloseTimestamp = $json['result']['oldestLedgerCloseTimestamp'];
            }
            if (isset($json['result']['cursor'])) {
                $result->cursor = $json['result']['cursor'];
            }
        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }

}