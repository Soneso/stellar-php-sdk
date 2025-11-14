<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

/**
 * Represents a bump sequence operation response from Horizon API
 *
 * This operation bumps forward the sequence number of the source account, allowing it to
 * invalidate any lower sequence transactions that have not yet been included in a ledger.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org Stellar developer docs Horizon Bump Sequence
 * @since 1.0.0
 */
class BumpSequenceOperationResponse extends OperationResponse
{

    private string $bumpTo;

    /**
     * Gets the new sequence number
     *
     * @return string The new sequence number
     */
    public function getBumpTo(): string
    {
        return $this->bumpTo;
    }

    protected function loadFromJson(array $json): void
    {

        if (isset($json['bump_to'])) $this->bumpTo = $json['bump_to'];

        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData): BumpSequenceOperationResponse
    {
        $result = new BumpSequenceOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}