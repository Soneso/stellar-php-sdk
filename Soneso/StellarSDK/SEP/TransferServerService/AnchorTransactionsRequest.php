<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use DateTime;

class AnchorTransactionsRequest
{

    /// jwt token previously received from the anchor via the SEP-10 authentication flow
    public string $jwt;

    /// The code of the asset of interest. E.g. BTC, ETH, USD, INR, etc.
    public string $assetCode;

    /// The stellar account ID involved in the transactions.
    public string $account;

    /// (optional) The response should contain transactions starting on or after this date & time.
    public ?DateTime $noOlderThan = null;

    /// (optional) The response should contain at most limit transactions.
    public ?int $limit = null;

    /// (optional) The kind of transaction that is desired. Should be either deposit or withdrawal.
    public ?string $kind = null;

    /// (optional) The response should contain transactions starting prior to this ID (exclusive).
    public ?string $pagingId = null;

}