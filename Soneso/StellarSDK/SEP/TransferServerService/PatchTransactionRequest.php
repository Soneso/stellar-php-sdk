<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

class PatchTransactionRequest
{

    /**
     * @var string $id Id of the transaction
     */
    public string $id;

    /**
     * @var array<string, mixed> $fields An object containing the values requested to be updated by the anchor
     * See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md#pending-transaction-info-update
     */
    public array $fields;

    /**
     * @var string|null jwt token previously received from the anchor via the SEP-10 authentication flow
     */
    public ?string $jwt = null;

    /**
     * @param string $id Id of the transaction
     * @param array<string, mixed> $fields An object containing the values requested to be updated by the anchor
     * See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md#pending-transaction-info-update
     * @param string|null $jwt jwt token previously received from the anchor via the SEP-10 authentication flow
     */
    public function __construct(string $id, array $fields, ?string $jwt)
    {
        $this->id = $id;
        $this->fields = $fields;
        $this->jwt = $jwt;
    }

}