<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Exception;

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