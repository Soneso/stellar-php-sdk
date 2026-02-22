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
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizationEntry;
use InvalidArgumentException;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType;


/**
 * Soroban authorization entry for smart contract invocations
 *
 * This class represents an authorization entry that grants permission to execute a specific
 * contract invocation. Each authorization entry contains credentials (either source account
 * or address-based) and a tree of authorized invocations representing the call hierarchy.
 *
 * Authorization entries are typically signed by the authorizing party before submission.
 *
 * @package Soneso\StellarSDK\Soroban
 * @see SorobanCredentials
 * @see SorobanAuthorizedInvocation
 * @see https://developers.stellar.org/docs/learn/smart-contract-internals/authorization Soroban Authorization
 * @since 1.0.0
 */
class SorobanAuthorizationEntry
{
    /**
     * @var SorobanCredentials credentials authorizing the invocation (source account or address-based)
     */
    public SorobanCredentials $credentials;

    /**
     * @var SorobanAuthorizedInvocation root of the authorized invocation tree
     */
    public SorobanAuthorizedInvocation $rootInvocation;

    /**
     * Creates a new Soroban authorization entry.
     *
     * @param SorobanCredentials $credentials the credentials authorizing the invocation
     * @param SorobanAuthorizedInvocation $rootInvocation the root invocation being authorized
     */
    public function __construct(SorobanCredentials $credentials, SorobanAuthorizedInvocation $rootInvocation)
    {
        $this->credentials = $credentials;
        $this->rootInvocation = $rootInvocation;
    }

    /**
     * Creates SorobanAuthorizationEntry from its XDR representation.
     *
     * @param XdrSorobanAuthorizationEntry $xdr the XDR object to decode
     * @return SorobanAuthorizationEntry the decoded authorization entry
     */
    public static function fromXdr(XdrSorobanAuthorizationEntry $xdr) : SorobanAuthorizationEntry {
        return new SorobanAuthorizationEntry(SorobanCredentials::fromXdr($xdr->credentials),
            SorobanAuthorizedInvocation::fromXdr($xdr->rootInvocation));
    }

    /**
     * Converts this object to its XDR representation.
     *
     * @return XdrSorobanAuthorizationEntry the XDR encoded authorization entry
     */
    public function toXdr(): XdrSorobanAuthorizationEntry {
        return new XdrSorobanAuthorizationEntry($this->credentials->toXdr(), $this->rootInvocation->toXdr());
    }

    /**
     * Creates SorobanAuthorizationEntry from base64-encoded XDR.
     *
     * @param string $base64Xdr the base64-encoded XDR string
     * @return SorobanAuthorizationEntry the decoded authorization entry
     * @throws \Exception if XDR decoding fails
     */
    public static function fromBase64Xdr(string $base64Xdr) : SorobanAuthorizationEntry {
        $xdr = base64_decode($base64Xdr, true);
        if ($xdr === false) {
            throw new InvalidArgumentException('Invalid base64-encoded XDR');
        }
        $xdrBuffer = new XdrBuffer($xdr);
        return SorobanAuthorizationEntry::fromXdr(XdrSorobanAuthorizationEntry::decode($xdrBuffer));
    }

    /**
     * Encodes this authorization entry as base64 XDR.
     *
     * @return string the base64-encoded XDR representation
     */
    public function toBase64Xdr() : string {
        return base64_encode($this->toXdr()->encode());
    }

    /**
     * Signs the authorization entry with the given keypair.
     *
     * The signature will be added to the signatures vector of the address credentials.
     * This method creates an Ed25519 signature over the authorization payload including
     * the network passphrase, nonce, signature expiration, and root invocation.
     *
     * @param KeyPair $signer the keypair to sign with (must match the authorized address)
     * @param Network $network the network this authorization is for (determines network passphrase)
     * @throws \RuntimeException if no address credentials are found in this entry
     */
    public function sign(KeyPair $signer, Network $network): void
    {
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
        $sigVal = $signature->toXdrSCVal();
        if ($this->credentials->addressCredentials->signature->vec != null) {
            array_push($this->credentials->addressCredentials->signature->vec, $sigVal);
        } else {
            $this->credentials->addressCredentials->signature = XdrSCVal::forVec([$sigVal]);
        }
    }

    /**
     * Returns the credentials for this authorization entry.
     *
     * @return SorobanCredentials the authorization credentials
     */
    public function getCredentials(): SorobanCredentials
    {
        return $this->credentials;
    }

    /**
     * Sets the credentials for this authorization entry.
     *
     * @param SorobanCredentials $credentials the authorization credentials
     */
    public function setCredentials(SorobanCredentials $credentials): void
    {
        $this->credentials = $credentials;
    }

    /**
     * Returns the root authorized invocation.
     *
     * @return SorobanAuthorizedInvocation the root of the invocation tree
     */
    public function getRootInvocation(): SorobanAuthorizedInvocation
    {
        return $this->rootInvocation;
    }

    /**
     * Sets the root authorized invocation.
     *
     * @param SorobanAuthorizedInvocation $rootInvocation the root of the invocation tree
     */
    public function setRootInvocation(SorobanAuthorizedInvocation $rootInvocation): void
    {
        $this->rootInvocation = $rootInvocation;
    }
}