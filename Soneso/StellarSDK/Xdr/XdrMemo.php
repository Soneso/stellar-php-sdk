<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrMemo
{
    // Text memos can be up to 28 characters
    const VALUE_TEXT_MAX_SIZE = 28;

    private XdrMemoType $type;
    private ?string $text = null;
    private ?int $id = null;
    private ?string $hash = null;
    private ?string $returnHash = null;

    public function __construct(XdrMemoType $type) {
        $this->type = $type;
    }

    /**
     * @return XdrMemoType
     */
    public function getType(): XdrMemoType
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @param string|null $text
     */
    public function setText(?string $text): void
    {
        $this->text = $text;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string|null
     */
    public function getHash(): ?string
    {
        return $this->hash;
    }

    /**
     * @param string|null $hash
     */
    public function setHash(?string $hash): void
    {
        $this->hash = $hash;
    }

    /**
     * @return string|null
     */
    public function getReturnHash(): ?string
    {
        return $this->returnHash;
    }

    /**
     * @param string|null $returnHash
     */
    public function setReturnHash(?string $returnHash): void
    {
        $this->returnHash = $returnHash;
    }

    public function encode() : string {
        $bytes = $this->type->encode();
        switch ($this->type->getValue()) {
            case XdrMemoType::MEMO_NONE:
                break;
            case XdrMemoType::MEMO_TEXT:
                $bytes .= XdrEncoder::string($this->getText(), static::VALUE_TEXT_MAX_SIZE);
                break;
            case XdrMemoType::MEMO_ID:
                $bytes .= XdrEncoder::unsignedInteger64($this->getId());
                break;
            case XdrMemoType::MEMO_HASH:
                $bytes .= XdrEncoder::opaqueFixed($this->getHash(), 32);
                break;
            case XdrMemoType::MEMO_RETURN:
                $bytes .= XdrEncoder::opaqueFixed($this->getReturnHash(), 32);
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrMemo {
        $type = new XdrMemoType($xdr->readUnsignedInteger32());
        $xdrMemo = new XdrMemo($type);
        switch ($type->getValue()) {
            case XdrMemoType::MEMO_NONE:
                break;
            case XdrMemoType::MEMO_ID:
                $xdrMemo->setId($xdr->readUnsignedInteger64());
                break;
            case XdrMemoType::MEMO_TEXT:
                $xdrMemo->setText($xdr->readString(static::VALUE_TEXT_MAX_SIZE));
                break;
            case XdrMemoType::MEMO_HASH:
                $xdrMemo->setHash($xdr->readOpaqueFixed(32));
                break;
            case XdrMemoType::MEMO_RETURN:
                $xdrMemo->setReturnHash($xdr->readOpaqueFixed(32));
                break;
        }
        return $xdrMemo;
    }
}