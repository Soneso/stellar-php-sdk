<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

class XdrTransactionEnvelope extends XdrTransactionEnvelopeBase
{
    public static function fromEnvelopeBase64XdrString(string $envelope) : XdrTransactionEnvelope {
        return XdrTransactionEnvelope::fromBase64Xdr($envelope);
    }
}
