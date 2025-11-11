<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Xdr\XdrBumpSequenceOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrSequenceNumber;

/**
 * Represents <a href="https://developers.stellar.org/docs/start/list-of-operations/#bump-sequence" target="_blank">BumpSequence</a> operation.
 *
 * Bumps the source account's sequence number to the specified value, invalidating any transactions with a lower sequence number.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org/docs/start/list-of-operations/" target="_blank">List of Operations</a>
 * @since 1.0.0
 */
class BumpSequenceOperation extends AbstractOperation
{
    /**
     * @var BigInteger The desired value for the source account's sequence number
     */
    private BigInteger $bumpTo;

    /**
     * Creates a BumpSequence operation.
     * @param BigInteger $bumpTo desired value for the operation’s source account sequence number.
     */
    public function __construct(BigInteger $bumpTo) {
        $this->bumpTo = $bumpTo;
    }

    /**
     * Gets the desired sequence number value.
     *
     * @return BigInteger The desired sequence number
     */
    public function getBumpTo(): BigInteger
    {
        return $this->bumpTo;
    }

    /**
     * Creates a BumpSequenceOperation from its XDR representation.
     *
     * @param XdrBumpSequenceOperation $xdrOp The XDR bump sequence operation to convert
     * @return BumpSequenceOperation The resulting BumpSequenceOperation instance
     */
    public static function fromXdrOperation(XdrBumpSequenceOperation $xdrOp): BumpSequenceOperation {
        $bumpTo = $xdrOp->getBumpTo()->getValue();
        return new BumpSequenceOperation($bumpTo);
    }

    /**
     * Converts this operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body
     */
    public function toOperationBody(): XdrOperationBody {
        $seqNr = new XdrSequenceNumber($this->bumpTo);
        $op = new XdrBumpSequenceOperation($seqNr);
        $type = new XdrOperationType(XdrOperationType::BUMP_SEQUENCE);
        $result = new XdrOperationBody($type);
        $result->setBumpSequenceOp($op);
        return $result;
    }
}