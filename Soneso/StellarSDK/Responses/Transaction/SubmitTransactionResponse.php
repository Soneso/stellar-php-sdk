<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Transaction;

use Soneso\StellarSDK\Responses\Response;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrTransactionMeta;
use Soneso\StellarSDK\Xdr\XdrTransactionResult;
use Soneso\StellarSDK\Xdr\XdrTransactionResultCode;

class SubmitTransactionResponse extends TransactionResponse
{

    private ?SubmitTransactionResponseExtras $extras = null;


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


    public function isSuccessful() : bool {
        $result = $this->getResultXdr();
        if ($result->result->resultCode->getValue() == XdrTransactionResultCode::SUCCESS) {
            return true;
        } else if ($result->result->resultCode->getValue() == XdrTransactionResultCode::FEE_BUMP_INNER_SUCCESS
            && $result->result->innerResultPair != null) {
            $innerResultPair = $result->result->innerResultPair;
            if ($innerResultPair->result->result->resultCode->getValue() == XdrTransactionResultCode::SUCCESS) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return SubmitTransactionResponseExtras|null
     */
    public function getExtras(): ?SubmitTransactionResponseExtras
    {
        return $this->extras;
    }

    /**
     * @param SubmitTransactionResponseExtras|null $extras
     */
    public function setExtras(?SubmitTransactionResponseExtras $extras): void
    {
        $this->extras = $extras;
    }
}