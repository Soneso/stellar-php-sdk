<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use InvalidArgumentException;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\AllowTrustOperationBuilder;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\FeeBumpTransactionBuilder;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceEntryExtV1;
use Soneso\StellarSDK\Xdr\XdrContractCostParamEntry;
use Soneso\StellarSDK\Xdr\XdrContractCostParams;
use Soneso\StellarSDK\Xdr\XdrContractExecutable;
use Soneso\StellarSDK\Xdr\XdrEnvelopeType;
use Soneso\StellarSDK\Xdr\XdrExtensionPoint;
use Soneso\StellarSDK\Xdr\XdrSCContractInstance;
use Soneso\StellarSDK\Xdr\XdrSCMapEntry;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrSequenceNumber;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrTransactionResult;
use Soneso\StellarSDK\Xdr\XdrTransactionResultCode;
use Soneso\StellarSDK\Xdr\XdrTransactionResultExt;
use Soneso\StellarSDK\Xdr\XdrTransactionResultResult;
use Soneso\StellarSDK\Xdr\XdrTransactionV0;
use Soneso\StellarSDK\Xdr\XdrTransactionV0Envelope;
use Soneso\StellarSDK\Xdr\XdrTrustLineEntryExtensionV2;

/**
 * Decoding of malformed XDR through the public decoders: array counts the remaining bytes
 * cannot hold, union discriminants without a matching arm and non-zero extension points.
 *
 * Each vector is built with the SDK's own encoder and patched at a computed offset; the test
 * checks the original bytes at that offset before patching.
 */
class XdrMalformedInputTest extends TestCase
{
    /** Packs as the bytes ff ff ff ff on 32-bit and 64-bit PHP alike. */
    private const NEGATIVE_COUNT = -1;

    public function testTransactionOperationsCountBeyondRemainingBytesIsRejected(): void
    {
        [$bytes, $countOffset] = $this->transactionOperationsCountVector();
        $remaining = strlen($bytes) - $countOffset - 4;
        $count = $this->countAboveBound($remaining);

        $this->expectCountRejected($count, $remaining);
        XdrTransactionEnvelope::fromEnvelopeBase64XdrString(base64_encode($this->patchCount($bytes, $countOffset, $count)));
    }

    public function testTransactionOperationsNegativeCountIsRejected(): void
    {
        [$bytes, $countOffset] = $this->transactionOperationsCountVector();

        $this->expectNegativeCountRejected();
        XdrTransactionEnvelope::fromEnvelopeBase64XdrString(base64_encode($this->patchCount($bytes, $countOffset, self::NEGATIVE_COUNT)));
    }

    public function testTransactionV0OperationsCountBeyondRemainingBytesIsRejected(): void
    {
        $transaction = $this->paymentTransaction();
        $v0 = new XdrTransactionV0(
            KeyPair::fromAccountId($transaction->getSourceAccount()->getAccountId())->getPublicKey(),
            new XdrSequenceNumber(new BigInteger(124)),
            [$transaction->getOperations()[0]->toXdr()],
        );
        $bytes = pack('N', XdrEnvelopeType::ENVELOPE_TYPE_TX_V0) . (new XdrTransactionV0Envelope($v0, []))->encode();
        // Envelope type, source account key (32), fee, sequence number (8), time bounds flag and
        // memo precede the operation count.
        $countOffset = 56;
        $this->assertSame(pack('N', 1), substr($bytes, $countOffset, 4));
        $remaining = strlen($bytes) - $countOffset - 4;
        $count = $this->countAboveBound($remaining);

        $this->expectCountRejected($count, $remaining);
        XdrTransactionEnvelope::fromEnvelopeBase64XdrString(base64_encode($this->patchCount($bytes, $countOffset, $count)));
    }

    public function testEnvelopeSignaturesCountBeyondRemainingBytesIsRejected(): void
    {
        $bytes = base64_decode($this->paymentTransaction()->toEnvelopeXdrBase64());
        // The empty signature list ends the unsigned envelope.
        $countOffset = strlen($bytes) - 4;
        $this->assertSame(pack('N', 0), substr($bytes, $countOffset, 4));

        $this->expectCountRejected(1, 0);
        XdrTransactionEnvelope::fromEnvelopeBase64XdrString(base64_encode($this->patchCount($bytes, $countOffset, 1)));
    }

