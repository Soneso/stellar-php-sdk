<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\Responses\Response;

class SEP24TransactionsResponse extends Response
{
    /**
     * @var array<SEP24Transaction> the parsed transactions.
     */
    public array $transactions = array();

    /**
     * Loads the needed data from a json array.
     * @param array<array-key, mixed> $json the data array to read from.
     * @return void
     */
    protected function loadFromJson(array $json) : void {

        if (isset($json['transactions'])) {
            foreach ($json['transactions'] as $tx) {
                $this->transactions[] = SEP24Transaction::fromJson($tx);
            }
        }
    }

    /**
     * Constructs a new instance of SEP24TransactionsResponse by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP24TransactionsResponse the object containing the parsed data.
     */
    public static function fromJson(array $json) : SEP24TransactionsResponse
    {
        $result = new SEP24TransactionsResponse();
        $result->loadFromJson($json);

        return $result;
    }

    /**
     * @return array<SEP24Transaction> the parsed transactions.
     */
    public function getTransactions(): array
    {
        return $this->transactions;
    }

    /**
     * @param array<SEP24Transaction> $transactions the parsed transactions.
     */
    public function setTransactions(array $transactions): void
    {
        $this->transactions = $transactions;
    }

}