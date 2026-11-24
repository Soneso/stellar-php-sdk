<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Core;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Price;

/**
 * Unit tests for Price::fromString().
 */
class PriceTest extends TestCase
{
    public static function validPriceProvider(): array
    {
        return [
            'decimal 1.5' => ['1.5', 3, 2],
            'integer 1' => ['1', 1, 1],
            'quarter 0.25' => ['0.25', 1, 4],
            'max int32 2147483647' => ['2147483647', 2147483647, 1],
            'one stroop 0.0000001' => ['0.0000001', 1, 10000000],
            'tenth multiple 0.3' => ['0.3', 3, 10],
            'mixed quarter 2.75' => ['2.75', 11, 4],
            'repeating third 0.3333333333' => ['0.3333333333', 1, 3],
            'leading space' => [' 1', 1, 1],
            'trailing space' => ['1 ', 1, 1],
            'trailing newline' => ["1\n", 1, 1],
            'tab and trailing space' => ["\t1.5 ", 3, 2],
            'carriage return' => ["\r1\r", 1, 1],
            'vertical tab' => ["\v1.5\v", 3, 2],
            'form feed' => ["\f1.5\f", 3, 2],
            'scientific notation 1e5' => ['1e5', 100000, 1],
            'leading plus +1.5' => ['+1.5', 3, 2],
            'leading zeros 007' => ['007', 7, 1],
            'leading dot .5' => ['.5', 1, 2],
            'trailing dot 5.' => ['5.', 5, 1],
            'uppercase negative exponent 1E-3' => ['1E-3', 1, 1000],
        ];
    }

    #[DataProvider('validPriceProvider')]
    public function testFromStringValidControls(string $input, int $expectedN, int $expectedD): void
    {
        $price = Price::fromString($input);

        $this->assertInstanceOf(Price::class, $price);
        $this->assertSame($expectedN, $price->getN());
        $this->assertSame($expectedD, $price->getD());
    }

    public static function invalidPriceProvider(): array
    {
        return [
            'zero' => ['0'],
            'zero decimal' => ['0.0'],
            'negative zero' => ['-0'],
            'negative zero decimal' => ['-0.0'],
            'empty string' => [''],
            'non-numeric text' => ['abc'],
            'trailing text' => ['1abc'],
            'internal space' => ['1 5'],
            'spaces only' => ['   '],
            'all whitespace only' => [" \t\n\r\v\f"],
            'leading NUL byte' => ["\0" . '1'],
            'trailing NUL byte' => ['1' . "\0"],
            'non-breaking space' => ["\u{00A0}1"],
            'fraction syntax' => ['1/3'],
            'negative decimal' => ['-1.5'],
            'INF text' => ['INF'],
            'NAN text' => ['NAN'],
            'float overflow to INF' => ['1e309'],
            'reciprocal overflow to INF' => ['1e-309'],
            'second reciprocal overflow to INF' => ['1.7976931348623157e308'],
            'float underflow to zero' => ['1e-400'],
            'too small for 32-bit denominator 1e-10' => ['1e-10'],
            'just below 1/2147483647' => ['0.000000000465661287'],
            'above 32-bit numerator 2147483648' => ['2147483648'],
        ];
    }

    #[DataProvider('invalidPriceProvider')]
    public function testFromStringInvalidReturnsFalse(string $input): void
    {
        $this->assertFalse(Price::fromString($input));
    }
}
