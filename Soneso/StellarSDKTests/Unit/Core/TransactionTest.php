<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Core;

use DateTime;
use Exception;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\AbstractTransaction;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\CreateAccountOperation;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\FeeBumpTransaction;
use Soneso\StellarSDK\FeeBumpTransactionBuilder;
use Soneso\StellarSDK\InflationOperation;
use Soneso\StellarSDK\LedgerBounds;
use Soneso\StellarSDK\ManageSellOfferOperation;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperation;
use Soneso\StellarSDK\Signer;
use Soneso\StellarSDK\TimeBounds;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\TransactionPreconditions;
use Soneso\StellarSDK\InvokeHostFunctionOperation;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionDataExt;
use Soneso\StellarSDK\Xdr\XdrSorobanResources;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;
use Soneso\StellarSDK\Xdr\XdrInt64;
use Soneso\StellarSDK\Xdr\XdrTransactionV0Envelope;
use Soneso\StellarSDK\Xdr\XdrTransactionV0;
use Soneso\StellarSDK\Xdr\XdrSequenceNumber;
use Soneso\StellarSDK\Xdr\XdrOperation;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrDecoratedSignature;
use Soneso\StellarSDK\Xdr\XdrTimeBounds;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;
use Soneso\StellarSDK\Xdr\XdrInvokeHostFunctionOp;
use Soneso\StellarSDK\Xdr\XdrCryptoKeyType;
use Soneso\StellarSDK\Xdr\XdrEnvelopeType;
use Soneso\StellarSDK\Xdr\XdrFeeBumpTransaction;
use Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionExt;
use Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionInnerTx;
use Soneso\StellarSDK\Xdr\XdrMemo;
use Soneso\StellarSDK\Xdr\XdrMemoType;
use Soneso\StellarSDK\Xdr\XdrMuxedAccount;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrPreconditions;
use Soneso\StellarSDK\Xdr\XdrPreconditionType;
use Soneso\StellarSDK\Xdr\XdrSetOptionsOperation;
use Soneso\StellarSDK\Xdr\XdrTransaction;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrTransactionExt;
use Soneso\StellarSDK\Xdr\XdrTransactionV0Ext;
use Soneso\StellarSDK\Xdr\XdrTransactionV1Envelope;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum4;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrManageSellOfferOperation;
use Soneso\StellarSDK\Xdr\XdrPaymentOperation;
use Soneso\StellarSDK\Xdr\XdrPrice;
use InvalidArgumentException;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;
use function PHPUnit\Framework\assertGreaterThan;
use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertCount;

class TransactionTest extends TestCase
{
    // Testnet transaction 11446413...aadc (ledger 5822970): one CREATE_CONTRACT_V2 host function that
    // deploys the Stellar Asset Contract of an AlphaNum4 asset with an empty constructor argument list.
    private const SAC_V2_ENVELOPE = 'AAAAAgAAAAAMCf4TNV9jU4d5QPRyB0MihH9zgsUW6keMIZsIAP/UDgABIWIAWNn5AAAAAQAAAAEAAAAAAAAAAAAAAABq/6QvAAAAAAAAAAEAAAAAAAAAGAAAAAMAAAABAAAAAUxXVjIAAAAADAn+EzVfY1OHeUD0cgdDIoR/c4LFFupHjCGbCAD/1A4AAAABAAAAAAAAAAAAAAABAAAAAAAAAAAAAAABAAAABgAAAAFv3MrH8x/kbUTlOq9FidhQHGBb+ZN/BNe3GCwznhvb8wAAABQAAAABAAKrMQAAAAAAAAHcAAAAAAABIP4AAAABAP/UDgAAAEBDm1gYDzIAJHtXc0hWqix7I22s4B9ga7eWJp5+8eWxuT/608SPbN9y5hzli1R2NX9ARlcA5fNgcEUnW1Y6v8sD';
    private const SAC_V2_NETWORK_HASH = '11446413b5a7a95f8bb18e616a5187f19a5a7b146fdef3c5c2c77e5ea87aaadc';

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
        assertEquals("101", $xdr->getSequenceNumber()->sequenceNumber->toString());
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

    public function testSetSorobanTransactionData()
    {
        $operation = new CreateAccountOperation("GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO", "100");

        $transaction = new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            [$operation]
        );

        assertNull($transaction->getSorobanTransactionData());

        $resources = new XdrSorobanResources(
            new XdrLedgerFootprint([], []),
            100000,
            200000,
            300000
        );
        $ext = new XdrSorobanTransactionDataExt(0);
        $sorobanData = new XdrSorobanTransactionData($ext, $resources, 50000);

        $transaction->setSorobanTransactionData($sorobanData);

        assertNotNull($transaction->getSorobanTransactionData());
        assertEquals(50000, $transaction->getSorobanTransactionData()->resourceFee);

