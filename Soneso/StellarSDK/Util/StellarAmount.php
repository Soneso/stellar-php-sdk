<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Util;

use Soneso\StellarSDK\Xdr\XdrBuffer;
use phpseclib3\Math\BigInteger;


class StellarAmount
{
    const STROOP_SCALE = 10000000; // 10 million, 7 zeroes
    
    protected BigInteger $stroops;
    protected BigInteger $stroopScaleBignum;
    protected BigInteger $maxSignedStroops64; // The largest amount of stroops that can fit in a signed int64
    
    /**
     * Returns the maximum supported amount
     *
     * @return StellarAmount
     */
    public static function maximum() : StellarAmount
    {
        return new StellarAmount(new BigInteger('9223372036854775807'));
    }
    
    /**
     * Reads a StellarAmount from a SIGNED 64-bit integer
     *
     * @param XdrBuffer $xdr
     * @return StellarAmount
     */
    public static function fromXdr(XdrBuffer $xdr) : StellarAmount
    {
        return new StellarAmount(new BigInteger($xdr->readInteger64()));
    }

    /**
     * StellarAmount constructor.
     *
     * @param BigInteger $stroops
     */
    public function __construct(BigInteger $stroops)
    {
        $this->stroopScaleBignum = new BigInteger(static::STROOP_SCALE);
        $this->maxSignedStroops64 = new BigInteger('9223372036854775807');
        $this->stroops = $stroops;
        
        // Ensure amount of stroops doesn't exceed the maximum
        $compared = $this->stroops->compare($this->maxSignedStroops64);
        if ($compared > 0) {
            throw new \InvalidArgumentException('Maximum value exceeded. Value cannot be larger than 9223372036854775807 stroops (922337203685.4775807 XLM)');
        }
        
        // Ensure amount is not negative
        $zero = new BigInteger('0');
        $compared = $this->stroops->compare($zero);
        if ($compared < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
        }
    }

    public static function fromFloat(float $amount) : StellarAmount {
        $amountStr = number_format($amount, 7, '.', '');
        return self::fromString($amountStr);
    }

    public static function fromString(string $decimalAmount) : StellarAmount {
        $amountStr = str_replace(',', '', $decimalAmount);
        $amountStr = str_replace(' ', '', $amountStr);
        $parts = explode('.', $amountStr);
        $unscaledAmount = new BigInteger('0');

        // Everything to the left of the decimal point
        if ($parts[0]) {
            $unscaledAmountLeft = (new BigInteger($parts[0]))->multiply(new BigInteger(static::STROOP_SCALE));
            $unscaledAmount = $unscaledAmount->add($unscaledAmountLeft);
        }

        // Add everything to the right of the decimal point
        if (count($parts) == 2 && str_replace('0', '', $parts[1]) != '') {
            // Should be a total of 7 decimal digits to the right of the decimal
            $unscaledAmountRight = str_pad($parts[1], 7, '0',STR_PAD_RIGHT);
            $unscaledAmount = $unscaledAmount->add(new BigInteger($unscaledAmountRight));
        }
        return new StellarAmount($unscaledAmount);
    }
    
    /**
     * @return string
     */
    public function getDecimalValueAsString() : string
    {
        /** @var $quotient BigInteger */
        /** @var $remainder BigInteger */
        list($quotient, $remainder) = $this->stroops->divide($this->stroopScaleBignum);
        $x = bcdiv($remainder->toString(), $this->stroopScaleBignum->toString(), 7);
        $q = $quotient->toString();
        return bcadd($q, strval($x), 7);
    }
    
    /**
     * Returns the raw value in stroops as a string
     *
     * @return string
     */
    public function getStroopsAsString(): string
    {
        return $this->stroops->toString();
    }
    
    /**
     * Returns the raw value in stroops
     *
     * @return BigInteger
     */
    public function getStroops(): BigInteger
    {
        return $this->stroops;
    }
}