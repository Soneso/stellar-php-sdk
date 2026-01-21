<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrAccountMergeResult;
use Soneso\StellarSDK\Xdr\XdrAccountMergeResultCode;
use Soneso\StellarSDK\Xdr\XdrAllowTrustResult;
use Soneso\StellarSDK\Xdr\XdrAllowTrustResultCode;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrBumpSequenceResult;
use Soneso\StellarSDK\Xdr\XdrBumpSequenceResultCode;
use Soneso\StellarSDK\Xdr\XdrChangeTrustResult;
use Soneso\StellarSDK\Xdr\XdrChangeTrustResultCode;
use Soneso\StellarSDK\Xdr\XdrClawbackClaimableBalanceResult;
use Soneso\StellarSDK\Xdr\XdrClawbackClaimableBalanceResultCode;
use Soneso\StellarSDK\Xdr\XdrCreateAccountResult;
use Soneso\StellarSDK\Xdr\XdrExtendFootprintTTLResult;
use Soneso\StellarSDK\Xdr\XdrExtendFootprintTTLResultCode;
use Soneso\StellarSDK\Xdr\XdrInflationResult;
use Soneso\StellarSDK\Xdr\XdrInflationResultCode;
use Soneso\StellarSDK\Xdr\XdrInvokeHostFunctionResult;
use Soneso\StellarSDK\Xdr\XdrInvokeHostFunctionResultCode;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolDepositResult;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolDepositResultCode;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolWithdrawResult;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolWithdrawResultCode;
use Soneso\StellarSDK\Xdr\XdrManageDataResult;
use Soneso\StellarSDK\Xdr\XdrManageDataResultCode;
use Soneso\StellarSDK\Xdr\XdrManageOfferResult;
use Soneso\StellarSDK\Xdr\XdrManageOfferResultCode;
use Soneso\StellarSDK\Xdr\XdrOperationResult;
use Soneso\StellarSDK\Xdr\XdrOperationResultCode;
use Soneso\StellarSDK\Xdr\XdrOperationResultTr;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrPathPaymentStrictReceiveResult;
use Soneso\StellarSDK\Xdr\XdrPathPaymentStrictReceiveResultCode;
use Soneso\StellarSDK\Xdr\XdrPathPaymentStrictSendResult;
use Soneso\StellarSDK\Xdr\XdrPathPaymentStrictSendResultCode;
use Soneso\StellarSDK\Xdr\XdrPaymentResult;
use Soneso\StellarSDK\Xdr\XdrPaymentResultCode;
use Soneso\StellarSDK\Xdr\XdrSetOptionsResult;
use Soneso\StellarSDK\Xdr\XdrSetOptionsResultCode;

