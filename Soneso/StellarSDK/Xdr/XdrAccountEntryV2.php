<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrAccountEntryV2
{
    public int $numSponsored;
    public int $numSponsoring;
    public array $signerSponsoringIDs; // [?XdrAccountID]
    public XdrAccountEntryV2Ext $ext;

    /**
     * @param int $numSponsored
     * @param int $numSponsoring
     * @param array $signerSponsoringIDs  [?XdrAccountID]
     * @param XdrAccountEntryV2Ext $ext
     */
    public function __construct(int $numSponsored, int $numSponsoring, array $signerSponsoringIDs, XdrAccountEntryV2Ext $ext)
    {
        $this->numSponsored = $numSponsored;
        $this->numSponsoring = $numSponsoring;
        $this->signerSponsoringIDs = $signerSponsoringIDs;
        $this->ext = $ext;
    }


    public function encode(): string {
        $bytes = XdrEncoder::unsignedInteger32($this->numSponsored);
        $bytes .= XdrEncoder::unsignedInteger32($this->numSponsoring);

        $bytes .= XdrEncoder::integer32(count($this->signerSponsoringIDs));
        foreach($this->signerSponsoringIDs as $val) {
            if($val != null) {
                $bytes .= XdrEncoder::integer32(1);
                $bytes .= $val->encode();
            } else {
                $bytes .= XdrEncoder::integer32(0);
            }
        }

        $bytes .= $this->ext->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrAccountEntryV2 {
        $numSponsored = $xdr->readUnsignedInteger32();
        $numSponsoring = $xdr->readUnsignedInteger32();
        $valCount = $xdr->readInteger32();
        $arr = array();
        for ($i = 0; $i < $valCount; $i++) {
            if ($xdr->readInteger32() == 1) {
                array_push($arr, XdrAccountID::decode($xdr));
            } else {
                array_push($arr, null);
            }
        }

        $ext = XdrAccountEntryV2Ext::decode($xdr);
        return new XdrAccountEntryV2($numSponsored, $numSponsoring, $arr, $ext);
    }

    /**
     * @return int
     */
    public function getNumSponsored(): int
    {
        return $this->numSponsored;
    }

    /**
     * @param int $numSponsored
     */
    public function setNumSponsored(int $numSponsored): void
    {
        $this->numSponsored = $numSponsored;
    }

    /**
     * @return int
     */
    public function getNumSponsoring(): int
    {
        return $this->numSponsoring;
    }

    /**
     * @param int $numSponsoring
     */
    public function setNumSponsoring(int $numSponsoring): void
    {
        $this->numSponsoring = $numSponsoring;
    }

    /**
     * @return array
     */
    public function getSignerSponsoringIDs(): array
    {
        return $this->signerSponsoringIDs;
    }

    /**
     * @param array $signerSponsoringIDs
     */
    public function setSignerSponsoringIDs(array $signerSponsoringIDs): void
    {
        $this->signerSponsoringIDs = $signerSponsoringIDs;
    }

    /**
     * @return XdrAccountEntryV2Ext
     */
    public function getExt(): XdrAccountEntryV2Ext
    {
        return $this->ext;
    }

    /**
     * @param XdrAccountEntryV2Ext $ext
     */
    public function setExt(XdrAccountEntryV2Ext $ext): void
    {
        $this->ext = $ext;
    }

}