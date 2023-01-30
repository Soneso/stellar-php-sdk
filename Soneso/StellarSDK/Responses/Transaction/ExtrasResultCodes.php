<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Transaction;

class ExtrasResultCodes
{
    private string $transactionResultCode;
    private array $operationsResultCodes = array();

    /**
     * @return string
     */
    public function getTransactionResultCode(): string
    {
        return $this->transactionResultCode;
    }

    /**
     * @return array
     */
    public function getOperationsResultCodes(): array
    {
        return $this->operationsResultCodes;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['transaction'])) $this->transactionResultCode = $json['transaction'];
        if (isset($json['operations'])) {
            foreach ($json['operations'] as $code) {
                array_push($this->operationsResultCodes, $code);
            }
        }
    }

    public static function fromJson(array $json) : ExtrasResultCodes {
        $result = new ExtrasResultCodes();
        $result->loadFromJson($json);
        return $result;
    }
}