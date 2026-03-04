<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

class XdrPreconditionsV2 extends XdrPreconditionsV2Base
{
    public function __construct(
        int $minSeqAge = 0,
        int $minSeqLedgerGap = 0,
        array $extraSigners = [],
        ?XdrTimeBounds $timeBounds = null,
        ?XdrLedgerBounds $ledgerBounds = null,
        ?XdrSequenceNumber $minSeqNum = null,
    ) {
        parent::__construct($minSeqAge, $minSeqLedgerGap, $extraSigners, $timeBounds, $ledgerBounds, $minSeqNum);
    }
}
