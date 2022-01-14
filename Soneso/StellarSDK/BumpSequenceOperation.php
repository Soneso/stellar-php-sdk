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
 * Represents <a href="https://developers.stellar.org/docs/start/list-of-operations/#bump-sequence">BumpSequence</a> operation.
 * @see <a href="https://developers.stellar.org/docs/start/list-of-operations/">List of Operations</a>
 */
class BumpSequenceOperation extends AbstractOperation
{
    private BigInteger $bumpTo;

    /**
     * Creates a BumpSequence operation.
     * @param BigInteger $bumpTo desired value for the operation’s source account sequence number.
     */
    public function __construct(BigInteger $bumpTo) {
        $this->bumpTo = $bumpTo;
    }

    /**
     * Desired value for the operation’s source account sequence number.
     * @return BigInteger
     */
    public function getBumpTo(): BigInteger
    {
        return $this->bumpTo;
    }

    public static function fromXdrOperation(XdrBumpSequenceOperation $xdrOp): BumpSequenceOperation {
        $bumpTo = $xdrOp->getBumpTo()->getValue();
        return new BumpSequenceOperation($bumpTo);
    }

    public function toOperationBody(): XdrOperationBody {
        $seqNr = new XdrSequenceNumber($this->bumpTo);
        $op = new XdrBumpSequenceOperation($seqNr);
        $type = new XdrOperationType(XdrOperationType::BUMP_SEQUENCE);
        $result = new XdrOperationBody($type);
        $result->setBumpSequenceOp($op);
        return $result;
    }
}