class XdrOperationResultTest extends TestCase
{
    #[Test]
    public function testCreateAccountResultRoundTrip(): void
    {
        $result = new XdrCreateAccountResult();
        $result->setResultCode(new XdrOperationResultCode(XdrOperationResultCode::INNER));

        $encoded = $result->encode();
        $decoded = XdrCreateAccountResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrOperationResultCode::INNER, $decoded->getResultCode()->getValue());
    }

    #[Test]
    public function testPaymentResultRoundTrip(): void
    {
        $result = new XdrPaymentResult();
        $resultCode = new XdrPaymentResultCode(XdrPaymentResultCode::SUCCESS);
        $result->setResultCode($resultCode);

        $encoded = $result->encode();
        $decoded = XdrPaymentResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrPaymentResultCode::SUCCESS, $decoded->getResultCode()->getValue());
    }

    #[Test]
    public function testPathPaymentStrictReceiveResultSuccess(): void
    {
        $result = new XdrPathPaymentStrictReceiveResult();
        $code = new XdrPathPaymentStrictReceiveResultCode(XdrPathPaymentStrictReceiveResultCode::SUCCESS);

        $encoded = $code->encode();
        $decoded = XdrPathPaymentStrictReceiveResultCode::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrPathPaymentStrictReceiveResultCode::SUCCESS, $decoded->getValue());
    }

    #[Test]
    public function testPathPaymentStrictSendResultSuccess(): void
    {
        $result = new XdrPathPaymentStrictSendResult();
        $code = new XdrPathPaymentStrictSendResultCode(XdrPathPaymentStrictSendResultCode::SUCCESS);

        $encoded = $code->encode();
        $decoded = XdrPathPaymentStrictSendResultCode::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrPathPaymentStrictSendResultCode::SUCCESS, $decoded->getValue());
    }

    #[Test]
    public function testManageOfferResultNonSuccess(): void
    {
        $code = new XdrManageOfferResultCode(XdrManageOfferResultCode::LINE_FULL);
        $result = new XdrManageOfferResult($code);

        $encoded = $result->encode();
        $decoded = XdrManageOfferResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrManageOfferResultCode::LINE_FULL, $decoded->getCode()->getValue());
        $this->assertNull($decoded->getSuccess());
    }

    #[Test]
    public function testSetOptionsResultRoundTrip(): void
    {
        $result = new XdrSetOptionsResult();
        $resultCode = new XdrSetOptionsResultCode(XdrSetOptionsResultCode::SUCCESS);
        $result->setResultCode($resultCode);

        $encoded = $result->encode();
        $decoded = XdrSetOptionsResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSetOptionsResultCode::SUCCESS, $decoded->getResultCode()->getValue());
    }

    #[Test]
    public function testChangeTrustResultRoundTrip(): void
    {
        $result = new XdrChangeTrustResult();
        $resultCode = new XdrChangeTrustResultCode(XdrChangeTrustResultCode::SUCCESS);
        $result->setResultCode($resultCode);

        $encoded = $result->encode();
        $decoded = XdrChangeTrustResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrChangeTrustResultCode::SUCCESS, $decoded->getResultCode()->getValue());
    }

    #[Test]
    public function testAllowTrustResultRoundTrip(): void
    {
        $result = new XdrAllowTrustResult();
        $resultCode = new XdrAllowTrustResultCode(XdrAllowTrustResultCode::SUCCESS);
        $result->setResultCode($resultCode);

        $encoded = $result->encode();
        $decoded = XdrAllowTrustResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrAllowTrustResultCode::SUCCESS, $decoded->getResultCode()->getValue());
    }

    #[Test]
    public function testAccountMergeResultNonSuccess(): void
    {
        $resultCode = new XdrAccountMergeResultCode(XdrAccountMergeResultCode::NO_ACCOUNT);
        $result = new XdrAccountMergeResult();
        $result->setResultCode($resultCode);

        $encoded = $result->encode();
        $decoded = XdrAccountMergeResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrAccountMergeResultCode::NO_ACCOUNT, $decoded->getResultCode()->getValue());
    }

    #[Test]
    public function testInflationResultRoundTrip(): void
    {
        $resultCode = new XdrInflationResultCode(XdrInflationResultCode::NOT_TIME);
        $result = new XdrInflationResult($resultCode);

        $encoded = $result->encode();
        $decoded = XdrInflationResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrInflationResultCode::NOT_TIME, $decoded->getCode()->getValue());
    }

    #[Test]
    public function testManageDataResultRoundTrip(): void
    {
        $resultCode = new XdrManageDataResultCode(XdrManageDataResultCode::SUCCESS);
        $result = new XdrManageDataResult($resultCode);

        $encoded = $result->encode();
        $decoded = XdrManageDataResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrManageDataResultCode::SUCCESS, $decoded->getCode()->getValue());
    }

    #[Test]
    public function testBumpSequenceResultRoundTrip(): void
    {
        $result = new XdrBumpSequenceResult();
        $resultCode = new XdrBumpSequenceResultCode(XdrBumpSequenceResultCode::SUCCESS);
        $result->setResultCode($resultCode);

        $encoded = $result->encode();
        $decoded = XdrBumpSequenceResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrBumpSequenceResultCode::SUCCESS, $decoded->getResultCode()->getValue());
    }

    #[Test]
    public function testExtendFootprintTTLResultRoundTrip(): void
    {
        $resultCode = new XdrExtendFootprintTTLResultCode(XdrExtendFootprintTTLResultCode::EXTEND_FOOTPRINT_TTL_SUCCESS);
        $result = new XdrExtendFootprintTTLResult($resultCode);

        $encoded = $result->encode();
        $decoded = XdrExtendFootprintTTLResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrExtendFootprintTTLResultCode::EXTEND_FOOTPRINT_TTL_SUCCESS, $decoded->getCode()->getValue());
    }

    #[Test]
    public function testInvokeHostFunctionResultRoundTrip(): void
    {
        $resultCode = new XdrInvokeHostFunctionResultCode(XdrInvokeHostFunctionResultCode::INVOKE_HOST_FUNCTION_MALFORMED);
        $result = new XdrInvokeHostFunctionResult($resultCode);

        $encoded = $result->encode();
        $decoded = XdrInvokeHostFunctionResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrInvokeHostFunctionResultCode::INVOKE_HOST_FUNCTION_MALFORMED, $decoded->type->value);
    }

    #[Test]
    public function testClawbackClaimableBalanceResultRoundTrip(): void
    {
        $resultCode = new XdrClawbackClaimableBalanceResultCode(XdrClawbackClaimableBalanceResultCode::SUCCESS);
        $result = new XdrClawbackClaimableBalanceResult($resultCode);

        $encoded = $result->encode();
        $decoded = XdrClawbackClaimableBalanceResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(
            XdrClawbackClaimableBalanceResultCode::SUCCESS,
            $decoded->getResultCode()->getValue()
        );
    }

    #[Test]
    public function testLiquidityPoolDepositResultRoundTrip(): void
    {
        $result = new XdrLiquidityPoolDepositResult();
        $resultCode = new XdrLiquidityPoolDepositResultCode(XdrLiquidityPoolDepositResultCode::SUCCESS);
        $result->setResultCode($resultCode);

        $encoded = $result->encode();
        $decoded = XdrLiquidityPoolDepositResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLiquidityPoolDepositResultCode::SUCCESS, $decoded->getResultCode()->getValue());
    }

    #[Test]
    public function testLiquidityPoolWithdrawResultRoundTrip(): void
    {
        $result = new XdrLiquidityPoolWithdrawResult();
        $resultCode = new XdrLiquidityPoolWithdrawResultCode(XdrLiquidityPoolWithdrawResultCode::SUCCESS);
        $result->setResultCode($resultCode);

        $encoded = $result->encode();
        $decoded = XdrLiquidityPoolWithdrawResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrLiquidityPoolWithdrawResultCode::SUCCESS, $decoded->getResultCode()->getValue());
    }

    #[Test]
    public function testOperationResultCodeValues(): void
    {
        $codes = [
            XdrOperationResultCode::INNER,
            XdrOperationResultCode::BAD_AUTH,
            XdrOperationResultCode::NO_ACCOUNT,
            XdrOperationResultCode::NOT_SUPPORTED,
            XdrOperationResultCode::TOO_MANY_SUBENTRIES,
            XdrOperationResultCode::EXCEEDED_WORK_LIMIT,
        ];

        foreach ($codes as $codeValue) {
            $code = new XdrOperationResultCode($codeValue);
            $encoded = $code->encode();
            $decoded = XdrOperationResultCode::decode(new XdrBuffer($encoded));

            $this->assertEquals($codeValue, $decoded->getValue());
        }
    }

    #[Test]
    public function testOperationResultTrCreateAccount(): void
    {
        $tr = new XdrOperationResultTr();
        $type = new XdrOperationType(XdrOperationType::CREATE_ACCOUNT);

        $encoded = $type->encode();
        $buffer = new XdrBuffer($encoded);

        $createAccountResult = new XdrCreateAccountResult();
        $createAccountResult->setResultCode(new XdrOperationResultCode(XdrOperationResultCode::INNER));
        $buffer = new XdrBuffer($type->encode() . $createAccountResult->encode());

        $decoded = XdrOperationResultTr::decode($buffer);

        $this->assertEquals(XdrOperationType::CREATE_ACCOUNT, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getCreateAccountResult());
        $this->assertNull($decoded->getPaymentResult());
    }

    #[Test]
    public function testOperationResultTrPayment(): void
    {
        $type = new XdrOperationType(XdrOperationType::PAYMENT);
        $paymentResult = new XdrPaymentResult();
        $paymentResult->setResultCode(new XdrPaymentResultCode(XdrPaymentResultCode::SUCCESS));

        $buffer = new XdrBuffer($type->encode() . $paymentResult->encode());
        $decoded = XdrOperationResultTr::decode($buffer);

        $this->assertEquals(XdrOperationType::PAYMENT, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getPaymentResult());
        $this->assertNull($decoded->getCreateAccountResult());
    }

    #[Test]
    public function testOperationResultTrManageSellOffer(): void
    {
        $type = new XdrOperationType(XdrOperationType::MANAGE_SELL_OFFER);
        $manageOfferResult = new XdrManageOfferResult(
            new XdrManageOfferResultCode(XdrManageOfferResultCode::SELL_NO_TRUST)
        );

        $buffer = new XdrBuffer($type->encode() . $manageOfferResult->encode());
        $decoded = XdrOperationResultTr::decode($buffer);

        $this->assertEquals(XdrOperationType::MANAGE_SELL_OFFER, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getManageOfferResult());
    }

    #[Test]
    public function testOperationResultTrManageBuyOffer(): void
    {
        $type = new XdrOperationType(XdrOperationType::MANAGE_BUY_OFFER);
        $manageOfferResult = new XdrManageOfferResult(
            new XdrManageOfferResultCode(XdrManageOfferResultCode::BUY_NO_TRUST)
        );

        $buffer = new XdrBuffer($type->encode() . $manageOfferResult->encode());
        $decoded = XdrOperationResultTr::decode($buffer);

        $this->assertEquals(XdrOperationType::MANAGE_BUY_OFFER, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getManageOfferResult());
    }

    #[Test]
    public function testOperationResultTrSetOptions(): void
    {
        $type = new XdrOperationType(XdrOperationType::SET_OPTIONS);
        $setOptionsResult = new XdrSetOptionsResult();
        $setOptionsResult->setResultCode(new XdrSetOptionsResultCode(XdrSetOptionsResultCode::SUCCESS));

        $buffer = new XdrBuffer($type->encode() . $setOptionsResult->encode());
        $decoded = XdrOperationResultTr::decode($buffer);

        $this->assertEquals(XdrOperationType::SET_OPTIONS, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getSetOptionsResult());
    }

    #[Test]
    public function testOperationResultTrChangeTrust(): void
    {
        $type = new XdrOperationType(XdrOperationType::CHANGE_TRUST);
        $changeTrustResult = new XdrChangeTrustResult();
        $changeTrustResult->setResultCode(new XdrChangeTrustResultCode(XdrChangeTrustResultCode::SUCCESS));

        $buffer = new XdrBuffer($type->encode() . $changeTrustResult->encode());
        $decoded = XdrOperationResultTr::decode($buffer);

        $this->assertEquals(XdrOperationType::CHANGE_TRUST, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getChangeTrustResult());
    }

    #[Test]
    public function testOperationResultTrBumpSequence(): void
    {
        $type = new XdrOperationType(XdrOperationType::BUMP_SEQUENCE);
        $bumpSequenceResult = new XdrBumpSequenceResult();
        $bumpSequenceResult->setResultCode(new XdrBumpSequenceResultCode(XdrBumpSequenceResultCode::SUCCESS));

        $buffer = new XdrBuffer($type->encode() . $bumpSequenceResult->encode());
        $decoded = XdrOperationResultTr::decode($buffer);

        $this->assertEquals(XdrOperationType::BUMP_SEQUENCE, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getBumpSequenceResult());
    }

    #[Test]
    public function testOperationResultTrAccountMerge(): void
    {
        $type = new XdrOperationType(XdrOperationType::ACCOUNT_MERGE);
        $accountMergeResult = new XdrAccountMergeResult();
        $accountMergeResult->setResultCode(new XdrAccountMergeResultCode(XdrAccountMergeResultCode::NO_ACCOUNT));

        $buffer = new XdrBuffer($type->encode() . $accountMergeResult->encode());
        $decoded = XdrOperationResultTr::decode($buffer);

        $this->assertEquals(XdrOperationType::ACCOUNT_MERGE, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getAccountMergeResult());
    }

    #[Test]
    public function testOperationResultTrInflation(): void
    {
        $type = new XdrOperationType(XdrOperationType::INFLATION);
        $inflationResult = new XdrInflationResult(new XdrInflationResultCode(XdrInflationResultCode::NOT_TIME));

        $buffer = new XdrBuffer($type->encode() . $inflationResult->encode());
        $decoded = XdrOperationResultTr::decode($buffer);

        $this->assertEquals(XdrOperationType::INFLATION, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getInflationResult());
    }

    #[Test]
    public function testOperationResultTrManageData(): void
    {
        $type = new XdrOperationType(XdrOperationType::MANAGE_DATA);
        $manageDataResult = new XdrManageDataResult(new XdrManageDataResultCode(XdrManageDataResultCode::SUCCESS));

        $buffer = new XdrBuffer($type->encode() . $manageDataResult->encode());
        $decoded = XdrOperationResultTr::decode($buffer);

        $this->assertEquals(XdrOperationType::MANAGE_DATA, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getManageDataResult());
    }

    #[Test]
    public function testOperationResultWithInnerCode(): void
    {
        $result = new XdrOperationResult();
        $result->setResultCode(new XdrOperationResultCode(XdrOperationResultCode::INNER));

        $type = new XdrOperationType(XdrOperationType::PAYMENT);
        $paymentResult = new XdrPaymentResult();
        $paymentResult->setResultCode(new XdrPaymentResultCode(XdrPaymentResultCode::SUCCESS));

        $buffer = new XdrBuffer($type->encode() . $paymentResult->encode());
        $tr = XdrOperationResultTr::decode($buffer);

        $result->setResultTr($tr);

        $encoded = $result->encode();
        $decoded = XdrOperationResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrOperationResultCode::INNER, $decoded->getResultCode()->getValue());
        $this->assertNotNull($decoded->getResultTr());
    }

    #[Test]
    public function testOperationResultWithBadAuth(): void
    {
        $result = new XdrOperationResult();
        $result->setResultCode(new XdrOperationResultCode(XdrOperationResultCode::BAD_AUTH));

        $encoded = $result->encode();
        $decoded = XdrOperationResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrOperationResultCode::BAD_AUTH, $decoded->getResultCode()->getValue());
        $this->assertNull($decoded->getResultTr());
    }

    #[Test]
    public function testOperationResultWithNoAccount(): void
    {
        $result = new XdrOperationResult();
        $result->setResultCode(new XdrOperationResultCode(XdrOperationResultCode::NO_ACCOUNT));

        $encoded = $result->encode();
        $decoded = XdrOperationResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrOperationResultCode::NO_ACCOUNT, $decoded->getResultCode()->getValue());
        $this->assertNull($decoded->getResultTr());
    }
}
