<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Core;

use DateTime;
use Exception;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\CreateAccountOperation;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperation;
use Soneso\StellarSDK\TimeBounds;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\TransactionPreconditions;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;
use function PHPUnit\Framework\assertGreaterThan;

class TransactionTest extends TestCase
{
    private KeyPair $sourceKeyPair;
    private Account $sourceAccount;
    private string $sourceAccountId;

    public function setUp(): void
    {
        error_reporting(E_ALL);
        $this->sourceKeyPair = KeyPair::random();
        $this->sourceAccountId = $this->sourceKeyPair->getAccountId();
        $this->sourceAccount = new Account($this->sourceAccountId, new BigInteger("100"));
    }

    public function testTransactionConstruction()
    {
        $operation = new CreateAccountOperation("GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO", "100");
        $operations = [$operation];

        $transaction = new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            $operations
        );

        assertEquals($this->sourceAccountId, $transaction->getSourceAccount()->getAccountId());
        assertEquals("101", $transaction->getSequenceNumber()->toString());
        assertEquals(1, count($transaction->getOperations()));
        assertEquals(100, $transaction->getFee());
    }

    public function testTransactionWithMultipleOperations()
    {
        $destId = "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO";
        $op1 = new CreateAccountOperation($destId, "100");
        $op2 = new PaymentOperation(
            MuxedAccount::fromAccountId($destId),
            Asset::native(),
            "50"
        );
        $operations = [$op1, $op2];

        $transaction = new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            $operations
        );

        assertEquals(2, count($transaction->getOperations()));
        assertEquals(200, $transaction->getFee());
    }

    public function testTransactionWithMemo()
    {
        $operation = new CreateAccountOperation("GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO", "100");
        $memo = Memo::text("Test payment");

        $transaction = new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            [$operation],
            $memo
        );

        assertEquals(Memo::MEMO_TYPE_TEXT, $transaction->getMemo()->getType());
        assertEquals("Test payment", $transaction->getMemo()->getValue());
    }

    public function testTransactionWithTimeBounds()
    {
        $operation = new CreateAccountOperation("GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO", "100");
        $minTime = new DateTime('@1000');
        $maxTime = new DateTime('@2000');
        $timeBounds = new TimeBounds($minTime, $maxTime);
        $preconditions = new TransactionPreconditions();
        $preconditions->setTimeBounds($timeBounds);

        $transaction = new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            [$operation],
            null,
            $preconditions
        );

        assertNotNull($transaction->getTimeBounds());
        assertEquals($minTime, $transaction->getTimeBounds()->getMinTime());
        assertEquals($maxTime, $transaction->getTimeBounds()->getMaxTime());
    }

    public function testTransactionWithCustomFee()
    {
        $operation = new CreateAccountOperation("GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO", "100");

        $transaction = new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            [$operation],
            null,
            null,
            500
        );

        assertEquals(500, $transaction->getFee());
    }

    public function testSetFee()
    {
        $operation = new CreateAccountOperation("GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO", "100");

        $transaction = new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            [$operation]
        );

        assertEquals(100, $transaction->getFee());

        $transaction->setFee(300);
        assertEquals(300, $transaction->getFee());
    }

    public function testAddResourceFee()
    {
        $operation = new CreateAccountOperation("GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO", "100");

        $transaction = new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            [$operation]
        );

        $initialFee = $transaction->getFee();
        $transaction->addResourceFee(5000);
        assertEquals($initialFee + 5000, $transaction->getFee());
    }

    public function testTransactionRequiresOperation()
    {
        $this->expectException(Exception::class);

        new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            []
        );
    }

    public function testTransactionToXdr()
    {
        $operation = new CreateAccountOperation("GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO", "100");

        $transaction = new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            [$operation]
        );

        $xdr = $transaction->toXdr();
        assertNotNull($xdr);
        assertEquals($this->sourceAccountId, MuxedAccount::fromXdr($xdr->getSourceAccount())->getAccountId());
        assertEquals("101", $xdr->getSequenceNumber()->getValue()->toString());
        assertEquals(1, count($xdr->getOperations()));
    }

    public function testTransactionToXdrBase64()
    {
        $operation = new CreateAccountOperation("GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO", "100");

        $transaction = new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            [$operation]
        );

        $base64 = $transaction->toXdrBase64();
        assertNotNull($base64);
        assertTrue(strlen($base64) > 0);
    }

    public function testTransactionToEnvelopeXdr()
    {
        $operation = new CreateAccountOperation("GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO", "100");

        $transaction = new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            [$operation]
        );

        $envelope = $transaction->toEnvelopeXdr();
        assertNotNull($envelope);
        assertNotNull($envelope->getV1());
        assertEquals($this->sourceAccountId, MuxedAccount::fromXdr($envelope->getV1()->getTx()->getSourceAccount())->getAccountId());
    }

    public function testTransactionFromV1EnvelopeXdr()
    {
        $operation = new CreateAccountOperation("GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO", "100");
        $memo = Memo::text("Test");

        $transaction = new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            [$operation],
            $memo
        );

        $envelope = $transaction->toEnvelopeXdr();
        $parsed = Transaction::fromV1EnvelopeXdr($envelope->getV1());

        assertEquals($this->sourceAccountId, $parsed->getSourceAccount()->getAccountId());
        assertEquals("101", $parsed->getSequenceNumber()->toString());
        assertEquals(1, count($parsed->getOperations()));
        assertEquals(Memo::MEMO_TYPE_TEXT, $parsed->getMemo()->getType());
        assertEquals("Test", $parsed->getMemo()->getValue());
    }

    public function testSignatureBase()
    {
        $operation = new CreateAccountOperation("GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO", "100");

        $transaction = new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            [$operation]
        );

        $signatureBase = $transaction->signatureBase(Network::testnet());
        assertNotNull($signatureBase);
        assertGreaterThan(0, strlen($signatureBase));
    }

    public function testTransactionBuilder()
    {
        $builder = Transaction::builder($this->sourceAccount);
        assertNotNull($builder);
        assertEquals(TransactionBuilder::class, get_class($builder));
    }

    public function testGetPreconditions()
    {
        $operation = new CreateAccountOperation("GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO", "100");

        $transaction = new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            [$operation]
        );

        assertNull($transaction->getPreconditions());

        $preconditions = new TransactionPreconditions();
        $preconditions->setTimeBounds(new TimeBounds(new DateTime('@1000'), new DateTime('@2000')));

        $transaction2 = new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            [$operation],
            null,
            $preconditions
        );

        assertNotNull($transaction2->getPreconditions());
    }

    public function testGetSorobanTransactionData()
    {
        $operation = new CreateAccountOperation("GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO", "100");

        $transaction = new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            [$operation]
        );

        assertNull($transaction->getSorobanTransactionData());
    }
}
