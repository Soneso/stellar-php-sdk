<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Soneso\StellarSDK\Responses\Response;

class AnchorTransactionsResponse extends Response
{
    private array $transactions = array();

    /**
     * transactions array [AnchorTransaction]
     * @return array
     */
    public function getTransactions(): array
    {
        return $this->transactions;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['transactions'])) {
            foreach ($json['transactions'] as $transaction) {
                array_push($this->transactions, AnchorTransaction::fromJson($transaction));
            }
        }
    }

    public static function fromJson(array $json) : AnchorTransactionsResponse
    {
        $result = new AnchorTransactionsResponse();
        $result->loadFromJson($json);
        return $result;
    }
}