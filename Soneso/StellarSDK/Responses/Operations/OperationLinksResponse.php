<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Responses\Link\LinkResponse;

/**
 * Hypermedia links for operation resources
 *
 * Provides HAL-style links to related resources for an operation, including the operation
 * itself, its effects, the parent transaction, and adjacent operations in the ledger sequence.
 * These links enable navigation through the operation history and related data.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 */
class OperationLinksResponse
{
    private LinkResponse $self;
    private LinkResponse $effects;
    private LinkResponse $transaction;
    private LinkResponse $precedes;
    private LinkResponse $succeeds;

    /**
     * Gets the link to this operation resource
     *
     * @return LinkResponse Link to the operation details
     */
    public function getSelf(): LinkResponse
    {
        return $this->self;
    }

    /**
     * Gets the link to the operation's effects
     *
     * @return LinkResponse Link to effects caused by this operation
     */
    public function getEffects(): LinkResponse
    {
        return $this->effects;
    }

    /**
     * Gets the link to the parent transaction
     *
     * @return LinkResponse Link to the transaction containing this operation
     */
    public function getTransaction(): LinkResponse
    {
        return $this->transaction;
    }

    /**
     * Gets the link to the next operation in sequence
     *
     * @return LinkResponse Link to the chronologically following operation
     */
    public function getPrecedes(): LinkResponse
    {
        return $this->precedes;
    }

    /**
     * Gets the link to the previous operation in sequence
     *
     * @return LinkResponse Link to the chronologically preceding operation
     */
    public function getSucceeds(): LinkResponse
    {
        return $this->succeeds;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['self'])) $this->self = LinkResponse::fromJson($json['self']);
        if (isset($json['effects'])) $this->effects = LinkResponse::fromJson($json['effects']);
        if (isset($json['transaction'])) $this->transaction = LinkResponse::fromJson($json['transaction']);
        if (isset($json['precedes'])) $this->precedes = LinkResponse::fromJson($json['precedes']);
        if (isset($json['succeeds'])) $this->succeeds = LinkResponse::fromJson($json['succeeds']);
    }

    public static function fromJson(array $json) : OperationLinksResponse {
        $result = new OperationLinksResponse();
        $result->loadFromJson($json);
        return $result;
    }
}