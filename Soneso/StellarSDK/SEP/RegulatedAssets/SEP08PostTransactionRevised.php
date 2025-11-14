<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\RegulatedAssets;

/**
 * Response indicating the transaction was modified for compliance and signed.
 *
 * This response means the original transaction was not compliant but has been revised by the
 * approval server to meet regulatory requirements. The revised transaction has been signed
 * by the issuer.
 *
 * The wallet MUST inspect the revised transaction carefully to understand what changes were
 * made before submitting it to the Stellar network. Common revisions include:
 * - Adding operations (e.g., fee payments, compliance operations)
 * - Modifying amounts to comply with velocity limits
 * - Changing destination accounts
 *
 * Security Warning: Always verify that revised transactions do not contain unexpected or
 * malicious modifications before submitting to the network.
 *
 * HTTP Status Code: 200
 *
 * @package Soneso\StellarSDK\SEP\RegulatedAssets
 * @see https://github.com/stellar/stellar-protocol/blob/v1.7.4/ecosystem/sep-0008.md#revised SEP-0008 v1.7.4
 */
class SEP08PostTransactionRevised extends SEP08PostTransactionResponse
{
    /**
     * @var string $tx Transaction envelope XDR, base64 encoded. This transaction is a revised
     * compliant version of the original request transaction, signed by the issuer.
     */
    public string $tx;

    /**
     * @var string $message A human-readable string explaining the modifications made to the
     * transaction to make it compliant.
     */
    public string $message;

    /**
     * Constructor.
     * @param string $tx Transaction envelope XDR, base64 encoded. This transaction is a revised
     *  compliant version of the original request transaction, signed by the issuer.
     * @param string $message A human-readable string explaining the modifications made to the
     *  transaction to make it compliant.
     */
    public function __construct(string $tx, string $message)
    {
        $this->tx = $tx;
        $this->message = $message;
    }

}