<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrTransactionResultResult
{
    private XdrTransactionResultCode $resultCode;
    private array $operations = array();

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

    public static function decode(XdrBuffer $xdr) : XdrTransactionResultResult {
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
        $result = new XdrTransactionResultResult();
        $result->setResultCode($code);
        $result->setOperations($operations);
        return $result;
    }

}