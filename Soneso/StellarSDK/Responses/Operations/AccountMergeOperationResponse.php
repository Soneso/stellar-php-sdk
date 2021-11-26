<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

class AccountMergeOperationResponse extends OperationResponse
{
    private string $account;
    private ?string $accountMuxed = null;
    private ?string $accountMuxedId = null;
    private string $into;
    private ?string $intoMuxed = null;
    private ?string $intoMuxedId = null;

    /**
     * @return string
     */
    public function getAccount(): string
    {
        return $this->account;
    }

    /**
     * @return string|null
     */
    public function getAccountMuxed(): ?string
    {
        return $this->accountMuxed;
    }

    /**
     * @return string|null
     */
    public function getAccountMuxedId(): ?string
    {
        return $this->accountMuxedId;
    }

    /**
     * @return string
     */
    public function getInto(): string
    {
        return $this->into;
    }

    /**
     * @return string|null
     */
    public function getIntoMuxed(): ?string
    {
        return $this->intoMuxed;
    }

    /**
     * @return string|null
     */
    public function getIntoMuxedId(): ?string
    {
        return $this->intoMuxedId;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['account'])) $this->account = $json['account'];
        if (isset($json['account_muxed'])) $this->accountMuxed = $json['account_muxed'];
        if (isset($json['account_muxed_id'])) $this->accountMuxedId = $json['account_muxed_id'];
        if (isset($json['into'])) $this->into = $json['into'];
        if (isset($json['into_muxed'])) $this->intoMuxed = $json['into_muxed'];
        if (isset($json['into_muxed_id'])) $this->intoMuxedId = $json['into_muxed_id'];

        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : AccountMergeOperationResponse {
        $result = new AccountMergeOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}