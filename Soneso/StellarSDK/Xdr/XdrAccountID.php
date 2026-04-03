<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use Soneso\StellarSDK\Crypto\StrKey;

class XdrAccountID extends XdrAccountIDBase
{
    /**
     * Serialize as a compact StrKey account ID string (G...).
     *
     * Overrides the generated base method, which would expand the inner
     * XdrPublicKey sub-fields. SEP-0011 requires a single G... value.
     *
     * @param string                $prefix Key prefix for the TxRep map.
     * @param array<string, string> $lines  Output map (modified in place).
     */
    public function toTxRep(string $prefix, array &$lines): void
    {
        $lines[$prefix] = TxRepHelper::formatAccountId($this);
    }

    /**
     * Deserialize from a compact StrKey account ID string (G...).
     *
     * @param array<string, string> $map    Parsed TxRep map.
     * @param string                $prefix Key prefix.
     * @return static
     * @throws \InvalidArgumentException If the value is missing or invalid.
     */
    public static function fromTxRep(array $map, string $prefix): static
    {
        $raw = TxRepHelper::getValue($map, $prefix);
        if ($raw === null) {
            throw new \InvalidArgumentException('Missing TxRep value for: ' . $prefix);
        }
        if (!StrKey::isValidAccountId($raw)) {
            throw new \InvalidArgumentException('Invalid account ID in TxRep for key "' . $prefix . '": ' . $raw);
        }
        return new static($raw);
    }

    private string $accountId; // G...

    /**
     * Constructor.
     * @param string $accountId Base32 encoded public key/account id starting with G
     */
    public function __construct(string $accountId)
    {
        $pk = new XdrPublicKey(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519));
        parent::__construct($pk);
        $this->accountId = $accountId;
    }

    /**
     * @return string Base32 encoded public key/account id starting with G
     */
    public function getAccountId(): string
    {
        return $this->accountId;
    }

    /**
     * Creates a new XdrAccountID from the passed stellar account id.
     * @param string $accountId Base32 encoded public key/account id starting with G
     * @return XdrAccountID
     */
    public static function fromAccountId(string $accountId) : XdrAccountID {
        return new XdrAccountID($accountId);
    }

    public function encode(): string
    {
        $this->accountID->ed25519 = StrKey::decodeAccountId($this->accountId);
        return parent::encode();
    }

    public static function decode(XdrBuffer $xdr): static {
        $pk = XdrPublicKey::decode($xdr);
        return new static(StrKey::encodeAccountId($pk->ed25519));
    }
}
