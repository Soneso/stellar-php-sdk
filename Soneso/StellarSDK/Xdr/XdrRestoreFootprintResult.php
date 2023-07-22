<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrRestoreFootprintResult
{

    public XdrRestoreFootprintResultCode $code;

    /**
     * @param XdrRestoreFootprintResultCode $code
     */
    public function __construct(XdrRestoreFootprintResultCode $code)
    {
        $this->code = $code;
    }


    public function encode(): string {
        $bytes = $this->code->encode();

        switch ($this->code->value) {
            case XdrRestoreFootprintResultCode::RESTORE_FOOTPRINT_SUCCESS:
            case XdrRestoreFootprintResultCode::RESTORE_FOOTPRINT_MALFORMED:
            case XdrRestoreFootprintResultCode::RESTORE_FOOTPRINT_RESOURCE_LIMIT_EXCEEDED:
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrRestoreFootprintResult {
        $result = new XdrRestoreFootprintResult(XdrRestoreFootprintResultCode::decode($xdr));
        switch ($result->code->value) {
            case XdrRestoreFootprintResultCode::RESTORE_FOOTPRINT_SUCCESS:
            case XdrRestoreFootprintResultCode::RESTORE_FOOTPRINT_MALFORMED:
            case XdrRestoreFootprintResultCode::RESTORE_FOOTPRINT_RESOURCE_LIMIT_EXCEEDED:
                break;
        }
        return $result;
    }

    /**
     * @return XdrRestoreFootprintResultCode
     */
    public function getCode(): XdrRestoreFootprintResultCode
    {
        return $this->code;
    }

    /**
     * @param XdrRestoreFootprintResultCode $code
     */
    public function setCode(XdrRestoreFootprintResultCode $code): void
    {
        $this->code = $code;
    }
}