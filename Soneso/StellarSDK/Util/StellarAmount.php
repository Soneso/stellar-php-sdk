<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Util;

use Soneso\StellarSDK\Constants\StellarConstants;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use phpseclib3\Math\BigInteger;

/**
 * Represents an amount in the Stellar network with proper precision handling
 *
 * Stellar amounts are represented internally as 64-bit signed integers in stroops,
 * where 1 XLM = 10,000,000 stroops. This class handles conversion between decimal
 * amounts and stroops while ensuring proper precision and validation.
 *
 * Maximum supported amount: 922337203685.4775807 XLM (9223372036854775807 stroops)
 *
 * Example usage:
 * ```php
 * // Create from decimal string
 * $amount = StellarAmount::fromString("100.50");
 *
 * // Create from float
 * $amount = StellarAmount::fromFloat(100.5);
 *
 * // Get decimal representation
 * $decimal = $amount->getDecimalValueAsString(); // "100.5000000"
 *
 * // Get stroops value
 * $stroops = $amount->getStroopsAsString(); // "1005000000"
 * ```
 *
 * @package Soneso\StellarSDK\Util
 * @see https://developers.stellar.org Stellar developer docs Documentation on Lumens (XLM)
 */
class StellarAmount
{
    /**
     * @var BigInteger The amount value in stroops
     */
    protected BigInteger $stroops;

    /**
     * @var BigInteger Scale factor for stroop conversion (10,000,000)
     */
    protected BigInteger $stroopScaleBignum;

    /**
     * @var BigInteger Maximum value that fits in a signed 64-bit integer (9223372036854775807)
     */
    protected BigInteger $maxSignedStroops64;
    
    /**
     * Returns the maximum supported amount
     *
     * @static
     * @return StellarAmount The maximum amount (922337203685.4775807 XLM)
     */
    public static function maximum() : StellarAmount
    {
        return new StellarAmount(new BigInteger('9223372036854775807'));
    }
    
    /**
     * Reads a StellarAmount from a SIGNED 64-bit integer
     *
     * @static
     * @param XdrBuffer $xdr The XDR buffer to read from
     * @return StellarAmount The decoded amount
     * @throws \InvalidArgumentException If amount exceeds maximum or is negative
     */
    public static function fromXdr(XdrBuffer $xdr) : StellarAmount
    {
        return new StellarAmount(new BigInteger($xdr->readInteger64()));
    }

    /**
     * StellarAmount constructor
     *
     * @param BigInteger $stroops The amount in stroops (1 XLM = 10,000,000 stroops)
     * @throws \InvalidArgumentException If amount exceeds maximum or is negative
     */
    public function __construct(BigInteger $stroops)
    {
        $this->stroopScaleBignum = new BigInteger(StellarConstants::STROOP_SCALE);
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

    /**
     * Creates a StellarAmount from a floating point number
     *
     * @static
     * @param float $amount The amount as a decimal number (e.g., 100.5 for 100.5 XLM)
     * @return StellarAmount The amount object
     * @throws \InvalidArgumentException If amount exceeds maximum or is negative
     */
    public static function fromFloat(float $amount) : StellarAmount {
        $amountStr = number_format($amount, 7, '.', '');
        return self::fromString($amountStr);
    }

    /**
     * Creates a StellarAmount from a decimal string
     *
     * Supports up to 7 decimal places. Commas and spaces are automatically removed.
     *
     * @static
     * @param string $decimalAmount The amount as a string (e.g., "100.5" or "1,000.25")
     * @return StellarAmount The amount object
     * @throws \InvalidArgumentException If amount exceeds maximum or is negative
     */
    public static function fromString(string $decimalAmount) : StellarAmount {
        $amountStr = str_replace(',', '', $decimalAmount);
        $amountStr = str_replace(' ', '', $amountStr);
        $parts = explode('.', $amountStr);
        $unscaledAmount = new BigInteger('0');

        // Everything to the left of the decimal point
        if ($parts[0]) {
            $unscaledAmountLeft = (new BigInteger($parts[0]))->multiply(new BigInteger(StellarConstants::STROOP_SCALE));
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
     * Returns the decimal value as a string with 7 decimal places
     *
     * @return string The amount formatted as a decimal string (e.g., "100.5000000")
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
     * @return string The amount in stroops as a numeric string
     */
    public function getStroopsAsString(): string
    {
        return $this->stroops->toString();
    }
    
    /**
     * Returns the raw value in stroops
     *
     * @return BigInteger The amount in stroops as a BigInteger object
     */
    public function getStroops(): BigInteger
    {
        return $this->stroops;
    }
}