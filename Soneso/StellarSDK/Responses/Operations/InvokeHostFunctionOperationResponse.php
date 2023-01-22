<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Responses\HostFunction\ParameterResponse;
use Soneso\StellarSDK\Responses\HostFunction\ParametersResponse;

class InvokeHostFunctionOperationResponse extends OperationResponse
{
    private string $function;
    private string $footprint;
    private ?ParametersResponse $parameters;

    protected function loadFromJson(array $json) : void {

        $this->function = $json['function'];
        $this->footprint = $json['footprint'];
        if (isset($json['parameters'])) {
            $this->parameters = new ParametersResponse();
            foreach ($json['parameters'] as $jsonValue) {
                $value = ParameterResponse::fromJson($jsonValue);
                $this->parameters->add($value);
            }
        }
    }

    public static function fromJson(array $jsonData) : InvokeHostFunctionOperationResponse {
        $result = new InvokeHostFunctionOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }

    /**
     * @return string
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * @param string $function
     */
    public function setFunction(string $function): void
    {
        $this->function = $function;
    }

    /**
     * @return string
     */
    public function getFootprint(): string
    {
        return $this->footprint;
    }

    /**
     * @param string $footprint
     */
    public function setFootprint(string $footprint): void
    {
        $this->footprint = $footprint;
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