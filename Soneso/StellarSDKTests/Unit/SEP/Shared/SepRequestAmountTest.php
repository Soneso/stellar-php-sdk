<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\SEP\Shared;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\SEP\Shared\SepRequestAmount;

class SepRequestAmountTest extends TestCase
{
    // Plain decimal rendering

    public function testSmallAmountRendersPlainDecimal(): void
    {
        // 123 stroops, an ordinary amount. Casting the float spells it "1.23E-5".
        $this->assertSame("0.0000123", SepRequestAmount::format(0.0000123));
    }

    public function testOneStroopRendersAllSevenDecimals(): void
    {
        $this->assertSame("0.0000001", SepRequestAmount::format(1e-7));
    }

    public function testWholeAmountSuppressesDecimalPoint(): void
    {
        $this->assertSame("100", SepRequestAmount::format(100.0));
        $this->assertSame("1", SepRequestAmount::format(1.0));
        // The trim stops at the decimal point, so zeroes in the integer part survive.
        $this->assertSame("120", SepRequestAmount::format(120.0));
        $this->assertSame("1000000000000000", SepRequestAmount::format(1e15));
    }

    public function testTrailingZeroesInFractionAreTrimmed(): void
    {
        $this->assertSame("0.000012", SepRequestAmount::format(0.0000120));
        $this->assertSame("0.00001", SepRequestAmount::format(0.0000100));
    }

    public function testLargeAmountRendersPlainDecimal(): void
    {
        $this->assertSame("1000000000000000000000", SepRequestAmount::format(1e21));
    }

    // Significant digits the default precision setting drops

    public function testSeventhDecimalSurvives(): void
    {
        // Casting these floats yields "12345678.123457" and "123456789.12346": the seventh
        // decimal is lost because the precision setting caps significant digits at 14.
        $this->assertSame("12345678.1234567", SepRequestAmount::format(12345678.1234567));
        $this->assertSame("123456789.1234567", SepRequestAmount::format(123456789.1234567));
    }

    public function testAmountIsNotRoundedToADifferentAmount(): void
    {
        // Casting this float yields "100000000", a different amount.
        $this->assertSame("99999999.9999999", SepRequestAmount::format(99999999.9999999));
    }

    public function testNoStroopIsInventedByADecimalPreScale(): void
    {
        // Formatting via a decimal pre-scale overstates these by a stroop: scaling by 10^7 pushes
        // the value past the range where the pre-rounding can still place a tie, from 2^28 for
        // fractional amounts and from ceil(2^52/1e7) for whole ones. 500000000.0 and 900000000.0
        // are both carried exactly by the float.
        $this->assertSame("500000000", SepRequestAmount::format(500000000.0));
        $this->assertSame("900000000", SepRequestAmount::format(900000000.0));
        $this->assertSame("780615714.9429096", SepRequestAmount::format(780615714.9429096));
    }

    public function testHalfStroopTieFollowsTheStoredFloat(): void
    {
        // The double nearest 1.5e-7 is 0.000000149999999999999993, so the nearer seven-decimal
        // value is one stroop, not two.
        $this->assertSame("0.0000001", SepRequestAmount::format(1.5e-7));
    }

    // Rounding to seven decimal places

    public function testMoreThanSevenDecimalsRoundsToSeven(): void
    {
        $this->assertSame("1.2345679", SepRequestAmount::format(1.23456789));
    }

    public function testBelowHalfAStroopRoundsToZero(): void
    {
        $this->assertSame("0", SepRequestAmount::format(0.00000004));
        $this->assertSame("0", SepRequestAmount::format(1e-10));
    }

    // Sign

    public function testNegativeAmountKeepsItsSign(): void
    {
        $this->assertSame("-0.0000123", SepRequestAmount::format(-0.0000123));
        $this->assertSame("-1000000000000000000000", SepRequestAmount::format(-1e21));
    }

