<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\RegulatedAssets;

/**
 * Response indicating the transaction was approved and signed without revision.
 *
 * This response means the transaction was found compliant with the issuer's regulatory
 * requirements and has been signed by the issuer without modifications. The wallet should
 * now submit the signed transaction to the Stellar network.
 *
 * The returned transaction envelope contains both the original signatures from the user
 * and additional signatures from the issuer, providing the necessary authorization to
 * complete the transaction on the network.
 *
 * HTTP Status Code: 200
 *
 * @package Soneso\StellarSDK\SEP\RegulatedAssets
 * @see https://github.com/stellar/stellar-protocol/blob/v1.7.4/ecosystem/sep-0008.md#success SEP-0008 v1.7.4
 */
class SEP08PostTransactionSuccess extends SEP08PostTransactionResponse
{
    /**
     * Constructor.
     *
     * @param string $tx Transaction envelope XDR (base64 encoded). This transaction will have both
     *                   the original signature(s) from the request as well as one or multiple additional
     *                   signatures from the issuer.
     * @param string|null $message A human-readable string containing information to pass on to the user.
     */
    public function __construct(
        public string $tx,
        public ?string $message = null,
    ) {
    }

}