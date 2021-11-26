<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Transaction;

class SubmitTransactionResponseExtras
{
    private string $envelopeXdr;
    private string $resultXdr;
    private ?ExtrasResultCodes $resultCodes = null;

    /**
     * @return string
     */
    public function getEnvelopeXdr(): string
    {
        return $this->envelopeXdr;
    }

    /**
     * @return string
     */
    public function getResultXdr(): string
    {
        return $this->resultXdr;
    }

    /**
     * @return ExtrasResultCodes|null
     */
    public function getResultCodes(): ?ExtrasResultCodes
    {
        return $this->resultCodes;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['envelope_xdr'])) $this->envelopeXdr = $json['envelope_xdr'];
        if (isset($json['result_xdr'])) $this->resultXdr = $json['result_xdr'];
        if (isset($json['result_codes'])) $this->resultCodes = ExtrasResultCodes::fromJson($json['result_codes']);
    }

    public static function fromJson(array $json) : SubmitTransactionResponseExtras
    {
        $result = new SubmitTransactionResponseExtras();
        $result->loadFromJson($json);
        return $result;
    }
}