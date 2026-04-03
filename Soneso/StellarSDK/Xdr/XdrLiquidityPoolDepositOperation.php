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

    /**
     * Override toTxRep because this class stores $liquidityPoolID as a hex string,
     * not as raw binary bytes (which the base class assumes via bytesToHex).
     *
     * @param string               $prefix
     * @param array<string,string> $lines
     */
    public function toTxRep(string $prefix, array &$lines): void {
        $lines[$prefix . '.liquidityPoolID'] = $this->liquidityPoolID;
        $lines[$prefix . '.maxAmountA'] = $this->maxAmountA->toString();
        $lines[$prefix . '.maxAmountB'] = $this->maxAmountB->toString();
        $this->minPrice->toTxRep($prefix . '.minPrice', $lines);
        $this->maxPrice->toTxRep($prefix . '.maxPrice', $lines);
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
        $maxAmountA = TxRepHelper::parseBigInt(TxRepHelper::getValue($map, $prefix . '.maxAmountA') ?? '0');
        $maxAmountB = TxRepHelper::parseBigInt(TxRepHelper::getValue($map, $prefix . '.maxAmountB') ?? '0');
        $minPrice = XdrPrice::fromTxRep($map, $prefix . '.minPrice');
        $maxPrice = XdrPrice::fromTxRep($map, $prefix . '.maxPrice');
        return new static($liquidityPoolID, $maxAmountA, $maxAmountB, $minPrice, $maxPrice);
    }
}
