<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Exception;

/**
 * Exception thrown when anchor needs customer information via SEP-12.
 *
 * Indicates that the anchor requires additional customer information before
 * processing the deposit or withdrawal. The information can be provided
 * non-interactively through SEP-12 KYC API. The exception contains a response
 * object listing the required fields.
 *
 * Client should submit the requested information via SEP-12 and then retry
 * the deposit/withdrawal operation.
 *
 * @package Soneso\StellarSDK\SEP\TransferServerService
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md SEP-06 Specification
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md SEP-12 KYC API
 * @see CustomerInformationNeededResponse
 */
class CustomerInformationNeededException extends Exception
{
    /**
     * @var CustomerInformationNeededResponse $response the response data received from the server.
     */
    public CustomerInformationNeededResponse $response;

    /**
     * Constructor.
     * @param CustomerInformationNeededResponse $response the response data received from the server.
     */
    public function __construct(CustomerInformationNeededResponse $response)
    {
        $this->response = $response;
        $message = "The anchor needs more information about the customer and all the information can be received non-interactively via SEP-12. Fields: " . implode(", ", $response->fields);
        parent::__construct($message);
    }
}