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
use Soneso\StellarSDK\Xdr\XdrClaimAtom;
use Soneso\StellarSDK\Xdr\XdrClaimAtomType;
use Soneso\StellarSDK\Xdr\XdrClaimOfferAtom;
use Soneso\StellarSDK\Xdr\XdrClaimOfferAtomV0;
use Soneso\StellarSDK\Xdr\XdrClaimLiquidityAtom;
use Soneso\StellarSDK\Xdr\XdrInnerTransactionResult;
use Soneso\StellarSDK\Xdr\XdrInnerTransactionResultPair;
use Soneso\StellarSDK\Xdr\XdrTransactionResultResult;
use Soneso\StellarSDK\Xdr\XdrTransactionResultCode;
use Soneso\StellarSDK\Xdr\XdrTransactionResultExt;
use Soneso\StellarSDK\Xdr\XdrClawbackResult;
use Soneso\StellarSDK\Xdr\XdrClawbackResultCode;
use Soneso\StellarSDK\Xdr\XdrRestoreFootprintResult;
use Soneso\StellarSDK\Xdr\XdrRestoreFootprintResultCode;
use Soneso\StellarSDK\Xdr\XdrPathPaymentResultSuccess;
use Soneso\StellarSDK\Xdr\XdrSimplePaymentResult;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum4;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrMuxedAccount;
use phpseclib3\Math\BigInteger;

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

    #[Test]
    public function testOperationResultTrPathPaymentStrictReceive(): void
    {
        $type = new XdrOperationType(XdrOperationType::PATH_PAYMENT_STRICT_RECEIVE);

        $code = new XdrPathPaymentStrictReceiveResultCode(XdrPathPaymentStrictReceiveResultCode::MALFORMED);
        $encoded = $code->encode();
        $decoded = XdrPathPaymentStrictReceiveResultCode::decode(new XdrBuffer($encoded));

        $result = new XdrPathPaymentStrictReceiveResult();
        $buffer = new XdrBuffer($type->encode() . $decoded->encode());
        $decodedTr = XdrOperationResultTr::decode($buffer);

        $this->assertEquals(XdrOperationType::PATH_PAYMENT_STRICT_RECEIVE, $decodedTr->getType()->getValue());
        $this->assertNotNull($decodedTr->getPathPaymentStrictReceiveResult());
        $this->assertEquals(
            XdrPathPaymentStrictReceiveResultCode::MALFORMED,
            $decodedTr->getPathPaymentStrictReceiveResult()->getCode()->getValue()
        );
    }

    #[Test]
    public function testOperationResultTrPathPaymentStrictSend(): void
    {
        $type = new XdrOperationType(XdrOperationType::PATH_PAYMENT_STRICT_SEND);

        $code = new XdrPathPaymentStrictSendResultCode(XdrPathPaymentStrictSendResultCode::UNDER_DESTMIN);
        $encoded = $code->encode();
        $decoded = XdrPathPaymentStrictSendResultCode::decode(new XdrBuffer($encoded));

        $result = new XdrPathPaymentStrictSendResult();
        $buffer = new XdrBuffer($type->encode() . $decoded->encode());
        $decodedTr = XdrOperationResultTr::decode($buffer);

        $this->assertEquals(XdrOperationType::PATH_PAYMENT_STRICT_SEND, $decodedTr->getType()->getValue());
        $this->assertNotNull($decodedTr->getPathPaymentStrictSendResult());
        $this->assertEquals(
            XdrPathPaymentStrictSendResultCode::UNDER_DESTMIN,
            $decodedTr->getPathPaymentStrictSendResult()->getCode()->getValue()
        );
    }

    #[Test]
    public function testOperationResultTrCreatePassiveSellOffer(): void
    {
        $type = new XdrOperationType(XdrOperationType::CREATE_PASSIVE_SELL_OFFER);
        $manageOfferResult = new XdrManageOfferResult(
            new XdrManageOfferResultCode(XdrManageOfferResultCode::SELL_NO_ISSUER)
        );

        $buffer = new XdrBuffer($type->encode() . $manageOfferResult->encode());
        $decoded = XdrOperationResultTr::decode($buffer);

        $this->assertEquals(XdrOperationType::CREATE_PASSIVE_SELL_OFFER, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getCreatePassiveSellOfferResult());
        $this->assertEquals(
            XdrManageOfferResultCode::SELL_NO_ISSUER,
            $decoded->getCreatePassiveSellOfferResult()->getCode()->getValue()
        );
    }

    #[Test]
    public function testOperationResultTrAllowTrust(): void
    {
        $type = new XdrOperationType(XdrOperationType::ALLOW_TRUST);
        $allowTrustResult = new XdrAllowTrustResult();
        $allowTrustResult->setResultCode(new XdrAllowTrustResultCode(XdrAllowTrustResultCode::TRUST_NOT_REQUIRED));

        $buffer = new XdrBuffer($type->encode() . $allowTrustResult->encode());
        $decoded = XdrOperationResultTr::decode($buffer);

        $this->assertEquals(XdrOperationType::ALLOW_TRUST, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getAllowTrustResult());
        $this->assertEquals(
            XdrAllowTrustResultCode::TRUST_NOT_REQUIRED,
            $decoded->getAllowTrustResult()->getResultCode()->getValue()
        );
    }

    #[Test]
    public function testOperationResultTrInvokeHostFunction(): void
    {
        $type = new XdrOperationType(XdrOperationType::INVOKE_HOST_FUNCTION);
        $invokeResult = new XdrInvokeHostFunctionResult(
            new XdrInvokeHostFunctionResultCode(XdrInvokeHostFunctionResultCode::INVOKE_HOST_FUNCTION_TRAPPED)
        );

        $buffer = new XdrBuffer($type->encode() . $invokeResult->encode());
        $decoded = XdrOperationResultTr::decode($buffer);

        $this->assertEquals(XdrOperationType::INVOKE_HOST_FUNCTION, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getInvokeHostFunctionResult());
        $this->assertEquals(
            XdrInvokeHostFunctionResultCode::INVOKE_HOST_FUNCTION_TRAPPED,
            $decoded->getInvokeHostFunctionResult()->type->value
        );
    }

    #[Test]
    public function testOperationResultTrExtendFootprintTTL(): void
    {
        $type = new XdrOperationType(XdrOperationType::EXTEND_FOOTPRINT_TTL);
        $extendResult = new XdrExtendFootprintTTLResult(
            new XdrExtendFootprintTTLResultCode(XdrExtendFootprintTTLResultCode::EXTEND_FOOTPRINT_TTL_MALFORMED)
        );

        $buffer = new XdrBuffer($type->encode() . $extendResult->encode());
        $decoded = XdrOperationResultTr::decode($buffer);

        $this->assertEquals(XdrOperationType::EXTEND_FOOTPRINT_TTL, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getExtendFootprintTTLResult());
        $this->assertEquals(
            XdrExtendFootprintTTLResultCode::EXTEND_FOOTPRINT_TTL_MALFORMED,
            $decoded->getExtendFootprintTTLResult()->getCode()->getValue()
        );
    }

    #[Test]
    public function testOperationResultTrRestoreFootprint(): void
    {
        $type = new XdrOperationType(XdrOperationType::RESTORE_FOOTPRINT);
        $restoreResult = new XdrRestoreFootprintResult(
            new XdrRestoreFootprintResultCode(XdrRestoreFootprintResultCode::RESTORE_FOOTPRINT_RESOURCE_LIMIT_EXCEEDED)
        );

        $buffer = new XdrBuffer($type->encode() . $restoreResult->encode());
        $decoded = XdrOperationResultTr::decode($buffer);

        $this->assertEquals(XdrOperationType::RESTORE_FOOTPRINT, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getRestoreFootprintResult());
        $this->assertEquals(
            XdrRestoreFootprintResultCode::RESTORE_FOOTPRINT_RESOURCE_LIMIT_EXCEEDED,
            $decoded->getRestoreFootprintResult()->getCode()->getValue()
        );
    }

    #[Test]
    public function testOperationResultTrClawback(): void
    {
        $type = new XdrOperationType(XdrOperationType::CLAWBACK);
        $clawbackResult = new XdrClawbackResult(
            new XdrClawbackResultCode(XdrClawbackResultCode::NO_TRUST)
        );

        $buffer = new XdrBuffer($type->encode() . $clawbackResult->encode());
        $decoded = XdrOperationResultTr::decode($buffer);

        $this->assertEquals(XdrOperationType::CLAWBACK, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getClawbackResult());
        $this->assertEquals(
            XdrClawbackResultCode::NO_TRUST,
            $decoded->getClawbackResult()->getResultCode()->getValue()
        );
    }

    #[Test]
    public function testOperationResultTrClawbackClaimableBalance(): void
    {
        $type = new XdrOperationType(XdrOperationType::CLAWBACK_CLAIMABLE_BALANCE);
        $clawbackResult = new XdrClawbackClaimableBalanceResult(
            new XdrClawbackClaimableBalanceResultCode(XdrClawbackClaimableBalanceResultCode::DOES_NOT_EXIST)
        );

        $buffer = new XdrBuffer($type->encode() . $clawbackResult->encode());
        $decoded = XdrOperationResultTr::decode($buffer);

        $this->assertEquals(XdrOperationType::CLAWBACK_CLAIMABLE_BALANCE, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getClawbackClaimableBalanceResult());
        $this->assertEquals(
            XdrClawbackClaimableBalanceResultCode::DOES_NOT_EXIST,
            $decoded->getClawbackClaimableBalanceResult()->getResultCode()->getValue()
        );
    }

    #[Test]
    public function testOperationResultTrLiquidityPoolDeposit(): void
    {
        $type = new XdrOperationType(XdrOperationType::LIQUIDITY_POOL_DEPOSIT);
        $depositResult = new XdrLiquidityPoolDepositResult();
        $depositResult->setResultCode(
            new XdrLiquidityPoolDepositResultCode(XdrLiquidityPoolDepositResultCode::NO_TRUST)
        );

        $buffer = new XdrBuffer($type->encode() . $depositResult->encode());
        $decoded = XdrOperationResultTr::decode($buffer);

        $this->assertEquals(XdrOperationType::LIQUIDITY_POOL_DEPOSIT, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getLiquidityPoolDepositResult());
        $this->assertEquals(
            XdrLiquidityPoolDepositResultCode::NO_TRUST,
            $decoded->getLiquidityPoolDepositResult()->getResultCode()->getValue()
        );
    }

    #[Test]
    public function testOperationResultTrLiquidityPoolWithdraw(): void
    {
        $type = new XdrOperationType(XdrOperationType::LIQUIDITY_POOL_WITHDRAW);
        $withdrawResult = new XdrLiquidityPoolWithdrawResult();
        $withdrawResult->setResultCode(
            new XdrLiquidityPoolWithdrawResultCode(XdrLiquidityPoolWithdrawResultCode::UNDERFUNDED)
        );

        $buffer = new XdrBuffer($type->encode() . $withdrawResult->encode());
        $decoded = XdrOperationResultTr::decode($buffer);

        $this->assertEquals(XdrOperationType::LIQUIDITY_POOL_WITHDRAW, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getLiquidityPoolWithdrawResult());
        $this->assertEquals(
            XdrLiquidityPoolWithdrawResultCode::UNDERFUNDED,
            $decoded->getLiquidityPoolWithdrawResult()->getResultCode()->getValue()
        );
    }

    #[Test]
    public function testClawbackResultRoundTrip(): void
    {
        $result = new XdrClawbackResult(new XdrClawbackResultCode(XdrClawbackResultCode::SUCCESS));

        $encoded = $result->encode();
        $decoded = XdrClawbackResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrClawbackResultCode::SUCCESS, $decoded->getResultCode()->getValue());
        $this->assertEquals($encoded, $decoded->encode());
    }

    #[Test]
    public function testRestoreFootprintResultRoundTrip(): void
    {
        $result = new XdrRestoreFootprintResult(
            new XdrRestoreFootprintResultCode(XdrRestoreFootprintResultCode::RESTORE_FOOTPRINT_SUCCESS)
        );

        $encoded = $result->encode();
        $decoded = XdrRestoreFootprintResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(
            XdrRestoreFootprintResultCode::RESTORE_FOOTPRINT_SUCCESS,
            $decoded->getCode()->getValue()
        );
        $this->assertEquals($encoded, $decoded->encode());
    }

    #[Test]
    public function testClaimOfferAtomRoundTrip(): void
    {
        $accountId = XdrAccountID::fromAccountId("GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H");
        $assetSold = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $assetBought = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));

        $claimAtom = new XdrClaimOfferAtom(
            $accountId,
            12345,
            $assetSold,
            new BigInteger(1000000),
            $assetBought,
            new BigInteger(2000000)
        );

        $encoded = $claimAtom->encode();
        $decoded = XdrClaimOfferAtom::decode(new XdrBuffer($encoded));

        $this->assertEquals($accountId->getAccountId(), $decoded->getAccountId()->getAccountId());
        $this->assertEquals(12345, $decoded->getOfferId());
        $this->assertEquals("1000000", $decoded->getAmountSold()->toString());
        $this->assertEquals("2000000", $decoded->getAmountBought()->toString());
        $this->assertEquals($encoded, $decoded->encode());
    }

    #[Test]
    public function testClaimOfferAtomV0RoundTrip(): void
    {
        $sellerEd25519 = str_repeat("\xaa", 32);
        $assetSold = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $assetBought = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));

        $claimAtom = new XdrClaimOfferAtomV0(
            $sellerEd25519,
            67890,
            $assetSold,
            new BigInteger(500000),
            $assetBought,
            new BigInteger(750000)
        );

        $encoded = $claimAtom->encode();
        $decoded = XdrClaimOfferAtomV0::decode(new XdrBuffer($encoded));

        $this->assertEquals($sellerEd25519, $decoded->getSellerEd25519());
        $this->assertEquals(67890, $decoded->getOfferId());
        $this->assertEquals("500000", $decoded->getAmountSold()->toString());
        $this->assertEquals("750000", $decoded->getAmountBought()->toString());
        $this->assertEquals($encoded, $decoded->encode());
    }

    #[Test]
    public function testClaimLiquidityAtomRoundTrip(): void
    {
        $poolId = hash('sha256', 'test-pool-id', true);
        $assetSold = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $assetBought = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));

        $claimAtom = new XdrClaimLiquidityAtom(
            $poolId,
            $assetSold,
            new BigInteger(3000000),
            $assetBought,
            new BigInteger(4000000)
        );

        $encoded = $claimAtom->encode();
        $decoded = XdrClaimLiquidityAtom::decode(new XdrBuffer($encoded));

        $this->assertEquals($poolId, $decoded->getLiquidityPoolID());
        $this->assertEquals("3000000", $decoded->getAmountSold()->toString());
        $this->assertEquals("4000000", $decoded->getAmountBought()->toString());
        $this->assertEquals($encoded, $decoded->encode());
    }

    #[Test]
    public function testClaimAtomWithV0RoundTrip(): void
    {
        $claimAtom = new XdrClaimAtom();
        $claimAtom->setType(new XdrClaimAtomType(XdrClaimAtomType::V0));

        $sellerEd25519 = str_repeat("\xbb", 32);
        $assetSold = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $assetBought = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));

        $v0 = new XdrClaimOfferAtomV0(
            $sellerEd25519,
            111,
            $assetSold,
            new BigInteger(100),
            $assetBought,
            new BigInteger(200)
        );
        $claimAtom->setV0($v0);

        $encoded = $claimAtom->encode();
        $decoded = XdrClaimAtom::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrClaimAtomType::V0, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getV0());
        $this->assertEquals($sellerEd25519, $decoded->getV0()->getSellerEd25519());
        $this->assertEquals($encoded, $decoded->encode());
    }

    #[Test]
    public function testClaimAtomWithOrderBookRoundTrip(): void
    {
        $accountId = XdrAccountID::fromAccountId("GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ");
        $assetSold = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $assetBought = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));

        $orderBook = new XdrClaimOfferAtom(
            $accountId,
            222,
            $assetSold,
            new BigInteger(300),
            $assetBought,
            new BigInteger(400)
        );

        $type = new XdrClaimAtomType(XdrClaimAtomType::ORDER_BOOK);
        $buffer = new XdrBuffer($type->encode() . $orderBook->encode());
        $decoded = XdrClaimAtom::decode($buffer);

        $this->assertEquals(XdrClaimAtomType::ORDER_BOOK, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getOrderBook());
        $this->assertEquals($accountId->getAccountId(), $decoded->getOrderBook()->getAccountId()->getAccountId());
    }

    #[Test]
    public function testClaimAtomWithLiquidityPoolRoundTrip(): void
    {
        $claimAtom = new XdrClaimAtom();
        $claimAtom->setType(new XdrClaimAtomType(XdrClaimAtomType::LIQUIDITY_POOL));

        $poolId = hash('sha256', 'liquidity-test', true);
        $assetSold = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $assetBought = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));

        $liquidityPool = new XdrClaimLiquidityAtom(
            $poolId,
            $assetSold,
            new BigInteger(500),
            $assetBought,
            new BigInteger(600)
        );
        $claimAtom->setLiquidityPool($liquidityPool);

        $encoded = $claimAtom->encode();
        $decoded = XdrClaimAtom::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrClaimAtomType::LIQUIDITY_POOL, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getLiquidityPool());
        $this->assertEquals($poolId, $decoded->getLiquidityPool()->getLiquidityPoolID());
        $this->assertEquals($encoded, $decoded->encode());
    }

    #[Test]
    public function testInnerTransactionResultRoundTrip(): void
    {
        $innerResult = new XdrInnerTransactionResult();
        $innerResult->feeCharged = new BigInteger(10000);

        $resultResult = new XdrTransactionResultResult();
        $resultResult->resultCode = new XdrTransactionResultCode(XdrTransactionResultCode::SUCCESS);
        $resultResult->results = [];
        $innerResult->result = $resultResult;

        $innerResult->ext = new XdrTransactionResultExt(0);

        $encoded = $innerResult->encode();
        $decoded = XdrInnerTransactionResult::decode(new XdrBuffer($encoded));

        $this->assertEquals("10000", $decoded->getFeeCharged()->toString());
        $this->assertEquals(
            XdrTransactionResultCode::SUCCESS,
            $decoded->getResult()->getResultCode()->getValue()
        );
        $this->assertEquals($encoded, $decoded->encode());
    }

    #[Test]
    public function testInnerTransactionResultPairRoundTrip(): void
    {
        $txHash = hash('sha256', 'test-transaction', false);

        $innerResult = new XdrInnerTransactionResult();
        $innerResult->feeCharged = new BigInteger(5000);

        $resultResult = new XdrTransactionResultResult();
        $resultResult->resultCode = new XdrTransactionResultCode(XdrTransactionResultCode::FAILED);
        $resultResult->results = [];
        $innerResult->result = $resultResult;

        $innerResult->ext = new XdrTransactionResultExt(0);

        $pair = new XdrInnerTransactionResultPair($txHash, $innerResult);

        $encoded = $pair->encode();
        $decoded = XdrInnerTransactionResultPair::decode(new XdrBuffer($encoded));

        $this->assertEquals($txHash, $decoded->getTransactionHash());
        $this->assertEquals("5000", $decoded->getResult()->getFeeCharged()->toString());
        $this->assertEquals($encoded, $decoded->encode());
    }

    #[Test]
    public function testInflationResultSuccess(): void
    {
        $result = new XdrInflationResult(new XdrInflationResultCode(XdrInflationResultCode::SUCCESS), []);

        $encoded = $result->encode();
        $decoded = XdrInflationResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrInflationResultCode::SUCCESS, $decoded->getCode()->getValue());
        $this->assertNotNull($decoded->getPayouts());
        $this->assertIsArray($decoded->getPayouts());
        $this->assertEquals($encoded, $decoded->encode());
    }

    #[Test]
    public function testInflationResultNotTime(): void
    {
        $result = new XdrInflationResult(new XdrInflationResultCode(XdrInflationResultCode::NOT_TIME));

        $encoded = $result->encode();
        $decoded = XdrInflationResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrInflationResultCode::NOT_TIME, $decoded->getCode()->getValue());
        $this->assertNull($decoded->getPayouts());
        $this->assertEquals($encoded, $decoded->encode());
    }

    #[Test]
    public function testPathPaymentStrictReceiveResultWithNoIssuer(): void
    {
        $issuer = XdrAccountID::fromAccountId("GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H");
        $alphaNum4 = new XdrAssetAlphaNum4("USD", $issuer);
        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $asset->setAlphaNum4($alphaNum4);

        $code = new XdrPathPaymentStrictReceiveResultCode(XdrPathPaymentStrictReceiveResultCode::NO_ISSUER);
        $buffer = new XdrBuffer($code->encode() . $asset->encode());
        $decoded = XdrPathPaymentStrictReceiveResult::decode($buffer);

        $this->assertEquals(
            XdrPathPaymentStrictReceiveResultCode::NO_ISSUER,
            $decoded->getCode()->getValue()
        );
        $this->assertNotNull($decoded->getNoIssuer());
        $this->assertEquals(
            XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4,
            $decoded->getNoIssuer()->getType()->getValue()
        );
    }

    #[Test]
    public function testPathPaymentStrictReceiveResultMalformed(): void
    {
        $code = new XdrPathPaymentStrictReceiveResultCode(XdrPathPaymentStrictReceiveResultCode::MALFORMED);
        $buffer = new XdrBuffer($code->encode());
        $decoded = XdrPathPaymentStrictReceiveResult::decode($buffer);

        $this->assertEquals(
            XdrPathPaymentStrictReceiveResultCode::MALFORMED,
            $decoded->getCode()->getValue()
        );
        $this->assertNull($decoded->getSuccess());
        $this->assertNull($decoded->getNoIssuer());
    }

    #[Test]
    public function testPathPaymentStrictSendResultLineFull(): void
    {
        $code = new XdrPathPaymentStrictSendResultCode(XdrPathPaymentStrictSendResultCode::LINE_FULL);
        $buffer = new XdrBuffer($code->encode());
        $decoded = XdrPathPaymentStrictSendResult::decode($buffer);

        $this->assertEquals(
            XdrPathPaymentStrictSendResultCode::LINE_FULL,
            $decoded->getCode()->getValue()
        );
    }

    #[Test]
    public function testPathPaymentStrictSendResultUnderDestmin(): void
    {
        $code = new XdrPathPaymentStrictSendResultCode(XdrPathPaymentStrictSendResultCode::UNDER_DESTMIN);
        $buffer = new XdrBuffer($code->encode());
        $decoded = XdrPathPaymentStrictSendResult::decode($buffer);

        $this->assertEquals(
            XdrPathPaymentStrictSendResultCode::UNDER_DESTMIN,
            $decoded->getCode()->getValue()
        );
    }

    #[Test]
    public function testClawbackResultMalformed(): void
    {
        $result = new XdrClawbackResult(new XdrClawbackResultCode(XdrClawbackResultCode::MALFORMED));

        $encoded = $result->encode();
        $decoded = XdrClawbackResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrClawbackResultCode::MALFORMED, $decoded->getResultCode()->getValue());
    }

    #[Test]
    public function testClawbackResultNotEnabled(): void
    {
        $result = new XdrClawbackResult(
            new XdrClawbackResultCode(XdrClawbackResultCode::NOT_ENABLED)
        );

        $encoded = $result->encode();
        $decoded = XdrClawbackResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(
            XdrClawbackResultCode::NOT_ENABLED,
            $decoded->getResultCode()->getValue()
        );
    }

    #[Test]
    public function testClawbackClaimableBalanceResultNotIssuer(): void
    {
        $result = new XdrClawbackClaimableBalanceResult(
            new XdrClawbackClaimableBalanceResultCode(XdrClawbackClaimableBalanceResultCode::NOT_ISSUER)
        );

        $encoded = $result->encode();
        $decoded = XdrClawbackClaimableBalanceResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(
            XdrClawbackClaimableBalanceResultCode::NOT_ISSUER,
            $decoded->getResultCode()->getValue()
        );
    }

    #[Test]
    public function testLiquidityPoolDepositResultMalformed(): void
    {
        $result = new XdrLiquidityPoolDepositResult();
        $result->setResultCode(
            new XdrLiquidityPoolDepositResultCode(XdrLiquidityPoolDepositResultCode::MALFORMED)
        );

        $encoded = $result->encode();
        $decoded = XdrLiquidityPoolDepositResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(
            XdrLiquidityPoolDepositResultCode::MALFORMED,
            $decoded->getResultCode()->getValue()
        );
    }

    #[Test]
    public function testLiquidityPoolWithdrawResultLineFull(): void
    {
        $result = new XdrLiquidityPoolWithdrawResult();
        $result->setResultCode(
            new XdrLiquidityPoolWithdrawResultCode(XdrLiquidityPoolWithdrawResultCode::LINE_FULL)
        );

        $encoded = $result->encode();
        $decoded = XdrLiquidityPoolWithdrawResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(
            XdrLiquidityPoolWithdrawResultCode::LINE_FULL,
            $decoded->getResultCode()->getValue()
        );
    }

    #[Test]
    public function testInvokeHostFunctionResultSuccess(): void
    {
        $result = new XdrInvokeHostFunctionResult(
            new XdrInvokeHostFunctionResultCode(XdrInvokeHostFunctionResultCode::INVOKE_HOST_FUNCTION_SUCCESS)
        );
        $result->success = hash('sha256', 'test-success', true);

        $encoded = $result->encode();
        $decoded = XdrInvokeHostFunctionResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(
            XdrInvokeHostFunctionResultCode::INVOKE_HOST_FUNCTION_SUCCESS,
            $decoded->type->value
        );
        $this->assertNotNull($decoded->success);
        $this->assertEquals($result->success, $decoded->success);
    }

    #[Test]
    public function testExtendFootprintTTLResultInsufficientRefundableFee(): void
    {
        $result = new XdrExtendFootprintTTLResult(
            new XdrExtendFootprintTTLResultCode(
                XdrExtendFootprintTTLResultCode::EXTEND_FOOTPRINT_TTL_INSUFFICIENT_REFUNDABLE_FEE
            )
        );

        $encoded = $result->encode();
        $decoded = XdrExtendFootprintTTLResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(
            XdrExtendFootprintTTLResultCode::EXTEND_FOOTPRINT_TTL_INSUFFICIENT_REFUNDABLE_FEE,
            $decoded->getCode()->getValue()
        );
    }

    #[Test]
    public function testRestoreFootprintResultMalformed(): void
    {
        $result = new XdrRestoreFootprintResult(
            new XdrRestoreFootprintResultCode(XdrRestoreFootprintResultCode::RESTORE_FOOTPRINT_MALFORMED)
        );

        $encoded = $result->encode();
        $decoded = XdrRestoreFootprintResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(
            XdrRestoreFootprintResultCode::RESTORE_FOOTPRINT_MALFORMED,
            $decoded->getCode()->getValue()
        );
    }

    #[Test]
    public function testRestoreFootprintResultInsufficientRefundableFee(): void
    {
        $result = new XdrRestoreFootprintResult(
            new XdrRestoreFootprintResultCode(
                XdrRestoreFootprintResultCode::RESTORE_FOOTPRINT_INSUFFICIENT_REFUNDABLE_FEE
            )
        );

        $encoded = $result->encode();
        $decoded = XdrRestoreFootprintResult::decode(new XdrBuffer($encoded));

        $this->assertEquals(
            XdrRestoreFootprintResultCode::RESTORE_FOOTPRINT_INSUFFICIENT_REFUNDABLE_FEE,
            $decoded->getCode()->getValue()
        );
    }
}
