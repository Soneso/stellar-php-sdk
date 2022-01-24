<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Soneso\StellarSDK\Responses\Response;

class AnchorTransactionResponse extends Response {

    private AnchorTransaction $transaction;

    /**
     * @return AnchorTransaction
     */
    public function getTransaction(): AnchorTransaction
    {
        return $this->transaction;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['transaction'])) {
            $this->transaction = AnchorTransaction::fromJson($json['transaction']);
        }
    }

    public static function fromJson(array $json) : AnchorTransactionResponse
    {
        $result = new AnchorTransactionResponse();
        $result->loadFromJson($json);
        return $result;
    }
}