    public function testContractCostParamsCountBeyondRemainingBytesIsRejected(): void
    {
        $params = new XdrContractCostParams([
            new XdrContractCostParamEntry(new XdrExtensionPoint(0), 10, 20),
        ]);
        $bytes = $params->encode();
        // The typedef array starts with its count.
        $countOffset = 0;
        $this->assertSame(pack('N', 1), substr($bytes, $countOffset, 4));
        $remaining = strlen($bytes) - $countOffset - 4;
        $count = $this->countAboveBound($remaining);

        $this->expectCountRejected($count, $remaining);
        XdrContractCostParams::fromBase64Xdr(base64_encode($this->patchCount($bytes, $countOffset, $count)));
    }

    public function testContractInstanceStorageCountBeyondRemainingBytesIsRejected(): void
    {
        $instance = new XdrSCContractInstance(
            XdrContractExecutable::forToken(),
            [new XdrSCMapEntry(XdrSCVal::forSymbol('key'), XdrSCVal::forU32(1))],
        );
        $bytes = $instance->encode();
        // The executable type (a token executable has no body) and the storage presence flag
        // precede the storage count.
        $countOffset = 8;
        $this->assertSame(pack('N', 1) . pack('N', 1), substr($bytes, $countOffset - 4, 8));
        $remaining = strlen($bytes) - $countOffset - 4;
        $count = $this->countAboveBound($remaining);

        $this->expectCountRejected($count, $remaining);
        XdrSCContractInstance::fromBase64Xdr(base64_encode($this->patchCount($bytes, $countOffset, $count)));
    }

    public function testTransactionResultCountWithoutElementsIsRejected(): void
    {
        $result = new XdrTransactionResultResult(XdrTransactionResultCode::SUCCESS());
        $result->setResults([]);
        $bytes = (new XdrTransactionResult(new BigInteger(100), $result, new XdrTransactionResultExt(0)))->encode();
        // The fee charged (8) and the result code precede the operation results count.
        $countOffset = 12;
        $this->assertSame(pack('N', XdrTransactionResultCode::SUCCESS) . pack('N', 0), substr($bytes, $countOffset - 4, 8));
        // A count of 0x40000000 and nothing after it: 16 bytes in total.
        $hostile = substr($bytes, 0, $countOffset) . pack('N', 0x40000000);
        $this->assertSame(16, strlen($hostile));

        $this->expectCountRejected(0x40000000, 0);
        XdrTransactionResult::fromBase64Xdr(base64_encode($hostile));
    }

    public function testTransactionResultCountBeyondRemainingBytesIsRejected(): void
    {
        $result = new XdrTransactionResultResult(XdrTransactionResultCode::SUCCESS());
        $result->setResults([]);
        $bytes = (new XdrTransactionResult(new BigInteger(100), $result, new XdrTransactionResultExt(0)))->encode();
        // The fee charged (8) and the result code precede the operation results count; the
        // extension (v0) follows it.
        $countOffset = 12;
        $this->assertSame(pack('N', XdrTransactionResultCode::SUCCESS) . pack('N', 0), substr($bytes, $countOffset - 4, 8));
        $remaining = strlen($bytes) - $countOffset - 4;
        $count = $this->countAboveBound($remaining);

        $this->expectCountRejected($count, $remaining);
        XdrTransactionResult::fromBase64Xdr(base64_encode($this->patchCount($bytes, $countOffset, $count)));
    }

    public function testSCValVecCountBeyondRemainingBytesIsRejected(): void
    {
        [$bytes, $countOffset] = $this->scValVecCountVector();
        $remaining = strlen($bytes) - $countOffset - 4;
        $count = $this->countAboveBound($remaining);

        $this->expectCountRejected($count, $remaining);
        XdrSCVal::fromBase64Xdr(base64_encode($this->patchCount($bytes, $countOffset, $count)));
    }

    public function testSCValVecNegativeCountIsRejected(): void
    {
        [$bytes, $countOffset] = $this->scValVecCountVector();

        $this->expectNegativeCountRejected();
        XdrSCVal::fromBase64Xdr(base64_encode($this->patchCount($bytes, $countOffset, self::NEGATIVE_COUNT)));
    }

    public function testFeeBumpExtensionWithoutMatchingArmIsRejected(): void
    {
        $feeBump = (new FeeBumpTransactionBuilder($this->paymentTransaction()))
            ->setBaseFee(200)
            ->setFeeAccount(KeyPair::random()->getAccountId())
            ->build();
        $bytes = base64_decode($feeBump->toEnvelopeXdrBase64());
        // The fee bump extension and the empty outer signature list end the envelope.
        $extOffset = strlen($bytes) - 8;
        $this->assertSame(pack('N', 0) . pack('N', 0), substr($bytes, $extOffset, 8));
        $patched = substr_replace($bytes, pack('N', 1), $extOffset, 4);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown XdrFeeBumpTransactionExt discriminant: 1');
        XdrTransactionEnvelope::fromEnvelopeBase64XdrString(base64_encode($patched));
    }

