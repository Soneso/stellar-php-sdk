<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Responses;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Responses\Effects\EffectResponse;
use Soneso\StellarSDK\Responses\Effects\EffectType;
use Soneso\StellarSDK\Responses\Effects\AccountCreatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\AccountCreditedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\AccountDebitedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\AccountFlagsUpdatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\AccountHomeDomainUpdatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\AccountInflationDestinationUpdatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\AccountRemovedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\AccountSponsorshipCreatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\AccountSponsorshipRemovedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\AccountSponsorshipUpdatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\AccountThresholdsUpdatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\ClaimableBalanceClaimantCreatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\ClaimableBalanceClaimedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\ClaimableBalanceClawedBackEffectResponse;
use Soneso\StellarSDK\Responses\Effects\ClaimableBalanceCreatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\ClaimableBalanceSponsorshipCreatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\ClaimableBalanceSponsorshipRemovedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\ClaimableBalanceSponsorshipUpdatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\ContractCreditedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\ContractDebitedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\DataCreatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\DataRemovedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\DataSponsorshipCreatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\DataSponsorshipRemovedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\DataSponsorshipUpdatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\DataUpdatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\LiquidityPoolCreatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\LiquidityPoolDepositedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\LiquidityPoolRemovedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\LiquidityPoolRevokedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\LiquidityPoolTradeEffectResponse;
use Soneso\StellarSDK\Responses\Effects\LiquidityPoolWithdrewEffectResponse;
use Soneso\StellarSDK\Responses\Effects\OfferCreatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\OfferRemovedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\OfferUpdatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\SequenceBumpedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\SignerCreatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\SignerRemovedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\SignerSponsorshipCreatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\SignerSponsorshipRemovedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\SignerSponsorshipUpdatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\SignerUpdatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\TradeEffectResponse;
use Soneso\StellarSDK\Responses\Effects\TrustlineAuthorizedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\TrustlineAuthorizedToMaintainLiabilitiesEffectResponse;
use Soneso\StellarSDK\Responses\Effects\TrustlineCreatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\TrustlineDeauthorizedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\TrustlineFlagsUpdatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\TrustlineRemovedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\TrustlineSponsorshipCreatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\TrustlineSponsorshipRemovedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\TrustlineSponsorshipUpdatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\TrustlineUpdatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\EffectLinksResponse;
use Soneso\StellarSDK\Responses\Effects\EffectsResponse;
use Soneso\StellarSDK\Responses\Effects\EffectsPageResponse;
use Soneso\StellarSDK\Asset;

/**
 * Unit tests for all Effect Response classes
 *
 * Tests JSON parsing and getter methods for all 61 effect response classes.
 * Covers base EffectResponse functionality and all specific effect types.
 */
