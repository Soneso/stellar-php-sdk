<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCAddress
{

    public XdrSCAddressType $type;
    public ?XdrAccountID $accountID = null;
    public ?string $contractID; // hash

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
                $bytes .= $this->accountID->encode();
                break;
            case XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT:
                $bytes .= XdrEncoder::opaqueFixed($this->contractID,32);
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCAddress {
        $result = new XdrSCAddress(XdrSCAddressType::decode($xdr));
        switch ($result->type->value) {
            case XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT:
                $result->accountID = XdrAccountID::decode($xdr);
                break;
            case XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT:
                $contractID = $xdr->readOpaqueFixed(32);
                break;
        }
        return $result;
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
    public function getAccountID(): ?XdrAccountID
    {
        return $this->accountID;
    }

    /**
     * @param XdrAccountID|null $accountID
     */
    public function setAccountID(?XdrAccountID $accountID): void
    {
        $this->accountID = $accountID;
    }

    /**
     * @return string|null
     */
    public function getContractID(): ?string
    {
        return $this->contractID;
    }

    /**
     * @param string|null $contractID
     */
    public function setContractID(?string $contractID): void
    {
        $this->contractID = $contractID;
    }

}