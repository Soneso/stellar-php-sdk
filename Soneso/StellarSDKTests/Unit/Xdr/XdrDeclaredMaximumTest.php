<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use Closure;
use InvalidArgumentException;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\BumpSequenceOperationBuilder;
use Soneso\StellarSDK\SEP\TxRep\TxRep;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrBumpSequenceOperation;
use Soneso\StellarSDK\Xdr\XdrClaimPredicate;
use Soneso\StellarSDK\Xdr\XdrClaimPredicateType;
use Soneso\StellarSDK\Xdr\XdrContractCodeEntry;
use Soneso\StellarSDK\Xdr\XdrContractCodeEntryExt;
use Soneso\StellarSDK\Xdr\XdrDataEntry;
use Soneso\StellarSDK\Xdr\XdrDataValue;
use Soneso\StellarSDK\Xdr\XdrDataValueMandatory;
use Soneso\StellarSDK\Xdr\XdrDecoratedSignature;
use Soneso\StellarSDK\Xdr\XdrEnvelopeType;
use Soneso\StellarSDK\Xdr\XdrError;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyData;
use Soneso\StellarSDK\Xdr\XdrManageDataOperation;
use Soneso\StellarSDK\Xdr\XdrMemo;
use Soneso\StellarSDK\Xdr\XdrMemoType;
use Soneso\StellarSDK\Xdr\XdrMessageType;
use Soneso\StellarSDK\Xdr\XdrMuxedAccount;
use Soneso\StellarSDK\Xdr\XdrOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrPeerAddress;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrSequenceNumber;
use Soneso\StellarSDK\Xdr\XdrStellarMessage;
use Soneso\StellarSDK\Xdr\XdrTransaction;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrTransactionV0;
use Soneso\StellarSDK\Xdr\XdrTransactionV1Envelope;
use Soneso\StellarSDK\Xdr\XdrTxAdvertVector;
use Soneso\StellarSDK\Xdr\XdrUpgradeType;

/**
 * Tests the declared maximum lengths of XDR values, each at the maximum and one above it, and TransactionBuilder::build().
 */
class XdrDeclaredMaximumTest extends TestCase
{
    /** The account of the raw key "\x01" x 32 that every transaction in these tests uses. */
    private const ACCOUNT_ID = 'GAAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQDZ7H';
    /** The XDR of one peer address: IPv4 127.0.0.1, port 11625, no failures. */
    private const PEER = "\x00\x00\x00\x00\x7f\x00\x00\x01\x00\x00\x2d\x69\x00\x00\x00\x00";

    public static function decodeCases(): array
    {
        $variable = static fn (int $n) => pack('N', $n) . str_repeat('s', $n) . str_repeat("\x00", (4 - $n % 4) % 4);
        $predicate = static fn (int $type) => static fn (int $n) => pack('NN', $type, $n)
            . str_repeat(pack('N', XdrClaimPredicateType::UNCONDITIONAL), $n);
        return [
            'manage data name' => [XdrManageDataOperation::class, static fn (int $n) => $variable($n) . pack('N', 1) . $variable(1),
                64, 'maxLength of 64 exceeded (string is 65 bytes)'],
            'manage data value' => [XdrManageDataOperation::class, static fn (int $n) => $variable(1) . pack('N', 1) . $variable($n),
                64, 'length of 65 exceeds max length of 64'],
            'data entry value' => [XdrDataEntry::class,
                static fn (int $n) => pack('N', 0) . str_repeat("\x01", 32) . $variable(4) . $variable($n) . pack('N', 0),
                64, 'length of 65 exceeds max length of 64'],
            'error message' => [XdrError::class, static fn (int $n) => pack('N', 0) . $variable($n),
                100, 'maxLength of 100 exceeded (string is 101 bytes)'],
            'upgrade' => [XdrUpgradeType::class, $variable, 128, 'length of 129 exceeds max length of 128'],
            'advert vector' => [XdrTxAdvertVector::class, static fn (int $n) => pack('N', $n) . str_repeat('h', 32 * $n),
                1000, 'XDR array count 1001 exceeds the declared maximum of 1000'],
            'AND predicate' => [XdrClaimPredicate::class, $predicate(XdrClaimPredicateType::AND),
                2, 'XDR array count 3 exceeds the declared maximum of 2'],
            'OR predicate' => [XdrClaimPredicate::class, $predicate(XdrClaimPredicateType::OR),
                2, 'XDR array count 3 exceeds the declared maximum of 2'],
            'symbol' => [XdrSCVal::class, static fn (int $n) => pack('N', XdrSCValType::SCV_SYMBOL) . $variable($n),
                32, 'maxLength of 32 exceeded (string is 33 bytes)'],
            'peers' => [XdrStellarMessage::class, static fn (int $n) => pack('NN', XdrMessageType::PEERS, $n) . str_repeat(self::PEER, $n),
                100, 'XDR array count 101 exceeds the declared maximum of 100'],
        ];
    }

