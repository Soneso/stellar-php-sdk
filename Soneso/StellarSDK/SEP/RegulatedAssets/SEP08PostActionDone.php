<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file

namespace Soneso\StellarSDK\SEP\RegulatedAssets;

use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostActionResponse;

/**
 * Response indicating no further action is required after posting action fields.
 *
 * This response means the approval server received sufficient information from the
 * POST action request and the wallet can now resubmit the original transaction to
 * the approval server for final evaluation.
 *
 * Workflow after receiving this response:
 * 1. Call postTransaction() again with the original transaction
 * 2. Handle the new response (likely success or revised)
 *
 * This represents the successful completion of the action_required workflow when
 * the wallet was able to provide the requested SEP-9 fields programmatically.
 *
 * HTTP Status Code: 200
 *
 * @package Soneso\StellarSDK\SEP\RegulatedAssets
 * @see https://github.com/stellar/stellar-protocol/blob/v1.7.4/ecosystem/sep-0008.md#following-the-action-url SEP-0008 v1.7.4
 */
class SEP08PostActionDone extends SEP08PostActionResponse
{

}