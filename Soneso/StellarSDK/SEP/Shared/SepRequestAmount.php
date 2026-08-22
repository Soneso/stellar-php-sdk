<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Shared;

/**
 * Renders an asset amount for a SEP request field.
 *
 * Scoped to the classic seven decimal places, and to request fields the SDK types as a float.
 * Not for Soroban token amounts, whose scale the token defines and which routinely carry far more
 * than seven decimals, and not for transaction amounts, which reach operation XDR through
 * AbstractOperation::toXdrAmount.
 *
 * @package Soneso\StellarSDK\SEP\Shared
 */
class SepRequestAmount
{
    /**
     * Renders an amount as a plain decimal string suitable for a request parameter.
     *
     * The rendering starts from the shortest decimal representation that reads back to the same
     * float and rounds it to at most seven decimal places, trimming trailing fractional zeroes
     * and suppressing the decimal point for whole amounts. It never uses scientific notation,
     * which anchors do not accept, and it never renders the binary expansion behind the shortest
     * representation. A fraction that ends exactly halfway rounds away from zero.
     *
     * The rendering is locale independent: every step is decimal string arithmetic on
     * json_encode's output, which always uses ASCII digits and a period whatever LC_NUMERIC a
     * caller's setlocale() run has applied. The serialize_precision setting is pinned to -1 for
     * the encode and restored, so a host configuration that caps or widens it cannot change the
     * digits.
     *
     * Two limits are worth knowing. An amount below half a stroop rounds to 0 and the sign is
     * dropped, and a non-finite amount renders as the empty string; neither names an amount the
     * caller can transact, and both leave the anchor to answer.
     *
     * @param float $amount The amount to render.
     * @return string The amount as a plain decimal string.
     */
    public static function format(float $amount): string
    {
        if (!is_finite($amount)) {
            return '';
        }

        $previousPrecision = ini_set('serialize_precision', '-1');
        $shortest = (string) json_encode($amount);
        if ($previousPrecision !== false) {
            ini_set('serialize_precision', $previousPrecision);
        }

        $sign = '';
        if (str_starts_with($shortest, '-')) {
            $sign = '-';
            $shortest = substr($shortest, 1);
        }

        // Split the representation into its digit string and the position of the decimal
        // point within it, folding a scientific exponent into that position.
        $exponent = 0;
        $ePos = stripos($shortest, 'e');
        if ($ePos !== false) {
            $exponent = (int) substr($shortest, $ePos + 1);
            $shortest = substr($shortest, 0, $ePos);
        }
        $dotPos = strpos($shortest, '.');
        if ($dotPos !== false) {
            $pointPosition = $dotPos;
            $shortest = str_replace('.', '', $shortest);
        } else {
            $pointPosition = strlen($shortest);
        }
        $digits = $shortest;
        $pointPosition += $exponent;

        if ($pointPosition <= 0) {
            $integerDigits = '0';
            $fractionDigits = str_repeat('0', -$pointPosition) . $digits;
        } elseif ($pointPosition >= strlen($digits)) {
            $integerDigits = $digits . str_repeat('0', $pointPosition - strlen($digits));
            $fractionDigits = '';
        } else {
            $integerDigits = substr($digits, 0, $pointPosition);
            $fractionDigits = substr($digits, $pointPosition);
        }

        // Round the fraction to seven places on the decimal digits, carrying into the
        // integer part when the fraction overflows.
        if (strlen($fractionDigits) > 7) {
            $roundsUp = $fractionDigits[7] >= '5';
            $fractionDigits = substr($fractionDigits, 0, 7);
            if ($roundsUp) {
                $combined = $integerDigits . $fractionDigits;
                $index = strlen($combined) - 1;
                $carry = true;
                while ($carry && $index >= 0) {
                    if ($combined[$index] === '9') {
                        $combined[$index] = '0';
                    } else {
                        $combined[$index] = (string) ((int) $combined[$index] + 1);
                        $carry = false;
                    }
                    $index--;
                }
                if ($carry) {
                    $combined = '1' . $combined;
                }
                $fractionDigits = substr($combined, -7);
                $integerDigits = substr($combined, 0, -7);
            }
        }

        $fractionDigits = rtrim($fractionDigits, '0');
        $body = $fractionDigits === '' ? $integerDigits : $integerDigits . '.' . $fractionDigits;

        // A zero result carries no sign.
        if (trim($body, '0.') === '') {
            return '0';
        }
        return $sign . $body;
    }
}
