<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

class XdrInnerTransactionResultPair extends XdrInnerTransactionResultPairBase
{
    public function encode(): string {
        $transactionHashBytes = pack("H*", $this->transactionHash);
        if (strlen($transactionHashBytes) > 32) {
            $transactionHashBytes = substr($transactionHashBytes, -32);
        }
        $bytes = XdrEncoder::opaqueFixed($transactionHashBytes, 32);
        $bytes .= $this->result->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr): static {
        $transactionHash = bin2hex($xdr->readOpaqueFixed(32));
        $result = XdrInnerTransactionResult::decode($xdr);
        return new static($transactionHash, $result);
    }
}
