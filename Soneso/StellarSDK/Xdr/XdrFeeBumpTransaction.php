<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

class XdrFeeBumpTransaction extends XdrFeeBumpTransactionBase
{
    public function __construct(XdrMuxedAccount $feeSource, int $fee, XdrFeeBumpTransactionInnerTx $innerTx, ?XdrFeeBumpTransactionExt $ext = null)
    {
        if ($ext === null) {
            $ext = new XdrFeeBumpTransactionExt(0);
        }
        parent::__construct($feeSource, $fee, $innerTx, $ext);
    }
}
