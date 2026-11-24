<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Soroban;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Soroban\Exceptions\SorobanContractParserException;
use Soneso\StellarSDK\Soroban\SorobanContractParser;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrEncoder;
use Soneso\StellarSDK\Xdr\XdrSCEnvMetaEntry;
use Soneso\StellarSDK\Xdr\XdrSCMetaEntry;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntry;
use Soneso\StellarSDKTests\TestUtils;
use Throwable;

class SorobanParserSectionsTest extends TestCase
{
    private const ENV = 'contractenvmetav0';
    private const SPEC = 'contractspecv0';
    private const META = 'contractmetav0';

    public function testUnknownKindAtOffset85(): void
    {
        $bytes = "\x00\x61\x73\x6d\x01\x00\x00\x00"
            . "\x00\x1e\x11contractenvmetav0"
            . "\x00\x00\x00\x00\x00\x00\x00\x17\x00\x00\x00\x00"
            . "\x00\x2f\x0econtractspecv0"
            . "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x05hello\x00\x00\x00"
            . "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x06";
        $this->assertSame(89, strlen($bytes));
        $this->assertEntryFailure($bytes,
            'invalid byte code: the contractspecv0 entry at byte offset 85 does not decode: Unknown enum value: 6');
    }

    public function testShippedSectionsDecodeCompletely(): void
    {
        [, $parts] = self::fixture();
        $info = SorobanContractParser::parseContractByteCode(self::module($parts));
        $expected = self::decodeAll($parts[self::SPEC], XdrSCSpecEntry::class);
        $this->assertCount(count($expected), $info->specEntries);
        $this->assertEquals($expected, $info->specEntries);
        $env = self::decodeAll($parts[self::ENV], XdrSCEnvMetaEntry::class)[0]->interfaceVersion;
        $this->assertSame([$env->protocol, $env->preRelease], [$info->envMetaProtocol, $info->envMetaPreRelease]);
        $meta = [];
        foreach (self::decodeAll($parts[self::META], XdrSCMetaEntry::class) as $entry) {
            $meta[$entry->v0->key] = $entry->v0->value;
        }
        $this->assertSame($meta, $info->metaEntries);
        $other = self::decodeAll($parts[self::SPEC], XdrSCSpecEntry::class)[0];
        $other->functionV0->doc = "\xef\xbb\xbfdoc";
        $parts[self::SPEC] .= $other->encode();
        $parts[self::ENV] .= pack('N3', 0, 99, 0);
        $parts[self::META] .= self::kv('k', "\xef\xbb\xbfv");
        $info = SorobanContractParser::parseContractByteCode(self::module($parts));
        $this->assertCount(count($expected) + 1, $info->specEntries);
        $this->assertSame("\xef\xbb\xbfdoc", $info->specEntries[count($expected)]->functionV0->doc);
        $this->assertSame($env->protocol, $info->envMetaProtocol);
        $this->assertSame("\xef\xbb\xbfv", $info->metaEntries['k']);
    }

    #[DataProvider('sectionOrders')]
    public function testSectionOrderAndUnrelatedSections(array $order): void
    {
        [$prefix, $parts] = self::fixture();
        $module = $prefix;
        foreach ($order as $name) {
            $module .= TestUtils::wasmCustomSection($name, $parts[$name] ?? "\1\2\3");
        }
        $info = SorobanContractParser::parseContractByteCode($module);
        $this->assertEquals(self::decodeAll($parts[self::SPEC], XdrSCSpecEntry::class), $info->specEntries);
    }

    public function testUnframedMarkersAreRejected(): void
    {
        [, $parts] = self::fixture();
        $this->assertFramingFailure(self::ENV . $parts[self::ENV] . self::SPEC . $parts[self::SPEC],
            'invalid byte code: the wasm magic at byte offset 0 is 636f6e74, expected 0061736d.');
    }

    public static function sectionOrders(): array
    {
        return [
            [[self::SPEC, 'name', self::ENV, self::META]],
            [[self::META, self::ENV, self::SPEC]],
            [[self::ENV, self::META, self::SPEC]],
            [[self::ENV, self::SPEC, self::META]],
        ];
    }

