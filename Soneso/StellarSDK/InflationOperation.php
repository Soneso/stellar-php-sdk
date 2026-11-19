<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

/**
 * Represents an inflation operation in transactions read from XDR.
 *
 * The network refuses this operation with opNOT_SUPPORTED since protocol 12.
 *
 * @package Soneso\StellarSDK
 */
class InflationOperation extends AbstractOperation
{
    /**
     * Converts this operation to its void XDR operation body.
     *
     * @return XdrOperationBody The XDR operation body
     */
    public function toOperationBody(): XdrOperationBody
    {
        return new XdrOperationBody(new XdrOperationType(XdrOperationType::INFLATION));
    }
}
