<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrInvokeHostFunctionResult
{

    public XdrInvokeHostFunctionResultCode $type;
    public ?XdrSCVal $success = null;

    /**
     * @param XdrInvokeHostFunctionResultCode $type
     */
    public function __construct(XdrInvokeHostFunctionResultCode $type)
    {
        $this->type = $type;
    }


    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->value) {
            case XdrInvokeHostFunctionResultCode::INVOKE_HOST_FUNCTION_SUCCESS:
                $bytes = $this->success->encode();
                break;
            case XdrInvokeHostFunctionResultCode::INVOKE_HOST_FUNCTION_MALFORMED:
            case XdrInvokeHostFunctionResultCode::INVOKE_HOST_FUNCTION_TRAPPED:
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrInvokeHostFunctionResult {
        $result = new XdrInvokeHostFunctionResult(XdrInvokeHostFunctionResultCode::decode($xdr));
        switch ($result->type->value) {
            case XdrInvokeHostFunctionResultCode::INVOKE_HOST_FUNCTION_SUCCESS:
                $result->success = XdrSCVal::decode($xdr);
                break;
            case XdrInvokeHostFunctionResultCode::INVOKE_HOST_FUNCTION_MALFORMED:
            case XdrInvokeHostFunctionResultCode::INVOKE_HOST_FUNCTION_TRAPPED:
                break;
        }
        return $result;
    }

    /**
     * @return XdrInvokeHostFunctionResultCode
     */
    public function getType(): XdrInvokeHostFunctionResultCode
    {
        return $this->type;
    }

    /**
     * @param XdrInvokeHostFunctionResultCode $type
     */
    public function setType(XdrInvokeHostFunctionResultCode $type): void
    {
        $this->type = $type;
    }

    /**
     * @return XdrSCVal|null
     */
    public function getSuccess(): ?XdrSCVal
    {
        return $this->success;
    }

    /**
     * @param XdrSCVal|null $success
     */
    public function setSuccess(?XdrSCVal $success): void
    {
        $this->success = $success;
    }

}