<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Base class for signer-related effects from the Stellar network
 *
 * This represents effects that involve changes to account signers.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse Base effect class
 * @see SignerCreatedEffectResponse When a signer is added
 * @see SignerUpdatedEffectResponse When a signer is modified
 * @see SignerRemovedEffectResponse When a signer is removed
 * @see https://developers.stellar.org/api/resources/effects Horizon Effects API
 * @since 1.0.0
 */
class SignerEffectResponse extends EffectResponse
{
    private string $publicKey;
    private int $weight;
    private ?string $key = null;

    /**
     * Gets the public key of the signer
     *
     * @return string The signer's public key
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Gets the weight of the signer
     *
     * @return int The signer's weight for multi-signature operations
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    /**
     * Gets the key value if available
     *
     * @return string|null The key value, or null if not set
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['public_key'])) $this->publicKey = $json['public_key'];
        if (isset($json['weight'])) $this->weight = $json['weight'];
        if (isset($json['key'])) $this->key = $json['key'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : SignerEffectResponse {
        $result = new SignerEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}