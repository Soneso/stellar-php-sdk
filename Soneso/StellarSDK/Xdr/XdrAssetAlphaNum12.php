<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

class XdrAssetAlphaNum12 extends XdrAssetAlphaNum12Base
{
    public function encode(): string {
        $bytes = XdrEncoder::opaqueFixed($this->assetCode, 12, true);
        $bytes .= $this->issuer->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr): static {
        $assetCode = $xdr->readOpaqueFixedString(12);
        $issuer = XdrAccountID::decode($xdr);
        return new static($assetCode, $issuer);
    }
}
