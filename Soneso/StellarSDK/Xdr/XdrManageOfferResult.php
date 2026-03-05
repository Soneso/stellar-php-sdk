<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

class XdrManageOfferResult extends XdrManageOfferResultBase
{
    public function __construct(XdrManageOfferResultCode $code, ?XdrManageOfferSuccessResult $success = null) {
        parent::__construct($code);
        $this->success = $success;
    }
}
