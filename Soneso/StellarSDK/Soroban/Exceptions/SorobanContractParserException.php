<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Exceptions;

use ErrorException;

/**
 * Exception thrown when parsing Soroban smart contract WASM bytecode fails
 *
 * The parser checks the WASM header and section framing. It decodes every entry of
 * the first contractenvmetav0 section, the first contractspecv0 section and every
 * contractmetav0 section; entries of later environment meta and spec sections are not read.
 *
 * This exception is thrown if the header or section framing is invalid, contractenvmetav0
 * or contractspecv0 is missing, the first contractenvmetav0 section holds no entry, or an
 * entry being read does not decode. Framing and entry errors name the byte offset in the
 * byte code. A present, empty contractspecv0 section is valid.
 *
 * SorobanContractParser::parseContractByteCode() throws this exception; SorobanServer
 * methods that load contract information, SorobanClient::forClientOptions, SorobanClient::deploy and
 * SorobanClient::deployFromExternalRef let it propagate.
 *
 * @package Soneso\StellarSDK\Soroban\Exceptions
 * @see \Soneso\StellarSDK\Soroban\SorobanContractParser Parser that throws this exception
 * @see \Soneso\StellarSDK\Soroban\SorobanServer Server methods that may propagate this exception
 * @see \Soneso\StellarSDK\Soroban\SorobanContractInfo Container for parsed contract metadata
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0046.md SEP-46: Contract Meta
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0048.md SEP-48: Contract Interface Specification
 * @since 1.0.0
 */
class SorobanContractParserException extends ErrorException
{

}