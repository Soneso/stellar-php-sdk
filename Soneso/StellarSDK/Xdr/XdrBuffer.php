<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use InvalidArgumentException;
use phpseclib3\Math\BigInteger;


/**
 * Enables easy iteration through a blob of XDR data
 */
class XdrBuffer
{

    protected string $xdrBytes;
    protected int $position; // Current position within the bytes
    protected int $size;

    public function __construct(string $xdrBytes)
    {
        $this->xdrBytes = $xdrBytes;
        
        $this->position = 0;
        $this->size = strlen($xdrBytes);
    }
    
    /**
     * @return int
     */
    public function readUnsignedInteger32() : int
    {
        $dataSize = 4;
        $this->assertBytesRemaining($dataSize);
        
        $data = XdrDecoder::unsignedInteger(substr($this->xdrBytes, $this->position, $dataSize));
        $this->position += $dataSize;
        
        return $data;
    }
    
    /**
     * @return int
     */
    public function readUnsignedInteger64() : int
    {
        $dataSize = 8;
        $this->assertBytesRemaining($dataSize);
        
        $data = XdrDecoder::unsignedInteger64(substr($this->xdrBytes, $this->position, $dataSize));
        $this->position += $dataSize;
        
        return $data;
    }
    
    /**
     * @return BigInteger
     */
    public function readBigInteger64() : BigInteger
    {
        $dataSize = 8;
        $this->assertBytesRemaining($dataSize);
        
        $bigInteger = new BigInteger(substr($this->xdrBytes, $this->position, $dataSize), 256);
        $this->position += $dataSize;
        
        return $bigInteger;
    }
    
    /**
     * @return int
     */
    public function readInteger32() : int
    {
        $dataSize = 4;
        $this->assertBytesRemaining($dataSize);
        
        $data = XdrDecoder::signedInteger(substr($this->xdrBytes, $this->position, $dataSize));
        $this->position += $dataSize;
        
        return $data;
    }
    
    /**
     * @return int
     */
    public function readInteger64() : int
    {
        $dataSize = 8;
        $this->assertBytesRemaining($dataSize);
        
        $data = XdrDecoder::signedInteger64(substr($this->xdrBytes, $this->position, $dataSize));
        $this->position += $dataSize;
        
        return $data;
    }

    public function readUnsignedInteger256(): string {
        return $this->readOpaqueFixed(256/8);
    }

    /**
     * @param $length
     * @return string
     */
    public function readOpaqueFixed($length): string
    {
        $this->assertBytesRemaining($length);
        
        $data = XdrDecoder::opaqueFixed(substr($this->xdrBytes, $this->position), $length);
        $this->position += $length;
        
        return $data;
    }
    
    /**
     * @param $length
     * @return string
     */
    public function readOpaqueFixedString($length) : string
    {
        $this->assertBytesRemaining($length);
        
        $data = XdrDecoder::opaqueFixedString(substr($this->xdrBytes, $this->position), $length);
        $this->position += $length;
        
        return $data;
    }

    /**
     * @param null $maxLength
     * @return string
     */
    public function readOpaqueVariable($maxLength = null) : string
    {
        $length = $this->readUnsignedInteger32();
        $paddedLength = $this->roundTo4($length);
        
        if ($maxLength !== null && $length > $maxLength) {
            throw new InvalidArgumentException(sprintf('length of %s exceeds max length of %s', $length, $maxLength));
        }
        
        $this->assertBytesRemaining($paddedLength);
        
        $data = XdrDecoder::opaqueFixed(substr($this->xdrBytes, $this->position), $length);
        $this->position += $paddedLength;
        
        return $data;
    }

    /**
     * @param null $maxLength
     * @return string
     */
    public function readString($maxLength = null): string
    {
        $strLen = $this->readUnsignedInteger32();
        $paddedLength = $this->roundTo4($strLen);
        if ($maxLength != null && $strLen > $maxLength) throw new InvalidArgumentException(sprintf('maxLength of %s exceeded (string is %s bytes)', $maxLength, $strLen));
        
        $this->assertBytesRemaining($paddedLength);
        
        $data = XdrDecoder::opaqueFixed(substr($this->xdrBytes, $this->position), $strLen);
        $this->position += $paddedLength;
        
        return $data;
    }
    
    /**
     * @return bool
     */
    public function readBoolean() : bool
    {
        $dataSize = 4;
        $this->assertBytesRemaining($dataSize);
        
        $data = XdrDecoder::boolean(substr($this->xdrBytes, $this->position, $dataSize));
        $this->position += $dataSize;
        
        return $data;
    }
    
    /**
     * @param $numBytes
     */
    protected function assertBytesRemaining($numBytes)
    {
        if ($this->position + $numBytes > $this->size) {
            throw new InvalidArgumentException('Unexpected end of XDR data');
        }
    }
    
    /**
     * rounds $number up to the nearest value that's a multiple of 4
     *
     * @param $number
     * @return int
     */
    protected function roundTo4($number) : int
    {
        $remainder = $number % 4;
        if (!$remainder) return $number;
        
        return $number + (4 - $remainder);
    }
}