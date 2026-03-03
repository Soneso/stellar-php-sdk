<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use Soneso\StellarSDK\Constants\StellarConstants;

class XdrTransaction extends XdrTransactionBase
{

    /**
     * Constructor.
     * @param XdrMuxedAccount $sourceAccount
     * @param XdrSequenceNumber $sequenceNumber
     * @param array<XdrOperation> $operations
     * @param int|null $fee
     * @param XdrMemo|null $memo
     * @param XdrPreconditions|null $preconditions
     * @param XdrTransactionExt|null $ext
     */
    public function __construct(
        XdrMuxedAccount $sourceAccount,
        XdrSequenceNumber $sequenceNumber,
        array $operations,
        ?int $fee = null,
        ?XdrMemo $memo = null,
        ?XdrPreconditions $preconditions = null,
        ?XdrTransactionExt $ext = null,
    )
    {
        if ($fee === null) {
            $fee = StellarConstants::MIN_BASE_FEE_STROOPS;
        }
        if ($memo === null) {
            $memo = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_NONE));
        }
        if ($ext === null) {
            $ext = new XdrTransactionExt(0);
        }
        parent::__construct($sourceAccount, $sequenceNumber, $operations, $fee, $memo, $preconditions, $ext);
    }

    /**
     * @return XdrMuxedAccount
     */
    public function getSourceAccount(): XdrMuxedAccount
    {
        return $this->sourceAccount;
    }

    /**
     * @return int
     */
    public function getFee(): int
    {
        return $this->fee;
    }

    /**
     * @return XdrSequenceNumber
     */
    public function getSequenceNumber(): XdrSequenceNumber
    {
        return $this->sequenceNumber;
    }

    /**
     * @return XdrPreconditions|null
     */
    public function getPreconditions(): ?XdrPreconditions
    {
        return $this->preconditions;
    }

    /**
     * @return XdrTimeBounds|null
     */
    public function getTimeBounds(): ?XdrTimeBounds
    {
        if ($this->preconditions !== null) {
            if ($this->preconditions->getType()->getValue() == XdrPreconditionType::TIME) {
               return $this->preconditions->getTimeBounds();
            } else if ($this->preconditions->getType()->getValue() == XdrPreconditionType::V2 && $this->preconditions->getV2() !== null) {
                return $this->preconditions->getV2()->getTimeBounds();
            }
        }
        return null;
    }

    /**
     * @return XdrMemo
     */
    public function getMemo(): XdrMemo
    {
        return $this->memo;
    }

    /**
     * @return array<XdrOperation>
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * @return XdrTransactionExt
     */
    public function getExt(): XdrTransactionExt
    {
        return $this->ext;
    }
}
