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
 * Helper class that creates XdrSignerKey objects.
 */
class Signer
{
    /**
     * @param KeyPair $keyPair
     * @return XdrSignerKey
     */
    public static function ed25519PublicKey(KeyPair $keyPair) : XdrSignerKey {
        return $keyPair->getXdrSignerKey();
    }

    /**
     * @param String $sha256HashKey starts with T...
     * @return XdrSignerKey
     */
    public static function sha256Hash(String $sha256HashKey) : XdrSignerKey {
        $signerKey = new XdrSignerKey();
        $signerKey->setType(new XdrSignerKeyType(XdrSignerKeyType::HASH_X));
        $signerKey->setHashX(StrKey::decodeSha256Hash($sha256HashKey));
        return $signerKey;
    }

    /**
     * @param Transaction $tx
     * @param Network $network
     * @return XdrSignerKey
     */
    public static function preAuthTx(Transaction $tx, Network $network) : XdrSignerKey {
        $signerKey = new XdrSignerKey();
        $type = new XdrSignerKeyType(XdrSignerKeyType::PRE_AUTH_TX);
        $signerKey->setType($type);
        $signerKey->setPreAuthTx($tx->hash($network));
        return $signerKey;
    }

    /**
     * @param String $preAuthTxKey starts with X...
     * @return XdrSignerKey
     */
    public static function preAuthTxHash(String $preAuthTxKey) : XdrSignerKey {
        $signerKey = new XdrSignerKey();
        $signerKey->setType(new XdrSignerKeyType(XdrSignerKeyType::PRE_AUTH_TX));
        $signerKey->setPreAuthTx(StrKey::decodePreAuth($preAuthTxKey));
        return $signerKey;
    }

    /**
     * @param SignedPayloadSigner $signedPayloadSigner
     * @return XdrSignerKey
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