    public function testZeroRendersWithoutSign(): void
    {
        $this->assertSame("0", SepRequestAmount::format(0.0));
        $this->assertSame("0", SepRequestAmount::format(-0.0));
        // An amount below half a stroop rounds away, and zero carries no sign.
        $this->assertSame("0", SepRequestAmount::format(-1e-8));
    }

    // Non-finite amounts

    public function testNonFiniteAmountRendersEmpty(): void
    {
        $this->assertSame("", SepRequestAmount::format(NAN));
        $this->assertSame("", SepRequestAmount::format(INF));
        $this->assertSame("", SepRequestAmount::format(-INF));
    }

    // Invariants

    public function testNoFiniteAmountRendersAnExponent(): void
    {
        $amounts = [1e-7, 0.0000123, 1e-10, 1e15, 1e16, 1e21, -1e21, -0.0000123, PHP_FLOAT_MAX];
        foreach ($amounts as $amount) {
            $rendered = SepRequestAmount::format($amount);
            $this->assertStringNotContainsStringIgnoringCase("e", $rendered, "exponent in " . $rendered);
        }
    }

    /**
     * Pins the output shape: digits, one optional period, an optional leading minus.
     *
     * This alone cannot tell a locale-independent rendering from a locale-following one. PHP starts
     * every process at LC_NUMERIC "C" and never adopts the host's regional format, so %f and %F
     * agree here; only a caller that has run setlocale() sees them diverge, which no test can
     * observe without changing the process locale. testFormatterUsesTheLocaleIndependentConversion
     * covers that separately.
     */
    public function testRenderingUsesAPeriodSeparator(): void
    {
        foreach ([0.0000123, 1e21, -0.0000123, 1e15, 1e-7, 500000000.0] as $amount) {
            $rendered = SepRequestAmount::format($amount);
            $this->assertSame(1, preg_match('/^-?[0-9]+(\.[0-9]+)?$/', $rendered),
                "unexpected characters in " . $rendered);
        }
    }

    /**
     * The formatter must use the locale-independent %F conversion.
     *
     * A caller that has run setlocale(LC_NUMERIC, ...) with a comma-separated locale gets
     * "0,0000123" from the lowercase %f and "0.0000123" from the uppercase %F, and an anchor
     * parses neither the comma nor a value it never expected. The difference is invisible under
     * the "C" locale every PHP process starts in, and the CI image installs no comma locale, so
     * this is asserted against the source rather than skipped on hosts that cannot show it.
     */
    public function testFormatterUsesTheLocaleIndependentConversion(): void
    {
        // Comments are stripped first: a docblock naming either conversion would otherwise decide
        // the outcome, letting a broken implementation pass because its comment mentions %F.
        $file = (new \ReflectionClass(SepRequestAmount::class))->getFileName();
        $code = '';
        foreach (token_get_all((string) file_get_contents((string) $file)) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $code .= is_array($token) ? $token[1] : $token;
        }

        $this->assertStringContainsString("sprintf('%.7F', \$amount)", $code);
        $this->assertDoesNotMatchRegularExpression('/%\.\d*f/', $code);
    }

    /**
     * A caller that has run setlocale() must still get a period.
     *
     * The unconditional assertion runs everywhere, including a CI image that installs no comma
     * locale. Where one is available the second assertion makes the check discriminating, because
     * the lowercase %f then renders a comma while the formatter must not.
     */
    public function testRenderingSurvivesACommaLocale(): void
    {
        $previous = setlocale(LC_NUMERIC, '0');
        $applied = setlocale(LC_NUMERIC, 'de_DE.UTF-8', 'de_DE', 'de_DE.utf8', 'fr_FR.UTF-8');

        try {
            $this->assertSame('0.0000123', SepRequestAmount::format(0.0000123));

            if ($applied !== false && sprintf('%.1f', 0.5) === '0,5') {
                $this->assertSame('0.0000123', SepRequestAmount::format(0.0000123));
                $this->assertSame('500000000', SepRequestAmount::format(500000000.0));
            }
        } finally {
            setlocale(LC_NUMERIC, $previous === false ? 'C' : $previous);
        }
    }
}
