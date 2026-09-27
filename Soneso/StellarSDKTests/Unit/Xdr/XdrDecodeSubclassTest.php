<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use Closure;
use TypeError;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Xdr\XdrAccountMergeOperation;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrContractCodeCostInputs;
use Soneso\StellarSDK\Xdr\XdrContractCodeEntryExtV1;
use Soneso\StellarSDK\Xdr\XdrDataValue;
use Soneso\StellarSDK\Xdr\XdrDataValueMandatory;
use Soneso\StellarSDK\Xdr\XdrExtensionPoint;
use Soneso\StellarSDK\Xdr\XdrMuxedAccount;
use Soneso\StellarSDK\Xdr\XdrThresholds;

/**
 * Hand-written XDR classes whose decode() declares the concrete class while fromBase64Xdr()
 * declares static, and application subclasses of them.
 *
 * decode() keeps working for an override that declares the concrete return type and for a
 * subclass with its own constructor. fromBase64Xdr() decodes the SDK class itself, builds a
 * subclass whose decode() override returns static (including one that implements an interface
 * declaring fromBase64Xdr() as returning static), and names the missing override for a subclass
 * that inherits decode().
 */
class XdrDecodeSubclassTest extends TestCase
{
    private const DESTINATION_ACCOUNT_ID = 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H';

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

    /**
     * @dataProvider sdkClasses
     * @param class-string $class
     * @param Closure(): object $fixture
     */
    public function testFromBase64XdrDecodesSdkClass(string $class, Closure $fixture): void
    {
        $original = $fixture();

        $decoded = $class::fromBase64Xdr($original->toBase64Xdr());

        $this->assertSame($class, get_class($decoded));
        $this->assertSame($original->encode(), $decoded->encode());
    }

    /**
     * @dataProvider staticDecodeSubclasses
     * @param class-string $class
     * @param Closure(): object $fixture
     */
    public function testInheritedFromBase64XdrBuildsSubclassWithStaticDecode(string $class, Closure $fixture): void
    {
        $original = $fixture();

        $decoded = $class::fromBase64Xdr($original->toBase64Xdr());

        $this->assertSame($class, get_class($decoded));
        $this->assertSame($original->encode(), $decoded->encode());
    }

    /**
     * @dataProvider inheritedDecodeSubclasses
     * @param class-string $class
     * @param class-string $parentClass
     * @param Closure(): object $fixture
     */
    public function testInheritedFromBase64XdrWithoutDecodeOverrideNamesTheOverride(
        string $class,
        string $parentClass,
        Closure $fixture,
    ): void {
        $base64 = $fixture()->toBase64Xdr();

        try {
            $class::fromBase64Xdr($base64);
        } catch (TypeError $e) {
            $this->assertSame(
                $class . '::fromBase64Xdr() needs a decode() override that returns ' . $class
                    . ', got ' . $parentClass,
                $e->getMessage()
            );
            return;
        }
        $this->fail('fromBase64Xdr() returned a parent instance for ' . $class);
    }

    /**
     * @return array<string, array{class-string, Closure(): object}>
     */
    public static function sdkClasses(): array
    {
        return [
            'XdrAccountMergeOperation' => [XdrAccountMergeOperation::class, self::accountMergeFixture()],
            'XdrContractCodeEntryExtV1' => [XdrContractCodeEntryExtV1::class, self::contractCodeEntryExtV1Fixture()],
            'XdrDataValue' => [XdrDataValue::class, self::dataValueFixture()],
            'XdrDataValueMandatory' => [XdrDataValueMandatory::class, self::dataValueMandatoryFixture()],
            'XdrThresholds' => [XdrThresholds::class, self::thresholdsFixture()],
        ];
    }

    /**
     * @return array<string, array{class-string, Closure(): object}>
     */
    public static function staticDecodeSubclasses(): array
    {
        return [
            'XdrAccountMergeOperation' => [StaticDecodeAccountMergeOperation::class, self::accountMergeFixture()],
            'XdrContractCodeEntryExtV1' => [StaticDecodeContractCodeEntryExtV1::class, self::contractCodeEntryExtV1Fixture()],
            'XdrDataValue through a static-returning factory interface' => [InterfaceDataValue::class, self::dataValueFixture()],
            'XdrDataValueMandatory' => [StaticDecodeDataValueMandatory::class, self::dataValueMandatoryFixture()],
            'XdrThresholds' => [StaticDecodeThresholds::class, self::thresholdsFixture()],
        ];
    }

