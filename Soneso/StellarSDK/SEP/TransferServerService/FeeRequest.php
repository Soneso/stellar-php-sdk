<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

class FeeRequest
{
    /**
     * @var string $operation Kind of operation (deposit or withdraw).
     */
    public string $operation;

    /**
     * @var string $assetCode Stellar asset code.
     */
    public string $assetCode;

    /**
     * @var float $amount Amount of the asset that will be deposited/withdrawn.
     */
    public float $amount;

    /**
     * @var string|null $type (optional) Type of deposit or withdrawal (SEPA, bank_account, cash, etc...).
     */
    public ?string $type = null;

    /**
     * @var string|null $jwt jwt previously received from the anchor via the SEP-10 authentication flow
     */
    public ?string $jwt = null;

    /**
     * @param string $operation Kind of operation (deposit or withdraw).
     * @param string $assetCode Stellar asset code.
     * @param float $amount Amount of the asset that will be deposited/withdrawn.
     * @param string|null $type (optional) Type of deposit or withdrawal (SEPA, bank_account, cash, etc...).
     * @param string|null $jwt jwt previously received from the anchor via the SEP-10 authentication flow
     */
    public function __construct(
        string $operation,
        string $assetCode,
        float $amount,
        ?string $type = null,
        ?string $jwt = null
    )
    {
        $this->operation = $operation;
        $this->assetCode = $assetCode;
        $this->amount = $amount;
        $this->type = $type;
        $this->jwt = $jwt;
    }


}