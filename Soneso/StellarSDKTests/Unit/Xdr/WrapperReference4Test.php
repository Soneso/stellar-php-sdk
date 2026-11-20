<?php declare(strict_types=1);

namespace Soneso\StellarSDKTests\Unit\Xdr;

use Closure;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrConfigUpgradeSetKey;
use Soneso\StellarSDK\Xdr\XdrInnerTransactionResultPair;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolDepositOperation;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolWithdrawOperation;
use Soneso\StellarSDKTests\Unit\Soroban\SecretSeedVariants;
use Throwable;

class Reference4ConfigUpgradeSetKey extends XdrConfigUpgradeSetKey {}
class Reference4LiquidityPoolDepositOperation extends XdrLiquidityPoolDepositOperation {}
class Reference4LiquidityPoolWithdrawOperation extends XdrLiquidityPoolWithdrawOperation {}
class Reference4InnerTransactionResultPair extends XdrInnerTransactionResultPair {}

class WrapperReference4Test extends TestCase
{
    /** Constructor parameters per type, with the XDR byte offset of each 32-byte hash field. */
    private const PARAMETERS = [
        'XdrConfigUpgradeSetKey' => ['contractID' => 0, 'contentHash' => 32],
        'XdrLiquidityPoolDepositOperation' => ['liquidityPoolID' => 0, 'maxAmountA' => null, 'maxAmountB' => null,
            'minPrice' => null, 'maxPrice' => null],
        'XdrLiquidityPoolWithdrawOperation' => ['liquidityPoolID' => 0, 'amount' => null, 'minAmountA' => null,
            'minAmountB' => null],
        'XdrInnerTransactionResultPair' => ['transactionHash' => 0, 'result' => null],
    ];

    /** The length of a "C..." or "L..." strkey. */
    private const STRKEY_LENGTH = 56;

    /** Four zero bytes ahead of a 32-byte id in hexadecimal, the input of the factory row new (hex72). */
    private const HEX72_PREFIX = '00000000';

    /** Label, text forms and strkey letter of each hash field in the error messages. */
    private const FIELDS = [
        'contractID' => ['Contract id', 'a "C..." strkey or 64 hexadecimal characters', 'C'],
        'contentHash' => ['Content hash', '64 hexadecimal characters', null],
        'liquidityPoolID' => ['Liquidity pool id', 'an "L..." strkey or 64 hexadecimal characters', 'L'],
        'transactionHash' => ['Transaction hash', '64 hexadecimal characters', null],
    ];

