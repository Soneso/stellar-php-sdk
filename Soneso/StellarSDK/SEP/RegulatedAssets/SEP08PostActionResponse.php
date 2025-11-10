<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\RegulatedAssets;

/**
 * Abstract base class for POST action responses when action_required status is received.
 *
 * When an approval server responds with action_required status and action_method is POST,
 * the wallet can optionally provide the requested SEP-9 KYC/AML fields programmatically
 * to avoid requiring the user to manually enter information in a browser.
 *
 * The server responds with one of two possible results:
 *
 * - no_further_action_required: The POST was sufficient, transaction can be resubmitted (SEP08PostActionDone)
 * - follow_next_url: Further action required, user must visit next_url in browser (SEP08PostActionNextUrl)
 *
 * This class provides a factory method to parse JSON responses and instantiate the
 * appropriate concrete response class based on the result field.
 *
 * HTTP Status Code: 200 for all valid responses
 *
 * @package Soneso\StellarSDK\SEP\RegulatedAssets
 * @see https://github.com/stellar/stellar-protocol/blob/v1.7.4/ecosystem/sep-0008.md#following-the-action-url SEP-0008 v1.7.4 Action URL
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0009.md SEP-0009 Standard KYC/AML Fields
 */
abstract class SEP08PostActionResponse
{
    /**
     * Factory method to construct an action response object from JSON data.
     *
     * Parses the JSON response after posting action fields and instantiates the appropriate
     * concrete response class based on the 'result' field value.
     *
     * Result Mapping:
     * - "no_further_action_required" -> SEP08PostActionDone
     * - "follow_next_url" -> SEP08PostActionNextUrl
     *
     * @param array<array-key, mixed> $json Decoded JSON response from action URL
     *
     * @return SEP08PostActionResponse Concrete subclass instance based on result field
     *
     * @throws SEP08InvalidPostActionResponse If result field is missing, unknown, or required
     *                                         fields for the given result are missing
     */
    public static function fromJson(array $json) : SEP08PostActionResponse
    {
        if (!isset($json['result'])) {
            throw new SEP08InvalidPostActionResponse("Missing result in response");
        }
        $result = $json['result'];
        if ('no_further_action_required' === $result) {
            return new SEP08PostActionDone();
        } else if ('follow_next_url' === $result) {
            if (!isset($json['next_url'])) {
                throw new SEP08InvalidPostActionResponse("Missing next_url in response");
            }
            $message = $json['message'] ?? null;
            return new SEP08PostActionNextUrl(nextUrl: $json['next_url'], message: $message);
        } else {
            throw new SEP08InvalidPostActionResponse("Unknown result: " . $result. " in response");
        }
    }
}