<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

class XdrFeeBumpTransactionInnerTx extends XdrFeeBumpTransactionInnerTxBase
{
    public function __construct(XdrEnvelopeType $type, ?XdrTransactionV1Envelope $v1 = null) {
        parent::__construct($type);
        if ($v1 !== null) {
            $this->v1 = $v1;
        }
    }
}
