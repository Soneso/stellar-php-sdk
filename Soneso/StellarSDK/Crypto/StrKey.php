<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Crypto;

use Base32\Base32;
use Exception;
use InvalidArgumentException;
use SodiumException;
use Soneso\StellarSDK\Constants\CryptoConstants;
use Soneso\StellarSDK\Constants\StellarConstants;
use Soneso\StellarSDK\SignedPayloadSigner;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrSignedPayload;
use function preg_replace;

/**
 * StrKey is a helper class that allows encoding and decoding Stellar keys
 * to/from strings, i.e. between their binary and string (i.e. "GABCD...", etc.)
 * representations.
 *
 * @package Soneso\StellarSDK\Crypto
 * @see https://developers.stellar.org Stellar developer docs Address types documentation
 */
class StrKey
{
    /**
     * Bytes a decoded strkey spends on framing: a 1-byte version prefix and a
     * 2-byte CRC-16 checksum. The payload sits between them, so decoded data
     * shorter than this cannot carry a strkey of any type.
     */
    private const DECODED_FRAMING_LENGTH = 3;

    /**
     * Returns true if the given Stellar account id ("G...") is a valid ed25519 public key.
     * @param string $accountId account id ("G...") to check
     * @return bool true if valid
     */
    public static function isValidAccountId(string $accountId) : bool {
        return static::isValid(VersionByte::ACCOUNT_ID, $accountId);
    }

    /**
     * Encodes `data` to strkey account id (ed25519 public key).
     *
     * @param string $data raw data to encode
     * @return string "G..." representation of the key
     * @throws InvalidArgumentException when $data is not 32 bytes long
     */
    public static function encodeAccountId(string $data) : string {
        return static::encodeCheck(VersionByte::ACCOUNT_ID, $data);
    }

    /**
     * Decodes strkey account id (ed25519 public key) to raw data.
     * @param string $accountId "G..." key representation to decode
     * @return string raw key
     * @throws InvalidArgumentException when $accountId is not a valid "G..." strkey:
     * wrong encoded length, wrong version byte, bad checksum, or a payload that is
     * not 32 bytes
     */
    public static function decodeAccountId(string $accountId) : string {
        return static::decodeCheck(VersionByte::ACCOUNT_ID, $accountId);
    }

    /**
     * Returns true if the given Stellar muxed account id ("M...") is a valid med25519 public key.
     * @param string $muxedAccountId muxed account id ("M...") to check
     * @return bool true if valid
     */
    public static function isValidMuxedAccountId(string $muxedAccountId) : bool {
        return static::isValid(VersionByte::MUXED_ACCOUNT_ID, $muxedAccountId);
    }

    /**
     * Encodes data to strkey muxed account id (med25519 public key).
     * @param string $data data data to encode
     * @return string "M..." representation of the key
     * @throws InvalidArgumentException when $data is not 40 bytes long
     */
    public static function encodeMuxedAccountId(string $data) : string {
        return static::encodeCheck(VersionByte::MUXED_ACCOUNT_ID, $data);
    }

    /**
     * Decodes strkey muxed account id (med25519 public key) to raw data.
     * @param string $muxedAccountId address data to decode ("M...")
     * @return string raw key
     * @throws InvalidArgumentException when $muxedAccountId is not a valid "M..." strkey:
     * wrong encoded length, wrong version byte, bad checksum, or a payload that is
     * not 40 bytes
     */
    public static function decodeMuxedAccountId(string $muxedAccountId) : string {
        return static::decodeCheck(VersionByte::MUXED_ACCOUNT_ID, $muxedAccountId);
    }

    /**
     * Returns true if the given Stellar secret key ("S...") is a valid ed25519 secret seed.
     * @param string $seed seed to check ("S...")
     * @return bool true if valid
     */
    public static function isValidSeed(string $seed) : bool {
        return static::isValid(VersionByte::SEED, $seed);
    }

    /**
     * Encodes data to strkey ed25519 seed ("S...")
     * @param string $data data to encode
     * @return string "S..." representation of the seed
     * @throws InvalidArgumentException when $data is not 32 bytes long
     */
    public static function encodeSeed(string $data) : string {
        return static::encodeCheck(VersionByte::SEED, $data);
    }

    /**
     * Decodes strkey ed25519 seed ("S...") to raw data.
     * @param string $seed seed ("S...") to decode
     * @return string raw seed data
     * @throws InvalidArgumentException when $seed is not a valid "S..." strkey:
     * wrong encoded length, wrong version byte, bad checksum, or a payload that is
     * not 32 bytes
     */
    public static function decodeSeed(string $seed) : string {
        return static::decodeCheck(VersionByte::SEED, $seed);
    }

    /**
     * Returns true if the given strkey PreAuthTx ("T...") is a valid stellar PreAuthTx strkey representation.
     * @param string $preAuth strkey PreAuthTx ("T...") to check
     * @return bool true if valid stellar PreAuthTx strkey representation.
     */
    public static function isValidPreAuthTx(string $preAuth) : bool {
        return static::isValid(VersionByte::PRE_AUTH_TX, $preAuth);
    }

