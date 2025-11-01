<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\SEP\Derivation;

use Soneso\StellarSDK\Constants\CryptoConstants;
use Soneso\StellarSDK\Constants\StellarConstants;

/**
 * A Hierarchical Deterministic node for use with Stellar
 *
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
     * Returns a new master node that can be used to derive subnodes
     *
     * @param string $entropy
     * @return HDNode
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
     * @param $privateKeyBytes (string) 32 bytes of randomly generated data for the private key
     * @param $chainCodeBytes (string) 32 bytes of randomly generated data for deriving additional keys
     */
    public function __construct(string $privateKeyBytes, string $chainCodeBytes)
    {
        if (strlen($privateKeyBytes) != StellarConstants::ED25519_PUBLIC_KEY_LENGTH_BYTES) throw new \InvalidArgumentException('Private key must be 32 bytes');
        if (strlen($chainCodeBytes) != CryptoConstants::CHAIN_CODE_LENGTH_BYTES) throw new \InvalidArgumentException('Chain code must be 32 bytes');

        $this->privateKeyBytes = $privateKeyBytes;
        $this->chainCodeBytes = $chainCodeBytes;
    }

    /**
     * @param $index int automatically converted to a hardened index
     * @return HDNode
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
     * Derives a path like m/0'/1'
     * @param string $path
     * @return HDNode
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
     * @return string
     */
    public function getPrivateKeyBytes() : string
    {
        return $this->privateKeyBytes;
    }

    /**
     * @return string
     */
    public function getChainCodeBytes() : string
    {
        return $this->chainCodeBytes;
    }
}