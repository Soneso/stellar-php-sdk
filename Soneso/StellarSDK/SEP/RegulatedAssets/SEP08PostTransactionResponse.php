<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\RegulatedAssets;

/**
 * Abstract base class for all approval server transaction responses.
 *
 * After submitting a transaction to an approval server via POST /tx_approve, the server
 * responds with one of five possible statuses defined by SEP-0008:
 *
 * - success: Transaction approved and signed without revision (SEP08PostTransactionSuccess)
 * - revised: Transaction modified for compliance and signed (SEP08PostTransactionRevised)
 * - pending: Approval decision delayed, retry later (SEP08PostTransactionPending)
 * - action_required: User action needed before approval (SEP08PostTransactionActionRequired)
 * - rejected: Transaction cannot be made compliant (SEP08PostTransactionRejected)
 *
 * This class provides a factory method to parse JSON responses and instantiate the
 * appropriate concrete response class based on the status field. Wallets should use
 * instanceof checks to determine the response type and handle accordingly.
 *
 * HTTP Status Codes:
 * - 200: success, revised, pending, action_required
 * - 400: rejected
 *
 * @package Soneso\StellarSDK\SEP\RegulatedAssets
 * @see https://github.com/stellar/stellar-protocol/blob/v1.7.4/ecosystem/sep-0008.md#responses SEP-0008 v1.7.4 Responses
 */
abstract class SEP08PostTransactionResponse
{

    /**
     * Factory method to construct a transaction response object from JSON data.
     *
     * Parses the JSON response from an approval server and instantiates the appropriate
     * concrete response class based on the 'status' field value.
     *
     * Status Mapping:
     * - "success" -> SEP08PostTransactionSuccess
     * - "revised" -> SEP08PostTransactionRevised
     * - "pending" -> SEP08PostTransactionPending
     * - "action_required" -> SEP08PostTransactionActionRequired
     * - "rejected" -> SEP08PostTransactionRejected
     *
     * @param array<array-key, mixed> $json Decoded JSON response from approval server
     *
     * @return SEP08PostTransactionResponse Concrete subclass instance based on status field
     *
     * @throws SEP08InvalidPostTransactionResponse If status field is missing, unknown, or required
     *                                              fields for the given status are missing
     */
    public static function fromJson(array $json) : SEP08PostTransactionResponse {
        if (!isset($json['status'])) {
            throw new SEP08InvalidPostTransactionResponse("Missing status in response");
        }

        $status = $json['status'];
        if ($status === 'success') {
            $message = $json['message'] ?? null;
            if (!isset($json['tx'])) {
                throw new SEP08InvalidPostTransactionResponse("Missing tx in response");
            }
            return new SEP08PostTransactionSuccess(tx:$json['tx'], message: $message);
        } else if ($status === 'revised') {
            if (!isset($json['tx'])) {
                throw new SEP08InvalidPostTransactionResponse("Missing tx in response");
            }
            if (!isset($json['message'])) {
                throw new SEP08InvalidPostTransactionResponse("Missing message in response");
            }
            return new SEP08PostTransactionRevised(tx: $json['tx'], message: $json['message']);
        } else if ($status === 'pending') {
            $timeout = $json['timeout'] ?? null;
            $message = $json['message'] ?? null;
            return new SEP08PostTransactionPending(timeout: $timeout, message: $message);
        } else if ($status === 'rejected') {
            if (!isset($json['error'])) {
                throw new SEP08InvalidPostTransactionResponse("Missing error in response");
            }
            return new SEP08PostTransactionRejected(error: $json['error']);
        } else if ($status === 'action_required') {
            if (!isset($json['message'])) {
                throw new SEP08InvalidPostTransactionResponse("Missing message in response");
            }
            if (!isset($json['action_url'])) {
                throw new SEP08InvalidPostTransactionResponse("Missing action_url in response");
            }
            $actionMethod = $json['action_method'] ?? null;
            $actionFields = $json['action_fields'] ?? null;

            return new SEP08PostTransactionActionRequired(
                message: $json['message'],
                actionUrl: $json['action_url'],
                actionMethod: $actionMethod,
                actionFields: $actionFields,
            );
        } else {
            throw new SEP08InvalidPostTransactionResponse("Unknown status: " . $status . " in response");
        }
    }
}