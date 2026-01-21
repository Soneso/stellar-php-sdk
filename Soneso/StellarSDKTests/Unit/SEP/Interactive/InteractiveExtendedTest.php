<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\SEP\Interactive;

use DateTime;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\Refund;
use Soneso\StellarSDK\SEP\Interactive\RefundPayment;
use Soneso\StellarSDK\SEP\Interactive\RequestErrorException;
use Soneso\StellarSDK\SEP\Interactive\SEP24AuthenticationRequiredException;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositAsset;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositRequest;
use Soneso\StellarSDK\SEP\Interactive\SEP24FeeRequest;
use Soneso\StellarSDK\SEP\Interactive\SEP24Transaction;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionNotFoundException;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionRequest;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionsRequest;
use Soneso\StellarSDK\SEP\Interactive\SEP24WithdrawAsset;
use Soneso\StellarSDK\SEP\Interactive\SEP24WithdrawRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\FinancialAccountKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\OrganizationKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;

/**
 * Extended unit tests for SEP-24 Interactive Transfer classes.
 * Tests request/response parsing, building, and all getter/setter methods.
 */
class InteractiveExtendedTest extends TestCase
{
    private string $serviceAddress = "http://api.stellar.org/transfer-sep24/";
    private string $jwtToken = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.test";

    // SEP24Transaction Tests

    public function testSEP24TransactionParsingComplete(): void
    {
        $json = [
            'id' => 'tx123',
            'kind' => 'deposit',
            'status' => 'completed',
            'status_eta' => 3600,
            'kyc_verified' => true,
            'more_info_url' => 'https://example.com/info',
            'amount_in' => '100.50',
            'amount_in_asset' => 'stellar:USDC:GA5Z...',
            'amount_out' => '99.50',
            'amount_out_asset' => 'iso4217:USD',
            'amount_fee' => '1.00',
            'amount_fee_asset' => 'stellar:USDC:GA5Z...',
            'quote_id' => 'quote123',
            'started_at' => '2025-01-20T10:00:00Z',
            'completed_at' => '2025-01-20T11:00:00Z',
            'updated_at' => '2025-01-20T11:00:00Z',
            'user_action_required_by' => '2025-01-20T12:00:00Z',
            'stellar_transaction_id' => 'stellar_tx123',
            'external_transaction_id' => 'ext_tx123',
            'message' => 'Transaction completed',
            'refunded' => false,
            'from' => '1BvBMSEYstWetqTFn5Au4m4GFg7xJaNVN2',
            'to' => 'GACW7NONV43MZIFHCOKCQJAKSJSISSICFVUJ2C6EZIW5773OU3HD64VI',
            'deposit_memo' => '12345',
            'deposit_memo_type' => 'id',
            'claimable_balance_id' => 'cb123'
        ];

        $transaction = SEP24Transaction::fromJson($json);

        $this->assertEquals('tx123', $transaction->getId());
        $this->assertEquals('deposit', $transaction->getKind());
        $this->assertEquals('completed', $transaction->getStatus());
        $this->assertEquals(3600, $transaction->getStatusEta());
        $this->assertTrue($transaction->getKycVerified());
        $this->assertEquals('https://example.com/info', $transaction->getMoreInfoUrl());
        $this->assertEquals('100.50', $transaction->getAmountIn());
        $this->assertEquals('stellar:USDC:GA5Z...', $transaction->getAmountInAsset());
        $this->assertEquals('99.50', $transaction->getAmountOut());
        $this->assertEquals('iso4217:USD', $transaction->getAmountOutAsset());
        $this->assertEquals('1.00', $transaction->getAmountFee());
        $this->assertEquals('stellar:USDC:GA5Z...', $transaction->getAmountFeeAsset());
        $this->assertEquals('quote123', $transaction->getQuoteId());
        $this->assertEquals('2025-01-20T10:00:00Z', $transaction->getStartedAt());
        $this->assertEquals('2025-01-20T11:00:00Z', $transaction->getCompletedAt());
        $this->assertEquals('2025-01-20T11:00:00Z', $transaction->getUpdatedAt());
        $this->assertEquals('2025-01-20T12:00:00Z', $transaction->getUserActionRequiredBy());
        $this->assertEquals('stellar_tx123', $transaction->getStellarTransactionId());
        $this->assertEquals('ext_tx123', $transaction->getExternalTransactionId());
        $this->assertEquals('Transaction completed', $transaction->getMessage());
        $this->assertFalse($transaction->getRefunded());
        $this->assertEquals('1BvBMSEYstWetqTFn5Au4m4GFg7xJaNVN2', $transaction->getFrom());
        $this->assertEquals('GACW7NONV43MZIFHCOKCQJAKSJSISSICFVUJ2C6EZIW5773OU3HD64VI', $transaction->getTo());
        $this->assertEquals('12345', $transaction->getDepositMemo());
        $this->assertEquals('id', $transaction->getDepositMemoType());
        $this->assertEquals('cb123', $transaction->getClaimableBalanceId());
    }

    public function testSEP24TransactionWithdrawalFields(): void
    {
        $json = [
            'id' => 'tx456',
            'kind' => 'withdrawal',
            'status' => 'pending_external',
            'started_at' => '2025-01-20T10:00:00Z',
            'withdraw_anchor_account' => 'GBANAGOAXH5ONSBI2I6I5LHP2TCRHWMZIAMGUQH2TNKQNCOGJ7GC3ZOL',
            'withdraw_memo' => '186384',
            'withdraw_memo_type' => 'id'
        ];

        $transaction = SEP24Transaction::fromJson($json);

        $this->assertEquals('withdrawal', $transaction->getKind());
        $this->assertEquals('GBANAGOAXH5ONSBI2I6I5LHP2TCRHWMZIAMGUQH2TNKQNCOGJ7GC3ZOL', $transaction->getWithdrawAnchorAccount());
        $this->assertEquals('186384', $transaction->getWithdrawMemo());
        $this->assertEquals('id', $transaction->getWithdrawMemoType());
    }

