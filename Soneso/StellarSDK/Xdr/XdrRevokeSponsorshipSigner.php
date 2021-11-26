<?php

namespace Soneso\StellarSDK\Xdr;

class XdrRevokeSponsorshipSigner
{
    private XdrAccountID $accountId;
    private XdrSignerKey $signerKey;

    public function __construct(XdrAccountID $accountId, XdrSignerKey $signerKey) {
        $this->accountId = $accountId;
        $this->signerKey = $signerKey;
    }

    /**
     * @return XdrAccountID
     */
    public function getAccountId(): XdrAccountID
    {
        return $this->accountId;
    }

    /**
     * @return XdrSignerKey
     */
    public function getSignerKey(): XdrSignerKey
    {
        return $this->signerKey;
    }

    public function encode() : string {
        $bytes = $this->accountId->encode();
        $bytes .= $this->signerKey->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrRevokeSponsorshipSigner {
        $accountId = XdrAccountID::decode($xdr);
        $signerKey = XdrSignerKey::decode($xdr);
        return new XdrRevokeSponsorshipSigner($accountId, $signerKey);
    }
}