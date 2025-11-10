<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

/**
 * Response data indicating customer information processing status.
 *
 * Contains the status of previously submitted customer information (pending or denied),
 * estimated time until status changes, and optional URL for more information.
 *
 * This response is included in CustomerInformationStatusException when customer
 * information is being processed or was not accepted.
 *
 * @package Soneso\StellarSDK\SEP\TransferServerService
 * @see https://github.com/stellar/stellar-protocol/blob/v4.3.0/ecosystem/sep-0006.md SEP-06 Specification
 * @see https://github.com/stellar/stellar-protocol/blob/v1.15.0/ecosystem/sep-0012.md SEP-12 v1.15.0 KYC API
 * @see CustomerInformationStatusException
 */
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