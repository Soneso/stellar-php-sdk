<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Errors;

class HorizonErrorResponseExtras
{
    private string $envelopeXdr;
    private string $resultXdr;
    private string $resultCodesTransaction;
    private array $resultCodesOperation;

    /**
     * A base64-encoded representation of the TransactionEnvelope XDR whose failure triggered this response.
     * @return string
     */
    public function getEnvelopeXdr(): string
    {
        return $this->envelopeXdr;
    }

    /**
     * A base64-encoded representation of the TransactionResult XDR returned by stellar-core when submitting this transaction.
     * @return string
     */
    public function getResultXdr(): string
    {
        return $this->resultXdr;
    }

    /**
     * The transaction Result Code returned by Stellar Core, which can be used to look up more information about an error in the docs.
     * @return string
     */
    public function getResultCodesTransaction(): string
    {
        return $this->resultCodesTransaction;
    }

    /**
     * An array of operation Result Codes returned by Stellar Core, which can be used to look up more information about an error in the docs.
     * @return array
     */
    public function getResultCodesOperation(): array
    {
        return $this->resultCodesOperation;
    }

    protected function loadFromJson(array $json): void
    {
        if (isset($json['envelope_xdr'])) $this->envelopeXdr = $json['envelope_xdr'];
        if (isset($json['result_xdr'])) $this->resultXdr = $json['result_xdr'];
        if (isset($json['result_codes'])) {

            if (isset($json['result_codes']['transaction'])) $this->resultCodesTransaction = $json['result_codes']['transaction'];

            $this->resultCodesOperation = array();
            if (isset($json['result_codes']['operations'])) {
                foreach ($json['result_codes']['operations'] as $resultCode) {
                    $this->resultCodesOperation[] = $resultCode;
                }
            }
        }
    }

    public static function fromJson(array $json): HorizonErrorResponseExtras
    {
        $result = new HorizonErrorResponseExtras();
        $result->loadFromJson($json);
        return $result;
    }
}