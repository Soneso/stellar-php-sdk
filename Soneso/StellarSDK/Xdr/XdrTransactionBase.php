<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrTransactionBase
{
    public XdrMuxedAccount $sourceAccount;
    public int $fee; //uint32
    public XdrSequenceNumber $sequenceNumber;
    public ?XdrPreconditions $preconditions = null;
    public XdrMemo $memo;
    /**
     * @var array<XdrOperation> $operations
     */
    public array $operations;
    public XdrTransactionExt $ext;

    /**
     * Constructor.
     * @param XdrMuxedAccount $sourceAccount
     * @param XdrSequenceNumber $sequenceNumber
     * @param array<XdrOperation> $operations
     * @param int $fee
     * @param XdrMemo $memo
     * @param ?XdrPreconditions $preconditions
     * @param XdrTransactionExt $ext
     */
    public function __construct(
        XdrMuxedAccount $sourceAccount,
        XdrSequenceNumber $sequenceNumber,
        array $operations,
        int $fee,
        XdrMemo $memo,
        ?XdrPreconditions $preconditions,
        XdrTransactionExt $ext,
    )
    {
        $this->sourceAccount = $sourceAccount;
        $this->sequenceNumber = $sequenceNumber;
        $this->operations = $operations;
        $this->fee = $fee;
        $this->memo = $memo;
        $this->preconditions = $preconditions;
        $this->ext = $ext;
    }

    public function encode() : string {
        $bytes = $this->sourceAccount->encode();

        $bytes .= XdrEncoder::unsignedInteger32($this->fee);
        $bytes .= $this->sequenceNumber->encode();
        if ($this->preconditions !== null && $this->preconditions->getType()->getValue() != XdrPreconditionType::NONE) {
            $bytes .= $this->preconditions->encode();
        } else {
            $bytes .= XdrEncoder::integer32(0);
        }

        $bytes .= $this->memo->encode();
        $bytes .= XdrEncoder::integer32(count($this->operations));
        foreach($this->operations as $operation) {
            if ($operation instanceof XdrOperation) {
                $bytes .= $operation->encode();
            }
        }
        $bytes .= $this->ext->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : static {
        $sourceAccount = XdrMuxedAccount::decode($xdr);
        $fee = $xdr->readUnsignedInteger32();
        $seqNr = XdrSequenceNumber::decode($xdr);
        $pcond = XdrPreconditions::decode($xdr);
        $memo = XdrMemo::decode($xdr);
        $opCount = $xdr->readInteger32();
        $operations = array();
        for ($i = 0; $i < $opCount; $i++) {
            array_push($operations, XdrOperation::decode($xdr));
        }
        $ext = XdrTransactionExt::decode($xdr);
        return new static($sourceAccount, $seqNr, $operations, $fee, $memo, $pcond, $ext);
    }
}
