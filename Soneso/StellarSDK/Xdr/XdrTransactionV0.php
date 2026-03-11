<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use Soneso\StellarSDK\Constants\StellarConstants;

class XdrTransactionV0 extends XdrTransactionV0Base
{
    /**
     * @param string $sourceAccountEd25519
     * @param XdrSequenceNumber $sequenceNumber
     * @param array<XdrOperation> $operations
     * @param int|null $fee
     * @param XdrMemo|null $memo
     * @param XdrTimeBounds|null $timeBounds
     * @param XdrTransactionV0Ext|null $ext
     */
    public function __construct(string $sourceAccountEd25519, XdrSequenceNumber $sequenceNumber, array $operations, ?int $fee = null, ?XdrMemo $memo = null, ?XdrTimeBounds $timeBounds = null, ?XdrTransactionV0Ext $ext = null)
    {
        parent::__construct(
            $sourceAccountEd25519,
            $fee ?? StellarConstants::MIN_BASE_FEE_STROOPS,
            $sequenceNumber,
            $memo ?? new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_NONE)),
            $operations,
            $ext ?? new XdrTransactionV0Ext(0),
            $timeBounds,
        );
    }

    /**
     * @return XdrSequenceNumber
     */
    public function getSequenceNumber(): XdrSequenceNumber
    {
        return $this->seqNum;
    }

    public static function decode(XdrBuffer $xdr): static {
        $sourceAccountEd25519 = $xdr->readOpaqueFixed(32);
        $fee = $xdr->readUnsignedInteger32();
        $seqNum = XdrSequenceNumber::decode($xdr);
        $timeBounds = null;
        if ($xdr->readInteger32() !== 0) {
            $timeBounds = XdrTimeBounds::decode($xdr);
        }
        $memo = XdrMemo::decode($xdr);
        $operations = [];
        $operationsSize = $xdr->readInteger32();
        for ($i = 0; $i < $operationsSize; $i++) {
            $operations[] = XdrOperation::decode($xdr);
        }
        $ext = XdrTransactionV0Ext::decode($xdr);
        return new static($sourceAccountEd25519, $seqNum, $operations, $fee, $memo, $timeBounds, $ext);
    }
}