    public function testSEP24TransactionWithRefunds(): void
    {
        $json = [
            'id' => 'tx789',
            'kind' => 'deposit',
            'status' => 'refunded',
            'started_at' => '2025-01-20T10:00:00Z',
            'refunded' => true,
            'refunds' => [
                'amount_refunded' => '100.00',
                'amount_fee' => '5.00',
                'payments' => [
                    [
                        'id' => 'refund_tx123',
                        'id_type' => 'stellar',
                        'amount' => '50.00',
                        'fee' => '2.50'
                    ],
                    [
                        'id' => 'refund_ext456',
                        'id_type' => 'external',
                        'amount' => '50.00',
                        'fee' => '2.50'
                    ]
                ]
            ]
        ];

        $transaction = SEP24Transaction::fromJson($json);

        $this->assertEquals('refunded', $transaction->getStatus());
        $this->assertTrue($transaction->getRefunded());

        $refunds = $transaction->getRefunds();
        $this->assertNotNull($refunds);
        $this->assertEquals('100.00', $refunds->getAmountRefunded());
        $this->assertEquals('5.00', $refunds->getAmountFee());

        $payments = $refunds->getPayments();
        $this->assertCount(2, $payments);

        $this->assertEquals('refund_tx123', $payments[0]->getId());
        $this->assertEquals('stellar', $payments[0]->getIdType());
        $this->assertEquals('50.00', $payments[0]->getAmount());
        $this->assertEquals('2.50', $payments[0]->getFee());

        $this->assertEquals('refund_ext456', $payments[1]->getId());
        $this->assertEquals('external', $payments[1]->getIdType());
    }

    public function testSEP24TransactionSetters(): void
    {
        $transaction = new SEP24Transaction();

        $transaction->setId('new_tx');
        $transaction->setKind('withdrawal');
        $transaction->setStatus('pending_anchor');
        $transaction->setStatusEta(1800);
        $transaction->setKycVerified(true);
        $transaction->setMoreInfoUrl('https://new.example.com');
        $transaction->setAmountIn('500.00');
        $transaction->setAmountInAsset('stellar:BTC:ISSUER');
        $transaction->setAmountOut('490.00');
        $transaction->setAmountOutAsset('iso4217:EUR');
        $transaction->setAmountFee('10.00');
        $transaction->setAmountFeeAsset('stellar:BTC:ISSUER');
        $transaction->setQuoteId('new_quote');
        $transaction->setStartedAt('2025-01-21T10:00:00Z');
        $transaction->setCompletedAt('2025-01-21T11:00:00Z');
        $transaction->setUpdatedAt('2025-01-21T11:30:00Z');
        $transaction->setUserActionRequiredBy('2025-01-22T10:00:00Z');
        $transaction->setStellarTransactionId('new_stellar_tx');
        $transaction->setExternalTransactionId('new_ext_tx');
        $transaction->setMessage('Processing');
        $transaction->setRefunded(false);
        $transaction->setFrom('GACCOUNT...');
        $transaction->setTo('BACCOUNT...');
        $transaction->setDepositMemo('67890');
        $transaction->setDepositMemoType('text');
        $transaction->setClaimableBalanceId('new_cb');
        $transaction->setWithdrawAnchorAccount('GWITHDRAW...');
        $transaction->setWithdrawMemo('98765');
        $transaction->setWithdrawMemoType('hash');

        $this->assertEquals('new_tx', $transaction->getId());
        $this->assertEquals('withdrawal', $transaction->getKind());
        $this->assertEquals('pending_anchor', $transaction->getStatus());
        $this->assertEquals(1800, $transaction->getStatusEta());
        $this->assertTrue($transaction->getKycVerified());
        $this->assertEquals('https://new.example.com', $transaction->getMoreInfoUrl());
        $this->assertEquals('500.00', $transaction->getAmountIn());
        $this->assertEquals('stellar:BTC:ISSUER', $transaction->getAmountInAsset());
        $this->assertEquals('490.00', $transaction->getAmountOut());
        $this->assertEquals('iso4217:EUR', $transaction->getAmountOutAsset());
        $this->assertEquals('10.00', $transaction->getAmountFee());
        $this->assertEquals('stellar:BTC:ISSUER', $transaction->getAmountFeeAsset());
        $this->assertEquals('new_quote', $transaction->getQuoteId());
        $this->assertEquals('2025-01-21T10:00:00Z', $transaction->getStartedAt());
        $this->assertEquals('2025-01-21T11:00:00Z', $transaction->getCompletedAt());
        $this->assertEquals('2025-01-21T11:30:00Z', $transaction->getUpdatedAt());
        $this->assertEquals('2025-01-22T10:00:00Z', $transaction->getUserActionRequiredBy());
        $this->assertEquals('new_stellar_tx', $transaction->getStellarTransactionId());
        $this->assertEquals('new_ext_tx', $transaction->getExternalTransactionId());
        $this->assertEquals('Processing', $transaction->getMessage());
        $this->assertFalse($transaction->getRefunded());
        $this->assertEquals('GACCOUNT...', $transaction->getFrom());
        $this->assertEquals('BACCOUNT...', $transaction->getTo());
        $this->assertEquals('67890', $transaction->getDepositMemo());
        $this->assertEquals('text', $transaction->getDepositMemoType());
        $this->assertEquals('new_cb', $transaction->getClaimableBalanceId());
        $this->assertEquals('GWITHDRAW...', $transaction->getWithdrawAnchorAccount());
        $this->assertEquals('98765', $transaction->getWithdrawMemo());
        $this->assertEquals('hash', $transaction->getWithdrawMemoType());
    }

    // SEP24WithdrawRequest Tests

    public function testSEP24WithdrawRequestComplete(): void
    {
        $request = new SEP24WithdrawRequest();

        $request->setJwt($this->jwtToken);
        $request->setAssetCode('USDC');
        $request->setAssetIssuer('GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN');
        $request->setDestinationAsset('iso4217:USD');
        $request->setAmount(100.50);
        $request->setQuoteId('quote789');
        $request->setAccount('GACCOUNT123');
        $request->setMemo('memo123');
        $request->setMemoType('text');
        $request->setWalletName('Test Wallet');
        $request->setWalletUrl('https://wallet.example.com');
        $request->setLang('en-US');
        $request->setRefundMemo('refund456');
        $request->setRefundMemoType('id');
        $request->setCustomerId('customer789');

        $kycFields = new StandardKYCFields();
        $naturalPerson = new NaturalPersonKYCFields();
        $naturalPerson->firstName = 'Alice';
        $naturalPerson->lastName = 'Smith';
        $kycFields->naturalPersonKYCFields = $naturalPerson;
        $request->setKycFields($kycFields);

        $customFields = ['custom_field' => 'value'];
        $request->setCustomFields($customFields);

        $customFiles = ['document' => 'binary_data'];
        $request->setCustomFiles($customFiles);

        $this->assertEquals($this->jwtToken, $request->getJwt());
        $this->assertEquals('USDC', $request->getAssetCode());
        $this->assertEquals('GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN', $request->getAssetIssuer());
        $this->assertEquals('iso4217:USD', $request->getDestinationAsset());
        $this->assertEquals(100.50, $request->getAmount());
        $this->assertEquals('quote789', $request->getQuoteId());
        $this->assertEquals('GACCOUNT123', $request->getAccount());
        $this->assertEquals('memo123', $request->getMemo());
        $this->assertEquals('text', $request->getMemoType());
        $this->assertEquals('Test Wallet', $request->getWalletName());
        $this->assertEquals('https://wallet.example.com', $request->getWalletUrl());
        $this->assertEquals('en-US', $request->getLang());
        $this->assertEquals('refund456', $request->getRefundMemo());
        $this->assertEquals('id', $request->getRefundMemoType());
        $this->assertEquals('customer789', $request->getCustomerId());
        $this->assertNotNull($request->getKycFields());
        $this->assertEquals('Alice', $request->getKycFields()->naturalPersonKYCFields->firstName);
        $this->assertEquals($customFields, $request->getCustomFields());
        $this->assertEquals($customFiles, $request->getCustomFiles());
    }

