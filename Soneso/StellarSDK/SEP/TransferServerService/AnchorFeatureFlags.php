<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

class AnchorFeatureFlags {

    /**
     * @var bool Whether the anchor supports creating accounts for users requesting
     * deposits. Defaults to true.
     */
    public bool $accountCreation = true;

    /**
     * @var bool Whether the anchor supports sending deposit funds as claimable
     * balances. This is relevant for users of Stellar accounts without a
     * trustline to the requested asset. Defaults to false.
     */
    public bool $claimableBalances = false;

    /**
     * Constructs a new instance of AnchorTransactionInfo by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return AnchorFeatureFlags the object containing the parsed data.
     */
    public static function fromJson(array $json) : AnchorFeatureFlags
    {
        $result = new AnchorFeatureFlags();
        if (isset($json['account_creation'])) $result->accountCreation = $json['account_creation'];
        if (isset($json['claimable_balances'])) $result->claimableBalances = $json['claimable_balances'];
        return $result;
    }
}