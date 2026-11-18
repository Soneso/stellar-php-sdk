<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Core;

use DateTime;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\AbstractTransaction;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\BumpSequenceOperationBuilder;
use Soneso\StellarSDK\FeeBumpTransaction;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\TimeBounds;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\TransactionPreconditions;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrPreconditions;
use Soneso\StellarSDK\Xdr\XdrPreconditionsV2;
use Soneso\StellarSDK\Xdr\XdrPreconditionType;

class TransactionPreconditionsTest extends TestCase
{
    public function testMainnetV2EnvelopePreservesHashAndBytes(): void
    {
        $input = 'AAAAAgAAAAAvXfCKt7y3JmM05cUEmx2N+YSNV1HLvkEPaziIeWJVHgBMS0ADVZxQAAJ+KQAAAAIAAAAB'
            . 'AAAAAAAAAAAAAAAAaOLQmQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABAAAAAQAAAADFHL84DnXIni7dgvSYo1/a'
            . 'MqqQDLeRh9gwUh3MymFwGAAAAA0AAAAAAAAAA7egsHMAAAAAxRy/OA51yJ4u3YL0mKNf2jKqkAy3kYfYMFIdzMphcBgA'
            . 'AAABU0hYAAAAAADlOMj3OZP8orENyYJ2H+XpUVJEUQMf0EclKEV/Tfo+pAAAAFwCSv6aAAAAAAAAAAAAAAACymFwGAAA'
            . 'AEA3pfR3+Np6BC3M5npshPbc+yU9ALZfhaHmuH94U6PyCTE5Ym1rObdNWYGsfKcHcqntA00mgWqCDExniuArYIYDeWJV'
            . 'HgAAAECf7w1XnQFGG5Bo8PFlGBfjPsJsTNjQGpK3CXoGcmfD9qPKR7Lm98BtuCzmkIsvA3ENHoOjBFSy208U9U0qDMwI';
        $transaction = AbstractTransaction::fromEnvelopeBase64XdrString($input);

        self::assertSame(
            '68f9605938736ef04b390e10807c82105d62ff750d4a6107c259470ab6276ce3',
            bin2hex($transaction->hash(Network::public()))
        );
        self::assertSame($input, $transaction->toEnvelopeXdrBase64());
    }

    public function testDefaultV2EnvelopePreservesArmAndBytes(): void
    {
        $envelope = self::builder()->build()->toEnvelopeXdr();
        $envelope->getV1()->getTx()->setPreconditions(self::defaultV2());
        $input = base64_encode($envelope->encode());
        $transaction = AbstractTransaction::fromEnvelopeBase64XdrString($input);

        self::assertInstanceOf(Transaction::class, $transaction);
        self::assertTrue($transaction->getPreconditions()->hasV2());
        self::assertNull($transaction->getTimeBounds());
        self::assertSame(XdrPreconditionType::V2, $transaction->toXdr()->getPreconditions()->getType()->getValue());
        self::assertSame($input, $transaction->toEnvelopeXdrBase64());
    }

    public function testUnsignedMinSeqAgeSelectsV2(): void
    {
        $preconditions = new TransactionPreconditions();
        $preconditions->setMinSeqAge(PHP_INT_MIN);
        $xdr = $preconditions->toXdr();

        self::assertSame(XdrPreconditionType::V2, $xdr->getType()->getValue());
        self::assertTrue($preconditions->hasV2());
        self::assertSame(
            '0000000200000000000000000000000080000000000000000000000000000000',
            bin2hex($xdr->encode())
        );
    }

