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

    /**
     * Override toTxRep because this class stores $liquidityPoolID as a hex string,
     * not as raw binary bytes (which the base class assumes via bytesToHex).
     *
     * @param string               $prefix
     * @param array<string,string> $lines
     */
    public function toTxRep(string $prefix, array &$lines): void {
        $lines[$prefix . '.liquidityPoolID'] = $this->liquidityPoolID;
        $lines[$prefix . '.amount'] = $this->amount->toString();
        $lines[$prefix . '.minAmountA'] = $this->minAmountA->toString();
        $lines[$prefix . '.minAmountB'] = $this->minAmountB->toString();
    }

    /**
     * Override fromTxRep to read the pool ID as a hex string directly (the
     * base class converts hex→binary bytes via hexToBytes, which breaks encode()).
     *
     * @param array<string,string> $map
     * @param string               $prefix
     * @return static
     */
    public static function fromTxRep(array $map, string $prefix): static {
        $liquidityPoolID = TxRepHelper::getValue($map, $prefix . '.liquidityPoolID') ?? '';
        $amount = TxRepHelper::parseBigInt(TxRepHelper::getValue($map, $prefix . '.amount') ?? '0');
        $minAmountA = TxRepHelper::parseBigInt(TxRepHelper::getValue($map, $prefix . '.minAmountA') ?? '0');
        $minAmountB = TxRepHelper::parseBigInt(TxRepHelper::getValue($map, $prefix . '.minAmountB') ?? '0');
        return new static($liquidityPoolID, $amount, $minAmountA, $minAmountB);
    }
}
