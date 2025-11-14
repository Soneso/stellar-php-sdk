<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Crypto;

use Exception;
use ParagonIE\Sodium\Core\Ed25519;
use SodiumException;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\SEP\Derivation\HDNode;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;
use Soneso\StellarSDK\Xdr\XdrDecoratedSignature;
use Soneso\StellarSDK\Xdr\XdrMuxedAccount;
use Soneso\StellarSDK\Xdr\XdrSignerKey;
use Soneso\StellarSDK\Xdr\XdrSignerKeyType;

/**
 * Represents an Ed25519 public/private keypair for signing Stellar transactions
 *
 * A KeyPair holds the cryptographic keys used to sign transactions and identify accounts
 * on the Stellar network. Public keys are encoded as G... addresses (account IDs), while
 * private keys are encoded as S... seeds.
 *
 * Security Considerations:
 * - Private keys (seeds) must be kept secure and never transmitted or stored in plain text
 * - Use secure random generation for production keypairs
 * - Consider hardware security modules (HSM) for high-value accounts
 * - Private keys should be encrypted at rest
 * - Never log or display private keys
 *
 * Usage:
 * <code>
 * // Generate a new random keypair
 * $keyPair = KeyPair::random();
 *
 * // Load from an existing seed
 * $keyPair = KeyPair::fromSeed("SBXXX...");
 *
 * // Sign a transaction
 * $transaction->sign($keyPair, Network::testnet());
 *
 * // Get the account ID (public key)
 * $accountId = $keyPair->getAccountId(); // G...
 * </code>
 *
 * @package Soneso\StellarSDK\Crypto
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
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


    /**
     * Creates a new KeyPair from raw key bytes
     *
     * @param string $publicKey Raw 32-byte Ed25519 public key
     * @param string|null $privateKey Optional raw 32-byte Ed25519 private key (seed)
     */
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
     * Generates a new random KeyPair using cryptographically secure random bytes
     *
     * WARNING: For production use, ensure your environment has a secure random source.
     * This method uses PHP's random_bytes() which should be cryptographically secure.
     *
     * @return KeyPair A new randomly generated keypair
     * @throws Exception If secure random byte generation fails
     */
    public static function random(): KeyPair {
        return static::fromPrivateKey(random_bytes(32));
    }

    /**
     * Creates a KeyPair from a Stellar account ID (public key)
     *
     * The account ID is the base32-encoded public key starting with 'G' (or 'M' for muxed accounts).
     * Note: This creates a public-key-only keypair that cannot sign transactions.
     *
     * @param string $accountId Base32-encoded account ID (G... or M...)
     * @return KeyPair A keypair containing only the public key
     */
    public static function fromAccountId(string $accountId): KeyPair {
        $toDecode = $accountId;
        if (str_starts_with($accountId, 'M')) {
            $mux = MuxedAccount::fromMed25519AccountId($accountId);
            $toDecode = $mux->getEd25519AccountId();
        }
        return new KeyPair(StrKey::decodeAccountId($toDecode));
    }

    /**
     * Creates a KeyPair from a Stellar secret seed (private key)
     *
     * The seed is the base32-encoded private key starting with 'S'. This creates a complete
     * keypair capable of signing transactions.
     *
     * SECURITY: Handle seeds with extreme care. Never log, transmit unencrypted, or expose them.
     *
     * @param string $seed Base32-encoded secret seed starting with S
     * @return KeyPair A complete keypair with signing capabilities
     */
    public static function fromSeed(string $seed): KeyPair {
        return static::fromPrivateKey(StrKey::decodeSeed($seed));
    }

    /**
     * Creates a KeyPair from raw 32-byte private key data
     *
     * SECURITY: The private key must be kept secure and never exposed. This method accepts
     * the raw entropy bytes rather than an encoded seed.
     *
     * @param string $privateKey Raw 32-byte Ed25519 private key seed
     * @return KeyPair A complete keypair derived from the private key
     */
    public static function fromPrivateKey(string $privateKey): KeyPair{
        return new KeyPair(StrKey::publicKeyFromPrivateKey($privateKey), $privateKey);
    }

    /**
     * Creates a KeyPair from raw 32-byte public key data
     *
     * Note: This creates a public-key-only keypair that cannot sign transactions.
     *
     * @param string $publicKey Raw 32-byte Ed25519 public key
     * @return KeyPair A keypair containing only the public key
     */
    public static function fromPublicKey(string $publicKey): KeyPair {
        return new KeyPair($publicKey);
    }

    /**
     * Creates a KeyPair from a BIP-39 mnemonic phrase using hierarchical deterministic derivation
     *
     * This follows the SEP-0005 standard for deriving Stellar keypairs from mnemonics.
     * The derivation path used is m/44'/148'/{index}'.
     *
     * @param Mnemonic $mnemonic The BIP-39 mnemonic phrase
     * @param int $index The account index (0 for first account, 1 for second, etc.)
     * @param string|null $passphrase Optional BIP-39 passphrase (defaults to empty string)
     * @return KeyPair The derived keypair at the specified index
     * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0005.md
     */
    public static function fromMnemonic(Mnemonic $mnemonic, int $index, ?string $passphrase = ''): KeyPair
    {
        $seedBytes = $mnemonic->generateSeed($passphrase, 64);
        $masterNode = HDNode::newMasterNode($seedBytes);
        $accountNode = $masterNode->derivePath(sprintf("m/44'/148'/%s'", $index));
        return static::fromPrivateKey($accountNode->getPrivateKeyBytes());
    }

    /**
     * Creates a KeyPair from a BIP-39 seed hex string using hierarchical deterministic derivation
     *
     * This is similar to fromMnemonic() but accepts the seed directly as a hex string rather
     * than generating it from a mnemonic phrase. Uses SEP-0005 derivation path m/44'/148'/{index}'.
     *
     * @param string $bip39SeedHex The BIP-39 seed as a hexadecimal string
     * @param int $index The account index (0 for first account, 1 for second, etc.)
     * @return KeyPair The derived keypair at the specified index
     * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0005.md
     */
    public static function fromBip39SeedHex(string $bip39SeedHex, int $index): KeyPair
    {
        $seedBytes = hex2bin($bip39SeedHex);
        $masterNode = HDNode::newMasterNode($seedBytes);
        $accountNode = $masterNode->derivePath(sprintf("m/44'/148'/%s'", $index));
        return static::fromPrivateKey($accountNode->getPrivateKeyBytes());
    }

    /**
     * Signs data and returns a decorated signature with hint
     *
     * The decorated signature includes both the signature and a hint (last 4 bytes of public key)
     * to help identify which key signed the transaction.
     *
     * SECURITY: This method requires the private key to be present in this keypair.
     *
     * @param string $value The raw data to sign
     * @return XdrDecoratedSignature|null The decorated signature or null if signing fails
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
     * Signs a payload and returns a decorated signature with XORed hint
     *
     * This is used for signed payload signers (SEP-0023) where the hint is XORed
     * with the last 4 bytes of the payload for additional verification.
     *
     * SECURITY: This method requires the private key to be present in this keypair.
     *
     * @param string $signerPayload The signer payload to sign
     * @return XdrDecoratedSignature|null The decorated signature with XORed hint or null if signing fails
     * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0023.md
     */
    public function signPayloadDecorated(string $signerPayload): ?XdrDecoratedSignature
    {
        $payloadSignature = $this->signDecorated($signerPayload);
        $payloadSignatureHint = str_split($payloadSignature->getHint());
        $hintArr = str_split($signerPayload,1);
        $lenBytes = count($hintArr);
        if ($lenBytes >= 4) {
            $hintArr = array_slice($hintArr, $lenBytes - 4,4);
        } else {
            while (count($hintArr) < 4) {
                $hintArr[] = 0;
            }
        }
        for ($x = 0; $x < count($hintArr); $x++) {
            $hintArr[$x] ^= $payloadSignatureHint[$x];
        }
        $payloadSignature->setHint(implode($hintArr));
        return $payloadSignature;
    }

    /**
     * Converts a string into a stream resource
     *
     * Internal utility method for stream operations.
     *
     * @param string $string The string to convert
     * @return resource A stream resource
     */
    function str_to_stream(string $string)
    {
        $stream = fopen('php://memory','r+');
        fwrite($stream, $string);
        rewind($stream);
        return $stream;
    }

    /**
     * Signs data with the private key using Ed25519 signature algorithm
     *
     * SECURITY: This method requires the private key to be present in this keypair.
     * The signature is generated using the Ed25519 algorithm which is required by
     * the Stellar network.
     *
     * @param string $value The raw data to sign
     * @return string|null The raw signature bytes or null if signing fails (no private key or error)
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
     * Verifies an Ed25519 signature against a message using this keypair's public key
     *
     * @param string $signature The signature bytes to verify
     * @param string $message The original message that was signed
     * @return bool True if the signature is valid, false otherwise
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
     * Returns the signature hint (last 4 bytes of the public key)
     *
     * The hint helps identify which key signed a transaction without including
     * the full public key in the signature.
     *
     * @return string The last 4 bytes of the public key
     */
    public function getHint(): string
    {
        return substr($this->publicKey, -4);
    }

    /**
     * Returns the checksum bytes for the public key
     *
     * @return string The last 2 bytes of the public key as checksum
     */
    public function getPublicKeyChecksum(): string
    {
        $checksumBytes = substr($this->publicKey, -2);
        $unpacked = unpack('v', $checksumBytes);
        return array_shift($unpacked);
    }

    /**
     * Returns the base32-encoded secret seed (private key)
     *
     * SECURITY: The secret seed (S...) must be kept secure. Never log, transmit
     * unencrypted, or expose this value. Returns null if this is a public-key-only keypair.
     *
     * @return string|null The secret seed starting with S, or null if not available
     */
    public function getSecretSeed(): ?string
    {
        return $this->seed;
    }

    /**
     * Returns the raw 32-byte private key
     *
     * SECURITY: This is the raw entropy of the private key. Keep it secure and never
     * expose it. Returns null if this is a public-key-only keypair.
     *
     * @return string|null The raw 32-byte private key, or null if not available
     */
    public function getPrivateKey(): ?string
    {
        return $this->privateKey;
    }

    /**
     * Returns the base32-encoded account ID (public key)
     *
     * This is the Stellar address that starts with 'G' and can be safely shared publicly.
     *
     * @return string The account ID starting with G
     */
    public function getAccountId(): string
    {
        return $this->accountId;
    }

    /**
     * Returns the raw 32-byte public key
     *
     * @return string The raw 32-byte Ed25519 public key
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Derives the Ed25519 secret key from the seed
     *
     * Internal method for signature operations.
     *
     * @return string|null The 64-byte Ed25519 secret key, or null if no private key
     */
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

    /**
     * Converts this keypair to an XDR muxed account
     *
     * @return XdrMuxedAccount XDR representation as a muxed account
     */
    public function getXdrMuxedAccount() : XdrMuxedAccount {
        return new XdrMuxedAccount($this->publicKey, null);
    }

    /**
     * Converts this keypair to an XDR signer key
     *
     * @return XdrSignerKey XDR representation as a signer key
     */
    public function getXdrSignerKey() : XdrSignerKey {
        $signerKey = new XdrSignerKey();
        $signerKey->setType(new XdrSignerKeyType(XdrSignerKeyType::ED25519));
        $signerKey->setEd25519($this->getPublicKey());
        return $signerKey;
    }
}