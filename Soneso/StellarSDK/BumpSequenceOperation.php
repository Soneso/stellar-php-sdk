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

class BumpSequenceOperation extends AbstractOperation
{
    private BigInteger $bumpTo;

    public function __construct(BigInteger $bumpTo) {
        $this->bumpTo = $bumpTo;
    }

    /**
     * @return BigInteger
     */
    public function getBumpTo(): BigInteger
    {
        return $this->bumpTo;
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