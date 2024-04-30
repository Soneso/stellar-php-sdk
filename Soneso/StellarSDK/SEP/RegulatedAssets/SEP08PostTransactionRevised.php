<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\RegulatedAssets;

/**
 * This response means that the transaction was revised to be made compliant.
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