    public function testUnsignedGettersReturnDecodedValuesAsDecimalStrings(): void
    {
        $cases = [
            ['8000000000000000', 'ffffffffffffffff', '8000000000000000',
                '9223372036854775808', '18446744073709551615', '9223372036854775808'],
            ['ffffffffffffffff', '8000000000000000', 'ffffffffffffffff',
                '18446744073709551615', '9223372036854775808', '18446744073709551615'],
            ['0000000000000005', '0000000000000007', '0000000000000003', '5', '7', '3'],
        ];
        foreach ($cases as [$minTime, $maxTime, $minSeqAge, $minTimeString, $maxTimeString, $minSeqAgeString]) {
            // PRECOND_V2 with time bounds, no ledger bounds, no minimum sequence number,
            // the minimum sequence age, ledger gap 0 and no extra signers.
            $hex = '00000002' . '00000001' . $minTime . $maxTime . '00000000' . '00000000'
                . $minSeqAge . '00000000' . '00000000';
            $preconditions = TransactionPreconditions::fromXdr(XdrPreconditions::decode(new XdrBuffer(hex2bin($hex))));

            self::assertSame($minTimeString, $preconditions->getTimeBounds()->getMinTimeAsString());
            self::assertSame($maxTimeString, $preconditions->getTimeBounds()->getMaxTimeAsString());
            self::assertSame($minSeqAgeString, $preconditions->getMinSeqAgeAsString());
        }
    }

    public function testFeeBumpDefaultV2PreservesBytesAndBothHashes(): void
    {
        $inner = self::builder()->build();
        $envelope = (new FeeBumpTransaction($inner->getSourceAccount(), 200, $inner))->toEnvelopeXdr();
        $outerXdr = $envelope->getFeeBump()->getTx();
        $innerXdr = $outerXdr->getInnerTx()->getV1()->getTx();
        $innerXdr->setPreconditions(self::defaultV2());
        $input = base64_encode($envelope->encode());
        $network = Network::testnet();
        $networkId = hash('sha256', $network->getNetworkPassphrase(), true);
        $outerHash = hash('sha256', $networkId . pack('N', 5) . $outerXdr->encode(), true);
        $innerHash = hash('sha256', $networkId . pack('N', 2) . $innerXdr->encode(), true);
        $transaction = AbstractTransaction::fromEnvelopeBase64XdrString($input);

        self::assertInstanceOf(FeeBumpTransaction::class, $transaction);
        self::assertSame(bin2hex($outerHash), bin2hex($transaction->hash($network)));
        self::assertSame(bin2hex($innerHash), bin2hex($transaction->getInnerTx()->hash($network)));
        self::assertSame($input, $transaction->toEnvelopeXdrBase64());
    }

    public function testNoneAndTimeArmsAndBuilderTimeBoundsStayUnchanged(): void
    {
        $timeBounds = new TimeBounds(new DateTime('@1000'), new DateTime('@2000'));
        $transactions = [
            XdrPreconditionType::NONE => self::builder()->build(),
            XdrPreconditionType::TIME => self::builder()->setTimeBounds($timeBounds)->build(),
        ];
        foreach ($transactions as $type => $original) {
            self::assertSame($type, $original->toXdr()->getPreconditions()->getType()->getValue());
            $input = $original->toEnvelopeXdrBase64();
            $decoded = AbstractTransaction::fromEnvelopeBase64XdrString($input);
            self::assertInstanceOf(Transaction::class, $decoded);
            self::assertFalse($decoded->getPreconditions()->hasV2());
            self::assertSame($type, $decoded->toXdr()->getPreconditions()->getType()->getValue());
            self::assertSame($input, $decoded->toEnvelopeXdrBase64());
        }
        self::assertSame(
            '0000000100000000000003e800000000000007d0',
            bin2hex($transactions[XdrPreconditionType::TIME]->toXdr()->getPreconditions()->encode())
        );
    }

    private static function builder(): TransactionBuilder
    {
        $account = new Account('GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ', new BigInteger(1));
        return (new TransactionBuilder($account))
            ->addOperation((new BumpSequenceOperationBuilder(new BigInteger(5)))->build());
    }

    private static function defaultV2(): XdrPreconditions
    {
        $preconditions = new XdrPreconditions(XdrPreconditionType::V2());
        $preconditions->setV2(new XdrPreconditionsV2(0, 0, []));
        return $preconditions;
    }
}
