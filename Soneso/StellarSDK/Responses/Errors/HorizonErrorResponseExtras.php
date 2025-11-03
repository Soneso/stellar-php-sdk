<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Errors;

/**
 * Represents additional error information for transaction failures
 *
 * Contains extra details about failed transactions including XDR representations,
 * result codes, and transaction hash. This information helps diagnose why a
 * transaction was rejected by Stellar Core.
 *
 * @package Soneso\StellarSDK\Responses\Errors
 * @see HorizonErrorResponse For the parent error response
 * @since 1.0.0
 */
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
     * Gets the base64-encoded TransactionEnvelope XDR
     *
     * Represents the transaction envelope whose failure triggered this response.
     *
     * @return string|null The envelope XDR, or null if not available
     */
    public function getEnvelopeXdr(): ?string
    {
        return $this->envelopeXdr;
    }

    /**
     * Gets the base64-encoded TransactionResult XDR
     *
     * Represents the transaction result returned by Stellar Core when submitting this transaction.
     *
     * @return string|null The result XDR, or null if not available
     */
    public function getResultXdr(): ?string
    {
        return $this->resultXdr;
    }

    /**
     * Gets the transaction result code
     *
     * The result code returned by Stellar Core can be used to look up more information
     * about the error in the documentation.
     *
     * @return string|null The transaction result code, or null if not available
     */
    public function getResultCodesTransaction(): ?string
    {
        return $this->resultCodesTransaction;
    }

    /**
     * Gets the operation result codes
     *
     * An array of result codes returned by Stellar Core for each operation. These codes
     * can be used to look up more information about errors in the documentation.
     *
     * @return array<string>|null The operation result codes, or null if not available
     */
    public function getResultCodesOperation(): ?array
    {
        return $this->resultCodesOperation;
    }

    /**
     * Gets the transaction hash
     *
     * The hash of the transaction if it was submitted.
     *
     * @return string|null The transaction hash, or null if not available
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