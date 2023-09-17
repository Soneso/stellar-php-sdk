<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

class SEP24TransactionRequest {

    /// jwt token previously received from the anchor via the SEP-10 authentication flow
    public string $jwt;

    /// (optional) The id of the transaction.
    public ?string $id = null;

    /// (optional) The stellar transaction id of the transaction.
    public ?string $stellarTransactionId = null;

    /// (optional) The external transaction id of the transaction.
    public ?string $externalTransactionId = null;

    /// (optional) Defaults to en if not specified or if the specified language is not supported.
    /// Language code specified using RFC 4646 which means it can also accept locale in the format en-US.
    public ?string $lang = null;
}