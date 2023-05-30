<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Responses\HostFunction\HostFunctionResponse;
use Soneso\StellarSDK\Responses\HostFunction\HostFunctionsResponse;

class InvokeHostFunctionOperationResponse extends OperationResponse
{
    private ?HostFunctionsResponse $hostFunctions = null;

    protected function loadFromJson(array $json) : void {

        if (isset($json['host_functions'])) {
            $this->hostFunctions = new HostFunctionsResponse();
            foreach ($json['host_functions'] as $jsonValue) {
                $value = HostFunctionResponse::fromJson($jsonValue);
                $this->hostFunctions->add($value);
            }
        }
    }

    public static function fromJson(array $jsonData) : InvokeHostFunctionOperationResponse {
        $result = new InvokeHostFunctionOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }

    /**
     * @return HostFunctionsResponse|null
     */
    public function getHostFunctions(): ?HostFunctionsResponse
    {
        return $this->hostFunctions;
    }

    /**
     * @param HostFunctionsResponse|null $hostFunctions
     */
    public function setHostFunctions(?HostFunctionsResponse $hostFunctions): void
    {
        $this->hostFunctions = $hostFunctions;
    }
}