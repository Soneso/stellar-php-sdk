<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\Responses\Response;

class SEP24TransactionResponse extends Response {

    public SEP24Transaction $transaction;

    protected function loadFromJson(array $json) : void {
        if (isset($json['transaction'])) {
            $this->transaction = SEP24Transaction::fromJson($json['transaction']);
        }
    }

    public static function fromJson(array $json) : SEP24TransactionResponse
    {
        $result = new SEP24TransactionResponse();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * @return SEP24Transaction
     */
    public function getTransaction(): SEP24Transaction
    {
        return $this->transaction;
    }

    /**
     * @param SEP24Transaction $transaction
     */
    public function setTransaction(SEP24Transaction $transaction): void
    {
        $this->transaction = $transaction;
    }
}