<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Crypto\StrKey;

class XdrLiquidityPoolDepositOperation extends XdrLiquidityPoolDepositOperationBase
{
    public function encode(): string {
        $idHex = $this->liquidityPoolID;
        if (str_starts_with($idHex, "L")) {
            $idHex = StrKey::decodeLiquidityPoolIdHex($idHex);
        }
        $poolIdBytes = pack("H*", $idHex);
        if (strlen($poolIdBytes) > 32) {
            $poolIdBytes = substr($poolIdBytes, -32);
        }
        $bytes = XdrEncoder::opaqueFixed($poolIdBytes, 32);
        $bytes .= XdrEncoder::bigInteger64($this->maxAmountA);
        $bytes .= XdrEncoder::bigInteger64($this->maxAmountB);
        $bytes .= $this->minPrice->encode();
        $bytes .= $this->maxPrice->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr): static {
        $liquidityPoolID = bin2hex($xdr->readOpaqueFixed(32));
        $maxAmountA = $xdr->readBigInteger64();
        $maxAmountB = $xdr->readBigInteger64();
        $minPrice = XdrPrice::decode($xdr);
        $maxPrice = XdrPrice::decode($xdr);
        return new static($liquidityPoolID, $maxAmountA, $maxAmountB, $minPrice, $maxPrice);
    }
}
