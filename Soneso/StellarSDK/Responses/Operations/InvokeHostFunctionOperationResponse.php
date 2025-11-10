<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

/**
 * Represents an invoke host function operation response from Horizon API
 *
 * This Soroban operation executes a smart contract function on the Stellar network. It can invoke
 * contract functions, deploy new contracts, or upload contract code. The operation includes the
 * function type (invoke, upload, or deploy), parameters passed to the function, the contract address,
 * and tracks any asset balance changes that occurred during contract execution.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org/api/resources/operations/object/invoke-host-function Horizon Invoke Host Function Operation
 */
class InvokeHostFunctionOperationResponse extends OperationResponse
{
    public string $function;
    public ?ParametersResponse $parameters = null;
    public string $address;
    public string $salt;
    public ?AssetBalanceChangesResponse $assetBalanceChanges = null;

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

        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : InvokeHostFunctionOperationResponse {
        $result = new InvokeHostFunctionOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }

    /**
     * Gets the host function type being invoked
     *
     * @return string Function type (InvokeContract, UploadContractWasm, or CreateContract)
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * Sets the host function type being invoked
     *
     * @param string $function Function type (InvokeContract, UploadContractWasm, or CreateContract)
     * @return void
     */
    public function setFunction(string $function): void
    {
        $this->function = $function;
    }

    /**
     * Gets the parameters passed to the contract function
     *
     * @return ParametersResponse|null Collection of function parameters or null
     */
    public function getParameters(): ?ParametersResponse
    {
        return $this->parameters;
    }

    /**
     * Sets the parameters passed to the contract function
     *
     * @param ParametersResponse|null $parameters Collection of function parameters or null
     * @return void
     */
    public function setParameters(?ParametersResponse $parameters): void
    {
        $this->parameters = $parameters;
    }

    /**
     * Gets the contract address being invoked
     *
     * @return string The smart contract address
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * Sets the contract address being invoked
     *
     * @param string $address The smart contract address
     * @return void
     */
    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    /**
     * Gets the salt used for contract deployment
     *
     * @return string The deployment salt value
     */
    public function getSalt(): string
    {
        return $this->salt;
    }

    /**
     * Sets the salt used for contract deployment
     *
     * @param string $salt The deployment salt value
     * @return void
     */
    public function setSalt(string $salt): void
    {
        $this->salt = $salt;
    }

    /**
     * Gets asset balance changes caused by contract execution
     *
     * @return AssetBalanceChangesResponse|null Collection of balance changes or null
     */
    public function getAssetBalanceChanges(): ?AssetBalanceChangesResponse
    {
        return $this->assetBalanceChanges;
    }

    /**
     * Sets asset balance changes caused by contract execution
     *
     * @param AssetBalanceChangesResponse|null $assetBalanceChanges Collection of balance changes or null
     * @return void
     */
    public function setAssetBalanceChanges(?AssetBalanceChangesResponse $assetBalanceChanges): void
    {
        $this->assetBalanceChanges = $assetBalanceChanges;
    }
}