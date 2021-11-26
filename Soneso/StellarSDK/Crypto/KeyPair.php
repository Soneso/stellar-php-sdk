<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Crypto;

use Exception;
use ParagonIE\Sodium\Core\Ed25519;
use SodiumException;
use Soneso\StellarSDK\SEP\Derivation\Bip39;
use Soneso\StellarSDK\SEP\Derivation\HDNode;
use Soneso\StellarSDK\Xdr\XdrDecoratedSignature;
use Soneso\StellarSDK\Xdr\XdrMuxedAccount;

/**
 * A public/private keypair for use with the Stellar network
 */
class KeyPair
{


    /**
     * Base-32 encoded public key
     *
     * @var string
     */
    private string $accountId;

    /**
     * Bytes of the public key
     *
     * @var string
     */
    private string $publicKey;

    /**
     * Base-32 encoded seed
     *
     * @var string|null
     */
    private ?string $seed = null;

    /**
     * Bytes of the private key
     *
     * @var string|null
     */
    private ?string $privateKey = null;


    public function __construct(string $publicKey, ?string $privateKey = null)
    {
        $this->publicKey = $publicKey;
        $this->accountId = StrKey::encodeAccountId($publicKey);
        if ($privateKey) {
            $this->privateKey = $privateKey;
            $this->seed = StrKey::encodeSeed($privateKey);
        }
    }

    /**
     * Creates a new random KeyPair.
     *
     * @return KeyPair
     * @throws Exception
     */
    public static function random(): KeyPair {
        return static::fromPrivateKey(random_bytes(32));
    }

    /**
     * Creates a new keypair from a base-32 encoded account ID String (S...)
     * @param string $accountId
     * @return KeyPair
     */
    public static function fromAccountId(string $accountId): KeyPair {
        return new KeyPair(StrKey::decodeAccountId($accountId));
    }

    /**
     * Creates a new keypair from a base-32 encoded seed string (S...)
     *
     * @param string $seed Base32 encoded string starting with S.
     * @return KeyPair the new generated KeyPair from the passed seed.
     */
    public static function fromSeed(string $seed): KeyPair {
        return static::fromPrivateKey(StrKey::decodeSeed($seed));
    }

    /**
     * Creates a new keypair from 32 bytes of entropy (private key)
     *
     * @param string $privateKey raw private key of 32 bytes.
     * @return KeyPair the new generated KeyPair from the passed raw private key.
     */
    public static function fromPrivateKey(string $privateKey): KeyPair{
        return new KeyPair(StrKey::publicKeyFromPrivateKey($privateKey), $privateKey);
    }

    /**
     * Creates a new keypair from 32 bytes public key data.
     * @param string $publicKey raw public key of 32 bytes.
     * @return KeyPair the new generated KeyPair from the passed public key.
     */
    public static function fromPublicKey(string $publicKey): KeyPair {
        return new KeyPair($publicKey);
    }

    /**
     * Creates a new keypair from a mnemonic, passphrase (optional) and index (defaults to 0)
     *
     * For more details, see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0005.md
     *
     * @param string $mnemonic
     * @param string $passphrase
     * @param int $index
     * @return Keypair
     */
    public static function fromMnemonic(string $mnemonic, string $passphrase = '', int $index = 0): KeyPair
    {
        $bip39 = new Bip39();
        $seedBytes = $bip39->mnemonicToSeedBytesWithErrorChecking($mnemonic, $passphrase);

        $masterNode = HDNode::newMasterNode($seedBytes);

        $accountNode = $masterNode->derivePath(sprintf("m/44'/148'/%s'", $index));

        return static::fromPrivateKey($accountNode->getPrivateKeyBytes());
    }

    /**
     * @param string $value
     * @return ?XdrDecoratedSignature
     */
    public function signDecorated(string $value): ?XdrDecoratedSignature
    {
        $sig = $this->sign($value);
        if (!$sig){
            return null;
        }

        return new XdrDecoratedSignature($this->getHint(),$sig);
    }

    /**
     * Signs the specified $value with the private key
     *
     * @param string $value
     * @return ?string - raw bytes representing the signature if signing is possible.
     */
    public function sign(string $value): ?string
    {
        try {
            return Ed25519::sign_detached($value, $this->getEd25519SecretKey());
        } catch (SodiumException $e) {
            return null;
        }
    }

    /**
     * @param string $signature
     * @param string $message
     * @return bool
     */
    public function verifySignature(string $signature, string $message): bool
    {
        try {
            return Ed25519::verify_detached($signature, $message, $this->publicKey);
        } catch (SodiumException $e) {
            return false;
        }
    }

    /**
     * Returns the last 4 characters of the public key
     *
     * @return string
     */
    public function getHint(): string
    {
        return substr($this->publicKey, -4);
    }

    /**
     * Returns the raw bytes of the checksum for the public key
     *
     * @return string
     */
    public function getPublicKeyChecksum(): string
    {
        $checksumBytes = substr($this->getPublicKeyBytes(), -2);
        $unpacked = unpack('v', $checksumBytes);
        return array_shift($unpacked);
    }

    /**
     * Returns the base-32 encoded private key (seed) (S...)
     * @return ?string
     */
    public function getSecretSeed(): ?string
    {
        return $this->seed;
    }

    /**
     * Returns raw data private key 32 bytes is available.
     * @return ?string
     */
    public function getPrivateKey(): ?string
    {
        return $this->privateKey;
    }

    /**
     * Returns the base-32 encoded public key - accountId (G...)
     * @return string
     */
    public function getAccountId(): string
    {
        return $this->accountId;
    }

    /**
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    protected function getEd25519SecretKey(): ?string
    {
        if (!$this->privateKey) {
            return null;
        }

        $sk = '';

        try {
            $pk = '';
            Ed25519::seed_keypair($pk, $sk, $this->privateKey);
        } catch (SodiumException $e) {
            return null;
        }

        return $sk;
    }

    public function getXdrMuxedAccount() : XdrMuxedAccount {
        return new XdrMuxedAccount($this->publicKey, null);
    }
}