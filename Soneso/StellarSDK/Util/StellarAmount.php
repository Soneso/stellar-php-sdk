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
     * @var BigInteger|null Shared, lazily created stroop scale factor.
     */
    private static ?BigInteger $stroopScaleCache = null;

    /**
     * @var BigInteger|null Shared, lazily created maximum signed 64-bit stroops value.
     */
    private static ?BigInteger $maxStroopsCache = null;

    /**
     * @var BigInteger|null Shared, lazily created zero value.
     */
    private static ?BigInteger $zeroCache = null;

    /**
     * Returns the shared stroop scale factor (10,000,000).
     *
     * BigInteger is immutable, so a single instance is reused across all
     * StellarAmount objects instead of re-parsing the constant on each call.
     */
    private static function stroopScale(): BigInteger
    {
        return self::$stroopScaleCache ??= new BigInteger(StellarConstants::STROOP_SCALE);
    }

    /**
     * Returns the shared maximum signed 64-bit stroops value (9223372036854775807).
     */
    private static function maxStroops(): BigInteger
    {
        return self::$maxStroopsCache ??= new BigInteger('9223372036854775807');
    }

    /**
     * Returns the shared zero value.
     */
    private static function zero(): BigInteger
    {
        return self::$zeroCache ??= new BigInteger('0');
    }

    /**
     * Returns the maximum supported amount
     *
     * @static
     * @return StellarAmount The maximum amount (922337203685.4775807 XLM)
     */
    public static function maximum() : StellarAmount
    {
        return new StellarAmount(self::maxStroops());
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
        $this->stroopScaleBignum = self::stroopScale();
        $this->maxSignedStroops64 = self::maxStroops();
        $this->stroops = $stroops;

        // Ensure amount of stroops doesn't exceed the maximum
        $compared = $this->stroops->compare($this->maxSignedStroops64);
        if ($compared > 0) {
            throw new \InvalidArgumentException('Maximum value exceeded. Value cannot be larger than 9223372036854775807 stroops (922337203685.4775807 XLM)');
        }

        // Ensure amount is not negative
        $compared = $this->stroops->compare(self::zero());
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
     * @throws \InvalidArgumentException If amount is not finite, exceeds maximum or is negative.
     *     A negative amount smaller than half a stroop rounds to zero and is accepted.
     */
    public static function fromFloat(float $amount) : StellarAmount {
        if (!is_finite($amount)) {
            throw new \InvalidArgumentException('Amount must be a finite number');
        }
        // %F renders the float itself, giving the nearest stroop to the stored value. Scaling to
        // stroops by a decimal pre-round instead can move the amount by a stroop or more, in
        // either direction, once the scaled value leaves the range where that rounding can place
        // a tie.
        $amountStr = sprintf('%.7F', $amount);
        return self::fromString($amountStr);
    }

    /**
     * Creates a StellarAmount from a decimal string
     *
     * Surrounding whitespace and any space are removed, and a comma is rejected, because a comma
     * separates groups in some locales and decimals in others. What remains must be digits with
     * at most one decimal point and at most one leading sign. An asset carries seven decimal
     * places, so a finer amount is rejected rather than rounded; trailing zeroes do not count
     * towards that limit, and "0.50000000" is accepted as 0.5.
     *
     * @static
     * @param string $decimalAmount The amount as a string (e.g., "100.5" or "1000.25")
     * @return StellarAmount The amount object
     * @throws \InvalidArgumentException If amount contains a comma, is not a decimal number,
     *     carries more than seven decimal places, exceeds maximum or is negative
     */
    public static function fromString(string $decimalAmount) : StellarAmount {
        // A comma groups digits in some locales and separates decimals in others, so "100,5" names
        // both 1005 and 100.5 and the intended one cannot be recovered from the string.
        if (str_contains($decimalAmount, ',')) {
            throw new \InvalidArgumentException(
                'Amount must not contain a comma: ' . substr($decimalAmount, 0, 64));
        }

        // Surrounding whitespace is padding: an amount read from a file or a CSV cell carries a
        // trailing newline. Whitespace between digits is not padding, so it is left in place for
        // the check below to refuse rather than read as a group separator. A space is the one
        // exception, removed anywhere, which is the long-standing contract of this method.
        $amountStr = str_replace(' ', '', trim($decimalAmount, " \t\n\r\v\f"));

        // The sign belongs to the amount as a whole, since the fractional digits are unsigned.
        $negative = str_starts_with($amountStr, '-');
        if ($negative || str_starts_with($amountStr, '+')) {
            $amountStr = substr($amountStr, 1);
        }

        // What remains must be digits around at most one point. That refuses a second sign, which
        // would otherwise be read as part of the integer and negated back to a positive amount,
        // and it requires at least one digit, so an empty, sign-only or point-only string names no
        // amount rather than zero. A zero amount itself stays valid, since a trustline limit of
        // "0" removes the trustline. The pattern is deliberately ASCII: with the u modifier it
        // would accept digits that carry no value here. The D modifier keeps $ from matching
        // before a trailing newline.
        if (preg_match('/^(\d+(\.\d*)?|\.\d+)$/D', $amountStr) !== 1) {
            throw new \InvalidArgumentException(
                'Amount is not a decimal number: ' . substr($decimalAmount, 0, 64));
        }

        $parts = explode('.', $amountStr);
        $unscaledAmount = self::zero();

        // Everything to the left of the decimal point
        if ($parts[0] !== '') {
            $unscaledAmountLeft = (new BigInteger($parts[0]))->multiply(self::stroopScale());
            $unscaledAmount = $unscaledAmount->add($unscaledAmountLeft);
        }

        // Add everything to the right of the decimal point
        if (count($parts) == 2) {
            // Trailing zeroes carry no value, so an amount is only out of range once they are
            // removed: "0.50000000" is 0.5 and fits, while "0.12345678" does not.
            $fraction = rtrim($parts[1], '0');
            if ($fraction !== '') {
                if (strlen($fraction) > 7) {
                    throw new \InvalidArgumentException(
                        'Amount cannot have more than 7 decimal places: '
                        . substr($decimalAmount, 0, 64));
                }
                $unscaledAmount = $unscaledAmount->add(
                    new BigInteger(str_pad($fraction, 7, '0', STR_PAD_RIGHT)));
            }
        }

        if ($negative) {
            $unscaledAmount = self::zero()->subtract($unscaledAmount);
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