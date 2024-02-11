<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

class SEP24FeeRequest
{
    /**
     * @var string $operation Kind of operation ('deposit' or 'withdraw').
     */
    public string $operation;

    /**
     * @var string $assetCode the asset code of the asset to request the operation fee for.
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
     * @var string|null $jwt (optional) the jwt token obtained from sep-10 authentication.
     */
    public ?string $jwt = null;


    /**
     * Constructor.
     * @param string $operation Kind of operation ('deposit' or 'withdraw').
     * @param string $assetCode The asset code of the asset to request the operation fee for.
     * @param float $amount Amount of the asset that will be deposited/withdrawn.
     * @param string|null $type (optional) Type of deposit or withdrawal (SEPA, bank_account, cash, etc...).
     * @param string|null $jwt (optional) the jwt token obtained from sep-10 authentication.
     */
    public function __construct(
        string $operation,
        string $assetCode,
        float $amount,
        ?string $type = null,
        ?string $jwt = null)
    {
        $this->operation = $operation;
        $this->type = $type;
        $this->assetCode = $assetCode;
        $this->amount = $amount;
        $this->jwt = $jwt;
    }

    /**
     * @return string Kind of operation ('deposit' or 'withdraw').
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * @param string $operation Kind of operation ('deposit' or 'withdraw').
     */
    public function setOperation(string $operation): void
    {
        $this->operation = $operation;
    }

    /**
     * @return string|null (optional) Type of deposit or withdrawal (SEPA, bank_account, cash, etc...).
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type (optional) Type of deposit or withdrawal (SEPA, bank_account, cash, etc...).
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string The asset code of the asset to request the operation fee for.
     */
    public function getAssetCode(): string
    {
        return $this->assetCode;
    }

    /**
     * @param string $assetCode The asset code of the asset to request the operation fee for.
     */
    public function setAssetCode(string $assetCode): void
    {
        $this->assetCode = $assetCode;
    }

    /**
     * @return float Amount of the asset that will be deposited/withdrawn.
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @param float $amount Amount of the asset that will be deposited/withdrawn.
     */
    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return string|null (optional) the jwt token obtained from sep-10 authentication.
     */
    public function getJwt(): ?string
    {
        return $this->jwt;
    }

    /**
     * @param string|null $jwt (optional) the jwt token obtained from sep-10 authentication.
     */
    public function setJwt(?string $jwt): void
    {
        $this->jwt = $jwt;
    }

}