    #[DataProvider('decodeCases')]
    public function testDecodeChecksTheDeclaredMaximum(string $class, Closure $xdr, int $max, string $message): void
    {
        $this->assertSame($xdr($max), $class::fromBase64Xdr(base64_encode($xdr($max)))->encode());
        $this->assertRejected(fn () => $class::fromBase64Xdr(base64_encode($xdr($max + 1))), $message);
    }

    public static function encodeCases(): array
    {
        return [
            'manage data name' => [static fn (int $n) => self::manageData($n, 1), 64,
                'XdrManageDataOperation.dataName length 65 exceeds the maximum of 64'],
            'manage data value' => [static fn (int $n) => self::manageData(1, $n), 64,
                'XdrManageDataOperation.dataValue length 65 exceeds the maximum of 64'],
            'data value' => [static fn (int $n) => new XdrDataValue(str_repeat('s', $n)), 64, 'XdrDataValue length 65 exceeds the maximum of 64'],
            'transaction operations' => [static fn (int $n) => self::transaction($n), 100,
                'XdrTransaction.operations length 101 exceeds the maximum of 100'],
            'V0 transaction operations' => [static fn (int $n) => self::transactionV0($n), 100,
                'XdrTransactionV0.operations length 101 exceeds the maximum of 100'],
            'memo text' => [static fn (int $n) => self::memo($n), 28, 'XdrMemo.text length 29 exceeds the maximum of 28'],
            'opaque typedef' => [static fn (int $n) => new XdrUpgradeType(str_repeat('s', $n)), 128, 'XdrUpgradeType length 129 exceeds the maximum of 128'],
            'array typedef' => [static fn (int $n) => new XdrTxAdvertVector(array_fill(0, $n, str_repeat('h', 32))), 1000,
                'XdrTxAdvertVector length 1001 exceeds the maximum of 1000'],
            'peers' => [static fn (int $n) => self::peers($n), 100, 'XdrStellarMessage.peers length 101 exceeds the maximum of 100'],
        ];
    }

    #[DataProvider('encodeCases')]
    public function testEncodeChecksTheDeclaredMaximum(Closure $make, int $max, string $message): void
    {
        $value = $make($max);
        $this->assertSame($value->toJsonValue(), $value::fromBase64Xdr(base64_encode($value->encode()))->toJsonValue());
        $over = $make($max + 1);
        $this->assertRejected(fn () => $over->encode(), $message);
        $this->assertRejected(fn () => $over->toBase64Xdr(), $message);
    }

