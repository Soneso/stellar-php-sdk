<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrDecoder
{

    private static ?bool $nativeIsBigEndian = null; // used for caching whether the current platform is big endian.
    
    /**
     * @param $xdr
     * @return int
     */
    public static function unsignedInteger(string $xdr) : int {
        // unsigned 32-bit big-endian
        $unpacked = unpack('N', $xdr);
        return array_pop($unpacked);
    }
    
    /**
     * @param $xdr
     * @return int
     */
    public static function signedInteger(string $xdr) : int {
        // pack() does not support a signed 32-byte int, so work around this with
        // custom encoding
        if (!self::nativeIsBigEndian()) {
            $xdr = strrev($xdr);
        }
        
        $unpacked = unpack('l', $xdr);
        
        return array_pop($unpacked);
    }

    /**
     * @param string $xdr
     * @return integer
     */
    public static function unsignedInteger64(string $xdr): int
    {
        // unsigned 64-bit big-endian
        $unpacked = unpack('J', $xdr);
        return array_pop($unpacked);
    }

    /**
     * @param string $xdr
     * @return integer
     */
    public static function signedInteger64(string $xdr): int
    {
        
        // pack() does not support a signed 64-byte int, so work around this with
        // custom encoding
        if (!self::nativeIsBigEndian()) {
            $xdr = strrev($xdr);
        }
        
        $unpacked = unpack('q', $xdr);
        
        return array_pop($unpacked);
    }

    /**
     * @param string $xdr
     * @return string
     */
    public static function unsignedInteger256(string $xdr): string
    {
        return self::opaqueFixed($xdr, (256/8));
    }

    /**
     * @param string $xdr
     * @return bool
     */
    public static function boolean(string $xdr): bool
    {
        $value = self::unsignedInteger($xdr);
        if ($value !== 1 && $value !== 0) {
            throw new \InvalidArgumentException('Unexpected XDR for a boolean value');
        }
        
        // Equivalent to 1 or 0 uint32
        return (bool)self::unsignedInteger($xdr);
    }

    /**
     * @param $xdr
     * @return string
     */
    public static function string($xdr): string
    {
        return self::opaqueVariable($xdr);
    }
    
    /**
     * Reads a fixed opaque value and returns it as a string
     *
     * @param $xdr
     * @param $length
     * @return string
     */
    public static function opaqueFixedString($xdr, $length) : string
    {
        $bytes = static::opaqueFixed($xdr, $length);
        
        // remove trailing nulls
        return strval(rtrim($bytes, "\x00"));
    }

    /**
     * @param $xdr
     * @param $length
     * @return string
     */
    public static function opaqueFixed($xdr, $length): string
    {
        return substr($xdr, 0, $length);
    }

    /**
     * @param $xdr
     * @return string
     */
    public static function opaqueVariable($xdr): string
    {
        $length = static::unsignedInteger($xdr);
        
        // first 4 bytes are the length
        return substr($xdr, 4, $length);
    }
    
    /**
     * @return bool
     */
    private static function nativeIsBigEndian(): bool
    {
        if (null === self::$nativeIsBigEndian) {
            self::$nativeIsBigEndian = pack('L', 1) === pack('N', 1);
        }
        
        return self::$nativeIsBigEndian;
    }
}