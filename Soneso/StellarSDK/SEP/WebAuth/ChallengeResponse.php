<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

use Soneso\StellarSDK\Responses\Response;

class ChallengeResponse extends Response
{
    private string $transaction;

    /**
     * @param string $transaction
     */
    public function setTransaction(string $transaction): void
    {
        $this->transaction = $transaction;
    }

    /**
     * @return string
     */
    public function getTransaction(): string
    {
        return $this->transaction;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['transaction'])) $this->transaction = $json['transaction'];
    }

    public static function fromJson(array $json) : ChallengeResponse
    {
        $result = new ChallengeResponse();
        $result->loadFromJson($json);
        return $result;
    }
}