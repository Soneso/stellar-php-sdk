<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractEvent
{
    public XdrExtensionPoint $ext;
    public ?string $hash = null;
    public XdrContractEventType $type;
    public XdrContractEventBody $body;

    /**
     * @param XdrExtensionPoint $ext
     * @param string|null $hash
     * @param XdrContractEventType $type
     * @param XdrContractEventBody $body
     */
    public function __construct(XdrExtensionPoint $ext, ?string $hash, XdrContractEventType $type, XdrContractEventBody $body)
    {
        $this->ext = $ext;
        $this->hash = $hash;
        $this->type = $type;
        $this->body = $body;
    }


    public function encode(): string {
        $bytes = $this->ext->encode();
        if ($this->hash != null) {
            $bytes .= XdrEncoder::integer32(1);
            $bytes .= XdrEncoder::opaqueFixed($this->hash,32);
        } else {
            $bytes .= XdrEncoder::integer32(0);
        }
        $bytes .= $this->type->encode();
        $bytes .= $this->body->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrContractEvent {
        $ext = XdrExtensionPoint::decode($xdr);
        $hash = null;
        if ($xdr->readInteger32() == 1) {
            $hash = $xdr->readOpaqueFixed(32);
        }

        $type = XdrContractEventType::decode($xdr);
        $body = XdrContractEventBody::decode($xdr);

        return new XdrContractEvent($ext, $hash, $type, $body);
    }

}