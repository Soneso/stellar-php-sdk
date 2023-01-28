<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrOperationResult
{

    private XdrOperationResultCode $resultCode;
    private ?XdrOperationResultTr $resultTr = null;

    /**
     * @return XdrOperationResultCode
     */
    public function getResultCode(): XdrOperationResultCode
    {
        return $this->resultCode;
    }

    /**
     * @param XdrOperationResultCode $resultCode
     */
    public function setResultCode(XdrOperationResultCode $resultCode): void
    {
        $this->resultCode = $resultCode;
    }

    /**
     * @return XdrOperationResultTr|null
     */
    public function getResultTr(): ?XdrOperationResultTr
    {
        return $this->resultTr;
    }

    /**
     * @param XdrOperationResultTr|null $resultTr
     */
    public function setResultTr(?XdrOperationResultTr $resultTr): void
    {
        $this->resultTr = $resultTr;
    }

    public function encode(): string {
        $bytes = $this->resultCode->encode();
        if ($this->resultTr != null) {
            $bytes .= $this->resultTr->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrOperationResult {
        $code = XdrOperationResultCode::decode($xdr);
        $resultTr = match ($code->getValue()) {
            XdrOperationResultCode::INNER => XdrOperationResultTr::decode($xdr),
            default => null,
        };
        $result = new XdrOperationResult();
        $result->resultCode = $code;
        $result->resultTr = $resultTr;
        return $result;
    }
}

