<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\Responses\Response;

class SEP24TransactionsResponse extends Response
{
    public array $transactions = array();

    protected function loadFromJson(array $json) : void {

        if (isset($json['transactions'])) {
            foreach ($json['transactions'] as $tx) {
                array_push($this->transactions, SEP24Transaction::fromJson($tx));
            }
        }
    }

    public static function fromJson(array $json) : SEP24TransactionsResponse
    {
        $result = new SEP24TransactionsResponse();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * @return array
     */
    public function getTransactions(): array
    {
        return $this->transactions;
    }

    /**
     * @param array $transactions
     */
    public function setTransactions(array $transactions): void
    {
        $this->transactions = $transactions;
    }

}