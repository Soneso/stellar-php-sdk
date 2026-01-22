<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Responses;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\Operations\OperationResponse;
use Soneso\StellarSDK\Responses\Operations\OperationType;
use Soneso\StellarSDK\Responses\Operations\OperationsPageResponse;
use Soneso\StellarSDK\Responses\Operations\AccountMergeOperationResponse;
use Soneso\StellarSDK\Responses\Operations\AllowTrustOperationResponse;
use Soneso\StellarSDK\Responses\Operations\BeginSponsoringFutureReservesOperationResponse;
use Soneso\StellarSDK\Responses\Operations\BumpSequenceOperationResponse;
use Soneso\StellarSDK\Responses\Operations\ChangeTrustOperationResponse;
use Soneso\StellarSDK\Responses\Operations\ClaimClaimableBalanceOperationResponse;
use Soneso\StellarSDK\Responses\Operations\ClawbackClaimableBalanceOperationResponse;
use Soneso\StellarSDK\Responses\Operations\ClawbackOperationResponse;
use Soneso\StellarSDK\Responses\Operations\CreateAccountOperationResponse;
use Soneso\StellarSDK\Responses\Operations\CreateClaimableBalanceOperationResponse;
use Soneso\StellarSDK\Responses\Operations\CreatePassiveSellOfferResponse;
use Soneso\StellarSDK\Responses\Operations\EndSponsoringFutureReservesOperationResponse;
use Soneso\StellarSDK\Responses\Operations\ExtendFootprintTTLOperationResponse;
use Soneso\StellarSDK\Responses\Operations\InflationOperationResponse;
use Soneso\StellarSDK\Responses\Operations\InvokeHostFunctionOperationResponse;
use Soneso\StellarSDK\Responses\Operations\LiquidityPoolDepositOperationResponse;
use Soneso\StellarSDK\Responses\Operations\LiquidityPoolWithdrawOperationResponse;
use Soneso\StellarSDK\Responses\Operations\ManageBuyOfferOperationResponse;
use Soneso\StellarSDK\Responses\Operations\ManageDataOperationResponse;
use Soneso\StellarSDK\Responses\Operations\ManageSellOfferOperationResponse;
use Soneso\StellarSDK\Responses\Operations\OperationLinksResponse;
use Soneso\StellarSDK\Responses\Operations\PathPaymentOperationResponse;
use Soneso\StellarSDK\Responses\Operations\PathPaymentStrictReceiveOperationResponse;
use Soneso\StellarSDK\Responses\Operations\PathPaymentStrictSendOperationResponse;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;
use Soneso\StellarSDK\Responses\Operations\RestoreFootprintOperationResponse;
use Soneso\StellarSDK\Responses\Operations\RevokeSponsorshipOperationResponse;
use Soneso\StellarSDK\Responses\Operations\SetOptionsOperationResponse;
use Soneso\StellarSDK\Responses\Operations\SetTrustlineFlagsOperationResponse;

/**
 * Unit tests for all Operation Response classes
 *
 * Tests JSON parsing and getter methods for all 37+ operation response classes.
 * Covers base OperationResponse functionality and all specific operation types.
 */
