<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\SEP\Derivation;

use Soneso\StellarSDK\Constants\CryptoConstants;
use Soneso\StellarSDK\Constants\StellarConstants;

/**
 * Hierarchical Deterministic (HD) node for Stellar key derivation.
 *
 * This class implements BIP-32 style hierarchical deterministic key derivation
 * for Stellar accounts. It supports the SEP-0005 standard for deriving multiple
 * keypairs from a single seed using derivation paths like m/44'/148'/0'.
 *
 * @package Soneso\StellarSDK\SEP\Derivation
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0005.md
 * @see https://github.com/bitcoin/bips/blob/master/bip-0032.mediawiki
 * @see Mnemonic
 */
class HDNode
{

    /**
     * @var string
     */
    protected string $privateKeyBytes;

    /**
     * @var string
     */
    protected string $chainCodeBytes;

    /**
     * Creates a new master HD node from entropy.
     *
     * @param string $entropy Binary entropy (typically 64 bytes from mnemonic seed).
     * @return HDNode A new master node that can derive child keys.
     */
    public static function newMasterNode(string $entropy) : HDNode
    {
        $hmac = hash_hmac('sha512', $entropy, 'ed25519 seed', true);

        return new HDNode(
            substr($hmac, 0, CryptoConstants::HMAC_KEY_PART_LENGTH),
            substr($hmac, CryptoConstants::HMAC_CHAIN_PART_OFFSET, CryptoConstants::HMAC_KEY_PART_LENGTH)
        );
    }

    /**
     * HDNode constructor.
     *
     * @param string $privateKeyBytes 32 bytes of private key material.
     * @param string $chainCodeBytes 32 bytes of chain code for deriving child keys.
     * @throws InvalidArgumentException If private key or chain code is not 32 bytes.
     */
    public function __construct(string $privateKeyBytes, string $chainCodeBytes)
    {
        if (strlen($privateKeyBytes) != StellarConstants::ED25519_PUBLIC_KEY_LENGTH_BYTES) throw new \InvalidArgumentException('Private key must be 32 bytes');
        if (strlen($chainCodeBytes) != CryptoConstants::CHAIN_CODE_LENGTH_BYTES) throw new \InvalidArgumentException('Chain code must be 32 bytes');

        $this->privateKeyBytes = $privateKeyBytes;
        $this->chainCodeBytes = $chainCodeBytes;
    }

    /**
     * Derives a child node at the specified index.
     *
     * @param int $index Child index (automatically converted to hardened).
     * @return HDNode The derived child node.
     * @throws InvalidArgumentException If the resulting index is not hardened.
     */
    public function derive(int $index) : HDNode
    {
        $index = intval($index) + intval(CryptoConstants::BIP32_HARDENED_MINIMUM_INDEX);
        if ($index < CryptoConstants::BIP32_HARDENED_MINIMUM_INDEX) throw new \InvalidArgumentException('Only hardened indexes are supported');

        // big-endian unsigned long (4 bytes)
        $indexBytes = pack('N', $index);
        $key = pack('C', CryptoConstants::KEY_PADDING_BYTE) . $this->privateKeyBytes . $indexBytes;

        $hmac = hash_hmac('sha512', $key, $this->chainCodeBytes, true);

        return new HDNode(
            substr($hmac, 0, CryptoConstants::HMAC_KEY_PART_LENGTH),
            substr($hmac, CryptoConstants::HMAC_CHAIN_PART_OFFSET, CryptoConstants::HMAC_KEY_PART_LENGTH)
        );
    }

    /**
     * Derives a node following a BIP-32 derivation path.
     *
     * @param string $path Derivation path (e.g., "m/44'/148'/0'").
     * @return HDNode The derived node.
     * @throws InvalidArgumentException If path format is invalid.
     */
    public function derivePath(string $path) : HDNode
    {
        $pathParts = $this->parseDerivationPath($path);

        $derived = $this;
        foreach ($pathParts as $index) {
            $derived = $derived->derive($index);
        }

        return $derived;
    }

    /**
     * Takes a path like "m/0'/1'" and returns an array of indexes to derive
     *
     * Note that since this class assumes all indexes are hardened, the returned
     * array for the above example would be:
     *  [0, 1]
     *
     * @param string $path
     * @return array
     */
    protected function parseDerivationPath(string $path) : array
    {
        $parsed = [];
        $parts = explode('/', $path);
        if (strtolower($parts[0]) != 'm') throw new \InvalidArgumentException('Path must start with "m"');

        // Remove initial 'm' since it refers to the current HDNode
        array_shift($parts);

        // Add each part to the return value
        foreach ($parts as $part) {
            // Each subsequent node must be hardened
            if (strpos($part, "'") != (strlen($part)-1)) throw new \InvalidArgumentException('Path can only contain hardened indexes');
            $part = str_replace("'", '', $part);

            if (!is_numeric($part)) throw new \InvalidArgumentException('Path must be numeric');

            $parsed[] = intval($part);
        }

        return $parsed;
    }

    /**
     * Gets the private key bytes for this node.
     *
     * @return string 32 bytes of private key material.
     */
    public function getPrivateKeyBytes() : string
    {
        return $this->privateKeyBytes;
    }

    /**
     * Gets the chain code bytes for this node.
     *
     * @return string 32 bytes of chain code.
     */
    public function getChainCodeBytes() : string
    {
        return $this->chainCodeBytes;
    }
}