    /**
     * @return array<string, array{class-string, class-string, Closure(): object}>
     */
    public static function inheritedDecodeSubclasses(): array
    {
        return [
            'XdrAccountMergeOperation' => [
                InheritingAccountMergeOperation::class,
                XdrAccountMergeOperation::class,
                self::accountMergeFixture(),
            ],
            'XdrContractCodeEntryExtV1' => [
                InheritingContractCodeEntryExtV1::class,
                XdrContractCodeEntryExtV1::class,
                self::contractCodeEntryExtV1Fixture(),
            ],
            'XdrDataValue' => [InheritingDataValue::class, XdrDataValue::class, self::dataValueFixture()],
            'XdrDataValueMandatory' => [
                InheritingDataValueMandatory::class,
                XdrDataValueMandatory::class,
                self::dataValueMandatoryFixture(),
            ],
            'XdrThresholds' => [InheritingThresholds::class, XdrThresholds::class, self::thresholdsFixture()],
        ];
    }

    private static function accountMergeFixture(): Closure
    {
        return static fn (): XdrAccountMergeOperation => new XdrAccountMergeOperation(
            new XdrMuxedAccount(KeyPair::fromAccountId(self::DESTINATION_ACCOUNT_ID)->getPublicKey())
        );
    }

    private static function contractCodeEntryExtV1Fixture(): Closure
    {
        return static fn (): XdrContractCodeEntryExtV1 => new XdrContractCodeEntryExtV1(
            new XdrExtensionPoint(0),
            new XdrContractCodeCostInputs(new XdrExtensionPoint(0), 45000, 12, 3, 1, 9, 2, 0, 4, 6, 512)
        );
    }

    private static function dataValueFixture(): Closure
    {
        return static fn (): XdrDataValue => new XdrDataValue("\x01\x02\x03");
    }

    private static function dataValueMandatoryFixture(): Closure
    {
        // The first bytes of a WebAssembly module: magic number and version 1.
        return static fn (): XdrDataValueMandatory => new XdrDataValueMandatory("\x00asm\x01\x00\x00\x00");
    }

    private static function thresholdsFixture(): Closure
    {
        // Master weight, low, medium and high threshold.
        return static fn (): XdrThresholds => new XdrThresholds("\x01\x02\x05\x0a");
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
 * Covariant decode() override that builds the subclass from the parent's result.
 */
class StaticDecodeAccountMergeOperation extends XdrAccountMergeOperation
{
    public static function decode(XdrBuffer $xdr): static
    {
        return new static(parent::decode($xdr)->getDestination());
    }
}

/**
 * Covariant decode() override that builds the subclass from the parent's result.
 */
class StaticDecodeContractCodeEntryExtV1 extends XdrContractCodeEntryExtV1
{
    public static function decode(XdrBuffer $xdr): static
    {
        $decoded = parent::decode($xdr);
        return new static($decoded->getExt(), $decoded->getCostInputs());
    }
}

/**
 * Covariant decode() override that builds the subclass from the parent's result.
 */
class StaticDecodeDataValueMandatory extends XdrDataValueMandatory
{
    public static function decode(XdrBuffer $xdr): static
    {
        return new static(parent::decode($xdr)->getValue());
    }
}

/**
 * Covariant decode() override that builds the subclass from the parent's result.
 */
class StaticDecodeThresholds extends XdrThresholds
{
    public static function decode(XdrBuffer $xdr): static
    {
        return new static(parent::decode($xdr)->thresholds);
    }
}

/**
 * Inherits fromBase64Xdr() and decode() unchanged.
 */
class InheritingAccountMergeOperation extends XdrAccountMergeOperation
{
}

/**
 * Inherits fromBase64Xdr() and decode() unchanged.
 */
class InheritingContractCodeEntryExtV1 extends XdrContractCodeEntryExtV1
{
}

/**
 * Inherits fromBase64Xdr() and decode() unchanged.
 */
class InheritingDataValue extends XdrDataValue
{
}

/**
 * Inherits fromBase64Xdr() and decode() unchanged.
 */
class InheritingDataValueMandatory extends XdrDataValueMandatory
{
}

/**
 * Inherits fromBase64Xdr() and decode() unchanged.
 */
class InheritingThresholds extends XdrThresholds
{
}
