<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrInnerTransactionResultResult
{
    public XdrTransactionResultCode $resultCode;
    public array $operations = array();


    public function encode(): string {
        $bytes = $this->resultCode->encode();
        $bytes .= XdrEncoder::integer32(count($this->operations));
        foreach($this->operations as $operation) {
            if ($operation instanceof XdrOperationResult) {
                $bytes .= $operation->encode();
            }
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrInnerTransactionResultResult {
        $code = XdrTransactionResultCode::decode($xdr);
        $operations = array();
        switch($code->getValue()) {
            case XdrTransactionResultCode::SUCCESS:
            case XdrTransactionResultCode::FAILED:
                $size = $xdr->readInteger32();
                for($i = 0; $i <$size; $i++) {
                    array_push($operations, XdrOperationResult::decode($xdr));
                }
                break;
        }
        $result = new XdrInnerTransactionResultResult();
        $result->setResultCode($code);
        $result->setOperations($operations);
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
     * @return array
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * @param array $operations
     */
    public function setOperations(array $operations): void
    {
        $this->operations = $operations;
    }

}