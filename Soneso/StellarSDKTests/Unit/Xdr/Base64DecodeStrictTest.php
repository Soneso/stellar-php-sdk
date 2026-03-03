<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\AbstractTransaction;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Xdr\XdrContractCostParams;
use Soneso\StellarSDK\Xdr\XdrContractEvent;
use Soneso\StellarSDK\Xdr\XdrContractIDPreimage;
use Soneso\StellarSDK\Xdr\XdrDiagnosticEvent;
use Soneso\StellarSDK\Xdr\XdrInnerTransactionResult;
use Soneso\StellarSDK\Xdr\XdrInnerTransactionResultPair;
use Soneso\StellarSDK\Xdr\XdrLedgerEntry;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryData;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrSCEnvMetaEntry;
use Soneso\StellarSDK\Xdr\XdrSCMetaEntry;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntry;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrTransactionEvent;
use Soneso\StellarSDK\Xdr\XdrTransactionMeta;
use Soneso\StellarSDK\Xdr\XdrTransactionResult;

class Base64DecodeStrictTest extends TestCase
{
    #[Test]
    public function transactionEnvelopeRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        XdrTransactionEnvelope::fromEnvelopeBase64XdrString('not-valid!!!@#$');
    }

    #[Test]
    public function abstractTransactionRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        AbstractTransaction::fromEnvelopeBase64XdrString('invalid{base64}data');
    }

    #[Test]
    public function xdrTransactionMetaRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        XdrTransactionMeta::fromBase64Xdr('!!!invalid!!!');
    }

    #[Test]
    public function xdrTransactionResultRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        XdrTransactionResult::fromBase64Xdr('bad base64 data');
    }

    #[Test]
    public function xdrSCValRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        XdrSCVal::fromBase64Xdr('not~valid~base64');
    }

    #[Test]
    public function xdrLedgerKeyRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        XdrLedgerKey::fromBase64Xdr('###invalid###');
    }

    #[Test]
    public function xdrLedgerEntryRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        XdrLedgerEntry::fromBase64Xdr('bad{data}here');
    }

    #[Test]
    public function xdrSorobanTransactionDataRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        XdrSorobanTransactionData::fromBase64Xdr('!!!');
    }

    #[Test]
    public function xdrDiagnosticEventRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        XdrDiagnosticEvent::fromBase64Xdr('@@@invalid@@@');
    }

    #[Test]
    public function sorobanAuthorizationEntryRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        SorobanAuthorizationEntry::fromBase64Xdr('not valid base64!');
    }

    #[Test]
    public function base64WithInvalidCharactersIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        XdrTransactionMeta::fromBase64Xdr('AAAA@#$%AAAA');
    }

    #[Test]
    public function xdrContractEventRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        XdrContractEvent::fromBase64Xdr('not-valid!!!');
    }

    #[Test]
    public function xdrInnerTransactionResultRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        XdrInnerTransactionResult::fromBase64Xdr('bad{data}');
    }

    #[Test]
    public function xdrContractCostParamsRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        XdrContractCostParams::fromBase64Xdr('###invalid');
    }

    #[Test]
    public function xdrContractIDPreimageRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        XdrContractIDPreimage::fromBase64Xdr('@bad@');
    }

    #[Test]
    public function xdrInnerTransactionResultPairRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        XdrInnerTransactionResultPair::fromBase64Xdr('!!!bad!!!');
    }

    #[Test]
    public function xdrLedgerEntryDataRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        XdrLedgerEntryData::fromBase64Xdr('not~valid');
    }

    #[Test]
    public function xdrLedgerFootprintRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        XdrLedgerFootprint::fromBase64Xdr('bad base64!');
    }

    #[Test]
    public function xdrSCEnvMetaEntryRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        XdrSCEnvMetaEntry::fromBase64Xdr('invalid{chars}');
    }

    #[Test]
    public function xdrSCMetaEntryRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        XdrSCMetaEntry::fromBase64Xdr('@@@bad@@@');
    }

    #[Test]
    public function xdrSCSpecEntryRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        XdrSCSpecEntry::fromBase64Xdr('!!!invalid');
    }

    #[Test]
    public function xdrTransactionEventRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded XDR');
        XdrTransactionEvent::fromBase64Xdr('bad!data!here');
    }
}
