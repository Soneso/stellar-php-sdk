<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

/**
 * Represents a Stellar network with its unique passphrase
 *
 * Each Stellar network (public, testnet, futurenet) has a unique network passphrase that
 * is included in every transaction signature. This ensures transactions cannot be replayed
 * on different networks, providing critical security.
 *
 * Network Passphrases:
 * - Public Network: "Public Global Stellar Network ; September 2015"
 * - Test Network: "Test SDF Network ; September 2015"
 * - Future Network: "Test SDF Future Network ; October 2022"
 *
 * Usage:
 * <code>
 * // Use predefined networks
 * $network = Network::public();
 * $network = Network::testnet();
 * $network = Network::futurenet();
 *
 * // Or create a custom network
 * $network = new Network("Custom Network Passphrase");
 *
 * // Sign a transaction for a specific network
 * $transaction->sign($keyPair, Network::testnet());
 * </code>
 *
 * @package Soneso\StellarSDK
 * @see https://developers.stellar.org/docs/learn/fundamentals/networks
 * @since 1.0.0
 */
class Network
{
    private string $networkPassphrase;

    /**
     * Creates a Network with the specified passphrase
     *
     * @param string $networkPassphrase The unique passphrase for this network
     */
    public function __construct(string $networkPassphrase)
    {
        $this->networkPassphrase = $networkPassphrase;
    }

    /**
     * Returns the network passphrase
     *
     * The network passphrase is hashed and included in transaction signatures to prevent
     * replay attacks across different networks.
     *
     * @return string The network passphrase
     */
    public function getNetworkPassphrase(): string
    {
        return $this->networkPassphrase;
    }

    /**
     * Returns a Network instance for the Stellar public network
     *
     * This is the main production network where real assets with value are traded.
     *
     * @return Network The public network instance
     */
    public static function public() : Network {
        return new Network("Public Global Stellar Network ; September 2015");
    }

    /**
     * Returns a Network instance for the Stellar test network
     *
     * Use this network for testing and development. Test network assets have no value
     * and accounts can be funded using friendbot.
     *
     * @return Network The test network instance
     */
    public static function testnet() : Network {
        return new Network("Test SDF Network ; September 2015");
    }

    /**
     * Returns a Network instance for the Stellar future network
     *
     * This network is used for testing upcoming protocol features. It may undergo
     * frequent resets and should be used with caution.
     *
     * @return Network The future network instance
     */
    public static function futurenet() : Network {
        return new Network("Test SDF Future Network ; October 2022");
    }
}