class EffectResponseTest extends TestCase
{
    // Helper method to create base effect JSON data
    private function getBaseEffectJson(int $typeI, string $type, string $account = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7'): array
    {
        return [
            'id' => '0000000012884905985-0000000001',
            'paging_token' => '12884905985-1',
            'account' => $account,
            'type' => $type,
            'type_i' => $typeI,
            'created_at' => '2019-02-28T17:00:00Z',
            '_links' => [
                'operation' => ['href' => 'https://horizon.stellar.org/operations/12884905985'],
                'succeeds' => ['href' => 'https://horizon.stellar.org/effects?cursor=12884905985-1&order=desc'],
                'precedes' => ['href' => 'https://horizon.stellar.org/effects?cursor=12884905985-1&order=asc']
            ]
        ];
    }

    // Account Effects Tests

    public function testAccountCreatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::ACCOUNT_CREATED, 'account_created');
        $json['starting_balance'] = '10000.0000000';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(AccountCreatedEffectResponse::class, $response);
        $this->assertEquals('0000000012884905985-0000000001', $response->getEffectId());
        $this->assertEquals('12884905985-1', $response->getPagingToken());
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $response->getAccount());
        $this->assertEquals('account_created', $response->getHumanReadableEffectType());
        $this->assertEquals(EffectType::ACCOUNT_CREATED, $response->getEffectType());
        $this->assertEquals('2019-02-28T17:00:00Z', $response->getCreatedAt());
        $this->assertEquals('10000.0000000', $response->getStartingBalance());
        $this->assertInstanceOf(EffectLinksResponse::class, $response->getLinks());
        $this->assertNull($response->getAccountMuxed());
        $this->assertNull($response->getAccountMuxedId());
    }

    public function testAccountCreatedEffectWithMuxedAccount(): void
    {
        $json = $this->getBaseEffectJson(EffectType::ACCOUNT_CREATED, 'account_created');
        $json['starting_balance'] = '10000.0000000';
        $json['account_muxed'] = 'MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ24';
        $json['account_muxed_id'] = '123';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(AccountCreatedEffectResponse::class, $response);
        $this->assertEquals('MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ24', $response->getAccountMuxed());
        $this->assertEquals('123', $response->getAccountMuxedId());
    }

    public function testAccountRemovedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::ACCOUNT_REMOVED, 'account_removed');

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(AccountRemovedEffectResponse::class, $response);
        $this->assertEquals('account_removed', $response->getHumanReadableEffectType());
        $this->assertEquals(EffectType::ACCOUNT_REMOVED, $response->getEffectType());
    }

    public function testAccountCreditedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::ACCOUNT_CREDITED, 'account_credited');
        $json['amount'] = '1000.0000000';
        $json['asset_type'] = 'native';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(AccountCreditedEffectResponse::class, $response);
        $this->assertEquals('1000.0000000', $response->getAmount());
        $this->assertInstanceOf(Asset::class, $response->getAsset());
        $this->assertEquals(Asset::TYPE_NATIVE, $response->getAsset()->getType());
    }

    public function testAccountCreditedEffectWithCreditAlphanum4(): void
    {
        $json = $this->getBaseEffectJson(EffectType::ACCOUNT_CREDITED, 'account_credited');
        $json['amount'] = '500.0000000';
        $json['asset_type'] = 'credit_alphanum4';
        $json['asset_code'] = 'USD';
        $json['asset_issuer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(AccountCreditedEffectResponse::class, $response);
        $this->assertEquals('500.0000000', $response->getAmount());
        $this->assertEquals('USD', $response->getAsset()->getCode());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getAsset()->getIssuer());
    }

    public function testAccountDebitedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::ACCOUNT_DEBITED, 'account_debited');
        $json['amount'] = '100.0000000';
        $json['asset_type'] = 'credit_alphanum12';
        $json['asset_code'] = 'LONGASSET';
        $json['asset_issuer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(AccountDebitedEffectResponse::class, $response);
        $this->assertEquals('100.0000000', $response->getAmount());
        $this->assertEquals('LONGASSET', $response->getAsset()->getCode());
    }

    public function testAccountThresholdsUpdatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::ACCOUNT_THRESHOLDS_UPDATED, 'account_thresholds_updated');
        $json['low_threshold'] = 1;
        $json['med_threshold'] = 2;
        $json['high_threshold'] = 3;

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(AccountThresholdsUpdatedEffectResponse::class, $response);
        $this->assertEquals(1, $response->getLowThreshold());
        $this->assertEquals(2, $response->getMedThreshold());
        $this->assertEquals(3, $response->getHighThreshold());
    }

    public function testAccountHomeDomainUpdatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::ACCOUNT_HOME_DOMAIN_UPDATED, 'account_home_domain_updated');
        $json['home_domain'] = 'stellar.org';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(AccountHomeDomainUpdatedEffectResponse::class, $response);
        $this->assertEquals('stellar.org', $response->getHomeDomain());
    }

    public function testAccountFlagsUpdatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::ACCOUNT_FLAGS_UPDATED, 'account_flags_updated');
        $json['auth_required'] = true;
        $json['auth_revocable'] = false;
        $json['auth_immutable'] = true;

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(AccountFlagsUpdatedEffectResponse::class, $response);
        $this->assertTrue($response->isAuthRequired());
        $this->assertFalse($response->isAuthRevocable());
        $this->assertTrue($response->isAuthImmutable());
    }

    public function testAccountInflationDestinationUpdatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::ACCOUNT_INFLATION_DESTINATION_UPDATED, 'account_inflation_destination_updated');

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(AccountInflationDestinationUpdatedEffectResponse::class, $response);
    }

    // Account Sponsorship Effects Tests

    public function testAccountSponsorshipCreatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::ACCOUNT_SPONSORSHIP_CREATED, 'account_sponsorship_created');
        $json['sponsor'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(AccountSponsorshipCreatedEffectResponse::class, $response);
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getSponsor());
    }

    public function testAccountSponsorshipUpdatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::ACCOUNT_SPONSORSHIP_UPDATED, 'account_sponsorship_updated');
        $json['new_sponsor'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['former_sponsor'] = 'GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(AccountSponsorshipUpdatedEffectResponse::class, $response);
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getNewSponsor());
        $this->assertEquals('GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL', $response->getFormerSponsor());
    }

    public function testAccountSponsorshipRemovedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::ACCOUNT_SPONSORSHIP_REMOVED, 'account_sponsorship_removed');
        $json['former_sponsor'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(AccountSponsorshipRemovedEffectResponse::class, $response);
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getFormerSponsor());
    }

    // Signer Effects Tests

    public function testSignerCreatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::SIGNER_CREATED, 'signer_created');

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(SignerCreatedEffectResponse::class, $response);
        $this->assertEquals('signer_created', $response->getHumanReadableEffectType());
    }

    public function testSignerCreatedEffectWithFullData(): void
    {
        $json = $this->getBaseEffectJson(EffectType::SIGNER_CREATED, 'signer_created');
        $json['public_key'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['weight'] = 10;
        $json['key'] = 'some_key_value';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(SignerCreatedEffectResponse::class, $response);
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getPublicKey());
        $this->assertEquals(10, $response->getWeight());
        $this->assertEquals('some_key_value', $response->getKey());
    }

    public function testSignerRemovedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::SIGNER_REMOVED, 'signer_removed');

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(SignerRemovedEffectResponse::class, $response);
        $this->assertEquals('signer_removed', $response->getHumanReadableEffectType());
    }

    public function testSignerRemovedEffectWithFullData(): void
    {
        $json = $this->getBaseEffectJson(EffectType::SIGNER_REMOVED, 'signer_removed');
        $json['public_key'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['weight'] = 0;

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(SignerRemovedEffectResponse::class, $response);
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getPublicKey());
        $this->assertEquals(0, $response->getWeight());
    }

    public function testSignerUpdatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::SIGNER_UPDATED, 'signer_updated');

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(SignerUpdatedEffectResponse::class, $response);
        $this->assertEquals('signer_updated', $response->getHumanReadableEffectType());
    }

    public function testSignerUpdatedEffectWithFullData(): void
    {
        $json = $this->getBaseEffectJson(EffectType::SIGNER_UPDATED, 'signer_updated');
        $json['public_key'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['weight'] = 5;

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(SignerUpdatedEffectResponse::class, $response);
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getPublicKey());
        $this->assertEquals(5, $response->getWeight());
    }

    // Signer Sponsorship Effects Tests

    public function testSignerSponsorshipCreatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::SIGNER_SPONSORSHIP_CREATED, 'signer_sponsorship_created');
        $json['signer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['sponsor'] = 'GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(SignerSponsorshipCreatedEffectResponse::class, $response);
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getSigner());
        $this->assertEquals('GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL', $response->getSponsor());
    }

    public function testSignerSponsorshipUpdatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::SIGNER_SPONSORSHIP_UPDATED, 'signer_sponsorship_updated');
        $json['signer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['former_sponsor'] = 'GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL';
        $json['new_sponsor'] = 'GC23QF2HUE52AMXUFUH3AYJAXXGXXV2VHXYYR6EYXETPKDXZSAW67XO4';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(SignerSponsorshipUpdatedEffectResponse::class, $response);
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getSigner());
        $this->assertEquals('GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL', $response->getFormerSponsor());
        $this->assertEquals('GC23QF2HUE52AMXUFUH3AYJAXXGXXV2VHXYYR6EYXETPKDXZSAW67XO4', $response->getNewSponsor());
    }

    public function testSignerSponsorshipRemovedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::SIGNER_SPONSORSHIP_REMOVED, 'signer_sponsorship_removed');
        $json['signer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['former_sponsor'] = 'GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(SignerSponsorshipRemovedEffectResponse::class, $response);
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getSigner());
        $this->assertEquals('GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL', $response->getFormerSponsor());
    }

    // Trustline Effects Tests

    public function testTrustlineCreatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::TRUSTLINE_CREATED, 'trustline_created');

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(TrustlineCreatedEffectResponse::class, $response);
        $this->assertEquals('trustline_created', $response->getHumanReadableEffectType());
    }

    public function testTrustlineCreatedEffectWithFullData(): void
    {
        $json = $this->getBaseEffectJson(EffectType::TRUSTLINE_CREATED, 'trustline_created');
        $json['limit'] = '1000000.0000000';
        $json['asset_type'] = 'credit_alphanum4';
        $json['asset_code'] = 'USD';
        $json['asset_issuer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(TrustlineCreatedEffectResponse::class, $response);
        $this->assertEquals('1000000.0000000', $response->getLimit());
        $this->assertEquals('credit_alphanum4', $response->getAssetType());
        $this->assertEquals('USD', $response->getAssetCode());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getAssetIssuer());
        $this->assertNull($response->getLiquidityPoolId());
    }

    public function testTrustlineCreatedEffectWithLiquidityPoolId(): void
    {
        $json = $this->getBaseEffectJson(EffectType::TRUSTLINE_CREATED, 'trustline_created');
        $json['limit'] = '10000.0000000';
        $json['asset_type'] = 'liquidity_pool_shares';
        $json['liquidity_pool_id'] = 'abcdef1234567890';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(TrustlineCreatedEffectResponse::class, $response);
        $this->assertEquals('liquidity_pool_shares', $response->getAssetType());
        $this->assertEquals('abcdef1234567890', $response->getLiquidityPoolId());
        $this->assertNull($response->getAssetCode());
        $this->assertNull($response->getAssetIssuer());
    }

    public function testTrustlineRemovedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::TRUSTLINE_REMOVED, 'trustline_removed');

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(TrustlineRemovedEffectResponse::class, $response);
        $this->assertEquals('trustline_removed', $response->getHumanReadableEffectType());
    }

    public function testTrustlineUpdatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::TRUSTLINE_UPDATED, 'trustline_updated');

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(TrustlineUpdatedEffectResponse::class, $response);
        $this->assertEquals('trustline_updated', $response->getHumanReadableEffectType());
    }

    public function testTrustlineAuthorizedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::TRUSTLINE_AUTHORIZED, 'trustline_authorized');

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(TrustlineAuthorizedEffectResponse::class, $response);
        $this->assertEquals('trustline_authorized', $response->getHumanReadableEffectType());
    }

    public function testTrustlineDeauthorizedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::TRUSTLINE_DEAUTHORIZED, 'trustline_deauthorized');

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(TrustlineDeauthorizedEffectResponse::class, $response);
        $this->assertEquals('trustline_deauthorized', $response->getHumanReadableEffectType());
    }

    public function testTrustlineAuthorizedToMaintainLiabilitiesEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::TRUSTLINE_AUTHORIZED_TO_MAINTAIN_LIABILITIES, 'trustline_authorized_to_maintain_liabilities');

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(TrustlineAuthorizedToMaintainLiabilitiesEffectResponse::class, $response);
        $this->assertEquals('trustline_authorized_to_maintain_liabilities', $response->getHumanReadableEffectType());
    }

    public function testTrustlineFlagsUpdatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::TRUSTLINE_FLAGS_UPDATED, 'trustline_flags_updated');
        $json['asset_type'] = 'credit_alphanum4';
        $json['asset_code'] = 'USD';
        $json['asset_issuer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['trustor'] = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
        $json['authorized_flag'] = true;
        $json['authorized_to_maintain_liabilities_flag'] = false;
        $json['clawback_enabled_flag'] = true;

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(TrustlineFlagsUpdatedEffectResponse::class, $response);
        $this->assertEquals('USD', $response->getAsset()->getCode());
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $response->getTrustor());
        $this->assertTrue($response->getAuthorizedFlag());
        $this->assertFalse($response->getAuthorizedToMaintainLiabilitiesFlag());
        $this->assertTrue($response->getClawbackEnabledFlag());
    }

    // Trustline Sponsorship Effects Tests

    public function testTrustlineSponsorshipCreatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::TRUSTLINE_SPONSORSHIP_CREATED, 'trustline_sponsorship_created');
        $json['asset'] = 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['asset_type'] = 'credit_alphanum4';
        $json['sponsor'] = 'GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(TrustlineSponsorshipCreatedEffectResponse::class, $response);
        $this->assertEquals('USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getAsset());
        $this->assertEquals('credit_alphanum4', $response->getAssetType());
        $this->assertEquals('GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL', $response->getSponsor());
        $this->assertNull($response->getLiquidityPoolId());
    }

    public function testTrustlineSponsorshipCreatedEffectWithLiquidityPoolId(): void
    {
        $json = $this->getBaseEffectJson(EffectType::TRUSTLINE_SPONSORSHIP_CREATED, 'trustline_sponsorship_created');
        $json['asset_type'] = 'liquidity_pool_shares';
        $json['liquidity_pool_id'] = 'pool123456';
        $json['sponsor'] = 'GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(TrustlineSponsorshipCreatedEffectResponse::class, $response);
        $this->assertEquals('liquidity_pool_shares', $response->getAssetType());
        $this->assertEquals('pool123456', $response->getLiquidityPoolId());
        $this->assertEquals('GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL', $response->getSponsor());
    }

    public function testTrustlineSponsorshipUpdatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::TRUSTLINE_SPONSORSHIP_UPDATED, 'trustline_sponsorship_updated');
        $json['asset'] = 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['asset_type'] = 'credit_alphanum4';
        $json['former_sponsor'] = 'GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL';
        $json['new_sponsor'] = 'GC23QF2HUE52AMXUFUH3AYJAXXGXXV2VHXYYR6EYXETPKDXZSAW67XO4';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(TrustlineSponsorshipUpdatedEffectResponse::class, $response);
        $this->assertEquals('USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getAsset());
        $this->assertEquals('credit_alphanum4', $response->getAssetType());
        $this->assertEquals('GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL', $response->getFormerSponsor());
        $this->assertEquals('GC23QF2HUE52AMXUFUH3AYJAXXGXXV2VHXYYR6EYXETPKDXZSAW67XO4', $response->getNewSponsor());
    }

    public function testTrustlineSponsorshipRemovedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::TRUSTLINE_SPONSORSHIP_REMOVED, 'trustline_sponsorship_removed');
        $json['asset'] = 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
        $json['asset_type'] = 'credit_alphanum4';
        $json['former_sponsor'] = 'GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(TrustlineSponsorshipRemovedEffectResponse::class, $response);
        $this->assertEquals('USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getAsset());
        $this->assertEquals('credit_alphanum4', $response->getAssetType());
        $this->assertEquals('GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL', $response->getFormerSponsor());
    }

    // Offer Effects Tests

    public function testOfferCreatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::OFFER_CREATED, 'offer_created');

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(OfferCreatedEffectResponse::class, $response);
        $this->assertEquals('offer_created', $response->getHumanReadableEffectType());
    }

    public function testOfferRemovedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::OFFER_REMOVED, 'offer_removed');

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(OfferRemovedEffectResponse::class, $response);
        $this->assertEquals('offer_removed', $response->getHumanReadableEffectType());
    }

    public function testOfferUpdatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::OFFER_UPDATED, 'offer_updated');

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(OfferUpdatedEffectResponse::class, $response);
        $this->assertEquals('offer_updated', $response->getHumanReadableEffectType());
    }

    // Trade Effect Test

    public function testTradeEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::TRADE, 'trade');
        $json['seller'] = 'GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL';
        $json['offer_id'] = '12345';
        $json['sold_amount'] = '100.0000000';
        $json['sold_asset_type'] = 'native';
        $json['bought_amount'] = '200.0000000';
        $json['bought_asset_type'] = 'credit_alphanum4';
        $json['bought_asset_code'] = 'USD';
        $json['bought_asset_issuer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(TradeEffectResponse::class, $response);
        $this->assertEquals('GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL', $response->getSeller());
        $this->assertEquals('12345', $response->getOfferId());
        $this->assertEquals('100.0000000', $response->getSoldAmount());
        $this->assertEquals(Asset::TYPE_NATIVE, $response->getSoldAsset()->getType());
        $this->assertEquals('200.0000000', $response->getBoughtAmount());
        $this->assertEquals('USD', $response->getBoughtAsset()->getCode());
        $this->assertNull($response->getSellerMuxed());
        $this->assertNull($response->getSellerMuxedId());
    }

    public function testTradeEffectWithMuxedSeller(): void
    {
        $json = $this->getBaseEffectJson(EffectType::TRADE, 'trade');
        $json['seller'] = 'GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL';
        $json['seller_muxed'] = 'MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ24';
        $json['seller_muxed_id'] = '456';
        $json['offer_id'] = '12345';
        $json['sold_amount'] = '100.0000000';
        $json['sold_asset_type'] = 'native';
        $json['bought_amount'] = '200.0000000';
        $json['bought_asset_type'] = 'native';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(TradeEffectResponse::class, $response);
        $this->assertEquals('MAAAAAABGFQ36FMUQEJBVEBWVMPXIZAKSJYCLOECKPNZ4CFKSDCEWV75TR3C55HR2FJ24', $response->getSellerMuxed());
        $this->assertEquals('456', $response->getSellerMuxedId());
    }

    // Data Effects Tests

    public function testDataCreatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::DATA_CREATED, 'data_created');
        $json['name'] = 'config';
        $json['value'] = 'dGVzdA==';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(DataCreatedEffectResponse::class, $response);
        $this->assertEquals('config', $response->getName());
        $this->assertEquals('dGVzdA==', $response->getValue());
    }

    public function testDataUpdatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::DATA_UPDATED, 'data_updated');
        $json['name'] = 'config';
        $json['value'] = 'bmV3VmFsdWU=';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(DataUpdatedEffectResponse::class, $response);
        $this->assertEquals('config', $response->getName());
        $this->assertEquals('bmV3VmFsdWU=', $response->getValue());
    }

    public function testDataRemovedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::DATA_REMOVED, 'data_removed');
        $json['name'] = 'config';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(DataRemovedEffectResponse::class, $response);
        $this->assertEquals('config', $response->getName());
    }

    // Data Sponsorship Effects Tests

    public function testDataSponsorshipCreatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::DATA_SPONSORSHIP_CREATED, 'data_sponsorship_created');
        $json['data_name'] = 'config';
        $json['sponsor'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(DataSponsorshipCreatedEffectResponse::class, $response);
        $this->assertEquals('config', $response->getDataName());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getSponsor());
    }

    public function testDataSponsorshipUpdatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::DATA_SPONSORSHIP_UPDATED, 'data_sponsorship_updated');
        $json['data_name'] = 'config';
        $json['former_sponsor'] = 'GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL';
        $json['new_sponsor'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(DataSponsorshipUpdatedEffectResponse::class, $response);
        $this->assertEquals('config', $response->getDataName());
        $this->assertEquals('GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL', $response->getFormerSponsor());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getNewSponsor());
    }

    public function testDataSponsorshipRemovedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::DATA_SPONSORSHIP_REMOVED, 'data_sponsorship_removed');
        $json['data_name'] = 'config';
        $json['former_sponsor'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(DataSponsorshipRemovedEffectResponse::class, $response);
        $this->assertEquals('config', $response->getDataName());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getFormerSponsor());
    }

    // Sequence Bumped Effect Test

    public function testSequenceBumpedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::SEQUENCE_BUMPED, 'sequence_bumped');
        $json['new_seq'] = '123456789';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(SequenceBumpedEffectResponse::class, $response);
        $this->assertEquals('123456789', $response->getNewSequence());
    }

    // Claimable Balance Effects Tests

    public function testClaimableBalanceCreatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::CLAIMABLE_BALANCE_CREATED, 'claimable_balance_created');
        $json['balance_id'] = '00000000846c047755e4a46912336f56096b48ece78ddb5fbf6d90f0eb4ecbf98b471d9e';
        $json['amount'] = '10.0000000';
        $json['asset'] = 'native';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(ClaimableBalanceCreatedEffectResponse::class, $response);
        $this->assertEquals('00000000846c047755e4a46912336f56096b48ece78ddb5fbf6d90f0eb4ecbf98b471d9e', $response->getBalanceId());
        $this->assertEquals('10.0000000', $response->getAmount());
        $this->assertInstanceOf(Asset::class, $response->getAsset());
        $this->assertEquals(Asset::TYPE_NATIVE, $response->getAsset()->getType());
    }

    public function testClaimableBalanceClaimantCreatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::CLAIMABLE_BALANCE_CLAIMANT_CREATED, 'claimable_balance_claimant_created');
        $json['balance_id'] = '00000000846c047755e4a46912336f56096b48ece78ddb5fbf6d90f0eb4ecbf98b471d9e';
        $json['amount'] = '10.0000000';
        $json['asset'] = 'native';
        $json['predicate'] = [
            'unconditional' => true
        ];

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(ClaimableBalanceClaimantCreatedEffectResponse::class, $response);
        $this->assertEquals('00000000846c047755e4a46912336f56096b48ece78ddb5fbf6d90f0eb4ecbf98b471d9e', $response->getBalanceId());
        $this->assertEquals('10.0000000', $response->getAmount());
        $this->assertInstanceOf(Asset::class, $response->getAsset());
        $this->assertEquals(Asset::TYPE_NATIVE, $response->getAsset()->getType());
        $this->assertNotNull($response->getPredicate());
    }

    public function testClaimableBalanceClaimedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::CLAIMABLE_BALANCE_CLAIMED, 'claimable_balance_claimed');
        $json['balance_id'] = '00000000846c047755e4a46912336f56096b48ece78ddb5fbf6d90f0eb4ecbf98b471d9e';
        $json['amount'] = '10.0000000';
        $json['asset'] = 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(ClaimableBalanceClaimedEffectResponse::class, $response);
        $this->assertEquals('00000000846c047755e4a46912336f56096b48ece78ddb5fbf6d90f0eb4ecbf98b471d9e', $response->getBalanceId());
        $this->assertEquals('10.0000000', $response->getAmount());
        $this->assertInstanceOf(Asset::class, $response->getAsset());
    }

    public function testClaimableBalanceClawedBackEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::CLAIMABLE_BALANCE_CLAWED_BACK, 'claimable_balance_clawed_back');
        $json['balance_id'] = '00000000846c047755e4a46912336f56096b48ece78ddb5fbf6d90f0eb4ecbf98b471d9e';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(ClaimableBalanceClawedBackEffectResponse::class, $response);
        $this->assertEquals('00000000846c047755e4a46912336f56096b48ece78ddb5fbf6d90f0eb4ecbf98b471d9e', $response->getBalanceId());
    }

    // Claimable Balance Sponsorship Effects Tests

    public function testClaimableBalanceSponsorshipCreatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::CLAIMABLE_BALANCE_SPONSORSHIP_CREATED, 'claimable_balance_sponsorship_created');
        $json['sponsor'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(ClaimableBalanceSponsorshipCreatedEffectResponse::class, $response);
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getSponsor());
    }

    public function testClaimableBalanceSponsorshipUpdatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::CLAIMABLE_BALANCE_SPONSORSHIP_UPDATED, 'claimable_balance_sponsorship_updated');
        $json['former_sponsor'] = 'GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL';
        $json['new_sponsor'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(ClaimableBalanceSponsorshipUpdatedEffectResponse::class, $response);
        $this->assertEquals('GBVFLWXYCIGPO3455XVFIKHS66FCT5AI64ZARKS7QJN4NF7K5FOXTJNL', $response->getFormerSponsor());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getNewSponsor());
    }

    public function testClaimableBalanceSponsorshipRemovedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::CLAIMABLE_BALANCE_SPONSORSHIP_REMOVED, 'claimable_balance_sponsorship_removed');
        $json['former_sponsor'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(ClaimableBalanceSponsorshipRemovedEffectResponse::class, $response);
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getFormerSponsor());
    }

    // Liquidity Pool Effects Tests

    public function testLiquidityPoolCreatedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::LIQUIDITY_POOL_CREATED, 'liquidity_pool_created');
        $json['liquidity_pool'] = [
            'id' => 'abcdef1234567890',
            'fee_bp' => 30,
            'type' => 'constant_product',
            'total_trustlines' => '100',
            'total_shares' => '5000',
            'reserves' => [
                [
                    'amount' => '1000.0000000',
                    'asset' => 'native'
                ],
                [
                    'amount' => '2000.0000000',
                    'asset' => 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
                ]
            ]
        ];

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(LiquidityPoolCreatedEffectResponse::class, $response);
        $liquidityPool = $response->getLiquidityPool();
        $this->assertEquals('abcdef1234567890', $liquidityPool->getPoolId());
        $this->assertEquals(30, $liquidityPool->getFee());
        $this->assertEquals('constant_product', $liquidityPool->getType());
        $this->assertEquals('100', $liquidityPool->getTotalTrustlines());
        $this->assertEquals('5000', $liquidityPool->getTotalShares());
        $this->assertCount(2, $liquidityPool->getReserves()->toArray());
    }

    public function testLiquidityPoolRemovedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::LIQUIDITY_POOL_REMOVED, 'liquidity_pool_removed');
        $json['liquidity_pool_id'] = 'abcdef1234567890';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(LiquidityPoolRemovedEffectResponse::class, $response);
        $this->assertEquals('abcdef1234567890', $response->getLiquidityPoolId());
    }

    public function testLiquidityPoolDepositedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::LIQUIDITY_POOL_DEPOSITED, 'liquidity_pool_deposited');
        $json['liquidity_pool'] = [
            'id' => 'abcdef1234567890',
            'fee_bp' => 30,
            'type' => 'constant_product',
            'total_trustlines' => '100',
            'total_shares' => '5000',
            'reserves' => [
                [
                    'amount' => '1000.0000000',
                    'asset' => 'native'
                ],
                [
                    'amount' => '2000.0000000',
                    'asset' => 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
                ]
            ]
        ];
        $json['reserves_deposited'] = [
            [
                'amount' => '100.0000000',
                'asset' => 'native'
            ],
            [
                'amount' => '200.0000000',
                'asset' => 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
            ]
        ];
        $json['shares_received'] = '150.0000000';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(LiquidityPoolDepositedEffectResponse::class, $response);
        $this->assertEquals('abcdef1234567890', $response->getLiquidityPool()->getPoolId());
        $this->assertEquals('150.0000000', $response->getSharesReceived());
        $this->assertCount(2, $response->getReservesDeposited()->toArray());
    }

    public function testLiquidityPoolWithdrewEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::LIQUIDITY_POOL_WITHDREW, 'liquidity_pool_withdrew');
        $json['liquidity_pool'] = [
            'id' => 'abcdef1234567890',
            'fee_bp' => 30,
            'type' => 'constant_product',
            'total_trustlines' => '100',
            'total_shares' => '5000',
            'reserves' => [
                [
                    'amount' => '1000.0000000',
                    'asset' => 'native'
                ],
                [
                    'amount' => '2000.0000000',
                    'asset' => 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
                ]
            ]
        ];
        $json['reserves_received'] = [
            [
                'amount' => '100.0000000',
                'asset' => 'native'
            ],
            [
                'amount' => '200.0000000',
                'asset' => 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
            ]
        ];
        $json['shares_redeemed'] = '150.0000000';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(LiquidityPoolWithdrewEffectResponse::class, $response);
        $this->assertEquals('abcdef1234567890', $response->getLiquidityPool()->getPoolId());
        $this->assertEquals('150.0000000', $response->getSharesRedeemed());
        $this->assertCount(2, $response->getReservesReceived()->toArray());
    }

    public function testLiquidityPoolTradeEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::LIQUIDITY_POOL_TRADE, 'liquidity_pool_trade');
        $json['liquidity_pool'] = [
            'id' => 'abcdef1234567890',
            'fee_bp' => 30,
            'type' => 'constant_product',
            'total_trustlines' => '100',
            'total_shares' => '5000',
            'reserves' => [
                [
                    'amount' => '1000.0000000',
                    'asset' => 'native'
                ],
                [
                    'amount' => '2000.0000000',
                    'asset' => 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
                ]
            ]
        ];
        $json['sold'] = [
            'amount' => '100.0000000',
            'asset' => 'native'
        ];
        $json['bought'] = [
            'amount' => '200.0000000',
            'asset' => 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
        ];

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(LiquidityPoolTradeEffectResponse::class, $response);
        $this->assertEquals('abcdef1234567890', $response->getLiquidityPool()->getPoolId());
        $this->assertNotNull($response->getSold());
        $this->assertNotNull($response->getBought());
    }

    public function testLiquidityPoolRevokedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::LIQUIDITY_POOL_REVOKED, 'liquidity_pool_revoked');
        $json['liquidity_pool'] = [
            'id' => 'abcdef1234567890',
            'fee_bp' => 30,
            'type' => 'constant_product',
            'total_trustlines' => '100',
            'total_shares' => '5000',
            'reserves' => [
                [
                    'amount' => '1000.0000000',
                    'asset' => 'native'
                ],
                [
                    'amount' => '2000.0000000',
                    'asset' => 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
                ]
            ]
        ];
        $json['reserves_revoked'] = [
            [
                'amount' => '100.0000000',
                'asset' => 'native',
                'claimable_balance_id' => '00000000846c047755e4a46912336f56096b48ece78ddb5fbf6d90f0eb4ecbf98b471d9e'
            ],
            [
                'amount' => '200.0000000',
                'asset' => 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX',
                'claimable_balance_id' => '00000000946c047755e4a46912336f56096b48ece78ddb5fbf6d90f0eb4ecbf98b471d9f'
            ]
        ];
        $json['shares_revoked'] = '150.0000000';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(LiquidityPoolRevokedEffectResponse::class, $response);
        $this->assertEquals('abcdef1234567890', $response->getLiquidityPool()->getPoolId());
        $this->assertEquals('150.0000000', $response->getSharesRevoked());
        $this->assertCount(2, $response->getReservesRevoked()->toArray());
    }

    // Contract Effects Tests

    public function testContractCreditedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::CONTRACT_CREDITED, 'contract_credited');
        $json['contract'] = 'CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC';
        $json['amount'] = '1000.0000000';
        $json['asset_type'] = 'native';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(ContractCreditedEffectResponse::class, $response);
        $this->assertEquals('CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC', $response->getContract());
        $this->assertEquals('1000.0000000', $response->getAmount());
        $this->assertEquals('native', $response->getAssetType());
        $this->assertNull($response->getAssetIssuer());
    }

    public function testContractCreditedEffectWithCreditAsset(): void
    {
        $json = $this->getBaseEffectJson(EffectType::CONTRACT_CREDITED, 'contract_credited');
        $json['contract'] = 'CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC';
        $json['amount'] = '500.0000000';
        $json['asset_type'] = 'credit_alphanum4';
        $json['asset_code'] = 'USD';
        $json['asset_issuer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(ContractCreditedEffectResponse::class, $response);
        $this->assertEquals('credit_alphanum4', $response->getAssetType());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getAssetIssuer());
    }

    public function testContractDebitedEffectFromJson(): void
    {
        $json = $this->getBaseEffectJson(EffectType::CONTRACT_DEBITED, 'contract_debited');
        $json['contract'] = 'CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC';
        $json['amount'] = '500.0000000';
        $json['asset_type'] = 'credit_alphanum4';
        $json['asset_code'] = 'USDC';
        $json['asset_issuer'] = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(ContractDebitedEffectResponse::class, $response);
        $this->assertEquals('CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC', $response->getContract());
        $this->assertEquals('500.0000000', $response->getAmount());
        $this->assertEquals('credit_alphanum4', $response->getAssetType());
        $this->assertEquals('USDC', $response->getAssetCode());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getAssetIssuer());
    }

    // Exception Tests

    public function testFromJsonThrowsExceptionWhenTypeIsMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No effect type_i found in json data');

        $json = [
            'id' => '0000000012884905985-0000000001',
            'account' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7'
        ];

        EffectResponse::fromJson($json);
    }

    public function testFromJsonThrowsExceptionForUnknownEffectType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown operation type: 9999');

        $json = [
            'type_i' => 9999,
            'id' => '0000000012884905985-0000000001',
            'account' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7'
        ];

        EffectResponse::fromJson($json);
    }

    // Links Tests

    public function testEffectLinksResponseFromJson(): void
    {
        $json = [
            'operation' => ['href' => 'https://horizon.stellar.org/operations/12884905985'],
            'succeeds' => ['href' => 'https://horizon.stellar.org/effects?cursor=12884905985-1&order=desc'],
            'precedes' => ['href' => 'https://horizon.stellar.org/effects?cursor=12884905985-1&order=asc']
        ];

        $links = EffectLinksResponse::fromJson($json);

        $this->assertInstanceOf(EffectLinksResponse::class, $links);
        $this->assertEquals('https://horizon.stellar.org/operations/12884905985', $links->getOperation()->getHref());
        $this->assertEquals('https://horizon.stellar.org/effects?cursor=12884905985-1&order=desc', $links->getSucceeds()->getHref());
        $this->assertEquals('https://horizon.stellar.org/effects?cursor=12884905985-1&order=asc', $links->getPrecedes()->getHref());
    }

    // Edge Cases Tests

    public function testEffectWithMinimalData(): void
    {
        $json = [
            'id' => '123',
            'paging_token' => '456',
            'account' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'type' => 'account_removed',
            'type_i' => EffectType::ACCOUNT_REMOVED,
            'created_at' => '2020-01-01T00:00:00Z',
            '_links' => [
                'operation' => ['href' => 'https://horizon.stellar.org/operations/123'],
                'succeeds' => ['href' => 'https://horizon.stellar.org/effects?cursor=456'],
                'precedes' => ['href' => 'https://horizon.stellar.org/effects?cursor=456']
            ]
        ];

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(AccountRemovedEffectResponse::class, $response);
        $this->assertEquals('123', $response->getEffectId());
    }

    public function testNullableFieldsAreHandledCorrectly(): void
    {
        $json = $this->getBaseEffectJson(EffectType::ACCOUNT_CREATED, 'account_created');
        $json['starting_balance'] = '1000.0000000';

        $response = EffectResponse::fromJson($json);

        $this->assertInstanceOf(AccountCreatedEffectResponse::class, $response);
        $this->assertNull($response->getAccountMuxed());
        $this->assertNull($response->getAccountMuxedId());
    }

    // Collection Tests

    public function testEffectsResponseCollection(): void
    {
        $json1 = $this->getBaseEffectJson(EffectType::ACCOUNT_CREATED, 'account_created');
        $json1['starting_balance'] = '1000.0000000';
        $effect1 = EffectResponse::fromJson($json1);

        $json2 = $this->getBaseEffectJson(EffectType::ACCOUNT_REMOVED, 'account_removed');
        $effect2 = EffectResponse::fromJson($json2);

        $collection = new EffectsResponse();
        $collection->add($effect1);
        $collection->add($effect2);

        $this->assertEquals(2, $collection->count());
        $this->assertCount(2, $collection->toArray());

        $array = $collection->toArray();
        $this->assertInstanceOf(AccountCreatedEffectResponse::class, $array[0]);
        $this->assertInstanceOf(AccountRemovedEffectResponse::class, $array[1]);
    }

    public function testEffectsPageResponseFromJson(): void
    {
        $json = [
            '_embedded' => [
                'records' => [
                    array_merge($this->getBaseEffectJson(EffectType::ACCOUNT_CREATED, 'account_created'), ['starting_balance' => '1000.0000000']),
                    $this->getBaseEffectJson(EffectType::ACCOUNT_REMOVED, 'account_removed'),
                    array_merge($this->getBaseEffectJson(EffectType::ACCOUNT_CREDITED, 'account_credited'), ['amount' => '500.0000000', 'asset_type' => 'native'])
                ]
            ],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/effects?order=desc&limit=10'],
                'next' => ['href' => 'https://horizon.stellar.org/effects?order=desc&limit=10&cursor=123'],
                'prev' => ['href' => 'https://horizon.stellar.org/effects?order=asc&limit=10&cursor=100']
            ]
        ];

        $page = EffectsPageResponse::fromJson($json);

        $this->assertInstanceOf(EffectsPageResponse::class, $page);
        $this->assertCount(3, $page->getEffects()->toArray());

        $effects = $page->getEffects()->toArray();
        $this->assertInstanceOf(AccountCreatedEffectResponse::class, $effects[0]);
        $this->assertInstanceOf(AccountRemovedEffectResponse::class, $effects[1]);
        $this->assertInstanceOf(AccountCreditedEffectResponse::class, $effects[2]);

        $this->assertTrue($page->hasNextPage());
        $this->assertTrue($page->hasPrevPage());
        $this->assertEquals('https://horizon.stellar.org/effects?order=desc&limit=10&cursor=123', $page->getLinks()->getNext()->getHref());
        $this->assertEquals('https://horizon.stellar.org/effects?order=asc&limit=10&cursor=100', $page->getLinks()->getPrev()->getHref());
    }

    public function testEffectsPageResponseWithEmptyRecords(): void
    {
        $json = [
            '_embedded' => [
                'records' => []
            ],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/effects'],
                'next' => ['href' => 'https://horizon.stellar.org/effects'],
                'prev' => ['href' => 'https://horizon.stellar.org/effects']
            ]
        ];

        $page = EffectsPageResponse::fromJson($json);

        $this->assertInstanceOf(EffectsPageResponse::class, $page);
        $this->assertCount(0, $page->getEffects()->toArray());
    }
}
