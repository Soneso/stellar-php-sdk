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
     * Never uses scientific notation, which casting a float to string switches to for small and
     * large magnitudes and which anchors do not accept. Stellar assets carry at most seven decimal
     * places, so the fraction is rounded to seven digits, trailing zeroes in the fractional part are
     * trimmed and the decimal point is suppressed for whole amounts.
     *
     * The rendering is locale independent: the %F conversion never follows LC_NUMERIC, unlike the
     * lowercase %f that an application's setlocale() call would reach. It also formats from the
     * float itself, so it does not lose significant digits the way casting does under the default
     * precision setting.
     *
     * Three limits are worth knowing. An amount below half a stroop rounds to 0 and the sign is
     * dropped, and a non-finite amount renders as the empty string; neither names an amount the
     * caller can transact, and both leave the anchor to answer. From 2^29 (536870912) upward a
     * float no longer carries seven meaningful fractional digits, because that is where one unit
     * in the last place first exceeds a stroop, so the last digits describe the stored value
     * rather than the amount that was written.
     *
     * @param float $amount The amount to render.
     * @return string The amount as a plain decimal string.
     */
    public static function format(float $amount): string
    {
        if (!is_finite($amount)) {
            return '';
        }

        $formatted = sprintf('%.7F', $amount);
        $formatted = rtrim($formatted, '0');
        $formatted = rtrim($formatted, '.');

        return $formatted === '-0' ? '0' : $formatted;
    }
}
