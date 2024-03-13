<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Soneso\StellarSDK\Responses\Response;

class DepositResponse extends Response
{
    /**
     * @var string|null $how (deprecated, use instructions instead) Terse but complete instructions
     * for how to deposit the asset. In the case of most cryptocurrencies it is
     * just an address to which the deposit should be sent.
     */
    public ?string $how;

    /**
     * @var string|null $id (optional) The anchor's ID for this deposit. The wallet will use this ID
     * to query the /transaction endpoint to check status of the request.
     */
    public ?string $id = null;

    /**
     * @var int|null $eta (optional) Estimate of how long the deposit will take to credit in seconds.
     */
    public ?int $eta;

    /**
     * @var float|null $minAmount (optional) Minimum amount of an asset that a user can deposit.
     */
    public ?float $minAmount = null;

    /**
     * @var float|null $maxAmount (optional) Maximum amount of asset that a user can deposit.
     */
    public ?float $maxAmount = null;

    /**
     * @var float|null $feeFixed (optional) Fixed fee (if any). In units of the deposited asset.
     */
    public ?float $feeFixed = null;

    /**
     * @var float|null $feePercent (optional) Percentage fee (if any). In units of percentage points.
     */
    public ?float $feePercent = null;

    /**
     * @var ExtraInfo|null $extraInfo additional information about the deposit process.
     */
    public ?ExtraInfo $extraInfo = null;

    /**
     * @var array<string, DepositInstruction>|null
     */
    public ?array $instructions;


    /**
     * Loads the needed data from a json array.
     * @param array<array-key, mixed> $json the data array to read from.
     * @return void
     */
    protected function loadFromJson(array $json) : void {
        if (isset($json['how'])) $this->how = $json['how'];
        if (isset($json['id'])) $this->id = $json['id'];
        if (isset($json['eta'])) $this->eta = $json['eta'];
        if (isset($json['fee_fixed'])) $this->feeFixed = $json['fee_fixed'];
        if (isset($json['fee_percent'])) $this->feePercent = $json['fee_percent'];
        if (isset($json['min_amount'])) $this->minAmount = $json['min_amount'];
        if (isset($json['max_amount'])) $this->maxAmount = $json['max_amount'];
        if (isset($json['extra_info'])) $this->extraInfo = ExtraInfo::fromJson($json['extra_info']);
        if (isset($json['instructions'])) {
            $this->instructions = array();
            $jsonFields = $json['instructions'];
            foreach(array_keys($jsonFields) as $key) {
                $value = DepositInstruction::fromJson($jsonFields[$key]);
                $this->instructions += [$key => $value];
            }
        }
    }

    /**
     * Constructs a new instance of DepositResponse by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return DepositResponse the object containing the parsed data.
     */
    public static function fromJson(array $json) : DepositResponse
    {
        $result = new DepositResponse();
        $result->loadFromJson($json);
        return $result;
    }
}