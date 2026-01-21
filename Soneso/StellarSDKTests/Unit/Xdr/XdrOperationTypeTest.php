<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrOperationType;

/**
 * Unit tests for XdrOperationType
 *
 * Tests all operation type factory methods, encoding/decoding,
 * and value retrieval.
 */
class XdrOperationTypeTest extends TestCase
{
    // Constructor and Value Tests

    public function testConstructorAndGetValue(): void
    {
        $type = new XdrOperationType(XdrOperationType::PAYMENT);
        $this->assertEquals(XdrOperationType::PAYMENT, $type->getValue());
    }

    // Encode/Decode Tests

    public function testEncodeDecodeRoundTrip(): void
    {
        $original = new XdrOperationType(XdrOperationType::MANAGE_DATA);

        $encoded = $original->encode();
        $decoded = XdrOperationType::decode(new XdrBuffer($encoded));

        $this->assertEquals($original->getValue(), $decoded->getValue());
    }

    // Factory Method Tests - All Operation Types

    public function testCreateAccount(): void
    {
        $type = XdrOperationType::CREATE_ACCOUNT();
        $this->assertEquals(XdrOperationType::CREATE_ACCOUNT, $type->getValue());
        $this->assertEquals(0, $type->getValue());
    }

    public function testPayment(): void
    {
        $type = XdrOperationType::PAYMENT();
        $this->assertEquals(XdrOperationType::PAYMENT, $type->getValue());
        $this->assertEquals(1, $type->getValue());
    }

    public function testPathPaymentStrictReceive(): void
    {
        $type = XdrOperationType::PATH_PAYMENT_STRICT_RECEIVE();
        $this->assertEquals(XdrOperationType::PATH_PAYMENT_STRICT_RECEIVE, $type->getValue());
        $this->assertEquals(2, $type->getValue());
    }

    public function testManageSellOffer(): void
    {
        $type = XdrOperationType::MANAGE_SELL_OFFER();
        $this->assertEquals(XdrOperationType::MANAGE_SELL_OFFER, $type->getValue());
        $this->assertEquals(3, $type->getValue());
    }

    public function testCreatePassiveSellOffer(): void
    {
        $type = XdrOperationType::CREATE_PASSIVE_SELL_OFFER();
        $this->assertEquals(XdrOperationType::CREATE_PASSIVE_SELL_OFFER, $type->getValue());
        $this->assertEquals(4, $type->getValue());
    }

    public function testSetOptions(): void
    {
        $type = XdrOperationType::SET_OPTIONS();
        $this->assertEquals(XdrOperationType::SET_OPTIONS, $type->getValue());
        $this->assertEquals(5, $type->getValue());
    }

    public function testChangeTrust(): void
    {
        $type = XdrOperationType::CHANGE_TRUST();
        $this->assertEquals(XdrOperationType::CHANGE_TRUST, $type->getValue());
        $this->assertEquals(6, $type->getValue());
    }

    public function testAllowTrust(): void
    {
        $type = XdrOperationType::ALLOW_TRUST();
        $this->assertEquals(XdrOperationType::ALLOW_TRUST, $type->getValue());
        $this->assertEquals(7, $type->getValue());
    }

    public function testAccountMerge(): void
    {
        $type = XdrOperationType::ACCOUNT_MERGE();
        $this->assertEquals(XdrOperationType::ACCOUNT_MERGE, $type->getValue());
        $this->assertEquals(8, $type->getValue());
    }

    public function testInflation(): void
    {
        $type = XdrOperationType::INFLATION();
        $this->assertEquals(XdrOperationType::INFLATION, $type->getValue());
        $this->assertEquals(9, $type->getValue());
    }

    public function testManageData(): void
    {
        $type = XdrOperationType::MANAGE_DATA();
        $this->assertEquals(XdrOperationType::MANAGE_DATA, $type->getValue());
        $this->assertEquals(10, $type->getValue());
    }

    public function testBumpSequence(): void
    {
        $type = XdrOperationType::BUMP_SEQUENCE();
        $this->assertEquals(XdrOperationType::BUMP_SEQUENCE, $type->getValue());
        $this->assertEquals(11, $type->getValue());
    }

    public function testManageBuyOffer(): void
    {
        $type = XdrOperationType::MANAGE_BUY_OFFER();
        $this->assertEquals(XdrOperationType::MANAGE_BUY_OFFER, $type->getValue());
        $this->assertEquals(12, $type->getValue());
    }

