<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrAccountEntryV2;
use Soneso\StellarSDK\Xdr\XdrAccountEntryV2Ext;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrExtensionPoint;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryChange;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryChangeType;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyAccount;
use Soneso\StellarSDK\Xdr\XdrOperationMeta;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionMeta;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionMetaExt;
use Soneso\StellarSDK\Xdr\XdrTransactionMeta;
use Soneso\StellarSDK\Xdr\XdrTransactionMetaV1;
use Soneso\StellarSDK\Xdr\XdrTransactionMetaV2;
use Soneso\StellarSDK\Xdr\XdrTransactionMetaV3;
use Soneso\StellarSDK\Xdr\XdrTransactionMetaV4;

class XdrTransactionMetaTest extends TestCase
{
    private const ACCOUNT_ID_1 = "GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H";

    #[Test]
    public function testTransactionMetaV0RoundTrip(): void
    {
        $operationMeta = new XdrOperationMeta([]);
        $operations = [$operationMeta];

        $meta = new XdrTransactionMeta(0);
        $meta->setOperations($operations);

        $encoded = $meta->encode();
        $decoded = XdrTransactionMeta::decode(new XdrBuffer($encoded));

        $this->assertEquals(0, $decoded->getV());
        $this->assertCount(1, $decoded->getOperations());
        $this->assertNull($decoded->getV1());
        $this->assertNull($decoded->getV2());
        $this->assertNull($decoded->getV3());
        $this->assertNull($decoded->getV4());
    }

    #[Test]
    public function testTransactionMetaV0WithMultipleOperations(): void
    {
        $operations = [
            new XdrOperationMeta([]),
            new XdrOperationMeta([]),
            new XdrOperationMeta([]),
        ];

        $meta = new XdrTransactionMeta(0);
        $meta->setOperations($operations);

        $encoded = $meta->encode();
        $decoded = XdrTransactionMeta::decode(new XdrBuffer($encoded));

        $this->assertEquals(0, $decoded->getV());
        $this->assertCount(3, $decoded->getOperations());
    }

    #[Test]
    public function testTransactionMetaV1RoundTrip(): void
    {
        $ledgerChanges = [];
        $operations = [new XdrOperationMeta([])];

        $metaV1 = new XdrTransactionMetaV1($ledgerChanges, $operations);
        $meta = new XdrTransactionMeta(1);
        $meta->setV1($metaV1);

        $encoded = $meta->encode();
        $decoded = XdrTransactionMeta::decode(new XdrBuffer($encoded));

        $this->assertEquals(1, $decoded->getV());
        $this->assertNotNull($decoded->getV1());
        $this->assertCount(0, $decoded->getV1()->getLedgerEntryChanges());
        $this->assertCount(1, $decoded->getV1()->getOperations());
        $this->assertNull($decoded->getV2());
    }

    #[Test]
    public function testTransactionMetaV1WithLedgerChanges(): void
    {
        $ledgerChange = new XdrLedgerEntryChange(
            new XdrLedgerEntryChangeType(XdrLedgerEntryChangeType::LEDGER_ENTRY_REMOVED)
        );
        $ledgerKey = new XdrLedgerKey(new XdrLedgerEntryType(XdrLedgerEntryType::ACCOUNT));
        $ledgerKeyAccount = new XdrLedgerKeyAccount(XdrAccountID::fromAccountId(self::ACCOUNT_ID_1));
        $ledgerKey->account = $ledgerKeyAccount;
        $ledgerChange->setRemoved($ledgerKey);

        $ledgerChanges = [$ledgerChange];
        $operations = [];

        $metaV1 = new XdrTransactionMetaV1($ledgerChanges, $operations);
        $meta = new XdrTransactionMeta(1);
        $meta->setV1($metaV1);

        $encoded = $meta->encode();
        $decoded = XdrTransactionMeta::decode(new XdrBuffer($encoded));

        $this->assertEquals(1, $decoded->getV());
        $this->assertNotNull($decoded->getV1());
        $this->assertCount(1, $decoded->getV1()->getLedgerEntryChanges());
        $this->assertEquals(
            XdrLedgerEntryChangeType::LEDGER_ENTRY_REMOVED,
            $decoded->getV1()->getLedgerEntryChanges()[0]->type->value
        );
    }

