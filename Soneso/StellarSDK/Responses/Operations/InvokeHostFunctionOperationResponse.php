<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;


class InvokeHostFunctionOperationResponse extends OperationResponse
{
    public string $function;
    public ?ParametersResponse $parameters = null;

    protected function loadFromJson(array $json) : void {
        $this->function = $json['function'];
        if (isset($json['parameters'])) {
            $this->parameters = new ParametersResponse();
            foreach ($json['parameters'] as $jsonValue) {
                $value = ParameterResponse::fromJson($jsonValue);
                $this->parameters->add($value);
            }
        }
        parent::loadFromJson($json);
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