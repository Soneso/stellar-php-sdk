<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Transaction;

use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Page\PageResponse;

class TransactionsPageResponse  extends PageResponse
{
    private TransactionsResponse $transactions;

    /**
     * @return TransactionsResponse
     */
    public function getTransactions(): TransactionsResponse {
        return $this->transactions;
    }

    protected function loadFromJson(array $json) : void {
        parent::loadFromJson($json);
        if (isset($json['_embedded']['records'])) {
            $this->transactions = new TransactionsResponse();
            foreach ($json['_embedded']['records'] as $jsonTransaction) {
                $transaction = TransactionResponse::fromJson($jsonTransaction);
                $this->transactions->add($transaction);
            }
        }
    }

    public static function fromJson(array $json) : TransactionsPageResponse {
        $result = new TransactionsPageResponse();
        $result->loadFromJson($json);
        return $result;
    }

    public function getNextPage(): TransactionsPageResponse | null {
        return $this->executeRequest(RequestType::TRANSACTIONS_PAGE, $this->getNextPageUrl());
    }

    public function getPreviousPage(): TransactionsPageResponse | null {
        return $this->executeRequest(RequestType::TRANSACTIONS_PAGE, $this->getPrevPageUrl());
    }
}