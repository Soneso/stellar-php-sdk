<?php

namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;

class XdrCreateClaimableBalanceOperation
{
    private XdrAsset $asset;
    private BigInteger $amount;
    private array $claimants; // [XdrClaimant]

    public function __construct(XdrAsset $asset, BigInteger $amount, array $claimants) {
        $this->asset = $asset;
        $this->amount = $amount;
        $this->claimants = $claimants;
    }

    /**
     * @return XdrAsset
     */
    public function getAsset(): XdrAsset
    {
        return $this->asset;
    }

    /**
     * @return BigInteger
     */
    public function getAmount(): BigInteger
    {
        return $this->amount;
    }

    /**
     * @return array
     */
    public function getClaimants(): array
    {
        return $this->claimants;
    }

    public function encode() : string {
        $bytes = $this->asset->encode();
        $bytes .= XdrEncoder::bigInteger64($this->amount);
        $bytes .= XdrEncoder::integer32(count($this->claimants));
        foreach($this->claimants as $claimant) {
            if($claimant instanceof XdrClaimant) {
                $bytes .= $claimant->encode();
            }
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) :  XdrCreateClaimableBalanceOperation {
        $asset = XdrAsset::decode($xdr);
        $amount = $xdr->readBigInteger64();
        $claimants = array();
        $size = $xdr->readInteger32();
        for ($i=0; $i < $size; $i++) {
            array_push($claimants, XdrClaimant::decode($xdr));
        }
        return new XdrCreateClaimableBalanceOperation($asset, $amount, $claimants);
    }
}