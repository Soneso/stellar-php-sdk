<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

class XdrAssetAlphaNum4 extends XdrAssetAlphaNum4Base
{
    public function encode(): string {
        $bytes = XdrEncoder::opaqueFixed($this->assetCode, 4, true);
        $bytes .= $this->issuer->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr): static {
        $assetCode = $xdr->readOpaqueFixedString(4);
        $issuer = XdrAccountID::decode($xdr);
        return new static($assetCode, $issuer);
    }
}
