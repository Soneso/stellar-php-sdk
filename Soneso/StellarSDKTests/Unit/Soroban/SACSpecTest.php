<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Soroban;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;
use Soneso\StellarSDK\Soroban\Contract\SACClient;
use Soneso\StellarSDK\Soroban\Contract\SACSpec;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntry;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntryKind;
use Soneso\StellarSDK\Xdr\XdrSCSpecFunctionV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecType;

/**
 * Checks the embedded Stellar Asset Contract spec and its strict decoder.
 */
class SACSpecTest extends TestCase
{
    private const SPEC_SIZE = 9612;

    private const FUNCTION_NAMES = [
        'admin', 'allowance', 'approve', 'authorized', 'balance', 'burn', 'burn_from', 'clawback',
        'decimals', 'mint', 'name', 'set_admin', 'set_authorized', 'symbol', 'transfer',
        'transfer_from', 'trust',
    ];

    private const EVENT_NAMES = [
        'Approve', 'Burn', 'Clawback', 'Mint', 'MintWithAmountOnly', 'MintWithMuxedBytes',
        'MintWithMuxedString', 'SetAdmin', 'SetAuthorized', 'Transfer', 'TransferWithAmountOnly',
        'TransferWithMuxedBytes', 'TransferWithMuxedString',
    ];

    public function testDecodedConstantHasPinnedSizeAndDigest(): void
    {
        $bytes = base64_decode(SACSpec::XDR_BASE64, true);
        $this->assertIsString($bytes);
        $this->assertSame(self::SPEC_SIZE, strlen($bytes));
        $this->assertSame(SACSpec::SHA256, hash('sha256', $bytes));
    }

    public function testConstantsMatchThePin(): void
    {
        $pinJson = file_get_contents(__DIR__ . '/../../../../tools/sac-spec/pin.json');
        $this->assertIsString($pinJson);
        $pin = json_decode($pinJson, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($pin);
        $this->assertSame($pin['sha256'], SACSpec::SHA256);
        $this->assertSame($pin['commit'], SACSpec::UPSTREAM_COMMIT);
    }

    public function testSpecHasThirtyFunctionAndEventEntries(): void
    {
        $entries = SACClient::specEntries();
        $this->assertCount(30, $entries);
        $functionNames = [];
        $eventNames = [];
        foreach ($entries as $entry) {
            $this->assertInstanceOf(XdrSCSpecEntry::class, $entry);
            switch ($entry->type->value) {
                case XdrSCSpecEntryKind::SC_SPEC_ENTRY_FUNCTION_V0:
                    $this->assertNotNull($entry->functionV0);
                    $functionNames[] = $entry->functionV0->name;
                    break;
                case XdrSCSpecEntryKind::SC_SPEC_ENTRY_EVENT_V0:
                    $this->assertNotNull($entry->eventV0);
                    $eventNames[] = $entry->eventV0->name;
                    break;
                default:
                    $this->fail('Unexpected spec entry kind ' . $entry->type->value);
            }
        }
        sort($functionNames, SORT_STRING);
        sort($eventNames, SORT_STRING);
        $this->assertSame(self::FUNCTION_NAMES, $functionNames);
        $this->assertSame(self::EVENT_NAMES, $eventNames);
    }

    public function testSpecEntriesAreFreshObjectsOnEveryCall(): void
    {
        $first = SACClient::specEntries();
        $second = SACClient::specEntries();
        $this->assertCount(count($first), $second);
        foreach ($first as $index => $entry) {
            $this->assertNotSame($entry, $second[$index]);
            $this->assertSame($entry->encode(), $second[$index]->encode());
        }
    }

    public function testReEncodedEntriesEqualTheDecodedBytes(): void
    {
        $encoded = '';
        foreach (SACClient::specEntries() as $entry) {
            $encoded .= $entry->encode();
        }
        $this->assertSame(base64_decode(SACSpec::XDR_BASE64, true), $encoded);
    }

    public function testBalanceSignature(): void
    {
        $function = $this->function('balance');
        $this->assertInputs([['id', XdrSCSpecType::SC_SPEC_TYPE_ADDRESS]], $function);
        $this->assertOutputs([XdrSCSpecType::SC_SPEC_TYPE_I128], $function);
    }

    public function testDecimalsSignature(): void
    {
        $function = $this->function('decimals');
        $this->assertInputs([], $function);
        $this->assertOutputs([XdrSCSpecType::SC_SPEC_TYPE_U32], $function);
    }

    public function testNameSignature(): void
    {
        $function = $this->function('name');
        $this->assertInputs([], $function);
        $this->assertOutputs([XdrSCSpecType::SC_SPEC_TYPE_STRING], $function);
    }

    public function testSymbolSignature(): void
    {
        $function = $this->function('symbol');
        $this->assertInputs([], $function);
        $this->assertOutputs([XdrSCSpecType::SC_SPEC_TYPE_STRING], $function);
    }

    public function testTransferSignature(): void
    {
        $function = $this->function('transfer');
        $this->assertInputs([
            ['from', XdrSCSpecType::SC_SPEC_TYPE_ADDRESS],
            ['to', XdrSCSpecType::SC_SPEC_TYPE_MUXED_ADDRESS],
            ['amount', XdrSCSpecType::SC_SPEC_TYPE_I128],
        ], $function);
        $this->assertOutputs([], $function);
    }

    public function testTruncatedConstantThrowsTheDigestMessage(): void
    {
        $truncated = substr(SACSpec::XDR_BASE64, 0, -4);
        $bytes = base64_decode($truncated, true);
        $this->assertIsString($bytes);
        $actual = hash('sha256', $bytes);
        $this->assertNotSame(SACSpec::SHA256, $actual);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote(
            'The embedded SAC spec has sha256 ' . $actual . ', expected ' . SACSpec::SHA256, '/') . '\z/');
        $this->decodeSpec($truncated, SACSpec::SHA256);
    }

