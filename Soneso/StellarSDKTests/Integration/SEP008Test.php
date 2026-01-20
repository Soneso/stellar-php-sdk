<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Integration;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Soneso\StellarSDK\AccountFlag;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\ManageBuyOfferOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostActionDone;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostActionNextUrl;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionActionRequired;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionPending;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionRejected;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionRevised;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionSuccess;
use Soneso\StellarSDK\SEP\Toml\StellarToml;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\SetTrustLineFlagsOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDK\Xdr\XdrTrustLineFlags;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;

class SEP008Test  extends TestCase
{

    private String $anchorToml = '
      # Sample stellar.toml
      VERSION="2.0.0"

      NETWORK_PASSPHRASE="Test SDF Network ; September 2015"
      WEB_AUTH_ENDPOINT="https://api.anchor.org/auth"
      TRANSFER_SERVER_SEP0024="http://api.stellar.org/transfer-sep24/"
      ANCHOR_QUOTE_SERVER="http://api.stellar.org/quotes-sep38/"
      SIGNING_KEY="GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP"

      [[CURRENCIES]]
      code="GOAT"
      regulated=true
      approval_server="https://goat.io/tx_approve"
      approval_criteria="The goat approval server will ensure that transactions are compliant with NFO regulation"

      [[CURRENCIES]]
      code="NOP"
      issuer="GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP"
      display_decimals=2

      [[CURRENCIES]]
      code="JACK"
      regulated=true
      approval_server="https://jack.io/tx_approve"
      approval_criteria="The jack approval server will ensure that transactions are compliant with NFO regulation"
     ';

    public function testRegulatedAssets(): void
    {

        // SET UP TEST
        $sdk = StellarSDK::getTestNetInstance();
        $network = Network::testnet();
        $asset1IssuerKp = KeyPair::random();
        $asset2IssuerKp = KeyPair::random();
        $accountAKp = KeyPair::random();
        $tomlData = new StellarToml(toml: $this->anchorToml);
        FriendBot::fundTestAccount($asset1IssuerKp->getAccountId());
        FriendBot::fundTestAccount($asset2IssuerKp->getAccountId());
        FriendBot::fundTestAccount($accountAKp->getAccountId());

        $sourceAccountId = $asset1IssuerKp->getAccountId();
        $sourceAccount = $sdk->requestAccount($sourceAccountId);
        $flags = AccountFlag::AUTH_REQUIRED_FLAG | AccountFlag::AUTH_REVOCABLE_FLAG;
        $so1 = (new SetOptionsOperationBuilder())->setSetFlags($flags)->build();
        $transaction = (new TransactionBuilder($sourceAccount))->addOperation($so1)->build();
        $transaction->sign($asset1IssuerKp, $network);
        $response = $sdk->submitTransaction($transaction);
        self::assertTrue($response->isSuccessful());
        self::assertNotNull($tomlData->currencies);
        foreach ($tomlData->currencies as $currency) {
            if ($currency->code == 'GOAT') {
                $currency->issuer = $asset1IssuerKp->getAccountId();
            } else if ($currency->code == 'JACK') {
                $currency->issuer = $asset2IssuerKp->getAccountId();
            }
        }
        foreach ($tomlData->currencies as $currency) {
            assertNotNull($currency->issuer);
        }

        $service = new RegulatedAssetsService(tomlData: $tomlData);
        $regulatedAssets = $service->regulatedAssets;
        self::assertCount(2, $regulatedAssets);
        $goatAsset = $regulatedAssets[0];
        $authRequired = $service->authorizationRequired($goatAsset);
        self::assertTrue($authRequired);
        self::assertEquals('https://goat.io/tx_approve', $goatAsset->approvalServer);
        self::assertEquals(
            'The goat approval server will ensure that transactions are compliant with NFO regulation',
            $goatAsset->approvalCriteria,
        );
        $jackAsset = $regulatedAssets[1];
        $authRequired = $service->authorizationRequired($jackAsset);
        self::assertFalse($authRequired);
        self::assertEquals('https://jack.io/tx_approve', $jackAsset->approvalServer);
        self::assertEquals(
            'The jack approval server will ensure that transactions are compliant with NFO regulation',
            $jackAsset->approvalCriteria,
        );
        assertEquals(Network::testnet()->getNetworkPassphrase(), $service->network->getNetworkPassphrase());

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->anchorToml)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $service = RegulatedAssetsService::fromDomain(domain: 'api.anchor.org', httpClient: $httpClient);
        assertEquals(Network::testnet()->getNetworkPassphrase(), $service->network->getNetworkPassphrase());
        self::assertCount(0, $service->regulatedAssets);

        // post tx

        $accountAId = $accountAKp->getAccountId();
        $accountA = $sdk->requestAccount($accountAId);

        // Operation 1: AllowTrust op where issuer fully authorizes account A, asset X
        $op1 = (new SetTrustLineFlagsOperationBuilder(
            trustorId: $accountAId,
            asset: $goatAsset,
            clearFlags: 0,
            setFlags: XdrTrustLineFlags::AUTHORIZED_FLAG,
        ))->build();

        // Operation 2: Account A manages offer to buy asset X
        $op2 = (new ManageBuyOfferOperationBuilder(
            selling: Asset::native(),
            buying: $goatAsset,
            amount: '10',
            price: '0.1',
        ))->build();

