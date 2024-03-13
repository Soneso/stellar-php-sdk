<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Soneso\StellarSDK\Responses\Response;

class AnchorTransactionsResponse extends Response
{
    /**
     * @var array<AnchorTransaction>
     */
    public array $transactions = array();

    /**
     * @param array<AnchorTransaction> $transactions
     */
    public function __construct(array $transactions)
    {
        $this->transactions = $transactions;
    }

    /**
     * Constructs a new instance of AnchorTransactionsResponse by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return AnchorTransactionsResponse the object containing the parsed data.
     */
    public static function fromJson(array $json) : AnchorTransactionsResponse
    {
        /**
         * @var array<AnchorTransaction> $transactions
         */
        $transactions = array();
        if (isset($json['transactions'])) {
            foreach ($json['transactions'] as $transaction) {
                $transactions[] = AnchorTransaction::fromJson($transaction);
            }
        }
        return new AnchorTransactionsResponse($transactions);
    }
}