    public function testNonBase64StringThrowsTheBase64Message(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote(
            'The embedded SAC spec is not valid base64', '/') . '\z/');
        $this->decodeSpec('not base64!', SACSpec::SHA256);
    }

    public function testTrailingBytesThrowTheOffsetMessage(): void
    {
        $bytes = base64_decode(SACSpec::XDR_BASE64, true) . str_repeat("\xFF", 4);
        try {
            $this->decodeSpec(base64_encode($bytes), hash('sha256', $bytes));
            $this->fail('Expected a RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertStringStartsWith('The embedded SAC spec does not decode at byte offset 9612: ',
                $e->getMessage());
            $this->assertNotNull($e->getPrevious());
        }
    }

    public function testNonCanonicalEntryThrowsTheOffsetMessage(): void
    {
        // A non-zero XDR padding byte decodes but re-encodes as zero, so the entry does not
        // reproduce its bytes. Corrupt the doc padding of a later function entry, so the offset is not 0.
        $bytes = base64_decode(SACSpec::XDR_BASE64, true);
        $this->assertIsString($bytes);
        $offset = 0;
        $target = null;
        foreach (SACClient::specEntries() as $entry) {
            $docLength = $entry->functionV0 !== null ? strlen($entry->functionV0->doc) : 0;
            if ($offset > 0 && $docLength % 4 !== 0) {
                $target = $offset;
                break;
            }
            $offset += strlen($entry->encode());
        }
        $this->assertNotNull($target);
        $this->assertGreaterThan(0, $target);
        $entry = XdrSCSpecEntry::decode(new XdrBuffer(substr($bytes, $target)));
        $this->assertNotNull($entry->functionV0);
        $paddingIndex = $target + 8 + strlen($entry->functionV0->doc);
        $this->assertSame("\x00", $bytes[$paddingIndex]);
        $bytes[$paddingIndex] = "\x01";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote(
            "The embedded SAC spec does not decode at byte offset {$target}: "
            . 'the entry does not re-encode to the bytes it was decoded from', '/') . '\z/');
        $this->decodeSpec(base64_encode($bytes), hash('sha256', $bytes));
    }

    public function testDecodeSpecAcceptsTheEmbeddedConstant(): void
    {
        $this->assertCount(30, $this->decodeSpec(SACSpec::XDR_BASE64, SACSpec::SHA256));
    }

    /**
     * @return array<XdrSCSpecEntry>
     */
    private function decodeSpec(string $base64, string $sha256): array
    {
        $method = new ReflectionMethod(SACClient::class, 'decodeSpec');
        $result = $method->invoke(null, $base64, $sha256);
        $this->assertIsArray($result);
        return $result;
    }

    private function function(string $name): XdrSCSpecFunctionV0
    {
        foreach (SACClient::specEntries() as $entry) {
            if ($entry->functionV0 !== null && $entry->functionV0->name === $name) {
                return $entry->functionV0;
            }
        }
        $this->fail("The spec has no function $name");
    }

    /**
     * @param array<array{0: string, 1: int}> $expected
     */
    private function assertInputs(array $expected, XdrSCSpecFunctionV0 $function): void
    {
        $actual = [];
        foreach ($function->inputs as $input) {
            $actual[] = [$input->name, $input->type->type->value];
        }
        $this->assertSame($expected, $actual, "inputs of {$function->name}");
    }

    /**
     * @param array<int> $expected
     */
    private function assertOutputs(array $expected, XdrSCSpecFunctionV0 $function): void
    {
        $actual = [];
        foreach ($function->outputs as $output) {
            $actual[] = $output->type->value;
        }
        $this->assertSame($expected, $actual, "outputs of {$function->name}");
    }
}
