<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Toml;

/// Validator Information. From the the stellar.toml [[VALIDATORS]] list, one set of fields for each node your organization runs. Combined with the steps outlined in SEP-20, this section allows to declare the node(s), and to let others know the location of any public archives they maintain.
/// See <a href="https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0001.md" target="_blank">Stellar Toml</a>
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