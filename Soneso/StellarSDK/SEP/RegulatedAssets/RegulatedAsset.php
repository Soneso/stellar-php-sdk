<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\RegulatedAssets;

use Soneso\StellarSDK\AssetTypeCreditAlphanum;
use Soneso\StellarSDK\Xdr\XdrAsset;

/**
 * Represents a regulated asset that requires issuer approval for transactions.
 *
 * Regulated assets implement transaction-level compliance by requiring the asset issuer to
 * approve each transaction before it can be submitted to the Stellar network. This enables
 * issuers to maintain regulatory compliance for securities, regulated financial instruments,
 * and other assets subject to jurisdictional requirements.
 *
 * Per SEP-0008 specification, regulated asset issuers must have both Authorization Required
 * and Authorization Revocable flags set on their account. This allows the issuer to grant
 * and revoke authorization to transact the asset, which is essential for the per-transaction
 * approval workflow.
 *
 * The approval workflow:
 * 1. Wallet builds and signs transaction with user's private key
 * 2. Wallet submits transaction to approval server (not to Stellar network)
 * 3. Approval server evaluates transaction against compliance criteria
 * 4. Server responds with approval (signed transaction) or rejection
 * 5. If approved, wallet submits signed transaction to Stellar network
 *
 * This class extends AssetTypeCreditAlphanum to include approval server information
 * and criteria required for the regulated assets compliance workflow.
 *
 * @package Soneso\StellarSDK\SEP\RegulatedAssets
 * @see https://github.com/stellar/stellar-protocol/blob/v1.7.4/ecosystem/sep-0008.md SEP-0008 v1.7.4 Specification
 */
class RegulatedAsset extends AssetTypeCreditAlphanum
{
    public string $approvalServer;
    public ?string $approvalCriteria = null;

    /**
     * Constructor
     * @param string $code asset code
     * @param string $issuer asset issuer
     * @param string $approvalServer approval server
     * @param string|null $approvalCriteria approval criteria
     */
    public function __construct(string $code, string $issuer, string $approvalServer, ?string $approvalCriteria = null)
    {
        $this->approvalServer = $approvalServer;
        $this->approvalCriteria = $approvalCriteria;
        parent::__construct($code, $issuer);
    }

    /**
     * Returns the asset type (credit_alphanum4 or credit_alphanum12).
     *
     * @return string Asset type identifier
     */
    public function getType(): string
    {
        return self::createNonNativeAsset($this->code,$this->issuer)->getType();
    }

    /**
     * Converts the asset to its XDR representation.
     *
     * @return XdrAsset XDR asset object
     */
    public function toXdr(): XdrAsset
    {
        return self::createNonNativeAsset($this->code,$this->issuer)->toXdr();
    }
}