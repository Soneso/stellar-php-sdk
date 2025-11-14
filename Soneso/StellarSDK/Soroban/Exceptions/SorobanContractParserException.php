<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Exceptions;

use ErrorException;

/**
 * Exception thrown when parsing Soroban smart contract WASM bytecode fails
 *
 * This exception is raised during contract bytecode parsing when required metadata sections
 * are missing or malformed. The parser expects contracts to embed metadata in WASM custom
 * sections following SEP-47 (Contract Metadata) and SEP-48 (Contract Specification) standards.
 *
 * Two specific error scenarios trigger this exception:
 *
 * 1. Missing Environment Meta Entry:
 *    The contractenvmetav0 custom section is missing or cannot be decoded from the WASM bytecode.
 *    This section contains the Soroban interface version that the contract requires.
 *
 * 2. Missing Contract Spec Entries:
 *    The contractspecv0 custom section is missing or contains no valid spec entries.
 *    This section defines function signatures, user-defined types, and event definitions.
 *
 * These errors typically indicate:
 * - Corrupted or incomplete WASM bytecode
 * - Contract compiled without proper metadata embedding
 * - Invalid contract deployment or storage
 * - Incorrect contract code retrieval
 *
 * The exception is thrown by SorobanContractParser::parseContractByteCode() and may be
 * propagated through SorobanServer methods that retrieve and parse contract information.
 *
 * @package Soneso\StellarSDK\Soroban\Exceptions
 * @see \Soneso\StellarSDK\Soroban\SorobanContractParser Parser that throws this exception
 * @see \Soneso\StellarSDK\Soroban\SorobanServer Server methods that may propagate this exception
 * @see \Soneso\StellarSDK\Soroban\SorobanContractInfo Container for parsed contract metadata
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0047.md SEP-47: Contract Metadata
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0048.md SEP-48: Contract Specification
 * @since 1.0.0
 */
class SorobanContractParserException extends ErrorException
{

}