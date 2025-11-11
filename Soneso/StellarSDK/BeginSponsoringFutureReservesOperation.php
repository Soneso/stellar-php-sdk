<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrBeginSponsoringFutureReservesOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

/**
 * Represents <a href="https://developers.stellar.org/docs/start/list-of-operations/#begin-sponsoring-future-reserves" target="_blank">BeginSponsoringFutureReserves</a> operation.
 *
 * Initiates a sponsorship relationship, where the source account will pay the base reserves for operations submitted by the sponsored account.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org/docs/start/list-of-operations/" target="_blank">List of Operations</a>
 * @see EndSponsoringFutureReservesOperation For ending the sponsorship
 * @since 1.0.0
 */
class BeginSponsoringFutureReservesOperation extends AbstractOperation
{
    /**
     * @var string The account ID that will be sponsored
     */
    private string $sponsoredId;

    /**
     * Creates a new BeginSponsoringFutureReservesOperation.
     *
     * @param string $sponsoredId The account ID of the account to be sponsored
     */
    public function __construct(string $sponsoredId) {
        $this->sponsoredId = $sponsoredId;
    }

    /**
     * Gets the sponsored account ID.
     *
     * @return string The sponsored account ID
     */
    public function getSponsoredId(): string
    {
        return $this->sponsoredId;
    }

    /**
     * Creates a BeginSponsoringFutureReservesOperation from its XDR representation.
     *
     * @param XdrBeginSponsoringFutureReservesOperation $xdrOp The XDR begin sponsoring future reserves operation to convert
     * @return BeginSponsoringFutureReservesOperation The resulting BeginSponsoringFutureReservesOperation instance
     */
    public static function fromXdrOperation(XdrBeginSponsoringFutureReservesOperation $xdrOp): BeginSponsoringFutureReservesOperation {
        $sponsoredId = $xdrOp->getSponsoredID()->getAccountId();
        return new BeginSponsoringFutureReservesOperation($sponsoredId);
    }

    /**
     * Converts this operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body
     */
    public function toOperationBody(): XdrOperationBody
    {
        $xdrSponsoredId = new XdrAccountID($this->sponsoredId);
        $op = new XdrBeginSponsoringFutureReservesOperation($xdrSponsoredId);
        $type = new XdrOperationType(XdrOperationType::BEGIN_SPONSORING_FUTURE_RESERVES);
        $result = new XdrOperationBody($type);
        $result->setBeginSponsoringFutureReservesOperation($op);
        return $result;
    }
}