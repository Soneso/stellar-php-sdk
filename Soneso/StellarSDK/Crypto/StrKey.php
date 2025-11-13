<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Crypto;

use Base32\Base32;
use Exception;
use InvalidArgumentException;
use ParagonIE\Sodium\Core\Ed25519;
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
     */
    public static function encodeAccountId(string $data) : string {
        return static::encodeCheck(VersionByte::ACCOUNT_ID, $data);
    }

    /**
     * Decodes strkey account id (ed25519 public key) to raw data.
     * @param string $accountId "G..." key representation to decode
     * @return string raw key
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
     */
    public static function encodeMuxedAccountId(string $data) : string {
        return static::encodeCheck(VersionByte::MUXED_ACCOUNT_ID, $data);
    }

    /**
     * Decodes strkey muxed account id (med25519 public key) to raw data.
     * @param string $muxedAccountId address data to decode ("M...")
     * @return string raw key
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
     */
    public static function encodeSeed(string $data) : string {
        return static::encodeCheck(VersionByte::SEED, $data);
    }

    /**
     * Decodes strkey ed25519 seed ("S...") to raw data.
     * @param string $seed seed ("S...") to decode
     * @return string raw seed data
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
     */
    public static function encodePreAuthTx(string $data) : string {
        return static::encodeCheck(VersionByte::PRE_AUTH_TX, $data);
    }

    /**
     * Decodes strkey PreAuthTx ("T...") to raw data.
     * @param string $preAuth PreAuthTx ("T...") to decode
     * @return string raw PreAuthTx data
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
     */
    public static function encodeSha256Hash(string $data) : string {
        return static::encodeCheck(VersionByte::SHA256_HASH, $data);
    }

    /**
     * Decodes strkey sha256 hash ("X...") to raw data.
     * @param string $hash strkey sha256 hash ("X...") to decode
     * @return string raw sha256 hash data.
     */
    public static function decodeSha256Hash(string $hash) : string {
        return static::decodeCheck(VersionByte::SHA256_HASH, $hash);
    }

    /**
     * Encodes a SignedPayloadSigner to strkey signed payload (P...).
     * @param SignedPayloadSigner $signedPayloadSigner SignedPayloadSigner to encode
     * @return string "P..." representation of the signed payload
     */
    public static function encodeSignedPayload(SignedPayloadSigner $signedPayloadSigner) : string {
        $pk = (KeyPair::fromAccountId($signedPayloadSigner->getSignerAccountId()->getAccountId()))->getPublicKey();
        $signedPayload = new XdrSignedPayload($pk, $signedPayloadSigner->getPayload());
        $data = $signedPayload->encode();
        return static::encodeCheck(VersionByte::SIGNED_PAYLOAD, $data);
    }

    /**
     * Encodes a XdrSignedPayload to strkey signed payload (P...).
     * @param XdrSignedPayload $signedPayload XdrSignedPayload to encode
     * @return string "P..." representation of the signed payload
     */
    public static function encodeXdrSignedPayload(XdrSignedPayload $signedPayload) : string {
        $data = $signedPayload->encode();
        return static::encodeCheck(VersionByte::SIGNED_PAYLOAD, $data);
    }

    /**
     * Decodes strkey signed payload ("P...") to a SignedPayloadSigner object.
     * @param string $signedPayload signed payload ("P...") to decode
     * @return SignedPayloadSigner object decoded from the given strkey signed payload
     */
    public static function decodeSignedPayload(string $signedPayload) : SignedPayloadSigner {
        $signedPayloadRaw = self::decodeCheck(VersionByte::SIGNED_PAYLOAD, $signedPayload);
        $xdr = new XdrBuffer($signedPayloadRaw);
        $xdrPayloadSigner = XdrSignedPayload::decode($xdr);
        return SignedPayloadSigner::fromPublicKey($xdrPayloadSigner->getEd25519(), $xdrPayloadSigner->getPayload());
    }

    /**
     * Decodes strkey signed payload ("P...") to a XdrSignedPayload object.
     * @param string $signedPayload signed payload ("P...") to decode
     * @return XdrSignedPayload object decoded from the given strkey signed payload
     */
    public static function decodeXdrSignedPayload(string $signedPayload) : XdrSignedPayload {
        $signedPayloadRaw = self::decodeCheck(VersionByte::SIGNED_PAYLOAD, $signedPayload);
        $xdr = new XdrBuffer($signedPayloadRaw);
        return XdrSignedPayload::decode($xdr);
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
     */
    public static function encodeContractId(string $data) : string {
        return static::encodeCheck(VersionByte::CONTRACT_ID, $data);
    }

    /**
     * Encodes hex representation of raw data contract id to strkey contract id (C...).
     * @param string $contractId hex representation of raw data contract id
     * @return string strkey representation of the contract id (C...).
     */
    public static function encodeContractIdHex(string $contractId) : string {
        return static::encodeCheck(VersionByte::CONTRACT_ID, hex2bin($contractId));
    }

    /**
     * Decodes strkey contract id (C...) to raw data.
     * @param string $contractId strkey contract id (C...) to decode
     * @return string raw data
     */
    public static function decodeContractId(string $contractId) : string {
        return static::decodeCheck(VersionByte::CONTRACT_ID, $contractId);
    }

    /**
     * Decodes strkey contract id (C...) to hex representation of it`s raw data
     * @param string $contractId strkey contract id (C...) to decode
     * @return string hex representation of the contract id's raw data
     */
    public static function decodeContractIdHex(string $contractId) : string {
        return bin2hex(static::decodeCheck(VersionByte::CONTRACT_ID, $contractId));
    }

    /**
     * Returns true if the given strkey representation of the claimable balance id ("B...") is a valid claimable balance id.
     * @param string $claimableBalanceId the claimable balance id ("B...") to check.
     * @return bool true if valid
     */
    public static function isValidClaimableBalanceId(string $claimableBalanceId) : bool {
        return static::isValid(VersionByte::CLAIMABLE_BALANCE_ID, $claimableBalanceId);
    }

    /**
     * Encodes raw data to strkey claimable balance id (B...).
     * @param string $data raw data to encode
     * @return string strkey claimable balance id (B...).
     */
    public static function encodeClaimableBalanceId(string $data) : string {
        if (strlen($data) === StellarConstants::ED25519_PUBLIC_KEY_LENGTH_BYTES) {
            // we need to add the discriminant (0)
            $prefixed = pack("C", 0) . $data;
            return static::encodeCheck(VersionByte::CLAIMABLE_BALANCE_ID, $prefixed);
        }
        return static::encodeCheck(VersionByte::CLAIMABLE_BALANCE_ID, $data);
    }

    /**
     * Encodes hex representation of raw data claimable balance id to strkey claimable balance id (B...).
     * @param string $claimableBalanceId hex representation of raw data claimable balance id
     * @return string strkey representation of the claimable balance id (B...).
     */
    public static function encodeClaimableBalanceIdHex(string $claimableBalanceId) : string {
        return self::encodeClaimableBalanceId(hex2bin($claimableBalanceId));
    }

    /**
     * Decodes strkey claimable balance id (B...) to raw data.
     * @param string $claimableBalanceId claimable balance id (B...) to decode
     * @return string raw data
     */
    public static function decodeClaimableBalanceId(string $claimableBalanceId) : string {
        return static::decodeCheck(VersionByte::CLAIMABLE_BALANCE_ID, $claimableBalanceId);
    }

    /**
     * Decodes strkey claimable balance id (B...) to hex representation of it`s raw data
     * @param string $claimableBalanceId strkey claimable balance id (B...) to decode
     * @return string hex representation of the claimable balance id's raw data
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
     */
    public static function encodeLiquidityPoolId(string $data) : string {
        return static::encodeCheck(VersionByte::LIQUIDITY_POOL_ID, $data);
    }

    /**
     * Encodes hex representation of raw data liquidity pool id to strkey liquidity pool id (L...).
     * @param string $liquidityPoolId hex representation of raw data liquidity pool id
     * @return string strkey representation of the liquidity pool id (L...).
     */
    public static function encodeLiquidityPoolIdHex(string $liquidityPoolId) : string {
        return static::encodeCheck(VersionByte::LIQUIDITY_POOL_ID, hex2bin($liquidityPoolId));
    }

    /**
     * Decodes strkey liquidity pool id (L...) to raw data.
     * @param string $liquidityPoolId liquidity pool id (L...) to decode
     * @return string raw data
     */
    public static function decodeLiquidityPoolId(string $liquidityPoolId) : string {
        return static::decodeCheck(VersionByte::LIQUIDITY_POOL_ID, $liquidityPoolId);
    }

    /**
     * Decodes strkey liquidity pool id (L...) to hex representation of it`s raw data
     * @param string $liquidityPoolId strkey liquidity pool id (L...) to decode
     * @return string hex representation of the liquidity pool id's raw data
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
        return Ed25519::publickey_from_secretkey($privateKey);
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

    private static function isValid(int $versionByte, string $data) : bool {
        switch ($versionByte) {
            case VersionByte::ACCOUNT_ID:
            case VersionByte::SEED:
            case VersionByte::PRE_AUTH_TX:
            case VersionByte::SHA256_HASH:
            case VersionByte::CONTRACT_ID:
            case VersionByte::LIQUIDITY_POOL_ID:
                if (strlen($data) !== CryptoConstants::STRKEY_ACCOUNT_ID_LENGTH) {
                    return false;
                }
                break;
            case VersionByte::MUXED_ACCOUNT_ID:
                if (strlen($data) !== CryptoConstants::STRKEY_MUXED_ACCOUNT_ID_LENGTH) {
                    return false;
                }
                break;
            case VersionByte::SIGNED_PAYLOAD:
                if (strlen($data) < CryptoConstants::STRKEY_SIGNED_PAYLOAD_MIN_LENGTH || strlen($data) > CryptoConstants::STRKEY_SIGNED_PAYLOAD_MAX_LENGTH) {
                    return false;
                }
                break;
            case VersionByte::CLAIMABLE_BALANCE_ID:
                if (strlen($data) !== CryptoConstants::STRKEY_CLAIMABLE_BALANCE_LENGTH) {
                    return false;
                }
                break;
            default:
                return false;
        }
        try {
            $decoded = self::decodeCheck($versionByte, $data);
            switch ($versionByte) {
                case VersionByte::ACCOUNT_ID:
                case VersionByte::SEED:
                case VersionByte::PRE_AUTH_TX:
                case VersionByte::SHA256_HASH:
                case VersionByte::CONTRACT_ID:
                case VersionByte::LIQUIDITY_POOL_ID:
                    return strlen($decoded) === StellarConstants::ED25519_PUBLIC_KEY_LENGTH_BYTES;
                case VersionByte::MUXED_ACCOUNT_ID:
                    return strlen($decoded) === StellarConstants::MUXED_ACCOUNT_DECODED_LENGTH;
                case VersionByte::SIGNED_PAYLOAD:
                    // 32 for the signer, +4 for the payload size, then either +4 for the
                    // min or +64 for the max payload
                    return strlen($decoded) >= StellarConstants::ED25519_PUBLIC_KEY_LENGTH_BYTES + CryptoConstants::SIGNED_PAYLOAD_LENGTH_PREFIX_BYTES + StellarConstants::SIGNED_PAYLOAD_MIN_LENGTH_BYTES && strlen($decoded) <= StellarConstants::ED25519_PUBLIC_KEY_LENGTH_BYTES + CryptoConstants::SIGNED_PAYLOAD_LENGTH_PREFIX_BYTES + StellarConstants::SIGNED_PAYLOAD_MAX_LENGTH_BYTES;
                case VersionByte::CLAIMABLE_BALANCE_ID:
                    return strlen($decoded) === StellarConstants::CLAIMABLE_BALANCE_DECODED_LENGTH;
                default:
                    return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }
    private static function encodeCheck(int $versionByte, string $data) : string {
        $version = pack('C', $versionByte);
        $checksum = static::calculateChecksum($version . $data);
        $base32String = Base32::encode($version . $data . $checksum);
        // Remove anything that is not base32 alphabet
        return preg_replace('/[^A-Z2-7]/', '', $base32String);
    }

    private static function decodeCheck(int $versionByte, string $encodedData) : string {
        $decoded = Base32::decode($encodedData);

        if ($encodedData != preg_replace('/[^A-Z2-7]/', '', Base32::encode($decoded))) {
            throw new InvalidArgumentException("invalid encoded string");
        }

        // Unpack version byte
        $unpacked = unpack('Cversion', substr($decoded, 0, 1));
        $version = $unpacked['version'];

        if ($version != $versionByte) {
            throw new InvalidArgumentException("version byte in encoded data does not match passed version byte by parameter");
        }

        // Unpack payload and checksum
        $payload = substr($decoded, 1, strlen($decoded) - 3);
        $checksum = substr($decoded, -2);

        // Verify checksum
        if (!static::verifyChecksum($checksum, substr($decoded, 0, -2))) {
            throw new InvalidArgumentException("invalid checksum in encoded data");
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
        return static::calculateChecksum($data) == $expectedChecksum;
    }
}