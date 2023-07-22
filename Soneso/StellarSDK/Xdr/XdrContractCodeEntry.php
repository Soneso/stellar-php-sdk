<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractCodeEntry
{
    public XdrExtensionPoint $ext;
    public string $cHash; // hash
    public XdrContractCodeBody $body;
    public int $expirationLedgerSeq; // uint32

    /**
     * @param XdrExtensionPoint $ext
     * @param string $cHash
     * @param XdrContractCodeBody $body
     * @param int $expirationLedgerSeq
     */
    public function __construct(XdrExtensionPoint $ext, string $cHash, XdrContractCodeBody $body, int $expirationLedgerSeq)
    {
        $this->ext = $ext;
        $this->cHash = $cHash;
        $this->body = $body;
        $this->expirationLedgerSeq = $expirationLedgerSeq;
    }


    public function encode(): string {
        $bytes = $this->ext->encode();
        $bytes .= XdrEncoder::opaqueFixed($this->cHash,32);
        $bytes .= $this->body->encode();
        $bytes .= XdrEncoder::unsignedInteger32($this->expirationLedgerSeq);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrContractCodeEntry {
        $ext = XdrExtensionPoint::decode($xdr);
        $cHash = $xdr->readOpaqueFixed(32);
        $body = XdrContractCodeBody::decode($xdr);
        $expirationLedgerSeq = $xdr->readUnsignedInteger32();

        return new XdrContractCodeEntry($ext, $cHash, $body, $expirationLedgerSeq);
    }

    /**
     * @return XdrExtensionPoint
     */
    public function getExt(): XdrExtensionPoint
    {
        return $this->ext;
    }

    /**
     * @param XdrExtensionPoint $ext
     */
    public function setExt(XdrExtensionPoint $ext): void
    {
        $this->ext = $ext;
    }

    /**
     * @return string
     */
    public function getCHash(): string
    {
        return $this->cHash;
    }

    /**
     * @param string $cHash
     */
    public function setCHash(string $cHash): void
    {
        $this->cHash = $cHash;
    }

    /**
     * @return XdrContractCodeBody
     */
    public function getBody(): XdrContractCodeBody
    {
        return $this->body;
    }

    /**
     * @param XdrContractCodeBody $body
     */
    public function setBody(XdrContractCodeBody $body): void
    {
        $this->body = $body;
    }

    /**
     * @return int
     */
    public function getExpirationLedgerSeq(): int
    {
        return $this->expirationLedgerSeq;
    }

    /**
     * @param int $expirationLedgerSeq
     */
    public function setExpirationLedgerSeq(int $expirationLedgerSeq): void
    {
        $this->expirationLedgerSeq = $expirationLedgerSeq;
    }

}