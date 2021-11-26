<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Transaction;

class FeeBumpTransactionResponse
{
    private string $hash;
    private TransactionSignaturesResponse $signatures;

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @return TransactionSignaturesResponse
     */
    public function getSignatures(): TransactionSignaturesResponse
    {
        return $this->signatures;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['hash'])) $this->hash = $json['hash'];

        if (isset($json['signatures'])) {
            $this->signatures = new TransactionSignaturesResponse();
            foreach ($json['signatures'] as $signature) {
                $this->signatures->add($signature);
            }
        }
    }

    public static function fromJson(array $json) : FeeBumpTransactionResponse
    {
        $result = new FeeBumpTransactionResponse();
        $result->loadFromJson($json);
        return $result;
    }
}