    public function testPathPaymentStrictSend(): void
    {
        $type = XdrOperationType::PATH_PAYMENT_STRICT_SEND();
        $this->assertEquals(XdrOperationType::PATH_PAYMENT_STRICT_SEND, $type->getValue());
        $this->assertEquals(13, $type->getValue());
    }

    public function testCreateClaimableBalance(): void
    {
        $type = XdrOperationType::CREATE_CLAIMABLE_BALANCE();
        $this->assertEquals(XdrOperationType::CREATE_CLAIMABLE_BALANCE, $type->getValue());
        $this->assertEquals(14, $type->getValue());
    }

    public function testClaimClaimableBalance(): void
    {
        $type = XdrOperationType::CLAIM_CLAIMABLE_BALANCE();
        $this->assertEquals(XdrOperationType::CLAIM_CLAIMABLE_BALANCE, $type->getValue());
        $this->assertEquals(15, $type->getValue());
    }

    public function testBeginSponsoringFutureReserves(): void
    {
        $type = XdrOperationType::BEGIN_SPONSORING_FUTURE_RESERVES();
        $this->assertEquals(XdrOperationType::BEGIN_SPONSORING_FUTURE_RESERVES, $type->getValue());
        $this->assertEquals(16, $type->getValue());
    }

    public function testEndSponsoringFutureReserves(): void
    {
        $type = XdrOperationType::END_SPONSORING_FUTURE_RESERVES();
        $this->assertEquals(XdrOperationType::END_SPONSORING_FUTURE_RESERVES, $type->getValue());
        $this->assertEquals(17, $type->getValue());
    }

    public function testRevokeSponsorship(): void
    {
        $type = XdrOperationType::REVOKE_SPONSORSHIP();
        $this->assertEquals(XdrOperationType::REVOKE_SPONSORSHIP, $type->getValue());
        $this->assertEquals(18, $type->getValue());
    }

    public function testClawback(): void
    {
        $type = XdrOperationType::CLAWBACK();
        $this->assertEquals(XdrOperationType::CLAWBACK, $type->getValue());
        $this->assertEquals(19, $type->getValue());
    }

    public function testClawbackClaimableBalance(): void
    {
        $type = XdrOperationType::CLAWBACK_CLAIMABLE_BALANCE();
        $this->assertEquals(XdrOperationType::CLAWBACK_CLAIMABLE_BALANCE, $type->getValue());
        $this->assertEquals(20, $type->getValue());
    }

    public function testSetTrustLineFlags(): void
    {
        $type = XdrOperationType::SET_TRUST_LINE_FLAGS();
        $this->assertEquals(XdrOperationType::SET_TRUST_LINE_FLAGS, $type->getValue());
        $this->assertEquals(21, $type->getValue());
    }

    public function testLiquidityPoolDeposit(): void
    {
        $type = XdrOperationType::LIQUIDITY_POOL_DEPOSIT();
        $this->assertEquals(XdrOperationType::LIQUIDITY_POOL_DEPOSIT, $type->getValue());
        $this->assertEquals(22, $type->getValue());
    }

    public function testLiquidityPoolWithdraw(): void
    {
        $type = XdrOperationType::LIQUIDITY_POOL_WITHDRAW();
        $this->assertEquals(XdrOperationType::LIQUIDITY_POOL_WITHDRAW, $type->getValue());
        $this->assertEquals(23, $type->getValue());
    }

    public function testInvokeHostFunction(): void
    {
        $type = XdrOperationType::INVOKE_HOST_FUNCTION();
        $this->assertEquals(XdrOperationType::INVOKE_HOST_FUNCTION, $type->getValue());
        $this->assertEquals(24, $type->getValue());
    }

    public function testExtendFootprintTtl(): void
    {
        $type = XdrOperationType::EXTEND_FOOTPRINT_TTL();
        $this->assertEquals(XdrOperationType::EXTEND_FOOTPRINT_TTL, $type->getValue());
        $this->assertEquals(25, $type->getValue());
    }

    public function testRestoreFootprint(): void
    {
        $type = XdrOperationType::RESTORE_FOOTPRINT();
        $this->assertEquals(XdrOperationType::RESTORE_FOOTPRINT, $type->getValue());
        $this->assertEquals(26, $type->getValue());
    }

    // Encode/Decode All Types