    private static function rows(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/fixtures/wrapper-reference4.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    /** @return list<class-string> the base, the wrapper and a subclass of the wrapper */
    private static function classes(string $type): array
    {
        $wrapper = 'Soneso\\StellarSDK\\Xdr\\' . $type;
        return [$wrapper . 'Base', $wrapper, __NAMESPACE__ . '\\Reference4' . substr($type, 3)];
    }

    /** @return array<string, string> the hash fields of $row in the form $class holds them */
    private static function held(array $row, string $class): array
    {
        $values = [];
        foreach (array_filter(self::PARAMETERS[$row['type']], 'is_int') as $field => $offset) {
            $raw = substr(base64_decode($row['b64'], true), $offset, 32);
            $values[$field] = str_ends_with($class, 'Base') ? $raw : bin2hex($raw);
        }
        return $values;
    }

    /** The object of $row built through the constructor of $class with $field set to $value. */
    private static function build(array $row, string $class, string $field, string $value): object
    {
        $decoded = $class::fromBase64Xdr($row['b64']);
        $arguments = [];
        foreach (array_keys(self::PARAMETERS[$row['type']]) as $parameter) {
            $arguments[] = $parameter === $field ? $value : $decoded->$parameter;
        }
        return new $class(...$arguments);
    }

    private static function assertWriters(array $row, object $object): void
    {
        self::assertSame(base64_decode($row['b64'], true), $object->encode());
        self::assertSame($row['b64'], $object->toBase64Xdr());
        self::assertSame($row['ref_json'], $object->toJson());
        self::assertSame(json_decode($row['ref_json'], true, 512, JSON_THROW_ON_ERROR), $object->toJsonValue());
        if (isset($row['txrep'])) {
            $lines = [];
            $object->toTxRep('x', $lines);
            self::assertSame($row['txrep'], $lines);
        }
    }

    private static function assertHeld(array $expected, object $object): void
    {
        foreach ($expected as $field => $value) {
            self::assertSame($value, $object->{'get' . ucfirst($field)}(), $field);
        }
    }

    /** @return iterable<string, array{array, class-string, string}> */
    public static function referenceCases(): iterable
    {
        foreach (self::rows() as $row) {
            $readers = isset($row['txrep']) ? ['decode', 'fromBase64Xdr', 'fromTxRep', 'fromJson', 'fromJsonValue']
                : ['decode', 'fromBase64Xdr', 'fromJson', 'fromJsonValue'];
            foreach (self::classes($row['type']) as $class) {
                foreach ($readers as $reader) {
                    yield $row['type'] . ':' . $row['label'] . ':' . $class . ':' . $reader => [$row, $class, $reader];
                }
            }
        }
    }

    #[DataProvider('referenceCases')]
    public function testReference(array $row, string $class, string $reader): void
    {
        $object = match ($reader) {
            'decode' => $class::decode(new XdrBuffer(base64_decode($row['b64'], true))),
            'fromBase64Xdr' => $class::fromBase64Xdr($row['b64']),
            'fromTxRep' => $class::fromTxRep($row['txrep'], 'x'),
            'fromJson' => $class::fromJson($row['ref_json']),
            'fromJsonValue' => $class::fromJsonValue(json_decode($row['ref_json'], true, 512, JSON_THROW_ON_ERROR)),
        };
        self::assertSame($class, get_class($object));
        self::assertHeld(self::held($row, $class), $object);
        self::assertWriters($row, $object);
        self::assertHeld(self::held($row, $class), $object);
    }

    /** @return iterable<string, array{array, class-string, string, string, bool}> */
    public static function inputCases(): iterable
    {
        foreach (self::rows() as $row) {
            foreach (self::classes($row['type']) as $class) {
                foreach (self::held($row, $class) as $field => $value) {
                    $inputs = ['held' => $value];
                    if (!str_ends_with($class, 'Base')) {
                        $inputs['upper case'] = strtoupper($value);
                        $raw = hex2bin($value);
                        match (self::FIELDS[$field][2]) {
                            'C' => $inputs['strkey'] = StrKey::encodeContractId($raw),
                            'L' => $inputs['strkey'] = StrKey::encodeLiquidityPoolId($raw),
                            null => null,
                        };
                    }
                    foreach ($inputs as $form => $input) {
                        foreach (['constructor' => false, 'setter' => true] as $via => $setter) {
                            yield implode(':', [$row['type'], $row['label'], $class, $field, $form, $via])
                                => [$row, $class, $field, $input, $setter];
                        }
                    }
                }
            }
        }
    }

    #[DataProvider('inputCases')]
    public function testInputForms(array $row, string $class, string $field, string $input, bool $setter): void
    {
        if ($setter) {
            $object = $class::fromBase64Xdr($row['b64']);
            $object->{'set' . ucfirst($field)}($input);
        } else {
            $object = self::build($row, $class, $field, $input);
        }
        self::assertWriters($row, $object);
        self::assertSame($input, $object->{'get' . ucfirst($field)}());
    }

    /** @return iterable<string, array{array, class-string, string}> */
    public static function txRepTextCases(): iterable
    {
        foreach (self::rows() as $row) {
            if (!isset($row['txrep'])) {
                continue;
            }
            foreach (array_slice(self::classes($row['type']), 1) as $class) {
                $hex = $row['txrep']['x.liquidityPoolID'];
                foreach ([StrKey::encodeLiquidityPoolId(hex2bin($hex)), strtoupper($hex)] as $line) {
                    yield $row['type'] . ':' . $row['label'] . ':' . $class . ':' . $line => [$row, $class, $line];
                }
            }
        }
    }

    #[DataProvider('txRepTextCases')]
    public function testTxRepReaderKeepsTheLineText(array $row, string $class, string $line): void
    {
        $object = $class::fromTxRep(['x.liquidityPoolID' => $line] + $row['txrep'], 'x');
        self::assertSame($line, $object->getLiquidityPoolID());
        self::assertWriters($row, $object);
        self::assertSame($line, $object->getLiquidityPoolID());
    }

    /** @return list<array> the first fixture row of each type */
    private static function typeRows(): array
    {
        $rows = [];
        foreach (self::rows() as $row) {
            $rows[$row['type']] ??= $row;
        }
        return array_values($rows);
    }

    /** @return array<string, Closure> every writer of $object, toTxRep() for a type with TxRep */
    private static function writers(array $row, object $object): array
    {
        $writers = [
            'encode' => fn () => $object->encode(),
            'toBase64Xdr' => fn () => $object->toBase64Xdr(),
            'toJson' => fn () => $object->toJson(),
            'toJsonValue' => fn () => $object->toJsonValue(),
        ];
        if (isset($row['txrep'])) {
            $writers['toTxRep'] = function () use ($object): void {
                $lines = [];
                $object->toTxRep('x', $lines);
            };
        }
        return $writers;
    }

    /** @return array<string, array{string, string}> invalid text values of a field with their messages */
    private static function textRejections(string $value, string $field): array
    {
        [$label, $forms, $letter] = self::FIELDS[$field];
        $text = static fn (string $input): string => $label . ' must be ' . $forms . ', "' . $input . '" ('
            . strlen($input) . ' characters) given';
        $invalid = [
            'new (hex72)' => [self::HEX72_PREFIX . $value, $text(self::HEX72_PREFIX . $value)],
            'new (not hex)' => [str_repeat('zz', 32), $text(str_repeat('zz', 32))],
            '62 hexadecimal' => [substr($value, 2), $text(substr($value, 2))],
            'empty' => ['', $text('')],
            'starts with S' => ['S' . substr($value, 1),
                $label . ' must be ' . $forms . ', a value starting with S (not shown, it may be a secret seed) given'],
        ];
        $strkeys = ['C' => StrKey::encodeContractId(hex2bin($value)), 'L' => StrKey::encodeLiquidityPoolId(hex2bin($value))];
        foreach ($strkeys as $other => $strkey) {
            if ($other !== $letter) {
                $invalid['strkey ' . $other] = [$strkey, $text($strkey)];
            }
        }
        if ($letter !== null) {
            $last = self::STRKEY_LENGTH - 1;
            $strkey = $strkeys[$letter];
            $broken = substr($strkey, 0, $last) . ($strkey[$last] === 'A' ? 'B' : 'A');
            $invalid['checksum'] = [$broken, $label . ' "' . $broken . '" is not ' . substr($forms, 0, strpos($forms, ' or '))
                . ': invalid checksum in encoded data'];
            $shaped = $letter . substr($value, 0, $last);
            if (ctype_xdigit($shaped)) {
                $invalid['56 hexadecimal'] = [$shaped, $text($shaped)];
            }
        }
        return $invalid;
    }

    /** @return iterable<string, array{array, class-string, string, string, string}> */
    public static function rejectionCases(): iterable
    {
        foreach (self::typeRows() as $row) {
            foreach (self::classes($row['type']) as $class) {
                foreach (self::held($row, $class) as $field => $value) {
                    $label = self::FIELDS[$field][0];
                    $invalid = str_ends_with($class, 'Base')
                        ? ['31 bytes' => [substr($value, 1), $label . ' must be 32 bytes; 31 bytes given'],
                            '33 bytes' => ["\0" . $value, $label . ' must be 32 bytes; 33 bytes given'],
                            'hexadecimal' => [bin2hex($value), $label . ' must be 32 bytes; 64 bytes given']]
                        : self::textRejections($value, $field);
                    foreach ($invalid as $name => [$input, $message]) {
                        yield implode(':', [$row['type'], $class, $field, $name]) => [$row, $class, $field, $input, $message];
                    }
                }
            }
        }
    }

    #[DataProvider('rejectionCases')]
    public function testEveryWriterRejects(array $row, string $class, string $field, string $input, string $message): void
    {
        $object = self::build($row, $class, $field, $input);
        foreach (self::writers($row, $object) as $name => $writer) {
            try {
                $writer();
                self::fail($name . ' accepted ' . $field);
            } catch (InvalidArgumentException $error) {
                self::assertSame($message, $error->getMessage(), $name);
            }
            self::assertSame($input, $object->{'get' . ucfirst($field)}(), $name);
        }
    }

    /** @return list<string> the forms of SecretSeedVariants::insideLongerValues() */
    private static function seedForms(): array
    {
        return array_keys(SecretSeedVariants::create()->insideLongerValues());
    }

    /** Runs $call and returns what it throws, or null. */
    private static function thrown(Closure $call): ?Throwable
    {
        try {
            $call();
        } catch (Throwable $error) {
            return $error;
        }
        return null;
    }

    /** @return iterable<string, array{array, class-string, string, string}> form labels only, never a seed */
    public static function seedCases(): iterable
    {
        foreach (self::typeRows() as $row) {
            foreach (array_slice(self::classes($row['type']), 1) as $class) {
                foreach (array_keys(self::held($row, $class)) as $field) {
                    foreach (self::seedForms() as $form) {
                        yield implode(':', [$row['type'], $class, $field, $form]) => [$row, $class, $field, $form];
                    }
                }
            }
        }
    }

    /**
     * A value holding a secret seed is refused by every writer with a message that names a seed
     * and shows none of it. The test never prints the seed: it asserts with fixed failure texts.
     */
    #[DataProvider('seedCases')]
    public function testEveryWriterRedactsASeed(array $row, string $class, string $field, string $form): void
    {
        $variants = SecretSeedVariants::create();
        $object = self::build($row, $class, $field, $variants->insideLongerValues()[$form]['input']);
        foreach (self::writers($row, $object) as $name => $writer) {
            $error = self::thrown($writer);
            self::assertTrue($error instanceof InvalidArgumentException, $name . ' did not throw InvalidArgumentException');
            self::assertTrue(str_contains($error->getMessage(), SecretSeedVariants::SECRET_SEED), $name . ' names no secret seed');
            $variants->assertNotShown($error, $name);
        }
    }

    /** @return iterable<string, array{array, string}> form labels only, never a seed */
    public static function baseTxRepSeedCases(): iterable
    {
        foreach (self::typeRows() as $row) {
            if (isset($row['txrep'])) {
                foreach (self::seedForms() as $form) {
                    yield $row['type'] . ':' . $form => [$row, $form];
                }
            }
        }
    }

    /**
     * The TxRep reader of a base decodes the hexadecimal line and refuses a line holding a secret
     * seed with a message that shows none of it.
     */
    #[DataProvider('baseTxRepSeedCases')]
    public function testBaseTxRepReaderRedactsASeed(array $row, string $form): void
    {
        $variants = SecretSeedVariants::create();
        $value = $variants->insideLongerValues()[$form];
        $base = self::classes($row['type'])[0];
        $error = self::thrown(fn () => $base::fromTxRep(['x.liquidityPoolID' => $value['input']] + $row['txrep'], 'x'));
        self::assertTrue($error instanceof InvalidArgumentException, 'fromTxRep() did not throw InvalidArgumentException');
        self::assertTrue($error->getMessage() === 'Invalid hex string: ' . $value['redacted'],
            'fromTxRep() does not name the line with the seed replaced');
        $variants->assertNotShown($error, 'fromTxRep()');
    }
}
