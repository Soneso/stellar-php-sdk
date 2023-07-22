<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSorobanCredentials
{

    public XdrSorobanCredentialsType $type;
    public ?XdrSorobanAddressCredentials $address = null;

    /**
     * @param XdrSorobanCredentialsType $type
     */
    public function __construct(XdrSorobanCredentialsType $type)
    {
        $this->type = $type;
    }


    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->value) {
            case XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT:
                break;
            case XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS:
                $bytes .= $this->address->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSorobanCredentials {
        $result = new XdrSorobanCredentials(XdrSorobanCredentialsType::decode($xdr));
        switch ($result->type->value) {
            case XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT:
                break;
            case XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS:
                $result->address = XdrSorobanAddressCredentials::decode($xdr);
                break;
        }
        return $result;
    }

    /**
     * @return XdrSorobanCredentialsType
     */
    public function getType(): XdrSorobanCredentialsType
    {
        return $this->type;
    }

    /**
     * @param XdrSorobanCredentialsType $type
     */
    public function setType(XdrSorobanCredentialsType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return XdrSorobanAddressCredentials|null
     */
    public function getAddress(): ?XdrSorobanAddressCredentials
    {
        return $this->address;
    }

    /**
     * @param XdrSorobanAddressCredentials|null $address
     */
    public function setAddress(?XdrSorobanAddressCredentials $address): void
    {
        $this->address = $address;
    }

}