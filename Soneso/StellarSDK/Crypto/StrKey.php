<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Crypto;

use Base32\Base32;
use InvalidArgumentException;
use ParagonIE\Sodium\Core\Ed25519;
use Soneso\StellarSDK\SignedPayloadSigner;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrSignedPayload;
use function preg_replace;

class StrKey
{

    public static function encodeAccountId(string $data) : string {
        return static::encodeCheck(VersionByte::ACCOUNT_ID, $data);
    }

    public static function decodeAccountId(string $accountId) : string {
        return static::decodeCheck(VersionByte::ACCOUNT_ID, $accountId);
    }

    public static function encodeMuxedAccountId(string $data) : string {
        return static::encodeCheck(VersionByte::MUXED_ACCOUNT_ID, $data);
    }

    public static function decodeMuxedAccountId(string $muxedAccountId) : string {
        return static::decodeCheck(VersionByte::MUXED_ACCOUNT_ID, $muxedAccountId);
    }

    public static function encodeSeed(string $data) : string {
        return static::encodeCheck(VersionByte::SEED, $data);
    }

    public static function decodeSeed(string $seed) : string {
        return static::decodeCheck(VersionByte::SEED, $seed);
    }

    public static function encodePreAuth(string $data) : string {
        return static::encodeCheck(VersionByte::PRE_AUTH_TX, $data);
    }

    public static function decodePreAuth(string $preAuth) : string {
        return static::decodeCheck(VersionByte::PRE_AUTH_TX, $preAuth);
    }

    public static function encodeSha256Hash(string $data) : string {
        return static::encodeCheck(VersionByte::SHA256_HASH, $data);
    }

    public static function decodeSha256Hash(string $hash) : string {
        return static::decodeCheck(VersionByte::SHA256_HASH, $hash);
    }

    public static function encodeSignedPayload(SignedPayloadSigner $signedPayloadSigner) : string {
        $pk = (KeyPair::fromAccountId($signedPayloadSigner->getSignerAccountId()->getAccountId()))->getPublicKey();
        $signedPayload = new XdrSignedPayload($pk, $signedPayloadSigner->getPayload());
        $data = $signedPayload->encode();
        return static::encodeCheck(VersionByte::SIGNED_PAYLOAD, $data);
    }

    public static function encodeXdrSignedPayload(XdrSignedPayload $signedPayload) : string {
        $data = $signedPayload->encode();
        return static::encodeCheck(VersionByte::SIGNED_PAYLOAD, $data);
    }

    public static function decodeSignedPayload(string $data) : SignedPayloadSigner {
        $signedPayloadRaw = self::decodeCheck(VersionByte::SIGNED_PAYLOAD, $data);
        $xdr = new XdrBuffer($signedPayloadRaw);
        $xdrPayloadSigner = XdrSignedPayload::decode($xdr);
        return SignedPayloadSigner::fromPublicKey($xdrPayloadSigner->getEd25519(), $xdrPayloadSigner->getPayload());
    }

    public static function decodeXdrSignedPayload(string $data) : XdrSignedPayload {
        $signedPayloadRaw = self::decodeCheck(VersionByte::SIGNED_PAYLOAD, $data);
        $xdr = new XdrBuffer($signedPayloadRaw);
        return XdrSignedPayload::decode($xdr);
    }

    public static function encodeContractId(string $data) : string {
        return static::encodeCheck(VersionByte::CONTRACT_ID, $data);
    }

    public static function encodeContractIdHex(string $contractId) : string {
        return static::encodeCheck(VersionByte::CONTRACT_ID, hex2bin($contractId));
    }

    public static function decodeContractId(string $contractId) : string {
        return static::decodeCheck(VersionByte::CONTRACT_ID, $contractId);
    }

    public static function decodeContractIdHex(string $contractId) : string {
        return bin2hex(static::decodeCheck(VersionByte::CONTRACT_ID, $contractId));
    }

    public static function publicKeyFromPrivateKey($privateKey) {
        return Ed25519::publickey_from_secretkey($privateKey);;
    }

    public static function accountIdFromSeed(string $seed) : string {
        return static::accountIdFromPrivateKey(self::decodeSeed($seed));
    }

    public static function accountIdFromPrivateKey(string $privateKey) : string {
        $publicKey = static::publicKeyFromPrivateKey($privateKey);
        return static::encodeAccountId($publicKey);
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
        $crc = 0x0000;
        $polynomial = 0x1021;

        foreach (str_split($data) as $byte) {
            $byte = ord($byte);

            for ($i = 0; $i < 8; $i++) {
                $bit = (($byte >> (7 - $i) & 1) == 1);
                $c15 = (($crc >> 15 & 1) == 1);
                $crc <<= 1;
                if ($c15 ^ $bit) $crc ^= $polynomial;
            }
        }

        return pack('v', $crc & 0xffff);
    }

    private static function verifyChecksum(string $expectedChecksum, string $data) : bool {
        return static::calculateChecksum($data) == $expectedChecksum;
    }
}