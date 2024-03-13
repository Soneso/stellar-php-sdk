<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Soneso\StellarSDK\Responses\Response;

class WithdrawResponse extends Response
{
    /**
     * @var string|null $accountId (optional) The account the user should send its token back to.
     * This field can be omitted if the anchor cannot provide this information
     * at the time of the request.
     */
    public ?string $accountId = null;

    /**
     * @var string|null $memoType (optional) Type of memo to attach to transaction, one of text, id or hash.
     */
    public ?string $memoType = null;

    /**
     * @var string|null $memo (optional) Value of memo to attach to transaction, for hash this should
     * be base64-encoded. The anchor should use this memo to match the Stellar
     * transaction with the database entry associated created to represent it.
     */
    public ?string $memo = null;

    /**
     * @var string|null $id (optional) The anchor's ID for this withdrawal. The wallet will use this
     * ID to query the /transaction endpoint to check status of the request.
     */
    public ?string $id = null;

    /**
     * @var int|null $eta (optional) Estimate of how long the withdrawal will take to credit
     * in seconds.
     */
    public ?int $eta = null;

    /**
     * @var float|null (optional) Minimum amount of an asset that a user can withdraw.
     */
    public ?float $minAmount = null;

    /**
     * @var float|null $maxAmount (optional) Maximum amount of asset that a user can withdraw.
     */
    public ?float $maxAmount = null;

    /**
     * @var float|null $feeFixed (optional) If there is a fee for withdraw. In units of the withdrawn asset
     */
    public ?float $feeFixed = null;

    /**
     * @var float|null $feePercent (optional) If there is a percent fee for withdraw.
     */
    public ?float $feePercent = null;

    /**
     * @var ExtraInfo|null $extraInfo (optional) Any additional data needed as an input for this withdraw,
     * example: Bank Name.
     */
    public ?ExtraInfo $extraInfo = null;

    /**
     * Loads the needed data from a json array.
     * @param array<array-key, mixed> $json the data array to read from.
     * @return void
     */
    protected function loadFromJson(array $json) : void {
        if (isset($json['account_id'])) $this->accountId = $json['account_id'];
        if (isset($json['memo_type'])) $this->memoType = $json['memo_type'];
        if (isset($json['memo'])) $this->memo = $json['memo'];
        if (isset($json['id'])) $this->id = $json['id'];
        if (isset($json['eta'])) $this->eta = $json['eta'];
        if (isset($json['min_amount'])) $this->minAmount = $json['min_amount'];
        if (isset($json['max_amount'])) $this->maxAmount = $json['max_amount'];
        if (isset($json['fee_fixed'])) $this->feeFixed = $json['fee_fixed'];
        if (isset($json['fee_percent'])) $this->feePercent = $json['fee_percent'];
        if (isset($json['extra_info'])) $this->extraInfo = ExtraInfo::fromJson($json['extra_info']);
    }

    /**
     * Constructs a new instance of WithdrawResponse by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return WithdrawResponse the object containing the parsed data.
     */
    public static function fromJson(array $json) : WithdrawResponse
    {
        $result = new WithdrawResponse();
        $result->loadFromJson($json);
        return $result;
    }
}