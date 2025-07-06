<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;


class InvokeHostFunctionOperationResponse extends OperationResponse
{
    public string $function;
    public ?ParametersResponse $parameters = null;
    public string $address;
    public string $salt;
    public ?AssetBalanceChangesResponse $assetBalanceChanges = null;
    public ?string $destinationMuxedId = null; // a uint64

    protected function loadFromJson(array $json) : void {
        $this->function = $json['function'];
        if (isset($json['parameters'])) {
            $this->parameters = new ParametersResponse();
            foreach ($json['parameters'] as $jsonValue) {
                $value = ParameterResponse::fromJson($jsonValue);
                $this->parameters->add($value);
            }
        }
        $this->address = $json['address'];
        $this->salt = $json['salt'];

        if (isset($json['asset_balance_changes'])) {
            $this->assetBalanceChanges  = new AssetBalanceChangesResponse();
            foreach ($json['asset_balance_changes'] as $jsonValue) {
                $value = AssetBalanceChangeResponse::fromJson($jsonValue);
                $this->assetBalanceChanges->add($value);
            }
        }
        if (isset($json['destination_muxed_id'])) {
            $this->destinationMuxedId = $json['destination_muxed_id'];
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

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getSalt(): string
    {
        return $this->salt;
    }

    /**
     * @param string $salt
     */
    public function setSalt(string $salt): void
    {
        $this->salt = $salt;
    }

    /**
     * @return AssetBalanceChangesResponse|null
     */
    public function getAssetBalanceChanges(): ?AssetBalanceChangesResponse
    {
        return $this->assetBalanceChanges;
    }

    /**
     * @param AssetBalanceChangesResponse|null $assetBalanceChanges
     */
    public function setAssetBalanceChanges(?AssetBalanceChangesResponse $assetBalanceChanges): void
    {
        $this->assetBalanceChanges = $assetBalanceChanges;
    }

    /**
     * @return string|null
     */
    public function getDestinationMuxedId(): ?string
    {
        return $this->destinationMuxedId;
    }

    /**
     * @param string|null $destinationMuxedId
     */
    public function setDestinationMuxedId(?string $destinationMuxedId): void
    {
        $this->destinationMuxedId = $destinationMuxedId;
    }

}