    public function testSEP24WithdrawRequestMinimal(): void
    {
        $request = new SEP24WithdrawRequest();
        $request->jwt = $this->jwtToken;
        $request->assetCode = 'native';

        $this->assertEquals($this->jwtToken, $request->jwt);
        $this->assertEquals('native', $request->assetCode);
        $this->assertNull($request->assetIssuer);
        $this->assertNull($request->amount);
        $this->assertNull($request->kycFields);
    }

    // SEP24DepositRequest Tests

    public function testSEP24DepositRequestComplete(): void
    {
        $request = new SEP24DepositRequest();

        $request->setJwt($this->jwtToken);
        $request->setAssetCode('USDC');
        $request->setAssetIssuer('GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN');
        $request->setSourceAsset('iso4217:USD');
        $request->setAmount(200.75);
        $request->setQuoteId('quote456');
        $request->setAccount('GACCOUNT456');
        $request->setMemo('deposit_memo');
        $request->setMemoType('id');
        $request->setWalletName('Deposit Wallet');
        $request->setWalletUrl('https://deposit.example.com');
        $request->setLang('fr');
        $request->setClaimableBalanceSupported('true');
        $request->setCustomerId('customer456');

        $kycFields = new StandardKYCFields();
        $orgKyc = new OrganizationKYCFields();
        $orgKyc->name = 'Test Corp';
        $kycFields->organizationKYCFields = $orgKyc;
        $request->setKycFields($kycFields);

        $customFields = ['extra_info' => 'test'];
        $request->setCustomFields($customFields);

        $this->assertEquals($this->jwtToken, $request->getJwt());
        $this->assertEquals('USDC', $request->getAssetCode());
        $this->assertEquals('GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN', $request->getAssetIssuer());
        $this->assertEquals('iso4217:USD', $request->getSourceAsset());
        $this->assertEquals(200.75, $request->getAmount());
        $this->assertEquals('quote456', $request->getQuoteId());
        $this->assertEquals('GACCOUNT456', $request->getAccount());
        $this->assertEquals('deposit_memo', $request->getMemo());
        $this->assertEquals('id', $request->getMemoType());
        $this->assertEquals('Deposit Wallet', $request->getWalletName());
        $this->assertEquals('https://deposit.example.com', $request->getWalletUrl());
        $this->assertEquals('fr', $request->getLang());
        $this->assertEquals('true', $request->getClaimableBalanceSupported());
        $this->assertEquals('customer456', $request->getCustomerId());
        $this->assertNotNull($request->getKycFields());
        $this->assertEquals('Test Corp', $request->getKycFields()->organizationKYCFields->name);
        $this->assertEquals($customFields, $request->getCustomFields());
    }

    // SEP24TransactionsRequest Tests

    public function testSEP24TransactionsRequestComplete(): void
    {
        $request = new SEP24TransactionsRequest();

        $noOlderThan = new DateTime('2025-01-01T00:00:00Z');

        $request->setJwt($this->jwtToken);
        $request->setAssetCode('ETH');
        $request->setNoOlderThan($noOlderThan);
        $request->setLimit(50);
        $request->setKind('deposit');
        $request->setPagingId('page123');
        $request->setLang('es');

        $this->assertEquals($this->jwtToken, $request->getJwt());
        $this->assertEquals('ETH', $request->getAssetCode());
        $this->assertEquals($noOlderThan, $request->getNoOlderThan());
        $this->assertEquals(50, $request->getLimit());
        $this->assertEquals('deposit', $request->getKind());
        $this->assertEquals('page123', $request->getPagingId());
        $this->assertEquals('es', $request->getLang());
    }

    public function testSEP24TransactionsRequestMinimal(): void
    {
        $request = new SEP24TransactionsRequest();
        $request->jwt = $this->jwtToken;
        $request->assetCode = 'BTC';

        $this->assertEquals($this->jwtToken, $request->jwt);
        $this->assertEquals('BTC', $request->assetCode);
        $this->assertNull($request->noOlderThan);
        $this->assertNull($request->limit);
        $this->assertNull($request->kind);
    }

    // SEP24DepositAsset Tests

    public function testSEP24DepositAssetComplete(): void
    {
        $json = [
            'enabled' => true,
            'min_amount' => 10.0,
            'max_amount' => 10000.0,
            'fee_fixed' => 5.0,
            'fee_percent' => 1.5,
            'fee_minimum' => 2.0
        ];

        $asset = SEP24DepositAsset::fromJson($json);

        $this->assertTrue($asset->isEnabled());
        $this->assertEquals(10.0, $asset->getMinAmount());
        $this->assertEquals(10000.0, $asset->getMaxAmount());
        $this->assertEquals(5.0, $asset->getFeeFixed());
        $this->assertEquals(1.5, $asset->getFeePercent());
        $this->assertEquals(2.0, $asset->getFeeMinimum());
    }

    public function testSEP24DepositAssetSetters(): void
    {
        $asset = new SEP24DepositAsset();

        $asset->setEnabled(true);
        $asset->setMinAmount(5.0);
        $asset->setMaxAmount(5000.0);
        $asset->setFeeFixed(3.0);
        $asset->setFeePercent(0.5);
        $asset->setFeeMinimum(1.0);

        $this->assertTrue($asset->isEnabled());
        $this->assertEquals(5.0, $asset->getMinAmount());
        $this->assertEquals(5000.0, $asset->getMaxAmount());
        $this->assertEquals(3.0, $asset->getFeeFixed());
        $this->assertEquals(0.5, $asset->getFeePercent());
        $this->assertEquals(1.0, $asset->getFeeMinimum());
    }

    public function testSEP24DepositAssetDisabled(): void
    {
        $json = [
            'enabled' => false
        ];

        $asset = SEP24DepositAsset::fromJson($json);

        $this->assertFalse($asset->isEnabled());
        $this->assertNull($asset->getMinAmount());
        $this->assertNull($asset->getMaxAmount());
    }

    // SEP24WithdrawAsset Tests

