<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Errors;

class HorizonErrorResponseExtras
{
    private ?string $envelopeXdr = null;
    private ?string $resultXdr = null;
    private ?string $resultCodesTransaction = null;
    /**
     * @var array<string>|null $resultCodesOperation
     */
    private ?array $resultCodesOperation = null;
    private ?string $txHash = null;

    /**
     * A base64-encoded representation of the TransactionEnvelope XDR whose failure triggered this response if available.
     * @return string | null
     */
    public function getEnvelopeXdr(): ?string
    {
        return $this->envelopeXdr;
    }

    /**
     * A base64-encoded representation of the TransactionResult XDR returned by stellar-core when submitting this transaction if available.
     * @return string | null
     */
    public function getResultXdr(): ?string
    {
        return $this->resultXdr;
    }

    /**
     * The transaction Result Code returned by Stellar Core, which can be used to look up more information about an error in the docs if available.
     * @return string | null
     */
    public function getResultCodesTransaction(): ?string
    {
        return $this->resultCodesTransaction;
    }

    /**
     * An array of operation Result Codes returned by Stellar Core, which can be used to look up more information about an error in the docs if available.
     * @return array<string> | null
     */
    public function getResultCodesOperation(): ?array
    {
        return $this->resultCodesOperation;
    }

    /**
     * The transaction hash if a transaction was submitted and if available.
     * @return string|null
     */
    public function getTxHash(): ?string
    {
        return $this->txHash;
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
        if (isset($json['hash'])) $this->txHash = $json['hash'];
    }

    public static function fromJson(array $json): HorizonErrorResponseExtras
    {
        $result = new HorizonErrorResponseExtras();
        $result->loadFromJson($json);
        return $result;
    }
}