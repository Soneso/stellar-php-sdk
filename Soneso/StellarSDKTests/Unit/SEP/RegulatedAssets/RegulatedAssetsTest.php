<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\SEP\RegulatedAssets;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAsset;
use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08IncompleteInitData;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08InvalidPostActionResponse;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08InvalidPostTransactionResponse;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostActionDone;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostActionNextUrl;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostActionResponse;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionActionRequired;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionPending;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionRejected;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionResponse;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionRevised;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionSuccess;
use Soneso\StellarSDK\SEP\Toml\StellarToml;

class RegulatedAssetsTest extends TestCase
{
    private string $validTomlWithNetworkAndHorizon = '
        VERSION="2.0.0"
        NETWORK_PASSPHRASE="Test SDF Network ; September 2015"
        HORIZON_URL="https://horizon-testnet.stellar.org"

        [[CURRENCIES]]
        code="USDC"
        issuer="GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5"
        regulated=true
        approval_server="https://usdc.io/tx_approve"
        approval_criteria="Compliant with US regulations"

        [[CURRENCIES]]
        code="EUR"
        issuer="GCZJM35NKGVK47BB4SPBDV25477PZYIYPVVG453LPYFNXLS3FGHDXOCM"
        regulated=true
        approval_server="https://eur.io/tx_approve"
        approval_criteria="EU KYC required"
    ';

    private string $tomlOnlyNetworkPassphrase = '
        VERSION="2.0.0"
        NETWORK_PASSPHRASE="Public Global Stellar Network ; September 2015"

        [[CURRENCIES]]
        code="XLM"
        issuer="GCZJM35NKGVK47BB4SPBDV25477PZYIYPVVG453LPYFNXLS3FGHDXOCM"
        regulated=true
        approval_server="https://xlm.io/tx_approve"
    ';

    private string $tomlMissingNetworkPassphrase = '
        VERSION="2.0.0"
        HORIZON_URL="https://horizon.stellar.org"

        [[CURRENCIES]]
        code="TEST"
        issuer="GCZJM35NKGVK47BB4SPBDV25477PZYIYPVVG453LPYFNXLS3FGHDXOCM"
        regulated=true
        approval_server="https://test.io/tx_approve"
    ';

    private string $tomlNoRegulatedAssets = '
        VERSION="2.0.0"
        NETWORK_PASSPHRASE="Test SDF Network ; September 2015"
        HORIZON_URL="https://horizon-testnet.stellar.org"

        [[CURRENCIES]]
        code="NORMAL"
        issuer="GCZJM35NKGVK47BB4SPBDV25477PZYIYPVVG453LPYFNXLS3FGHDXOCM"
        regulated=false
    ';

    private string $tomlIncompleteRegulatedAssets = '
        VERSION="2.0.0"
        NETWORK_PASSPHRASE="Test SDF Network ; September 2015"

        [[CURRENCIES]]
        code="INCOMPLETE1"
        regulated=true
        approval_server="https://incomplete.io/approve"

        [[CURRENCIES]]
        code="INCOMPLETE2"
        issuer="GCZJM35NKGVK47BB4SPBDV25477PZYIYPVVG453LPYFNXLS3FGHDXOCM"
        regulated=true
    ';

    private string $tomlCustomNetwork = '
        VERSION="2.0.0"
        NETWORK_PASSPHRASE="Custom Private Network ; 2026"
        HORIZON_URL="https://custom-horizon.example.com"

        [[CURRENCIES]]
        code="CUSTOM"
        issuer="GCZJM35NKGVK47BB4SPBDV25477PZYIYPVVG453LPYFNXLS3FGHDXOCM"
        regulated=true
        approval_server="https://custom.io/approve"
    ';

    public function testRegulatedAssetConstruction(): void
    {
        $asset = new RegulatedAsset(
            code: "USDC",
            issuer: "GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5",
            approvalServer: "https://usdc.io/tx_approve",
            approvalCriteria: "Compliant with US regulations"
        );

        self::assertEquals("USDC", $asset->getCode());
        self::assertEquals("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5", $asset->getIssuer());
        self::assertEquals("https://usdc.io/tx_approve", $asset->approvalServer);
        self::assertEquals("Compliant with US regulations", $asset->approvalCriteria);
        self::assertNotNull($asset->getType());
        self::assertNotNull($asset->toXdr());
    }

