<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file

namespace Soneso\StellarSDK\SEP\RegulatedAssets;

use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostActionResponse;

/**
 * Response indicating further action is required via browser interaction.
 *
 * This response means the POST action request was processed but additional user action
 * is still required. The wallet must open the provided next_url in a browser for the
 * user to complete the necessary steps (e.g., KYC form, document upload, identity verification).
 *
 * The next_url typically includes pre-filled information from the POST action request,
 * reducing the amount of data the user needs to enter manually.
 *
 * Workflow after receiving this response:
 * 1. Open next_url in system browser or in-app browser
 * 2. Wait for user to complete the action
 * 3. Resubmit the original transaction to the approval server
 *
 * This indicates that programmatic submission of action fields was not sufficient and
 * manual user interaction is required to complete the compliance workflow.
 *
 * HTTP Status Code: 200
 *
 * @package Soneso\StellarSDK\SEP\RegulatedAssets
 * @see https://github.com/stellar/stellar-protocol/blob/v1.7.4/ecosystem/sep-0008.md#following-the-action-url SEP-0008 v1.7.4
 */
class SEP08PostActionNextUrl extends SEP08PostActionResponse
{
    /**
     * @var string A URL where the user can complete the required actions with all the
     * parameters included in the original POST pre-filled or already accepted.
     */
    public string $nextUrl;
    /**
     * @var string|null (optional) A human-readable string containing information
     * regarding the further action required.
     */
    public ?string $message = null;

    /**
     * Constructor
     * @param string $nextUrl A URL where the user can complete the required actions with all the
     *  parameters included in the original POST pre-filled or already accepted.
     * @param string|null $message (optional) A human-readable string containing information
     *  regarding the further action required.
     */
    public function __construct(string $nextUrl, ?string $message)
    {
        $this->nextUrl = $nextUrl;
        $this->message = $message;
    }

}