    /**
     * Encodes data to strkey PreAuthTx ("T...").
     * @param string $data data to encode
     * @return string "T..." representation of the PreAuthTx
     * @throws InvalidArgumentException when $data is not 32 bytes long
     */
    public static function encodePreAuthTx(string $data) : string {
        return static::encodeCheck(VersionByte::PRE_AUTH_TX, $data);
    }

    /**
     * Decodes strkey PreAuthTx ("T...") to raw data.
     * @param string $preAuth PreAuthTx ("T...") to decode
     * @return string raw PreAuthTx data
     * @throws InvalidArgumentException when $preAuth is not a valid "T..." strkey:
     * wrong encoded length, wrong version byte, bad checksum, or a payload that is
     * not 32 bytes
     */
    public static function decodePreAuthTx(string $preAuth) : string {
        return static::decodeCheck(VersionByte::PRE_AUTH_TX, $preAuth);
    }

    /**
     * Returns true if the given strkey Sha256Hash ("X...") is a valid stellar Sha256Hash strkey representation.
     * @param string $hash strkey Sha256Hash ("X...") to ckeck
     * @return bool true if valid stellar Sha256Hash strkey representation.
     */
    public static function isValidSha256Hash(string $hash) : bool {
        return static::isValid(VersionByte::SHA256_HASH, $hash);
    }

    /**
     * Encodes data to strkey sha256 hash ("X...").
     * @param string $data data to encode
     * @return string "X..." representation of the sha256 hash
     * @throws InvalidArgumentException when $data is not 32 bytes long
     */
    public static function encodeSha256Hash(string $data) : string {
        return static::encodeCheck(VersionByte::SHA256_HASH, $data);
    }

    /**
     * Decodes strkey sha256 hash ("X...") to raw data.
     * @param string $hash strkey sha256 hash ("X...") to decode
     * @return string raw sha256 hash data.
     * @throws InvalidArgumentException when $hash is not a valid "X..." strkey:
     * wrong encoded length, wrong version byte, bad checksum, or a payload that is
     * not 32 bytes
     */
    public static function decodeSha256Hash(string $hash) : string {
        return static::decodeCheck(VersionByte::SHA256_HASH, $hash);
    }