    public function testRegulatedAssetWithoutCriteria(): void
    {
        $asset = new RegulatedAsset(
            code: "EUR",
            issuer: "GCZJM35NKGVK47BB4SPBDV25477PZYIYPVVG453LPYFNXLS3FGHDXOCM",
            approvalServer: "https://eur.io/tx_approve"
        );

        self::assertEquals("EUR", $asset->getCode());
        self::assertNull($asset->approvalCriteria);
    }

    public function testRegulatedAssetShortCode(): void
    {
        $asset = new RegulatedAsset(
            code: "XLM",
            issuer: "GCZJM35NKGVK47BB4SPBDV25477PZYIYPVVG453LPYFNXLS3FGHDXOCM",
            approvalServer: "https://xlm.io/approve"
        );

        self::assertEquals("credit_alphanum4", $asset->getType());
    }

    public function testRegulatedAssetLongCode(): void
    {
        $asset = new RegulatedAsset(
            code: "LONGERNAME",
            issuer: "GCZJM35NKGVK47BB4SPBDV25477PZYIYPVVG453LPYFNXLS3FGHDXOCM",
            approvalServer: "https://long.io/approve"
        );

        self::assertEquals("credit_alphanum12", $asset->getType());
    }

    public function testServiceInitializationWithFullToml(): void
    {
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData);

