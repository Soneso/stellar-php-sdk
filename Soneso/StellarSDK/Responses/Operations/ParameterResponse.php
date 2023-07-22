<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;


class ParameterResponse
{
    public string $type;
    public array $json;
    public ?string $value = null;
    public ?string $from = null;
    public ?string $salt = null;
    public ?string $asset = null;

    protected function loadFromJson(array $json) : void {
        $this->json = $json;
        $this->type = $json['type'];
        if (isset($json['value'])) {
            $this->value = $json['value'];
        }
        if (isset($json['from'])) {
            $this->from = $json['from'];
        }
        if (isset($json['salt'])) {
            $this->salt = $json['salt'];
        }
        if (isset($json['asset'])) {
            $this->value = $json['asset'];
        }
    }

    public static function fromJson(array $json) : ParameterResponse {
        $result = new ParameterResponse();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return array
     */
    public function getJson(): array
    {
        return $this->json;
    }

    /**
     * @param array $json
     */
    public function setJson(array $json): void
    {
        $this->json = $json;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param string|null $value
     */
    public function setValue(?string $value): void
    {
        $this->value = $value;
    }

    /**
     * @return string|null
     */
    public function getFrom(): ?string
    {
        return $this->from;
    }

    /**
     * @param string|null $from
     */
    public function setFrom(?string $from): void
    {
        $this->from = $from;
    }

    /**
     * @return string|null
     */
    public function getSalt(): ?string
    {
        return $this->salt;
    }

    /**
     * @param string|null $salt
     */
    public function setSalt(?string $salt): void
    {
        $this->salt = $salt;
    }

    /**
     * @return string|null
     */
    public function getAsset(): ?string
    {
        return $this->asset;
    }

    /**
     * @param string|null $asset
     */
    public function setAsset(?string $asset): void
    {
        $this->asset = $asset;
    }

}