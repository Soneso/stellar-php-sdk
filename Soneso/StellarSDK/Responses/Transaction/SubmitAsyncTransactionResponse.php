<?php  declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Transaction;

use Soneso\StellarSDK\Responses\Response;
use Soneso\StellarSDK\Xdr\XdrTransactionResult;

class SubmitAsyncTransactionResponse extends Response
{
    const TX_STATUS_ERROR = 'ERROR';
    const TX_STATUS_PENDING = 'PENDING';
    const TX_STATUS_DUPLICATE = 'DUPLICATE';
    const TX_STATUS_TRY_AGAIN_LATER = 'TRY_AGAIN_LATER';

    public string $txStatus;
    public string $hash;
    public int $httpStatusCode;
    public ?string $errorResultXdrBase64 = null;
    public ?XdrTransactionResult $errorResult = null;

    /**
     * Constructor.
     * @param string $txStatus Status of the transaction submission. Possible values: [ERROR, PENDING, DUPLICATE, TRY_AGAIN_LATER]
     * @param string $hash Hash of the transaction.
     * @param int $httpStatusCode The HTTP status code of the response obtained from Horizon.
     * @param string|null $errorResultXdrBase64 TransactionResult XDR string which is present only if the submission status from core is an ERROR.
     */
    public function __construct(string $txStatus, string $hash, int $httpStatusCode, ?string $errorResultXdrBase64 = null)
    {
        $this->txStatus = $txStatus;
        $this->hash = $hash;
        $this->httpStatusCode = $httpStatusCode;
        $this->errorResultXdrBase64 = $errorResultXdrBase64;
        if ($errorResultXdrBase64 !== null) {
            $this->errorResult = XdrTransactionResult::fromBase64Xdr($errorResultXdrBase64);
        }
    }


    public static function fromJson(array $json, int $httpResponseStatusCode) : SubmitAsyncTransactionResponse
    {
        $txStatus = $json['tx_status'];
        $hash = $json['hash'];
        $errorResultXdrBase64 = isset($json['error_result_xdr_base64']) ? $json['error_result_xdr_base64'] : null;
        return new SubmitAsyncTransactionResponse(
            txStatus: $txStatus,
            hash: $hash,
            httpStatusCode: $httpResponseStatusCode,
            errorResultXdrBase64: $errorResultXdrBase64,
        );
    }
}