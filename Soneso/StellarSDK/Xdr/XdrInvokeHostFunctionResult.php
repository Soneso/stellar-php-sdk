<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrInvokeHostFunctionResult
{

    public XdrInvokeHostFunctionResultCode $type;
    public ?array $success = null;
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
                $bytes .= XdrEncoder::integer32(count($this->success));
                foreach($this->success as $val) {
                    $bytes .= $val->encode();
                }
                break;
            case XdrInvokeHostFunctionResultCode::INVOKE_HOST_FUNCTION_MALFORMED:
            case XdrInvokeHostFunctionResultCode::INVOKE_HOST_FUNCTION_TRAPPED:
            case XdrInvokeHostFunctionResultCode::INVOKE_HOST_FUNCTION_RESOURCE_LIMIT_EXCEEDED:
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrInvokeHostFunctionResult {
        $result = new XdrInvokeHostFunctionResult(XdrInvokeHostFunctionResultCode::decode($xdr));
        switch ($result->type->value) {
            case XdrInvokeHostFunctionResultCode::INVOKE_HOST_FUNCTION_SUCCESS:
                $valCount = $xdr->readInteger32();
                $result->success = array();
                for ($i = 0; $i < $valCount; $i++) {
                    array_push($result->success, XdrSCVal::decode($xdr));
                }
                break;
            case XdrInvokeHostFunctionResultCode::INVOKE_HOST_FUNCTION_MALFORMED:
            case XdrInvokeHostFunctionResultCode::INVOKE_HOST_FUNCTION_TRAPPED:
            case XdrInvokeHostFunctionResultCode::INVOKE_HOST_FUNCTION_RESOURCE_LIMIT_EXCEEDED:
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
     * @return array|null
     */
    public function getSuccess(): ?array
    {
        return $this->success;
    }

    /**
     * @param array|null $success
     */
    public function setSuccess(?array $success): void
    {
        $this->success = $success;
    }

}