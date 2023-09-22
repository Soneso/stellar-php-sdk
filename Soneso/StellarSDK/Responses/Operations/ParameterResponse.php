<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;


class ParameterResponse
{
    public string $type;
    public string $value;

    protected function loadFromJson(array $json) : void {
        $this->type = $json['type'];
        $this->value = $json['value'];
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
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

}