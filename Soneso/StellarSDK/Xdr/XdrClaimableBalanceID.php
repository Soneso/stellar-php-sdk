<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use InvalidArgumentException;
use Soneso\StellarSDK\Crypto\StrKey;

/**
 * ClaimableBalanceID union override that stores $hash as hexadecimal rather than
 * as the raw bytes the generated base expects, and accepts more than one
 * hexadecimal spelling of it; see getCanonicalHashHex().
 */
class XdrClaimableBalanceID extends XdrClaimableBalanceIDBase
{
    /**
     * Length of a "B..." claimable balance strkey.
     */
    private const STRKEY_LENGTH = 58;

    /**
     * Length of the bare 32-byte balance hash in hexadecimal characters.
     */
    private const HASH_HEX_LENGTH = 64;

    /**
     * Length in hexadecimal characters of a balance id that carries the 1-byte
     * strkey discriminant ahead of the hash, the shape
     * StrKey::decodeClaimableBalanceIdHex() returns.
     */
    private const STRKEY_PREFIXED_HEX_LENGTH = 66;

    /**
     * Length in hexadecimal characters of a balance id that carries the 4-byte XDR
     * union discriminant ahead of the hash, the shape Horizon reports and
     * getPaddedBalanceIdHex() produces.
     */
    private const XDR_PREFIXED_HEX_LENGTH = 72;

    public function __construct(XdrClaimableBalanceIDType $type, string $hash) {
        parent::__construct($type);
        $this->hash = $hash;
    }

