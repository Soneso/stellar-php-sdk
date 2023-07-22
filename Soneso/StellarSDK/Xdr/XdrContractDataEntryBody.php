<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractDataEntryBody
{

    public XdrContractEntryBodyType $type;
    public ?XdrContractDataEntryBodyData $data = null;

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
                $bytes .= $this->data->encode();
                break;
            case XdrContractEntryBodyType::EXPIRATION_EXTENSION:
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrContractDataEntryBody {
        $result = new XdrContractDataEntryBody(XdrContractEntryBodyType::decode($xdr));
        switch ($result->type->value) {
            case XdrContractEntryBodyType::DATA_ENTRY:
                $result->data = XdrContractDataEntryBodyData::decode($xdr);
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
     * @return XdrContractDataEntryBodyData|null
     */
    public function getData(): ?XdrContractDataEntryBodyData
    {
        return $this->data;
    }

    /**
     * @param XdrContractDataEntryBodyData|null $data
     */
    public function setData(?XdrContractDataEntryBodyData $data): void
    {
        $this->data = $data;
    }

}