    #[Test]
    public function testTransactionMetaV2RoundTrip(): void
    {
        $txChangesBefore = [];
        $operations = [new XdrOperationMeta([])];
        $txChangesAfter = [];

        $metaV2 = new XdrTransactionMetaV2($txChangesBefore, $operations, $txChangesAfter);
        $meta = new XdrTransactionMeta(2);
        $meta->setV2($metaV2);

        $encoded = $meta->encode();
        $decoded = XdrTransactionMeta::decode(new XdrBuffer($encoded));

        $this->assertEquals(2, $decoded->getV());
        $this->assertNotNull($decoded->getV2());
        $this->assertCount(0, $decoded->getV2()->getTxChangesBefore());
        $this->assertCount(1, $decoded->getV2()->getOperations());
        $this->assertCount(0, $decoded->getV2()->getTxChangesAfter());
        $this->assertNull($decoded->getV1());
        $this->assertNull($decoded->getV3());
    }

    #[Test]
    public function testTransactionMetaV2WithChanges(): void
    {
        $ledgerChange = new XdrLedgerEntryChange(
            new XdrLedgerEntryChangeType(XdrLedgerEntryChangeType::LEDGER_ENTRY_REMOVED)
        );
        $ledgerKey = new XdrLedgerKey(new XdrLedgerEntryType(XdrLedgerEntryType::ACCOUNT));
        $ledgerKeyAccount = new XdrLedgerKeyAccount(XdrAccountID::fromAccountId(self::ACCOUNT_ID_1));
        $ledgerKey->account = $ledgerKeyAccount;
        $ledgerChange->setRemoved($ledgerKey);

        $txChangesBefore = [$ledgerChange];
        $operations = [new XdrOperationMeta([])];
        $txChangesAfter = [$ledgerChange];

        $metaV2 = new XdrTransactionMetaV2($txChangesBefore, $operations, $txChangesAfter);
        $meta = new XdrTransactionMeta(2);
        $meta->setV2($metaV2);

        $encoded = $meta->encode();
        $decoded = XdrTransactionMeta::decode(new XdrBuffer($encoded));

        $this->assertEquals(2, $decoded->getV());
        $this->assertCount(1, $decoded->getV2()->getTxChangesBefore());
        $this->assertCount(1, $decoded->getV2()->getTxChangesAfter());
    }

    #[Test]
    public function testTransactionMetaV3RoundTrip(): void
    {
        $ext = new XdrExtensionPoint(0);
        $txChangesBefore = [];
        $operations = [];
        $txChangesAfter = [];
        $sorobanMeta = $this->createMinimalSorobanMeta();

        $metaV3 = new XdrTransactionMetaV3(
            $ext,
            $txChangesBefore,
            $operations,
            $txChangesAfter,
            $sorobanMeta
        );
        $meta = new XdrTransactionMeta(3);
        $meta->setV3($metaV3);

        $encoded = $meta->encode();
        $decoded = XdrTransactionMeta::decode(new XdrBuffer($encoded));

        $this->assertEquals(3, $decoded->getV());
        $this->assertNotNull($decoded->getV3());
        $this->assertNotNull($decoded->getV3()->getSorobanMeta());
        $this->assertCount(0, $decoded->getV3()->getTxChangesBefore());
        $this->assertCount(0, $decoded->getV3()->getOperations());
        $this->assertNull($decoded->getV2());
        $this->assertNull($decoded->getV4());
    }

    #[Test]
    public function testTransactionMetaV3Getters(): void
    {
        $ext = new XdrExtensionPoint(0);
        $txChangesBefore = [];
        $operations = [new XdrOperationMeta([])];
        $txChangesAfter = [];
        $sorobanMeta = $this->createMinimalSorobanMeta();

        $metaV3 = new XdrTransactionMetaV3(
            $ext,
            $txChangesBefore,
            $operations,
            $txChangesAfter,
            $sorobanMeta
        );

        $this->assertCount(1, $metaV3->getOperations());
        $this->assertNotNull($metaV3->getSorobanMeta());
        $this->assertEquals(0, $metaV3->getExt()->getDiscriminant());
    }

    #[Test]
    public function testTransactionMetaV3Setters(): void
    {
        $ext = new XdrExtensionPoint(0);
        $metaV3 = new XdrTransactionMetaV3($ext, [], [], [], $this->createMinimalSorobanMeta());

        $newOperations = [new XdrOperationMeta([]), new XdrOperationMeta([])];
        $metaV3->setOperations($newOperations);

        $newExt = new XdrExtensionPoint(1);
        $metaV3->setExt($newExt);

        $this->assertCount(2, $metaV3->getOperations());
        $this->assertEquals(1, $metaV3->getExt()->getDiscriminant());
    }