        // Operation 3: AllowTrust op where issuer sets account A, asset X to AUTHORIZED_TO_MAINTAIN_LIABILITIES_FLAG state
        $op3 = (new SetTrustLineFlagsOperationBuilder(
            trustorId: $accountAId,
            asset: $goatAsset,
            clearFlags: XdrTrustLineFlags::AUTHORIZED_FLAG,
            setFlags: XdrTrustLineFlags::AUTHORIZED_TO_MAINTAIN_LIABILITIES_FLAG,
        ))->build();

        $tx = (new TransactionBuilder(sourceAccount: $accountA))
            ->addOperation($op1)
            ->addOperation($op2)
            ->addOperation($op3)
            ->build();

        $txB46Xdr = $tx->toEnvelopeXdrBase64();
        $actionUrl = 'https://goat.io/action';

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode([
                'status' => 'success',
                'tx' => $txB46Xdr,
                'message' => 'hello'
            ])),
            new Response(200, ['X-Foo' => 'Bar'], json_encode([
                'status' => 'revised',
                'tx' => $txB46Xdr . $txB46Xdr,
                'message' => 'hello'
            ])),
            new Response(200, ['X-Foo' => 'Bar'], json_encode([
                'status' => 'pending',
                'timeout' => 3,
                'message' => 'hello'
            ])),
            new Response(400, ['X-Foo' => 'Bar'], json_encode([
                'status' => 'rejected',
                'error' => 'hello'
            ])),
            new Response(200, ['X-Foo' => 'Bar'], json_encode([
                'status' => 'action_required',
                'action_url' => $actionUrl,
                'action_method' => 'POST',
                'action_fields' => ['email_address', 'mobile_number'],
                'message' => 'hello'
            ])),
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) use ($txB46Xdr) {
            $this->assertEquals("POST", $request->getMethod());
            $body = $request->getBody()->__toString();
            $jsonData = @json_decode($body, true);
            $this->assertEquals($txB46Xdr, $jsonData['tx']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);

        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);
        $response = $service->postTransaction(
            tx: $txB46Xdr,
            approvalServer: $goatAsset->approvalServer,
        );

        self::assertTrue($response instanceof SEP08PostTransactionSuccess);
        if ($response instanceof SEP08PostTransactionSuccess) {
            assertEquals($txB46Xdr, $response->tx);
            assertEquals('hello', $response->message);
        }

        $response = $service->postTransaction(tx: $txB46Xdr, approvalServer: $goatAsset->approvalServer);
        self::assertTrue($response instanceof SEP08PostTransactionRevised);
        if ($response instanceof SEP08PostTransactionRevised) {
            assertEquals($txB46Xdr . $txB46Xdr, $response->tx);
            assertEquals('hello', $response->message);
        }

        $response = $service->postTransaction(tx: $txB46Xdr, approvalServer: $goatAsset->approvalServer);
        self::assertTrue($response instanceof SEP08PostTransactionPending);
        if ($response instanceof SEP08PostTransactionPending) {
            assertEquals(3, $response->timeout);
            assertEquals('hello', $response->message);
        }

        $response = $service->postTransaction(tx: $txB46Xdr, approvalServer: $goatAsset->approvalServer);
        self::assertTrue($response instanceof SEP08PostTransactionRejected);
        if ($response instanceof SEP08PostTransactionRejected) {
            assertEquals('hello', $response->error);
        }

        $response = $service->postTransaction(tx: $txB46Xdr, approvalServer: $goatAsset->approvalServer);
        self::assertTrue($response instanceof SEP08PostTransactionActionRequired);
        if ($response instanceof SEP08PostTransactionActionRequired) {
            assertEquals('hello', $response->message);
            assertEquals($actionUrl, $response->actionUrl);
            assertEquals('POST', $response->actionMethod);
            assertEquals(['email_address', 'mobile_number'], $response->actionFields);
        }

        // post action
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode([
                'result' => 'no_further_action_required'
            ])),
            new Response(200, ['X-Foo' => 'Bar'], json_encode([
                'result' => 'follow_next_url',
                'next_url' => $actionUrl,
                'message' => 'Please submit mobile number'
            ])),
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) use ($txB46Xdr) {
            $this->assertEquals("POST", $request->getMethod());
            $body = $request->getBody()->__toString();
            $jsonData = @json_decode($body, true);
            $this->assertEquals('test@mail.com', $jsonData['email_address']);
            $this->assertEquals('+3472829839222', $jsonData['mobile_number']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);

        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);
        $response = $service->postAction(url: $actionUrl, actionFields:[
            'email_address' => 'test@mail.com',
            'mobile_number' => '+3472829839222'],
        );
        self::assertTrue($response instanceof SEP08PostActionDone);

        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);
        $response = $service->postAction(
            url: $actionUrl,
            actionFields:[
                'email_address' => 'test@mail.com',
                'mobile_number' => '+3472829839222',
            ],
        );
        self::assertTrue($response instanceof SEP08PostActionNextUrl);
        if ($response instanceof SEP08PostActionNextUrl) {
            assertEquals('Please submit mobile number', $response->message);
            assertEquals($actionUrl, $response->nextUrl);
        }
    }
}
