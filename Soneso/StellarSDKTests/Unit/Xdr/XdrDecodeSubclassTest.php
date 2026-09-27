<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use TypeError;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrDataValue;

/**
 * Application subclasses of a hand-written XDR class keep working against its decoder and
 * factory: an override that declares the concrete return type loads and delegates to the
 * parent, a subclass with its own constructor inherits a decoder that builds the parent class,
 * a subclass may implement an interface that declares fromBase64Xdr() as returning static, and
 * fromBase64Xdr() on a subclass without a matching decode() override names the missing override.
 */
class XdrDecodeSubclassTest extends TestCase
{
    public function testDecodeOverrideWithConcreteReturnTypeDecodesPresentValue(): void
    {
        $bytes = (new XdrDataValue("\x01\x02\x03"))->encode();

        $decoded = ConcreteReturnDataValue::decode(new XdrBuffer($bytes));

        $this->assertSame(XdrDataValue::class, get_class($decoded));
        $this->assertSame("\x01\x02\x03", $decoded->getValue());
    }

    public function testDecodeOverrideWithConcreteReturnTypeDecodesAbsentValue(): void
    {
        $decoded = ConcreteReturnDataValue::decode(new XdrBuffer(pack('N', 0)));

        $this->assertSame(XdrDataValue::class, get_class($decoded));
        $this->assertNull($decoded->getValue());
    }

    public function testInheritedDecodeIgnoresSubclassConstructor(): void
    {
        $decoded = TaggedDataValue::decode(new XdrBuffer(pack('N', 0)));

        $this->assertSame(XdrDataValue::class, get_class($decoded));
        $this->assertNull($decoded->getValue());
    }

    public function testFactoryFromStaticReturningInterfaceBuildsSubclass(): void
    {
        $base64 = base64_encode((new XdrDataValue("\x01\x02\x03"))->encode());

        $decoded = InterfaceDataValue::fromBase64Xdr($base64);

        $this->assertSame(InterfaceDataValue::class, get_class($decoded));
        $this->assertSame("\x01\x02\x03", $decoded->getValue());
    }

    public function testInheritedFactoryWithoutDecodeOverrideNamesTheOverride(): void
    {
        $base64 = base64_encode(pack('N', 0));

        try {
            InheritingDataValue::fromBase64Xdr($base64);
        } catch (TypeError $e) {
            $this->assertSame(
                InheritingDataValue::class . '::fromBase64Xdr() needs a decode() override that returns '
                    . InheritingDataValue::class . ', got ' . XdrDataValue::class,
                $e->getMessage()
            );
            return;
        }
        $this->fail('fromBase64Xdr() returned a parent instance for ' . InheritingDataValue::class);
    }
}

/**
 * Factory contract an application may declare for its own XDR types.
 */
interface Base64XdrFactory
{
    public static function fromBase64Xdr(string $xdr): static;
}

/**
 * Overrides decode() with the concrete return type the SDK declares.
 */
class ConcreteReturnDataValue extends XdrDataValue
{
    public static function decode(XdrBuffer $xdr): XdrDataValue
    {
        return parent::decode($xdr);
    }
}

/**
 * Adds a required constructor argument and inherits decode().
 */
class TaggedDataValue extends XdrDataValue
{
    public function __construct(?string $value, public string $applicationTag)
    {
        parent::__construct($value);
    }
}

/**
 * Implements the static-returning factory contract through the inherited fromBase64Xdr() and a
 * covariant decode() override.
 */
class InterfaceDataValue extends XdrDataValue implements Base64XdrFactory
{
    public static function decode(XdrBuffer $xdr): static
    {
        return new static(parent::decode($xdr)->getValue());
    }
}

/**
 * Inherits fromBase64Xdr() and decode() unchanged.
 */
class InheritingDataValue extends XdrDataValue
{
}