    #[Test]
    public function testTransactionMetaV4RoundTrip(): void
    {
        $ext = new XdrExtensionPoint(0);
        $txChangesBefore = [];
        $operations = [];
        $txChangesAfter = [];
        $sorobanMeta = null;
        $events = [];
        $diagnosticEvents = [];

        $metaV4 = new XdrTransactionMetaV4(
            $ext,
            $txChangesBefore,
            $operations,
            $txChangesAfter,
            $sorobanMeta,
            $events,
            $diagnosticEvents
        );
        $meta = new XdrTransactionMeta(4);
        $meta->setV4($metaV4);

        $encoded = $meta->encode();
        $decoded = XdrTransactionMeta::decode(new XdrBuffer($encoded));

        $this->assertEquals(4, $decoded->getV());
        $this->assertNotNull($decoded->getV4());
        $this->assertNull($decoded->getV4()->getSorobanMeta());
        $this->assertCount(0, $decoded->getV4()->getTxChangesBefore());
        $this->assertCount(0, $decoded->getV4()->getDiagnosticEvents());
        $this->assertNull($decoded->getV3());
    }

    #[Test]
    public function testTransactionMetaV4Getters(): void
    {
        $ext = new XdrExtensionPoint(0);
        $txChangesBefore = [];
        $operations = [];
        $txChangesAfter = [];
        $sorobanMeta = null;
        $events = [];
        $diagnosticEvents = [];

        $metaV4 = new XdrTransactionMetaV4(
            $ext,
            $txChangesBefore,
            $operations,
            $txChangesAfter,
            $sorobanMeta,
            $events,
            $diagnosticEvents
        );

        $this->assertCount(0, $metaV4->getTxChangesBefore());
        $this->assertCount(0, $metaV4->getOperations());
        $this->assertCount(0, $metaV4->getTxChangesAfter());
        $this->assertNull($metaV4->getSorobanMeta());
        $this->assertCount(0, $metaV4->getDiagnosticEvents());
        $this->assertCount(0, $metaV4->getEvents());
    }

    #[Test]
    public function testTransactionMetaV4Setters(): void
    {
        $ext = new XdrExtensionPoint(0);
        $metaV4 = new XdrTransactionMetaV4($ext, [], [], [], null, [], []);

        $newChangesBefore = [
            new XdrLedgerEntryChange(
                new XdrLedgerEntryChangeType(XdrLedgerEntryChangeType::LEDGER_ENTRY_REMOVED)
            )
        ];
        $metaV4->setTxChangesBefore($newChangesBefore);

        $newOperations = [];
        $metaV4->setOperations($newOperations);

        $this->assertCount(1, $metaV4->getTxChangesBefore());
        $this->assertCount(0, $metaV4->getOperations());
    }

    #[Test]
    public function testTransactionMetaBase64Conversion(): void
    {
        $meta = new XdrTransactionMeta(0);
        $meta->setOperations([]);

        $base64 = $meta->toBase64Xdr();
        $this->assertNotEmpty($base64);

        $decoded = XdrTransactionMeta::fromBase64Xdr($base64);
        $this->assertEquals(0, $decoded->getV());
    }

    #[Test]
    public function testTransactionMetaAllVersionsDistinct(): void
    {
        $metaV0 = new XdrTransactionMeta(0);
        $metaV0->setOperations([]);

        $metaV1 = new XdrTransactionMeta(1);
        $metaV1->setV1(new XdrTransactionMetaV1([], []));

        $metaV2 = new XdrTransactionMeta(2);
        $metaV2->setV2(new XdrTransactionMetaV2([], [], []));

        $ext = new XdrExtensionPoint(0);
        $metaV3 = new XdrTransactionMeta(3);
        $metaV3->setV3(new XdrTransactionMetaV3($ext, [], [], [], $this->createMinimalSorobanMeta()));

        $metaV4 = new XdrTransactionMeta(4);
        $metaV4->setV4(new XdrTransactionMetaV4($ext, [], [], [], null, [], []));

        $encodedV0 = $metaV0->encode();
        $encodedV1 = $metaV1->encode();
        $encodedV2 = $metaV2->encode();
        $encodedV3 = $metaV3->encode();
        $encodedV4 = $metaV4->encode();

        $decodedV0 = XdrTransactionMeta::decode(new XdrBuffer($encodedV0));
        $decodedV1 = XdrTransactionMeta::decode(new XdrBuffer($encodedV1));
        $decodedV2 = XdrTransactionMeta::decode(new XdrBuffer($encodedV2));
        $decodedV3 = XdrTransactionMeta::decode(new XdrBuffer($encodedV3));
        $decodedV4 = XdrTransactionMeta::decode(new XdrBuffer($encodedV4));

        $this->assertEquals(0, $decodedV0->getV());
        $this->assertEquals(1, $decodedV1->getV());
        $this->assertEquals(2, $decodedV2->getV());
        $this->assertEquals(3, $decodedV3->getV());
        $this->assertEquals(4, $decodedV4->getV());
    }

