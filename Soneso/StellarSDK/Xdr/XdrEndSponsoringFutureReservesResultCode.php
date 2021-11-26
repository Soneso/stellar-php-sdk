<?php

namespace Soneso\StellarSDK\Xdr;

class XdrEndSponsoringFutureReservesResultCode
{

    private int $value;

    const SUCCESS = 0;

    const NOT_SPONSORED = -1;

    public function __construct(int $value) {
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    public function encode(): string {
        return XdrEncoder::integer32($this->value);
    }

    public static function decode(XdrBuffer $xdr) : XdrEndSponsoringFutureReservesResultCode {
        $value = $xdr->readInteger32();
        return new XdrEndSponsoringFutureReservesResultCode($value);
    }
}