    /**
     * Returns true if the given strkey representation ("P...") is a valid
     * signed payload: correct structure and checksum, a payload of 1 to 64
     * bytes, zero padding after the payload, and no data beyond that padding.
     * @param string $signedPayload signed payload ("P...") to check
     * @return bool true if valid signed payload strkey representation.
     */
    public static function isValidSignedPayload(string $signedPayload) : bool {
        $rule = self::strkeyLengthRule(VersionByte::SIGNED_PAYLOAD);
        $length = strlen($signedPayload);
        if ($length < $rule['minEncodedLength'] || $length > $rule['maxEncodedLength']) {
            return false;
        }
        try {
            self::decodeXdrSignedPayload($signedPayload);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Encodes a SignedPayloadSigner to strkey signed payload (P...).
     * @param SignedPayloadSigner $signedPayloadSigner SignedPayloadSigner to encode
     * @return string "P..." representation of the signed payload
     * @throws InvalidArgumentException when the payload is empty or longer than 64 bytes,
     * or when the signer account id is not a valid "G..." strkey
     */
    public static function encodeSignedPayload(SignedPayloadSigner $signedPayloadSigner) : string {
        // SignedPayloadSigner enforces the SEP-23 bounds at construction;
        // the check here covers any future relaxation of that invariant.
        self::checkSignedPayloadLength($signedPayloadSigner->getPayload());
        $pk = self::decodeAccountId($signedPayloadSigner->getSignerAccountId()->getAccountId());
        $signedPayload = new XdrSignedPayload($pk, $signedPayloadSigner->getPayload());
        $data = $signedPayload->encode();
        return static::encodeCheck(VersionByte::SIGNED_PAYLOAD, $data);
    }

    /**
     * Encodes a XdrSignedPayload to strkey signed payload (P...).
     * @param XdrSignedPayload $signedPayload XdrSignedPayload to encode
     * @return string "P..." representation of the signed payload
     * @throws InvalidArgumentException when the payload is empty or longer than 64 bytes
     */
    public static function encodeXdrSignedPayload(XdrSignedPayload $signedPayload) : string {
        self::checkSignedPayloadLength($signedPayload->getPayload());
        $data = $signedPayload->encode();
        return static::encodeCheck(VersionByte::SIGNED_PAYLOAD, $data);
    }

    /**
     * Decodes strkey signed payload ("P...") to a SignedPayloadSigner object.
     * @param string $signedPayload signed payload ("P...") to decode
     * @return SignedPayloadSigner object decoded from the given strkey signed payload
     * @throws InvalidArgumentException when $signedPayload is not a valid "P..." strkey:
     * an encoded length outside the SEP-23 bounds, a wrong version byte, a bad checksum,
     * or a decoded payload that is empty, longer than 64 bytes, padded with a non-zero
     * byte, or followed by data beyond its padding
     */
    public static function decodeSignedPayload(string $signedPayload) : SignedPayloadSigner {
        $xdrPayloadSigner = self::decodeXdrSignedPayload($signedPayload);
        return SignedPayloadSigner::fromPublicKey($xdrPayloadSigner->getEd25519(), $xdrPayloadSigner->getPayload());
    }

    /**
     * Decodes strkey signed payload ("P...") to a XdrSignedPayload object.
     * @param string $signedPayload signed payload ("P...") to decode
     * @return XdrSignedPayload object decoded from the given strkey signed payload
     * @throws InvalidArgumentException when $signedPayload is not a valid "P..." strkey:
     * an encoded length outside the SEP-23 bounds, a wrong version byte, a bad checksum,
     * or a decoded payload that is empty, longer than 64 bytes, padded with a non-zero
     * byte, or followed by data beyond its padding
     */
    public static function decodeXdrSignedPayload(string $signedPayload) : XdrSignedPayload {
        $signedPayloadRaw = self::decodeCheck(VersionByte::SIGNED_PAYLOAD, $signedPayload);
        $xdr = new XdrBuffer($signedPayloadRaw);
        $result = XdrSignedPayload::decode($xdr);
        self::checkSignedPayloadLength($result->getPayload());
        self::checkSignedPayloadFraming($signedPayloadRaw, $result->getPayload());
        return $result;
    }

    /**
     * Returns true if the given strkey representation of the contract id ("C...") is a valid contract id.
     * @param string $contractId contract id ("C...") to check
     * @return bool true if valid contract id strkey representation.
     */
    public static function isValidContractId(string $contractId) : bool {
        return static::isValid(VersionByte::CONTRACT_ID, $contractId);
    }

    /**
     * Encodes raw data to strkey contract id (C...).
     * @param string $data data to encode
     * @return string strkey contract id (C...).
     * @throws InvalidArgumentException when $data is not 32 bytes long
     */
    public static function encodeContractId(string $data) : string {
        return static::encodeCheck(VersionByte::CONTRACT_ID, $data);
    }

    /**
     * Encodes hex representation of raw data contract id to strkey contract id (C...).
     * @param string $contractId hex representation of raw data contract id
     * @return string strkey representation of the contract id (C...).
     * @throws InvalidArgumentException when $contractId is not a hexadecimal string,
     * or when it does not decode to 32 bytes, that is when it is not 64 hex
     * characters long
     */
    public static function encodeContractIdHex(string $contractId) : string {
        return static::encodeCheck(
            VersionByte::CONTRACT_ID,
            self::decodeHexOrFail($contractId, '$contractId')
        );
    }

    /**
     * Decodes strkey contract id (C...) to raw data.
     * @param string $contractId strkey contract id (C...) to decode
     * @return string raw data
     * @throws InvalidArgumentException when $contractId is not a valid "C..." strkey:
     * wrong encoded length, wrong version byte, bad checksum, or a payload that is
     * not 32 bytes
     */
    public static function decodeContractId(string $contractId) : string {
        return static::decodeCheck(VersionByte::CONTRACT_ID, $contractId);
    }

    /**
     * Decodes strkey contract id (C...) to hex representation of it`s raw data
     * @param string $contractId strkey contract id (C...) to decode
     * @return string hex representation of the contract id's raw data
     * @throws InvalidArgumentException when $contractId is not a valid "C..." strkey:
     * wrong encoded length, wrong version byte, bad checksum, or a payload that is
     * not 32 bytes
     */
    public static function decodeContractIdHex(string $contractId) : string {
        return bin2hex(static::decodeCheck(VersionByte::CONTRACT_ID, $contractId));
    }

    /**
     * Returns true if the given strkey representation of the claimable balance id ("B...") is a valid claimable balance id.
     * @param string $claimableBalanceId the claimable balance id ("B...") to check.
     * @return bool true if valid: correct structure and checksum, a 33-byte payload,
     * and a discriminant byte of 0
     */
    public static function isValidClaimableBalanceId(string $claimableBalanceId) : bool {
        return static::isValid(VersionByte::CLAIMABLE_BALANCE_ID, $claimableBalanceId);
    }

    /**
     * Encodes raw data to strkey claimable balance id (B...).
     * @param string $data raw data to encode: the 33-byte strkey payload led by the
     * discriminant, the bare 32-byte balance hash, to which the zero discriminant is
     * prepended, or the 36-byte XDR form, whose 4-byte big-endian discriminant is
     * narrowed to the 1-byte strkey discriminant
     * @return string strkey claimable balance id (B...).
     * @throws InvalidArgumentException when $data has none of the accepted lengths,
     * or when the discriminant it carries is not 0
     */
    public static function encodeClaimableBalanceId(string $data) : string {
        if (strlen($data) === StellarConstants::ED25519_PUBLIC_KEY_LENGTH_BYTES) {
            // The bare balance hash, so prepend the discriminant it is missing.
            return static::encodeCheck(VersionByte::CLAIMABLE_BALANCE_ID, pack("C", 0) . $data);
        }
        if (strlen($data) === StellarConstants::CLAIMABLE_BALANCE_XDR_DECODED_LENGTH) {
            // The XDR form. The full 4-byte discriminant is judged before narrowing,
            // so a value that is non-zero only in its high bytes cannot slip through.
            $discriminant = unpack('N', substr($data, 0, 4))[1];
            self::checkClaimableBalanceDiscriminant($discriminant);
            return static::encodeCheck(
                VersionByte::CLAIMABLE_BALANCE_ID,
                pack("C", $discriminant) . substr($data, 4)
            );
        }
        if (strlen($data) === StellarConstants::CLAIMABLE_BALANCE_DECODED_LENGTH) {
            self::checkClaimableBalanceDiscriminant(ord($data[0]));
        }
        // Any other size is a payload-length rejection, which encodeCheck() reports.
        return static::encodeCheck(VersionByte::CLAIMABLE_BALANCE_ID, $data);
    }

    /**
     * Encodes hex representation of raw data claimable balance id to strkey claimable balance id (B...).
     * @param string $claimableBalanceId hex representation of the claimable balance id:
     * 66 characters (the 33-byte strkey payload), 64 characters (the bare balance
     * hash), or 72 characters (the XDR form, as Horizon reports balance ids)
     * @return string strkey representation of the claimable balance id (B...).
     * @throws InvalidArgumentException when $claimableBalanceId is not hexadecimal,
     * decodes to none of the accepted lengths, or carries a discriminant that is not 0
     */
    public static function encodeClaimableBalanceIdHex(string $claimableBalanceId) : string {
        return self::encodeClaimableBalanceId(
            self::decodeHexOrFail($claimableBalanceId, '$claimableBalanceId')
        );
    }

    /**
     * Decodes strkey claimable balance id (B...) to raw data.
     * @param string $claimableBalanceId claimable balance id (B...) to decode
     * @return string raw data
     * @throws InvalidArgumentException when $claimableBalanceId is not a valid "B..."
     * strkey: wrong encoded length, wrong version byte, bad checksum, a payload
     * that is not 33 bytes, or a discriminant byte that is not 0
     */
    public static function decodeClaimableBalanceId(string $claimableBalanceId) : string {
        return static::decodeCheck(VersionByte::CLAIMABLE_BALANCE_ID, $claimableBalanceId);
    }

    /**
     * Decodes strkey claimable balance id (B...) to hex representation of it`s raw data
     * @param string $claimableBalanceId strkey claimable balance id (B...) to decode
     * @return string hex representation of the claimable balance id's raw data
     * @throws InvalidArgumentException when $claimableBalanceId is not a valid "B..."
     * strkey: wrong encoded length, wrong version byte, bad checksum, a payload
     * that is not 33 bytes, or a discriminant byte that is not 0
     */
    public static function decodeClaimableBalanceIdHex(string $claimableBalanceId) : string {
        return bin2hex(static::decodeCheck(VersionByte::CLAIMABLE_BALANCE_ID, $claimableBalanceId));
    }

    /**
     * Returns true if the given strkey representation of the liquidity pool id ("L...") is a valid liquidity pool id.
     * @param string $liquidityPoolId the liquidity pool id ("L...") to check.
     * @return bool true if valid
     */
    public static function isValidLiquidityPoolId(string $liquidityPoolId) : bool {
        return static::isValid(VersionByte::LIQUIDITY_POOL_ID, $liquidityPoolId);
    }

    /**
     * Encodes raw data to strkey liquidity pool id (L...).
     * @param string $data raw data to encode
     * @return string strkey liquidity pool id (L...).
     * @throws InvalidArgumentException when $data is not 32 bytes long
     */
    public static function encodeLiquidityPoolId(string $data) : string {
        return static::encodeCheck(VersionByte::LIQUIDITY_POOL_ID, $data);
    }

    /**
     * Encodes hex representation of raw data liquidity pool id to strkey liquidity pool id (L...).
     * @param string $liquidityPoolId hex representation of raw data liquidity pool id
     * @return string strkey representation of the liquidity pool id (L...).
     * @throws InvalidArgumentException when $liquidityPoolId is not a hexadecimal string,
     * or when it does not decode to 32 bytes, that is when it is not 64 hex characters
     * long
     */
    public static function encodeLiquidityPoolIdHex(string $liquidityPoolId) : string {
        return static::encodeCheck(
            VersionByte::LIQUIDITY_POOL_ID,
            self::decodeHexOrFail($liquidityPoolId, '$liquidityPoolId')
        );
    }

    /**
     * Decodes strkey liquidity pool id (L...) to raw data.
     * @param string $liquidityPoolId liquidity pool id (L...) to decode
     * @return string raw data
     * @throws InvalidArgumentException when $liquidityPoolId is not a valid "L..." strkey:
     * wrong encoded length, wrong version byte, bad checksum, or a payload that is
     * not 32 bytes
     */
    public static function decodeLiquidityPoolId(string $liquidityPoolId) : string {
        return static::decodeCheck(VersionByte::LIQUIDITY_POOL_ID, $liquidityPoolId);
    }

    /**
     * Decodes strkey liquidity pool id (L...) to hex representation of it`s raw data
     * @param string $liquidityPoolId strkey liquidity pool id (L...) to decode
     * @return string hex representation of the liquidity pool id's raw data
     * @throws InvalidArgumentException when $liquidityPoolId is not a valid "L..." strkey:
     * wrong encoded length, wrong version byte, bad checksum, or a payload that is
     * not 32 bytes
     */
    public static function decodeLiquidityPoolIdHex(string $liquidityPoolId) : string {
        return bin2hex(static::decodeCheck(VersionByte::LIQUIDITY_POOL_ID, $liquidityPoolId));
    }

    /**
     * Derives the public key from a private key
     *
     * @param string $privateKey The Ed25519 private key (32 bytes)
     * @return string The corresponding Ed25519 public key (32 bytes)
     * @throws \Exception If the private key is invalid or key derivation fails
     */
    public static function publicKeyFromPrivateKey($privateKey) {
        try {
            $keypair = sodium_crypto_sign_seed_keypair($privateKey);
            $pk = substr($keypair, SODIUM_CRYPTO_SIGN_SECRETKEYBYTES, SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES);
            sodium_memzero($keypair);
            return $pk;
        } catch (SodiumException $e) {
            throw new CryptoException("Ed25519 public key derivation failed", 0, $e);
        }
    }

    /**
     * Derives the account ID from a secret seed
     *
     * @param string $seed The secret seed in strkey format (S...)
     * @return string The account ID in strkey format (G...)
     */
    public static function accountIdFromSeed(string $seed) : string {
        return static::accountIdFromPrivateKey(self::decodeSeed($seed));
    }

    /**
     * Derives the account ID from a private key
     *
     * @param string $privateKey The Ed25519 private key (32 bytes)
     * @return string The account ID in strkey format (G...)
     */
    public static function accountIdFromPrivateKey(string $privateKey) : string {
        $publicKey = static::publicKeyFromPrivateKey($privateKey);
        return static::encodeAccountId($publicKey);
    }

    /**
     * Reports whether the given string is a valid strkey of a fixed-length type.
     * Answers false for every rejection rather than throwing, because callers use
     * it as a predicate and the negative answer is the common one. A version byte
     * that has no length rule is a rejection like any other here.
     *
     * The signed payload, the one type whose payload length varies, is checked by
     * isValidSignedPayload() instead. Its length rule carries a null payload length,
     * which no decoded length equals, so this method would answer false for it.
     *
     * @param int $versionByte the VersionByte constant the string must carry
     * @param string $data strkey string to check
     * @return bool true if the string is a valid strkey of that type
     */
    private static function isValid(int $versionByte, string $data) : bool {
        try {
            $rule = self::strkeyLengthRule($versionByte);
            $encodedLength = strlen($data);
            if ($encodedLength < $rule['minEncodedLength'] || $encodedLength > $rule['maxEncodedLength']) {
                return false;
            }
            return strlen(self::decodeCheck($versionByte, $data)) === $rule['payloadLength'];
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Returns the length rule of a strkey type: the legal range of encoded string
     * lengths, the decoded payload length the type requires, and the letter its
     * encoded form starts with.
     *
     * This is the single source of those values. Encoding, decoding and validation
     * all read them from here, so the encode and decode sides cannot drift apart.
     * The prefix letter travels with the lengths so that a rejection can name the
     * strkey type the way a caller sees it, as "G" rather than as a version byte
     * of 48. Six types share a length rule but not a prefix, which is why each
     * version byte gets its own arm.
     *
     * The payload length is null for the signed payload, the one strkey type whose
     * payload size varies. Its bounds come from the SEP-23 framing checks, which
     * read the declared payload length out of the decoded data.
     *
     * @param int $versionByte one of the VersionByte constants
     * @return array{minEncodedLength: int, maxEncodedLength: int, payloadLength: int|null, prefix: string}
     * @throws InvalidArgumentException when the version byte is not a VersionByte constant
     */
    private static function strkeyLengthRule(int $versionByte) : array {
        return match ($versionByte) {
            VersionByte::ACCOUNT_ID => [
                'minEncodedLength' => CryptoConstants::STRKEY_ACCOUNT_ID_LENGTH,
                'maxEncodedLength' => CryptoConstants::STRKEY_ACCOUNT_ID_LENGTH,
                'payloadLength' => StellarConstants::ED25519_PUBLIC_KEY_LENGTH_BYTES,
                'prefix' => 'G',
            ],
            VersionByte::SEED => [
                'minEncodedLength' => CryptoConstants::STRKEY_ACCOUNT_ID_LENGTH,
                'maxEncodedLength' => CryptoConstants::STRKEY_ACCOUNT_ID_LENGTH,
                'payloadLength' => StellarConstants::ED25519_PUBLIC_KEY_LENGTH_BYTES,
                'prefix' => 'S',
            ],
            VersionByte::PRE_AUTH_TX => [
                'minEncodedLength' => CryptoConstants::STRKEY_ACCOUNT_ID_LENGTH,
                'maxEncodedLength' => CryptoConstants::STRKEY_ACCOUNT_ID_LENGTH,
                'payloadLength' => StellarConstants::ED25519_PUBLIC_KEY_LENGTH_BYTES,
                'prefix' => 'T',
            ],
            VersionByte::SHA256_HASH => [
                'minEncodedLength' => CryptoConstants::STRKEY_ACCOUNT_ID_LENGTH,
                'maxEncodedLength' => CryptoConstants::STRKEY_ACCOUNT_ID_LENGTH,
                'payloadLength' => StellarConstants::ED25519_PUBLIC_KEY_LENGTH_BYTES,
                'prefix' => 'X',
            ],
            VersionByte::CONTRACT_ID => [
                'minEncodedLength' => CryptoConstants::STRKEY_ACCOUNT_ID_LENGTH,
                'maxEncodedLength' => CryptoConstants::STRKEY_ACCOUNT_ID_LENGTH,
                'payloadLength' => StellarConstants::ED25519_PUBLIC_KEY_LENGTH_BYTES,
                'prefix' => 'C',
            ],
            VersionByte::LIQUIDITY_POOL_ID => [
                'minEncodedLength' => CryptoConstants::STRKEY_ACCOUNT_ID_LENGTH,
                'maxEncodedLength' => CryptoConstants::STRKEY_ACCOUNT_ID_LENGTH,
                'payloadLength' => StellarConstants::ED25519_PUBLIC_KEY_LENGTH_BYTES,
                'prefix' => 'L',
            ],
            VersionByte::MUXED_ACCOUNT_ID => [
                'minEncodedLength' => CryptoConstants::STRKEY_MUXED_ACCOUNT_ID_LENGTH,
                'maxEncodedLength' => CryptoConstants::STRKEY_MUXED_ACCOUNT_ID_LENGTH,
                'payloadLength' => StellarConstants::MUXED_ACCOUNT_DECODED_LENGTH,
                'prefix' => 'M',
            ],
            VersionByte::CLAIMABLE_BALANCE_ID => [
                'minEncodedLength' => CryptoConstants::STRKEY_CLAIMABLE_BALANCE_LENGTH,
                'maxEncodedLength' => CryptoConstants::STRKEY_CLAIMABLE_BALANCE_LENGTH,
                'payloadLength' => StellarConstants::CLAIMABLE_BALANCE_DECODED_LENGTH,
                'prefix' => 'B',
            ],
            VersionByte::SIGNED_PAYLOAD => [
                'minEncodedLength' => CryptoConstants::STRKEY_SIGNED_PAYLOAD_MIN_LENGTH,
                'maxEncodedLength' => CryptoConstants::STRKEY_SIGNED_PAYLOAD_MAX_LENGTH,
                'payloadLength' => null,
                'prefix' => 'P',
            ],
            default => throw new InvalidArgumentException(sprintf(
                'Programming error: %d is not a StrKey version byte, so no strkey length rule exists for it',
                $versionByte
            )),
        };
    }

    private static function encodeCheck(int $versionByte, string $data) : string {
        $rule = self::strkeyLengthRule($versionByte);
        $expectedPayloadLength = $rule['payloadLength'];
        if ($expectedPayloadLength !== null && strlen($data) !== $expectedPayloadLength) {
            throw new InvalidArgumentException(sprintf(
                '%s-strkey requires a payload of %d bytes, %d bytes given',
                $rule['prefix'],
                $expectedPayloadLength,
                strlen($data)
            ));
        }
        $version = pack('C', $versionByte);
        $checksum = static::calculateChecksum($version . $data);
        $base32String = Base32::encode($version . $data . $checksum);
        // Remove anything that is not base32 alphabet
        return preg_replace('/[^A-Z2-7]/', '', $base32String);
    }

    private static function decodeCheck(int $versionByte, string $encodedData) : string {
        $rule = self::strkeyLengthRule($versionByte);

        // Reject on length before decoding, so unusable input never reaches the decoder.
        $encodedLength = strlen($encodedData);
        if ($encodedLength < $rule['minEncodedLength'] || $encodedLength > $rule['maxEncodedLength']) {
            throw new InvalidArgumentException(sprintf(
                '%s-strkey must be %s characters long, %d characters given',
                $rule['prefix'],
                $rule['minEncodedLength'] === $rule['maxEncodedLength']
                    ? (string) $rule['minEncodedLength']
                    : sprintf('%d to %d', $rule['minEncodedLength'], $rule['maxEncodedLength']),
                $encodedLength
            ));
        }

        $decoded = Base32::decode($encodedData);

        // Identity, not equality: the input has to be the exact canonical encoding of
        // what it decodes to. A loose comparison would read two numeric-looking strings
        // as numbers rather than as characters, so anything the base32 filter drops -
        // a leading run of zeros, for one - could be read as equal to the encoding it
        // is not.
        if ($encodedData !== preg_replace('/[^A-Z2-7]/', '', Base32::encode($decoded))) {
            throw new InvalidArgumentException("invalid encoded string");
        }

        // Total-function guard for the fixed-offset version and checksum reads below.
        // Every length the current rule table admits decodes to well past the framing
        // bytes, so no input reaches this today; it holds the reads safe should a
        // shorter strkey type ever join the table.
        if (strlen($decoded) < self::DECODED_FRAMING_LENGTH) {
            // @codeCoverageIgnoreStart
            throw new InvalidArgumentException(sprintf(
                'Decoded strkey data of %d bytes is too short to hold the %d framing bytes'
                    . ' (1-byte version prefix and 2-byte checksum)',
                strlen($decoded),
                self::DECODED_FRAMING_LENGTH
            ));
            // @codeCoverageIgnoreEnd
        }

        // Unpack version byte
        $unpacked = unpack('Cversion', substr($decoded, 0, 1));
        $version = $unpacked['version'];

        if ($version !== $versionByte) {
            throw new InvalidArgumentException("version byte in encoded data does not match passed version byte by parameter");
        }

        // Unpack payload and checksum
        $payload = substr($decoded, 1, strlen($decoded) - self::DECODED_FRAMING_LENGTH);
        $checksum = substr($decoded, -2);

        // Verify checksum
        if (!static::verifyChecksum($checksum, substr($decoded, 0, -2))) {
            throw new InvalidArgumentException("invalid checksum in encoded data");
        }

        // The payload contract of the strkey type, held at the point the payload is
        // handed back, so that no caller has to trust the encoded length to stand in
        // for it.
        if ($rule['payloadLength'] !== null && strlen($payload) !== $rule['payloadLength']) {
            // @codeCoverageIgnoreStart
            throw new InvalidArgumentException(sprintf(
                '%s-strkey must carry a payload of %d bytes, %d bytes decoded',
                $rule['prefix'],
                $rule['payloadLength'],
                strlen($payload)
            ));
            // @codeCoverageIgnoreEnd
        }

        if ($versionByte === VersionByte::CLAIMABLE_BALANCE_ID) {
            self::checkClaimableBalanceDiscriminant(ord($payload[0]));
        }

        // Return payload.
        return $payload;
    }

    /**
     * @param string $data
     * @return string CRC-16 checksum of $data as a 2-byte little-endian
     */
    private static function calculateChecksum(string $data) : string
    {
        $crc = CryptoConstants::CRC16_INITIAL;
        $polynomial = CryptoConstants::CRC16_POLYNOMIAL;

        foreach (str_split($data) as $byte) {
            $byte = ord($byte);

            for ($i = 0; $i < 8; $i++) {
                $bit = (($byte >> (7 - $i) & 1) == 1);
                $c15 = (($crc >> 15 & 1) == 1);
                $crc <<= 1;
                if ($c15 ^ $bit) $crc ^= $polynomial;
            }
        }

        return pack('v', $crc & CryptoConstants::CRC16_MASK);
    }

    private static function verifyChecksum(string $expectedChecksum, string $data) : bool {
        return hash_equals($expectedChecksum, static::calculateChecksum($data));
    }

    /**
     * Decodes a hexadecimal string to the bytes it denotes, rejecting anything
     * hex2bin() cannot decode before hex2bin() sees it.
     *
     * hex2bin() answers false and raises a warning for odd-length and non-hexadecimal
     * input. Every strkey encoder that takes hex goes through here so that such input
     * is reported the way every other rejection in this class is: an
     * InvalidArgumentException naming the argument and the rule it broke, with no PHP
     * diagnostic emitted first.
     *
     * Only the hexadecimal form is judged here. How many bytes the decoded data must
     * carry is a property of the strkey type, checked by encodeCheck() against
     * strkeyLengthRule().
     *
     * @param string $hex hexadecimal string, upper or lower case, of even length
     * @param string $argumentName the caller's parameter name, as it reads in the
     * signature, so that a rejection points at the argument the caller passed
     * @return string the decoded bytes
     * @throws InvalidArgumentException when $hex is empty, of odd length, or holds a
     * character outside [0-9a-fA-F]
     */
    private static function decodeHexOrFail(string $hex, string $argumentName) : string {
        if ($hex === '') {
            throw new InvalidArgumentException(sprintf(
                '%s must be a hexadecimal string, an empty string given',
                $argumentName
            ));
        }
        if (strlen($hex) % 2 !== 0) {
            throw new InvalidArgumentException(sprintf(
                '%s must be a hexadecimal string of even length, %d characters given',
                $argumentName,
                strlen($hex)
            ));
        }
        // strspn stops at the first character outside the hex alphabet, which both
        // detects the offence and locates it for the message.
        $offset = strspn($hex, '0123456789abcdefABCDEF');
        if ($offset !== strlen($hex)) {
            // The offending byte is shown literally only when it is printable ASCII.
            // Every other byte is written as an \xNN escape, because a single byte
            // taken out of a multibyte character is not valid UTF-8 on its own and
            // would leave the message unusable for callers that serialize it, for
            // example to JSON.
            $char = $hex[$offset];
            $shown = ($char >= "\x20" && $char <= "\x7E")
                ? $char
                : '\x' . strtoupper(bin2hex($char));
            throw new InvalidArgumentException(sprintf(
                '%s must contain only hexadecimal characters [0-9a-fA-F], "%s" found at index %d',
                $argumentName,
                $shown,
                $offset
            ));
        }
        $bytes = hex2bin($hex);
        if ($bytes === false) {
            // hex2bin() answers false only for the two shapes rejected above. The guard
            // keeps the declared string return honest rather than passing a bool on.
            // @codeCoverageIgnoreStart
            throw new InvalidArgumentException(sprintf(
                '%s could not be decoded from hexadecimal to bytes',
                $argumentName
            ));
            // @codeCoverageIgnoreEnd
        }
        return $bytes;
    }

    /**
     * Requires the discriminant of a claimable balance id to name a case the
     * ClaimableBalanceID union actually has.
     *
     * A B-strkey carries the discriminant in its first payload byte, the XDR form as
     * 4 big-endian bytes; the caller reads it from whichever spelling it holds. The
     * union defines a single case, CLAIMABLE_BALANCE_ID_TYPE_V0 (0), so a value
     * announcing anything else describes no balance id that exists on the wire, and
     * the XDR decoder refuses it. The checksum covers the discriminant, so it cannot
     * tell a corrupted byte from a deliberate one; both are rejected here.
     *
     * @param int $discriminant the discriminant value read from the id
     * @throws InvalidArgumentException when the discriminant is not 0
     */
    private static function checkClaimableBalanceDiscriminant(int $discriminant) : void {
        if ($discriminant !== 0) {
            throw new InvalidArgumentException(sprintf(
                'Claimable balance discriminant 0x%02X is not defined:'
                    . ' CLAIMABLE_BALANCE_ID_TYPE_V0 (0) is the only case ClaimableBalanceID has',
                $discriminant
            ));
        }
    }

    /**
     * Requires a signed payload to be representable as a SEP-23 P-strkey:
     * 1 to 64 raw payload bytes. A zero-length payload is valid XDR, but
     * the resulting P-strkey is rejected across the ecosystem (SEP-23
     * encodes a 4-byte length prefix and a payload padded to a 4-byte
     * multiple; the accepted padded region is 4 to 64 bytes, which is a
     * raw payload of 1 to 64 bytes). The 64-byte maximum is the XDR bound
     * of the payload field (opaque<64>).
     * @param string $payload raw, unpadded payload bytes
     * @throws InvalidArgumentException when the payload is empty or longer than 64 bytes
     */
    private static function checkSignedPayloadLength(string $payload) : void {
        if ($payload === '') {
            throw new InvalidArgumentException(
                'Zero-length signed payload has no SEP-23 strkey representation'
            );
        }
        if (strlen($payload) > StellarConstants::SIGNED_PAYLOAD_MAX_LENGTH_BYTES) {
            throw new InvalidArgumentException(sprintf(
                'Signed payload length %d exceeds the maximum of %d bytes (XDR opaque<64>)',
                strlen($payload),
                StellarConstants::SIGNED_PAYLOAD_MAX_LENGTH_BYTES
            ));
        }
    }

    /**
     * Requires the decoded data of a signed payload P-strkey to carry exactly
     * the framing SEP-23 prescribes: the signer key, the 4-byte payload length
     * prefix, and the payload padded to a 4-byte multiple. Two rules follow
     * from that framing and both are enforced here.
     *
     * The data ends where the padded payload ends. SEP-23 permits nothing after
     * the padding, so surplus bytes mean the length prefix understates the payload.
     *
     * Every padding byte is zero. XDR fills an opaque field up to the next 4-byte
     * boundary with zero bytes (RFC 4506 section 4.10), and the checksum covers
     * that fill. Padding that carried anything else would let two strkeys differing
     * only in their tail decode to the same signer and payload, giving one signer
     * more than one spelling, which would make string comparison of P-strkeys
     * unsound.
     *
     * @param string $decodedData decoded strkey data (version byte and checksum removed)
     * @param string $payload raw payload read from the length prefix
     * @throws InvalidArgumentException when the decoded data carries surplus bytes
     * or a non-zero padding byte
     */
    private static function checkSignedPayloadFraming(string $decodedData, string $payload) : void {
        $paddingOffset = StellarConstants::ED25519_PUBLIC_KEY_LENGTH_BYTES
            + CryptoConstants::SIGNED_PAYLOAD_LENGTH_PREFIX_BYTES
            + strlen($payload);
        $expectedLength = $paddingOffset + ((4 - strlen($payload) % 4) % 4);
        if (strlen($decodedData) !== $expectedLength) {
            throw new InvalidArgumentException(sprintf(
                'Signed payload declares %d payload bytes, but the decoded data is %d bytes where %d are expected',
                strlen($payload),
                strlen($decodedData),
                $expectedLength
            ));
        }
        for ($offset = $paddingOffset; $offset < $expectedLength; $offset++) {
            if ($decodedData[$offset] !== "\x00") {
                throw new InvalidArgumentException(sprintf(
                    'Signed payload padding must be zero, byte 0x%02X found at offset %d',
                    ord($decodedData[$offset]),
                    $offset
                ));
            }
        }
    }
}