    public static function txRepCases(): array
    {
        return [
            'transaction operations' => [XdrTransaction::class, static fn (int $n) => self::transaction($n), 100,
                'p.operations.len length 101 exceeds the maximum of 100'],
            'V0 transaction operations' => [XdrTransactionV0::class, static fn (int $n) => self::transactionV0($n), 100,
                'p.operations.len length 101 exceeds the maximum of 100'],
            'envelope signatures' => [XdrTransactionV1Envelope::class, static fn (int $n) => self::envelope(1, $n), 20,
                'p.signatures.len length 21 exceeds the maximum of 20'],
            'memo text' => [XdrMemo::class, static fn (int $n) => self::memo($n), 28, 'p.text length 29 exceeds the maximum of 28'],
            'manage data name' => [XdrManageDataOperation::class, static fn (int $n) => self::manageData($n, 1), 64,
                'p.dataName length 65 exceeds the maximum of 64'],
            'manage data value' => [XdrManageDataOperation::class, static fn (int $n) => self::manageData(1, $n), 64,
                'p.dataValue length 65 exceeds the maximum of 64'],
        ];
    }

    #[DataProvider('txRepCases')]
    public function testTxRepReaderChecksTheDeclaredMaximum(string $class, Closure $make, int $max, string $message): void
    {
        $value = $make($max);
        $this->assertSame($value->toBase64Xdr(), $class::fromTxRep(self::txRepLines($value), 'p')->toBase64Xdr());
        $this->assertRejected(fn () => $class::fromTxRep(self::txRepLines($make($max + 1)), 'p'), $message);
    }

    public static function jsonCases(): array
    {
        return [
            'memo text' => [XdrMemo::class, static fn (int $n) => self::memo($n), 28, 'XdrMemo.text length 29 exceeds the maximum of 28'],
            'transaction operations' => [XdrTransaction::class, static fn (int $n) => self::transaction($n), 100,
                'XdrTransaction.operations length 101 exceeds the maximum of 100'],
            'V0 transaction operations' => [XdrTransactionV0::class, static fn (int $n) => self::transactionV0($n), 100,
                'XdrTransactionV0.operations length 101 exceeds the maximum of 100'],
            'manage data name' => [XdrManageDataOperation::class, static fn (int $n) => self::manageData($n, 1), 64,
                'XdrManageDataOperation.dataName length 65 exceeds the maximum of 64'],
            'manage data value' => [XdrManageDataOperation::class, static fn (int $n) => self::manageData(1, $n), 64,
                'XdrManageDataOperation.dataValue length 65 exceeds the maximum of 64'],
            'data value' => [XdrDataValue::class, static fn (int $n) => new XdrDataValue(str_repeat('s', $n)), 64,
                'XdrDataValue length 65 exceeds the maximum of 64'],
            'envelope signatures' => [XdrTransactionV1Envelope::class, static fn (int $n) => self::envelope(1, $n), 20,
                'XdrTransactionV1Envelope.signatures length 21 exceeds the maximum of 20'],
            'opaque typedef field' => [XdrDecoratedSignature::class,
                static fn (int $n) => new XdrDecoratedSignature("\x01\x02\x03\x04", str_repeat("\x05", $n)), 64,
                'XdrSignature length 65 exceeds the maximum of 64'],
            'string typedef field' => [XdrLedgerKeyData::class,
                static fn (int $n) => new XdrLedgerKeyData(new XdrAccountID(self::ACCOUNT_ID), str_repeat('s', $n)), 64,
                'XdrString64 length 65 exceeds the maximum of 64'],
            'opaque typedef' => [XdrUpgradeType::class, static fn (int $n) => new XdrUpgradeType(str_repeat('s', $n)), 128,
                'XdrUpgradeType length 129 exceeds the maximum of 128'],
            'array typedef' => [XdrTxAdvertVector::class, static fn (int $n) => new XdrTxAdvertVector(array_fill(0, $n, str_repeat('h', 32))),
                1000, 'XdrTxAdvertVector length 1001 exceeds the maximum of 1000'],
        ];
    }

    #[DataProvider('jsonCases')]
    public function testJsonReaderChecksTheDeclaredMaximum(string $class, Closure $make, int $max, string $message): void
    {
        $value = $make($max);
        $this->assertSame($value->toBase64Xdr(), $class::fromJsonValue($value->toJsonValue())->toBase64Xdr());
        $this->assertRejected(fn () => $class::fromJsonValue($make($max + 1)->toJsonValue()), $message);
    }

