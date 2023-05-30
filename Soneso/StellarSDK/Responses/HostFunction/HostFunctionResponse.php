<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\HostFunction;


class HostFunctionResponse
{
    public string $type;
    public ?ParametersResponse $parameters;


    protected function loadFromJson(array $json) : void {

        $this->type = $json['type'];
        if (isset($json['parameters'])) {
            $this->parameters = new ParametersResponse();
            foreach ($json['parameters'] as $jsonValue) {
                $value = ParameterResponse::fromJson($jsonValue);
                $this->parameters->add($value);
            }
        }
    }

    public static function fromJson(array $json) : HostFunctionResponse {
        $result = new HostFunctionResponse();
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
     * @return ParametersResponse|null
     */
    public function getParameters(): ?ParametersResponse
    {
        return $this->parameters;
    }

    /**
     * @param ParametersResponse|null $parameters
     */
    public function setParameters(?ParametersResponse $parameters): void
    {
        $this->parameters = $parameters;
    }

}