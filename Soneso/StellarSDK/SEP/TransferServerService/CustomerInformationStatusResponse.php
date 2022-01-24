<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Soneso\StellarSDK\Responses\Response;

class CustomerInformationStatusResponse extends Response
{
    /// Status of customer information processing. One of: pending, denied.
    private string $status;

    /// (optional) A URL the user can visit if they want more information about their account / status.
    private ?string $moreInfoUrl = null;

    /// (optional) Estimated number of seconds until the customer information status will update.
    private ?int $eta = null;

    /**
     * Status of customer information processing. One of: pending, denied.
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * (optional) A URL the user can visit if they want more information about their account / status.
     * @return string|null
     */
    public function getMoreInfoUrl(): ?string
    {
        return $this->moreInfoUrl;
    }

    /**
     * (optional) Estimated number of seconds until the customer information status will update.
     * @return int|null
     */
    public function getEta(): ?int
    {
        return $this->eta;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['status'])) $this->status = $json['status'];
        if (isset($json['more_info_url'])) $this->moreInfoUrl = $json['more_info_url'];
        if (isset($json['eta'])) $this->eta = $json['eta'];
    }

    public static function fromJson(array $json) : CustomerInformationStatusResponse
    {
        $result = new CustomerInformationStatusResponse();
        $result->loadFromJson($json);
        return $result;
    }
}