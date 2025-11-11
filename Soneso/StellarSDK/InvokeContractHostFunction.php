<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Exception;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrInvokeContractArgs;
use Soneso\StellarSDK\Xdr\XdrSCVal;

/**
 * Represents a Soroban smart contract invocation host function
 *
 * This host function type is used to invoke functions on deployed Soroban smart contracts.
 * It specifies the contract to call, the function name, and any arguments to pass to the function.
 *
 * The contract ID can be provided in various formats including hex contract ID, StrKey-encoded
 * contract ID, or other address types that can be converted using Address::fromAnyId().
 *
 * Usage:
 * <code>
 * // Invoke a contract function without arguments
 * $hostFunction = new InvokeContractHostFunction(
 *     "CCFIFQ...", // Contract ID
 *     "transfer"   // Function name
 * );
 *
 * // Invoke a contract function with arguments
 * $hostFunction = new InvokeContractHostFunction(
 *     "CCFIFQ...",
 *     "transfer",
 *     [XdrSCVal::forAddress($fromAddr), XdrSCVal::forAddress($toAddr), XdrSCVal::forU64(1000)]
 * );
 * </code>
 *
 * @package Soneso\StellarSDK
 * @see HostFunction Base class for all host functions
 * @see Address For address conversion utilities
 * @see https://developers.stellar.org/docs/smart-contracts/guides/functions
 * @since 1.0.0
 */
class InvokeContractHostFunction extends HostFunction
{
    /**
     * @var string $contractId Can be any type that can be converted to an address.
     * E.g. hex contract id, StrKey contract id, hex claimable balance id, StrKey claimable balance id, etc.
     * Use Address::fromAnyId($contractId) to get the exact type.
     */
    public string $contractId;

    /**
     * @var string $functionName The name of the contract function to invoke
     */
    public string $functionName;

    /**
     * @var array<XdrSCVal>|null $arguments Optional array of Soroban values to pass as function arguments
     */
    public ?array $arguments;

    /**
     * Constructs a new InvokeContractHostFunction
     *
     * @param string $contractId The contract ID (can be hex or StrKey-encoded)
     * @param string $functionName The name of the function to invoke
     * @param array<XdrSCVal>|null $arguments Optional array of function arguments
     */
    public function __construct(string $contractId, string $functionName, ?array $arguments = null)
    {
        $this->contractId = $contractId;
        $this->functionName = $functionName;
        $this->arguments = $arguments;
        parent::__construct();
    }

    /**
     * Converts the invoke contract host function to XDR format
     *
     * @return XdrHostFunction The XDR host function
     */
    public function toXdr() : XdrHostFunction {
        $args = array();
        if ($this->arguments != null) {
            $args = array_merge($args, $this->arguments);
        }
        // allow all kinds of addresses
        $address = Address::fromAnyId($this->contractId);
        $invokeArgs = new XdrInvokeContractArgs($address->toXdr(),
            $this->functionName,$args);
        return XdrHostFunction::forInvokingContractWithArgs($invokeArgs);
    }

    /**
     * Creates an InvokeContractHostFunction from XDR format
     *
     * @param XdrHostFunction $xdr The XDR host function
     * @return InvokeContractHostFunction The decoded host function
     * @throws Exception If the XDR does not contain invoke contract data
     */
    public static function fromXdr(XdrHostFunction $xdr) : InvokeContractHostFunction {
        $invokeContract = $xdr->invokeContract;
        if ($invokeContract == null) {
            throw new Exception("Invalid argument");
        }
        // allow all types of addresses
        $contractId = $invokeContract->contractAddress->toStrKey();
        $functionName = $invokeContract->functionName;
        $args= $invokeContract->getArgs();

        return new InvokeContractHostFunction($contractId, $functionName, $args);
    }

    /**
     * Gets the contract ID
     *
     * @return string The contract ID
     */
    public function getContractId(): string
    {
        return $this->contractId;
    }

    /**
     * Sets the contract ID
     *
     * @param string $contractId The contract ID
     * @return void
     */
    public function setContractId(string $contractId): void
    {
        $this->contractId = $contractId;
    }

    /**
     * Gets the function name
     *
     * @return string The function name
     */
    public function getFunctionName(): string
    {
        return $this->functionName;
    }

    /**
     * Sets the function name
     *
     * @param string $functionName The function name
     * @return void
     */
    public function setFunctionName(string $functionName): void
    {
        $this->functionName = $functionName;
    }

    /**
     * Gets the function arguments
     *
     * @return array<XdrSCVal>|null The array of Soroban values, or null if no arguments
     */
    public function getArguments(): ?array
    {
        return $this->arguments;
    }

    /**
     * Sets the function arguments
     *
     * @param array<XdrSCVal>|null $arguments The array of Soroban values, or null for no arguments
     * @return void
     */
    public function setArguments(?array $arguments): void
    {
        $this->arguments = $arguments;
    }

}