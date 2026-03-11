<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

use InvalidArgumentException;

class XdrTransactionEnvelope extends XdrTransactionEnvelopeBase
{
    public static function fromEnvelopeBase64XdrString(string $envelope) : XdrTransactionEnvelope {
        $xdr = base64_decode($envelope, true);
        if ($xdr === false) {
            throw new InvalidArgumentException('Invalid base64-encoded XDR');
        }
        return XdrTransactionEnvelope::decode(new XdrBuffer($xdr));
    }
}