    public function testEncodeDecodeAllTypes(): void
    {
        $types = [
            XdrOperationType::CREATE_ACCOUNT(),
            XdrOperationType::PAYMENT(),
            XdrOperationType::PATH_PAYMENT_STRICT_RECEIVE(),
            XdrOperationType::MANAGE_SELL_OFFER(),
            XdrOperationType::CREATE_PASSIVE_SELL_OFFER(),
            XdrOperationType::SET_OPTIONS(),
            XdrOperationType::CHANGE_TRUST(),
            XdrOperationType::ALLOW_TRUST(),
            XdrOperationType::ACCOUNT_MERGE(),
            XdrOperationType::INFLATION(),
            XdrOperationType::MANAGE_DATA(),
            XdrOperationType::BUMP_SEQUENCE(),
            XdrOperationType::MANAGE_BUY_OFFER(),
            XdrOperationType::PATH_PAYMENT_STRICT_SEND(),
            XdrOperationType::CREATE_CLAIMABLE_BALANCE(),
            XdrOperationType::CLAIM_CLAIMABLE_BALANCE(),
            XdrOperationType::BEGIN_SPONSORING_FUTURE_RESERVES(),
            XdrOperationType::END_SPONSORING_FUTURE_RESERVES(),
            XdrOperationType::REVOKE_SPONSORSHIP(),
            XdrOperationType::CLAWBACK(),
            XdrOperationType::CLAWBACK_CLAIMABLE_BALANCE(),
            XdrOperationType::SET_TRUST_LINE_FLAGS(),
            XdrOperationType::LIQUIDITY_POOL_DEPOSIT(),
            XdrOperationType::LIQUIDITY_POOL_WITHDRAW(),
            XdrOperationType::INVOKE_HOST_FUNCTION(),
            XdrOperationType::EXTEND_FOOTPRINT_TTL(),
            XdrOperationType::RESTORE_FOOTPRINT(),
        ];

        foreach ($types as $index => $type) {
            $encoded = $type->encode();
            $decoded = XdrOperationType::decode(new XdrBuffer($encoded));
            $this->assertEquals($type->getValue(), $decoded->getValue(), "Failed for type index $index");
        }
    }

    // Constants Verification

    public function testConstantsValues(): void
    {
        $this->assertEquals(0, XdrOperationType::CREATE_ACCOUNT);
        $this->assertEquals(1, XdrOperationType::PAYMENT);
        $this->assertEquals(2, XdrOperationType::PATH_PAYMENT_STRICT_RECEIVE);
        $this->assertEquals(3, XdrOperationType::MANAGE_SELL_OFFER);
        $this->assertEquals(4, XdrOperationType::CREATE_PASSIVE_SELL_OFFER);
        $this->assertEquals(5, XdrOperationType::SET_OPTIONS);
        $this->assertEquals(6, XdrOperationType::CHANGE_TRUST);
        $this->assertEquals(7, XdrOperationType::ALLOW_TRUST);
        $this->assertEquals(8, XdrOperationType::ACCOUNT_MERGE);
        $this->assertEquals(9, XdrOperationType::INFLATION);
        $this->assertEquals(10, XdrOperationType::MANAGE_DATA);
        $this->assertEquals(11, XdrOperationType::BUMP_SEQUENCE);
        $this->assertEquals(12, XdrOperationType::MANAGE_BUY_OFFER);
        $this->assertEquals(13, XdrOperationType::PATH_PAYMENT_STRICT_SEND);
        $this->assertEquals(14, XdrOperationType::CREATE_CLAIMABLE_BALANCE);
        $this->assertEquals(15, XdrOperationType::CLAIM_CLAIMABLE_BALANCE);
        $this->assertEquals(16, XdrOperationType::BEGIN_SPONSORING_FUTURE_RESERVES);
        $this->assertEquals(17, XdrOperationType::END_SPONSORING_FUTURE_RESERVES);
        $this->assertEquals(18, XdrOperationType::REVOKE_SPONSORSHIP);
        $this->assertEquals(19, XdrOperationType::CLAWBACK);
        $this->assertEquals(20, XdrOperationType::CLAWBACK_CLAIMABLE_BALANCE);
        $this->assertEquals(21, XdrOperationType::SET_TRUST_LINE_FLAGS);
        $this->assertEquals(22, XdrOperationType::LIQUIDITY_POOL_DEPOSIT);
        $this->assertEquals(23, XdrOperationType::LIQUIDITY_POOL_WITHDRAW);
        $this->assertEquals(24, XdrOperationType::INVOKE_HOST_FUNCTION);
        $this->assertEquals(25, XdrOperationType::EXTEND_FOOTPRINT_TTL);
        $this->assertEquals(26, XdrOperationType::RESTORE_FOOTPRINT);
    }
}
