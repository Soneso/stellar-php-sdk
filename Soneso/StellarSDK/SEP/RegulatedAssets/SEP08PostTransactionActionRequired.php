<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\RegulatedAssets;

/**
 * Response indicating user action is required before transaction approval.
 *
 * This response means the user must complete an action before the transaction can be approved.
 * The approval service provides a URL that facilitates the action. Upon completion, the wallet
 * resubmits the original transaction to the approval server.
 *
 * Action Method Workflow:
 * - GET (or not specified): Open action_url in browser, optionally passing action_fields as query parameters
 * - POST: Send action_fields as JSON in request body. Server responds with:
 *   - no_further_action_required: Can resubmit transaction immediately
 *   - follow_next_url: User must visit next_url in browser for additional action
 *
 * The action_fields array references SEP-9 Standard KYC/AML field names that the client
 * may already possess, allowing the server to skip collecting this information from the user.
 * Examples include: email_address, mobile_number, first_name, last_name, etc.
 *
 * HTTP Status Code: 200
 *
 * @package Soneso\StellarSDK\SEP\RegulatedAssets
 * @see https://github.com/stellar/stellar-protocol/blob/v1.7.4/ecosystem/sep-0008.md#action-required SEP-0008 v1.7.4
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0009.md SEP-0009 KYC/AML Fields
 */
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