    #[Test]
    public function testTransactionMetaV1GettersAndSetters(): void
    {
        $metaV1 = new XdrTransactionMetaV1([], []);

        $this->assertCount(0, $metaV1->getLedgerEntryChanges());
        $this->assertCount(0, $metaV1->getOperations());

        $newChanges = [
            new XdrLedgerEntryChange(
                new XdrLedgerEntryChangeType(XdrLedgerEntryChangeType::LEDGER_ENTRY_REMOVED)
            )
        ];
        $metaV1->setLedgerEntryChanges($newChanges);

        $newOperations = [new XdrOperationMeta([])];
        $metaV1->setOperations($newOperations);

        $this->assertCount(1, $metaV1->getLedgerEntryChanges());
        $this->assertCount(1, $metaV1->getOperations());
    }

    #[Test]
    public function testTransactionMetaV2GettersAndSetters(): void
    {
        $metaV2 = new XdrTransactionMetaV2([], [], []);

        $this->assertCount(0, $metaV2->getTxChangesBefore());
        $this->assertCount(0, $metaV2->getOperations());
        $this->assertCount(0, $metaV2->getTxChangesAfter());

        $newChanges = [
            new XdrLedgerEntryChange(
                new XdrLedgerEntryChangeType(XdrLedgerEntryChangeType::LEDGER_ENTRY_REMOVED)
            )
        ];
        $metaV2->setTxChangesBefore($newChanges);
        $metaV2->setTxChangesAfter($newChanges);

        $newOperations = [new XdrOperationMeta([])];
        $metaV2->setOperations($newOperations);

        $this->assertCount(1, $metaV2->getTxChangesBefore());
        $this->assertCount(1, $metaV2->getOperations());
        $this->assertCount(1, $metaV2->getTxChangesAfter());
    }

    #[Test]
    public function testOperationMetaGettersAndSetters(): void
    {
        $opMeta = new XdrOperationMeta([]);

        $this->assertCount(0, $opMeta->getLedgerEntryChanges());

        $newChanges = [
            new XdrLedgerEntryChange(
                new XdrLedgerEntryChangeType(XdrLedgerEntryChangeType::LEDGER_ENTRY_REMOVED)
            )
        ];
        $opMeta->setLedgerEntryChanges($newChanges);

        $this->assertCount(1, $opMeta->getLedgerEntryChanges());
    }

    #[Test]
    public function testTransactionMetaSetters(): void
    {
        $meta = new XdrTransactionMeta(0);

        $this->assertEquals(0, $meta->getV());

        $meta->setV(2);
        $this->assertEquals(2, $meta->getV());

        $operations = [new XdrOperationMeta([])];
        $meta->setOperations($operations);
        $this->assertCount(1, $meta->getOperations());

        $metaV1 = new XdrTransactionMetaV1([], []);
        $meta->setV1($metaV1);
        $this->assertNotNull($meta->getV1());

        $metaV2 = new XdrTransactionMetaV2([], [], []);
        $meta->setV2($metaV2);
        $this->assertNotNull($meta->getV2());

        $ext = new XdrExtensionPoint(0);
        $metaV3 = new XdrTransactionMetaV3($ext, [], [], [], $this->createMinimalSorobanMeta());
        $meta->setV3($metaV3);
        $this->assertNotNull($meta->getV3());

        $metaV4 = new XdrTransactionMetaV4($ext, [], [], [], null, [], []);
        $meta->setV4($metaV4);
        $this->assertNotNull($meta->getV4());
    }

    private function createMinimalSorobanMeta(): XdrSorobanTransactionMeta
    {
        $ext = new XdrSorobanTransactionMetaExt(0, null);
        $returnValue = new XdrSCVal(new XdrSCValType(XdrSCValType::SCV_VOID));

        return new XdrSorobanTransactionMeta(
            $ext,
            [],
            $returnValue,
            []
        );
    }
}
