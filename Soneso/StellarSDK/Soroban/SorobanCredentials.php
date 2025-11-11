<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentials;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType;

/**
 * Credentials for Soroban authorization
 *
 * This class represents credentials used in Soroban authorization entries. There are two types:
 * source account credentials (no addressCredentials) or address-based credentials (with addressCredentials).
 * Source account credentials use the transaction source account for authorization.
 *
 * @package Soneso\StellarSDK\Soroban
 * @see SorobanAddressCredentials
 * @see SorobanAuthorizationEntry
 * @see https://developers.stellar.org/docs/learn/smart-contract-internals/authorization Soroban Authorization
 * @since 1.0.0
 */
class SorobanCredentials
{
    /**
     * @var SorobanAddressCredentials|null address-based credentials or null for source account credentials
     */
    public ?SorobanAddressCredentials $addressCredentials = null;

    /**
     * Creates new Soroban credentials.
     *
     * @param SorobanAddressCredentials|null $addressCredentials address credentials or null for source account
     */
    public function __construct(?SorobanAddressCredentials $addressCredentials = null)
    {
        $this->addressCredentials = $addressCredentials;
    }

    /**
     * Creates source account credentials.
     *
     * Source account credentials use the transaction source account for authorization
     * without requiring additional signatures.
     *
     * @return SorobanCredentials credentials using the source account
     */
    public static function forSourceAccount() : SorobanCredentials {
        return new SorobanCredentials();
    }

    /**
     * Creates address-based credentials.
     *
     * @param Address $address the address to authorize
     * @param int $nonce unique nonce for replay protection
     * @param int $signatureExpirationLedger ledger after which signatures expire
     * @param XdrSCVal $signature the signature data
     * @return SorobanCredentials credentials using address-based authorization
     */
    public static function forAddress(Address $address, int $nonce, int $signatureExpirationLedger, XdrSCVal $signature) : SorobanCredentials {
        $addressCredentials = new SorobanAddressCredentials($address, $nonce, $signatureExpirationLedger, $signature);
        return new SorobanCredentials($addressCredentials);
    }

    /**
     * Creates credentials from existing address credentials.
     *
     * @param SorobanAddressCredentials $addressCredentials the address credentials to use
     * @return SorobanCredentials credentials using the provided address credentials
     */
    public static function forAddressCredentials(SorobanAddressCredentials $addressCredentials) : SorobanCredentials {
        return new SorobanCredentials($addressCredentials);
    }

    /**
     * Creates SorobanCredentials from its XDR representation.
     *
     * @param XdrSorobanCredentials $xdr the XDR object to decode
     * @return SorobanCredentials the decoded credentials
     */
    public static function fromXdr(XdrSorobanCredentials $xdr) : SorobanCredentials {
        if ($xdr->type->value == XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS && $xdr->address != null) {
            return new SorobanCredentials(SorobanAddressCredentials::fromXdr($xdr->address));
        }
        return new SorobanCredentials();
    }

    /**
     * Converts this object to its XDR representation.
     *
     * @return XdrSorobanCredentials the XDR encoded credentials
     */
    public function toXdr(): XdrSorobanCredentials {
        if ($this->addressCredentials != null) {
            $xdr = new XdrSorobanCredentials(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS());
            $xdr->address = $this->addressCredentials->toXdr();
            return $xdr;
        }
        return new XdrSorobanCredentials(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT());
    }

    /**
     * Returns the address credentials if using address-based authorization.
     *
     * @return SorobanAddressCredentials|null the address credentials or null for source account
     */
    public function getAddressCredentials(): ?SorobanAddressCredentials
    {
        return $this->addressCredentials;
    }

    /**
     * Sets the address credentials.
     *
     * @param SorobanAddressCredentials|null $addressCredentials the address credentials or null for source account
     */
    public function setAddressCredentials(?SorobanAddressCredentials $addressCredentials): void
    {
        $this->addressCredentials = $addressCredentials;
    }
}