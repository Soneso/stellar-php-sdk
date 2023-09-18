<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCError
{

    public XdrSCErrorType $type;
    public ?XdrSCErrorCode $code = null;
    public ?int $contractCode; // uint32

    /**
     * @param XdrSCErrorType $type
     */
    public function __construct(XdrSCErrorType $type)
    {
        $this->type = $type;
    }

    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->value) {
            case XdrSCErrorType::SCE_CONTRACT:
                $bytes .= XdrEncoder::unsignedInteger32($this->contractCode);
                break;
            case XdrSCErrorType::SCE_WASM_VM:
            case XdrSCErrorType::SCE_CONTEXT:
            case XdrSCErrorType::SCE_STORAGE:
            case XdrSCErrorType::SCE_OBJECT:
            case XdrSCErrorType::SCE_CRYPTO:
            case XdrSCErrorType::SCE_EVENTS:
            case XdrSCErrorType::SCE_BUDGET:
            case XdrSCErrorType::SCE_VALUE:
                break;
            case XdrSCErrorType::SCE_AUTH:
                $bytes .= $this->code->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCError {
        $result = new XdrSCError(XdrSCErrorType::decode($xdr));
        switch ($result->type->value) {
            case XdrSCErrorType::SCE_CONTRACT:
                $result->contractCode = $xdr->readUnsignedInteger32();
                break;
            case XdrSCErrorType::SCE_WASM_VM:
            case XdrSCErrorType::SCE_CONTEXT:
            case XdrSCErrorType::SCE_STORAGE:
            case XdrSCErrorType::SCE_OBJECT:
            case XdrSCErrorType::SCE_CRYPTO:
            case XdrSCErrorType::SCE_EVENTS:
            case XdrSCErrorType::SCE_BUDGET:
            case XdrSCErrorType::SCE_VALUE:
                break;
            case XdrSCErrorType::SCE_AUTH:
                $result->code = XdrSCErrorCode::decode($xdr);
                break;
        }
        return $result;
    }

    /**
     * @return XdrSCErrorType
     */
    public function getType(): XdrSCErrorType
    {
        return $this->type;
    }

    /**
     * @param XdrSCErrorType $type
     */
    public function setType(XdrSCErrorType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return XdrSCErrorCode|null
     */
    public function getCode(): ?XdrSCErrorCode
    {
        return $this->code;
    }

    /**
     * @param XdrSCErrorCode|null $code
     */
    public function setCode(?XdrSCErrorCode $code): void
    {
        $this->code = $code;
    }

    /**
     * @return int|null
     */
    public function getContractCode(): ?int
    {
        return $this->contractCode;
    }

    /**
     * @param int|null $contractCode
     */
    public function setContractCode(?int $contractCode): void
    {
        $this->contractCode = $contractCode;
    }

}