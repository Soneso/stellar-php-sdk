<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Util\Hash;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrEnvelopeType;
use Soneso\StellarSDK\Xdr\XdrHashIDPreimage;
use Soneso\StellarSDK\Xdr\XdrHashIDPreimageSorobanAuthorization;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizationEntry;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType;


class SorobanAuthorizationEntry
{
    public SorobanCredentials $credentials;
    public SorobanAuthorizedInvocation $rootInvocation;

    /**
     * @param SorobanCredentials $credentials
     * @param SorobanAuthorizedInvocation $rootInvocation
     */
    public function __construct(SorobanCredentials $credentials, SorobanAuthorizedInvocation $rootInvocation)
    {
        $this->credentials = $credentials;
        $this->rootInvocation = $rootInvocation;
    }

    public static function fromXdr(XdrSorobanAuthorizationEntry $xdr) : SorobanAuthorizationEntry {
        return new SorobanAuthorizationEntry(SorobanCredentials::fromXdr($xdr->credentials),
            SorobanAuthorizedInvocation::fromXdr($xdr->rootInvocation));
    }

    public function toXdr(): XdrSorobanAuthorizationEntry {
        return new XdrSorobanAuthorizationEntry($this->credentials->toXdr(), $this->rootInvocation->toXdr());
    }


    public static function fromBase64Xdr(String $base64Xdr) : SorobanAuthorizationEntry {
        $xdr = base64_decode($base64Xdr);
        $xdrBuffer = new XdrBuffer($xdr);
        return SorobanAuthorizationEntry::fromXdr(XdrSorobanAuthorizationEntry::decode($xdrBuffer));
    }

    public function toBase64Xdr() : String {
        return base64_encode($this->toXdr()->encode());
    }

    /**
     * Signs the authorization entry. The signature will be added to the signatureArgs of the soroban credentials
     * @param KeyPair $signer
     * @param Network $network
     */
    public function sign(KeyPair $signer, Network $network) {
        $xdrCredentials = $this->credentials->toXdr();
        if ($this->credentials->addressCredentials == null ||
            $xdrCredentials->type->value != XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS ||
            $xdrCredentials->address == null) {
            throw new \RuntimeException("no soroban address credentials found");
        }

        $networkId = Hash::generate($network->getNetworkPassphrase());
        $authPreimageXdr = new XdrHashIDPreimageSorobanAuthorization($networkId, $xdrCredentials->address->nonce,
            $xdrCredentials->address->signatureExpirationLedger, $this->rootInvocation->toXdr());
        $rootInvocationPreimage = new XdrHashIDPreimage(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_SOROBAN_AUTHORIZATION));
        $rootInvocationPreimage->sorobanAuthorization = $authPreimageXdr;

        $payload = Hash::generate($rootInvocationPreimage->encode()); // sha256
        $signatureBytes = $signer->sign($payload);
        $signature = new AccountEd25519Signature($signer->getPublicKey(), $signatureBytes);
        array_push($this->credentials->addressCredentials->signatureArgs, $signature->toXdrSCVal());
    }

    /**
     * @return SorobanCredentials
     */
    public function getCredentials(): SorobanCredentials
    {
        return $this->credentials;
    }

    /**
     * @param SorobanCredentials $credentials
     */
    public function setCredentials(SorobanCredentials $credentials): void
    {
        $this->credentials = $credentials;
    }

    /**
     * @return SorobanAuthorizedInvocation
     */
    public function getRootInvocation(): SorobanAuthorizedInvocation
    {
        return $this->rootInvocation;
    }

    /**
     * @param SorobanAuthorizedInvocation $rootInvocation
     */
    public function setRootInvocation(SorobanAuthorizedInvocation $rootInvocation): void
    {
        $this->rootInvocation = $rootInvocation;
    }
}