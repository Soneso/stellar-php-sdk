<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

class XdrConfigUpgradeSetKey extends XdrConfigUpgradeSetKeyBase
{
    public function encode(): string {
        $bytes = XdrEncoder::opaqueFixed(hex2bin($this->contractID), 32);
        $bytes .= XdrEncoder::opaqueFixed(hex2bin($this->contentHash), 32);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr): static {
        $contractID = bin2hex($xdr->readOpaqueFixed(32));
        $contentHash = bin2hex($xdr->readOpaqueFixed(32));
        return new static($contractID, $contentHash);
    }
}
