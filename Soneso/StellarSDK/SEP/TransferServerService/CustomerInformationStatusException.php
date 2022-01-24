<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Exception;

class CustomerInformationStatusException extends Exception
{
    private CustomerInformationStatusResponse $response;

    public function __construct(CustomerInformationStatusResponse $response) {
        $this->response = $response;
        $message = "Customer information was submitted for the account, but the information is either still being processed or was not accepted. Status: ". $response->getStatus();
        if ($response->getMoreInfoUrl()) {
            $message = $message . " more info url: " . $response->getMoreInfoUrl();
        }
        if ($response->getEta()) {
            $message = $message . " eta: " . strval($response->getEta());
        }
        parent::__construct($message);
    }

    /**
     * @return CustomerInformationStatusResponse
     */
    public function getResponse(): CustomerInformationStatusResponse {
        return $this->response;
    }
}