<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use DateTime;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\CryptoKeyType;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrDecoratedSignature;
use Soneso\StellarSDK\Xdr\XdrEnvelopeType;
use Soneso\StellarSDK\Xdr\XdrFeeBumpTransaction;
use Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionExt;
use Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionInnerTx;
use Soneso\StellarSDK\Xdr\XdrLedgerBounds;
use Soneso\StellarSDK\Xdr\XdrMemo;
use Soneso\StellarSDK\Xdr\XdrMemoType;
use Soneso\StellarSDK\Xdr\XdrMuxedAccount;
use Soneso\StellarSDK\Xdr\XdrOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrPreconditions;
use Soneso\StellarSDK\Xdr\XdrPreconditionType;
use Soneso\StellarSDK\Xdr\XdrSequenceNumber;
use Soneso\StellarSDK\Xdr\XdrTimeBounds;
use Soneso\StellarSDK\Xdr\XdrTransaction;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrTransactionExt;
use Soneso\StellarSDK\Xdr\XdrTransactionResult;
use Soneso\StellarSDK\Xdr\XdrTransactionResultCode;
use Soneso\StellarSDK\Xdr\XdrTransactionResultExt;
use Soneso\StellarSDK\Xdr\XdrTransactionResultResult;
use Soneso\StellarSDK\Xdr\XdrTransactionV1Envelope;
use Soneso\StellarSDK\Xdr\XdrBumpSequenceOperation;

/**
 * Unit tests for Transaction XDR classes using encode/decode round-trip testing.
 */
class XdrTransactionTest extends TestCase
{
    private const TEST_ACCOUNT_ED25519 = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    /**
     * Test XdrSequenceNumber encode/decode round-trip.
     */
    public function testSequenceNumberRoundTrip(): void
    {
        $testCases = [
            new BigInteger('0'),
            new BigInteger('1'),
            new BigInteger('12345'),
            new BigInteger('9223372036854775807'), // max int64
            new BigInteger('18446744073709551615'), // max uint64 as big integer
        ];

        foreach ($testCases as $value) {
            $sequenceNumber = new XdrSequenceNumber($value);
            $encoded = $sequenceNumber->encode();
            $xdrBuffer = new XdrBuffer($encoded);
            $decoded = XdrSequenceNumber::decode($xdrBuffer);

            $this->assertEquals(
                $value->toString(),
                $decoded->getValue()->toString(),
                "Sequence number roundtrip failed for: " . $value->toString()
            );
        }
    }

    /**
     * Test XdrMemoType values.
     */
    public function testMemoTypeValues(): void
    {
        $this->assertEquals(0, XdrMemoType::MEMO_NONE);
        $this->assertEquals(1, XdrMemoType::MEMO_TEXT);
        $this->assertEquals(2, XdrMemoType::MEMO_ID);
        $this->assertEquals(3, XdrMemoType::MEMO_HASH);
        $this->assertEquals(4, XdrMemoType::MEMO_RETURN);
    }

