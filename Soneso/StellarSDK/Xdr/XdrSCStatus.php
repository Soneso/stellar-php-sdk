<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCStatus
{

    public XdrSCStatusType $type;
    public ?XdrSCUnknownErrorCode $unknownCode = null;
    public ?XdrSCHostValErrorCode $valCode = null;
    public ?XdrSCHostObjErrorCode $objCode = null;
    public ?XdrSCHostFnErrorCode $fnCode = null;
    public ?XdrSCHostStorageErrorCode $storageCode = null;
    public ?XdrSCHostContextErrorCode $contextCode = null;
    public ?XdrSCVmErrorCode $vmCode = null;
    public ?int $contractCode = null;
    public ?XdrSCHostAuthErrorCode $authCode = null;

    /**
     * @param XdrSCStatusType $type
     */
    public function __construct(XdrSCStatusType $type)
    {
        $this->type = $type;
    }

    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->value) {
            case XdrSCStatusType::SST_OK:
                break;
            case XdrSCStatusType::SST_UNKNOWN_ERROR:
                $bytes .= $this->unknownCode->encode();
                break;
            case XdrSCStatusType::SST_HOST_VALUE_ERROR:
                $bytes .= $this->valCode->encode();
                break;
            case XdrSCStatusType::SST_HOST_OBJECT_ERROR:
                $bytes .= $this->objCode->encode();
                break;
            case XdrSCStatusType::SST_HOST_FUNCTION_ERROR:
                $bytes .= $this->fnCode->encode();
                break;
            case XdrSCStatusType::SST_HOST_STORAGE_ERROR:
                $bytes .= $this->storageCode->encode();
                break;
            case XdrSCStatusType::SST_HOST_CONTEXT_ERROR:
                $bytes .= $this->contextCode->encode();
                break;
            case XdrSCStatusType::SST_VM_ERROR:
                $bytes .= $this->vmCode->encode();
                break;
            case XdrSCStatusType::SST_CONTRACT_ERROR:
                $bytes .= XdrEncoder::unsignedInteger32($this->contractCode);
                break;
            case XdrSCStatusType::SST_HOST_AUTH_ERROR:
                $bytes .= $this->authCode->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCStatus {
        $result = new XdrSCStatus(XdrSCStatusType::decode($xdr));
        switch ($result->getType()->getValue()) {
            case XdrSCStatusType::SST_OK:
                break;
            case XdrSCStatusType::SST_UNKNOWN_ERROR:
                $result->unknownCode = XdrSCUnknownErrorCode::decode($xdr);
                break;
            case XdrSCStatusType::SST_HOST_VALUE_ERROR:
                $result->valCode = XdrSCHostValErrorCode::decode($xdr);
                break;
            case XdrSCStatusType::SST_HOST_OBJECT_ERROR:
                $result->objCode = XdrSCHostObjErrorCode::decode($xdr);
                break;
            case XdrSCStatusType::SST_HOST_FUNCTION_ERROR:
                $result->fnCode = XdrSCHostFnErrorCode::decode($xdr);
                break;
            case XdrSCStatusType::SST_HOST_STORAGE_ERROR:
                $result->storageCode = XdrSCHostStorageErrorCode::decode($xdr);
                break;
            case XdrSCStatusType::SST_HOST_CONTEXT_ERROR:
                $result->contextCode = XdrSCHostContextErrorCode::decode($xdr);
                break;
            case XdrSCStatusType::SST_VM_ERROR:
                $result->vmCode = XdrSCVmErrorCode::decode($xdr);
                break;
            case XdrSCStatusType::SST_CONTRACT_ERROR:
                $result->contractCode = $xdr->readUnsignedInteger32();
                break;
            case XdrSCStatusType::SST_HOST_AUTH_ERROR:
                $result->authCode = XdrSCHostAuthErrorCode::decode($xdr);
                break;
        }
        return $result;
    }

    /**
     * @return XdrSCStatusType
     */
    public function getType(): XdrSCStatusType
    {
        return $this->type;
    }

    /**
     * @param XdrSCStatusType $type
     */
    public function setType(XdrSCStatusType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return XdrSCUnknownErrorCode|null
     */
    public function getUnknownCode(): ?XdrSCUnknownErrorCode
    {
        return $this->unknownCode;
    }

    /**
     * @param XdrSCUnknownErrorCode|null $unknownCode
     */
    public function setUnknownCode(?XdrSCUnknownErrorCode $unknownCode): void
    {
        $this->unknownCode = $unknownCode;
    }

    /**
     * @return XdrSCHostValErrorCode|null
     */
    public function getValCode(): ?XdrSCHostValErrorCode
    {
        return $this->valCode;
    }

    /**
     * @param XdrSCHostValErrorCode|null $valCode
     */
    public function setValCode(?XdrSCHostValErrorCode $valCode): void
    {
        $this->valCode = $valCode;
    }

    /**
     * @return XdrSCHostObjErrorCode|null
     */
    public function getObjCode(): ?XdrSCHostObjErrorCode
    {
        return $this->objCode;
    }

    /**
     * @param XdrSCHostObjErrorCode|null $objCode
     */
    public function setObjCode(?XdrSCHostObjErrorCode $objCode): void
    {
        $this->objCode = $objCode;
    }

    /**
     * @return XdrSCHostFnErrorCode|null
     */
    public function getFnCode(): ?XdrSCHostFnErrorCode
    {
        return $this->fnCode;
    }

    /**
     * @param XdrSCHostFnErrorCode|null $fnCode
     */
    public function setFnCode(?XdrSCHostFnErrorCode $fnCode): void
    {
        $this->fnCode = $fnCode;
    }

    /**
     * @return XdrSCHostStorageErrorCode|null
     */
    public function getStorageCode(): ?XdrSCHostStorageErrorCode
    {
        return $this->storageCode;
    }

    /**
     * @param XdrSCHostStorageErrorCode|null $storageCode
     */
    public function setStorageCode(?XdrSCHostStorageErrorCode $storageCode): void
    {
        $this->storageCode = $storageCode;
    }

    /**
     * @return XdrSCHostContextErrorCode|null
     */
    public function getContextCode(): ?XdrSCHostContextErrorCode
    {
        return $this->contextCode;
    }

    /**
     * @param XdrSCHostContextErrorCode|null $contextCode
     */
    public function setContextCode(?XdrSCHostContextErrorCode $contextCode): void
    {
        $this->contextCode = $contextCode;
    }

    /**
     * @return XdrSCVmErrorCode|null
     */
    public function getVmCode(): ?XdrSCVmErrorCode
    {
        return $this->vmCode;
    }

    /**
     * @param XdrSCVmErrorCode|null $vmCode
     */
    public function setVmCode(?XdrSCVmErrorCode $vmCode): void
    {
        $this->vmCode = $vmCode;
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

    /**
     * @return XdrSCHostAuthErrorCode|null
     */
    public function getAuthCode(): ?XdrSCHostAuthErrorCode
    {
        return $this->authCode;
    }

    /**
     * @param XdrSCHostAuthErrorCode|null $authCode
     */
    public function setAuthCode(?XdrSCHostAuthErrorCode $authCode): void
    {
        $this->authCode = $authCode;
    }

}