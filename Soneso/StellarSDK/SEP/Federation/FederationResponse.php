<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Federation;

use Soneso\StellarSDK\Responses\Response;

class FederationResponse extends Response
{
    private ?string $stellarAddress = null;
    private ?string $accountId = null;
    private ?string $memoType = null;
    private ?string $memo = null;

    /**
     * @return string|null
     */
    public function getStellarAddress(): ?string
    {
        return $this->stellarAddress;
    }

    /**
     * @param string|null $stellarAddress
     */
    public function setStellarAddress(?string $stellarAddress): void
    {
        $this->stellarAddress = $stellarAddress;
    }

    /**
     * @return string|null
     */
    public function getAccountId(): ?string
    {
        return $this->accountId;
    }

    /**
     * @param string|null $accountId
     */
    public function setAccountId(?string $accountId): void
    {
        $this->accountId = $accountId;
    }

    /**
     * @return string|null
     */
    public function getMemoType(): ?string
    {
        return $this->memoType;
    }

    /**
     * @param string|null $memoType
     */
    public function setMemoType(?string $memoType): void
    {
        $this->memoType = $memoType;
    }

    /**
     * @return string|null
     */
    public function getMemo(): ?string
    {
        return $this->memo;
    }

    /**
     * @param string|null $memo
     */
    public function setMemo(?string $memo): void
    {
        $this->memo = $memo;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['stellar_address'])) $this->stellarAddress = $json['stellar_address'];
        if (isset($json['account_id'])) $this->accountId = $json['account_id'];
        if (isset($json['memo_type'])) $this->memoType = $json['memo_type'];
        if (isset($json['memo'])) $this->memo = $json['memo'];
    }

    public static function fromJson(array $json) : FederationResponse
    {
        $result = new FederationResponse();
        $result->loadFromJson($json);
        return $result;
    }
}