    public function testSEP24WithdrawAssetComplete(): void
    {
        $json = [
            'enabled' => true,
            'min_amount' => 20.0,
            'max_amount' => 20000.0,
            'fee_fixed' => 10.0,
            'fee_percent' => 2.0,
            'fee_minimum' => 5.0
        ];

        $asset = SEP24WithdrawAsset::fromJson($json);

        $this->assertTrue($asset->isEnabled());
        $this->assertEquals(20.0, $asset->getMinAmount());
        $this->assertEquals(20000.0, $asset->getMaxAmount());
        $this->assertEquals(10.0, $asset->getFeeFixed());
        $this->assertEquals(2.0, $asset->getFeePercent());
        $this->assertEquals(5.0, $asset->getFeeMinimum());
    }

    public function testSEP24WithdrawAssetSetters(): void
    {
        $asset = new SEP24WithdrawAsset();

        $asset->setEnabled(false);
        $asset->setMinAmount(15.0);
        $asset->setMaxAmount(15000.0);
        $asset->setFeeFixed(7.5);
        $asset->setFeePercent(1.25);
        $asset->setFeeMinimum(3.5);

        $this->assertFalse($asset->isEnabled());
        $this->assertEquals(15.0, $asset->getMinAmount());
        $this->assertEquals(15000.0, $asset->getMaxAmount());
        $this->assertEquals(7.5, $asset->getFeeFixed());
        $this->assertEquals(1.25, $asset->getFeePercent());
        $this->assertEquals(3.5, $asset->getFeeMinimum());
    }

    // SEP24FeeRequest Tests

    public function testSEP24FeeRequestConstructorComplete(): void
    {
        $request = new SEP24FeeRequest('deposit', 'USDC', 1000.0, 'bank_account', $this->jwtToken);

        $this->assertEquals('deposit', $request->getOperation());
        $this->assertEquals('USDC', $request->getAssetCode());
        $this->assertEquals(1000.0, $request->getAmount());
        $this->assertEquals('bank_account', $request->getType());
        $this->assertEquals($this->jwtToken, $request->getJwt());
    }

    public function testSEP24FeeRequestConstructorMinimal(): void
    {
        $request = new SEP24FeeRequest('withdrawal', 'BTC', 0.5);

        $this->assertEquals('withdrawal', $request->getOperation());
        $this->assertEquals('BTC', $request->getAssetCode());
        $this->assertEquals(0.5, $request->getAmount());
        $this->assertNull($request->getType());
        $this->assertNull($request->getJwt());
    }

    public function testSEP24FeeRequestSetters(): void
    {
        $request = new SEP24FeeRequest('deposit', 'ETH', 10.0);

        $request->setOperation('withdrawal');
        $request->setAssetCode('XRP');
        $request->setAmount(25.5);
        $request->setType('SEPA');
        $request->setJwt($this->jwtToken);

        $this->assertEquals('withdrawal', $request->getOperation());
        $this->assertEquals('XRP', $request->getAssetCode());
        $this->assertEquals(25.5, $request->getAmount());
        $this->assertEquals('SEPA', $request->getType());
        $this->assertEquals($this->jwtToken, $request->getJwt());
    }

    // Refund Tests

    public function testRefundParsing(): void
    {
        $json = [
            'amount_refunded' => '150.00',
            'amount_fee' => '7.50',
            'payments' => [
                [
                    'id' => 'payment1',
                    'id_type' => 'stellar',
                    'amount' => '75.00',
                    'fee' => '3.75'
                ],
                [
                    'id' => 'payment2',
                    'id_type' => 'external',
                    'amount' => '75.00',
                    'fee' => '3.75'
                ]
            ]
        ];

        $refund = Refund::fromJson($json);

        $this->assertEquals('150.00', $refund->getAmountRefunded());
        $this->assertEquals('7.50', $refund->getAmountFee());
        $this->assertCount(2, $refund->getPayments());
    }

    public function testRefundSetters(): void
    {
        $refund = new Refund();

        $payment1 = new RefundPayment();
        $payment1->setId('new_payment1');
        $payment1->setIdType('stellar');
        $payment1->setAmount('100.00');
        $payment1->setFee('5.00');

        $payment2 = new RefundPayment();
        $payment2->setId('new_payment2');
        $payment2->setIdType('external');
        $payment2->setAmount('100.00');
        $payment2->setFee('5.00');

        $refund->setAmountRefunded('200.00');
        $refund->setAmountFee('10.00');
        $refund->setPayments([$payment1, $payment2]);

        $this->assertEquals('200.00', $refund->getAmountRefunded());
        $this->assertEquals('10.00', $refund->getAmountFee());
        $this->assertCount(2, $refund->getPayments());
        $this->assertEquals('new_payment1', $refund->getPayments()[0]->getId());
        $this->assertEquals('new_payment2', $refund->getPayments()[1]->getId());
    }

    // RefundPayment Tests

    public function testRefundPaymentParsing(): void
    {
        $json = [
            'id' => 'refund_payment_123',
            'id_type' => 'stellar',
            'amount' => '50.00',
            'fee' => '2.50'
        ];

        $payment = RefundPayment::fromJson($json);

        $this->assertEquals('refund_payment_123', $payment->getId());
        $this->assertEquals('stellar', $payment->getIdType());
        $this->assertEquals('50.00', $payment->getAmount());
        $this->assertEquals('2.50', $payment->getFee());
    }

    public function testRefundPaymentSetters(): void
    {
        $payment = new RefundPayment();

        $payment->setId('new_refund_id');
        $payment->setIdType('external');
        $payment->setAmount('75.00');
        $payment->setFee('3.75');

        $this->assertEquals('new_refund_id', $payment->getId());
        $this->assertEquals('external', $payment->getIdType());
        $this->assertEquals('75.00', $payment->getAmount());
        $this->assertEquals('3.75', $payment->getFee());
    }

    // InteractiveService Tests with Mocks

    public function testInteractiveServiceDepositWithAllFields(): void
    {
        $service = new InteractiveService($this->serviceAddress);

        $responseJson = '{"type": "interactive_customer_info_needed", "url": "https://example.com/deposit", "id": "deposit_tx123"}';

        $mock = new MockHandler([
            new Response(200, [], $responseJson)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $body = $request->getBody()->__toString();

            $this->assertStringContainsString('asset_code', $body);
            $this->assertStringContainsString('USDC', $body);
            $this->assertStringContainsString('source_asset', $body);
            $this->assertStringContainsString('iso4217:USD', $body);
            $this->assertStringContainsString('amount', $body);
            $this->assertStringContainsString('quote_id', $body);
            $this->assertStringContainsString('account', $body);
            $this->assertStringContainsString('memo', $body);
            $this->assertStringContainsString('memo_type', $body);
            $this->assertStringContainsString('lang', $body);
            $this->assertStringContainsString('customer_id', $body);

            return $request;
        }));

        $service->setMockHandlerStack($stack);