    public function encode(): string {
        $bytes = $this->type->encode();
        switch ($this->type->getValue()) {
            case XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0:
                $bytes .= XdrEncoder::opaqueFixed(pack("H*", $this->getCanonicalHashHex()), 32);
                break;
            default:
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr): static {
        $type = XdrClaimableBalanceIDType::decode($xdr);
        $hash = '';
        switch ($type->getValue()) {
            case XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0:
                $hash = bin2hex($xdr->readOpaqueFixed(32));
                break;
            default:
                break;
        }
        return new static($type, $hash);
    }

    /**
     * Returns the balance id as hex string with leading zeros, so it can be used in horizon requests.
     * e.g. '000000003be9c4382b2e4acc74600f6eb1b68e51de5e5cc22ee2adcf68bd7fdfa1f40cf9'
     * instead of '3be9c4382b2e4acc74600f6eb1b68e51de5e5cc22ee2adcf68bd7fdfa1f40cf9'
     *
     * The zeros stand for the 4-byte XDR union discriminant, so they may only ever pad
     * a full 32-byte hash.
     *
     * @return string balance id as hex string with leading zeros, so it can be used in horizon requests.
     * @throws InvalidArgumentException when the hash field holds none of the spellings
     * getCanonicalHashHex() accepts
     */
    public function getPaddedBalanceIdHex() {
        return str_pad(
            $this->getCanonicalHashHex(),
            self::XDR_PREFIXED_HEX_LENGTH,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Returns the 32-byte balance hash as 64 lower case hexadecimal characters,
     * whichever of the accepted spellings the hash field holds.
     *
     * A balance id reaches this class in four shapes, all denoting the same hash: the
     * strkey form "B..."; the bare hash as 64 hexadecimal characters; the hash behind
     * the 1-byte strkey discriminant, as 66 hexadecimal characters, which is what
     * StrKey::decodeClaimableBalanceIdHex() hands back; and the hash behind the 4-byte
     * XDR union discriminant, as 72 hexadecimal characters, which is what Horizon
     * reports and what getPaddedBalanceIdHex() produces. A discriminant carried in the
     * hexadecimal must name CLAIMABLE_BALANCE_ID_TYPE_V0, the case this id holds;
     * anything else means the prefix and the type field describe different balance ids.
     *
     * Every reader of the hash field resolves it here: the XDR this object encodes, the
     * strkey it reports, its SEP-51 JSON and SEP-11 TxRep forms, and the padded hex
     * Horizon takes.
     *
     * @return string the balance hash as 64 lower case hexadecimal characters
     * @throws InvalidArgumentException when the hash field is unset, holds a string
     * that is neither a "B..." strkey nor hexadecimal, has a length matching none of
     * the accepted shapes, or carries a discriminant other than
     * CLAIMABLE_BALANCE_ID_TYPE_V0
     */
    public function getCanonicalHashHex(): string {
        $value = $this->hash;
        if ($value === null || $value === '') {
            throw new InvalidArgumentException('Claimable balance id is not set');
        }
        if (strlen($value) === self::STRKEY_LENGTH && $value[0] === 'B') {
            // StrKey judges the checksum and the discriminant and returns the 33
            // payload bytes as hexadecimal, discriminant first. The base32 and
            // hexadecimal alphabets share A-F and 2-7, so a string of this length and
            // shape can read as either. The strkey reading is taken first because it
            // is the only one that can succeed: 58 characters is not a length any of
            // the hexadecimal spellings has. A value that fails it and is hexadecimal
            // falls to the hex rules below, which name the rule it breaks.
            try {
                return substr(StrKey::decodeClaimableBalanceIdHex($value), 2);
            } catch (InvalidArgumentException $e) {
                if (!ctype_xdigit($value)) {
                    throw $e;
                }
            }
        }
        if (!ctype_xdigit($value)) {
            throw new InvalidArgumentException(
                'Claimable balance id must be a "B..." strkey or a hexadecimal string,'
                . ' "' . XdrJsonHelper::safePreview($value) . '" given'
            );
        }
        // Hexadecimal is case insensitive; lower case is the canonical spelling, so
        // every reader reports a single string per balance.
        $value = strtolower($value);
        if (strlen($value) === self::HASH_HEX_LENGTH) {
            return $value;
        }
        if (strlen($value) === self::STRKEY_PREFIXED_HEX_LENGTH
            || strlen($value) === self::XDR_PREFIXED_HEX_LENGTH) {
            $prefix = substr($value, 0, strlen($value) - self::HASH_HEX_LENGTH);
            if ($prefix !== str_repeat('0', strlen($prefix))) {
                throw new InvalidArgumentException(sprintf(
                    'Claimable balance id carries the type prefix "%s", which does not name'
                        . ' CLAIMABLE_BALANCE_ID_TYPE_V0 (0), the only case ClaimableBalanceID has',
                    $prefix
                ));
            }
            return substr($value, -self::HASH_HEX_LENGTH);
        }
        throw new InvalidArgumentException(sprintf(
            'Claimable balance id must be %d characters as a "B..." strkey, or the balance'
                . ' hash as %d hexadecimal characters, which a type discriminant may prefix to'
                . ' %d or %d characters; %d characters given',
            self::STRKEY_LENGTH,
            self::HASH_HEX_LENGTH,
            self::STRKEY_PREFIXED_HEX_LENGTH,
            self::XDR_PREFIXED_HEX_LENGTH,
            strlen($value)
        ));
    }

    public static function forClaimableBalanceId(string $claimableBalanceId) : XdrClaimableBalanceID {
        return new XdrClaimableBalanceID(
            XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0(),
            $claimableBalanceId,
        );
    }

    /**
     * Returns the balance id as the "B..." strkey that SEP-51 uses for
     * ClaimableBalanceID, resolving whichever spelling the hash field holds.
     *
     * @return string the balance id as a "B..." strkey
     * @throws InvalidArgumentException when the hash field holds none of the spellings
     * getCanonicalHashHex() accepts, or when the discriminant is not
     * CLAIMABLE_BALANCE_ID_TYPE_V0
     */
    public function toJsonValue(): string {
        switch ($this->type->getValue()) {
            case XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0:
                return StrKey::encodeClaimableBalanceId(pack("H*", $this->getCanonicalHashHex()));
            default:
                throw new InvalidArgumentException(
                    'Unknown XdrClaimableBalanceID discriminant: ' . $this->type->getValue()
                );
        }
    }

    /**
     * Emits the hash as the bare 32-byte balance hash in hexadecimal, resolving
     * whichever spelling the hash field holds.
     *
     * @param string               $prefix
     * @param array<string,string> $lines
     * @throws InvalidArgumentException when the hash field holds none of the spellings
     * getCanonicalHashHex() accepts
     */
    public function toTxRep(string $prefix, array &$lines): void {
        $this->type->toTxRep($prefix . '.type', $lines);
        switch ($this->type->getValue()) {
            case XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0:
                $lines[$prefix . '.v0'] = $this->getCanonicalHashHex();
                break;
            default:
                break;
        }
    }

    /**
     * Override fromTxRep to store the hash as a hex string (as the constructor
     * and encode() expect) rather than converting to/from binary bytes.
     *
     * @param array<string,string> $map
     * @param string               $prefix
     * @return static
     */
    public static function fromTxRep(array $map, string $prefix): static {
        $disc = XdrClaimableBalanceIDType::fromTxRep($map, $prefix . '.type');
        $hash = '';
        switch ($disc->getValue()) {
            case XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0:
                $hash = TxRepHelper::getValue($map, $prefix . '.v0') ?? '';
                break;
            default:
                break;
        }
        return new static($disc, $hash);
    }
}