    public function testFacadeReadsTxRepAtTheMaximum(): void
    {
        foreach ([self::envelope(100, 0), self::envelope(1, 20)] as $envelope) {
            $xdr = self::envelopeXdr($envelope);
            $this->assertSame($xdr, TxRep::transactionEnvelopeXdrBase64FromTxRep(TxRep::fromTransactionEnvelopeXdrBase64($xdr)));
        }
        $memoLines = [
            str_repeat('m', 28) => str_repeat('m', 28),
            '"' . str_repeat('m', 27) . '\\""' => str_repeat('m', 27) . '"',
            '"\\x01' . str_repeat('m', 27) . '"' => "\x01" . str_repeat('m', 27),
        ];
        foreach ($memoLines as $line => $text) {
            $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep(self::facadeTextWithMemo($line));
            $this->assertSame($text, XdrTransactionEnvelope::fromBase64Xdr($xdr)->getV1()->getTx()->getMemo()->getText());
        }
    }

    // The facade reads these values itself: each message names the TxRep key, which the message of the encoding does not.
    public static function facadeTxRepAboveTheMaximum(): array
    {
        $operation = PHP_EOL . implode(PHP_EOL, ['tx.operations[100].sourceAccount._present: false',
            'tx.operations[100].body.type: BUMP_SEQUENCE', 'tx.operations[100].body.bumpSequenceOp.bumpTo: 1']);
        $signature = PHP_EOL . 'signatures[20].hint: 01020304' . PHP_EOL . 'signatures[20].signature: ' . str_repeat('05', 64);
        $memo = 'tx.memo.text length 29 exceeds the maximum of 28';
        return [
            '101 operations' => [str_replace('tx.operations.len: 100', 'tx.operations.len: 101',
                self::facadeText(self::envelope(100, 0))) . $operation, 'tx.operations.len length 101 exceeds the maximum of 100'],
            'memo text of 29 bytes' => [self::facadeTextWithMemo(str_repeat('m', 29)), $memo],
            'memo text of 29 bytes in quotes' => [self::facadeTextWithMemo('"' . str_repeat('m', 28) . '\\""'), $memo],
            'memo text of 29 bytes with an escaped byte' => [self::facadeTextWithMemo('"\\x01' . str_repeat('m', 28) . '"'), $memo],
            '21 signatures' => [str_replace('signatures.len: 20', 'signatures.len: 21', self::facadeText(self::envelope(1, 20)))
                . $signature, 'signatures.len length 21 exceeds the maximum of 20'],
        ];
    }

    #[DataProvider('facadeTxRepAboveTheMaximum')]
    public function testFacadeRejectsTxRepAboveTheMaximum(string $txRep, string $message): void
    {
        $this->assertRejected(fn () => TxRep::transactionEnvelopeXdrBase64FromTxRep($txRep), $message);
    }

    public function testTransactionBuilderBuildsAtMost100Operations(): void
    {
        $account = new Account(self::ACCOUNT_ID, new BigInteger(7));
        $this->assertCount(100, self::builder($account, 100)->build()->getOperations());
        $builder = self::builder($account, 101);
        $this->assertRejected(fn () => $builder->build(), 'a transaction holds at most 100 operations, got 101');
        $this->assertSame('8', $account->getSequenceNumber()->toString());
    }

    public function testControlUnboundedValuesOf70KilobytesPass(): void
    {
        $content = str_repeat("\x07", 70 * 1024);
        foreach ([XdrSCVal::forBytes($content), XdrHostFunction::forUploadContractWasm($content), new XdrContractCodeEntry(
            new XdrContractCodeEntryExt(0), str_repeat("\x02", 32), new XdrDataValueMandatory($content))] as $value) {
            $xdr = $value->encode();
            $this->assertStringEndsWith(pack('N', strlen($content)) . $content, $xdr, get_class($value));
            $this->assertSame($xdr, $value::fromBase64Xdr(base64_encode($xdr))->encode(), get_class($value));
            $this->assertSame($xdr, $value::fromJsonValue($value->toJsonValue())->encode(), get_class($value));
        }
    }

