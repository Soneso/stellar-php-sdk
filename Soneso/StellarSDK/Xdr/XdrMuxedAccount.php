<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use InvalidArgumentException;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;

class XdrMuxedAccount extends XdrMuxedAccountBase
{
    /**
     * Constructor. Provide $ed25519 or $med25519.
     * @param string|null $ed25519
     * @param XdrMuxedAccountMed25519|null $med25519
     */
    public function __construct(?string $ed25519 = null, ?XdrMuxedAccountMed25519 $med25519 = null) {

        if (!$ed25519 && !$med25519) {
            throw new \InvalidArgumentException("ed25519 or med25519 must be provided");
        }
        if ($ed25519 && $med25519) {
            throw new \InvalidArgumentException("can not accept both ed25519 and med25519");
        }

        if ($ed25519) {
            parent::__construct(XdrCryptoKeyType::KEY_TYPE_ED25519());
            $this->ed25519 = $ed25519;
        } else {
            parent::__construct(XdrCryptoKeyType::KEY_TYPE_MUXED_ED25519());
            $this->med25519 = $med25519;
        }
    }

    public static function decode(XdrBuffer $xdr): static {
        $type = XdrCryptoKeyType::decode($xdr);
        switch ($type->getValue()) {
            case XdrCryptoKeyType::KEY_TYPE_ED25519:
                return new static($xdr->readOpaqueFixed(32));
            case XdrCryptoKeyType::KEY_TYPE_MUXED_ED25519:
                return new static(null, XdrMuxedAccountMed25519::decode($xdr));
            default:
                throw new \InvalidArgumentException("wrong discriminant " . $type->getValue() . " in xdrBuffer");
        }
    }

    public function getDiscriminant(): int
    {
        return $this->type->getValue();
    }

    /**
     * Serialize as a compact StrKey address string.
     *
     * Returns a G... address for plain Ed25519 accounts, or an M... address
     * for muxed (med25519) accounts. Overrides the generated base method,
     * which would expand discriminant and sub-field lines.
     *
     * @param string                $prefix Key prefix for the TxRep map.
     * @param array<string, string> $lines  Output map (modified in place).
     */
    public function toTxRep(string $prefix, array &$lines): void
    {
        $lines[$prefix] = TxRepHelper::formatMuxedAccount($this);
    }

    /**
     * Deserialize from a compact StrKey address string (G... or M...).
     *
     * Constructs a plain Ed25519 muxed account from a G... address, or a
     * muxed ed25519 account from an M... address.
     *
     * @param array<string, string> $map    Parsed TxRep map.
     * @param string                $prefix Key prefix.
     * @return static
     * @throws InvalidArgumentException If the value is missing or invalid.
     */
    public static function fromTxRep(array $map, string $prefix): static
    {
        $raw = TxRepHelper::getValue($map, $prefix);
        if ($raw === null) {
            throw new InvalidArgumentException('Missing TxRep value for: ' . $prefix);
        }

        if ($raw === '') {
            throw new InvalidArgumentException('Empty muxed account address for: ' . $prefix);
        }

        $firstChar = $raw[0];

        if ($firstChar === 'G') {
            if (!StrKey::isValidAccountId($raw)) {
                throw new InvalidArgumentException('Invalid account ID: ' . $raw);
            }
            return new static(KeyPair::fromAccountId($raw)->getPublicKey());
        }

        if ($firstChar === 'M') {
            // M... address encodes: ed25519 (32 bytes) + id (8 bytes) in inverted order.
            $bytes = StrKey::decodeMuxedAccountId($raw);
            $xdrBuffer = new XdrBuffer($bytes);
            $med25519 = XdrMuxedAccountMed25519::decodeInverted($xdrBuffer);
            return new static(null, $med25519);
        }

        throw new InvalidArgumentException('Unrecognized muxed account address prefix "' . $firstChar . '": ' . $raw);
    }
}
