<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;

class XdrChangeTrustOperation extends XdrChangeTrustOperationBase
{
    const MAX_LIMIT = "9223372036854775807";

    public function __construct(XdrChangeTrustAsset $line, ?BigInteger $limit = null) {
        if ($limit === null) {
            $limit = new BigInteger(self::MAX_LIMIT);
        }
        parent::__construct($line, $limit);
    }
}
