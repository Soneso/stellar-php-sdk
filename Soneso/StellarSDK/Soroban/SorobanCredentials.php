<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use Soneso\StellarSDK\Xdr\XdrSorobanCredentials;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType;


class SorobanCredentials
{
    public ?SorobanAddressCredentials $addressCredentials = null;

    /**
     * @param SorobanAddressCredentials|null $addressCredentials
     */
    public function __construct(?SorobanAddressCredentials $addressCredentials = null)
    {
        $this->addressCredentials = $addressCredentials;
    }

    /**
     * @return SorobanCredentials
     */
    public static function forSourceAccount() : SorobanCredentials {
        return new SorobanCredentials();
    }

    /**
     * @param Address $address
     * @param int $nonce
     * @param int $signatureExpirationLedger
     * @param array|null $signatureArgs
     * @return SorobanCredentials
     */
    public static function forAddress(Address $address, int $nonce, int $signatureExpirationLedger, ?array $signatureArgs = array()) : SorobanCredentials {
        $addressCredentials = new SorobanAddressCredentials($address, $nonce, $signatureExpirationLedger, $signatureArgs);
        return new SorobanCredentials($addressCredentials);
    }

    /**
     * @param SorobanAddressCredentials $addressCredentials
     * @return SorobanCredentials
     */
    public static function forAddressCredentials(SorobanAddressCredentials $addressCredentials) : SorobanCredentials {
        return new SorobanCredentials($addressCredentials);
    }

    public static function fromXdr(XdrSorobanCredentials $xdr) : SorobanCredentials {
        if ($xdr->type->value == XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS && $xdr->address != null) {
            return new SorobanCredentials(SorobanAddressCredentials::fromXdr($xdr->address));
        }
        return new SorobanCredentials();
    }

    public function toXdr(): XdrSorobanCredentials {
        if ($this->addressCredentials != null) {
            $xdr = new XdrSorobanCredentials(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS());
            $xdr->address = $this->addressCredentials->toXdr();
            return $xdr;
        }
        return new XdrSorobanCredentials(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT());
    }

    /**
     * @return SorobanAddressCredentials|null
     */
    public function getAddressCredentials(): ?SorobanAddressCredentials
    {
        return $this->addressCredentials;
    }

    /**
     * @param SorobanAddressCredentials|null $addressCredentials
     */
    public function setAddressCredentials(?SorobanAddressCredentials $addressCredentials): void
    {
        $this->addressCredentials = $addressCredentials;
    }
}