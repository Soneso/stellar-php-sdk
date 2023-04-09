<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Util\Hash;
use Soneso\StellarSDK\Xdr\XdrAddressWithNonce;
use Soneso\StellarSDK\Xdr\XdrContractAuth;
use Soneso\StellarSDK\Xdr\XdrHashIDPreimage;
use Soneso\StellarSDK\Xdr\XdrHashIDPreimageContractAuth;
use Soneso\StellarSDK\Xdr\XdrSCVal;

/**
 * Represents a contract authorization.
 * See Soroban Documentation - Authorization <https://soroban.stellar.org/docs/learn/authorization> for more information.
 */
class ContractAuth
{
    public AuthorizedInvocation $rootInvocation;
    public array $signatureArgs;
    public ?Address $address = null;
    public ?int $nonce = null;

    /**
     * @param AuthorizedInvocation $rootInvocation The root invocation.
     * @param array $signatureArgs The signature arguments. array of XdrSCVal.
     * @param Address|null $address The address, must be set if nonce is set.
     * @param int|null $nonce The nonce, must be set if address is set.
     */
    public function __construct(AuthorizedInvocation $rootInvocation, array $signatureArgs = array(), ?Address $address = null, ?int $nonce = null)
    {
        $this->rootInvocation = $rootInvocation;
        $this->signatureArgs = $signatureArgs;
        $this->address = $address;
        $this->nonce = $nonce;
        if (($this->address != null && $this->nonce === null) || ($this->nonce != null && $this->address == null)) {
            throw new \InvalidArgumentException("address and nonce must both be set or both be null");
        }
    }

    /** Sign the contract authorization, the signature will be added to the `signature_args`
     *  For custom accounts, this signature format may not be applicable.
     *  See Soroban Documentation - Stellar Account Signatures <https://soroban.stellar.org/docs/how-to-guides/invoking-contracts-with-transactions#stellar-account-signatures>
     * @param KeyPair $signer
     * @param Network $network
     */
    public function sign(KeyPair $signer, Network $network) {
        if ($this->address == null || $this->nonce === null) {
            throw new \RuntimeException("address and nonce must be set.");
        }

        $networkId = Hash::generate($network->getNetworkPassphrase());
        $contractAuthPreimageXdr = new XdrHashIDPreimageContractAuth($networkId, $this->nonce, $this->rootInvocation->toXdr());
        $rootInvocationPreimage = XdrHashIDPreimage::forContractAuth($contractAuthPreimageXdr)->encode();
        $payload = Hash::generate($rootInvocationPreimage); // sha256
        $signatureBytes = $signer->sign($payload);
        $signature = new AccountEd25519Signature($signer->getPublicKey(), $signatureBytes);
        array_push($this->signatureArgs, $signature->toXdrSCVal());

    }

    public function toXdr(): XdrContractAuth {
        $addressWithNonce = null;
        if ($this->address != null && $this->nonce !== null) {
            $addressWithNonce = new XdrAddressWithNonce($this->address->toXdr(), $this->nonce);
        }
        $sigArgs = array(); // See: https://discord.com/channels/897514728459468821/1076723574884282398/1078095366890729595
        if (count($this->signatureArgs) > 0) {
           array_push($sigArgs, XdrSCVal::forVec($this->signatureArgs));
        }
        return new XdrContractAuth($addressWithNonce,$this->rootInvocation->toXdr(), $sigArgs);
    }

    public static function fromXdr(XdrContractAuth $xdr) : ContractAuth {
        $address = null;
        $nonce = null;
        if ($xdr->addressWithNonce != null) {
            $address = Address::fromXdr($xdr->addressWithNonce->address);
            $nonce = $xdr->addressWithNonce->nonce;
        }
        $rootInvocation = $xdr->rootInvocation;
        $xdrArgs = $xdr->signatureArgs;
        $sigArgs = array();
        if(count($xdrArgs) > 0) { // See: https://discord.com/channels/897514728459468821/1076723574884282398/1078095366890729595
            $val = $xdrArgs[0];
            if ($val instanceof XdrSCVal && $val->vec != null) {
                $sigArgs = $val->vec;
            }
        }

        return new ContractAuth(AuthorizedInvocation::fromXdr($rootInvocation),
            $sigArgs, $address ,$nonce);
    }

}