    /**
     * Test XdrMemo encode/decode round-trip for MEMO_NONE.
     */
    public function testMemoNoneRoundTrip(): void
    {
        $memo = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_NONE));
        $encoded = $memo->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrMemo::decode($xdrBuffer);

        $this->assertEquals(XdrMemoType::MEMO_NONE, $decoded->getType()->getValue());
        $this->assertNull($decoded->getText());
        $this->assertNull($decoded->getId());
        $this->assertNull($decoded->getHash());
        $this->assertNull($decoded->getReturnHash());
    }

    /**
     * Test XdrMemo encode/decode round-trip for MEMO_TEXT.
     */
    public function testMemoTextRoundTrip(): void
    {
        $testText = "Test memo text";
        $memo = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_TEXT));
        $memo->setText($testText);

        $encoded = $memo->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrMemo::decode($xdrBuffer);

        $this->assertEquals(XdrMemoType::MEMO_TEXT, $decoded->getType()->getValue());
        $this->assertEquals($testText, $decoded->getText());
    }

    /**
     * Test XdrMemo encode/decode round-trip for MEMO_TEXT with max length.
     */
    public function testMemoTextMaxLengthRoundTrip(): void
    {
        $testText = str_repeat("a", 28); // Max length
        $memo = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_TEXT));
        $memo->setText($testText);

        $encoded = $memo->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrMemo::decode($xdrBuffer);

        $this->assertEquals(XdrMemoType::MEMO_TEXT, $decoded->getType()->getValue());
        $this->assertEquals($testText, $decoded->getText());
        $this->assertEquals(28, strlen($decoded->getText()));
    }

    /**
     * Test XdrMemo encode/decode round-trip for MEMO_ID.
     */
    public function testMemoIdRoundTrip(): void
    {
        $testId = 9876543210;
        $memo = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_ID));
        $memo->setId($testId);

        $encoded = $memo->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrMemo::decode($xdrBuffer);

        $this->assertEquals(XdrMemoType::MEMO_ID, $decoded->getType()->getValue());
        $this->assertEquals($testId, $decoded->getId());
    }

    /**
     * Test XdrMemo encode/decode round-trip for MEMO_HASH.
     */
    public function testMemoHashRoundTrip(): void
    {
        $testHash = str_repeat('a', 32);
        $memo = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_HASH));
        $memo->setHash($testHash);

        $encoded = $memo->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrMemo::decode($xdrBuffer);

        $this->assertEquals(XdrMemoType::MEMO_HASH, $decoded->getType()->getValue());
        $this->assertEquals($testHash, $decoded->getHash());
    }

    /**
     * Test XdrMemo encode/decode round-trip for MEMO_RETURN.
     */
    public function testMemoReturnRoundTrip(): void
    {
        $testHash = str_repeat('b', 32);
        $memo = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_RETURN));
        $memo->setReturnHash($testHash);

        $encoded = $memo->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrMemo::decode($xdrBuffer);

        $this->assertEquals(XdrMemoType::MEMO_RETURN, $decoded->getType()->getValue());
        $this->assertEquals($testHash, $decoded->getReturnHash());
    }

    /**
     * Test XdrTimeBounds encode/decode round-trip.
     */
    public function testTimeBoundsRoundTrip(): void
    {
        $minTime = new DateTime('2025-01-01 00:00:00');
        $maxTime = new DateTime('2025-12-31 23:59:59');

        $timeBounds = new XdrTimeBounds($minTime, $maxTime);
        $encoded = $timeBounds->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrTimeBounds::decode($xdrBuffer);

        $this->assertEquals($minTime->getTimestamp(), $decoded->getMinTimestamp());
        $this->assertEquals($maxTime->getTimestamp(), $decoded->getMaxTimestamp());
    }

    /**
     * Test XdrTimeBounds with zero timestamps.
     */
    public function testTimeBoundsZeroTimestamps(): void
    {
        $minTime = DateTime::createFromFormat('U', '0');
        $maxTime = DateTime::createFromFormat('U', '0');

        $timeBounds = new XdrTimeBounds($minTime, $maxTime);
        $encoded = $timeBounds->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrTimeBounds::decode($xdrBuffer);

        $this->assertEquals(0, $decoded->getMinTimestamp());
        $this->assertEquals(0, $decoded->getMaxTimestamp());
    }

    /**
     * Test XdrLedgerBounds encode/decode round-trip.
     */
    public function testLedgerBoundsRoundTrip(): void
    {
        $minLedger = 100;
        $maxLedger = 1000;

        $ledgerBounds = new XdrLedgerBounds($minLedger, $maxLedger);
        $encoded = $ledgerBounds->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerBounds::decode($xdrBuffer);

        $this->assertEquals($minLedger, $decoded->getMinLedger());
        $this->assertEquals($maxLedger, $decoded->getMaxLedger());
    }

    /**
     * Test XdrLedgerBounds with zero values.
     */
    public function testLedgerBoundsZeroValues(): void
    {
        $ledgerBounds = new XdrLedgerBounds(0, 0);
        $encoded = $ledgerBounds->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrLedgerBounds::decode($xdrBuffer);

        $this->assertEquals(0, $decoded->getMinLedger());
        $this->assertEquals(0, $decoded->getMaxLedger());
    }

    /**
     * Test XdrPreconditions encode/decode round-trip with NONE type.
     */
    public function testPreconditionsNoneRoundTrip(): void
    {
        $preconditions = new XdrPreconditions(new XdrPreconditionType(XdrPreconditionType::NONE));
        $encoded = $preconditions->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrPreconditions::decode($xdrBuffer);

        $this->assertEquals(XdrPreconditionType::NONE, $decoded->getType()->getValue());
        $this->assertNull($decoded->getTimeBounds());
        $this->assertNull($decoded->getV2());
    }

    /**
     * Test XdrPreconditions encode/decode round-trip with TIME type.
     */
    public function testPreconditionsTimeRoundTrip(): void
    {
        $minTime = new DateTime('2025-01-01 00:00:00');
        $maxTime = new DateTime('2025-12-31 23:59:59');
        $timeBounds = new XdrTimeBounds($minTime, $maxTime);

        $preconditions = new XdrPreconditions(new XdrPreconditionType(XdrPreconditionType::TIME));
        $preconditions->setTimeBounds($timeBounds);

        $encoded = $preconditions->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrPreconditions::decode($xdrBuffer);

        $this->assertEquals(XdrPreconditionType::TIME, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getTimeBounds());
        $this->assertEquals($minTime->getTimestamp(), $decoded->getTimeBounds()->getMinTimestamp());
        $this->assertEquals($maxTime->getTimestamp(), $decoded->getTimeBounds()->getMaxTimestamp());
    }

    /**
     * Test XdrTransaction encode/decode round-trip with minimal configuration.
     */
    public function testTransactionMinimalRoundTrip(): void
    {
        $sourceAccount = new XdrMuxedAccount(hex2bin(self::TEST_ACCOUNT_ED25519));
        $sequenceNumber = new XdrSequenceNumber(new BigInteger('12345'));
        $operation = $this->createBumpSequenceOperation(100);
        $operations = [$operation];

        $transaction = new XdrTransaction(
            $sourceAccount,
            $sequenceNumber,
            $operations,
            100,
            null,
            null,
            null
        );

        $encoded = $transaction->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrTransaction::decode($xdrBuffer);

        $this->assertEquals(100, $decoded->getFee());
        $this->assertEquals('12345', $decoded->getSequenceNumber()->getValue()->toString());
        $this->assertCount(1, $decoded->getOperations());
        $this->assertEquals(XdrMemoType::MEMO_NONE, $decoded->getMemo()->getType()->getValue());
    }

    /**
     * Test XdrTransaction encode/decode round-trip with full configuration.
     */
    public function testTransactionFullRoundTrip(): void
    {
        $sourceAccount = new XdrMuxedAccount(hex2bin(self::TEST_ACCOUNT_ED25519));
        $sequenceNumber = new XdrSequenceNumber(new BigInteger('99999'));

        $operations = [
            $this->createBumpSequenceOperation(100),
            $this->createBumpSequenceOperation(200)
        ];

        $memo = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_TEXT));
        $memo->setText("Test transaction");

        $minTime = new DateTime('2025-01-01 00:00:00');
        $maxTime = new DateTime('2025-12-31 23:59:59');
        $timeBounds = new XdrTimeBounds($minTime, $maxTime);
        $preconditions = new XdrPreconditions(new XdrPreconditionType(XdrPreconditionType::TIME));
        $preconditions->setTimeBounds($timeBounds);

        $transaction = new XdrTransaction(
            $sourceAccount,
            $sequenceNumber,
            $operations,
            500,
            $memo,
            $preconditions,
            null
        );

        $encoded = $transaction->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrTransaction::decode($xdrBuffer);

        $this->assertEquals(500, $decoded->getFee());
        $this->assertEquals('99999', $decoded->getSequenceNumber()->getValue()->toString());
        $this->assertCount(2, $decoded->getOperations());
        $this->assertEquals(XdrMemoType::MEMO_TEXT, $decoded->getMemo()->getType()->getValue());
        $this->assertEquals("Test transaction", $decoded->getMemo()->getText());
        $this->assertNotNull($decoded->getPreconditions());
        $this->assertEquals(XdrPreconditionType::TIME, $decoded->getPreconditions()->getType()->getValue());
    }

    /**
     * Test XdrTransactionV1Envelope encode/decode round-trip.
     */
    public function testTransactionV1EnvelopeRoundTrip(): void
    {
        $sourceAccount = new XdrMuxedAccount(hex2bin(self::TEST_ACCOUNT_ED25519));
        $sequenceNumber = new XdrSequenceNumber(new BigInteger('12345'));
        $operation = $this->createBumpSequenceOperation(100);

        $transaction = new XdrTransaction(
            $sourceAccount,
            $sequenceNumber,
            [$operation],
            100
        );

        $signatures = [
            $this->createDecoratedSignature('sig1'),
            $this->createDecoratedSignature('sig2')
        ];

        $envelope = new XdrTransactionV1Envelope($transaction, $signatures);
        $encoded = $envelope->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrTransactionV1Envelope::decode($xdrBuffer);

        $this->assertEquals(100, $decoded->getTx()->getFee());
        $this->assertCount(2, $decoded->getSignatures());
    }

    /**
     * Test XdrTransactionEnvelope encode/decode round-trip for V1 transaction.
     */
    public function testTransactionEnvelopeV1RoundTrip(): void
    {
        $sourceAccount = new XdrMuxedAccount(hex2bin(self::TEST_ACCOUNT_ED25519));
        $sequenceNumber = new XdrSequenceNumber(new BigInteger('12345'));
        $operation = $this->createBumpSequenceOperation(100);

        $transaction = new XdrTransaction(
            $sourceAccount,
            $sequenceNumber,
            [$operation],
            100
        );

        $signatures = [$this->createDecoratedSignature('test')];
        $v1Envelope = new XdrTransactionV1Envelope($transaction, $signatures);

        $envelope = new XdrTransactionEnvelope(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX));
        $envelope->setV1($v1Envelope);

        $encoded = $envelope->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrTransactionEnvelope::decode($xdrBuffer);

        $this->assertEquals(XdrEnvelopeType::ENVELOPE_TYPE_TX, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getV1());
        $this->assertEquals(100, $decoded->getV1()->getTx()->getFee());
    }

    /**
     * Test XdrTransactionEnvelope base64 conversion.
     */
    public function testTransactionEnvelopeBase64Conversion(): void
    {
        $sourceAccount = new XdrMuxedAccount(hex2bin(self::TEST_ACCOUNT_ED25519));
        $sequenceNumber = new XdrSequenceNumber(new BigInteger('12345'));
        $operation = $this->createBumpSequenceOperation(100);

        $transaction = new XdrTransaction(
            $sourceAccount,
            $sequenceNumber,
            [$operation],
            100
        );

        $signatures = [$this->createDecoratedSignature('test')];
        $v1Envelope = new XdrTransactionV1Envelope($transaction, $signatures);

        $envelope = new XdrTransactionEnvelope(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX));
        $envelope->setV1($v1Envelope);

        $base64 = $envelope->toBase64Xdr();
        $this->assertNotEmpty($base64);

        $decoded = XdrTransactionEnvelope::fromEnvelopeBase64XdrString($base64);
        $this->assertEquals(XdrEnvelopeType::ENVELOPE_TYPE_TX, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getV1());
    }

    /**
     * Test XdrFeeBumpTransactionInnerTx encode/decode round-trip.
     */
    public function testFeeBumpTransactionInnerTxRoundTrip(): void
    {
        $sourceAccount = new XdrMuxedAccount(hex2bin(self::TEST_ACCOUNT_ED25519));
        $sequenceNumber = new XdrSequenceNumber(new BigInteger('12345'));
        $operation = $this->createBumpSequenceOperation(100);

        $transaction = new XdrTransaction(
            $sourceAccount,
            $sequenceNumber,
            [$operation],
            100
        );

        $signatures = [$this->createDecoratedSignature('test')];
        $v1Envelope = new XdrTransactionV1Envelope($transaction, $signatures);

        $innerTx = new XdrFeeBumpTransactionInnerTx(
            new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX),
            $v1Envelope
        );

        $encoded = $innerTx->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrFeeBumpTransactionInnerTx::decode($xdrBuffer);

        $this->assertEquals(XdrEnvelopeType::ENVELOPE_TYPE_TX, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getV1());
    }

    /**
     * Test XdrFeeBumpTransaction encode/decode round-trip.
     */
    public function testFeeBumpTransactionRoundTrip(): void
    {
        $feeSource = new XdrMuxedAccount(hex2bin(self::TEST_ACCOUNT_ED25519));

        $sourceAccount = new XdrMuxedAccount(hex2bin(self::TEST_ACCOUNT_ED25519));
        $sequenceNumber = new XdrSequenceNumber(new BigInteger('12345'));
        $operation = $this->createBumpSequenceOperation(100);

        $transaction = new XdrTransaction(
            $sourceAccount,
            $sequenceNumber,
            [$operation],
            100
        );

        $signatures = [$this->createDecoratedSignature('test')];
        $v1Envelope = new XdrTransactionV1Envelope($transaction, $signatures);

        $innerTx = new XdrFeeBumpTransactionInnerTx(
            new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX),
            $v1Envelope
        );

        $feeBumpTx = new XdrFeeBumpTransaction($feeSource, 200, $innerTx);
        $encoded = $feeBumpTx->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrFeeBumpTransaction::decode($xdrBuffer);

        $this->assertEquals(200, $decoded->getFee());
        $this->assertEquals(CryptoKeyType::KEY_TYPE_ED25519, $decoded->getFeeSource()->getDiscriminant());
    }

    /**
     * Test XdrFeeBumpTransactionEnvelope encode/decode round-trip.
     */
    public function testFeeBumpTransactionEnvelopeRoundTrip(): void
    {
        $feeSource = new XdrMuxedAccount(hex2bin(self::TEST_ACCOUNT_ED25519));

        $sourceAccount = new XdrMuxedAccount(hex2bin(self::TEST_ACCOUNT_ED25519));
        $sequenceNumber = new XdrSequenceNumber(new BigInteger('12345'));
        $operation = $this->createBumpSequenceOperation(100);

        $transaction = new XdrTransaction(
            $sourceAccount,
            $sequenceNumber,
            [$operation],
            100
        );

        $signatures = [$this->createDecoratedSignature('test')];
        $v1Envelope = new XdrTransactionV1Envelope($transaction, $signatures);

        $innerTx = new XdrFeeBumpTransactionInnerTx(
            new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX),
            $v1Envelope
        );

        $feeBumpTx = new XdrFeeBumpTransaction($feeSource, 200, $innerTx);
        $feeBumpSignatures = [
            $this->createDecoratedSignature('feesig1'),
            $this->createDecoratedSignature('feesig2')
        ];

        $envelope = new XdrFeeBumpTransactionEnvelope($feeBumpTx, $feeBumpSignatures);
        $encoded = $envelope->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrFeeBumpTransactionEnvelope::decode($xdrBuffer);

        $this->assertEquals(200, $decoded->getTx()->getFee());
        $this->assertCount(2, $decoded->getSignatures());
    }

    /**
     * Test XdrTransactionResult encode/decode round-trip with success result.
     */
    public function testTransactionResultSuccessRoundTrip(): void
    {
        $result = new XdrTransactionResult();
        $result->setFeeCharged(new BigInteger('100'));

        $resultResult = new XdrTransactionResultResult();
        $resultCode = new XdrTransactionResultCode(XdrTransactionResultCode::SUCCESS);
        $resultResult->setResultCode($resultCode);
        $resultResult->setResults([]);

        $result->setResult($resultResult);
        $result->setExt(new XdrTransactionResultExt(0));

        $encoded = $result->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrTransactionResult::decode($xdrBuffer);

        $this->assertEquals('100', $decoded->getFeeCharged()->toString());
        $this->assertEquals(XdrTransactionResultCode::SUCCESS, $decoded->getResult()->getResultCode()->getValue());
    }

    /**
     * Test XdrTransactionResult base64 conversion.
     */
    public function testTransactionResultBase64Conversion(): void
    {
        $result = new XdrTransactionResult();
        $result->setFeeCharged(new BigInteger('250'));

        $resultResult = new XdrTransactionResultResult();
        $resultCode = new XdrTransactionResultCode(XdrTransactionResultCode::SUCCESS);
        $resultResult->setResultCode($resultCode);
        $resultResult->setResults([]);

        $result->setResult($resultResult);
        $result->setExt(new XdrTransactionResultExt(0));

        $base64 = $result->toBase64Xdr();
        $this->assertNotEmpty($base64);

        $decoded = XdrTransactionResult::fromBase64Xdr($base64);
        $this->assertEquals('250', $decoded->getFeeCharged()->toString());
        $this->assertEquals(XdrTransactionResultCode::SUCCESS, $decoded->getResult()->getResultCode()->getValue());
    }

    /**
     * Test XdrTransactionResultResult with failed transaction.
     */
    public function testTransactionResultFailedRoundTrip(): void
    {
        $resultResult = new XdrTransactionResultResult();
        $resultCode = new XdrTransactionResultCode(XdrTransactionResultCode::FAILED);
        $resultResult->setResultCode($resultCode);
        $resultResult->setResults([]);

        $encoded = $resultResult->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrTransactionResultResult::decode($xdrBuffer);

        $this->assertEquals(XdrTransactionResultCode::FAILED, $decoded->getResultCode()->getValue());
        $this->assertNotNull($decoded->getResults());
        $this->assertIsArray($decoded->getResults());
    }

    /**
     * Test XdrTransactionResultResult with various result codes.
     */
    public function testTransactionResultResultCodes(): void
    {
        $codes = [
            XdrTransactionResultCode::SUCCESS,
            XdrTransactionResultCode::FAILED,
            XdrTransactionResultCode::TOO_EARLY,
            XdrTransactionResultCode::TOO_LATE,
            XdrTransactionResultCode::MISSING_OPERATION,
            XdrTransactionResultCode::BAD_SEQ,
            XdrTransactionResultCode::BAD_AUTH,
            XdrTransactionResultCode::INSUFFICIENT_BALANCE,
            XdrTransactionResultCode::NO_ACCOUNT,
            XdrTransactionResultCode::INSUFFICIENT_FEE,
            XdrTransactionResultCode::BAD_AUTH_EXTRA,
            XdrTransactionResultCode::INTERNAL_ERROR,
        ];

        foreach ($codes as $code) {
            $resultResult = new XdrTransactionResultResult();
            $resultCode = new XdrTransactionResultCode($code);
            $resultResult->setResultCode($resultCode);

            if ($code === XdrTransactionResultCode::SUCCESS || $code === XdrTransactionResultCode::FAILED) {
                $resultResult->setResults([]);
            }

            $encoded = $resultResult->encode();
            $xdrBuffer = new XdrBuffer($encoded);
            $decoded = XdrTransactionResultResult::decode($xdrBuffer);

            $this->assertEquals($code, $decoded->getResultCode()->getValue());
        }
    }

    /**
     * Test XdrTransaction with multiple operations.
     */
    public function testTransactionWithMultipleOperations(): void
    {
        $sourceAccount = new XdrMuxedAccount(hex2bin(self::TEST_ACCOUNT_ED25519));
        $sequenceNumber = new XdrSequenceNumber(new BigInteger('54321'));

        $operations = [
            $this->createBumpSequenceOperation(100),
            $this->createBumpSequenceOperation(200),
            $this->createBumpSequenceOperation(300),
            $this->createBumpSequenceOperation(400),
            $this->createBumpSequenceOperation(500)
        ];

        $transaction = new XdrTransaction(
            $sourceAccount,
            $sequenceNumber,
            $operations,
            500
        );

        $encoded = $transaction->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrTransaction::decode($xdrBuffer);

        $this->assertCount(5, $decoded->getOperations());
        $this->assertEquals('54321', $decoded->getSequenceNumber()->getValue()->toString());
    }

    /**
     * Test XdrTransaction with high fee values.
     */
    public function testTransactionWithHighFee(): void
    {
        $sourceAccount = new XdrMuxedAccount(hex2bin(self::TEST_ACCOUNT_ED25519));
        $sequenceNumber = new XdrSequenceNumber(new BigInteger('1'));
        $operation = $this->createBumpSequenceOperation(100);

        $highFee = 1000000;
        $transaction = new XdrTransaction(
            $sourceAccount,
            $sequenceNumber,
            [$operation],
            $highFee
        );

        $encoded = $transaction->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrTransaction::decode($xdrBuffer);

        $this->assertEquals($highFee, $decoded->getFee());
    }

    /**
     * Test XdrPreconditions with V2 type (if implemented).
     */
    public function testPreconditionsV2Type(): void
    {
        $preconditions = new XdrPreconditions(new XdrPreconditionType(XdrPreconditionType::V2));

        $this->assertEquals(XdrPreconditionType::V2, $preconditions->getType()->getValue());
    }

    /**
     * Helper: Create a bump sequence operation for testing.
     */
    private function createBumpSequenceOperation(int $bumpTo): XdrOperation
    {
        $body = new XdrOperationBody(new XdrOperationType(XdrOperationType::BUMP_SEQUENCE));
        $bumpSeqOp = new XdrBumpSequenceOperation(new XdrSequenceNumber(new BigInteger($bumpTo)));
        $body->setBumpSequenceOp($bumpSeqOp);
        return new XdrOperation($body);
    }

    /**
     * Helper: Create a decorated signature for testing.
     */
    private function createDecoratedSignature(string $hint): XdrDecoratedSignature
    {
        $signatureHint = str_pad($hint, 4, '0', STR_PAD_LEFT);
        $signatureHint = substr($signatureHint, 0, 4);

        $signature = str_repeat('x', 64);

        return new XdrDecoratedSignature($signatureHint, $signature);
    }
}
