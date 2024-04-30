<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\RegulatedAssets;

use Soneso\StellarSDK\AssetTypeCreditAlphanum;
use Soneso\StellarSDK\Xdr\XdrAsset;

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


    public function getType(): string
    {
        return self::createNonNativeAsset($this->code,$this->issuer)->getType();
    }

    public function toXdr(): XdrAsset
    {
        return self::createNonNativeAsset($this->code,$this->issuer)->toXdr();
    }
}