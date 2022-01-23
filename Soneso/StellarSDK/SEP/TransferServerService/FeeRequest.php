<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

class FeeRequest
{
    /// jwt token previously received from the anchor via the SEP-10 authentication flow
    public string $jwt;

    /// Kind of operation (deposit or withdraw).
    public string $operation;

    /// (optional) Type of deposit or withdrawal (SEPA, bank_account, cash, etc...).
    public ?string $type;

    /// Asset code.
    public string $assetCode;

    /// Amount of the asset that will be deposited/withdrawn.
    public float $amount;

}