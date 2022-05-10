<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use InvalidArgumentException;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Xdr\XdrAccountID;

/**
 * Data model for the <a href="https://github.com/stellar/stellar-protocol/blob/master/core/cap-0040.md#xdr-changes">signed payload signer </a>
 */
class SignedPayloadSigner
{

    const SIGNED_PAYLOAD_MAX_PAYLOAD_LENGTH = 64;

    private XdrAccountID $signerAccountId;
    private String $payload; // byte[]

    /**
     * @param XdrAccountID $signerAccountId
     * @param string $payload
     */
    public function __construct(XdrAccountID $signerAccountId, string $payload)
    {
        if (strlen($payload) > SignedPayloadSigner::SIGNED_PAYLOAD_MAX_PAYLOAD_LENGTH) {
            throw new InvalidArgumentException(sprintf("invalid payload length, must be less than  %s", SignedPayloadSigner::SIGNED_PAYLOAD_MAX_PAYLOAD_LENGTH));
        }
        $this->payload = $payload;
        $this->signerAccountId = $signerAccountId;
    }

    /**
     * @param string $accountId "G..."
     * @param string $payload
     * @return SignedPayloadSigner
     */
    public static function fromAccountId(string $accountId, string $payload) : SignedPayloadSigner {
        return new SignedPayloadSigner(new XdrAccountID($accountId), $payload);
    }

    /**
     * @param string $publicKey bytes of ED25519 public key
     * @param string $payload
     * @return SignedPayloadSigner
     */
    public static function fromPublicKey(string $publicKey, string $payload) : SignedPayloadSigner {
        return new SignedPayloadSigner(new XdrAccountID(StrKey::encodeAccountId($publicKey)), $payload);
    }

    /**
     * @return XdrAccountID
     */
    public function getSignerAccountId(): XdrAccountID
    {
        return $this->signerAccountId;
    }

    /**
     * @return string
     */
    public function getPayload(): string
    {
        return $this->payload;
    }

}