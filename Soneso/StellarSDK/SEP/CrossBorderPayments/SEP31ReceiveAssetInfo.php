<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\CrossBorderPayments;

/**
 * Holds info about an Asset that the Receiving Anchor supports receiving from the Sending Anchor.
 */
class SEP31ReceiveAssetInfo
{
    /**
     * @var SEP12TypesInfo $sep12Info an object containing the description of required KYC fields.
     */
    public SEP12TypesInfo $sep12Info;

    /**
     * @var float|null $minAmount (optional) Minimum amount. No limit if not specified.
     */
    public ?float $minAmount = null;

    /**
     * @var float|null $maxAmount (optional) Maximum amount. No limit if not specified.
     */
    public ?float $maxAmount = null;

    /**
     * @var float|null $feeFixed (optional) A fixed fee in units of the Stellar asset.
     * Left blank if there is no fee or fee calculation cannot be modeled using a fixed and percentage fee.
     */
    public ?float $feeFixed = null;

    /**
     * @var float|null $feePercent (optional) A percentage fee in percentage points.
     * Left blank if there is no fee or fee calculation cannot be modeled using a fixed and percentage fee.
     */
    public ?float $feePercent = null;

    /**
     * @var string|null $senderSep12Type (deprecated, optional) The value of the type parameter the Sending Anchor
     * should use for a SEP-12 GET /customer request. This field can be omitted if no KYC is necessary.
     * Use a value from sep12.sender.types instead if any are present.
     */
    public ?string $senderSep12Type = null;

    /**
     * @var string|null $receiverSep12Type (deprecated, optional) The value of the type parameter the Sending Anchor
     * should use for a SEP-12 GET /customer request. This field can be omitted if no KYC is necessary.
     * Use a values from sep12.receiver.types instead if any are present.
     */
    public ?string $receiverSep12Type = null;

    /**
     * @var array<array-key, mixed> |null (deprecated, optional) An object containing the per-transaction parameters
     * required in POST /transactions requests. Pass SEP-9 fields via SEP-12 PUT /customer instead.
     */
    public ?array $fields = null;

    /**
     * @var bool|null $quotesSupported (optional) If true, the Receiving Anchor can deliver the off-chain assets
     * listed in the SEP-38 GET /prices response in exchange for receiving the Stellar asset.
     */
    public ?bool $quotesSupported = null;

    /**
     * @var bool|null $quotesRequired (optional) If true, the Receiving Anchor can only deliver an off-chain asset
     * listed in the SEP-38 GET /prices response in exchange for receiving the Stellar asset.
     */
    public ?bool $quotesRequired = null;

    /**
     * @param SEP12TypesInfo $sep12Info an object containing the description of required KYC fields.
     * @param float|null $minAmount (optional) Minimum amount. No limit if not specified.
     * @param float|null $maxAmount (optional) Maximum amount. No limit if not specified.
     * @param float|null $feeFixed (optional) A fixed fee in units of the Stellar asset. Left blank if there
     * is no fee or fee calculation cannot be modeled using a fixed and percentage fee.
     * @param float|null $feePercent (optional) A percentage fee in percentage points. Left blank if there
     * is no fee or fee calculation cannot be modeled using a fixed and percentage fee.
     * @param string|null $senderSep12Type (deprecated, optional) The value of the type parameter the Sending Anchor
     * should use for a SEP-12 GET /customer request. This field can be omitted if no KYC is necessary.
     * Use a value from sep12.sender.types instead if any are present.
     * @param string|null $receiverSep12Type (deprecated, optional) The value of the type parameter the Sending Anchor
     * should use for a SEP-12 GET /customer request. This field can be omitted if no KYC is necessary.
     * Use a values from sep12.receiver.types instead if any are present.
     * @param array<array-key, mixed>|null $fields (deprecated, optional) An object containing the per-transaction parameters
     * required in POST /transactions requests. Pass SEP-9 fields via SEP-12 PUT /customer instead.
     * @param bool|null $quotesSupported (optional) If true, the Receiving Anchor can deliver the off-chain assets
     * listed in the SEP-38 GET /prices response in exchange for receiving the Stellar asset.
     * @param bool|null $quotesRequired (optional) If true, the Receiving Anchor can only deliver an off-chain asset
     * listed in the SEP-38 GET /prices response in exchange for receiving the Stellar asset.
     */
    public function __construct(
        SEP12TypesInfo $sep12Info,
        ?float $minAmount = null,
        ?float $maxAmount = null,
        ?float $feeFixed = null,
        ?float $feePercent = null,
        ?string $senderSep12Type = null,
        ?string $receiverSep12Type = null,
        ?array $fields = null,
        ?bool $quotesSupported = null,
        ?bool $quotesRequired = null
    )
    {
        $this->sep12Info = $sep12Info;
        $this->minAmount = $minAmount;
        $this->maxAmount = $maxAmount;
        $this->feeFixed = $feeFixed;
        $this->feePercent = $feePercent;
        $this->quotesSupported = $quotesSupported;
        $this->quotesRequired = $quotesRequired;
    }

    /**
     * Constructs a new instance of SEP31ReceiveAssetInfo by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP31ReceiveAssetInfo the object containing the parsed data.
     */
    public static function fromJson(array $json) : SEP31ReceiveAssetInfo
    {
        $sep12Info = SEP12TypesInfo::fromJson($json['sep12']);
        $result = new SEP31ReceiveAssetInfo($sep12Info);
        if (isset($json['fee_fixed'])) $result->feeFixed = $json['fee_fixed'];
        if (isset($json['fee_percent'])) $result->feePercent = $json['fee_percent'];
        if (isset($json['min_amount'])) $result->minAmount = $json['min_amount'];
        if (isset($json['max_amount'])) $result->maxAmount = $json['max_amount'];
        if (isset($json['sender_sep12_type'])) $result->senderSep12Type = $json['sender_sep12_type'];
        if (isset($json['receiver_sep12_type'])) $result->receiverSep12Type = $json['receiver_sep12_type'];
        if (isset($json['fields'])) $result->fields = $json['fields'];
        if (isset($json['quotes_supported'])) $result->quotesSupported = $json['quotes_supported'];
        if (isset($json['quotes_required'])) $result->quotesRequired = $json['quotes_required'];

        return $result;

    }
}