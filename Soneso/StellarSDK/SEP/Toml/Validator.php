<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Toml;

/**
 * Validator information from the stellar.toml [[VALIDATORS]] list.
 *
 * Contains fields for each node the organization runs. Combined with SEP-20,
 * this section allows declaration of node(s) and public archive locations.
 *
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0001.md SEP-1 Validator Information
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0020.md SEP-20 Self-verification of validator nodes
 */
class Validator
{
    /// A name for display in stellar-core configs that conforms to ^[a-z0-9-]{2,16}$.
    public ?string $alias = null;

    /// A human-readable name for display in quorum explorers and other interfaces.
    public ?string $displayName = null;

    /// The Stellar account associated with the node.
    public ?string $publicKey = null;

    /// The IP:port or domain:port peers can use to connect to the node.
    public ?string $host = null;

    /// The location of the history archive published by this validator.
    public ?string $history = null;
}