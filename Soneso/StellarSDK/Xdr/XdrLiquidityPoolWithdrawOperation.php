<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Crypto\StrKey;

class XdrLiquidityPoolWithdrawOperation extends XdrLiquidityPoolWithdrawOperationBase
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
        $bytes .= XdrEncoder::bigInteger64($this->amount);
        $bytes .= XdrEncoder::bigInteger64($this->minAmountA);
        $bytes .= XdrEncoder::bigInteger64($this->minAmountB);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr): static {
        $liquidityPoolID = bin2hex($xdr->readOpaqueFixed(32));
        $amount = $xdr->readBigInteger64();
        $minAmountA = $xdr->readBigInteger64();
        $minAmountB = $xdr->readBigInteger64();
        return new static($liquidityPoolID, $amount, $minAmountA, $minAmountB);
    }
}
