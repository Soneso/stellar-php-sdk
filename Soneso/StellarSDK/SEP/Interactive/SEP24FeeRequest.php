<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

class SEP24FeeRequest
{
    public string $operation;
    public ?string $type = null;
    public string $assetCode;
    public float $amount;
    public ?string $jwt = null;

    /**
     * @param string $operation
     * @param string|null $type
     * @param string $assetCode
     * @param float $amount
     * @param string|null $jwt
     */
    public function __construct(string $operation, string $assetCode, float $amount,  ?string $type = null, ?string $jwt = null)
    {
        $this->operation = $operation;
        $this->type = $type;
        $this->assetCode = $assetCode;
        $this->amount = $amount;
        $this->jwt = $jwt;
    }

    /**
     * @return string
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * @param string $operation
     */
    public function setOperation(string $operation): void
    {
        $this->operation = $operation;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getAssetCode(): string
    {
        return $this->assetCode;
    }

    /**
     * @param string $assetCode
     */
    public function setAssetCode(string $assetCode): void
    {
        $this->assetCode = $assetCode;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     */
    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return string|null
     */
    public function getJwt(): ?string
    {
        return $this->jwt;
    }

    /**
     * @param string|null $jwt
     */
    public function setJwt(?string $jwt): void
    {
        $this->jwt = $jwt;
    }

}