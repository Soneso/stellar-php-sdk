<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Exception;

/**
 * Exception thrown when customer information is pending or denied.
 *
 * Indicates that customer information was previously submitted via SEP-12,
 * but it is either still being processed (pending status) or was not accepted
 * (denied status). The exception contains a response object with status details,
 * estimated time until status change, and optional URL for more information.
 *
 * Client should wait for processing to complete or provide additional/corrected
 * information as indicated by the anchor.
 *
 * @package Soneso\StellarSDK\SEP\TransferServerService
 * @see https://github.com/stellar/stellar-protocol/blob/v4.3.0/ecosystem/sep-0006.md SEP-06 Specification
 * @see https://github.com/stellar/stellar-protocol/blob/v1.15.0/ecosystem/sep-0012.md SEP-12 v1.15.0 KYC API
 * @see CustomerInformationStatusResponse
 */
class CustomerInformationStatusException extends Exception
{
    /**
     * @var CustomerInformationStatusResponse $response the response data received from the server.
     */
    public CustomerInformationStatusResponse $response;

    /**
     * Constructor.
     *
     * @param CustomerInformationStatusResponse $response the response data received from the server.
     */
    public function __construct(CustomerInformationStatusResponse $response) {
        $this->response = $response;
        $message = "Customer information was submitted for the account, but the information is either still being processed or was not accepted. Status: ". $response->status;
        if ($response->moreInfoUrl) {
            $message = $message . " more info url: " . $response->moreInfoUrl;
        }
        if ($response->eta) {
            $message = $message . " eta: " . $response->eta;
        }
        parent::__construct($message);
    }
}