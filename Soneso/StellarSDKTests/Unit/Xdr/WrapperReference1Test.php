<?php declare(strict_types=1);

namespace Soneso\StellarSDKTests\Unit\Xdr;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrManageDataOperation;
use Soneso\StellarSDK\Xdr\XdrTimeBounds;
use Soneso\StellarSDK\Xdr\XdrTransaction;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrTransactionV0;

class Reference1Transaction extends XdrTransaction {}
class Reference1TransactionV0 extends XdrTransactionV0 {}
class Reference1ManageDataOperation extends XdrManageDataOperation {}
class Reference1TimeBounds extends XdrTimeBounds {}
class Reference1TransactionEnvelope extends XdrTransactionEnvelope {}

class WrapperReference1Test extends TestCase
{
    /** @return iterable<string, array{array, class-string, string}> */
    public static function referenceCases(): iterable
    {
        $rows = json_decode(file_get_contents(__DIR__ . '/fixtures/wrapper-reference1.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($rows as $row) {
            $wrapper = 'Soneso\\StellarSDK\\Xdr\\' . $row['type'];
            $subclass = __NAMESPACE__ . '\\Reference1' . substr($row['type'], 3);
            foreach ([$wrapper . 'Base', $wrapper, $subclass] as $class) {
                foreach (['decode', 'fromBase64Xdr', 'fromTxRep', 'fromJson', 'fromJsonValue'] as $reader) {
                    yield $row['type'] . ':' . $row['label'] . ':' . $class . ':' . $reader => [$row, $class, $reader];
                }
            }
        }
    }

    #[DataProvider('referenceCases')]
    public function testReference(array $row, string $class, string $reader): void
    {
        $zone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');
        try {
            $bytes = base64_decode($row['b64'], true);
            $jsonValue = json_decode($row['ref_json'], true, 512, JSON_THROW_ON_ERROR);
            $object = match ($reader) {
                'decode' => $class::decode(new XdrBuffer($bytes)),
                'fromBase64Xdr' => $class::fromBase64Xdr($row['b64']),
                'fromTxRep' => $class::fromTxRep($row['txrep'], 'x'),
                'fromJson' => $class::fromJson($row['ref_json']),
                'fromJsonValue' => $class::fromJsonValue($jsonValue),
            };
            self::assertSame($class, get_class($object));
            self::assertSame($bytes, $object->encode());
            self::assertSame($row['b64'], $object->toBase64Xdr());
            self::assertSame($row['ref_json'], $object->toJson());
            self::assertSame($jsonValue, $object->toJsonValue());
            $lines = [];
            $object->toTxRep('x', $lines);
            self::assertSame($row['txrep'], $lines);
            if ($object instanceof XdrTimeBounds) {
                self::assertSame('+00:00', $object->getMinTime()->getTimezone()->getName());
                self::assertSame('+00:00', $object->getMaxTime()->getTimezone()->getName());
                self::assertSame($object->getMinTimestamp(), $object->getMinTime()->getTimestamp());
                self::assertSame($object->getMaxTimestamp(), $object->getMaxTime()->getTimestamp());
            }
        } finally {
            date_default_timezone_set($zone);
        }
    }

    /** @return iterable<string, array{class-string, mixed, string}> */
    public static function invalidJsonCases(): iterable
    {
        $rows = json_decode(file_get_contents(__DIR__ . '/fixtures/wrapper-reference1.json'), true, 512, JSON_THROW_ON_ERROR);
        $examples = [];
        foreach ($rows as $row) {
            $examples[$row['type']] ??= json_decode($row['ref_json'], true, 512, JSON_THROW_ON_ERROR);
        }
        unset($examples['XdrTransactionEnvelope']);
        foreach ($examples as $type => $value) {
            $class = 'Soneso\\StellarSDK\\Xdr\\' . $type;
            yield $type . ':shape' => [$class, false, 'Expected object for ' . $type . ' JSON value, got bool'];
            foreach (array_keys($value) as $key) {
                $missing = $value;
                unset($missing[$key]);
                yield $type . ':missing:' . $key => [$class, $missing, 'Missing required field ' . $key . ' for ' . $type];
            }
            $missing = $value;
            $missing[array_key_first($value)] = false;
            $last = array_key_last($value);
            unset($missing[$last]);
            yield $type . ':required-first' => [$class, $missing, 'Missing required field ' . $last . ' for ' . $type];
        }
        foreach ([
            ['XdrTransaction', 'fee', 'int'],
            ['XdrTransaction', 'operations', 'JSON array'],
            ['XdrTransactionV0', 'source_account_ed25519', 'hex string'],
            ['XdrTransactionV0', 'fee', 'int'],
            ['XdrTransactionV0', 'operations', 'JSON array'],
            ['XdrManageDataOperation', 'data_name', 'string'],
            ['XdrManageDataOperation', 'data_value', 'hex string or null'],
            ['XdrTimeBounds', 'min_time', 'uint64 JSON value (string or int)'],
            ['XdrTimeBounds', 'max_time', 'uint64 JSON value (string or int)'],
        ] as [$type, $key, $expected]) {
            $value = $examples[$type];
            $value[$key] = false;
            yield $type . ':type:' . $key => ['Soneso\\StellarSDK\\Xdr\\' . $type, $value, 'Expected ' . $expected . ' for ' . $key . ', got bool'];
        }
        yield 'time-bound-types-before-conversion' => [XdrTimeBounds::class,
            ['min_time' => 'invalid', 'max_time' => false],
            'Expected uint64 JSON value (string or int) for max_time, got bool'];
    }

    #[DataProvider('invalidJsonCases')]
    public function testJsonValidation(string $class, mixed $value, string $message): void
    {
        try {
            $class::fromJsonValue($value);
            self::fail('Expected rejection of invalid JSON');
        } catch (\InvalidArgumentException $error) {
            self::assertSame($message, $error->getMessage());
        }
    }
}
