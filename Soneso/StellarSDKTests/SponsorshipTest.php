<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\BeginSponsoringFutureReservesOperationBuilder;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\Claimant;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\CreateClaimableBalanceOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\EndSponsoringFutureReservesOperationBuilder;
use Soneso\StellarSDK\ManageDataOperationBuilder;
use Soneso\StellarSDK\ManageSellOfferOperationBuilder;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\RevokeSponsorshipOperationBuilder;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDK\Xdr\XdrSignerKey;
use Soneso\StellarSDK\Xdr\XdrSignerKeyType;
use Soneso\StellarSDKTests\TestUtils;

class SponsorshipTest extends TestCase
{
    public function testSponsorship(): void
    {
        $sdk = StellarSDK::getTestNetInstance();

        $masterAccountKeyPair = KeyPair::random();
        $masterAccountId = $masterAccountKeyPair->getAccountId();
        FriendBot::fundTestAccount($masterAccountId);

        $accountAKeyPair = KeyPair::random();
        $accountAId = $accountAKeyPair->getAccountId();

        $beginSponsoringBuilder = (new BeginSponsoringFutureReservesOperationBuilder($accountAId))->setSourceAccount($masterAccountId);
        $createAccountBuilder = new CreateAccountOperationBuilder($accountAId, "100");
        $dataName = "soneso";
        $dataValue = "is super";
        $manageDataBuilder = (new ManageDataOperationBuilder($dataName, $dataValue))->setSourceAccount($accountAId);
        $richAsset = Asset::createFromCanonicalForm("RICH:".$masterAccountId);
        $changeTrustBuilder = (new ChangeTrustOperationBuilder($richAsset, "100000"))->setSourceAccount($accountAId);
        $paymentBuilder = (new PaymentOperationBuilder($accountAId, $richAsset, "1000"));
        $manageSellOfferBuilder = (new ManageSellOfferOperationBuilder($richAsset, Asset::native(), "10", "2"))->setSourceAccount($accountAId);
        $claimant = new Claimant($masterAccountId, Claimant::predicateUnconditional());
        $claimants = array();
        array_push($claimants, $claimant);
        $createClaimBuilder = (new CreateClaimableBalanceOperationBuilder($claimants, $richAsset, "10"));
        $setOptionsBuilder = (new SetOptionsOperationBuilder())->setSourceAccount($accountAId);
        $signer = new XdrSignerKey();
        $signer->setType(new XdrSignerKeyType(XdrSignerKeyType::ED25519));
        $signer->setEd25519($masterAccountKeyPair->getPublicKey());
        $setOptionsBuilder->setSigner($signer, 1);
        $endSponsorshipBuilder = (new EndSponsoringFutureReservesOperationBuilder())->setSourceAccount($accountAId);
        $revokeAccountSpBuilder = (new RevokeSponsorshipOperationBuilder())->revokeAccountSponsorship($accountAId);
        $revokeDataSpBuilder = (new RevokeSponsorshipOperationBuilder())->revokeDataSponsorship($accountAId, $dataName);
        $revokeTrustlineSpBuilder = (new RevokeSponsorshipOperationBuilder())->revokeTrustlineSponsorship($accountAId, $richAsset);
        $revokeSignerSpBuilder = (new RevokeSponsorshipOperationBuilder())->revokeEd25519Signer($accountAId, $masterAccountId);

        $masterAccount = $sdk->requestAccount($masterAccountId);
        $transaction = (new TransactionBuilder($masterAccount))
            ->addOperation($beginSponsoringBuilder->build())
            ->addOperation($createAccountBuilder->build())
            ->addOperation($manageDataBuilder->build())
            ->addOperation($changeTrustBuilder->build())
            ->addOperation($paymentBuilder->build())
            ->addOperation($manageSellOfferBuilder->build())
            ->addOperation($createClaimBuilder->build())
            ->addOperation($setOptionsBuilder->build())
            ->addOperation($endSponsorshipBuilder->build())
            ->addOperation($revokeAccountSpBuilder->build())
            ->addOperation($revokeDataSpBuilder->build())
            ->addOperation($revokeTrustlineSpBuilder->build())
            ->addOperation($revokeSignerSpBuilder->build())
            ->addMemo(Memo::text("sponsor"))->build();

        $transaction->sign($masterAccountKeyPair, Network::testnet());
        $transaction->sign($accountAKeyPair, Network::testnet());

        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);
    }
}