    public function testAllowTrustNativeAssetIsRejected(): void
    {
        $transaction = (new TransactionBuilder(new Account(KeyPair::random()->getAccountId(), new BigInteger('123'))))
            ->addOperation((new AllowTrustOperationBuilder(KeyPair::random()->getAccountId(), 'ABC', true, false))->build())
            ->build();
        $bytes = base64_decode($transaction->toEnvelopeXdrBase64());
        // Envelope type, source account (4 + 32), fee, sequence number (8), preconditions, memo,
        // operation count, operation source flag, operation type and trustor (4 + 32) precede the asset.
        $assetTypeOffset = 108;
        $this->assertSame(pack('N', XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4) . "ABC\x00", substr($bytes, $assetTypeOffset, 8));
        // ASSET_TYPE_NATIVE is a valid asset type without an arm in the allow trust asset union.
        $patched = substr_replace($bytes, pack('N', XdrAssetType::ASSET_TYPE_NATIVE), $assetTypeOffset, 8);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown XdrAllowTrustOperationAsset discriminant: ' . XdrAssetType::ASSET_TYPE_NATIVE);
        XdrTransactionEnvelope::fromEnvelopeBase64XdrString(base64_encode($patched));
    }

    public function testClaimableBalanceEntryExtV1DecodesZeroExtensionPoint(): void
    {
        $decoded = XdrClaimableBalanceEntryExtV1::fromBase64Xdr(base64_encode(pack('N', 0) . pack('N', 1)));

        $this->assertSame(1, $decoded->getFlags());
    }

    public function testClaimableBalanceEntryExtV1RejectsNonZeroExtensionPoint(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('XdrClaimableBalanceEntryExtV1 extension point must be 0, got 1');
        XdrClaimableBalanceEntryExtV1::fromBase64Xdr(base64_encode(pack('N', 1) . pack('N', 0)));
    }

    public function testTrustLineEntryExtensionV2RejectsNonZeroExtensionPoint(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('XdrTrustLineEntryExtensionV2 extension point must be 0, got 1');
        XdrTrustLineEntryExtensionV2::fromBase64Xdr(base64_encode(pack('N', 0) . pack('N', 1)));
    }

    private function paymentTransaction(): Transaction
    {
        return (new TransactionBuilder(new Account(KeyPair::random()->getAccountId(), new BigInteger('123'))))
            ->addOperation((new PaymentOperationBuilder(KeyPair::random()->getAccountId(), Asset::native(), '10'))->build())
            ->build();
    }

    /**
     * A one-operation transaction envelope and the offset of its operation count.
     *
     * @return array{0: string, 1: int}
     */
    private function transactionOperationsCountVector(): array
    {
        $bytes = base64_decode($this->paymentTransaction()->toEnvelopeXdrBase64());
        // Envelope type, source account (4 + 32), fee, sequence number (8), preconditions and
        // memo precede the operation count.
        $countOffset = 60;
        $this->assertSame(pack('N', 1), substr($bytes, $countOffset, 4));
        return [$bytes, $countOffset];
    }

    /**
     * A one-element SCV_VEC value and the offset of its element count.
     *
     * @return array{0: string, 1: int}
     */
    private function scValVecCountVector(): array
    {
        $bytes = XdrSCVal::forVec([XdrSCVal::forU32(1)])->encode();
        // The value type and the vec presence flag precede the vec count.
        $countOffset = 8;
        $this->assertSame(pack('N', XdrSCValType::SCV_VEC) . pack('N', 1) . pack('N', 1), substr($bytes, 0, 12));
        return [$bytes, $countOffset];
    }

    /**
     * The smallest count above a quarter of the remaining bytes. It does not exceed the
     * remaining bytes, so only the 4-byte element bound rejects it.
     */
    private function countAboveBound(int $remaining): int
    {
        $count = intdiv($remaining, 4) + 1;
        $this->assertLessThanOrEqual($remaining, $count);
        return $count;
    }

    private function patchCount(string $bytes, int $countOffset, int $count): string
    {
        return substr_replace($bytes, pack('N', $count), $countOffset, 4);
    }

    private function expectNegativeCountRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('XDR array count cannot be negative, got -1');
    }

    /**
     * Expects the array count diagnostic for the given count and the bytes that remain after it.
     */
    private function expectCountRejected(int $count, int $remaining): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'XDR array count %d exceeds the maximum of %d for the %d remaining bytes',
            $count,
            intdiv($remaining, 4),
            $remaining
        ));
    }
}