        $request = new SEP24DepositRequest();
        $request->jwt = $this->jwtToken;
        $request->assetCode = 'USDC';
        $request->assetIssuer = 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN';
        $request->sourceAsset = 'iso4217:USD';
        $request->amount = 100.0;
        $request->quoteId = 'quote123';
        $request->account = 'GACCOUNT123';
        $request->memo = 'test_memo';
        $request->memoType = 'text';
        $request->lang = 'en';
        $request->customerId = 'customer123';

        $response = $service->deposit($request);

        $this->assertEquals('deposit_tx123', $response->id);
        $this->assertEquals('interactive_customer_info_needed', $response->type);
        $this->assertEquals('https://example.com/deposit', $response->url);
    }

    public function testInteractiveServiceWithdrawWithAllFields(): void
    {
        $service = new InteractiveService($this->serviceAddress);

        $responseJson = '{"type": "interactive_customer_info_needed", "url": "https://example.com/withdraw", "id": "withdraw_tx456"}';

        $mock = new MockHandler([
            new Response(200, [], $responseJson)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $body = $request->getBody()->__toString();

            $this->assertStringContainsString('asset_code', $body);
            $this->assertStringContainsString('BTC', $body);
            $this->assertStringContainsString('destination_asset', $body);
            $this->assertStringContainsString('amount', $body);
            $this->assertStringContainsString('refund_memo', $body);
            $this->assertStringContainsString('refund_memo_type', $body);

            return $request;
        }));

        $service->setMockHandlerStack($stack);

        $request = new SEP24WithdrawRequest();
        $request->jwt = $this->jwtToken;
        $request->assetCode = 'BTC';
        $request->destinationAsset = 'iso4217:USD';
        $request->amount = 0.5;
        $request->refundMemo = 'refund123';
        $request->refundMemoType = 'id';

        $response = $service->withdraw($request);

        $this->assertEquals('withdraw_tx456', $response->id);
        $this->assertEquals('interactive_customer_info_needed', $response->type);
    }

    public function testInteractiveServiceTransactionsWithFilters(): void
    {
        $service = new InteractiveService($this->serviceAddress);

        $responseJson = '{"transactions": []}';

        $mock = new MockHandler([
            new Response(200, [], $responseJson)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            parse_str($request->getUri()->getQuery(), $query);

            $this->assertEquals('ETH', $query['asset_code']);
            $this->assertEquals('deposit', $query['kind']);
            $this->assertEquals('25', $query['limit']);
            $this->assertEquals('page456', $query['paging_id']);
            $this->assertEquals('fr', $query['lang']);
            $this->assertArrayHasKey('no_older_than', $query);

            return $request;
        }));

        $service->setMockHandlerStack($stack);

        $request = new SEP24TransactionsRequest();
        $request->jwt = $this->jwtToken;
        $request->assetCode = 'ETH';
        $request->kind = 'deposit';
        $request->limit = 25;
        $request->pagingId = 'page456';
        $request->lang = 'fr';
        $request->noOlderThan = new DateTime('2025-01-01');

        $response = $service->transactions($request);

        $this->assertNotNull($response);
        $this->assertCount(0, $response->getTransactions());
    }

    public function testInteractiveServiceTransactionById(): void
    {
        $service = new InteractiveService($this->serviceAddress);

        $responseJson = '{"transaction": {"id": "tx123", "kind": "deposit", "status": "completed", "started_at": "2025-01-20T10:00:00Z"}}';

        $mock = new MockHandler([
            new Response(200, [], $responseJson)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            parse_str($request->getUri()->getQuery(), $query);

            $this->assertEquals('tx123', $query['id']);

            return $request;
        }));

        $service->setMockHandlerStack($stack);

        $request = new SEP24TransactionRequest();
        $request->jwt = $this->jwtToken;
        $request->id = 'tx123';

        $response = $service->transaction($request);

        $this->assertNotNull($response);
        $this->assertEquals('tx123', $response->getTransaction()->getId());
        $this->assertEquals('deposit', $response->getTransaction()->getKind());
        $this->assertEquals('completed', $response->getTransaction()->getStatus());
    }

    public function testInteractiveServiceTransactionByStellarTxId(): void
    {
        $service = new InteractiveService($this->serviceAddress);

        $responseJson = '{"transaction": {"id": "tx456", "kind": "withdrawal", "status": "pending_stellar", "started_at": "2025-01-20T10:00:00Z", "stellar_transaction_id": "stellar_tx789"}}';

        $mock = new MockHandler([
            new Response(200, [], $responseJson)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            parse_str($request->getUri()->getQuery(), $query);

            $this->assertEquals('stellar_tx789', $query['stellar_transaction_id']);

            return $request;
        }));

        $service->setMockHandlerStack($stack);

        $request = new SEP24TransactionRequest();
        $request->jwt = $this->jwtToken;
        $request->stellarTransactionId = 'stellar_tx789';

        $response = $service->transaction($request);

        $this->assertNotNull($response);
        $this->assertEquals('stellar_tx789', $response->getTransaction()->getStellarTransactionId());
    }

    public function testInteractiveServiceTransactionByExternalTxId(): void
    {
        $service = new InteractiveService($this->serviceAddress);

        $responseJson = '{"transaction": {"id": "tx789", "kind": "deposit", "status": "completed", "started_at": "2025-01-20T10:00:00Z", "external_transaction_id": "ext_tx999"}}';

        $mock = new MockHandler([
            new Response(200, [], $responseJson)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            parse_str($request->getUri()->getQuery(), $query);

            $this->assertEquals('ext_tx999', $query['external_transaction_id']);

            return $request;
        }));

        $service->setMockHandlerStack($stack);

        $request = new SEP24TransactionRequest();
        $request->jwt = $this->jwtToken;
        $request->externalTransactionId = 'ext_tx999';

        $response = $service->transaction($request);

        $this->assertNotNull($response);
        $this->assertEquals('ext_tx999', $response->getTransaction()->getExternalTransactionId());
    }

    public function testInteractiveServiceFeeRequest(): void
    {
        $service = new InteractiveService($this->serviceAddress);

        $responseJson = '{"fee": 2.5}';

        $mock = new MockHandler([
            new Response(200, [], $responseJson)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            parse_str($request->getUri()->getQuery(), $query);

            $this->assertEquals('deposit', $query['operation']);
            $this->assertEquals('USDC', $query['asset_code']);
            $this->assertEquals('1000', $query['amount']);
            $this->assertEquals('bank_account', $query['type']);

            return $request;
        }));

        $service->setMockHandlerStack($stack);

        $request = new SEP24FeeRequest('deposit', 'USDC', 1000.0, 'bank_account', $this->jwtToken);

        $response = $service->fee($request);

        $this->assertNotNull($response);
        $this->assertEquals(2.5, $response->getFee());
    }

    public function testInteractiveServiceAuthenticationRequired(): void
    {
        $service = new InteractiveService($this->serviceAddress);

        $mock = new MockHandler([
            new Response(403, [], '{"type": "authentication_required"}')
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);

        $service->setMockHandlerStack($stack);

        $request = new SEP24FeeRequest('deposit', 'USDC', 100.0);

        $this->expectException(SEP24AuthenticationRequiredException::class);
        $service->fee($request);
    }

    public function testInteractiveServiceRequestError(): void
    {
        $service = new InteractiveService($this->serviceAddress);

        $mock = new MockHandler([
            new Response(400, [], '{"error": "Invalid asset code"}')
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);

        $service->setMockHandlerStack($stack);

        $request = new SEP24FeeRequest('deposit', 'INVALID', 100.0, null, $this->jwtToken);

        $this->expectException(RequestErrorException::class);
        $service->fee($request);
    }

    public function testInteractiveServiceTransactionNotFound(): void
    {
        $service = new InteractiveService($this->serviceAddress);

        $mock = new MockHandler([
            new Response(404, [], '{"error": "Transaction not found"}')
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);

        $service->setMockHandlerStack($stack);

        $request = new SEP24TransactionRequest();
        $request->jwt = $this->jwtToken;
        $request->id = 'nonexistent_tx';

        $this->expectException(SEP24TransactionNotFoundException::class);
        $service->transaction($request);
    }

    // Edge Cases and Validation Tests

    public function testSEP24TransactionAllStatusValues(): void
    {
        $statuses = [
            'incomplete', 'pending_user_transfer_start', 'pending_user_transfer_complete',
            'pending_external', 'pending_anchor', 'on_hold', 'pending_stellar',
            'pending_trust', 'pending_user', 'completed', 'refunded', 'expired',
            'no_market', 'too_small', 'too_large', 'error'
        ];

        foreach ($statuses as $status) {
            $json = [
                'id' => 'test_tx',
                'kind' => 'deposit',
                'status' => $status,
                'started_at' => '2025-01-20T10:00:00Z'
            ];

            $transaction = SEP24Transaction::fromJson($json);
            $this->assertEquals($status, $transaction->getStatus());
        }
    }

    public function testSEP24TransactionBothKindValues(): void
    {
        $depositJson = [
            'id' => 'deposit_tx',
            'kind' => 'deposit',
            'status' => 'completed',
            'started_at' => '2025-01-20T10:00:00Z'
        ];

        $withdrawalJson = [
            'id' => 'withdrawal_tx',
            'kind' => 'withdrawal',
            'status' => 'completed',
            'started_at' => '2025-01-20T10:00:00Z'
        ];

        $depositTx = SEP24Transaction::fromJson($depositJson);
        $withdrawalTx = SEP24Transaction::fromJson($withdrawalJson);

        $this->assertEquals('deposit', $depositTx->getKind());
        $this->assertEquals('withdrawal', $withdrawalTx->getKind());
    }

    public function testSEP24TransactionNullableFields(): void
    {
        $json = [
            'id' => 'minimal_tx',
            'kind' => 'deposit',
            'status' => 'pending_anchor',
            'started_at' => '2025-01-20T10:00:00Z'
        ];

        $transaction = SEP24Transaction::fromJson($json);

        $this->assertNull($transaction->getStatusEta());
        $this->assertNull($transaction->getKycVerified());
        $this->assertNull($transaction->getMoreInfoUrl());
        $this->assertNull($transaction->getAmountIn());
        $this->assertNull($transaction->getAmountOut());
        $this->assertNull($transaction->getAmountFee());
        $this->assertNull($transaction->getCompletedAt());
        $this->assertNull($transaction->getUpdatedAt());
        $this->assertNull($transaction->getUserActionRequiredBy());
        $this->assertNull($transaction->getRefunds());
    }

    public function testRefundWithoutPayments(): void
    {
        $json = [
            'amount_refunded' => '100.00',
            'amount_fee' => '5.00'
        ];

        $refund = Refund::fromJson($json);

        $this->assertEquals('100.00', $refund->getAmountRefunded());
        $this->assertEquals('5.00', $refund->getAmountFee());
        $this->assertCount(0, $refund->getPayments());
    }

    public function testSEP24DepositRequestWithKYCAndCustomFields(): void
    {
        $request = new SEP24DepositRequest();
        $request->jwt = $this->jwtToken;
        $request->assetCode = 'USDC';

        $kycFields = new StandardKYCFields();

        $naturalPerson = new NaturalPersonKYCFields();
        $naturalPerson->firstName = 'John';
        $naturalPerson->lastName = 'Doe';
        $naturalPerson->emailAddress = 'john@example.com';

        $financialAccount = new FinancialAccountKYCFields();
        $financialAccount->bankAccountNumber = '123456789';
        $financialAccount->bankNumber = '987654321';
        $naturalPerson->financialAccountKYCFields = $financialAccount;

        $kycFields->naturalPersonKYCFields = $naturalPerson;

        $orgKyc = new OrganizationKYCFields();
        $orgKyc->name = 'Test Organization';
        $orgKyc->registrationNumber = 'REG123456';
        $kycFields->organizationKYCFields = $orgKyc;

        $request->kycFields = $kycFields;
        $request->customFields = ['custom_key' => 'custom_value', 'another_key' => 'another_value'];
        $request->customFiles = ['id_document' => 'binary_data_here'];

        $this->assertNotNull($request->kycFields);
        $this->assertEquals('John', $request->kycFields->naturalPersonKYCFields->firstName);
        $this->assertEquals('Test Organization', $request->kycFields->organizationKYCFields->name);
        $this->assertCount(2, $request->customFields);
        $this->assertArrayHasKey('id_document', $request->customFiles);
    }

    public function testSEP24WithdrawRequestWithKYCAndCustomFields(): void
    {
        $request = new SEP24WithdrawRequest();
        $request->jwt = $this->jwtToken;
        $request->assetCode = 'BTC';

        $kycFields = new StandardKYCFields();

        $naturalPerson = new NaturalPersonKYCFields();
        $naturalPerson->firstName = 'Jane';
        $naturalPerson->lastName = 'Smith';

        $kycFields->naturalPersonKYCFields = $naturalPerson;
        $request->kycFields = $kycFields;

        $request->customFields = ['withdrawal_reason' => 'personal'];
        $request->customFiles = ['proof_of_address' => 'document_binary'];

        $this->assertEquals('Jane', $request->kycFields->naturalPersonKYCFields->firstName);
        $this->assertEquals('Smith', $request->kycFields->naturalPersonKYCFields->lastName);
        $this->assertEquals('personal', $request->customFields['withdrawal_reason']);
    }

    public function testSEP24TransactionKYCVerifiedValues(): void
    {
        $verifiedJson = [
            'id' => 'tx1',
            'kind' => 'deposit',
            'status' => 'completed',
            'started_at' => '2025-01-20T10:00:00Z',
            'kyc_verified' => true
        ];

        $unverifiedJson = [
            'id' => 'tx2',
            'kind' => 'deposit',
            'status' => 'incomplete',
            'started_at' => '2025-01-20T10:00:00Z',
            'kyc_verified' => false
        ];

        $verifiedTx = SEP24Transaction::fromJson($verifiedJson);
        $unverifiedTx = SEP24Transaction::fromJson($unverifiedJson);

        $this->assertTrue($verifiedTx->getKycVerified());
        $this->assertFalse($unverifiedTx->getKycVerified());
    }

    public function testSEP24TransactionMemoTypes(): void
    {
        $memoTypes = ['text', 'id', 'hash'];

        foreach ($memoTypes as $memoType) {
            $depositJson = [
                'id' => 'deposit_tx',
                'kind' => 'deposit',
                'status' => 'completed',
                'started_at' => '2025-01-20T10:00:00Z',
                'deposit_memo' => 'test_memo',
                'deposit_memo_type' => $memoType
            ];

            $withdrawalJson = [
                'id' => 'withdrawal_tx',
                'kind' => 'withdrawal',
                'status' => 'completed',
                'started_at' => '2025-01-20T10:00:00Z',
                'withdraw_memo' => 'test_memo',
                'withdraw_memo_type' => $memoType
            ];

            $depositTx = SEP24Transaction::fromJson($depositJson);
            $withdrawalTx = SEP24Transaction::fromJson($withdrawalJson);

            $this->assertEquals($memoType, $depositTx->getDepositMemoType());
            $this->assertEquals($memoType, $withdrawalTx->getWithdrawMemoType());
        }
    }

    public function testRefundPaymentIdTypes(): void
    {
        $stellarJson = [
            'id' => 'stellar_hash',
            'id_type' => 'stellar',
            'amount' => '100.00',
            'fee' => '5.00'
        ];

        $externalJson = [
            'id' => 'external_ref',
            'id_type' => 'external',
            'amount' => '100.00',
            'fee' => '5.00'
        ];

        $stellarPayment = RefundPayment::fromJson($stellarJson);
        $externalPayment = RefundPayment::fromJson($externalJson);

        $this->assertEquals('stellar', $stellarPayment->getIdType());
        $this->assertEquals('external', $externalPayment->getIdType());
    }

    // Additional Transaction State Tests

    public function testTransactionStateIncomplete(): void
    {
        $json = [
            'id' => 'incomplete_tx',
            'kind' => 'deposit',
            'status' => 'incomplete',
            'started_at' => '2025-01-20T10:00:00Z',
            'message' => 'More info needed from user'
        ];

        $transaction = SEP24Transaction::fromJson($json);

        $this->assertEquals('incomplete', $transaction->getStatus());
        $this->assertEquals('More info needed from user', $transaction->getMessage());
        $this->assertNull($transaction->getCompletedAt());
    }

    public function testTransactionStatePendingUserTransferStart(): void
    {
        $json = [
            'id' => 'pending_user_start_tx',
            'kind' => 'deposit',
            'status' => 'pending_user_transfer_start',
            'started_at' => '2025-01-20T10:00:00Z',
            'more_info_url' => 'https://example.com/instructions',
            'message' => 'Please send funds to the provided address'
        ];

        $transaction = SEP24Transaction::fromJson($json);

        $this->assertEquals('pending_user_transfer_start', $transaction->getStatus());
        $this->assertEquals('https://example.com/instructions', $transaction->getMoreInfoUrl());
        $this->assertNotNull($transaction->getMessage());
    }

    public function testTransactionStatePendingUserTransferComplete(): void
    {
        $json = [
            'id' => 'pending_complete_tx',
            'kind' => 'deposit',
            'status' => 'pending_user_transfer_complete',
            'started_at' => '2025-01-20T10:00:00Z',
            'status_eta' => 600,
            'message' => 'Waiting for user to complete the transfer'
        ];

        $transaction = SEP24Transaction::fromJson($json);

        $this->assertEquals('pending_user_transfer_complete', $transaction->getStatus());
        $this->assertEquals(600, $transaction->getStatusEta());
    }

    public function testTransactionStatePendingTrust(): void
    {
        $json = [
            'id' => 'pending_trust_tx',
            'kind' => 'deposit',
            'status' => 'pending_trust',
            'started_at' => '2025-01-20T10:00:00Z',
            'to' => 'GACCOUNT123',
            'message' => 'Destination account must establish trustline for asset'
        ];

        $transaction = SEP24Transaction::fromJson($json);

        $this->assertEquals('pending_trust', $transaction->getStatus());
        $this->assertEquals('GACCOUNT123', $transaction->getTo());
        $this->assertStringContainsString('trustline', $transaction->getMessage());
    }

    public function testTransactionStatePendingUser(): void
    {
        $json = [
            'id' => 'pending_user_tx',
            'kind' => 'withdrawal',
            'status' => 'pending_user',
            'started_at' => '2025-01-20T10:00:00Z',
            'user_action_required_by' => '2025-01-21T10:00:00Z',
            'message' => 'User action required to complete withdrawal'
        ];

        $transaction = SEP24Transaction::fromJson($json);

        $this->assertEquals('pending_user', $transaction->getStatus());
        $this->assertEquals('2025-01-21T10:00:00Z', $transaction->getUserActionRequiredBy());
    }

    public function testTransactionStateOnHold(): void
    {
        $json = [
            'id' => 'on_hold_tx',
            'kind' => 'deposit',
            'status' => 'on_hold',
            'started_at' => '2025-01-20T10:00:00Z',
            'message' => 'Transaction is on hold pending review'
        ];

        $transaction = SEP24Transaction::fromJson($json);

        $this->assertEquals('on_hold', $transaction->getStatus());
        $this->assertNull($transaction->getStatusEta());
    }

    public function testTransactionStatePendingStellar(): void
    {
        $json = [
            'id' => 'pending_stellar_tx',
            'kind' => 'withdrawal',
            'status' => 'pending_stellar',
            'started_at' => '2025-01-20T10:00:00Z',
            'stellar_transaction_id' => 'stellar_hash_123',
            'status_eta' => 30
        ];

        $transaction = SEP24Transaction::fromJson($json);

        $this->assertEquals('pending_stellar', $transaction->getStatus());
        $this->assertEquals('stellar_hash_123', $transaction->getStellarTransactionId());
        $this->assertEquals(30, $transaction->getStatusEta());
    }

    public function testTransactionStateExpired(): void
    {
        $json = [
            'id' => 'expired_tx',
            'kind' => 'deposit',
            'status' => 'expired',
            'started_at' => '2025-01-20T10:00:00Z',
            'completed_at' => '2025-01-25T10:00:00Z',
            'message' => 'Transaction expired due to inactivity'
        ];

        $transaction = SEP24Transaction::fromJson($json);

        $this->assertEquals('expired', $transaction->getStatus());
        $this->assertNotNull($transaction->getCompletedAt());
    }

    public function testTransactionStateNoMarket(): void
    {
        $json = [
            'id' => 'no_market_tx',
            'kind' => 'withdrawal',
            'status' => 'no_market',
            'started_at' => '2025-01-20T10:00:00Z',
            'completed_at' => '2025-01-20T11:00:00Z',
            'message' => 'No market available for this asset pair'
        ];

        $transaction = SEP24Transaction::fromJson($json);

        $this->assertEquals('no_market', $transaction->getStatus());
    }

    public function testTransactionStateTooSmall(): void
    {
        $json = [
            'id' => 'too_small_tx',
            'kind' => 'deposit',
            'status' => 'too_small',
            'started_at' => '2025-01-20T10:00:00Z',
            'amount_in' => '0.50',
            'message' => 'Amount is below minimum threshold'
        ];

        $transaction = SEP24Transaction::fromJson($json);

        $this->assertEquals('too_small', $transaction->getStatus());
        $this->assertEquals('0.50', $transaction->getAmountIn());
    }

    public function testTransactionStateTooLarge(): void
    {
        $json = [
            'id' => 'too_large_tx',
            'kind' => 'withdrawal',
            'status' => 'too_large',
            'started_at' => '2025-01-20T10:00:00Z',
            'amount_in' => '1000000.00',
            'message' => 'Amount exceeds maximum threshold'
        ];

        $transaction = SEP24Transaction::fromJson($json);

        $this->assertEquals('too_large', $transaction->getStatus());
        $this->assertEquals('1000000.00', $transaction->getAmountIn());
    }

    public function testTransactionStateError(): void
    {
        $json = [
            'id' => 'error_tx',
            'kind' => 'deposit',
            'status' => 'error',
            'started_at' => '2025-01-20T10:00:00Z',
            'completed_at' => '2025-01-20T10:30:00Z',
            'message' => 'Transaction failed due to system error'
        ];

        $transaction = SEP24Transaction::fromJson($json);

        $this->assertEquals('error', $transaction->getStatus());
        $this->assertNotNull($transaction->getMessage());
        $this->assertNotNull($transaction->getCompletedAt());
    }

    public function testTransactionWithUserActionRequired(): void
    {
        $json = [
            'id' => 'user_action_tx',
            'kind' => 'withdrawal',
            'status' => 'pending_user',
            'started_at' => '2025-01-20T10:00:00Z',
            'user_action_required_by' => '2025-01-21T10:00:00Z',
            'message' => 'Please provide additional information',
            'more_info_url' => 'https://example.com/info'
        ];

        $transaction = SEP24Transaction::fromJson($json);

        $this->assertEquals('pending_user', $transaction->getStatus());
        $this->assertEquals('2025-01-21T10:00:00Z', $transaction->getUserActionRequiredBy());
        $this->assertEquals('Please provide additional information', $transaction->getMessage());
        $this->assertEquals('https://example.com/info', $transaction->getMoreInfoUrl());
    }

    public function testTransactionStateTransition(): void
    {
        $service = new InteractiveService($this->serviceAddress);

        $incompleteResponse = '{"transaction": {"id": "transition_tx", "kind": "deposit", "status": "incomplete", "started_at": "2025-01-20T10:00:00Z"}}';
        $pendingExternalResponse = '{"transaction": {"id": "transition_tx", "kind": "deposit", "status": "pending_external", "started_at": "2025-01-20T10:00:00Z", "status_eta": 3600}}';
        $completedResponse = '{"transaction": {"id": "transition_tx", "kind": "deposit", "status": "completed", "started_at": "2025-01-20T10:00:00Z", "completed_at": "2025-01-20T11:00:00Z"}}';

        $mock = new MockHandler([
            new Response(200, [], $incompleteResponse),
            new Response(200, [], $pendingExternalResponse),
            new Response(200, [], $completedResponse)
        ]);

        $service->setMockHandlerStack(HandlerStack::create($mock));

        $request = new SEP24TransactionRequest();
        $request->jwt = $this->jwtToken;
        $request->id = 'transition_tx';

        $response1 = $service->transaction($request);
        $this->assertEquals('incomplete', $response1->getTransaction()->getStatus());

        $response2 = $service->transaction($request);
        $this->assertEquals('pending_external', $response2->getTransaction()->getStatus());
        $this->assertEquals(3600, $response2->getTransaction()->getStatusEta());

        $response3 = $service->transaction($request);
        $this->assertEquals('completed', $response3->getTransaction()->getStatus());
        $this->assertNotNull($response3->getTransaction()->getCompletedAt());
    }

    public function testTransactionWithDepositAndWithdrawalExchangeFields(): void
    {
        $depositExchangeJson = [
            'id' => 'deposit_exchange_tx',
            'kind' => 'deposit-exchange',
            'status' => 'completed',
            'started_at' => '2025-01-20T10:00:00Z',
            'amount_in' => '500',
            'amount_in_asset' => 'iso4217:BRL',
            'amount_out' => '100',
            'amount_out_asset' => 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
            'amount_fee' => '2.50',
            'amount_fee_asset' => 'iso4217:BRL',
            'quote_id' => 'quote_exchange_123'
        ];

        $withdrawalExchangeJson = [
            'id' => 'withdrawal_exchange_tx',
            'kind' => 'withdrawal-exchange',
            'status' => 'completed',
            'started_at' => '2025-01-20T10:00:00Z',
            'amount_in' => '100',
            'amount_in_asset' => 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
            'amount_out' => '500',
            'amount_out_asset' => 'iso4217:BRL',
            'amount_fee' => '1.00',
            'amount_fee_asset' => 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
            'quote_id' => 'quote_exchange_456'
        ];

        $depositTx = SEP24Transaction::fromJson($depositExchangeJson);
        $withdrawalTx = SEP24Transaction::fromJson($withdrawalExchangeJson);

        $this->assertEquals('deposit-exchange', $depositTx->getKind());
        $this->assertEquals('iso4217:BRL', $depositTx->getAmountInAsset());
        $this->assertEquals('stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN', $depositTx->getAmountOutAsset());
        $this->assertEquals('quote_exchange_123', $depositTx->getQuoteId());

        $this->assertEquals('withdrawal-exchange', $withdrawalTx->getKind());
        $this->assertEquals('stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN', $withdrawalTx->getAmountInAsset());
        $this->assertEquals('iso4217:BRL', $withdrawalTx->getAmountOutAsset());
        $this->assertEquals('quote_exchange_456', $withdrawalTx->getQuoteId());
    }
}

