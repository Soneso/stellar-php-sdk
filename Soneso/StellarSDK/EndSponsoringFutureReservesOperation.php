<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

/**
 * Represents <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a> operation.
 *
 * Terminates a sponsorship relationship initiated by a BeginSponsoringFutureReserves operation.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a>
 * @see BeginSponsoringFutureReservesOperation For starting the sponsorship
 * @since 1.0.0
 */
class EndSponsoringFutureReservesOperation extends AbstractOperation
{
    /**
     * Converts this operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body
     */
    public function toOperationBody(): XdrOperationBody
    {
        $type = new XdrOperationType(XdrOperationType::END_SPONSORING_FUTURE_RESERVES);
        return new XdrOperationBody($type);
    }
}