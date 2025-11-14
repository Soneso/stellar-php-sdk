<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

/**
 * Represents a restore footprint operation response from Horizon API
 *
 * This Soroban operation restores archived smart contract data entries back into the active ledger.
 * When contract data entries expire, they are archived and become inaccessible. This operation
 * unarchives entries specified in the read-write footprint, making them available again for contract
 * execution. The restored entries must have their TTL extended to prevent immediate re-archival.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org Stellar developer docs Horizon Restore Footprint Operation
 */
class RestoreFootprintOperationResponse extends OperationResponse
{

    public static function fromJson(array $jsonData) : RestoreFootprintOperationResponse {
        $result = new RestoreFootprintOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}