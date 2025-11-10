<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

/**
 * Request parameters for updating pending transaction information via SEP-06.
 *
 * Used to update transaction details when the anchor requests additional information
 * via the pending_transaction_info_update status. The transaction's required_info_updates
 * field indicates which fields need to be provided.
 *
 * Should only be used when the anchor explicitly requests updates. Attempting to update
 * when no information is requested will result in an error response.
 *
 * @package Soneso\StellarSDK\SEP\TransferServerService
 * @see https://github.com/stellar/stellar-protocol/blob/v4.3.0/ecosystem/sep-0006.md SEP-06 Specification
 * @see https://github.com/stellar/stellar-protocol/blob/v4.3.0/ecosystem/sep-0006.md#pending-transaction-info-update
 * @see TransferServerService::patchTransaction()
 * @see AnchorTransaction
 */
class PatchTransactionRequest
{

    /**
     * @var string $id Id of the transaction
     */
    public string $id;

    /**
     * @var array<string, mixed> $fields An object containing the values requested to be updated by the anchor
     * See: https://github.com/stellar/stellar-protocol/blob/v4.3.0/ecosystem/sep-0006.md#pending-transaction-info-update
     */
    public array $fields;

    /**
     * @var string|null jwt token previously received from the anchor via the SEP-10 authentication flow
     */
    public ?string $jwt = null;

    /**
     * @param string $id Id of the transaction
     * @param array<string, mixed> $fields An object containing the values requested to be updated by the anchor
     * See: https://github.com/stellar/stellar-protocol/blob/v4.3.0/ecosystem/sep-0006.md#pending-transaction-info-update
     * @param string|null $jwt jwt token previously received from the anchor via the SEP-10 authentication flow
     */
    public function __construct(string $id, array $fields, ?string $jwt)
    {
        $this->id = $id;
        $this->fields = $fields;
        $this->jwt = $jwt;
    }

}