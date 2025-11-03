<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Xdr\XdrSignedPayload;
use Soneso\StellarSDK\Xdr\XdrSignerKey;
use Soneso\StellarSDK\Xdr\XdrSignerKeyType;

/**
 * Helper class for creating signer keys used in multi-signature accounts
 *
 * This class provides factory methods for creating different types of signers
 * that can be added to Stellar accounts. Signers enable multi-signature setups
 * and advanced authorization schemes.
 *
 * Supported signer types:
 * - Ed25519 public key: Standard account signer
 * - SHA256 hash: Hash preimage signer for hash-locked transactions
 * - Pre-authorized transaction: Specific transaction hash signer
 * - Signed payload: Ed25519 key with additional signature data
 *
 * @package Soneso\StellarSDK
 * @see https://developers.stellar.org/docs/encyclopedia/signatures-multisig Documentation on multi-signature
 */
class Signer
{
    /**
     * Creates an Ed25519 public key signer
     *
     * @param KeyPair $keyPair The key pair containing the public key
     * @return XdrSignerKey The signer key object
     */
    public static function ed25519PublicKey(KeyPair $keyPair) : XdrSignerKey {
        return $keyPair->getXdrSignerKey();
    }

    /**
     * Creates a SHA256 hash signer from a strkey encoded hash
     *
     * @param string $sha256HashKey The hash in strkey format (starts with X...)
     * @return XdrSignerKey The signer key object
     */
    public static function sha256Hash(String $sha256HashKey) : XdrSignerKey {
        $signerKey = new XdrSignerKey();
        $signerKey->setType(new XdrSignerKeyType(XdrSignerKeyType::HASH_X));
        $signerKey->setHashX(StrKey::decodeSha256Hash($sha256HashKey));
        return $signerKey;
    }

    /**
     * Creates a pre-authorized transaction signer from a transaction
     *
     * This signer authorizes a specific transaction in advance. The transaction
     * can be submitted later without requiring additional signatures.
     *
     * @param Transaction $tx The transaction to pre-authorize
     * @param Network $network The network for which to authorize the transaction
     * @return XdrSignerKey The signer key object
     */
    public static function preAuthTx(Transaction $tx, Network $network) : XdrSignerKey {
        $signerKey = new XdrSignerKey();
        $type = new XdrSignerKeyType(XdrSignerKeyType::PRE_AUTH_TX);
        $signerKey->setType($type);
        $signerKey->setPreAuthTx($tx->hash($network));
        return $signerKey;
    }

    /**
     * Creates a pre-authorized transaction signer from a strkey encoded hash
     *
     * @param string $preAuthTxKey The transaction hash in strkey format (starts with T...)
     * @return XdrSignerKey The signer key object
     */
    public static function preAuthTxHash(String $preAuthTxKey) : XdrSignerKey {
        $signerKey = new XdrSignerKey();
        $signerKey->setType(new XdrSignerKeyType(XdrSignerKeyType::PRE_AUTH_TX));
        $signerKey->setPreAuthTx(StrKey::decodePreAuthTx($preAuthTxKey));
        return $signerKey;
    }

    /**
     * Creates a signed payload signer
     *
     * This signer type includes an Ed25519 key along with additional payload data
     * that must be signed. Useful for advanced signature schemes.
     *
     * @param SignedPayloadSigner $signedPayloadSigner The signed payload signer configuration
     * @return XdrSignerKey The signer key object
     */
    public static function signedPayload(SignedPayloadSigner $signedPayloadSigner) : XdrSignerKey {
        $signerKey = new XdrSignerKey();
        $type = new XdrSignerKeyType(XdrSignerKeyType::ED25519_SIGNED_PAYLOAD);
        $signerKey->setType($type);
        $pk = (KeyPair::fromAccountId($signedPayloadSigner->getSignerAccountId()->getAccountId()))->getPublicKey();
        $payloadSigner = new XdrSignedPayload($pk, $signedPayloadSigner->getPayload());
        $signerKey->setSignedPayload($payloadSigner);
        return $signerKey;
    }

}