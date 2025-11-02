<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

/**
 * Base class for alphanumeric credit assets issued on the Stellar network
 *
 * Credit assets are issued by a specific account and identified by both a code and
 * the issuer's account ID. This abstract class provides common functionality for
 * both 4-character and 12-character asset codes.
 *
 * @package Soneso\StellarSDK
 * @see AssetTypeCreditAlphanum4 For assets with 1-4 character codes
 * @see AssetTypeCreditAlphanum12 For assets with 5-12 character codes
 * @since 1.0.0
 */
abstract class AssetTypeCreditAlphanum extends Asset
{
    protected string $code;
    protected string $issuer;

    /**
     * Creates a credit asset with the specified code and issuer
     *
     * @param string $code The asset code
     * @param string $issuer The issuer account ID (public key starting with G)
     */
    public function __construct(string $code, string $issuer) {
        $this->code = $code;
        $this->issuer = $issuer;
    }

    /**
     * Returns the asset code
     *
     * @return string The asset code (e.g., "USD", "BTC")
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Sets the asset code
     *
     * @param string $code The new asset code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * Returns the issuer account ID
     *
     * @return string The issuer's public key (G...)
     */
    public function getIssuer(): string
    {
        return $this->issuer;
    }

    /**
     * Sets the issuer account ID
     *
     * @param string $issuer The new issuer account ID
     */
    public function setIssuer(string $issuer): void
    {
        $this->issuer = $issuer;
    }
}