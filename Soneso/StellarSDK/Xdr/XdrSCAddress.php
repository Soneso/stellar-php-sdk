<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCAddress
{

    public XdrSCAddressType $type;
    public ?XdrAccountID $accountId = null;
    public ?string $contractId; // hex

    /**
     * @param XdrSCAddressType $type
     */
    public function __construct(XdrSCAddressType $type)
    {
        $this->type = $type;
    }

    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->value) {
            case XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT:
                $bytes .= $this->accountId->encode();
                break;
            case XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT:
                $bytes .= XdrEncoder::opaqueFixed(hex2bin($this->contractId),32);
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCAddress {
        $result = new XdrSCAddress(XdrSCAddressType::decode($xdr));
        switch ($result->type->value) {
            case XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT:
                $result->accountId = XdrAccountID::decode($xdr);
                break;
            case XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT:
                $result->contractId = bin2hex($xdr->readOpaqueFixed(32));
                break;
        }
        return $result;
    }

    public static function forAccountId(string $accountId) : XdrSCAddress {
        $res = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT());
        $res->accountId = XdrAccountID::fromAccountId($accountId);
        return $res;
    }

    public static function forContractId(string $contractId) : XdrSCAddress {
        $res = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT());
        $res->contractId = $contractId;
        return $res;
    }

    /**
     * @return XdrSCAddressType
     */
    public function getType(): XdrSCAddressType
    {
        return $this->type;
    }

    /**
     * @param XdrSCAddressType $type
     */
    public function setType(XdrSCAddressType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return XdrAccountID|null
     */
    public function getAccountId(): ?XdrAccountID
    {
        return $this->accountId;
    }

    /**
     * @param XdrAccountID|null $accountId
     */
    public function setAccountId(?XdrAccountID $accountId): void
    {
        $this->accountId = $accountId;
    }

    /**
     * @return string|null
     */
    public function getContractId(): ?string
    {
        return $this->contractId;
    }

    /**
     * @param string|null $contractId
     */
    public function setContractId(?string $contractId): void
    {
        $this->contractId = $contractId;
    }

}