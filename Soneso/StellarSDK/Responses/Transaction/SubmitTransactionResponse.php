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

class SubmitTransactionResponse extends Response
{
    private string $hash;
    private int $ledger;
    private string $envelopeXdrBase64;
    private string $resultXdrBase64;
    private string $metaXdrBase64;

    private ?SubmitTransactionResponseExtras $extras = null;


    protected function loadFromJson(array $json) : void {
        if (isset($json['extras'])) $this->extras = SubmitTransactionResponseExtras::fromJson($json['extras']);
        $this->hash = $json['hash'];
        $this->envelopeXdrBase64 = $json['envelope_xdr'];
        $this->resultXdrBase64 = $json['result_xdr'];
        $this->metaXdrBase64 = $json['result_meta_xdr'];

        parent::loadFromJson($json);
    }

    public static function fromJson(array $json) : SubmitTransactionResponse
    {
        $result = new SubmitTransactionResponse();
        $result->loadFromJson($json);
        return $result;
    }

    public function getEnvelopeXdr() : XdrTransactionEnvelope {
        return XdrTransactionEnvelope::fromEnvelopeBase64XdrString($this->getEnvelopeXdrBase64());
    }

    public function getResultXdr() : XdrTransactionResult {
        return XdrTransactionResult::fromBase64Xdr($this->getResultXdrBase64());
    }

    public function getMetaXdr() : XdrTransactionMeta {
        return XdrTransactionMeta::fromBase64Xdr($this->getMetaXdrBase64());
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
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     */
    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }

    /**
     * @return int
     */
    public function getLedger(): int
    {
        return $this->ledger;
    }

    /**
     * @param int $ledger
     */
    public function setLedger(int $ledger): void
    {
        $this->ledger = $ledger;
    }

    /**
     * @return string
     */
    public function getEnvelopeXdrBase64(): string
    {
        return $this->envelopeXdrBase64;
    }

    /**
     * @param string $envelopeXdrBase64
     */
    public function setEnvelopeXdrBase64(string $envelopeXdrBase64): void
    {
        $this->envelopeXdrBase64 = $envelopeXdrBase64;
    }

    /**
     * @return string
     */
    public function getResultXdrBase64(): string
    {
        return $this->resultXdrBase64;
    }

    /**
     * @param string $resultXdrBase64
     */
    public function setResultXdrBase64(string $resultXdrBase64): void
    {
        $this->resultXdrBase64 = $resultXdrBase64;
    }

    /**
     * @return string
     */
    public function getMetaXdrBase64(): string
    {
        return $this->metaXdrBase64;
    }

    /**
     * @param string $metaXdrBase64
     */
    public function setMetaXdrBase64(string $metaXdrBase64): void
    {
        $this->metaXdrBase64 = $metaXdrBase64;
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