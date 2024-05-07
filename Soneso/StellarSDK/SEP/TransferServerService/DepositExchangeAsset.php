<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

class DepositExchangeAsset
{
    /**
     * @var bool $enabled true if SEP-6 deposit exchange for this asset is supported
     */
    public bool $enabled;
    /**
     * @var bool|null $authenticationRequired Optional. true if client must be authenticated before accessing the
     * deposit endpoint for this asset. false if not specified.
     */
    public ?bool $authenticationRequired = null;

    /**
     * @var float|null $feeFixed Optional fixed (flat) fee for deposit, in units of the Stellar asset.
     * Null if there is no fee or the fee schedule is complex.
     */
    public ?float $feeFixed = null;

    /**
     * @var float|null $feePercent Optional percentage fee for deposit, in percentage points of the Stellar
     * asset. Null if there is no fee or the fee schedule is complex.
     */
    public ?float $feePercent = null;

    /**
     * @var float|null $minAmount Optional minimum amount. No limit if not specified.
     */
    public ?float $minAmount = null;

    /**
     * @var float|null $maxAmount Optional maximum amount. No limit if not specified.
     */
    public ?float $maxAmount = null;

    /**
     * @var array<string, AnchorField>|null $fields (Deprecated) Accepting personally identifiable information through
     * request parameters is a security risk due to web server request logging.
     * KYC information should be supplied to the Anchor via SEP-12).
     */
    public ?array $fields = null;

    /**
     * @param bool $enabled true if SEP-6 deposit exchange for this asset is supported
     */
    public function __construct(bool $enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * Constructs a new instance of DepositAsset by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return DepositExchangeAsset the object containing the parsed data.
     */
    public static function fromJson(array $json) : DepositExchangeAsset
    {
        $enabled = false;
        if (isset($json['enabled'])) $enabled = $json['enabled'];

        $result = new DepositExchangeAsset($enabled);

        if (isset($json['authentication_required'])) $result->authenticationRequired = $json['authentication_required'];
        if (isset($json['fee_fixed'])) $result->feeFixed = $json['fee_fixed'];
        if (isset($json['fee_percent'])) $result->feePercent = $json['fee_percent'];
        if (isset($json['min_amount'])) $result->minAmount = $json['min_amount'];
        if (isset($json['max_amount'])) $result->maxAmount = $json['max_amount'];
        if (isset($json['fields'])) {
            $result->fields = array();
            $jsonFields = $json['fields'];
            foreach(array_keys($jsonFields) as $key) {
                $value = AnchorField::fromJson($jsonFields[$key]);
                $result->fields += [$key => $value];
            }
        }
        return $result;
    }
}