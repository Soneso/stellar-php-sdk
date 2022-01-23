<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Soneso\StellarSDK\Responses\Response;

class WithdrawResponse extends Response
{
    /// The account the user should send its token back to.
    private string $accountId;

    /// (optional) Type of memo to attach to transaction, one of text, id or hash.
    private ?string $memoType;

    /// (optional) Value of memo to attach to transaction, for hash this should be base64-encoded.
    private ?string $memo;

    /// (optional) The anchor's ID for this withdrawal. The wallet will use this ID to query the /transaction endpoint to check status of the request.
    private ?string $id;

    /// (optional) Estimate of how long the withdrawal will take to credit in seconds.
    private ?int $eta;

    /// (optional) Minimum amount of an asset that a user can withdraw.
    private ?float $minAmount;

    /// (optional) Maximum amount of asset that a user can withdraw.
    private ?float $maxAmount;

    /// (optional) If there is a fee for withdraw. In units of the withdrawn asset.
    private ?float $feeFixed;

    /// (optional) If there is a percent fee for withdraw.
    private ?float $feePercent;

    /// (optional) JSON object with additional information about the withdraw process. Any additional data needed as an input for this withdraw, example: Bank Name.
    private ?array $extraInfo;

    /**
     * The account the user should send its token back to.
     * @return string
     */
    public function getAccountId(): string
    {
        return $this->accountId;
    }

    /**
     * (optional) Type of memo to attach to transaction, one of text, id or hash.
     * @return string|null
     */
    public function getMemoType(): ?string
    {
        return $this->memoType;
    }

    /**
     * (optional) Value of memo to attach to transaction, for hash this should be base64-encoded.
     * @return string|null
     */
    public function getMemo(): ?string
    {
        return $this->memo;
    }

    /**
     * (optional) The anchor's ID for this withdrawal. The wallet will use this ID to query the /transaction endpoint to check status of the request.
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * (optional) Estimate of how long the withdrawal will take to credit in seconds.
     * @return int|null
     */
    public function getEta(): ?int
    {
        return $this->eta;
    }

    /**
     * (optional) Minimum amount of an asset that a user can withdraw.
     * @return float|null
     */
    public function getMinAmount(): ?float
    {
        return $this->minAmount;
    }

    /**
     * (optional) Maximum amount of asset that a user can withdraw.
     * @return float|null
     */
    public function getMaxAmount(): ?float
    {
        return $this->maxAmount;
    }

    /**
     * (optional) If there is a fee for withdraw. In units of the withdrawn asset.
     * @return float|null
     */
    public function getFeeFixed(): ?float
    {
        return $this->feeFixed;
    }

    /**
     * (optional) If there is a percent fee for withdraw.
     * @return float|null
     */
    public function getFeePercent(): ?float
    {
        return $this->feePercent;
    }

    /**
     * (optional) JSON object with additional information about the withdraw process. Any additional data needed as an input for this withdraw, example: Bank Name.
     * @return array|null
     */
    public function getExtraInfo(): ?array
    {
        return $this->extraInfo;
    }

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
        if (isset($json['extra_info'])) $this->extraInfo = $json['extra_info'];
    }

    public static function fromJson(array $json) : WithdrawResponse
    {
        $result = new WithdrawResponse();
        $result->loadFromJson($json);
        return $result;
    }
}