        self::assertEquals(Network::testnet()->getNetworkPassphrase(), $service->network->getNetworkPassphrase());
        self::assertCount(2, $service->regulatedAssets);
        self::assertEquals("USDC", $service->regulatedAssets[0]->getCode());
        self::assertEquals("EUR", $service->regulatedAssets[1]->getCode());
    }

    public function testServiceInitializationWithExplicitHorizon(): void
    {
        $tomlData = new StellarToml(toml: $this->tomlOnlyNetworkPassphrase);
        $horizonUrl = "https://horizon.stellar.org";

        $service = new RegulatedAssetsService(
            tomlData: $tomlData,
            horizonUrl: $horizonUrl
        );

        self::assertEquals(Network::public()->getNetworkPassphrase(), $service->network->getNetworkPassphrase());
    }

    public function testServiceInitializationPublicNetworkDefault(): void
    {
        $tomlData = new StellarToml(toml: $this->tomlOnlyNetworkPassphrase);
        $service = new RegulatedAssetsService(tomlData: $tomlData);

        self::assertEquals(Network::public()->getNetworkPassphrase(), $service->network->getNetworkPassphrase());
        self::assertNotNull($service->sdk);
    }

    public function testServiceInitializationCustomNetwork(): void
    {
        $tomlData = new StellarToml(toml: $this->tomlCustomNetwork);
        $service = new RegulatedAssetsService(tomlData: $tomlData);

        self::assertEquals("Custom Private Network ; 2026", $service->network->getNetworkPassphrase());
        self::assertNotNull($service->sdk);
    }

    public function testServiceInitializationFailsWithoutNetworkPassphrase(): void
    {
        $this->expectException(SEP08IncompleteInitData::class);
        $this->expectExceptionMessage('could not find a network passphrase');

        $tomlData = new StellarToml(toml: $this->tomlMissingNetworkPassphrase);
        new RegulatedAssetsService(tomlData: $tomlData);
    }

    public function testServiceInitializationNoRegulatedAssets(): void
    {
        $tomlData = new StellarToml(toml: $this->tomlNoRegulatedAssets);
        $service = new RegulatedAssetsService(tomlData: $tomlData);

        self::assertCount(0, $service->regulatedAssets);
    }

    public function testServiceInitializationSkipsIncompleteAssets(): void
    {
        $tomlData = new StellarToml(toml: $this->tomlIncompleteRegulatedAssets);
        $service = new RegulatedAssetsService(tomlData: $tomlData);

        self::assertCount(0, $service->regulatedAssets);
    }

    public function testServiceFromDomain(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->validTomlWithNetworkAndHorizon)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $httpClient = new Client(['handler' => $stack]);

        $service = RegulatedAssetsService::fromDomain(
            domain: 'example.com',
            httpClient: $httpClient
        );

        self::assertCount(2, $service->regulatedAssets);
        self::assertEquals(Network::testnet()->getNetworkPassphrase(), $service->network->getNetworkPassphrase());
    }

    public function testPostTransactionSuccess(): void
    {
        $txXdr = 'AAAAAgAAAAA=';
        $approvalServer = 'https://approval.example.com/tx_approve';

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'success',
                'tx' => $txXdr,
                'message' => 'Transaction approved'
            ]))
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) use ($txXdr, $approvalServer) {
            self::assertEquals('POST', $request->getMethod());
            self::assertEquals($approvalServer, (string)$request->getUri());

            $body = json_decode($request->getBody()->__toString(), true);
            self::assertEquals($txXdr, $body['tx']);

            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $response = $service->postTransaction($txXdr, $approvalServer);

        self::assertInstanceOf(SEP08PostTransactionSuccess::class, $response);
        self::assertEquals($txXdr, $response->tx);
        self::assertEquals('Transaction approved', $response->message);
    }

    public function testPostTransactionSuccessWithoutMessage(): void
    {
        $txXdr = 'AAAAAgAAAAA=';
        $approvalServer = 'https://approval.example.com/tx_approve';

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'success',
                'tx' => $txXdr
            ]))
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $response = $service->postTransaction($txXdr, $approvalServer);

        self::assertInstanceOf(SEP08PostTransactionSuccess::class, $response);
        self::assertNull($response->message);
    }

    public function testPostTransactionRevised(): void
    {
        $txXdr = 'AAAAAgAAAAA=';
        $revisedTxXdr = 'AAAAAgAAAABREVISED=';
        $approvalServer = 'https://approval.example.com/tx_approve';

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'revised',
                'tx' => $revisedTxXdr,
                'message' => 'Transaction revised to add compliance operation'
            ]))
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $response = $service->postTransaction($txXdr, $approvalServer);

        self::assertInstanceOf(SEP08PostTransactionRevised::class, $response);
        self::assertEquals($revisedTxXdr, $response->tx);
        self::assertEquals('Transaction revised to add compliance operation', $response->message);
    }

    public function testPostTransactionPending(): void
    {
        $txXdr = 'AAAAAgAAAAA=';
        $approvalServer = 'https://approval.example.com/tx_approve';

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'pending',
                'timeout' => 5000,
                'message' => 'Manual review required'
            ]))
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $response = $service->postTransaction($txXdr, $approvalServer);

        self::assertInstanceOf(SEP08PostTransactionPending::class, $response);
        self::assertEquals(5000, $response->timeout);
        self::assertEquals('Manual review required', $response->message);
    }

    public function testPostTransactionPendingWithoutTimeout(): void
    {
        $txXdr = 'AAAAAgAAAAA=';
        $approvalServer = 'https://approval.example.com/tx_approve';

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'pending'
            ]))
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $response = $service->postTransaction($txXdr, $approvalServer);

        self::assertInstanceOf(SEP08PostTransactionPending::class, $response);
        self::assertEquals(0, $response->timeout);
        self::assertNull($response->message);
    }

    public function testPostTransactionRejected(): void
    {
        $txXdr = 'AAAAAgAAAAA=';
        $approvalServer = 'https://approval.example.com/tx_approve';

        $mock = new MockHandler([
            new Response(400, [], json_encode([
                'status' => 'rejected',
                'error' => 'Destination account is sanctioned'
            ]))
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $response = $service->postTransaction($txXdr, $approvalServer);

        self::assertInstanceOf(SEP08PostTransactionRejected::class, $response);
        self::assertEquals('Destination account is sanctioned', $response->error);
    }

    public function testPostTransactionActionRequired(): void
    {
        $txXdr = 'AAAAAgAAAAA=';
        $approvalServer = 'https://approval.example.com/tx_approve';

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'action_required',
                'message' => 'Additional KYC information required',
                'action_url' => 'https://approval.example.com/action',
                'action_method' => 'POST',
                'action_fields' => ['email_address', 'mobile_number', 'photo_id_front']
            ]))
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $response = $service->postTransaction($txXdr, $approvalServer);

        self::assertInstanceOf(SEP08PostTransactionActionRequired::class, $response);
        self::assertEquals('Additional KYC information required', $response->message);
        self::assertEquals('https://approval.example.com/action', $response->actionUrl);
        self::assertEquals('POST', $response->actionMethod);
        self::assertEquals(['email_address', 'mobile_number', 'photo_id_front'], $response->actionFields);
    }

    public function testPostTransactionActionRequiredWithGetMethod(): void
    {
        $txXdr = 'AAAAAgAAAAA=';
        $approvalServer = 'https://approval.example.com/tx_approve';

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'action_required',
                'message' => 'Complete KYC form',
                'action_url' => 'https://approval.example.com/kyc',
                'action_method' => 'GET'
            ]))
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $response = $service->postTransaction($txXdr, $approvalServer);

        self::assertInstanceOf(SEP08PostTransactionActionRequired::class, $response);
        self::assertEquals('GET', $response->actionMethod);
        self::assertNull($response->actionFields);
    }

    public function testPostTransactionInvalidResponseMissingStatus(): void
    {
        $this->expectException(SEP08InvalidPostTransactionResponse::class);
        $this->expectExceptionMessage('Missing status in response');

        $txXdr = 'AAAAAgAAAAA=';
        $approvalServer = 'https://approval.example.com/tx_approve';

        $mock = new MockHandler([
            new Response(200, [], json_encode(['tx' => $txXdr]))
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $service->postTransaction($txXdr, $approvalServer);
    }

    public function testPostTransactionInvalidResponseUnknownStatus(): void
    {
        $this->expectException(SEP08InvalidPostTransactionResponse::class);
        $this->expectExceptionMessage('Unknown status: invalid_status in response');

        $txXdr = 'AAAAAgAAAAA=';
        $approvalServer = 'https://approval.example.com/tx_approve';

        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'invalid_status']))
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $service->postTransaction($txXdr, $approvalServer);
    }

    public function testPostTransactionInvalidResponseSuccessMissingTx(): void
    {
        $this->expectException(SEP08InvalidPostTransactionResponse::class);
        $this->expectExceptionMessage('Missing tx in response');

        $txXdr = 'AAAAAgAAAAA=';
        $approvalServer = 'https://approval.example.com/tx_approve';

        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'success']))
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $service->postTransaction($txXdr, $approvalServer);
    }

    public function testPostTransactionInvalidResponseRevisedMissingTx(): void
    {
        $this->expectException(SEP08InvalidPostTransactionResponse::class);
        $this->expectExceptionMessage('Missing tx in response');

        $txXdr = 'AAAAAgAAAAA=';
        $approvalServer = 'https://approval.example.com/tx_approve';

        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'revised', 'message' => 'test']))
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $service->postTransaction($txXdr, $approvalServer);
    }

    public function testPostTransactionInvalidResponseRevisedMissingMessage(): void
    {
        $this->expectException(SEP08InvalidPostTransactionResponse::class);
        $this->expectExceptionMessage('Missing message in response');

        $txXdr = 'AAAAAgAAAAA=';
        $approvalServer = 'https://approval.example.com/tx_approve';

        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'revised', 'tx' => $txXdr]))
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $service->postTransaction($txXdr, $approvalServer);
    }

    public function testPostTransactionInvalidResponseRejectedMissingError(): void
    {
        $this->expectException(SEP08InvalidPostTransactionResponse::class);
        $this->expectExceptionCode(400);

        $txXdr = 'AAAAAgAAAAA=';
        $approvalServer = 'https://approval.example.com/tx_approve';

        $mock = new MockHandler([
            new Response(400, [], json_encode(['status' => 'rejected']))
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $service->postTransaction($txXdr, $approvalServer);
    }

    public function testPostTransactionInvalidResponseActionRequiredMissingMessage(): void
    {
        $this->expectException(SEP08InvalidPostTransactionResponse::class);
        $this->expectExceptionMessage('Missing message in response');

        $txXdr = 'AAAAAgAAAAA=';
        $approvalServer = 'https://approval.example.com/tx_approve';

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'action_required',
                'action_url' => 'https://example.com/action'
            ]))
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $service->postTransaction($txXdr, $approvalServer);
    }

    public function testPostTransactionInvalidResponseActionRequiredMissingActionUrl(): void
    {
        $this->expectException(SEP08InvalidPostTransactionResponse::class);
        $this->expectExceptionMessage('Missing action_url in response');

        $txXdr = 'AAAAAgAAAAA=';
        $approvalServer = 'https://approval.example.com/tx_approve';

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'action_required',
                'message' => 'Action required'
            ]))
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $service->postTransaction($txXdr, $approvalServer);
    }

    public function testPostTransactionInvalidJsonResponse(): void
    {
        $this->expectException(SEP08InvalidPostTransactionResponse::class);
        $this->expectExceptionCode(200);

        $txXdr = 'AAAAAgAAAAA=';
        $approvalServer = 'https://approval.example.com/tx_approve';

        $mock = new MockHandler([
            new Response(200, [], 'Invalid JSON content')
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $service->postTransaction($txXdr, $approvalServer);
    }

    public function testPostTransactionInvalidStatusCode(): void
    {
        $this->expectException(SEP08InvalidPostTransactionResponse::class);
        $this->expectExceptionCode(500);

        $txXdr = 'AAAAAgAAAAA=';
        $approvalServer = 'https://approval.example.com/tx_approve';

        $mock = new MockHandler([
            new Response(500, [], json_encode(['error' => 'Internal server error']))
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $service->postTransaction($txXdr, $approvalServer);
    }

    public function testPostActionDone(): void
    {
        $actionUrl = 'https://approval.example.com/action';
        $actionFields = [
            'email_address' => 'user@example.com',
            'mobile_number' => '+1234567890'
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'result' => 'no_further_action_required'
            ]))
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) use ($actionUrl, $actionFields) {
            self::assertEquals('POST', $request->getMethod());
            self::assertEquals($actionUrl, (string)$request->getUri());

            $body = json_decode($request->getBody()->__toString(), true);
            self::assertEquals($actionFields['email_address'], $body['email_address']);
            self::assertEquals($actionFields['mobile_number'], $body['mobile_number']);

            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $response = $service->postAction($actionUrl, $actionFields);

        self::assertInstanceOf(SEP08PostActionDone::class, $response);
    }

    public function testPostActionNextUrl(): void
    {
        $actionUrl = 'https://approval.example.com/action';
        $nextUrl = 'https://approval.example.com/kyc_form';
        $actionFields = ['email_address' => 'user@example.com'];

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'result' => 'follow_next_url',
                'next_url' => $nextUrl,
                'message' => 'Please complete identity verification'
            ]))
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $response = $service->postAction($actionUrl, $actionFields);

        self::assertInstanceOf(SEP08PostActionNextUrl::class, $response);
        self::assertEquals($nextUrl, $response->nextUrl);
        self::assertEquals('Please complete identity verification', $response->message);
    }

    public function testPostActionNextUrlWithoutMessage(): void
    {
        $actionUrl = 'https://approval.example.com/action';
        $nextUrl = 'https://approval.example.com/kyc_form';

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'result' => 'follow_next_url',
                'next_url' => $nextUrl
            ]))
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $response = $service->postAction($actionUrl, []);

        self::assertInstanceOf(SEP08PostActionNextUrl::class, $response);
        self::assertNull($response->message);
    }

    public function testPostActionInvalidResponseMissingResult(): void
    {
        $this->expectException(SEP08InvalidPostActionResponse::class);
        $this->expectExceptionMessage('Missing result in response');

        $actionUrl = 'https://approval.example.com/action';

        $mock = new MockHandler([
            new Response(200, [], json_encode(['message' => 'Some message']))
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $service->postAction($actionUrl, []);
    }

    public function testPostActionInvalidResponseUnknownResult(): void
    {
        $this->expectException(SEP08InvalidPostActionResponse::class);
        $this->expectExceptionMessage('Unknown result: invalid_result in response');

        $actionUrl = 'https://approval.example.com/action';

        $mock = new MockHandler([
            new Response(200, [], json_encode(['result' => 'invalid_result']))
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $service->postAction($actionUrl, []);
    }

    public function testPostActionInvalidResponseFollowNextUrlMissingUrl(): void
    {
        $this->expectException(SEP08InvalidPostActionResponse::class);
        $this->expectExceptionMessage('Missing next_url in response');

        $actionUrl = 'https://approval.example.com/action';

        $mock = new MockHandler([
            new Response(200, [], json_encode(['result' => 'follow_next_url']))
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $service->postAction($actionUrl, []);
    }

    public function testPostActionInvalidJsonResponse(): void
    {
        $this->expectException(SEP08InvalidPostActionResponse::class);
        $this->expectExceptionCode(200);

        $actionUrl = 'https://approval.example.com/action';

        $mock = new MockHandler([
            new Response(200, [], 'Invalid JSON')
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $service->postAction($actionUrl, []);
    }

    public function testPostActionInvalidStatusCode(): void
    {
        $this->expectException(SEP08InvalidPostActionResponse::class);
        $this->expectExceptionCode(500);

        $actionUrl = 'https://approval.example.com/action';

        $mock = new MockHandler([
            new Response(500, [], 'Internal Server Error')
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $service->postAction($actionUrl, []);
    }

    public function testPostTransactionResponseFromJsonSuccess(): void
    {
        $json = [
            'status' => 'success',
            'tx' => 'AAAAAgAAAAA=',
            'message' => 'Approved'
        ];

        $response = SEP08PostTransactionResponse::fromJson($json);

        self::assertInstanceOf(SEP08PostTransactionSuccess::class, $response);
    }

    public function testPostTransactionResponseFromJsonRevised(): void
    {
        $json = [
            'status' => 'revised',
            'tx' => 'AAAAAgAAAABREVISED=',
            'message' => 'Revised'
        ];

        $response = SEP08PostTransactionResponse::fromJson($json);

        self::assertInstanceOf(SEP08PostTransactionRevised::class, $response);
    }

    public function testPostTransactionResponseFromJsonPending(): void
    {
        $json = [
            'status' => 'pending',
            'timeout' => 1000,
            'message' => 'Please wait'
        ];

        $response = SEP08PostTransactionResponse::fromJson($json);

        self::assertInstanceOf(SEP08PostTransactionPending::class, $response);
    }

    public function testPostTransactionResponseFromJsonRejected(): void
    {
        $json = [
            'status' => 'rejected',
            'error' => 'Not compliant'
        ];

        $response = SEP08PostTransactionResponse::fromJson($json);

        self::assertInstanceOf(SEP08PostTransactionRejected::class, $response);
    }

    public function testPostTransactionResponseFromJsonActionRequired(): void
    {
        $json = [
            'status' => 'action_required',
            'message' => 'Action needed',
            'action_url' => 'https://example.com/action',
            'action_method' => 'POST',
            'action_fields' => ['email_address']
        ];

        $response = SEP08PostTransactionResponse::fromJson($json);

        self::assertInstanceOf(SEP08PostTransactionActionRequired::class, $response);
    }

    public function testPostActionResponseFromJsonDone(): void
    {
        $json = ['result' => 'no_further_action_required'];

        $response = SEP08PostActionResponse::fromJson($json);

        self::assertInstanceOf(SEP08PostActionDone::class, $response);
    }

    public function testPostActionResponseFromJsonNextUrl(): void
    {
        $json = [
            'result' => 'follow_next_url',
            'next_url' => 'https://example.com/next',
            'message' => 'Continue here'
        ];

        $response = SEP08PostActionResponse::fromJson($json);

        self::assertInstanceOf(SEP08PostActionNextUrl::class, $response);
    }

    public function testPostTransactionPendingDefaultTimeout(): void
    {
        $pending = new SEP08PostTransactionPending();

        self::assertEquals(0, $pending->timeout);
        self::assertNull($pending->message);
    }

    public function testPostTransactionPendingWithTimeout(): void
    {
        $pending = new SEP08PostTransactionPending(timeout: 3000, message: 'Wait 3 seconds');

        self::assertEquals(3000, $pending->timeout);
        self::assertEquals('Wait 3 seconds', $pending->message);
    }

    public function testPostTransactionActionRequiredDefaultMethod(): void
    {
        $actionRequired = new SEP08PostTransactionActionRequired(
            message: 'Action needed',
            actionUrl: 'https://example.com/action'
        );

        self::assertEquals('GET', $actionRequired->actionMethod);
        self::assertNull($actionRequired->actionFields);
    }

    public function testComplexActionFieldsArray(): void
    {
        $actionUrl = 'https://approval.example.com/action';
        $complexFields = [
            'email_address' => 'test@example.com',
            'mobile_number' => '+1234567890',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_country_code' => 'US',
            'address_city' => 'New York',
            'address_postal_code' => '10001',
            'address_street_address' => '123 Main St',
            'birth_date' => '1990-01-01',
            'bank_account_number' => '1234567890',
            'bank_routing_number' => '021000021'
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'result' => 'no_further_action_required'
            ]))
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) use ($complexFields) {
            $body = json_decode($request->getBody()->__toString(), true);

            foreach ($complexFields as $key => $value) {
                self::assertArrayHasKey($key, $body);
                self::assertEquals($value, $body[$key]);
            }

            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $response = $service->postAction($actionUrl, $complexFields);

        self::assertInstanceOf(SEP08PostActionDone::class, $response);
    }

    public function testServiceInitializationSetsTomlData(): void
    {
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData);
        self::assertSame($tomlData, $service->tomlData);
    }

    public function testPostTransactionActionRequiredDefaultsActionMethodToGet(): void
    {
        $txXdr = 'AAAAAgAAAAA=';
        $approvalServer = 'https://approval.example.com/tx_approve';

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'action_required',
                'message' => 'KYC required',
                'action_url' => 'https://approval.example.com/kyc',
            ]))
        ]);

        $httpClient = new Client(['handler' => new HandlerStack($mock)]);
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $service = new RegulatedAssetsService(tomlData: $tomlData, httpClient: $httpClient);

        $response = $service->postTransaction($txXdr, $approvalServer);

        self::assertInstanceOf(SEP08PostTransactionActionRequired::class, $response);
        self::assertEquals('GET', $response->actionMethod);
        self::assertNull($response->actionFields);
    }

    public function testPostTransactionResponseFromJsonActionRequiredDefaultMethod(): void
    {
        $json = [
            'status' => 'action_required',
            'message' => 'Action needed',
            'action_url' => 'https://example.com/action',
        ];

        $response = SEP08PostTransactionResponse::fromJson($json);

        self::assertInstanceOf(SEP08PostTransactionActionRequired::class, $response);
        self::assertEquals('GET', $response->actionMethod);
    }

    public function testServiceInitializationWithExplicitNetwork(): void
    {
        $tomlData = new StellarToml(toml: $this->validTomlWithNetworkAndHorizon);
        $network = Network::testnet();

        $service = new RegulatedAssetsService(
            tomlData: $tomlData,
            network: $network
        );

        self::assertSame($network, $service->network);
        self::assertNotNull($service->sdk);
    }

    public function testServiceInitializationFailsWithoutNetworkFromAnySource(): void
    {
        $this->expectException(SEP08IncompleteInitData::class);
        $this->expectExceptionMessage('could not find a network passphrase');

        $tomlData = new StellarToml(toml: $this->tomlMissingNetworkPassphrase);
        new RegulatedAssetsService(tomlData: $tomlData);
    }
}