class OperationResponseTest extends TestCase
{
    // Helper method to create base operation JSON data
    private function getBaseOperationJson(
        int $typeI,
        string $type,
        string $sourceAccount = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7'
    ): array {
        return [
            'id' => '12884905985',
            'paging_token' => '12884905985',
            'transaction_successful' => true,
            'source_account' => $sourceAccount,
            'type' => $type,
            'type_i' => $typeI,
            'created_at' => '2019-02-28T17:00:00Z',
            'transaction_hash' => '5ebd5c0af4385500b53dd63b0ef5f6e8feef1a7e1c0a2d8c6c3f6e5e3c1a2b3c',
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/operations/12884905985'],
                'transaction' => ['href' => 'https://horizon-testnet.stellar.org/transactions/5ebd5c0af4'],
                'effects' => ['href' => 'https://horizon-testnet.stellar.org/operations/12884905985/effects'],
                'succeeds' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=desc&cursor=12884905985'],
                'precedes' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=asc&cursor=12884905985']
            ]
        ];
    }

    // Base OperationResponse Tests

    public function testBaseOperationResponseFields(): void
    {
        $json = $this->getBaseOperationJson(OperationType::PAYMENT, 'payment');
        $json['asset_type'] = 'native';
        $json['from'] = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
        $json['to'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';
        $json['amount'] = '100.0000000';

        $response = OperationResponse::fromJson($json);

        $this->assertEquals('12884905985', $response->getOperationId());
        $this->assertEquals('12884905985', $response->getPagingToken());
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $response->getSourceAccount());
        $this->assertEquals('payment', $response->getHumanReadableOperationType());
        $this->assertEquals(OperationType::PAYMENT, $response->getOperationType());
        $this->assertEquals('2019-02-28T17:00:00Z', $response->getCreatedAt());
        $this->assertEquals('5ebd5c0af4385500b53dd63b0ef5f6e8feef1a7e1c0a2d8c6c3f6e5e3c1a2b3c', $response->getTransactionHash());
        $this->assertTrue($response->isTransactionSuccessful());
        $this->assertInstanceOf(OperationLinksResponse::class, $response->getLinks());
        $this->assertNull($response->getSourceAccountMuxed());
        $this->assertNull($response->getSourceAccountMuxedId());
        $this->assertNull($response->getTransaction());
    }

    public function testBaseOperationResponseWithMuxedAccount(): void
    {
        $json = $this->getBaseOperationJson(OperationType::PAYMENT, 'payment');
        $json['source_account_muxed'] = 'MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ24';
        $json['source_account_muxed_id'] = '420';
        $json['asset_type'] = 'native';
        $json['from'] = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
        $json['to'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';
        $json['amount'] = '100.0000000';

        $response = OperationResponse::fromJson($json);

        $this->assertEquals('MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ24', $response->getSourceAccountMuxed());
        $this->assertEquals('420', $response->getSourceAccountMuxedId());
    }

    public function testOperationResponseThrowsExceptionForMissingTypeI(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No operation type_i found in json data');

        OperationResponse::fromJson(['id' => '123']);
    }

    public function testOperationResponseThrowsExceptionForUnknownType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown operation type: 999');

        OperationResponse::fromJson(['type_i' => 999]);
    }

    // CreateAccount Operation Tests

    public function testCreateAccountOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::CREATE_ACCOUNT, 'create_account');
        $json['starting_balance'] = '10000.0000000';
        $json['funder'] = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
        $json['account'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(CreateAccountOperationResponse::class, $response);
        $this->assertEquals('10000.0000000', $response->getStartingBalance());
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $response->getFunder());
        $this->assertEquals('GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6', $response->getAccount());
        $this->assertNull($response->getFunderMuxed());
        $this->assertNull($response->getFunderMuxedId());
    }

    public function testCreateAccountOperationWithMuxedFunder(): void
    {
        $json = $this->getBaseOperationJson(OperationType::CREATE_ACCOUNT, 'create_account');
        $json['starting_balance'] = '10000.0000000';
        $json['funder'] = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
        $json['funder_muxed'] = 'MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ24';
        $json['funder_muxed_id'] = '123';
        $json['account'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(CreateAccountOperationResponse::class, $response);
        $this->assertEquals('MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ24', $response->getFunderMuxed());
        $this->assertEquals('123', $response->getFunderMuxedId());
    }

    // Payment Operation Tests

    public function testPaymentOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::PAYMENT, 'payment');
        $json['asset_type'] = 'native';
        $json['from'] = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
        $json['to'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';
        $json['amount'] = '100.0000000';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(PaymentOperationResponse::class, $response);
        $this->assertEquals('100.0000000', $response->getAmount());
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $response->getFrom());
        $this->assertEquals('GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6', $response->getTo());
        $this->assertInstanceOf(Asset::class, $response->getAsset());
        $this->assertEquals(Asset::TYPE_NATIVE, $response->getAsset()->getType());
        $this->assertNull($response->getFromMuxed());
        $this->assertNull($response->getFromMuxedId());
        $this->assertNull($response->getToMuxed());
        $this->assertNull($response->getToMuxedId());
    }

    public function testPaymentOperationWithCreditAsset(): void
    {
        $json = $this->getBaseOperationJson(OperationType::PAYMENT, 'payment');
        $json['asset_type'] = 'credit_alphanum4';
        $json['asset_code'] = 'USD';
        $json['asset_issuer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['from'] = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
        $json['to'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';
        $json['amount'] = '500.0000000';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(PaymentOperationResponse::class, $response);
        $this->assertEquals('500.0000000', $response->getAmount());
        $asset = $response->getAsset();
        $this->assertEquals(Asset::TYPE_CREDIT_ALPHANUM_4, $asset->getType());
        $this->assertEquals('USD', $asset->getCode());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $asset->getIssuer());
    }

    public function testPaymentOperationWithMuxedAccounts(): void
    {
        $json = $this->getBaseOperationJson(OperationType::PAYMENT, 'payment');
        $json['asset_type'] = 'native';
        $json['from'] = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
        $json['from_muxed'] = 'MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ24';
        $json['from_muxed_id'] = '100';
        $json['to'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';
        $json['to_muxed'] = 'MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ25';
        $json['to_muxed_id'] = '200';
        $json['amount'] = '100.0000000';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(PaymentOperationResponse::class, $response);
        $this->assertEquals('MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ24', $response->getFromMuxed());
        $this->assertEquals('100', $response->getFromMuxedId());
        $this->assertEquals('MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ25', $response->getToMuxed());
        $this->assertEquals('200', $response->getToMuxedId());
    }

    // PathPayment Operations Tests

    public function testPathPaymentStrictReceiveOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::PATH_PAYMENT, 'path_payment_strict_receive');
        $json['amount'] = '10.0000000';
        $json['source_amount'] = '100.0000000';
        $json['source_max'] = '200.0000000';
        $json['from'] = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
        $json['to'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';
        $json['asset_type'] = 'credit_alphanum4';
        $json['asset_code'] = 'EUR';
        $json['asset_issuer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['source_asset_type'] = 'credit_alphanum4';
        $json['source_asset_code'] = 'USD';
        $json['source_asset_issuer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['path'] = [];

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(PathPaymentStrictReceiveOperationResponse::class, $response);
        $this->assertInstanceOf(PathPaymentOperationResponse::class, $response);
        $this->assertEquals('10.0000000', $response->getAmount());
        $this->assertEquals('100.0000000', $response->getSourceAmount());
        $this->assertEquals('200.0000000', $response->getSourceMax());
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $response->getFrom());
        $this->assertEquals('GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6', $response->getTo());
        $this->assertEquals('EUR', $response->getAsset()->getCode());
        $this->assertEquals('USD', $response->getSourceAsset()->getCode());
    }

    public function testPathPaymentStrictSendOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::PATH_PAYMENT_STRICT_SEND, 'path_payment_strict_send');
        $json['amount'] = '10.0000000';
        $json['source_amount'] = '100.0000000';
        $json['destination_min'] = '5.0000000';
        $json['from'] = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
        $json['to'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';
        $json['asset_type'] = 'native';
        $json['source_asset_type'] = 'native';
        $json['path'] = [
            [
                'asset_type' => 'credit_alphanum4',
                'asset_code' => 'USD',
                'asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
            ]
        ];

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(PathPaymentStrictSendOperationResponse::class, $response);
        $this->assertInstanceOf(PathPaymentOperationResponse::class, $response);
        $this->assertEquals('10.0000000', $response->getAmount());
        $this->assertEquals('100.0000000', $response->getSourceAmount());
        $this->assertEquals('5.0000000', $response->getDestinationMin());
        $path = $response->getPath();
        $this->assertCount(1, $path->toArray());
    }

    // ManageOffer Operations Tests

    public function testManageSellOfferOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::MANAGE_SELL_OFFER, 'manage_sell_offer');
        $json['offer_id'] = '12345';
        $json['amount'] = '100.0000000';
        $json['price'] = '2.5000000';
        $json['price_r'] = ['n' => 5, 'd' => 2];
        $json['buying_asset_type'] = 'native';
        $json['selling_asset_type'] = 'credit_alphanum4';
        $json['selling_asset_code'] = 'USD';
        $json['selling_asset_issuer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(ManageSellOfferOperationResponse::class, $response);
        $this->assertEquals('12345', $response->getOfferId());
        $this->assertEquals('100.0000000', $response->getAmount());
        $this->assertEquals('2.5000000', $response->getPrice());
        $this->assertEquals(5, $response->getPriceR()->getN());
        $this->assertEquals(2, $response->getPriceR()->getD());
        $this->assertEquals(Asset::TYPE_NATIVE, $response->getBuyingAsset()->getType());
        $this->assertEquals('USD', $response->getSellingAsset()->getCode());
    }

    public function testManageBuyOfferOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::MANAGE_BUY_OFFER, 'manage_buy_offer');
        $json['offer_id'] = '67890';
        $json['amount'] = '50.0000000';
        $json['price'] = '1.5000000';
        $json['price_r'] = ['n' => 3, 'd' => 2];
        $json['buying_asset_type'] = 'credit_alphanum4';
        $json['buying_asset_code'] = 'BTC';
        $json['buying_asset_issuer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['selling_asset_type'] = 'native';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(ManageBuyOfferOperationResponse::class, $response);
        $this->assertEquals('67890', $response->getOfferId());
        $this->assertEquals('50.0000000', $response->getAmount());
        $this->assertEquals('1.5000000', $response->getPrice());
        $this->assertEquals('BTC', $response->getBuyingAsset()->getCode());
        $this->assertEquals(Asset::TYPE_NATIVE, $response->getSellingAsset()->getType());
    }

    public function testCreatePassiveSellOfferOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::CREATE_PASSIVE_SELL_OFFER, 'create_passive_sell_offer');
        $json['offer_id'] = '0';
        $json['amount'] = '200.0000000';
        $json['price'] = '3.0000000';
        $json['price_r'] = ['n' => 3, 'd' => 1];
        $json['buying_asset_type'] = 'native';
        $json['selling_asset_type'] = 'credit_alphanum4';
        $json['selling_asset_code'] = 'ETH';
        $json['selling_asset_issuer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(CreatePassiveSellOfferResponse::class, $response);
        $this->assertEquals('200.0000000', $response->getAmount());
        $this->assertEquals('3.0000000', $response->getPrice());
        $this->assertEquals(3, $response->getPriceR()->getN());
        $this->assertEquals(1, $response->getPriceR()->getD());
        $this->assertEquals(Asset::TYPE_NATIVE, $response->getBuyingAsset()->getType());
        $this->assertEquals('ETH', $response->getSellingAsset()->getCode());
    }

    // SetOptions Operation Tests

    public function testSetOptionsOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::SET_OPTIONS, 'set_options');
        $json['low_threshold'] = 1;
        $json['med_threshold'] = 2;
        $json['high_threshold'] = 3;
        $json['inflation_dest'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';
        $json['home_domain'] = 'stellar.org';
        $json['signer_key'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';
        $json['signer_weight'] = 5;
        $json['master_key_weight'] = 10;
        $json['set_flags'] = [1, 2];
        $json['set_flags_s'] = ['auth_required', 'auth_revocable'];
        $json['clear_flags'] = [4];
        $json['clear_flags_s'] = ['auth_immutable'];

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(SetOptionsOperationResponse::class, $response);
        $this->assertEquals(1, $response->getLowThreshold());
        $this->assertEquals(2, $response->getMedThreshold());
        $this->assertEquals(3, $response->getHighThreshold());
        $this->assertEquals('GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6', $response->getInflationDestination());
        $this->assertEquals('stellar.org', $response->getHomeDomain());
        $this->assertEquals('GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6', $response->getSignerKey());
        $this->assertEquals(5, $response->getSignerWeight());
        $this->assertEquals(10, $response->getMasterKeyWeight());
        $this->assertCount(2, $response->getSetFlags());
        $this->assertCount(2, $response->getSetFlagsS());
        $this->assertCount(1, $response->getClearFlags());
        $this->assertCount(1, $response->getClearFlagsS());
    }

    public function testSetOptionsOperationWithNullFields(): void
    {
        $json = $this->getBaseOperationJson(OperationType::SET_OPTIONS, 'set_options');

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(SetOptionsOperationResponse::class, $response);
        $this->assertNull($response->getLowThreshold());
        $this->assertNull($response->getMedThreshold());
        $this->assertNull($response->getHighThreshold());
        $this->assertNull($response->getInflationDestination());
        $this->assertNull($response->getHomeDomain());
        $this->assertNull($response->getSignerKey());
        $this->assertNull($response->getSignerWeight());
        $this->assertNull($response->getMasterKeyWeight());
        $this->assertNull($response->getSetFlags());
        $this->assertNull($response->getSetFlagsS());
        $this->assertNull($response->getClearFlags());
        $this->assertNull($response->getClearFlagsS());
    }

    // ChangeTrust Operation Tests

    public function testChangeTrustOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::CHANGE_TRUST, 'change_trust');
        $json['trustor'] = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
        $json['trustee'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['asset_type'] = 'credit_alphanum4';
        $json['asset_code'] = 'USD';
        $json['asset_issuer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['limit'] = '1000.0000000';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(ChangeTrustOperationResponse::class, $response);
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $response->getTrustor());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getTrustee());
        $this->assertEquals('credit_alphanum4', $response->getAssetType());
        $this->assertEquals('USD', $response->getAssetCode());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getAssetIssuer());
        $this->assertEquals('1000.0000000', $response->getLimit());
        $this->assertNull($response->getLiquidityPoolId());
    }

    public function testChangeTrustOperationForLiquidityPool(): void
    {
        $json = $this->getBaseOperationJson(OperationType::CHANGE_TRUST, 'change_trust');
        $json['trustor'] = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
        $json['asset_type'] = 'liquidity_pool_shares';
        $json['liquidity_pool_id'] = 'dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7';
        $json['limit'] = '500.0000000';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(ChangeTrustOperationResponse::class, $response);
        $this->assertEquals('liquidity_pool_shares', $response->getAssetType());
        $this->assertEquals('dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7', $response->getLiquidityPoolId());
        $this->assertNull($response->getTrustee());
    }

    public function testChangeTrustOperationWithMuxedAccount(): void
    {
        $json = $this->getBaseOperationJson(OperationType::CHANGE_TRUST, 'change_trust');
        $json['trustor'] = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
        $json['trustor_muxed'] = 'MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ24';
        $json['trustor_muxed_id'] = '777';
        $json['trustee'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['asset_type'] = 'credit_alphanum4';
        $json['asset_code'] = 'USD';
        $json['asset_issuer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['limit'] = '1000.0000000';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(ChangeTrustOperationResponse::class, $response);
        $this->assertEquals('MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ24', $response->getTrustorMuxed());
        $this->assertEquals('777', $response->getTrustorMuxedId());
    }

    // AllowTrust Operation Tests

    public function testAllowTrustOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::ALLOW_TRUST, 'allow_trust');
        $json['trustee'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['trustor'] = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
        $json['asset_type'] = 'credit_alphanum4';
        $json['asset_code'] = 'USD';
        $json['asset_issuer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['authorize'] = true;
        $json['authorize_to_maintain_liabilities'] = false;

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(AllowTrustOperationResponse::class, $response);
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getTrustee());
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $response->getTrustor());
        $this->assertEquals('credit_alphanum4', $response->getAssetType());
        $this->assertEquals('USD', $response->getAssetCode());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getAssetIssuer());
        $this->assertTrue($response->isAuthorize());
        $this->assertFalse($response->getAuthorizeToMaintainLiabilities());
    }

    public function testAllowTrustOperationWithMuxedAccounts(): void
    {
        $json = $this->getBaseOperationJson(OperationType::ALLOW_TRUST, 'allow_trust');
        $json['trustee'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['trustee_muxed'] = 'MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ24';
        $json['trustee_muxed_id'] = '999';
        $json['trustor'] = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
        $json['asset_type'] = 'credit_alphanum4';
        $json['asset_code'] = 'USD';
        $json['asset_issuer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['authorize'] = true;
        $json['authorize_to_maintain_liabilities'] = false;

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(AllowTrustOperationResponse::class, $response);
        $this->assertEquals('MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ24', $response->getTrusteeMuxed());
        $this->assertEquals('999', $response->getTrusteeMuxedId());
    }

    // AccountMerge Operation Tests

    public function testAccountMergeOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::ACCOUNT_MERGE, 'account_merge');
        $json['account'] = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
        $json['into'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(AccountMergeOperationResponse::class, $response);
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $response->getAccount());
        $this->assertEquals('GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6', $response->getInto());
        $this->assertNull($response->getAccountMuxed());
        $this->assertNull($response->getAccountMuxedId());
        $this->assertNull($response->getIntoMuxed());
        $this->assertNull($response->getIntoMuxedId());
    }

    public function testAccountMergeOperationWithMuxedAccounts(): void
    {
        $json = $this->getBaseOperationJson(OperationType::ACCOUNT_MERGE, 'account_merge');
        $json['account'] = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
        $json['account_muxed'] = 'MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ24';
        $json['account_muxed_id'] = '111';
        $json['into'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';
        $json['into_muxed'] = 'MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ25';
        $json['into_muxed_id'] = '222';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(AccountMergeOperationResponse::class, $response);
        $this->assertEquals('MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ24', $response->getAccountMuxed());
        $this->assertEquals('111', $response->getAccountMuxedId());
        $this->assertEquals('MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ25', $response->getIntoMuxed());
        $this->assertEquals('222', $response->getIntoMuxedId());
    }

    // Inflation Operation Tests

    public function testInflationOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::INFLATION, 'inflation');

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(InflationOperationResponse::class, $response);
        $this->assertEquals('inflation', $response->getHumanReadableOperationType());
        $this->assertEquals(OperationType::INFLATION, $response->getOperationType());
    }

    // ManageData Operation Tests

    public function testManageDataOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::MANAGE_DATA, 'manage_data');
        $json['name'] = 'config.test';
        $json['value'] = 'dGVzdHZhbHVl'; // base64 encoded 'testvalue'

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(ManageDataOperationResponse::class, $response);
        $this->assertEquals('config.test', $response->getName());
        $this->assertEquals('dGVzdHZhbHVl', $response->getValue());
    }

    public function testManageDataOperationDeleteEntry(): void
    {
        $json = $this->getBaseOperationJson(OperationType::MANAGE_DATA, 'manage_data');
        $json['name'] = 'config.test';
        $json['value'] = ''; // Empty string for deleted entries

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(ManageDataOperationResponse::class, $response);
        $this->assertEquals('config.test', $response->getName());
        $this->assertEquals('', $response->getValue());
    }

    // BumpSequence Operation Tests

    public function testBumpSequenceOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::BUMP_SEQUENCE, 'bump_sequence');
        $json['bump_to'] = '123456789';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(BumpSequenceOperationResponse::class, $response);
        $this->assertEquals('123456789', $response->getBumpTo());
    }

    // CreateClaimableBalance Operation Tests

    public function testCreateClaimableBalanceOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::CREATE_CLAIMABLE_BALANCE, 'create_claimable_balance');
        $json['asset'] = 'native';
        $json['amount'] = '100.0000000';
        $json['sponsor'] = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
        $json['claimants'] = [
            [
                'destination' => 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6',
                'predicate' => [
                    'unconditional' => true
                ]
            ]
        ];

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(CreateClaimableBalanceOperationResponse::class, $response);
        $this->assertInstanceOf(Asset::class, $response->getAsset());
        $this->assertEquals(Asset::TYPE_NATIVE, $response->getAsset()->getType());
        $this->assertEquals('100.0000000', $response->getAmount());
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $response->getSponsor());
        $this->assertCount(1, $response->getClaimants()->toArray());
    }

    // ClaimClaimableBalance Operation Tests

    public function testClaimClaimableBalanceOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::CLAIM_CLAIMABLE_BALANCE, 'claim_claimable_balance');
        $json['balance_id'] = '00000000da0d57da7d4850e7fc10d2a9d0ebc731f7afb40574c03395b17d49149b91f5be';
        $json['claimant'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(ClaimClaimableBalanceOperationResponse::class, $response);
        $this->assertEquals('00000000da0d57da7d4850e7fc10d2a9d0ebc731f7afb40574c03395b17d49149b91f5be', $response->getBalanceId());
        $this->assertEquals('GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6', $response->getClaimant());
        $this->assertNull($response->getClaimantMuxed());
        $this->assertNull($response->getClaimantMuxedId());
    }

    public function testClaimClaimableBalanceOperationWithMuxedClaimant(): void
    {
        $json = $this->getBaseOperationJson(OperationType::CLAIM_CLAIMABLE_BALANCE, 'claim_claimable_balance');
        $json['balance_id'] = '00000000da0d57da7d4850e7fc10d2a9d0ebc731f7afb40574c03395b17d49149b91f5be';
        $json['claimant'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';
        $json['claimant_muxed'] = 'MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ24';
        $json['claimant_muxed_id'] = '555';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(ClaimClaimableBalanceOperationResponse::class, $response);
        $this->assertEquals('MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ24', $response->getClaimantMuxed());
        $this->assertEquals('555', $response->getClaimantMuxedId());
    }

    // BeginSponsoringFutureReserves Operation Tests

    public function testBeginSponsoringFutureReservesOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::BEGIN_SPONSORING_FUTURE_RESERVES, 'begin_sponsoring_future_reserves');
        $json['sponsored_id'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(BeginSponsoringFutureReservesOperationResponse::class, $response);
        $this->assertEquals('GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6', $response->getSponsoredId());
    }

    // EndSponsoringFutureReserves Operation Tests

    public function testEndSponsoringFutureReservesOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::END_SPONSORING_FUTURE_RESERVES, 'end_sponsoring_future_reserves');
        $json['begin_sponsor'] = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(EndSponsoringFutureReservesOperationResponse::class, $response);
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $response->getBeginSponsor());
        $this->assertNull($response->getBeginSponsorMuxed());
        $this->assertNull($response->getBeginSponsorMuxedId());
    }

    // RevokeSponsorship Operation Tests

    public function testRevokeSponsorshipOperationForAccount(): void
    {
        $json = $this->getBaseOperationJson(OperationType::REVOKE_SPONSORSHIP, 'revoke_sponsorship');
        $json['account_id'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(RevokeSponsorshipOperationResponse::class, $response);
        $this->assertEquals('GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6', $response->getAccountId());
        $this->assertNull($response->getClaimableBalanceId());
        $this->assertNull($response->getDataAccountId());
        $this->assertNull($response->getOfferId());
        $this->assertNull($response->getTrustlineAccountId());
        $this->assertNull($response->getSignerAccountId());
    }

    public function testRevokeSponsorshipOperationForClaimableBalance(): void
    {
        $json = $this->getBaseOperationJson(OperationType::REVOKE_SPONSORSHIP, 'revoke_sponsorship');
        $json['claimable_balance_id'] = '00000000da0d57da7d4850e7fc10d2a9d0ebc731f7afb40574c03395b17d49149b91f5be';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(RevokeSponsorshipOperationResponse::class, $response);
        $this->assertEquals('00000000da0d57da7d4850e7fc10d2a9d0ebc731f7afb40574c03395b17d49149b91f5be', $response->getClaimableBalanceId());
        $this->assertNull($response->getAccountId());
    }

    public function testRevokeSponsorshipOperationForDataEntry(): void
    {
        $json = $this->getBaseOperationJson(OperationType::REVOKE_SPONSORSHIP, 'revoke_sponsorship');
        $json['data_account_id'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';
        $json['data_name'] = 'config.test';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(RevokeSponsorshipOperationResponse::class, $response);
        $this->assertEquals('GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6', $response->getDataAccountId());
        $this->assertEquals('config.test', $response->getDataName());
    }

    public function testRevokeSponsorshipOperationForOffer(): void
    {
        $json = $this->getBaseOperationJson(OperationType::REVOKE_SPONSORSHIP, 'revoke_sponsorship');
        $json['offer_id'] = '12345';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(RevokeSponsorshipOperationResponse::class, $response);
        $this->assertEquals('12345', $response->getOfferId());
    }

    public function testRevokeSponsorshipOperationForTrustline(): void
    {
        $json = $this->getBaseOperationJson(OperationType::REVOKE_SPONSORSHIP, 'revoke_sponsorship');
        $json['trustline_account_id'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';
        $json['trustline_asset'] = 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(RevokeSponsorshipOperationResponse::class, $response);
        $this->assertEquals('GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6', $response->getTrustlineAccountId());
        $this->assertEquals('USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getTrustlineAsset());
    }

    public function testRevokeSponsorshipOperationForSigner(): void
    {
        $json = $this->getBaseOperationJson(OperationType::REVOKE_SPONSORSHIP, 'revoke_sponsorship');
        $json['signer_account_id'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';
        $json['signer_key'] = 'GCDENOCHA6TQL6DFC4FS54HIH7RP7XR7VZCQZFANMGLT2WXJ7D7KGV2P';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(RevokeSponsorshipOperationResponse::class, $response);
        $this->assertEquals('GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6', $response->getSignerAccountId());
        $this->assertEquals('GCDENOCHA6TQL6DFC4FS54HIH7RP7XR7VZCQZFANMGLT2WXJ7D7KGV2P', $response->getSignerKey());
    }

    // Clawback Operation Tests

    public function testClawbackOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::CLAWBACK, 'clawback');
        $json['asset_type'] = 'credit_alphanum4';
        $json['asset_code'] = 'USD';
        $json['asset_issuer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['from'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';
        $json['amount'] = '50.0000000';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(ClawbackOperationResponse::class, $response);
        $asset = $response->getAsset();
        $this->assertEquals(Asset::TYPE_CREDIT_ALPHANUM_4, $asset->getType());
        $this->assertEquals('USD', $asset->getCode());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $asset->getIssuer());
        $this->assertEquals('GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6', $response->getFrom());
        $this->assertEquals('50.0000000', $response->getAmount());
        $this->assertNull($response->getFromMuxed());
        $this->assertNull($response->getFromMuxedId());
    }

    public function testClawbackOperationWithMuxedAccount(): void
    {
        $json = $this->getBaseOperationJson(OperationType::CLAWBACK, 'clawback');
        $json['asset_type'] = 'credit_alphanum4';
        $json['asset_code'] = 'USD';
        $json['asset_issuer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['from'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';
        $json['from_muxed'] = 'MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ24';
        $json['from_muxed_id'] = '333';
        $json['amount'] = '50.0000000';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(ClawbackOperationResponse::class, $response);
        $this->assertEquals('MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ24', $response->getFromMuxed());
        $this->assertEquals('333', $response->getFromMuxedId());
    }

    // ClawbackClaimableBalance Operation Tests

    public function testClawbackClaimableBalanceOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::CLAWBACK_CLAIMABLE_BALANCE, 'clawback_claimable_balance');
        $json['balance_id'] = '00000000da0d57da7d4850e7fc10d2a9d0ebc731f7afb40574c03395b17d49149b91f5be';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(ClawbackClaimableBalanceOperationResponse::class, $response);
        $this->assertEquals('00000000da0d57da7d4850e7fc10d2a9d0ebc731f7afb40574c03395b17d49149b91f5be', $response->getBalanceId());
    }

    // SetTrustlineFlags Operation Tests

    public function testSetTrustlineFlagsOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::SET_TRUSTLINE_FLAGS, 'set_trustline_flags');
        $json['trustor'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';
        $json['asset_type'] = 'credit_alphanum4';
        $json['asset_code'] = 'USD';
        $json['asset_issuer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['set_flags'] = [1];
        $json['set_flags_s'] = ['authorized'];
        $json['clear_flags'] = [2];
        $json['clear_flags_s'] = ['authorized_to_maintain_liabilities'];

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(SetTrustlineFlagsOperationResponse::class, $response);
        $this->assertEquals('GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6', $response->getTrustor());
        $this->assertEquals('credit_alphanum4', $response->getAssetType());
        $this->assertEquals('USD', $response->getAssetCode());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getAssetIssuer());
        $this->assertCount(1, $response->getSetFlags());
        $this->assertCount(1, $response->getSetFlagsS());
        $this->assertCount(1, $response->getClearFlags());
        $this->assertCount(1, $response->getClearFlagsS());
    }

    // LiquidityPool Operations Tests

    public function testLiquidityPoolDepositOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::LIQUIDITY_POOL_DEPOSIT, 'liquidity_pool_deposit');
        $json['liquidity_pool_id'] = 'dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7';
        $json['reserves_max'] = [
            ['asset' => 'native', 'amount' => '1000.0000000'],
            ['asset' => 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', 'amount' => '2000.0000000']
        ];
        $json['min_price'] = '0.5';
        $json['min_price_r'] = ['n' => 1, 'd' => 2];
        $json['max_price'] = '2.0';
        $json['max_price_r'] = ['n' => 2, 'd' => 1];
        $json['reserves_deposited'] = [
            ['asset' => 'native', 'amount' => '500.0000000'],
            ['asset' => 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', 'amount' => '1000.0000000']
        ];
        $json['shares_received'] = '750.0000000';

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(LiquidityPoolDepositOperationResponse::class, $response);
        $this->assertEquals('dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7', $response->getLiquidityPoolId());
        $this->assertCount(2, $response->getReservesMax()->toArray());
        $this->assertEquals('0.5', $response->getMinPrice());
        $this->assertEquals(1, $response->getMinPriceR()->getN());
        $this->assertEquals(2, $response->getMinPriceR()->getD());
        $this->assertEquals('2.0', $response->getMaxPrice());
        $this->assertEquals(2, $response->getMaxPriceR()->getN());
        $this->assertEquals(1, $response->getMaxPriceR()->getD());
        $this->assertCount(2, $response->getReservesDeposited()->toArray());
        $this->assertEquals('750.0000000', $response->getSharesReceived());
    }

    public function testLiquidityPoolWithdrawOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::LIQUIDITY_POOL_WITHDRAW, 'liquidity_pool_withdraw');
        $json['liquidity_pool_id'] = 'dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7';
        $json['reserves_min'] = [
            ['asset' => 'native', 'amount' => '100.0000000'],
            ['asset' => 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', 'amount' => '200.0000000']
        ];
        $json['shares'] = '150.0000000';
        $json['reserves_received'] = [
            ['asset' => 'native', 'amount' => '150.0000000'],
            ['asset' => 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', 'amount' => '300.0000000']
        ];

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(LiquidityPoolWithdrawOperationResponse::class, $response);
        $this->assertEquals('dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7', $response->getLiquidityPoolId());
        $this->assertCount(2, $response->getReservesMin()->toArray());
        $this->assertEquals('150.0000000', $response->getShares());
        $this->assertCount(2, $response->getReservesReceived()->toArray());
    }

    // Soroban Operations Tests

    public function testInvokeHostFunctionOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::INVOKE_HOST_FUNCTION, 'invoke_host_function');
        $json['function'] = 'InvokeContract';
        $json['parameters'] = [
            ['value' => 'AAAAEgAAAAEAAAAAAAAAAAAAAAD8WgBmO1xeEZTacfPLrr7LVBRucl9TYh+MJB3M0g==', 'type' => 'Address']
        ];
        $json['address'] = 'CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC';
        $json['salt'] = '7bb1cd417f3c6c87c2f87e0e9f';
        $json['asset_balance_changes'] = [];

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(InvokeHostFunctionOperationResponse::class, $response);
        $this->assertEquals('InvokeContract', $response->getFunction());
        $this->assertCount(1, $response->getParameters()->toArray());
        $this->assertEquals('CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC', $response->getAddress());
        $this->assertEquals('7bb1cd417f3c6c87c2f87e0e9f', $response->getSalt());
    }

    public function testInvokeHostFunctionOperationWithAssetBalanceChanges(): void
    {
        $json = $this->getBaseOperationJson(OperationType::INVOKE_HOST_FUNCTION, 'invoke_host_function');
        $json['function'] = 'InvokeContract';
        $json['parameters'] = [];
        $json['address'] = 'CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC';
        $json['salt'] = '7bb1cd417f3c6c87c2f87e0e9f';
        $json['asset_balance_changes'] = [
            [
                'asset_type' => 'native',
                'asset_code' => '',
                'asset_issuer' => '',
                'type' => 'transfer',
                'from' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
                'to' => 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6',
                'amount' => '10.0000000'
            ]
        ];

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(InvokeHostFunctionOperationResponse::class, $response);
        $this->assertCount(1, $response->getAssetBalanceChanges()->toArray());
    }

    public function testExtendFootprintTTLOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::EXTEND_FOOTPRINT_TTL, 'extend_footprint_ttl');
        $json['extend_to'] = 1000;

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(ExtendFootprintTTLOperationResponse::class, $response);
        $this->assertEquals(1000, $response->getExtendTo());
    }

    public function testRestoreFootprintOperationFromJson(): void
    {
        $json = $this->getBaseOperationJson(OperationType::RESTORE_FOOTPRINT, 'restore_footprint');

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(RestoreFootprintOperationResponse::class, $response);
        $this->assertEquals('restore_footprint', $response->getHumanReadableOperationType());
    }

    // Helper Classes Tests

    public function testOperationLinksResponse(): void
    {
        $json = $this->getBaseOperationJson(OperationType::PAYMENT, 'payment');
        $json['asset_type'] = 'native';
        $json['from'] = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
        $json['to'] = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';
        $json['amount'] = '100.0000000';

        $response = OperationResponse::fromJson($json);

        $links = $response->getLinks();
        $this->assertNotNull($links);
        $this->assertInstanceOf(OperationLinksResponse::class, $links);
        $this->assertNotNull($links->getSelf());
        $this->assertNotNull($links->getEffects());
        $this->assertNotNull($links->getTransaction());
        $this->assertNotNull($links->getPrecedes());
        $this->assertNotNull($links->getSucceeds());
    }

    public function testParameterResponse(): void
    {
        $json = $this->getBaseOperationJson(OperationType::INVOKE_HOST_FUNCTION, 'invoke_host_function');
        $json['function'] = 'InvokeContract';
        $json['parameters'] = [
            ['value' => 'AAAAEgAAAAEAAAAAAAAAAAAAAAD8WgBmO1xeEZTacfPLrr7LVBRucl9TYh+MJB3M0g==', 'type' => 'Address'],
            ['value' => 'AAAAEAAAAAA=', 'type' => 'U32']
        ];
        $json['address'] = 'CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC';
        $json['salt'] = '7bb1cd417f3c6c87c2f87e0e9f';
        $json['asset_balance_changes'] = [];

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(InvokeHostFunctionOperationResponse::class, $response);
        $parameters = $response->getParameters();
        $this->assertCount(2, $parameters->toArray());

        $parametersArray = $parameters->toArray();
        $this->assertEquals('Address', $parametersArray[0]->getType());
        $this->assertEquals('AAAAEgAAAAEAAAAAAAAAAAAAAAD8WgBmO1xeEZTacfPLrr7LVBRucl9TYh+MJB3M0g==', $parametersArray[0]->getValue());
        $this->assertEquals('U32', $parametersArray[1]->getType());
        $this->assertEquals('AAAAEAAAAAA=', $parametersArray[1]->getValue());
    }

    public function testAssetBalanceChangeResponse(): void
    {
        $json = $this->getBaseOperationJson(OperationType::INVOKE_HOST_FUNCTION, 'invoke_host_function');
        $json['function'] = 'InvokeContract';
        $json['parameters'] = [];
        $json['address'] = 'CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC';
        $json['salt'] = '7bb1cd417f3c6c87c2f87e0e9f';
        $json['asset_balance_changes'] = [
            [
                'asset_type' => 'credit_alphanum4',
                'asset_code' => 'USD',
                'asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX',
                'type' => 'transfer',
                'from' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
                'to' => 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6',
                'amount' => '10.0000000',
                'destination_muxed_id' => '999'
            ]
        ];

        $response = OperationResponse::fromJson($json);

        $this->assertInstanceOf(InvokeHostFunctionOperationResponse::class, $response);
        $balanceChanges = $response->getAssetBalanceChanges();
        $this->assertCount(1, $balanceChanges->toArray());

        $balanceChangesArray = $balanceChanges->toArray();
        $change = $balanceChangesArray[0];
        $this->assertEquals('credit_alphanum4', $change->getAssetType());
        $this->assertEquals('USD', $change->getAssetCode());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $change->getAssetIssuer());
        $this->assertEquals('transfer', $change->getType());
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $change->getFrom());
        $this->assertEquals('GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6', $change->getTo());
        $this->assertEquals('10.0000000', $change->getAmount());
        $this->assertEquals('999', $change->getDestinationMuxedId());
    }

    // OperationsPageResponse Tests

    public function testOperationsPageResponseFromJson(): void
    {
        $json = [
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/operations?cursor=&limit=2&order=desc'],
                'next' => ['href' => 'https://horizon-testnet.stellar.org/operations?cursor=12884905985&limit=2&order=desc'],
                'prev' => ['href' => 'https://horizon-testnet.stellar.org/operations?cursor=12884905984&limit=2&order=asc']
            ],
            '_embedded' => [
                'records' => [
                    $this->getBaseOperationJson(OperationType::CREATE_ACCOUNT, 'create_account') + [
                        'starting_balance' => '10000.0000000',
                        'funder' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
                        'account' => 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6'
                    ],
                    $this->getBaseOperationJson(OperationType::PAYMENT, 'payment') + [
                        'asset_type' => 'native',
                        'from' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
                        'to' => 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6',
                        'amount' => '100.0000000'
                    ]
                ]
            ]
        ];

        $response = OperationsPageResponse::fromJson($json);

        $this->assertInstanceOf(OperationsPageResponse::class, $response);
        $operations = $response->getOperations();
        $this->assertCount(2, $operations->toArray());

        $operationsArray = $operations->toArray();
        $this->assertInstanceOf(CreateAccountOperationResponse::class, $operationsArray[0]);
        $this->assertInstanceOf(PaymentOperationResponse::class, $operationsArray[1]);
    }

    public function testOperationsPageResponseEmptyRecords(): void
    {
        $json = [
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/operations?cursor=&limit=10&order=desc'],
                'next' => ['href' => 'https://horizon-testnet.stellar.org/operations?cursor=12884905985&limit=10&order=desc'],
                'prev' => ['href' => 'https://horizon-testnet.stellar.org/operations?cursor=12884905984&limit=10&order=asc']
            ],
            '_embedded' => [
                'records' => []
            ]
        ];

        $response = OperationsPageResponse::fromJson($json);

        $this->assertInstanceOf(OperationsPageResponse::class, $response);
        $operations = $response->getOperations();
        $this->assertCount(0, $operations->toArray());
    }
}
