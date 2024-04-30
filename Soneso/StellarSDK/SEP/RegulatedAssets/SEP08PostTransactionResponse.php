<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\RegulatedAssets;

abstract class SEP08PostTransactionResponse
{

    /**
     * Constructs a new SEP08PostTransactionResponse object from the given data array.
     * @param array<array-key, mixed> $json the data array to extract the needed values from.
     * @return SEP08PostTransactionResponse the response
     * @throws SEP08InvalidPostTransactionResponse
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