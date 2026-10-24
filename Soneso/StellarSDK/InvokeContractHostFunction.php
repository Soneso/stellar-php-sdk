<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Exception;
use InvalidArgumentException;
use ReflectionClass;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\ContractAddressGuard;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrInvokeContractArgs;
use Soneso\StellarSDK\Xdr\XdrSCVal;

/**
 * Represents a Soroban smart contract invocation host function
 *
 * This host function type is used to invoke functions on deployed Soroban smart contracts.
 * It specifies the contract to call, the function name, and any arguments to pass to the function.
 *
 * The contract ID is a contract strkey ("C...") or the contract hash as 64 hexadecimal characters.
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
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 */
class InvokeContractHostFunction extends HostFunction
{
    /**
     * @var string $contractId the contract to invoke, as a "C..." strkey or as 64 hexadecimal characters;
     * an instance from fromXdr() holds the strkey of the address its XDR names, of any kind
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
     * @param string $contractId The contract to invoke, as a "C..." strkey or 64 hexadecimal characters
     * @param string $functionName The name of the function to invoke
     * @param array<XdrSCVal>|null $arguments Optional array of function arguments
     * @throws InvalidArgumentException If the contract ID is not a contract address
     */
    public function __construct(string $contractId, string $functionName, ?array $arguments = null)
    {
        ContractAddressGuard::requireContractId($contractId, ContractAddressGuard::CONTRACT_TO_INVOKE);
        $this->contractId = $contractId;
        $this->functionName = $functionName;
        $this->arguments = $arguments;
        parent::__construct();
    }

    /**
     * Converts the invoke contract host function to XDR format
     *
     * Encodes the contract ID as the address kind it names, so an instance from fromXdr()
     * re-encodes to the XDR it was decoded from.
     *
     * @return XdrHostFunction The XDR host function
     * @throws InvalidArgumentException If the contract ID names no address
     */
    public function toXdr() : XdrHostFunction {
        $args = array();
        if ($this->arguments !== null) {
            $args = array_merge($args, $this->arguments);
        }
        $address = Address::fromAnyId($this->contractId);
        if ($address === null) {
            throw ContractAddressGuard::notAnAddress($this->contractId, ContractAddressGuard::CONTRACT_TO_INVOKE);
        }
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
        if ($invokeContract === null) {
            throw new Exception("Invalid argument");
        }
        // The constructor admits contract targets only. A decoded instance keeps the address
        // its XDR names, of any kind, so decoding and encoding an envelope stays lossless.
        // HostFunction's constructor holds no state, so skipping both constructors is safe.
        $hostFunction = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $hostFunction->contractId = $invokeContract->contractAddress->toStrKey();
        $hostFunction->functionName = $invokeContract->functionName;
        $hostFunction->arguments = $invokeContract->getArgs();
        return $hostFunction;
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
     * @param string $contractId The contract to invoke, as a "C..." strkey or 64 hexadecimal characters
     * @return void
     * @throws InvalidArgumentException If the contract ID is not a contract address
     */
    public function setContractId(string $contractId): void
    {
        ContractAddressGuard::requireContractId($contractId, ContractAddressGuard::CONTRACT_TO_INVOKE);
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