<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Transaction;

use Soneso\StellarSDK\Responses\Response;

class SubmitTransactionResponse extends TransactionResponse
{
    private ?SubmitTransactionResponseExtras $extras = null;

    /**
     * @return SubmitTransactionResponseExtras|null
     */
    public function getExtras(): ?SubmitTransactionResponseExtras
    {
        return $this->extras;
    }


    protected function loadFromJson(array $json) : void {
        if (isset($json['extras'])) $this->extras = SubmitTransactionResponseExtras::fromJson($json['extras']);
        parent::loadFromJson($json);
    }

    public static function fromJson(array $json) : SubmitTransactionResponse
    {
        $result = new SubmitTransactionResponse();
        $result->loadFromJson($json);
        return $result;
    }
}