<?php  declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit;

use DateTime;
use PHPUnit\Framework\TestCase;
use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\AssetTypeCreditAlphanum12;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\SEP\TxRep\TxRep;
use Soneso\StellarSDK\TimeBounds;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\LedgerBounds;
use Soneso\StellarSDK\TransactionPreconditions;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Xdr\XdrSignerKey;
use Soneso\StellarSDK\Xdr\XdrSignerKeyType;

class TxRepExtendedTest extends TestCase {

    private function createTimeBounds(): TimeBounds {
        return new TimeBounds(
            (new DateTime())->setTimestamp(1595282368),
            (new DateTime())->setTimestamp(1595284000)
        );
    }

    public function testMemoHash(): void {
        $sourceKeyPair = KeyPair::random();
        $sourceAccountId = $sourceKeyPair->getAccountId();
        $destinationKeyPair = KeyPair::random();
        $destinationAccountId = $destinationKeyPair->getAccountId();

        $sourceAccount = new Account($sourceAccountId, new BigInteger(1234567890));

        $hash = hash('sha256', 'test', true);
        $memo = Memo::hash($hash);

        $paymentOp = (new PaymentOperationBuilder(
            $destinationAccountId,
            Asset::native(),
            "100.0"
        ))->build();

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($paymentOp)
            ->addMemo($memo)
            ->setTimeBounds($this->createTimeBounds())
            ->build();

        $transaction->sign($sourceKeyPair, Network::testnet());

        $xdr = $transaction->toEnvelopeXdrBase64();
        $txRep = TxRep::fromTransactionEnvelopeXdrBase64($xdr);

        self::assertStringContainsString('tx.memo.type: MEMO_HASH', $txRep);
        self::assertStringContainsString('tx.memo.hash:', $txRep);

        $xdr2 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txRep);
        $txRep2 = TxRep::fromTransactionEnvelopeXdrBase64($xdr2);