    public function testFirstSpecAndEnvironmentAndAllMetaSections(): void
    {
        [, $parts] = self::fixture();
        $module = self::module($parts) . TestUtils::wasmCustomSection(self::SPEC, pack('N', 6))
            . TestUtils::wasmCustomSection(self::ENV, pack('N', 99)) . TestUtils::wasmCustomSection(self::META, self::kv('k', 'v') . self::kv('rsver', '2'));
        $info = SorobanContractParser::parseContractByteCode($module);
        $this->assertEquals(self::decodeAll($parts[self::SPEC], XdrSCSpecEntry::class), $info->specEntries);
        $this->assertSame(self::decodeAll($parts[self::ENV], XdrSCEnvMetaEntry::class)[0]->interfaceVersion->protocol, $info->envMetaProtocol);
        $this->assertSame([
            'rsver' => '2',
            'rssdkver' => '20.0.0#822ce6cc3e461ccc925275b472d77b6ca35b2cd9',
            'k' => 'v',
        ], $info->metaEntries);
    }

    #[DataProvider('metaLayouts')]
    public function testRepeatedSeps(bool $split): void
    {
        [, $parts] = self::fixture();
        $parts[self::META] = self::kv('sep', '41');
        $later = self::kv('sep', '40,46') . self::kv('sep', '');
        if (!$split) {
            $parts[self::META] .= $later;
        }
        $info = SorobanContractParser::parseContractByteCode(self::module($parts)
            . ($split ? TestUtils::wasmCustomSection(self::META, $later) : ''));
        $this->assertSame('41,40,46,', $info->metaEntries['sep']);
        $this->assertSame(['41', '40', '46'], $info->supportedSeps);
    }

    public static function metaLayouts(): array
    {
        return ['same section' => [false], 'separate sections' => [true]];
    }

    public function testDataMarkerDoesNotStartSpec(): void
    {
        [$prefix, $parts] = self::fixture();
        $segment = self::SPEC . pack('N', 6);
        $data = "\1\0\x41\0\x0b" . TestUtils::wasmUnsignedLeb128(strlen($segment)) . $segment;
        $info = SorobanContractParser::parseContractByteCode($prefix . "\x0b" . TestUtils::wasmUnsignedLeb128(strlen($data)) . $data
            . substr(self::module($parts), strlen($prefix)));
        $this->assertEquals(self::decodeAll($parts[self::SPEC], XdrSCSpecEntry::class), $info->specEntries);
    }

    #[DataProvider('specContents')]
    public function testPaddedLebAndEmptySpec(bool $emptySpec): void
    {
        [$prefix, $parts] = self::fixture();
        $parts[self::SPEC] = $emptySpec ? '' : $parts[self::SPEC];
        // Pad the size with zero groups without changing its value.
        $module = $prefix . "\0" . chr((15 + strlen($parts[self::SPEC])) | 128)
            . "\x80\x80\x80\0\x0e" . self::SPEC . $parts[self::SPEC]
            . TestUtils::wasmCustomSection(self::ENV, $parts[self::ENV]) . "\0\5\x80\x80\x80\x80\0"
            . "\0" . TestUtils::wasmUnsignedLeb128(5 + strlen(self::META) + strlen($parts[self::META]))
            . "\x8e\x80\x80\x80\0" . self::META . $parts[self::META];
        $info = SorobanContractParser::parseContractByteCode($module);
        $this->assertEquals(self::decodeAll($parts[self::SPEC], XdrSCSpecEntry::class), $info->specEntries);
        $this->assertSame([
            'rsver' => '1.74.1',
            'rssdkver' => '20.0.0#822ce6cc3e461ccc925275b472d77b6ca35b2cd9',
        ], $info->metaEntries);
    }

    public static function specContents(): array
    {
        return ['nonempty spec' => [false], 'empty spec' => [true]];
    }

    #[DataProvider('invalidEntries')]
    public function testInvalidEntry(string $section, string $suffix, bool $cut, bool $split, string $message): void
    {
        [$prefix, $parts] = self::fixture();
        $original = $parts[$section];
        $parts[$section] = $cut ? substr($original, 0, -1) : $original . $suffix;
        $module = $prefix . TestUtils::wasmCustomSection($section, $parts[$section]);
        unset($parts[$section]);
        foreach ($parts as $name => $content) {
            $module .= TestUtils::wasmCustomSection($name, $content);
        }
        if ($split) {
            $module .= TestUtils::wasmCustomSection(self::META, substr(self::kv('k', 'v'), 8));
        }
        $this->assertEntryFailure($module, $message);
    }

