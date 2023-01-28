<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrTransactionResultResult
{
    public XdrTransactionResultCode $resultCode;
    public ?array $results = null;
    public ?XdrInnerTransactionResultPair $innerResultPair = null;

    public function encode(): string {
        $bytes = $this->resultCode->encode();
        switch ($this->resultCode->getValue()) {
            case XdrTransactionResultCode::SUCCESS:
            case XdrTransactionResultCode::FAILED:
                $bytes .= XdrEncoder::integer32(count($this->results));
                foreach($this->results as $operation) {
                    if ($operation instanceof XdrOperationResult) {
                        $bytes .= $operation->encode();
                    }
                }
                break;
            case XdrTransactionResultCode::FEE_BUMP_INNER_SUCCESS:
            case XdrTransactionResultCode::FEE_BUMP_INNER_FAILED:
                $bytes .= $this->innerResultPair->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrTransactionResultResult {
        $result = new XdrTransactionResultResult();
        $code = XdrTransactionResultCode::decode($xdr);
        $result->resultCode = $code;

        switch($code->getValue()) {
            case XdrTransactionResultCode::SUCCESS:
            case XdrTransactionResultCode::FAILED:
                $size = $xdr->readInteger32();
                $results = array();
                for($i = 0; $i <$size; $i++) {
                    array_push($results, XdrOperationResult::decode($xdr));
                }
                $result->results = $results;
                break;
            case XdrTransactionResultCode::FEE_BUMP_INNER_SUCCESS:
            case XdrTransactionResultCode::FEE_BUMP_INNER_FAILED:
                $result->innerResultPair = XdrInnerTransactionResultPair::decode($xdr);
                break;
        }
        return $result;
    }

    /**
     * @return XdrTransactionResultCode
     */
    public function getResultCode(): XdrTransactionResultCode
    {
        return $this->resultCode;
    }

    /**
     * @param XdrTransactionResultCode $resultCode
     */
    public function setResultCode(XdrTransactionResultCode $resultCode): void
    {
        $this->resultCode = $resultCode;
    }

    /**
     * @return array|null
     */
    public function getResults(): ?array
    {
        return $this->results;
    }

    /**
     * @param array|null $results
     */
    public function setResults(?array $results): void
    {
        $this->results = $results;
    }

    /**
     * @return XdrInnerTransactionResultPair|null
     */
    public function getInnerResultPair(): ?XdrInnerTransactionResultPair
    {
        return $this->innerResultPair;
    }

    /**
     * @param XdrInnerTransactionResultPair|null $innerResultPair
     */
    public function setInnerResultPair(?XdrInnerTransactionResultPair $innerResultPair): void
    {
        $this->innerResultPair = $innerResultPair;
    }

}