        self::assertEquals($txRep, $txRep2);
    }

    public function testMemoReturn(): void {
        $sourceKeyPair = KeyPair::random();
        $sourceAccountId = $sourceKeyPair->getAccountId();
        $destinationKeyPair = KeyPair::random();
        $destinationAccountId = $destinationKeyPair->getAccountId();

        $sourceAccount = new Account($sourceAccountId, new BigInteger(9876543210));

        $hash = hash('sha256', 'return', true);
        $memo = Memo::return($hash);

        $paymentOp = (new PaymentOperationBuilder(
            $destinationAccountId,
            Asset::native(),
            "50.0"
        ))->build();

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($paymentOp)
            ->addMemo($memo)
            ->setTimeBounds($this->createTimeBounds())
            ->build();

        $transaction->sign($sourceKeyPair, Network::testnet());

        $xdr = $transaction->toEnvelopeXdrBase64();
        $txRep = TxRep::fromTransactionEnvelopeXdrBase64($xdr);

        self::assertStringContainsString('tx.memo.type: MEMO_RETURN', $txRep);
        self::assertStringContainsString('tx.memo.retHash:', $txRep);

        $xdr2 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txRep);
        $txRep2 = TxRep::fromTransactionEnvelopeXdrBase64($xdr2);

        self::assertEquals($txRep, $txRep2);
    }

    public function testMemoId(): void {
        $sourceKeyPair = KeyPair::random();
        $sourceAccountId = $sourceKeyPair->getAccountId();
        $destinationKeyPair = KeyPair::random();
        $destinationAccountId = $destinationKeyPair->getAccountId();

        $sourceAccount = new Account($sourceAccountId, new BigInteger(1234567890));

        $memo = Memo::id(123456789);

        $paymentOp = (new PaymentOperationBuilder(
            $destinationAccountId,
            Asset::native(),
            "75.0"
        ))->build();

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($paymentOp)
            ->addMemo($memo)
            ->setTimeBounds($this->createTimeBounds())
            ->build();

        $transaction->sign($sourceKeyPair, Network::testnet());

        $xdr = $transaction->toEnvelopeXdrBase64();
        $txRep = TxRep::fromTransactionEnvelopeXdrBase64($xdr);

        self::assertStringContainsString('tx.memo.type: MEMO_ID', $txRep);
        self::assertStringContainsString('tx.memo.id: 123456789', $txRep);

        $xdr2 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txRep);
        $txRep2 = TxRep::fromTransactionEnvelopeXdrBase64($xdr2);

        self::assertEquals($txRep, $txRep2);
    }

    public function testPreconditionsWithLedgerBounds(): void {
        $sourceKeyPair = KeyPair::random();
        $sourceAccountId = $sourceKeyPair->getAccountId();
        $destinationKeyPair = KeyPair::random();
        $destinationAccountId = $destinationKeyPair->getAccountId();

        $sourceAccount = new Account($sourceAccountId, new BigInteger(1234567890));

        $paymentOp = (new PaymentOperationBuilder(
            $destinationAccountId,
            Asset::native(),
            "100.0"
        ))->build();

        $preconditions = new TransactionPreconditions();
        $preconditions->setLedgerBounds(new LedgerBounds(1000, 2000));

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($paymentOp)
            ->setPreconditions($preconditions)
            ->build();

        $transaction->sign($sourceKeyPair, Network::testnet());

        $xdr = $transaction->toEnvelopeXdrBase64();
        $txRep = TxRep::fromTransactionEnvelopeXdrBase64($xdr);

        self::assertStringContainsString('tx.cond.type: PRECOND_V2', $txRep);
        self::assertStringContainsString('tx.cond.v2.ledgerBounds._present: true', $txRep);
        self::assertStringContainsString('tx.cond.v2.ledgerBounds.minLedger: 1000', $txRep);
        self::assertStringContainsString('tx.cond.v2.ledgerBounds.maxLedger: 2000', $txRep);

        $xdr2 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txRep);
        $txRep2 = TxRep::fromTransactionEnvelopeXdrBase64($xdr2);

        self::assertEquals($txRep, $txRep2);
    }

    public function testPreconditionsWithMinSeqNum(): void {
        $sourceKeyPair = KeyPair::random();
        $sourceAccountId = $sourceKeyPair->getAccountId();
        $destinationKeyPair = KeyPair::random();
        $destinationAccountId = $destinationKeyPair->getAccountId();

        $sourceAccount = new Account($sourceAccountId, new BigInteger(1234567890));

        $paymentOp = (new PaymentOperationBuilder(
            $destinationAccountId,
            Asset::native(),
            "100.0"
        ))->build();

        $preconditions = new TransactionPreconditions();
        $preconditions->setMinSeqNumber(new BigInteger(1234567880));

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($paymentOp)
            ->setPreconditions($preconditions)
            ->build();

        $transaction->sign($sourceKeyPair, Network::testnet());

        $xdr = $transaction->toEnvelopeXdrBase64();
        $txRep = TxRep::fromTransactionEnvelopeXdrBase64($xdr);

        self::assertStringContainsString('tx.cond.type: PRECOND_V2', $txRep);
        self::assertStringContainsString('tx.cond.v2.minSeqNum._present: true', $txRep);
        self::assertStringContainsString('tx.cond.v2.minSeqNum: 1234567880', $txRep);

        $xdr2 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txRep);
        $txRep2 = TxRep::fromTransactionEnvelopeXdrBase64($xdr2);

        self::assertEquals($txRep, $txRep2);
    }

    public function testPreconditionsWithMinSeqAge(): void {
        $sourceKeyPair = KeyPair::random();
        $sourceAccountId = $sourceKeyPair->getAccountId();
        $destinationKeyPair = KeyPair::random();
        $destinationAccountId = $destinationKeyPair->getAccountId();

        $sourceAccount = new Account($sourceAccountId, new BigInteger(1234567890));

        $paymentOp = (new PaymentOperationBuilder(
            $destinationAccountId,
            Asset::native(),
            "100.0"
        ))->build();

        $preconditions = new TransactionPreconditions();
        $preconditions->setMinSeqAge(3600);

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($paymentOp)
            ->setPreconditions($preconditions)
            ->build();

        $transaction->sign($sourceKeyPair, Network::testnet());

        $xdr = $transaction->toEnvelopeXdrBase64();
        $txRep = TxRep::fromTransactionEnvelopeXdrBase64($xdr);

        self::assertStringContainsString('tx.cond.type: PRECOND_V2', $txRep);
        self::assertStringContainsString('tx.cond.v2.minSeqAge: 3600', $txRep);

        $xdr2 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txRep);
        $txRep2 = TxRep::fromTransactionEnvelopeXdrBase64($xdr2);

        self::assertEquals($txRep, $txRep2);
    }

    public function testPreconditionsWithMinSeqLedgerGap(): void {
        $sourceKeyPair = KeyPair::random();
        $sourceAccountId = $sourceKeyPair->getAccountId();
        $destinationKeyPair = KeyPair::random();
        $destinationAccountId = $destinationKeyPair->getAccountId();

        $sourceAccount = new Account($sourceAccountId, new BigInteger(1234567890));

        $paymentOp = (new PaymentOperationBuilder(
            $destinationAccountId,
            Asset::native(),
            "100.0"
        ))->build();

        $preconditions = new TransactionPreconditions();
        $preconditions->setMinSeqLedgerGap(10);

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($paymentOp)
            ->setPreconditions($preconditions)
            ->build();

        $transaction->sign($sourceKeyPair, Network::testnet());

        $xdr = $transaction->toEnvelopeXdrBase64();
        $txRep = TxRep::fromTransactionEnvelopeXdrBase64($xdr);

        self::assertStringContainsString('tx.cond.type: PRECOND_V2', $txRep);
        self::assertStringContainsString('tx.cond.v2.minSeqLedgerGap: 10', $txRep);

        $xdr2 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txRep);
        $txRep2 = TxRep::fromTransactionEnvelopeXdrBase64($xdr2);

        self::assertEquals($txRep, $txRep2);
    }

    public function testPreconditionsWithExtraSigners(): void {
        $sourceKeyPair = KeyPair::random();
        $sourceAccountId = $sourceKeyPair->getAccountId();
        $destinationKeyPair = KeyPair::random();
        $destinationAccountId = $destinationKeyPair->getAccountId();

        $sourceAccount = new Account($sourceAccountId, new BigInteger(1234567890));

        $paymentOp = (new PaymentOperationBuilder(
            $destinationAccountId,
            Asset::native(),
            "100.0"
        ))->build();

        $extraSigner1 = KeyPair::random();
        $extraSigner2 = KeyPair::random();

        $xdrSignerKey1 = new XdrSignerKey();
        $xdrSignerKey1->setType(new XdrSignerKeyType(XdrSignerKeyType::ED25519));
        $xdrSignerKey1->setEd25519(StrKey::decodeAccountId($extraSigner1->getAccountId()));

        $xdrSignerKey2 = new XdrSignerKey();
        $xdrSignerKey2->setType(new XdrSignerKeyType(XdrSignerKeyType::ED25519));
        $xdrSignerKey2->setEd25519(StrKey::decodeAccountId($extraSigner2->getAccountId()));

        $preconditions = new TransactionPreconditions();
        $preconditions->setExtraSigners([$xdrSignerKey1, $xdrSignerKey2]);

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($paymentOp)
            ->setPreconditions($preconditions)
            ->build();

        $transaction->sign($sourceKeyPair, Network::testnet());

        $xdr = $transaction->toEnvelopeXdrBase64();
        $txRep = TxRep::fromTransactionEnvelopeXdrBase64($xdr);

        self::assertStringContainsString('tx.cond.type: PRECOND_V2', $txRep);
        self::assertStringContainsString('tx.cond.v2.extraSigners.len: 2', $txRep);
        self::assertStringContainsString('tx.cond.v2.extraSigners[0]:', $txRep);
        self::assertStringContainsString('tx.cond.v2.extraSigners[1]:', $txRep);

        $xdr2 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txRep);
        $txRep2 = TxRep::fromTransactionEnvelopeXdrBase64($xdr2);

        self::assertEquals($txRep, $txRep2);
    }

    public function testPreconditionsCombined(): void {
        $sourceKeyPair = KeyPair::random();
        $sourceAccountId = $sourceKeyPair->getAccountId();
        $destinationKeyPair = KeyPair::random();
        $destinationAccountId = $destinationKeyPair->getAccountId();

        $sourceAccount = new Account($sourceAccountId, new BigInteger(1234567890));

        $paymentOp = (new PaymentOperationBuilder(
            $destinationAccountId,
            Asset::native(),
            "100.0"
        ))->build();

        $extraSigner = KeyPair::random();
        $xdrSignerKey = new XdrSignerKey();
        $xdrSignerKey->setType(new XdrSignerKeyType(XdrSignerKeyType::ED25519));
        $xdrSignerKey->setEd25519(StrKey::decodeAccountId($extraSigner->getAccountId()));

        $preconditions = new TransactionPreconditions();
        $preconditions->setTimeBounds($this->createTimeBounds());
        $preconditions->setLedgerBounds(new LedgerBounds(1000, 2000));
        $preconditions->setMinSeqNumber(new BigInteger(1234567880));
        $preconditions->setMinSeqAge(3600);
        $preconditions->setMinSeqLedgerGap(10);
        $preconditions->setExtraSigners([$xdrSignerKey]);

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($paymentOp)
            ->setPreconditions($preconditions)
            ->build();

        $transaction->sign($sourceKeyPair, Network::testnet());

        $xdr = $transaction->toEnvelopeXdrBase64();
        $txRep = TxRep::fromTransactionEnvelopeXdrBase64($xdr);

        self::assertStringContainsString('tx.cond.type: PRECOND_V2', $txRep);
        self::assertStringContainsString('tx.cond.v2.timeBounds._present: true', $txRep);
        self::assertStringContainsString('tx.cond.v2.ledgerBounds._present: true', $txRep);
        self::assertStringContainsString('tx.cond.v2.minSeqNum._present: true', $txRep);
        self::assertStringContainsString('tx.cond.v2.minSeqAge: 3600', $txRep);
        self::assertStringContainsString('tx.cond.v2.minSeqLedgerGap: 10', $txRep);
        self::assertStringContainsString('tx.cond.v2.extraSigners.len: 1', $txRep);

        $xdr2 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txRep);
        $txRep2 = TxRep::fromTransactionEnvelopeXdrBase64($xdr2);

        self::assertEquals($txRep, $txRep2);
    }

    public function testAssetAlphanum4(): void {
        $sourceKeyPair = KeyPair::random();
        $sourceAccountId = $sourceKeyPair->getAccountId();
        $destinationKeyPair = KeyPair::random();
        $destinationAccountId = $destinationKeyPair->getAccountId();
        $issuerKeyPair = KeyPair::random();
        $issuerAccountId = $issuerKeyPair->getAccountId();

        $sourceAccount = new Account($sourceAccountId, new BigInteger(1234567890));

        $asset = new AssetTypeCreditAlphanum4("USD", $issuerAccountId);

        $paymentOp = (new PaymentOperationBuilder(
            $destinationAccountId,
            $asset,
            "100.0"
        ))->build();

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($paymentOp)
            ->setTimeBounds($this->createTimeBounds())
            ->build();

        $transaction->sign($sourceKeyPair, Network::testnet());

        $xdr = $transaction->toEnvelopeXdrBase64();
        $txRep = TxRep::fromTransactionEnvelopeXdrBase64($xdr);

        self::assertStringContainsString('tx.operations[0].body.paymentOp.asset: USD:', $txRep);
        self::assertStringContainsString($issuerAccountId, $txRep);

        $xdr2 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txRep);
        $txRep2 = TxRep::fromTransactionEnvelopeXdrBase64($xdr2);

        self::assertEquals($txRep, $txRep2);
    }

    public function testAssetAlphanum12(): void {
        $sourceKeyPair = KeyPair::random();
        $sourceAccountId = $sourceKeyPair->getAccountId();
        $destinationKeyPair = KeyPair::random();
        $destinationAccountId = $destinationKeyPair->getAccountId();
        $issuerKeyPair = KeyPair::random();
        $issuerAccountId = $issuerKeyPair->getAccountId();

        $sourceAccount = new Account($sourceAccountId, new BigInteger(1234567890));

        $asset = new AssetTypeCreditAlphanum12("TESTCOIN", $issuerAccountId);

        $paymentOp = (new PaymentOperationBuilder(
            $destinationAccountId,
            $asset,
            "250.5"
        ))->build();

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($paymentOp)
            ->setTimeBounds($this->createTimeBounds())
            ->build();

        $transaction->sign($sourceKeyPair, Network::testnet());

        $xdr = $transaction->toEnvelopeXdrBase64();
        $txRep = TxRep::fromTransactionEnvelopeXdrBase64($xdr);

        self::assertStringContainsString('tx.operations[0].body.paymentOp.asset: TESTCOIN:', $txRep);
        self::assertStringContainsString($issuerAccountId, $txRep);

        $xdr2 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txRep);
        $txRep2 = TxRep::fromTransactionEnvelopeXdrBase64($xdr2);

        self::assertEquals($txRep, $txRep2);
    }

    public function testOperationWithSourceAccount(): void {
        $sourceKeyPair = KeyPair::random();
        $sourceAccountId = $sourceKeyPair->getAccountId();
        $destinationKeyPair = KeyPair::random();
        $destinationAccountId = $destinationKeyPair->getAccountId();
        $opSourceKeyPair = KeyPair::random();
        $opSourceAccountId = $opSourceKeyPair->getAccountId();

        $sourceAccount = new Account($sourceAccountId, new BigInteger(1234567890));

        $paymentOp = (new PaymentOperationBuilder(
            $destinationAccountId,
            Asset::native(),
            "100.0"
        ))
            ->setSourceAccount($opSourceAccountId)
            ->build();

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($paymentOp)
            ->setTimeBounds($this->createTimeBounds())
            ->build();

        $transaction->sign($sourceKeyPair, Network::testnet());

        $xdr = $transaction->toEnvelopeXdrBase64();
        $txRep = TxRep::fromTransactionEnvelopeXdrBase64($xdr);

        self::assertStringContainsString('tx.operations[0].sourceAccount._present: true', $txRep);
        self::assertStringContainsString('tx.operations[0].sourceAccount: ' . $opSourceAccountId, $txRep);

        $xdr2 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txRep);
        $txRep2 = TxRep::fromTransactionEnvelopeXdrBase64($xdr2);

        self::assertEquals($txRep, $txRep2);
    }

    public function testOperationWithoutSourceAccount(): void {
        $sourceKeyPair = KeyPair::random();
        $sourceAccountId = $sourceKeyPair->getAccountId();
        $destinationKeyPair = KeyPair::random();
        $destinationAccountId = $destinationKeyPair->getAccountId();

        $sourceAccount = new Account($sourceAccountId, new BigInteger(1234567890));

        $paymentOp = (new PaymentOperationBuilder(
            $destinationAccountId,
            Asset::native(),
            "100.0"
        ))->build();

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($paymentOp)
            ->setTimeBounds($this->createTimeBounds())
            ->build();

        $transaction->sign($sourceKeyPair, Network::testnet());

        $xdr = $transaction->toEnvelopeXdrBase64();
        $txRep = TxRep::fromTransactionEnvelopeXdrBase64($xdr);

        self::assertStringContainsString('tx.operations[0].sourceAccount._present: false', $txRep);

        $xdr2 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txRep);
        $txRep2 = TxRep::fromTransactionEnvelopeXdrBase64($xdr2);

        self::assertEquals($txRep, $txRep2);
    }

    public function testMultipleSignatures(): void {
        $sourceKeyPair = KeyPair::random();
        $sourceAccountId = $sourceKeyPair->getAccountId();
        $destinationKeyPair = KeyPair::random();
        $destinationAccountId = $destinationKeyPair->getAccountId();

        $signer1 = KeyPair::random();
        $signer2 = KeyPair::random();

        $sourceAccount = new Account($sourceAccountId, new BigInteger(1234567890));

        $paymentOp = (new PaymentOperationBuilder(
            $destinationAccountId,
            Asset::native(),
            "100.0"
        ))->build();

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($paymentOp)
            ->setTimeBounds($this->createTimeBounds())
            ->build();

        $transaction->sign($sourceKeyPair, Network::testnet());
        $transaction->sign($signer1, Network::testnet());
        $transaction->sign($signer2, Network::testnet());

        $xdr = $transaction->toEnvelopeXdrBase64();
        $txRep = TxRep::fromTransactionEnvelopeXdrBase64($xdr);

        self::assertStringContainsString('signatures.len: 3', $txRep);
        self::assertStringContainsString('signatures[0].hint:', $txRep);
        self::assertStringContainsString('signatures[0].signature:', $txRep);
        self::assertStringContainsString('signatures[1].hint:', $txRep);
        self::assertStringContainsString('signatures[1].signature:', $txRep);
        self::assertStringContainsString('signatures[2].hint:', $txRep);
        self::assertStringContainsString('signatures[2].signature:', $txRep);

        $xdr2 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txRep);
        $txRep2 = TxRep::fromTransactionEnvelopeXdrBase64($xdr2);

        self::assertEquals($txRep, $txRep2);
    }

    public function testHighFeeTransaction(): void {
        $sourceKeyPair = KeyPair::random();
        $sourceAccountId = $sourceKeyPair->getAccountId();
        $destinationKeyPair = KeyPair::random();
        $destinationAccountId = $destinationKeyPair->getAccountId();

        $sourceAccount = new Account($sourceAccountId, new BigInteger(1234567890));

        $paymentOp = (new PaymentOperationBuilder(
            $destinationAccountId,
            Asset::native(),
            "100.0"
        ))->build();

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($paymentOp)
            ->setTimeBounds($this->createTimeBounds())
            ->setMaxOperationFee(1000000)
            ->build();

        $transaction->sign($sourceKeyPair, Network::testnet());

        $xdr = $transaction->toEnvelopeXdrBase64();
        $txRep = TxRep::fromTransactionEnvelopeXdrBase64($xdr);

        self::assertStringContainsString('tx.fee: 1000000', $txRep);

        $xdr2 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txRep);
        $txRep2 = TxRep::fromTransactionEnvelopeXdrBase64($xdr2);

        self::assertEquals($txRep, $txRep2);
    }

    public function testMaxSequenceNumber(): void {
        $sourceKeyPair = KeyPair::random();
        $sourceAccountId = $sourceKeyPair->getAccountId();
        $destinationKeyPair = KeyPair::random();
        $destinationAccountId = $destinationKeyPair->getAccountId();

        $maxSeqNum = PHP_INT_MAX;
        $sourceAccount = new Account($sourceAccountId, new BigInteger($maxSeqNum - 1));

        $paymentOp = (new PaymentOperationBuilder(
            $destinationAccountId,
            Asset::native(),
            "100.0"
        ))->build();

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($paymentOp)
            ->setTimeBounds($this->createTimeBounds())
            ->build();

        $transaction->sign($sourceKeyPair, Network::testnet());

        $xdr = $transaction->toEnvelopeXdrBase64();
        $txRep = TxRep::fromTransactionEnvelopeXdrBase64($xdr);

        self::assertStringContainsString('tx.seqNum:', $txRep);

        $xdr2 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txRep);
        $txRep2 = TxRep::fromTransactionEnvelopeXdrBase64($xdr2);

        self::assertEquals($txRep, $txRep2);
    }

    public function testMinSequenceNumber(): void {
        $sourceKeyPair = KeyPair::random();
        $sourceAccountId = $sourceKeyPair->getAccountId();
        $destinationKeyPair = KeyPair::random();
        $destinationAccountId = $destinationKeyPair->getAccountId();

        $sourceAccount = new Account($sourceAccountId, new BigInteger(0));

        $paymentOp = (new PaymentOperationBuilder(
            $destinationAccountId,
            Asset::native(),
            "100.0"
        ))->build();

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($paymentOp)
            ->setTimeBounds($this->createTimeBounds())
            ->build();

        $transaction->sign($sourceKeyPair, Network::testnet());

        $xdr = $transaction->toEnvelopeXdrBase64();
        $txRep = TxRep::fromTransactionEnvelopeXdrBase64($xdr);

        self::assertStringContainsString('tx.seqNum: 1', $txRep);

        $xdr2 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txRep);
        $txRep2 = TxRep::fromTransactionEnvelopeXdrBase64($xdr2);

        self::assertEquals($txRep, $txRep2);
    }

    public function testRoundTripConsistency(): void {
        $sourceKeyPair = KeyPair::random();
        $sourceAccountId = $sourceKeyPair->getAccountId();
        $destinationKeyPair = KeyPair::random();
        $destinationAccountId = $destinationKeyPair->getAccountId();

        $sourceAccount = new Account($sourceAccountId, new BigInteger(1234567890));

        $paymentOp = (new PaymentOperationBuilder(
            $destinationAccountId,
            Asset::native(),
            "100.0"
        ))->build();

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($paymentOp)
            ->setTimeBounds($this->createTimeBounds())
            ->addMemo(Memo::text("Test consistency"))
            ->build();

        $transaction->sign($sourceKeyPair, Network::testnet());

        $originalXdr = $transaction->toEnvelopeXdrBase64();

        // Round 1: XDR -> TxRep -> XDR
        $txRep1 = TxRep::fromTransactionEnvelopeXdrBase64($originalXdr);
        $xdr1 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txRep1);

        // Round 2: XDR -> TxRep -> XDR
        $txRep2 = TxRep::fromTransactionEnvelopeXdrBase64($xdr1);
        $xdr2 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txRep2);

        // Round 3: XDR -> TxRep -> XDR
        $txRep3 = TxRep::fromTransactionEnvelopeXdrBase64($xdr2);
        $xdr3 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txRep3);

        self::assertEquals($txRep1, $txRep2);
        self::assertEquals($txRep2, $txRep3);
        self::assertEquals($xdr1, $xdr2);
        self::assertEquals($xdr2, $xdr3);
    }
}