    public static function invalidEntries(): array
    {
        // The fixture prefix is 325 bytes. Spec and meta names add 17 framing bytes;
        // the environment name adds 20. Their contents are 52, 96 and 12 bytes.
        // The meta entries occupy 28 and 68 bytes, so its last entry starts at 370.
        return [
            'contractspecv0 unknown' => [self::SPEC, pack('N', 255), false, false,
                'invalid byte code: the contractspecv0 entry at byte offset 394 does not decode: Unknown enum value: 255'],
            'contractspecv0 trailing' => [self::SPEC, "\0\0", false, false,
                'invalid byte code: the contractspecv0 entry at byte offset 394 does not decode: Unexpected end of XDR data'],
            'contractspecv0 cut' => [self::SPEC, '', true, false,
                'invalid byte code: the contractspecv0 entry at byte offset 342 does not decode: Unexpected end of XDR data'],
            'contractenvmetav0 unknown' => [self::ENV, pack('N', 255), false, false,
                'invalid byte code: the contractenvmetav0 entry at byte offset 357 does not decode: Unknown enum value: 255'],
            'contractenvmetav0 trailing' => [self::ENV, "\0\0", false, false,
                'invalid byte code: the contractenvmetav0 entry at byte offset 357 does not decode: Unexpected end of XDR data'],
            'contractenvmetav0 cut' => [self::ENV, '', true, false,
                'invalid byte code: the contractenvmetav0 entry at byte offset 345 does not decode: Unexpected end of XDR data'],
            'contractmetav0 unknown' => [self::META, pack('N', 255), false, false,
                'invalid byte code: the contractmetav0 entry at byte offset 438 does not decode: Unknown enum value: 255'],
            'contractmetav0 trailing' => [self::META, "\0\0", false, false,
                'invalid byte code: the contractmetav0 entry at byte offset 438 does not decode: Unexpected end of XDR data'],
            'contractmetav0 cut' => [self::META, '', true, false,
                'invalid byte code: the contractmetav0 entry at byte offset 370 does not decode: Unexpected end of XDR data'],
            'malformed spec doc' => [self::SPEC, pack('N2', 0, 0xffffffff), false, false,
                'invalid byte code: the contractspecv0 entry at byte offset 394 does not decode: maxLength of 1024 exceeded (string is 4294967295 bytes)'],
            'split meta' => [self::META, substr(self::kv('k', 'v'), 0, 8), false, true,
                'invalid byte code: the contractmetav0 entry at byte offset 438 does not decode: Unexpected end of XDR data'],
        ];
    }

    #[DataProvider('invalidFraming')]
    public function testInvalidFraming(string $module, string $message): void
    {
        $this->assertFramingFailure($module, $message);
    }

