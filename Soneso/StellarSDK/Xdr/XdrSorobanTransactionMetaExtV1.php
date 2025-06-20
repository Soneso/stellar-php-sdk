<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSorobanTransactionMetaExtV1
{
    public XdrExtensionPoint $ext;

    // The following are the components of the overall Soroban resource fee
    // charged for the transaction.
    // The following relation holds:
    // `resourceFeeCharged = totalNonRefundableResourceFeeCharged + totalRefundableResourceFeeCharged`
    // where `resourceFeeCharged` is the overall fee charged for the
    // transaction. Also, `resourceFeeCharged` <= `sorobanData.resourceFee`
    // i.e.we never charge more than the declared resource fee.
    // The inclusion fee for charged the Soroban transaction can be found using
    // the following equation:
    // `result.feeCharged = resourceFeeCharged + inclusionFeeCharged`.

    // Total amount (in stroops) that has been charged for non-refundable
    // Soroban resources.
    // Non-refundable resources are charged based on the usage declared in
    // the transaction envelope (such as `instructions`, `readBytes` etc.) and
    // is charged regardless of the success of the transaction.
    public int $totalNonRefundableResourceFeeCharged;

    // Total amount (in stroops) that has been charged for refundable
    // Soroban resource fees.
    // Currently this comprises the rent fee (`rentFeeCharged`) and the
    // fee for the events and return value.
    // Refundable resources are charged based on the actual resources usage.
    // Since currently refundable resources are only used for the successful
    // transactions, this will be `0` for failed transactions.
    public int $totalRefundableResourceFeeCharged;

    // Amount (in stroops) that has been charged for rent.
    // This is a part of `totalNonRefundableResourceFeeCharged`.
    public int $rentFeeCharged;

    /**
     * @param XdrExtensionPoint $ext
     * @param int $totalNonRefundableResourceFeeCharged
     * @param int $totalRefundableResourceFeeCharged
     * @param int $rentFeeCharged
     */
    public function __construct(
        XdrExtensionPoint $ext,
        int $totalNonRefundableResourceFeeCharged,
        int $totalRefundableResourceFeeCharged,
        int $rentFeeCharged,
    )
    {
        $this->ext = $ext;
        $this->totalNonRefundableResourceFeeCharged = $totalNonRefundableResourceFeeCharged;
        $this->totalRefundableResourceFeeCharged = $totalRefundableResourceFeeCharged;
        $this->rentFeeCharged = $rentFeeCharged;
    }

    public function encode(): string {
        $bytes = $this->ext->encode();
        $bytes .= XdrEncoder::integer64($this->totalNonRefundableResourceFeeCharged);
        $bytes .= XdrEncoder::integer64($this->totalRefundableResourceFeeCharged);
        $bytes .= XdrEncoder::integer64($this->rentFeeCharged);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrSorobanTransactionMetaExtV1 {
        $ext = XdrExtensionPoint::decode($xdr);
        $totalNonRefundableResourceFeeCharged = $xdr->readInteger64();
        $totalRefundableResourceFeeCharged = $xdr->readInteger64();
        $rentFeeCharged = $xdr->readInteger64();

        return new XdrSorobanTransactionMetaExtV1(
            $ext,
            $totalNonRefundableResourceFeeCharged,
            $totalRefundableResourceFeeCharged,
            $rentFeeCharged,
        );
    }

    public function getExt(): XdrExtensionPoint
    {
        return $this->ext;
    }

    public function setExt(XdrExtensionPoint $ext): void
    {
        $this->ext = $ext;
    }

    public function getTotalNonRefundableResourceFeeCharged(): int
    {
        return $this->totalNonRefundableResourceFeeCharged;
    }

    public function setTotalNonRefundableResourceFeeCharged(int $totalNonRefundableResourceFeeCharged): void
    {
        $this->totalNonRefundableResourceFeeCharged = $totalNonRefundableResourceFeeCharged;
    }

    public function getTotalRefundableResourceFeeCharged(): int
    {
        return $this->totalRefundableResourceFeeCharged;
    }

    public function setTotalRefundableResourceFeeCharged(int $totalRefundableResourceFeeCharged): void
    {
        $this->totalRefundableResourceFeeCharged = $totalRefundableResourceFeeCharged;
    }

    public function getRentFeeCharged(): int
    {
        return $this->rentFeeCharged;
    }

    public function setRentFeeCharged(int $rentFeeCharged): void
    {
        $this->rentFeeCharged = $rentFeeCharged;
    }
}