    private static function manageData(int $nameLength, int $valueLength): XdrManageDataOperation
    {
        return new XdrManageDataOperation(str_repeat('s', $nameLength), new XdrDataValue(str_repeat('s', $valueLength)));
    }

    private static function memo(int $length): XdrMemo
    {
        $memo = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_TEXT));
        $memo->setText(str_repeat('m', $length));
        return $memo;
    }

    /** @return array<XdrOperation> $count operations that bump the sequence number to 1 */
    private static function operations(int $count): array
    {
        $body = new XdrOperationBody(new XdrOperationType(XdrOperationType::BUMP_SEQUENCE));
        $body->setBumpSequenceOp(new XdrBumpSequenceOperation(new XdrSequenceNumber(new BigInteger(1))));
        return array_fill(0, $count, new XdrOperation($body));
    }

    private static function transaction(int $operations, ?XdrMemo $memo = null): XdrTransaction
    {
        return new XdrTransaction(new XdrMuxedAccount(str_repeat("\x01", 32)), new XdrSequenceNumber(new BigInteger(2)),
            self::operations($operations), 100, $memo);
    }

    private static function transactionV0(int $operations): XdrTransactionV0
    {
        return new XdrTransactionV0(str_repeat("\x01", 32), new XdrSequenceNumber(new BigInteger(2)),
            self::operations($operations), 100);
    }

    private static function envelope(int $operations, int $signatures, ?XdrMemo $memo = null): XdrTransactionV1Envelope
    {
        return new XdrTransactionV1Envelope(self::transaction($operations, $memo),
            array_fill(0, $signatures, new XdrDecoratedSignature("\x01\x02\x03\x04", str_repeat("\x05", 64))));
    }

    private static function envelopeXdr(XdrTransactionV1Envelope $v1): string
    {
        $envelope = new XdrTransactionEnvelope(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX));
        $envelope->setV1($v1);
        return $envelope->toBase64Xdr();
    }

    private static function facadeText(XdrTransactionV1Envelope $v1): string
    {
        return TxRep::fromTransactionEnvelopeXdrBase64(self::envelopeXdr($v1));
    }

    /** The TxRep of a one-operation envelope whose memo text line holds $line as written. */
    private static function facadeTextWithMemo(string $line): string
    {
        return str_replace('tx.memo.text: "m"', 'tx.memo.text: ' . $line, self::facadeText(self::envelope(1, 0, self::memo(1))));
    }

    /** @return array<string, string> the TxRep lines of $value under the prefix "p" */
    private static function txRepLines(object $value): array
    {
        $lines = [];
        $value->toTxRep('p', $lines);
        return $lines;
    }

    private static function peers(int $count): XdrStellarMessage
    {
        $message = new XdrStellarMessage(new XdrMessageType(XdrMessageType::PEERS));
        $message->setPeers(array_fill(0, $count, XdrPeerAddress::fromBase64Xdr(base64_encode(self::PEER))));
        return $message;
    }

    private static function builder(Account $account, int $operations): TransactionBuilder
    {
        $builder = new TransactionBuilder($account);
        for ($i = 0; $i < $operations; $i++) {
            $builder->addOperation((new BumpSequenceOperationBuilder(new BigInteger(1)))->build());
        }
        return $builder;
    }

    private function assertRejected(Closure $call, string $message): void
    {
        try {
            $call();
        } catch (InvalidArgumentException $e) {
            $this->assertSame($message, $e->getMessage());
            return;
        }
        $this->fail('no InvalidArgumentException, expected: ' . $message);
    }
}