    public static function invalidFraming(): array
    {
        [$prefix, $parts] = self::fixture();
        $valid = self::module($parts);
        // The complete fixture is 539 bytes; appended sections start there.
        return [
            'short header' => [substr($valid, 0, 7),
                'invalid byte code: the 7 bytes at byte offset 0 are shorter than the 8-byte wasm header.'],
            'one-byte header' => ["\x00",
                'invalid byte code: the 1 byte at byte offset 0 is shorter than the 8-byte wasm header.'],
            'magic' => [substr_replace($valid, 'n', 3, 1),
                'invalid byte code: the wasm magic at byte offset 0 is 0061736e, expected 0061736d.'],
            'version' => [substr_replace($valid, "\2", 4, 1),
                'invalid byte code: the wasm version at byte offset 4 is 02000000, expected 01000000.'],
            'past end' => [$valid . "\x0b\xe8\x07",
                'invalid byte code: the section with id 11 at byte offset 539 declares 1000 bytes, but 0 bytes remain.'],
            'one-byte declared size' => ["\x00\x61\x73\x6d\x01\x00\x00\x00\x0b\x01",
                'invalid byte code: the section with id 11 at byte offset 8 declares 1 byte, but 0 bytes remain.'],
            'one byte remaining' => ["\x00\x61\x73\x6d\x01\x00\x00\x00\x0b\x02\x00",
                'invalid byte code: the section with id 11 at byte offset 8 declares 2 bytes, but 1 byte remains.'],
            'cut leb' => [$valid . "\0\x80",
                'invalid byte code: the size of the section at byte offset 539 (id 0) starting at byte offset 540 ends at byte offset 541 before its last byte.'],
            'overflow' => [$valid . "\x0b\xff\xff\xff\xff\x1f",
                'invalid byte code: the size of the section at byte offset 539 (id 11) starting at byte offset 540 exceeds the u32 range.'],
            'six bytes' => [$valid . "\x0b\x80\x80\x80\x80\x80\0",
                'invalid byte code: the size of the section at byte offset 539 (id 11) starting at byte offset 540 is longer than 5 bytes.'],
            'max u32' => [$valid . "\x0b\xff\xff\xff\xff\x0f",
                'invalid byte code: the section with id 11 at byte offset 539 declares 4294967295 bytes, but 0 bytes remain.'],
            'name bounds' => [$valid . "\0\3\x0eco",
                'invalid byte code: the custom section at byte offset 539 declares a 14-byte name, but 2 bytes of the section remain.'],
            'one name byte remaining' => ["\x00\x61\x73\x6d\x01\x00\x00\x00\x00\x02\x02\x61",
                'invalid byte code: the custom section at byte offset 8 declares a 2-byte name, but 1 byte of the section remains.'],
            'cut name leb' => [$valid . "\0\1\x8e",
                'invalid byte code: the name length of the custom section at byte offset 539 starting at byte offset 541 ends at byte offset 542 before its last byte.'],
            'empty custom' => [$valid . "\0\0",
                'invalid byte code: the name length of the custom section at byte offset 539 starting at byte offset 541 ends at byte offset 541 before its last byte.'],
            'missing spec' => [self::module([self::ENV => $parts[self::ENV]]),
                'invalid byte code: spec entries not found.'],
            'missing env' => [self::module([self::SPEC => $parts[self::SPEC]]),
                'invalid byte code: environment meta not found.'],
            'empty first env' => [$prefix . TestUtils::wasmCustomSection(self::ENV, '') . substr($valid, strlen($prefix)),
                'invalid byte code: environment meta not found.'],
        ];
    }

    private function assertFramingFailure(string $module, string $message): void
    {
        try {
            SorobanContractParser::parseContractByteCode($module);
            $this->fail('Expected a parser exception');
        } catch (SorobanContractParserException $e) {
            $this->assertSame($message, $e->getMessage());
        }
    }

    private function assertEntryFailure(string $module, string $message): void
    {
        try {
            SorobanContractParser::parseContractByteCode($module);
            $this->fail('Expected a parser exception');
        } catch (SorobanContractParserException $e) {
            $this->assertSame($message, $e->getMessage());
            $this->assertInstanceOf(Throwable::class, $e->getPrevious());
        }
    }

    private static function fixture(): array
    {
        $bytes = file_get_contents(__DIR__ . '/../../wasm/soroban_hello_world_contract.wasm');
        $prefix = substr($bytes, 0, 8);
        $parts = [];
        $read = static function () use ($bytes, &$pos): int {
            $value = $shift = 0;
            do {
                $byte = ord($bytes[$pos++]);
                $value |= ($byte & 127) << $shift;
                $shift += 7;
            } while ($byte & 128);
            return $value;
        };
        for ($pos = 8; $pos < strlen($bytes); $pos = $end) {
            $start = $pos;
            $id = ord($bytes[$pos++]);
            $size = $read();
            $end = $pos + $size;
            if ($id === 0) {
                $length = $read();
                $name = substr($bytes, $pos, $length);
                $parts[$name] = substr($bytes, $pos + $length, $end - $pos - $length);
            } else {
                $prefix .= substr($bytes, $start, $end - $start);
            }
        }
        return [$prefix, $parts];
    }

    private static function decodeAll(string $bytes, string $class): array
    {
        $entries = [];
        while ($bytes !== '') {
            $entry = $class::decode(new XdrBuffer($bytes));
            $entries[] = $entry;
            $bytes = substr($bytes, strlen($entry->encode()));
        }
        return $entries;
    }

    private static function module(array $parts): string
    {
        return TestUtils::wasmModule($parts, self::fixture()[0]);
    }

    private static function kv(string $key, string $value): string
    {
        return pack('N', 0) . XdrEncoder::string($key) . XdrEncoder::string($value);
    }
}
