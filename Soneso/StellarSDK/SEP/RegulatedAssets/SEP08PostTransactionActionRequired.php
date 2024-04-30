<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\RegulatedAssets;

class SEP08PostTransactionActionRequired extends SEP08PostTransactionResponse
{

    /**
     * @var string $message A human-readable string containing information regarding the action required.
     */
    public string $message;
    /**
     * @var string $actionUrl A URL that allows the user to complete the actions required to have the
     * transaction approved.
     */
    public string $actionUrl;
    /**
     * @var string $actionMethod GET or POST, indicating the type of request that should be made to the action_url.
     */
    public string $actionMethod = 'GET';
    /**
     * @var array<String>|null $actionFields (optional) An array of additional fields defined by SEP-9 Standard
     * KYC / AML fields that the client may optionally provide to the approval service when sending the request
     * to the action_url so as to circumvent the need for the user to enter the information manually.
     */
    public ?array $actionFields = null;

    /**
     * Constructor.
     * @param string $message A human-readable string containing information regarding the action required.
     * @param string $actionUrl A URL that allows the user to complete the actions required to have the
     *  transaction approved.
     * @param string $actionMethod GET or POST, indicating the type of request that should be made to the action_url.
     * @param array<String>|null $actionFields (optional) An array of additional fields defined by SEP-9 Standard
     *  KYC / AML fields that the client may optionally provide to the approval service when sending the request
     *  to the action_url so as to circumvent the need for the user to enter the information manually.
     */
    public function __construct(string $message, string $actionUrl, string $actionMethod, ?array $actionFields)
    {
        $this->message = $message;
        $this->actionUrl = $actionUrl;
        $this->actionMethod = $actionMethod;
        $this->actionFields = $actionFields;
    }

}