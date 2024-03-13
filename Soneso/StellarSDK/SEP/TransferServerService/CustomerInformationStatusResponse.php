<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

class CustomerInformationStatusResponse
{
    /**
     * @var string $status Status of customer information processing. One of: pending, denied.
     */
    public string $status;

    /**
     * @var string|null $moreInfoUrl (optional) A URL the user can visit if they want more information
     * about their account / status.
     */
    public ?string $moreInfoUrl = null;

    /**
     * @var int|null $eta (optional) Estimated number of seconds until the customer information
     * status will update.
     */
    public ?int $eta = null;

    /**
     * @param string $status Status of customer information processing. One of: pending, denied.
     * @param string|null $moreInfoUrl (optional) A URL the user can visit if they want more information
     * @param int|null $eta (optional) Estimated number of seconds until the customer information
     *  status will update.
     */
    public function __construct(string $status, ?string $moreInfoUrl = null, ?int $eta = null)
    {
        $this->status = $status;
        $this->moreInfoUrl = $moreInfoUrl;
        $this->eta = $eta;
    }

    /**
     * Constructs a new instance of CustomerInformationStatusResponse by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return CustomerInformationStatusResponse the object containing the parsed data.
     */
    public static function fromJson(array $json) : CustomerInformationStatusResponse
    {
        $result = new CustomerInformationStatusResponse($json['status']);
        if (isset($json['more_info_url'])) $result->moreInfoUrl = $json['more_info_url'];
        if (isset($json['eta'])) $result->eta = $json['eta'];
        return $result;
    }
}