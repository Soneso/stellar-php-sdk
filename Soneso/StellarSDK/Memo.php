<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use InvalidArgumentException;
use Soneso\StellarSDK\Constants\StellarConstants;
use Soneso\StellarSDK\Xdr\XdrMemo;
use Soneso\StellarSDK\Xdr\XdrMemoType;

/**
 *
 * Union with fields:
 *  memoType (enum)
 *  value:
 *      none: void
 *      text: string(28)
 *      id: uint64
 *      hash: Hash
 *      return: Hash
 */
class Memo
{
    const MEMO_TYPE_NONE = 0;
    const MEMO_TYPE_TEXT = 1;
    const MEMO_TYPE_ID = 2;
    const MEMO_TYPE_HASH = 3;
    const MEMO_TYPE_RETURN = 4;

    /**
     * See the MEMO_TYPE constants
     *
     * @var int
     */
    private int $type;
    private mixed $value = null;

    /**
     * @param int $type
     * @param null $value
     */
    public function __construct(int $type, $value = null)
    {
        $this->type = $type;
        $this->value = $value;

        $this->validate();
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @return Memo of type none
     */
    public static function none() : Memo {
        return new Memo(self::MEMO_TYPE_NONE);
    }

    /**
     * @param string $text max 28 characters.
     * @return Memo of type text
     */
    public static function text(string $text) : Memo {
        return new Memo(self::MEMO_TYPE_TEXT, $text);
    }

    /**
     * @param int $id
     * @return Memo of type id
     */
    public static function id(int $id) : Memo {
        return new Memo(self::MEMO_TYPE_ID, $id);
    }

    /**
     * @param string $hash 32 bytes
     * @return Memo of type hash
     */
    public static function hash(string $hash) : Memo {
        return new Memo(self::MEMO_TYPE_HASH, $hash);
    }

    /**
     * @param string $hash 32 bytes
     * @return Memo of type return
     */
    public static function return(string $hash) : Memo {
        return new Memo(self::MEMO_TYPE_RETURN, $hash);
    }

    public function validate()
    {
        if ($this->type == static::MEMO_TYPE_NONE) return;
        if ($this->type == static::MEMO_TYPE_TEXT) {
            // Verify length does not exceed max
            if (strlen($this->value) > XdrMemo::VALUE_TEXT_MAX_SIZE) {
                throw new InvalidArgumentException(sprintf('memo text is greater than the maximum of %s bytes', XdrMemo::VALUE_TEXT_MAX_SIZE));
            }
        }
        if ($this->type == static::MEMO_TYPE_ID) {
            if ($this->value < 0) throw new InvalidArgumentException('value cannot be negative');
            if ($this->value > PHP_INT_MAX) throw new InvalidArgumentException(sprintf('value cannot be larger than %s', PHP_INT_MAX));
        }
        if ($this->type == static::MEMO_TYPE_HASH || $this->type == static::MEMO_TYPE_RETURN) {
            if (strlen($this->value) !== StellarConstants::MEMO_HASH_LENGTH) throw new InvalidArgumentException(sprintf('hash values must be %s bytes, got %s bytes', StellarConstants::MEMO_HASH_LENGTH, strlen($this->value)));
        }
    }

    /**
     * Returns the type of this memo as a string.
     * Possible values are 'id', 'text', 'hash', 'none' and 'return'.
     *
     * @return string type of this memo as a string.
     */
    public function typeAsString(): string
    {
        return match ($this->type) {
            XdrMemoType::MEMO_ID => 'id',
            XdrMemoType::MEMO_TEXT => 'text',
            XdrMemoType::MEMO_HASH => 'hash',
            XdrMemoType::MEMO_NONE => 'none',
            XdrMemoType::MEMO_RETURN => 'return',
            default => 'unknown',
        };
    }

    /**
     * Returns the value of this memo as a string. It this memo has no value it returns null.
     *
     * @return string|null the value of this memo as a string if any. If the memo type is 'return' or 'hash' it
     * returns a base 64 encoded string of the memo value. If the memo type is 'text' it just returns the value.
     * If the memo type is 'id' it returns the string representation of the int value. If the memo typ is 'none',
     * it returns null.
     */
    public function valueAsString(): ?string
    {
        if ($this->value === null) {
            return null;
        }
        switch ($this->type) {
            case static::MEMO_TYPE_TEXT:
                return $this->getValue();
            case static::MEMO_TYPE_RETURN:
            case static::MEMO_TYPE_HASH:
                return base64_encode($this->value);
            case static::MEMO_TYPE_ID:
                return strval($this->getValue());
            default:
                return null;
        }
    }

    public function toXdr() : XdrMemo
    {
        $xdrMemoType = new XdrMemoType($this->type);
        $xdr = new XdrMemo($xdrMemoType);
        switch ($this->type) {
            case static::MEMO_TYPE_NONE:
                break;
            case static::MEMO_TYPE_TEXT:
                $xdr->setText($this->getValue());
                break;
            case static::MEMO_TYPE_HASH:
                $xdr->setHash($this->getValue());
                break;
            case static::MEMO_TYPE_ID:
                $xdr->setId($this->getValue());
                break;
            case static::MEMO_TYPE_RETURN:
                $xdr->setReturnHash($this->getValue());
                break;
        }
        return $xdr;
    }

    /**
     * @param XdrMemo $xdr
     * @return Memo
     */
    public static function fromXdr(XdrMemo $xdr): Memo
    {
        $type = $xdr->getType()->getValue();
        $value = null;

        if ($type == static::MEMO_TYPE_TEXT) {
            $value = $xdr->getText();
        }
        else if ($type == static::MEMO_TYPE_ID) {
            $value = $xdr->getId();
        }
        else if ($type == static::MEMO_TYPE_HASH) {
            $value = $xdr->getHash();
        } else if ($type == static::MEMO_TYPE_RETURN) {
            $value = $xdr->getReturnHash();
        }
        return new Memo($type, $value);
    }
}