<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Exception;

class CustomerInformationNeededException extends Exception
{
    private CustomerInformationNeededResponse $response;

    public function __construct(CustomerInformationNeededResponse $response)
    {
        $this->response = $response;
        $message = "The anchor needs more information about the customer and all the information can be received non-interactively via SEP-12. Fields: " . implode(", ", $response->getFields());
        parent::__construct($message);
    }

    /**
     * @return CustomerInformationNeededResponse
     */
    public function getResponse(): CustomerInformationNeededResponse
    {
        return $this->response;
    }
}