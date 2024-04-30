<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\RegulatedAssets;

/**
 * This response means that the transaction was found compliant and signed without being revised.
 */
class SEP08PostTransactionSuccess extends SEP08PostTransactionResponse
{
    /**
     * @var string $tx Transaction envelope XDR, base64 encoded. This transaction will have both
     * the original signature(s) from the request as well as one or multiple additional signatures from the issuer.
     */
    public string $tx;

    /**
     * @var string|null $message (optional) A human-readable string containing information to pass on to the user.
     */
    public ?string $message = null;

    /**
     * Constructor.
     * @param string $tx Transaction envelope XDR, base64 encoded. This transaction will have both
     *  the original signature(s) from the request as well as one or multiple additional signatures from the issuer.
     * @param string|null $message (optional) A human-readable string containing information to pass on to the user.
     */
    public function __construct(string $tx, ?string $message = null)
    {
        $this->tx = $tx;
        $this->message = $message;
    }

}