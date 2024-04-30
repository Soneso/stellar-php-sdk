<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\SEP\RegulatedAssets;

/**
 * This response means that the transaction is not compliant and could not be revised to be made compliant.
 */
class SEP08PostTransactionRejected extends SEP08PostTransactionResponse
{

    /**
     * @var string $error A human-readable string explaining why the transaction is not
     * compliant and could not be made compliant.
     */
    public string $error;

    /**
     * @param string $error
     */
    public function __construct(string $error)
    {
        $this->error = $error;
    }

}