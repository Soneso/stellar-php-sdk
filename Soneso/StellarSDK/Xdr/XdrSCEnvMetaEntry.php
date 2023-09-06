<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCEnvMetaEntry
{

    public XdrSCEnvMetaKind $type;
    public ?int $interfaceVersion = null;

    /**
     * @param XdrSCEnvMetaKind $type
     */
    public function __construct(XdrSCEnvMetaKind $type)
    {
        $this->type = $type;
    }

    public static function forInterfaceVersion(int $interfaceVersion) : XdrSCEnvMetaEntry {
        $result = new XdrSCEnvMetaEntry(XdrSCEnvMetaKind::INTERFACE_VERSION());
        $result->interfaceVersion = $interfaceVersion;
        return $result;
    }

    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->value) {
            case XdrSCEnvMetaKind::SC_ENV_META_KIND_INTERFACE_VERSION:
                $bytes .= XdrEncoder::unsignedInteger64($this->interfaceVersion);
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCEnvMetaEntry
    {
        $result = new XdrSCEnvMetaEntry(XdrSCEnvMetaKind::decode($xdr));
        switch ($result->type->value) {
            case XdrSCEnvMetaKind::SC_ENV_META_KIND_INTERFACE_VERSION:
                $result->interfaceVersion = $xdr->readUnsignedInteger64();
                break;
        }
        return $result;
    }

    public static function fromBase64Xdr(String $base64Xdr) : XdrSCEnvMetaEntry {
        $xdr = base64_decode($base64Xdr);
        $xdrBuffer = new XdrBuffer($xdr);
        return XdrSCEnvMetaEntry::decode($xdrBuffer);
    }

    public function toBase64Xdr() : String {
        return base64_encode($this->encode());
    }

    /**
     * @return XdrSCEnvMetaKind
     */
    public function getType(): XdrSCEnvMetaKind
    {
        return $this->type;
    }

    /**
     * @param XdrSCEnvMetaKind $type
     */
    public function setType(XdrSCEnvMetaKind $type): void
    {
        $this->type = $type;
    }

    /**
     * @return int|null
     */
    public function getInterfaceVersion(): ?int
    {
        return $this->interfaceVersion;
    }

    /**
     * @param int|null $interfaceVersion
     */
    public function setInterfaceVersion(?int $interfaceVersion): void
    {
        $this->interfaceVersion = $interfaceVersion;
    }

}