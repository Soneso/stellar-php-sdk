<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

class SignerEffectResponse extends EffectResponse
{
    private string $publicKey;
    private int $weight;
    private ?string $key = null;

    /**
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * @return int
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    /**
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['public_key'])) $this->publicKey = $json['public_key'];
        if (isset($json['weight'])) $this->weight = $json['weight'];
        if (isset($json['key'])) $this->publicKey = $json['key'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : SignerEffectResponse {
        $result = new SignerEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}