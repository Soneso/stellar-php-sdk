<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractCodeBody
{

    public XdrContractEntryBodyType $type;
    public ?XdrDataValueMandatory $code;

    /**
     * @param XdrContractEntryBodyType $type
     */
    public function __construct(XdrContractEntryBodyType $type)
    {
        $this->type = $type;
    }


    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->value) {
            case XdrContractEntryBodyType::DATA_ENTRY:
                $bytes .= $this->code->encode();
                break;
            case XdrContractEntryBodyType::EXPIRATION_EXTENSION:
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrContractCodeBody {
        $result = new XdrContractCodeBody(XdrContractEntryBodyType::decode($xdr));
        switch ($result->type->value) {
            case XdrContractEntryBodyType::DATA_ENTRY:
                $result->code = XdrDataValueMandatory::decode($xdr);
                break;
            case XdrContractEntryBodyType::EXPIRATION_EXTENSION:
                break;
        }
        return $result;
    }

    /**
     * @return XdrContractEntryBodyType
     */
    public function getType(): XdrContractEntryBodyType
    {
        return $this->type;
    }

    /**
     * @param XdrContractEntryBodyType $type
     */
    public function setType(XdrContractEntryBodyType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return XdrDataValueMandatory|null
     */
    public function getCode(): ?XdrDataValueMandatory
    {
        return $this->code;
    }

    /**
     * @param XdrDataValueMandatory|null $code
     */
    public function setCode(?XdrDataValueMandatory $code): void
    {
        $this->code = $code;
    }
}