        // Set to null
        $transaction->setSorobanTransactionData(null);
        assertNull($transaction->getSorobanTransactionData());
    }

    public function testGetTimeBoundsWithNullPreconditions()
    {
        $operation = new CreateAccountOperation("GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO", "100");

        $transaction = new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            [$operation]
        );

        assertNull($transaction->getTimeBounds());
    }

    public function testTransactionWithInvalidOperationType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("operation array contains unknown operation type");

        // Pass a non-AbstractOperation object
        new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            ["not an operation"]
        );
    }

    public function testTransactionToXdrWithSorobanData()
    {
        $operation = new CreateAccountOperation("GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO", "100");

        $resources = new XdrSorobanResources(
            new XdrLedgerFootprint([], []),
            100000,
            200000,
            300000
        );
        $ext = new XdrSorobanTransactionDataExt(0);
        $sorobanData = new XdrSorobanTransactionData($ext, $resources, 50000);

        $transaction = new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            [$operation],
            null,
            null,
            null,
            $sorobanData
        );

        $xdr = $transaction->toXdr();
        assertNotNull($xdr);
        assertNotNull($xdr->ext);
        assertEquals(1, $xdr->ext->getDiscriminant());
        assertNotNull($xdr->ext->sorobanTransactionData);
        assertEquals(50000, $xdr->ext->sorobanTransactionData->resourceFee);
    }

    public function testFromV1EnvelopeXdrWithPreconditions()
    {
        $operation = new CreateAccountOperation("GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO", "100");
        $preconditions = new TransactionPreconditions();
        $preconditions->setTimeBounds(new TimeBounds(new DateTime('@1000'), new DateTime('@2000')));

        $transaction = new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            [$operation],
            null,
            $preconditions
        );

        $envelope = $transaction->toEnvelopeXdr();
        $parsed = Transaction::fromV1EnvelopeXdr($envelope->getV1());

        assertNotNull($parsed->getPreconditions());
        assertNotNull($parsed->getTimeBounds());
        assertEquals(1000, $parsed->getTimeBounds()->getMinTime()->getTimestamp());
        assertEquals(2000, $parsed->getTimeBounds()->getMaxTime()->getTimestamp());
    }

    public function testFromV1EnvelopeXdrWithSignatures()
    {
        $operation = new CreateAccountOperation("GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO", "100");

        $transaction = new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            [$operation]
        );

        // Sign the transaction
        $transaction->sign($this->sourceKeyPair, Network::testnet());

        $envelope = $transaction->toEnvelopeXdr();
        $parsed = Transaction::fromV1EnvelopeXdr($envelope->getV1());

        assertCount(1, $parsed->getSignatures());
    }

    public function testFromV1EnvelopeXdrWithSorobanData()
    {
        $operation = new CreateAccountOperation("GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO", "100");

        $resources = new XdrSorobanResources(
            new XdrLedgerFootprint([], []),
            100000,
            200000,
            300000
        );
        $ext = new XdrSorobanTransactionDataExt(0);
        $sorobanData = new XdrSorobanTransactionData($ext, $resources, 50000);

        $transaction = new Transaction(
            MuxedAccount::fromAccountId($this->sourceAccountId),
            new BigInteger("101"),
            [$operation],
            null,
            null,
            null,
            $sorobanData
        );

        $envelope = $transaction->toEnvelopeXdr();
        $parsed = Transaction::fromV1EnvelopeXdr($envelope->getV1());

        assertNotNull($parsed->getSorobanTransactionData());
        assertEquals(50000, $parsed->getSorobanTransactionData()->resourceFee);
    }

    public function testFromV0EnvelopeXdrBasic()
    {
        // Create a V0 envelope manually
        $sourceKeyPair = KeyPair::random();
        $sourceEd25519 = $sourceKeyPair->getPublicKey();

        // Create a simple operation XDR
        $destId = "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO";
        $operation = new CreateAccountOperation($destId, "100");
        $xdrOperation = $operation->toXdr();

        // Create V0 transaction using proper constructor
        $v0Tx = new XdrTransactionV0(
            $sourceEd25519,
            new XdrSequenceNumber(new BigInteger(101)),
            [$xdrOperation],
            100,
            Memo::none()->toXdr(),
            null
        );

        $v0Envelope = new XdrTransactionV0Envelope($v0Tx, []);

        $parsed = Transaction::fromV0EnvelopeXdr($v0Envelope);

        assertEquals($sourceKeyPair->getAccountId(), $parsed->getSourceAccount()->getAccountId());
        assertEquals("101", $parsed->getSequenceNumber()->toString());
        assertEquals(1, count($parsed->getOperations()));
        assertEquals(100, $parsed->getFee());
        assertNull($parsed->getPreconditions());
    }

    public function testFromV0EnvelopeXdrWithTimeBounds()
    {
        $sourceKeyPair = KeyPair::random();
        $sourceEd25519 = $sourceKeyPair->getPublicKey();

        $destId = "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO";
        $operation = new CreateAccountOperation($destId, "100");
        $xdrOperation = $operation->toXdr();

        $v0Tx = new XdrTransactionV0(
            $sourceEd25519,
            new XdrSequenceNumber(new BigInteger(101)),
            [$xdrOperation],
            100,
            Memo::none()->toXdr(),
            new XdrTimeBounds(new DateTime('@1000'), new DateTime('@2000'))
        );

        $v0Envelope = new XdrTransactionV0Envelope($v0Tx, []);

        $parsed = Transaction::fromV0EnvelopeXdr($v0Envelope);

        assertNotNull($parsed->getPreconditions());
        assertNotNull($parsed->getTimeBounds());
        assertEquals(1000, $parsed->getTimeBounds()->getMinTime()->getTimestamp());
        assertEquals(2000, $parsed->getTimeBounds()->getMaxTime()->getTimestamp());
    }

    public function testFromV0EnvelopeXdrWithSignatures()
    {
        $sourceKeyPair = KeyPair::random();
        $sourceEd25519 = $sourceKeyPair->getPublicKey();

        $destId = "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO";
        $operation = new CreateAccountOperation($destId, "100");
        $xdrOperation = $operation->toXdr();

        $v0Tx = new XdrTransactionV0(
            $sourceEd25519,
            new XdrSequenceNumber(new BigInteger(101)),
            [$xdrOperation],
            100,
            Memo::none()->toXdr(),
            null
        );

        // Create a mock signature
        $hint = substr($sourceEd25519, -4);
        $sig = str_repeat("\x00", 64);
        $decoratedSig = new XdrDecoratedSignature($hint, $sig);

        $v0Envelope = new XdrTransactionV0Envelope($v0Tx, [$decoratedSig]);

        $parsed = Transaction::fromV0EnvelopeXdr($v0Envelope);

        assertCount(1, $parsed->getSignatures());
    }

    public static function inflationEnvelopeProvider(): array
    {
        $source = new XdrMuxedAccount(str_repeat("\x11", 32));
        $operations = self::inflationXdrOperations();
        $sequence = new XdrSequenceNumber(new BigInteger(101));
        $memo = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_NONE));
        $signatures = [new XdrDecoratedSignature(str_repeat("\x11", 4), str_repeat("\x33", 64))];

        $v1Tx = new XdrTransaction($source, $sequence, $operations, 200, $memo,
            new XdrPreconditions(XdrPreconditionType::NONE()), new XdrTransactionExt(0));
        $v1 = new XdrTransactionV1Envelope($v1Tx, $signatures);
        $v1Envelope = new XdrTransactionEnvelope(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX));
        $v1Envelope->setV1($v1);

        $v0Tx = new XdrTransactionV0(str_repeat("\x11", 32), $sequence, $operations, 200, $memo,
            null, new XdrTransactionV0Ext(0));
        $v0Envelope = new XdrTransactionEnvelope(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0));
        $v0Envelope->setV0(new XdrTransactionV0Envelope($v0Tx, $signatures));
        // The V0 signature payload prefixes the source key with its muxed-account discriminant.
        $v0TaggedTransaction = pack('N', XdrEnvelopeType::ENVELOPE_TYPE_TX)
            . pack('N', XdrCryptoKeyType::KEY_TYPE_ED25519) . $v0Tx->encode();

        $inner = new XdrFeeBumpTransactionInnerTx(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX));
        $inner->setV1($v1);
        $feeBumpTx = new XdrFeeBumpTransaction($source, 400, $inner, new XdrFeeBumpTransactionExt(0));
        $feeBumpEnvelope = new XdrTransactionEnvelope(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP));
        $feeBumpEnvelope->setFeeBump(new XdrFeeBumpTransactionEnvelope($feeBumpTx, $signatures));

        return [
            'V1' => [$v1Envelope, pack('N', XdrEnvelopeType::ENVELOPE_TYPE_TX) . $v1Tx->encode()],
            'V0' => [$v0Envelope, $v0TaggedTransaction],
            'fee bump' => [$feeBumpEnvelope, pack('N', XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP) . $feeBumpTx->encode()],
        ];
    }

    #[DataProvider('inflationEnvelopeProvider')]
    public function testImportedInflationEnvelopePreservesOperationsEnvelopeAndHash(
        XdrTransactionEnvelope $envelope,
        string $taggedTransaction,
    ): void
    {
        [$inflation, $payment] = self::inflationXdrOperations();
        $input = base64_encode($envelope->encode());
        $network = Network::public();
        $expectedHash = $this->expectedTransactionHash($network, $taggedTransaction);
        $imported = AbstractTransaction::fromEnvelopeBase64XdrString($input);

        $transaction = $imported instanceof FeeBumpTransaction ? $imported->getInnerTx() : $imported;
        $this->assertInstanceOf(Transaction::class, $transaction);
        $parsed = $transaction->getOperations();
        $this->assertCount(2, $parsed);
        $this->assertInstanceOf(InflationOperation::class, $parsed[0]);
        $this->assertSame($inflation->encode(), $parsed[0]->toXdr()->encode());
        $this->assertInstanceOf(PaymentOperation::class, $parsed[1]);
        $this->assertSame($payment->encode(), $parsed[1]->toXdr()->encode());
        $this->assertSame($expectedHash, $imported->hash($network));
        $this->assertSame($input, $imported->toEnvelopeXdrBase64());
    }

    public static function zeroAndEmptySetOptionsProvider(): array
    {
        return [
            'empty home domain' => [new XdrSetOptionsOperation(homeDomain: '')],
            'clear flags 0' => [new XdrSetOptionsOperation(clearFlags: 0)],
        ];
    }

    #[DataProvider('zeroAndEmptySetOptionsProvider')]
    public function testImportedV1SetOptionsHashEqualsTheNetworkHash(XdrSetOptionsOperation $xdrSetOptions): void
    {
        $tx = self::setOptionsV1Transaction($xdrSetOptions);
        $envelope = new XdrTransactionEnvelope(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX));
        $envelope->setV1(new XdrTransactionV1Envelope($tx, []));

        $this->assertImportedHash($envelope, pack('N', XdrEnvelopeType::ENVELOPE_TYPE_TX) . $tx->encode());
    }

    #[DataProvider('zeroAndEmptySetOptionsProvider')]
    public function testImportedV0SetOptionsHashEqualsTheNetworkHash(XdrSetOptionsOperation $xdrSetOptions): void
    {
        $tx = new XdrTransactionV0(self::setOptionsSourceEd25519(), new XdrSequenceNumber(new BigInteger(101)),
            [self::setOptionsXdrOperation($xdrSetOptions)], 100, new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_NONE)),
            null, new XdrTransactionV0Ext(0));
        $envelope = new XdrTransactionEnvelope(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0));
        $envelope->setV0(new XdrTransactionV0Envelope($tx, []));

        // stellar-core hashes a V0 transaction in its V1 form: the KEY_TYPE_ED25519
        // discriminant of the muxed source account, followed by the V0 body.
        $this->assertImportedHash($envelope, pack('N', XdrEnvelopeType::ENVELOPE_TYPE_TX)
            . pack('N', XdrCryptoKeyType::KEY_TYPE_ED25519) . $tx->encode());
    }

    #[DataProvider('zeroAndEmptySetOptionsProvider')]
    public function testImportedFeeBumpSetOptionsHashEqualsTheNetworkHash(XdrSetOptionsOperation $xdrSetOptions): void
    {
        $innerTx = new XdrFeeBumpTransactionInnerTx(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX));
        $innerTx->setV1(new XdrTransactionV1Envelope(self::setOptionsV1Transaction($xdrSetOptions), []));
        $tx = new XdrFeeBumpTransaction(new XdrMuxedAccount(self::setOptionsSourceEd25519()), 400, $innerTx,
            new XdrFeeBumpTransactionExt(0));
        $envelope = new XdrTransactionEnvelope(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP));
        $envelope->setFeeBump(new XdrFeeBumpTransactionEnvelope($tx, []));

        $this->assertImportedHash($envelope, pack('N', XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP) . $tx->encode());
    }

    public function testImportedSacCreateContractV2EnvelopeKeepsItsBytesAndNetworkHash(): void
    {
        $imported = AbstractTransaction::fromEnvelopeBase64XdrString(self::SAC_V2_ENVELOPE);

        $this->assertSame(self::SAC_V2_ENVELOPE, $imported->toEnvelopeXdrBase64());
        $this->assertSame(self::SAC_V2_NETWORK_HASH, bin2hex($imported->hash(Network::testnet())));
    }

    public function testImportedFeeBumpOfSacCreateContractV2KeepsItsBytesAndHashes(): void
    {
        $network = Network::testnet();
        // The V1 envelope follows the 4-byte ENVELOPE_TYPE_TX discriminant.
        $innerTx = new XdrFeeBumpTransactionInnerTx(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX));
        $innerTx->setV1(XdrTransactionV1Envelope::decode(new XdrBuffer(substr(base64_decode(self::SAC_V2_ENVELOPE), 4))));
        $tx = new XdrFeeBumpTransaction(new XdrMuxedAccount(str_repeat("\x22", 32)), 200000, $innerTx,
            new XdrFeeBumpTransactionExt(0));
        $envelope = new XdrTransactionEnvelope(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP));
        $envelope->setFeeBump(new XdrFeeBumpTransactionEnvelope($tx,
            [new XdrDecoratedSignature(str_repeat("\x22", 4), str_repeat("\x33", 64))]));
        $input = base64_encode($envelope->encode());

        $imported = AbstractTransaction::fromEnvelopeBase64XdrString($input);

        $this->assertInstanceOf(FeeBumpTransaction::class, $imported);
        $this->assertSame($input, $imported->toEnvelopeXdrBase64());
        $this->assertSame(self::SAC_V2_NETWORK_HASH, bin2hex($imported->getInnerTx()->hash($network)));
        $this->assertSame(bin2hex($this->expectedTransactionHash($network,
            pack('N', XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP) . $tx->encode())), bin2hex($imported->hash($network)));
    }

    public function testImportedManageSellOfferWithNegativeOfferIdPreservesEnvelopeAndHash(): void
    {
        $source = new XdrMuxedAccount(str_repeat("\x11", 32));
        $selling = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $buying = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $buying->setAlphaNum4(new XdrAssetAlphaNum4(
            'USD',
            XdrAccountID::fromAccountId('GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO')
        ));
        $body = new XdrOperationBody(new XdrOperationType(XdrOperationType::MANAGE_SELL_OFFER));
        $body->setManageSellOfferOp(new XdrManageSellOfferOperation(
            $selling,
            $buying,
            new BigInteger(1000000000),
            new XdrPrice(3, 2),
            -1
        ));
        $xdrTx = new XdrTransaction(
            $source,
            new XdrSequenceNumber(new BigInteger(101)),
            [new XdrOperation($body)],
            200,
            new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_NONE)),
            new XdrPreconditions(XdrPreconditionType::NONE()),
            new XdrTransactionExt(0)
        );
        $envelope = new XdrTransactionEnvelope(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX));
        $envelope->setV1(new XdrTransactionV1Envelope($xdrTx, []));
        $input = base64_encode($envelope->encode());

        $network = Network::testnet();
        $expectedHash = $this->expectedTransactionHash(
            $network,
            pack('N', XdrEnvelopeType::ENVELOPE_TYPE_TX) . $xdrTx->encode()
        );

        $imported = AbstractTransaction::fromEnvelopeBase64XdrString($input);

        $this->assertInstanceOf(Transaction::class, $imported);
        $ops = $imported->getOperations();
        $this->assertCount(1, $ops);
        $this->assertInstanceOf(ManageSellOfferOperation::class, $ops[0]);
        $this->assertSame(-1, $ops[0]->getOfferId());
        $this->assertSame($input, $imported->toEnvelopeXdrBase64());
        $this->assertSame($expectedHash, $imported->hash($network));
    }

    public function testV0EnvelopeWithTimeBoundsAndTextMemoRoundTripsAsV0(): void
    {
        $envelope = self::buildV0EnvelopeFixture(withTimeBounds: true, textMemo: 'v0 memo');
        $input = base64_encode($envelope->encode());
        $expectedV1Body = self::buildEquivalentV1TransactionFixture(withTimeBounds: true, textMemo: 'v0 memo')->encode();

        $readers = [
            AbstractTransaction::fromEnvelopeBase64XdrString($input),
            AbstractTransaction::fromEnvelopeXdr(XdrTransactionEnvelope::fromEnvelopeBase64XdrString($input)),
            Transaction::fromV0EnvelopeXdr($envelope->getV0()),
        ];

        foreach ($readers as $imported) {
            $this->assertInstanceOf(Transaction::class, $imported);
            $written = $imported->toEnvelopeXdr();
            $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0, $written->getType()->getValue());
            $this->assertNotNull($written->getV0());
            $this->assertNull($written->getV1());
            $this->assertSame($input, base64_encode($written->encode()));
            $this->assertSame($input, $imported->toEnvelopeXdrBase64());
            $this->assertSame($expectedV1Body, $imported->toXdr()->encode());
            $this->assertSame(base64_encode($expectedV1Body), $imported->toXdrBase64());
        }
    }

    public function testV0EnvelopeWithoutTimeBoundsAndMemoNoneRoundTripsAsV0(): void
    {
        $envelope = self::buildV0EnvelopeFixture(withTimeBounds: false, textMemo: null);
        $input = base64_encode($envelope->encode());

        $imported = AbstractTransaction::fromEnvelopeBase64XdrString($input);

        $this->assertInstanceOf(Transaction::class, $imported);
        $written = $imported->toEnvelopeXdr();
        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0, $written->getType()->getValue());
        $this->assertNotNull($written->getV0());
        $this->assertNull($written->getV1());
        $this->assertNull($written->getV0()->getTx()->getTimeBounds());
        $this->assertSame($input, base64_encode($written->encode()));
        $this->assertSame($input, $imported->toEnvelopeXdrBase64());
    }

    public function testV0EnvelopeSignedAfterReadingKeepsV0AndVerifiesAgainstV1Hash(): void
    {
        $envelope = self::buildV0EnvelopeFixture(withTimeBounds: true, textMemo: 'v0 memo');
        $existingSig = $envelope->getV0()->getSignatures()[0];
        $input = base64_encode($envelope->encode());
        $signer = KeyPair::fromSeed('SCZANGBA5YHTNYVVV4C3U252E2B6P6F5T3U6MM63WBSBZATAQI3EBTQ4');
        $network = Network::testnet();

        $imported = AbstractTransaction::fromEnvelopeBase64XdrString($input);
        $this->assertInstanceOf(Transaction::class, $imported);
        $imported->sign($signer, $network);

        $written = $imported->toEnvelopeXdr();
        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0, $written->getType()->getValue());
        $this->assertNotNull($written->getV0());
        $this->assertNull($written->getV1());

        $signatures = $written->getV0()->getSignatures();
        $this->assertCount(2, $signatures);
        $this->assertSame($existingSig->encode(), $signatures[0]->encode());
        $this->assertSame($signer->getHint(), $signatures[1]->getHint());

        $expectedV1Tx = self::buildEquivalentV1TransactionFixture(withTimeBounds: true, textMemo: 'v0 memo');
        $expectedHash = $this->expectedTransactionHash(
            $network,
            pack('N', XdrEnvelopeType::ENVELOPE_TYPE_TX) . $expectedV1Tx->encode()
        );
        $actualHash = $imported->hash($network);
        $this->assertSame(bin2hex($expectedHash), bin2hex($actualHash));
        $this->assertTrue($signer->verifySignature($signatures[1]->getSignature(), $actualHash));

        $decoded = XdrTransactionEnvelope::fromEnvelopeBase64XdrString($imported->toEnvelopeXdrBase64());
        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0, $decoded->getType()->getValue());
        $this->assertCount(2, $decoded->getV0()->getSignatures());
        $this->assertSame($existingSig->encode(), $decoded->getV0()->getSignatures()[0]->encode());
        $this->assertSame($signatures[1]->encode(), $decoded->getV0()->getSignatures()[1]->encode());
    }

    public function testFeeBumpAroundV0TransactionWritesV1InnerAndRoundTrips(): void
    {
        $v0Envelope = self::buildV0EnvelopeFixture(withTimeBounds: true, textMemo: 'v0 memo');
        $existingSig = $v0Envelope->getV0()->getSignatures()[0];
        $v0Input = base64_encode($v0Envelope->encode());
        $innerTx = AbstractTransaction::fromEnvelopeBase64XdrString($v0Input);
        $this->assertInstanceOf(Transaction::class, $innerTx);

        $feeSourceAccountId = 'GCTIJ5XTJIGZSZI2H5K2XXMPDHDL4TSZ7TPHGTOVGNHWXZKAGHNBC7JH';
        $feeSource = MuxedAccount::fromAccountId($feeSourceAccountId);
        $network = Network::testnet();

        $expectedV1Tx = self::buildEquivalentV1TransactionFixture(withTimeBounds: true, textMemo: 'v0 memo');
        $expectedInnerV1 = new XdrTransactionV1Envelope($expectedV1Tx, [$existingSig]);
        $expectedInnerWrapper = new XdrFeeBumpTransactionInnerTx(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX));
        $expectedInnerWrapper->setV1($expectedInnerV1);
        $expectedFeeBumpTx = new XdrFeeBumpTransaction($feeSource->toXdr(), 400, $expectedInnerWrapper, new XdrFeeBumpTransactionExt(0));
        $expectedFeeBumpEnvelope = new XdrTransactionEnvelope(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP));
        $expectedFeeBumpEnvelope->setFeeBump(new XdrFeeBumpTransactionEnvelope($expectedFeeBumpTx, []));
        $expectedFeeBumpBase64 = base64_encode($expectedFeeBumpEnvelope->encode());
        $expectedFeeBumpHash = $this->expectedTransactionHash(
            $network,
            pack('N', XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP) . $expectedFeeBumpTx->encode()
        );

        $feeBumps = [
            new FeeBumpTransaction($feeSource, 400, $innerTx),
            (new FeeBumpTransactionBuilder($innerTx))
                ->setFeeAccount($feeSourceAccountId)
                ->setBaseFee(200)
                ->build(),
            (new FeeBumpTransactionBuilder($innerTx))
                ->setMuxedFeeAccount($feeSource)
                ->setBaseFee(200)
                ->build(),
        ];

        foreach ($feeBumps as $feeBump) {
            $written = $feeBump->toEnvelopeXdr();
            $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP, $written->getType()->getValue());
            $innerXdr = $written->getFeeBump()->getTx()->getInnerTx();
            $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX, $innerXdr->getType()->getValue());
            $this->assertNotNull($innerXdr->getV1());
            $this->assertSame($expectedV1Tx->encode(), $innerXdr->getV1()->getTx()->encode());
            $this->assertCount(1, $innerXdr->getV1()->getSignatures());
            $this->assertSame($existingSig->encode(), $innerXdr->getV1()->getSignatures()[0]->encode());

            $this->assertSame($expectedFeeBumpBase64, $feeBump->toEnvelopeXdrBase64());
            $this->assertSame(bin2hex($expectedFeeBumpHash), bin2hex($feeBump->hash($network)));

            // Wrapping a V0 transaction leaves the inner transaction's V0 form untouched.
            $this->assertSame($v0Input, $innerTx->toEnvelopeXdrBase64());

            foreach ([
                AbstractTransaction::fromEnvelopeBase64XdrString($feeBump->toEnvelopeXdrBase64()),
                FeeBumpTransaction::fromFeeBumpTransactionEnvelope($written->getFeeBump()),
            ] as $roundTripped) {
                $this->assertInstanceOf(FeeBumpTransaction::class, $roundTripped);
                $this->assertSame($expectedFeeBumpBase64, $roundTripped->toEnvelopeXdrBase64());
                $this->assertSame(bin2hex($expectedFeeBumpHash), bin2hex($roundTripped->hash($network)));
                $this->assertSame($feeSourceAccountId, $roundTripped->getFeeAccount()->getAccountId());
                $this->assertSame(400, $roundTripped->getFee());
                $this->assertSame('v0 memo', $roundTripped->getInnerTx()->getMemo()->getValue());
                $this->assertSame(1000, $roundTripped->getInnerTx()->getTimeBounds()->getMinTime()->getTimestamp());
                $this->assertSame(2000, $roundTripped->getInnerTx()->getTimeBounds()->getMaxTime()->getTimestamp());
                $this->assertCount(1, $roundTripped->getInnerTx()->getSignatures());
                $this->assertSame($existingSig->encode(), $roundTripped->getInnerTx()->getSignatures()[0]->encode());
            }
        }
    }

    public function testV1EnvelopeWithSameFieldsRoundTripsAsV1(): void
    {
        $v1Tx = self::buildEquivalentV1TransactionFixture(withTimeBounds: true, textMemo: 'v0 memo');
        $existingSig = self::fixedV0TestSignature();
        $v1Envelope = new XdrTransactionEnvelope(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX));
        $v1Envelope->setV1(new XdrTransactionV1Envelope($v1Tx, [$existingSig]));
        $input = base64_encode($v1Envelope->encode());

        $imported = AbstractTransaction::fromEnvelopeBase64XdrString($input);
        $this->assertInstanceOf(Transaction::class, $imported);
        $written = $imported->toEnvelopeXdr();
        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX, $written->getType()->getValue());
        $this->assertNotNull($written->getV1());
        $this->assertNull($written->getV0());
        $this->assertSame($input, $imported->toEnvelopeXdrBase64());

        $sourceAccount = new Account(self::v0FixtureSourceKeyPair()->getAccountId(), new BigInteger('12344'));
        $built = (new TransactionBuilder($sourceAccount))
            ->addOperation(new PaymentOperation(
                MuxedAccount::fromAccountId('GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO'),
                Asset::native(),
                '1'
            ))
            ->addMemo(Memo::text('v0 memo'))
            ->setTimeBounds(new TimeBounds(new DateTime('@1000'), new DateTime('@2000')))
            ->setMaxOperationFee(100)
            ->build();
        $built->addSignature($existingSig);
        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX, $built->toEnvelopeXdr()->getType()->getValue());
        $this->assertSame($input, $built->toEnvelopeXdrBase64());

        $v0Envelope = self::buildV0EnvelopeFixture(withTimeBounds: true, textMemo: 'v0 memo');
        $v0Input = base64_encode($v0Envelope->encode());
        $importedV0 = AbstractTransaction::fromEnvelopeBase64XdrString($v0Input);
        $this->assertInstanceOf(Transaction::class, $importedV0);
        $writtenV0 = $importedV0->toEnvelopeXdr();
        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0, $writtenV0->getType()->getValue());
        $this->assertNotNull($writtenV0->getV0());
        $this->assertNull($writtenV0->getV1());
        $this->assertSame($v0Input, $importedV0->toEnvelopeXdrBase64());
    }

    public function testV0SetSorobanTransactionDataSwitchesToV1AndBack(): void
    {
        $envelope = self::buildV0EnvelopeFixture(withTimeBounds: true, textMemo: 'v0 memo');
        $input = base64_encode($envelope->encode());
        $tx = AbstractTransaction::fromEnvelopeBase64XdrString($input);
        $this->assertInstanceOf(Transaction::class, $tx);

        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0, $tx->toEnvelopeXdr()->getType()->getValue());
        $this->assertSame($input, $tx->toEnvelopeXdrBase64());

        $tx->setSorobanTransactionData(null);
        $this->assertSame($input, $tx->toEnvelopeXdrBase64());

        $sorobanData = new XdrSorobanTransactionData(
            new XdrSorobanTransactionDataExt(0),
            new XdrSorobanResources(new XdrLedgerFootprint([], []), 100000, 200000, 300000),
            50000
        );
        $tx->setSorobanTransactionData($sorobanData);

        $written = $tx->toEnvelopeXdr();
        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX, $written->getType()->getValue());
        $this->assertNotNull($written->getV1());
        $this->assertNull($written->getV0());
        $this->assertSame(1, $written->getV1()->getTx()->getExt()->getDiscriminant());
        $this->assertSame($sorobanData->encode(), $written->getV1()->getTx()->getExt()->getSorobanTransactionData()->encode());

        $decoded = AbstractTransaction::fromEnvelopeBase64XdrString($tx->toEnvelopeXdrBase64());
        $this->assertInstanceOf(Transaction::class, $decoded);
        $this->assertSame(50000, $decoded->getSorobanTransactionData()->resourceFee);

        $tx->setSorobanTransactionData(null);
        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0, $tx->toEnvelopeXdr()->getType()->getValue());
        $this->assertSame($input, $tx->toEnvelopeXdrBase64());
    }

    public function testV0SetLedgerBoundsSwitchesToV1AndBack(): void
    {
        $envelope = self::buildV0EnvelopeFixture(withTimeBounds: true, textMemo: 'v0 memo');
        $input = base64_encode($envelope->encode());
        $tx = AbstractTransaction::fromEnvelopeBase64XdrString($input);
        $this->assertInstanceOf(Transaction::class, $tx);

        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0, $tx->toEnvelopeXdr()->getType()->getValue());
        $this->assertSame($input, $tx->toEnvelopeXdrBase64());

        $tx->getPreconditions()->setLedgerBounds(null);
        $this->assertSame($input, $tx->toEnvelopeXdrBase64());

        $tx->getPreconditions()->setLedgerBounds(new LedgerBounds(100, 200));

        $written = $tx->toEnvelopeXdr();
        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX, $written->getType()->getValue());
        $this->assertNotNull($written->getV1());
        $this->assertNull($written->getV0());
        $cond = $written->getV1()->getTx()->getPreconditions();
        $this->assertSame(XdrPreconditionType::V2, $cond->getType()->getValue());
        $this->assertSame(100, $cond->getV2()->getLedgerBounds()->getMinLedger());
        $this->assertSame(200, $cond->getV2()->getLedgerBounds()->getMaxLedger());
        $this->assertSame(1000, $cond->getV2()->getTimeBounds()->getMinTimestamp());
        $this->assertSame(2000, $cond->getV2()->getTimeBounds()->getMaxTimestamp());

        $tx->getPreconditions()->setLedgerBounds(null);
        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0, $tx->toEnvelopeXdr()->getType()->getValue());
        $this->assertSame($input, $tx->toEnvelopeXdrBase64());
    }

    public function testV0SetMinSeqNumberSwitchesToV1AndBack(): void
    {
        $envelope = self::buildV0EnvelopeFixture(withTimeBounds: true, textMemo: 'v0 memo');
        $input = base64_encode($envelope->encode());
        $tx = AbstractTransaction::fromEnvelopeBase64XdrString($input);
        $this->assertInstanceOf(Transaction::class, $tx);

        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0, $tx->toEnvelopeXdr()->getType()->getValue());
        $this->assertSame($input, $tx->toEnvelopeXdrBase64());

        $tx->getPreconditions()->setMinSeqNumber(null);
        $this->assertSame($input, $tx->toEnvelopeXdrBase64());

        $tx->getPreconditions()->setMinSeqNumber(new BigInteger('12340'));

        $written = $tx->toEnvelopeXdr();
        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX, $written->getType()->getValue());
        $this->assertNotNull($written->getV1());
        $this->assertNull($written->getV0());
        $cond = $written->getV1()->getTx()->getPreconditions();
        $this->assertSame(XdrPreconditionType::V2, $cond->getType()->getValue());
        $this->assertSame('12340', $cond->getV2()->getMinSeqNum()->sequenceNumber->toString());

        $tx->getPreconditions()->setMinSeqNumber(null);
        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0, $tx->toEnvelopeXdr()->getType()->getValue());
        $this->assertSame($input, $tx->toEnvelopeXdrBase64());
    }

    public function testV0SetMinSeqAgeSwitchesToV1AndBack(): void
    {
        $envelope = self::buildV0EnvelopeFixture(withTimeBounds: true, textMemo: 'v0 memo');
        $input = base64_encode($envelope->encode());
        $tx = AbstractTransaction::fromEnvelopeBase64XdrString($input);
        $this->assertInstanceOf(Transaction::class, $tx);

        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0, $tx->toEnvelopeXdr()->getType()->getValue());
        $this->assertSame($input, $tx->toEnvelopeXdrBase64());

        $tx->getPreconditions()->setMinSeqAge(0);
        $this->assertSame($input, $tx->toEnvelopeXdrBase64());

        $tx->getPreconditions()->setMinSeqAge(60);

        $written = $tx->toEnvelopeXdr();
        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX, $written->getType()->getValue());
        $this->assertNotNull($written->getV1());
        $this->assertNull($written->getV0());
        $cond = $written->getV1()->getTx()->getPreconditions();
        $this->assertSame(XdrPreconditionType::V2, $cond->getType()->getValue());
        $this->assertSame(60, $cond->getV2()->getMinSeqAge());

        $tx->getPreconditions()->setMinSeqAge(PHP_INT_MIN);
        $writtenUnsigned = $tx->toEnvelopeXdr();
        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX, $writtenUnsigned->getType()->getValue());
        $this->assertSame(PHP_INT_MIN, $writtenUnsigned->getV1()->getTx()->getPreconditions()->getV2()->getMinSeqAge());

        $tx->getPreconditions()->setMinSeqAge(0);
        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0, $tx->toEnvelopeXdr()->getType()->getValue());
        $this->assertSame($input, $tx->toEnvelopeXdrBase64());
    }

    public function testV0SetMinSeqLedgerGapSwitchesToV1AndBack(): void
    {
        $envelope = self::buildV0EnvelopeFixture(withTimeBounds: true, textMemo: 'v0 memo');
        $input = base64_encode($envelope->encode());
        $tx = AbstractTransaction::fromEnvelopeBase64XdrString($input);
        $this->assertInstanceOf(Transaction::class, $tx);

        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0, $tx->toEnvelopeXdr()->getType()->getValue());
        $this->assertSame($input, $tx->toEnvelopeXdrBase64());

        $tx->getPreconditions()->setMinSeqLedgerGap(0);
        $this->assertSame($input, $tx->toEnvelopeXdrBase64());

        $tx->getPreconditions()->setMinSeqLedgerGap(5);

        $written = $tx->toEnvelopeXdr();
        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX, $written->getType()->getValue());
        $this->assertNotNull($written->getV1());
        $this->assertNull($written->getV0());
        $cond = $written->getV1()->getTx()->getPreconditions();
        $this->assertSame(XdrPreconditionType::V2, $cond->getType()->getValue());
        $this->assertSame(5, $cond->getV2()->getMinSeqLedgerGap());

        $tx->getPreconditions()->setMinSeqLedgerGap(0);
        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0, $tx->toEnvelopeXdr()->getType()->getValue());
        $this->assertSame($input, $tx->toEnvelopeXdrBase64());
    }

    public function testV0SetExtraSignersSwitchesToV1AndBack(): void
    {
        $envelope = self::buildV0EnvelopeFixture(withTimeBounds: true, textMemo: 'v0 memo');
        $input = base64_encode($envelope->encode());
        $tx = AbstractTransaction::fromEnvelopeBase64XdrString($input);
        $this->assertInstanceOf(Transaction::class, $tx);

        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0, $tx->toEnvelopeXdr()->getType()->getValue());
        $this->assertSame($input, $tx->toEnvelopeXdrBase64());

        $tx->getPreconditions()->setExtraSigners([]);
        $this->assertSame($input, $tx->toEnvelopeXdrBase64());

        $extraSigner = Signer::ed25519PublicKey(
            KeyPair::fromAccountId('GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO')
        );
        $tx->getPreconditions()->setExtraSigners([$extraSigner]);

        $written = $tx->toEnvelopeXdr();
        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX, $written->getType()->getValue());
        $this->assertNotNull($written->getV1());
        $this->assertNull($written->getV0());
        $cond = $written->getV1()->getTx()->getPreconditions();
        $this->assertSame(XdrPreconditionType::V2, $cond->getType()->getValue());
        $this->assertCount(1, $cond->getV2()->getExtraSigners());
        $this->assertSame($extraSigner->encode(), $cond->getV2()->getExtraSigners()[0]->encode());

        $tx->getPreconditions()->setExtraSigners([]);
        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0, $tx->toEnvelopeXdr()->getType()->getValue());
        $this->assertSame($input, $tx->toEnvelopeXdrBase64());
    }

    public function testSparseOperationsArrayProducesListInXdr(): void
    {
        $destId = 'GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO';
        $op1 = new CreateAccountOperation($destId, '100');
        $op2 = new PaymentOperation(
            MuxedAccount::fromAccountId($destId),
            Asset::native(),
            '50'
        );
        $source = MuxedAccount::fromAccountId($this->sourceAccountId);
        $sequence = new BigInteger('101');

        $listTx = new Transaction($source, $sequence, [$op1, $op2]);
        $sparseTx = new Transaction($source, $sequence, [1 => $op1, 3 => $op2]);

        $listXdr = $listTx->toXdr();
        $sparseXdr = $sparseTx->toXdr();

        $sparseOps = $sparseXdr->getOperations();
        $this->assertSame([0, 1], array_keys($sparseOps));
        $this->assertSame($op1->toXdr()->encode(), $sparseOps[0]->encode());
        $this->assertSame($op2->toXdr()->encode(), $sparseOps[1]->encode());
        $this->assertSame($listTx->toXdrBase64(), $sparseTx->toXdrBase64());

        $listLines = [];
        $listXdr->toTxRep('tx', $listLines);
        $sparseLines = [];
        $sparseXdr->toTxRep('tx', $sparseLines);
        $this->assertSame($listLines, $sparseLines);

        $this->assertSame($listXdr->toJson(), $sparseXdr->toJson());
    }

    private static function v0FixtureSourceKeyPair(): KeyPair
    {
        return KeyPair::fromSeed('SDJHRQF4GCMIIKAAAQ6IHY42X73FQFLHUULAPSKKD4DFDM7UXWWCRHBE');
    }

    private static function fixedV0TestSignature(): XdrDecoratedSignature
    {
        return new XdrDecoratedSignature("\x01\x02\x03\x04", str_repeat("\xab", 64));
    }

    private static function buildV0FixtureOperation(): XdrOperation
    {
        $destEd25519 = KeyPair::fromAccountId('GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO')->getPublicKey();
        $paymentBody = new XdrOperationBody(new XdrOperationType(XdrOperationType::PAYMENT));
        $paymentBody->setPaymentOp(new XdrPaymentOperation(
            new XdrMuxedAccount($destEd25519),
            new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE)),
            new BigInteger('10000000')
        ));
        return new XdrOperation($paymentBody);
    }

    private static function buildV0FixtureMemo(?string $textMemo): XdrMemo
    {
        if ($textMemo === null) {
            return new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_NONE));
        }
        $memo = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_TEXT));
        $memo->setText($textMemo);
        return $memo;
    }

    private static function buildV0EnvelopeFixture(bool $withTimeBounds, ?string $textMemo): XdrTransactionEnvelope
    {
        $timeBounds = $withTimeBounds ? new XdrTimeBounds(new DateTime('@1000'), new DateTime('@2000')) : null;
        $v0Tx = new XdrTransactionV0(
            self::v0FixtureSourceKeyPair()->getPublicKey(),
            new XdrSequenceNumber(new BigInteger('12345')),
            [self::buildV0FixtureOperation()],
            100,
            self::buildV0FixtureMemo($textMemo),
            $timeBounds,
            new XdrTransactionV0Ext(0)
        );
        $envelope = new XdrTransactionEnvelope(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0));
        $envelope->setV0(new XdrTransactionV0Envelope($v0Tx, [self::fixedV0TestSignature()]));
        return $envelope;
    }

    private static function buildEquivalentV1TransactionFixture(bool $withTimeBounds, ?string $textMemo): XdrTransaction
    {
        if ($withTimeBounds) {
            $cond = new XdrPreconditions(new XdrPreconditionType(XdrPreconditionType::TIME));
            $cond->setTimeBounds(new XdrTimeBounds(new DateTime('@1000'), new DateTime('@2000')));
        } else {
            $cond = new XdrPreconditions(new XdrPreconditionType(XdrPreconditionType::NONE));
        }
        return new XdrTransaction(
            new XdrMuxedAccount(self::v0FixtureSourceKeyPair()->getPublicKey()),
            new XdrSequenceNumber(new BigInteger('12345')),
            [self::buildV0FixtureOperation()],
            100,
            self::buildV0FixtureMemo($textMemo),
            $cond,
            new XdrTransactionExt(0)
        );
    }

    private function assertImportedHash(XdrTransactionEnvelope $envelope, string $taggedTransaction): void
    {
        $network = Network::testnet();
        $imported = AbstractTransaction::fromEnvelopeXdr(XdrTransactionEnvelope::decode(new XdrBuffer($envelope->encode())));

        $this->assertSame(bin2hex($this->expectedTransactionHash($network, $taggedTransaction)), bin2hex($imported->hash($network)));
    }

    private function expectedTransactionHash(Network $network, string $taggedTransaction): string
    {
        $networkId = hash('sha256', $network->getNetworkPassphrase(), true);
        return hash('sha256', $networkId . $taggedTransaction, true);
    }

    private static function inflationXdrOperations(): array
    {
        $inflation = new XdrOperation(new XdrOperationBody(new XdrOperationType(XdrOperationType::INFLATION)));
        $paymentBody = new XdrOperationBody(new XdrOperationType(XdrOperationType::PAYMENT));
        $paymentBody->setPaymentOp(new XdrPaymentOperation(new XdrMuxedAccount(str_repeat("\x22", 32)),
            new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE)), new BigInteger(1234567)));
        $payment = new XdrOperation($paymentBody);
        return [$inflation, $payment];
    }

    private static function setOptionsV1Transaction(XdrSetOptionsOperation $xdrSetOptions): XdrTransaction
    {
        return new XdrTransaction(new XdrMuxedAccount(self::setOptionsSourceEd25519()),
            new XdrSequenceNumber(new BigInteger(101)), [self::setOptionsXdrOperation($xdrSetOptions)], 100,
            new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_NONE)), new XdrPreconditions(XdrPreconditionType::NONE()),
            new XdrTransactionExt(0));
    }

    private static function setOptionsXdrOperation(XdrSetOptionsOperation $xdrSetOptions): XdrOperation
    {
        $body = new XdrOperationBody(new XdrOperationType(XdrOperationType::SET_OPTIONS));
        $body->setSetOptionsOp($xdrSetOptions);
        return new XdrOperation($body);
    }

    private static function setOptionsSourceEd25519(): string
    {
        return KeyPair::fromAccountId('GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO')->getPublicKey();
    }
}
