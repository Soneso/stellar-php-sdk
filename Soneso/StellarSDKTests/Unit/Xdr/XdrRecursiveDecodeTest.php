<?php declare(strict_types=1);

namespace Soneso\StellarSDKTests\Unit\Xdr;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\{XdrBuffer, XdrSCVal, XdrClaimPredicate, XdrSorobanAuthorizedInvocation,
    XdrSorobanDelegateSignature, XdrSCAddress, XdrSCValType};

class XdrRecursiveDecodeTest extends TestCase
{
    private string|false $nestingLimit;

    protected function setUp(): void
    {
        // The debugger's call-stack limit must allow the XDR budget to be reached.
        $this->nestingLimit = ini_set('xdebug.max_nesting_level', '3000');
    }

    protected function tearDown(): void
    {
        if ($this->nestingLimit !== false) {
            ini_set('xdebug.max_nesting_level', $this->nestingLimit);
        }
    }

    private function payload(string $kind, int $depth): string
    {
        if ($kind === 'predicate') {
            return str_repeat(pack('N*', 3, 1), $depth - 1) . pack('N', 0);
        }
        if ($kind === 'invocation') {
            $function = pack('N', 0) . XdrSCAddress::forContractId(str_repeat('00', 32))->encode()
                . pack('N*', 0, 0);
            return str_repeat($function . pack('N', 1), $depth - 1) . $function . pack('N', 0);
        }
        $bytes = '';
        while ($kind === 'map' && $depth > 2) {
            $bytes .= pack('N*', 17, 1, 1, 1);
            $depth -= 2;
        }
        return $bytes . str_repeat(pack('N*', 16, 1, 1), $depth - 1) . pack('N*', 16, 1, 0);
    }

    private function decode(string $kind, XdrBuffer $buffer): object
    {
        return match ($kind) {
            'predicate' => XdrClaimPredicate::decode($buffer),
            'invocation' => XdrSorobanAuthorizedInvocation::decode($buffer),
            default => XdrSCVal::decode($buffer),
        };
    }

    private function inspect(string $kind, object $node, int $depth): void
    {
        while ($depth > 0) {
            if ($kind === 'predicate') {
                self::assertSame($depth === 1 ? 0 : 3, $node->type->getValue());
                $node = $node->notPredicate;
            } elseif ($kind === 'invocation') {
                self::assertSame('', $node->function->contractFn->functionName);
                self::assertSame([], $node->function->contractFn->args);
                self::assertCount($depth === 1 ? 0 : 1, $node->subInvocations);
                $node = $node->subInvocations[0] ?? null;
            } elseif ($kind === 'map' && $depth > 2) {
                self::assertSame(XdrSCValType::SCV_MAP, $node->type->getValue());
                self::assertCount(1, $node->map);
                self::assertSame(XdrSCValType::SCV_VOID, $node->map[0]->key->type->getValue());
                $node = $node->map[0]->val;
                $depth--;
            } else {
                self::assertSame(XdrSCValType::SCV_VEC, $node->type->getValue());
                self::assertCount($depth === 1 ? 0 : 1, $node->vec);
                $node = $node->vec[0] ?? null;
            }
            $depth--;
        }
        self::assertNull($node);
    }

    private function reject(callable $decode, int $limit): void
    {
        try {
            $decode();
            self::fail('Expected depth exception at limit ' . $limit);
        } catch (InvalidArgumentException $e) {
            self::assertStringContainsString('recursion limit (' . $limit . ')', $e->getMessage());
        }
    }

    public function testBoundariesAndBufferReuse(): void
    {
        foreach (['vec', 'map', 'predicate', 'invocation'] as $kind) {
            foreach ([500, 501] as $depth) {
                $first = $this->payload($kind, $depth);
                $buffer = new XdrBuffer($first . $this->payload($kind, 500));
                if ($depth === 501) {
                    $this->reject(fn() => $this->decode($kind, $buffer), 500);
                    $position = (new \ReflectionProperty(XdrBuffer::class, 'position'))->getValue($buffer);
                    $buffer->readOpaqueFixed(strlen($first) - $position);
                } else {
                    $this->inspect($kind, $this->decode($kind, $buffer), 500);
                }
                self::assertSame(0, $buffer->getRecursionDepth());
                $this->inspect($kind, $this->decode($kind, $buffer), 500);
                self::assertSame(0, $buffer->getRecursionDepth());
            }
        }
    }

    private function delegates(int $depth, int $signatureDepth): string
    {
        $address = XdrSCAddress::forContractId(str_repeat('00', 32))->encode();
        return str_repeat($address . $this->payload('vec', 1) . pack('N', 1), $depth - 1)
            . $address . $this->payload('vec', $signatureDepth) . pack('N', 0);
    }

    public function testDelegateBoundaryAndSharedBudget(): void
    {
        foreach ([[128, 1, 0], [129, 1, 128], [128, 372, 0], [128, 373, 500]] as [$depth, $sig, $limit]) {
            $first = $this->delegates($depth, $sig);
            $buffer = new XdrBuffer($first . $this->delegates(128, 372));
            if ($limit) {
                $this->reject(fn() => XdrSorobanDelegateSignature::decode($buffer), $limit);
                $position = (new \ReflectionProperty(XdrBuffer::class, 'position'))->getValue($buffer);
                $buffer->readOpaqueFixed(strlen($first) - $position);
            } else {
                $this->inspectDelegates(XdrSorobanDelegateSignature::decode($buffer), $depth, $sig);
            }
            self::assertSame(0, $buffer->getRecursionDepth());
            $this->inspectDelegates(XdrSorobanDelegateSignature::decode($buffer), 128, 372);
            self::assertSame(0, $buffer->getRecursionDepth());
        }
    }

    private function inspectDelegates(XdrSorobanDelegateSignature $node, int $depth, int $signature): void
    {
        for ($i = 1; $i <= $depth; $i++) {
            $this->inspect('vec', $node->signature, $i === $depth ? $signature : 1);
            self::assertCount($i === $depth ? 0 : 1, $node->nestedDelegates);
            $node = $node->nestedDelegates[0] ?? null;
        }
    }

    public function testSiblingBreadthDoesNotConsumeDepth(): void
    {
        $bytes = pack('N*', 16, 1, 600) . str_repeat($this->payload('vec', 1), 600);
        $buffer = new XdrBuffer($bytes . $this->payload('vec', 501));
        $root = XdrSCVal::decode($buffer);
        self::assertCount(600, $root->vec);
        foreach ($root->vec as $child) {
            $this->inspect('vec', $child, 1);
        }
        self::assertSame(0, $buffer->getRecursionDepth());
        $this->reject(fn() => XdrSCVal::decode($buffer), 500);
    }
}
