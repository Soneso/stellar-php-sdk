<?php declare(strict_types=1);

namespace Soneso\StellarSDKTests\Unit\Xdr;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrDataValue;
use Soneso\StellarSDK\Xdr\XdrFloodAdvert;
use Soneso\StellarSDK\Xdr\XdrManageDataOperation;
use Soneso\StellarSDK\Xdr\XdrSponsorshipDescriptor;
use Soneso\StellarSDK\Xdr\XdrStellarMessage;
use Soneso\StellarSDK\Xdr\XdrTxAdvertVector;
use Soneso\StellarSDK\Xdr\XdrTxDemandVector;

class XdrTypedefWireFormatTest extends TestCase
{
    private const TWO_HASHES = 'AAAAAgEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgI=';

    public function testPresentDescriptorUsesPresenceFlag(): void
    {
        $wire = 'AAAAAQAAAAA/DDS/k60NmXHQTMyQ9wVRHIOKrZc0pKL7DXoD/H/omg==';
        $descriptor = XdrSponsorshipDescriptor::fromBase64Xdr($wire);
        $this->assertSame($wire, $descriptor->toBase64Xdr());
        $json = '"GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ"';
        $this->assertSame($json, $descriptor->toJson());
        $this->assertSame($wire, XdrSponsorshipDescriptor::fromJson($json)->toBase64Xdr());
        $key = $descriptor->getSponsorshipDescriptor();
        $this->assertSame('AAAAAD8MNL+TrQ2ZcdBMzJD3BVEcg4qtlzSkovsNegP8f+ia', $key->toBase64Xdr());
        $descriptor->setSponsorshipDescriptor(null);
        $this->assertNull($descriptor->getSponsorshipDescriptor());
        $descriptor->setSponsorshipDescriptor($key);
        $this->assertSame($wire, $descriptor->toBase64Xdr());
    }

    public function testAbsentDescriptorUsesNull(): void
    {
        $descriptor = XdrSponsorshipDescriptor::fromBase64Xdr('AAAAAA==');
        $this->assertSame('AAAAAA==', $descriptor->toBase64Xdr());
        $this->assertNull($descriptor->getSponsorshipDescriptor());
        $this->assertNull($descriptor->toJsonValue());
        $this->assertSame('null', $descriptor->toJson());
        $this->assertSame('AAAAAA==', XdrSponsorshipDescriptor::fromJson('null')->toBase64Xdr());
        $this->assertSame('AAAAAA==', (new XdrSponsorshipDescriptor(null))->toBase64Xdr());
    }

    public static function vectors(): array
    {
        return [[XdrTxAdvertVector::class, 'txAdvertVector'], [XdrTxDemandVector::class, 'txDemandVector']];
    }

    #[DataProvider('vectors')]
    public function testHashesUseFixedWidth(string $class, string $field): void
    {
        $vector = $class::fromBase64Xdr(self::TWO_HASHES);
        $this->assertSame([str_repeat("\x01", 32), str_repeat("\x02", 32)], $vector->$field);
        $this->assertSame(self::TWO_HASHES, $vector->toBase64Xdr());
    }

    #[DataProvider('vectors')]
    public function testZeroHashKeepsBytes(string $class, string $field): void
    {
        $wire = "\x00\x00\x00\x01" . str_repeat("\x00", 32);
        $vector = $class::fromBase64Xdr(base64_encode($wire));
        $this->assertSame([str_repeat("\x00", 32)], $vector->$field);
        $this->assertSame($wire, $vector->encode());
    }

    #[DataProvider('vectors')]
    public function testRejectsShortHash(string $class): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/\AUnexpected length for value\. Has length 31, expected 32\z/');
        (new $class([str_repeat("\x01", 31)]))->encode();
    }

    public function testFloodAdvertKeepsHashes(): void
    {
        $advert = XdrFloodAdvert::fromBase64Xdr(self::TWO_HASHES);
        $this->assertSame([str_repeat("\x01", 32), str_repeat("\x02", 32)], $advert->getTxHashes()->txAdvertVector);
        $this->assertSame(self::TWO_HASHES, $advert->toBase64Xdr());
    }

    public function testFloodDemandMessageKeepsHashes(): void
    {
        $wire = 'AAAAEwAAAAEDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAw==';
        $message = XdrStellarMessage::fromBase64Xdr($wire);
        $this->assertSame([str_repeat("\x03", 32)], $message->getFloodDemand()->getTxHashes()->txDemandVector);
        $this->assertSame($wire, $message->toBase64Xdr());
    }

    public static function dataValues(): array
    {
        return [['', '0000000100000000'], ['0', '000000010000000130000000'], [null, '00000000']];
    }

    #[DataProvider('dataValues')]
    public function testDataValueEncodesEmptyAndZeroAsPresent(?string $value, string $hex): void
    {
        $this->assertSame($hex, bin2hex((new XdrDataValue($value))->encode()));
        $this->assertSame($value, XdrDataValue::fromBase64Xdr(base64_encode(hex2bin($hex)))->getValue());
    }

    #[DataProvider('dataValues')]
    public function testManageDataOperationKeepsBytes(?string $value, string $hex): void
    {
        $operation = new XdrManageDataOperation('key', new XdrDataValue($value));
        $wire = hex2bin('000000036b657900' . $hex);
        $this->assertSame($wire, $operation->encode());
        $this->assertSame($value, XdrManageDataOperation::fromBase64Xdr(base64_encode($wire))->getValue()->getValue());
    }
}
