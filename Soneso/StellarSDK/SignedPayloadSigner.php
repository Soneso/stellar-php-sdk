<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use InvalidArgumentException;
use Soneso\StellarSDK\Constants\StellarConstants;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Xdr\XdrAccountID;

/**
 * Data model for the <a href="https://github.com/stellar/stellar-protocol/blob/master/core/cap-0040.md#xdr-changes">signed payload signer </a>
 */
class SignedPayloadSigner
{

    private XdrAccountID $signerAccountId;
    private String $payload; // byte[]

    /**
     * @param XdrAccountID $signerAccountId ed25519 account id ("G...") of the signer
     * @param string $payload
     * @throws InvalidArgumentException when $payload is empty or longer than 64 bytes,
     * or when $signerAccountId does not hold a valid "G..." strkey
     */
    public function __construct(XdrAccountID $signerAccountId, string $payload)
    {
        $len = strlen($payload);
        if ($len < 1 || $len > StellarConstants::SIGNED_PAYLOAD_MAX_LENGTH_BYTES) {
            throw new InvalidArgumentException(sprintf(
                'invalid payload length %d, must be between 1 and %d bytes',
                $len,
                StellarConstants::SIGNED_PAYLOAD_MAX_LENGTH_BYTES
            ));
        }
        self::checkEd25519AccountId($signerAccountId->getAccountId());
        $this->payload = $payload;
        $this->signerAccountId = $signerAccountId;
    }

    /**
     * @param string $accountId ed25519 account id ("G...")
     * @param string $payload
     * @return SignedPayloadSigner
     * @throws InvalidArgumentException when $payload is empty or longer than 64 bytes,
     * or when $accountId is not a valid "G..." strkey
     */
    public static function fromAccountId(string $accountId, string $payload) : SignedPayloadSigner {
        return new SignedPayloadSigner(new XdrAccountID($accountId), $payload);
    }

    /**
     * @param string $publicKey bytes of ED25519 public key
     * @param string $payload
     * @return SignedPayloadSigner
     * @throws InvalidArgumentException when $payload is empty or longer than 64 bytes,
     * or when $publicKey is not 32 bytes long
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

    /**
     * Checks that the given account id is an ed25519 public key strkey ("G...").
     *
     * CAP-40 defines the signer over a raw ed25519 public key. A muxed account id
     * ("M...") additionally carries a multiplexing id that the signer has no room
     * for, so only the ed25519 form is accepted here.
     *
     * @param string $accountId account id to check
     * @throws InvalidArgumentException when $accountId is not a valid "G..." strkey
     */
    private static function checkEd25519AccountId(string $accountId): void
    {
        if (StrKey::isValidAccountId($accountId)) {
            return;
        }
        if (StrKey::isValidMuxedAccountId($accountId)) {
            throw new InvalidArgumentException(
                'a signed payload signer takes an ed25519 account id (G...), not a muxed account id (M...)'
            );
        }
        // An "S..." value is secret key material. Name the mistake without echoing it,
        // since exception messages reach logs.
        if (str_starts_with($accountId, 'S')) {
            throw new InvalidArgumentException(
                'a signed payload signer takes an ed25519 account id (G...), not a secret seed (S...)'
            );
        }
        throw new InvalidArgumentException('invalid ed25519 account id: ' . $accountId);
    }

}