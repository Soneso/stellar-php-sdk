<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Requests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\Offers\OffersPageResponse;
use Soneso\StellarSDK\Responses\Offers\OfferResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;

/**
 * Unit tests for OffersRequestBuilder
 *
 * Tests URL construction, query parameters, and filtering methods for the offers endpoint.
 * OffersRequestBuilder has 13 methods to test:
 * 1. offer()
 * 2. forAccount()
 * 3. forSponsor()
 * 4. forSeller()
 * 5. forSellingAsset()
 * 6. forBuyingAsset()
 * 7. cursor()
 * 8. limit()
 * 9. order()
 * 10. request()
 * 11. execute()
 * 12. stream()
 * 13. buildUrl() (inherited, tested via execute)
 */
class OffersRequestBuilderTest extends TestCase
{
    private function createMockClient(string &$requestedUrl, int $statusCode = 200, array $responseData = []): Client
    {
        $defaultResponse = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/offers'],
                'next' => ['href' => 'https://horizon.stellar.org/offers?cursor=next'],
                'prev' => ['href' => 'https://horizon.stellar.org/offers?cursor=prev']
            ],
            '_embedded' => [
                'records' => []
            ]
        ];

        $responseData = array_merge($defaultResponse, $responseData);

        $mock = new MockHandler([
            new Response($statusCode, ['Content-Type' => 'application/json'], json_encode($responseData))
        ]);

        $handlerStack = HandlerStack::create($mock);

        $history = [];
        $handlerStack->push(Middleware::history($history));

        $handlerStack->push(Middleware::mapRequest(function ($request) use (&$requestedUrl) {
            $requestedUrl = (string) $request->getUri();
            return $request;
        }));

        return new Client(['handler' => $handlerStack]);
    }

    private function createSingleOfferMockClient(string &$requestedUrl, int $statusCode = 200): Client
    {
        $offerData = [
            'id' => '123456',
            'paging_token' => '123456',
            'seller' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'selling' => [
                'asset_type' => 'native'
            ],
            'buying' => [
                'asset_type' => 'credit_alphanum4',
                'asset_code' => 'USD',
                'asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
            ],
            'amount' => '100.0000000',
            'price_r' => ['n' => 3, 'd' => 2],
            'price' => '1.5000000',
            'last_modified_ledger' => 12345,
            'last_modified_time' => '2024-01-15T10:30:45Z',
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/offers/123456'],
                'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7']
            ]
        ];

        $mock = new MockHandler([
            new Response($statusCode, ['Content-Type' => 'application/json'], json_encode($offerData))
        ]);

        $handlerStack = HandlerStack::create($mock);

        $history = [];
        $handlerStack->push(Middleware::history($history));

        $handlerStack->push(Middleware::mapRequest(function ($request) use (&$requestedUrl) {
            $requestedUrl = (string) $request->getUri();
            return $request;
        }));

        return new Client(['handler' => $handlerStack]);
    }

    public function testOffer(): void
    {
        $requestedUrl = '';
        $client = $this->createSingleOfferMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $response = $sdk->offers()->offer('123456');

        $this->assertInstanceOf(OfferResponse::class, $response);
        $this->assertStringContainsString('offers/123456', $requestedUrl);
        $this->assertEquals('123456', $response->getOfferId());
    }

    public function testForAccount(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $accountId = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
        $sdk->offers()->forAccount($accountId)->execute();

        $this->assertStringContainsString('accounts/' . $accountId . '/offers', $requestedUrl);
    }

    public function testForSponsor(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sponsorId = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';
        $sdk->offers()->forSponsor($sponsorId)->execute();

        $this->assertStringContainsString('sponsor=' . $sponsorId, $requestedUrl);
    }

    public function testForSeller(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sellerId = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
        $sdk->offers()->forSeller($sellerId)->execute();

        $this->assertStringContainsString('seller=' . $sellerId, $requestedUrl);
    }

    public function testForSellingAssetNative(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sellingAsset = Asset::native();
        $sdk->offers()->forSellingAsset($sellingAsset)->execute();

        $this->assertStringContainsString('selling_asset_type=native', $requestedUrl);
        $this->assertStringNotContainsString('selling_asset_code', $requestedUrl);
        $this->assertStringNotContainsString('selling_asset_issuer', $requestedUrl);
    }

    public function testForSellingAssetCreditAlphanum4(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sellingAsset = Asset::createNonNativeAsset('USD', 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX');
        $sdk->offers()->forSellingAsset($sellingAsset)->execute();

        $this->assertStringContainsString('selling_asset_type=credit_alphanum4', $requestedUrl);
        $this->assertStringContainsString('selling_asset_code=USD', $requestedUrl);
        $this->assertStringContainsString('selling_asset_issuer=GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $requestedUrl);
    }

    public function testForSellingAssetCreditAlphanum12(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sellingAsset = Asset::createNonNativeAsset('LONGASSET', 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX');
        $sdk->offers()->forSellingAsset($sellingAsset)->execute();

        $this->assertStringContainsString('selling_asset_type=credit_alphanum12', $requestedUrl);
        $this->assertStringContainsString('selling_asset_code=LONGASSET', $requestedUrl);
        $this->assertStringContainsString('selling_asset_issuer=GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $requestedUrl);
    }

    public function testForBuyingAssetNative(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $buyingAsset = Asset::native();
        $sdk->offers()->forBuyingAsset($buyingAsset)->execute();

        $this->assertStringContainsString('buying_asset_type=native', $requestedUrl);
        $this->assertStringNotContainsString('buying_asset_code', $requestedUrl);
        $this->assertStringNotContainsString('buying_asset_issuer', $requestedUrl);
    }

    public function testForBuyingAssetCreditAlphanum4(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $buyingAsset = Asset::createNonNativeAsset('EUR', 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN');
        $sdk->offers()->forBuyingAsset($buyingAsset)->execute();

        $this->assertStringContainsString('buying_asset_type=credit_alphanum4', $requestedUrl);
        $this->assertStringContainsString('buying_asset_code=EUR', $requestedUrl);
        $this->assertStringContainsString('buying_asset_issuer=GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN', $requestedUrl);
    }

    public function testForBuyingAssetCreditAlphanum12(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $buyingAsset = Asset::createNonNativeAsset('BITCOINTOKEN', 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN');
        $sdk->offers()->forBuyingAsset($buyingAsset)->execute();

        $this->assertStringContainsString('buying_asset_type=credit_alphanum12', $requestedUrl);
        $this->assertStringContainsString('buying_asset_code=BITCOINTOKEN', $requestedUrl);
        $this->assertStringContainsString('buying_asset_issuer=GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN', $requestedUrl);
    }

    public function testCursor(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->offers()->cursor('165563085')->execute();

        $this->assertStringContainsString('cursor=165563085', $requestedUrl);
    }

    public function testCursorNow(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->offers()->cursor('now')->execute();

        $this->assertStringContainsString('cursor=now', $requestedUrl);
    }

    public function testLimit(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->offers()->limit(100)->execute();

        $this->assertStringContainsString('limit=100', $requestedUrl);
    }

    public function testOrderAscending(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->offers()->order('asc')->execute();

        $this->assertStringContainsString('order=asc', $requestedUrl);
    }

    public function testOrderDescending(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->offers()->order('desc')->execute();

        $this->assertStringContainsString('order=desc', $requestedUrl);
    }

    public function testExecuteReturnsOffersPageResponse(): void
    {
        $requestedUrl = '';
        $responseData = [
            '_embedded' => [
                'records' => [
                    [
                        'id' => '165563085',
                        'paging_token' => '165563085',
                        'seller' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
                        'selling' => [
                            'asset_type' => 'native'
                        ],
                        'buying' => [
                            'asset_type' => 'credit_alphanum4',
                            'asset_code' => 'USD',
                            'asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
                        ],
                        'amount' => '100.0000000',
                        'price_r' => ['n' => 3, 'd' => 2],
                        'price' => '1.5000000',
                        'last_modified_ledger' => 12345,
                        'last_modified_time' => '2024-01-15T10:30:45Z',
                        '_links' => [
                            'self' => ['href' => 'https://horizon.stellar.org/offers/165563085'],
                            'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7']
                        ]
                    ]
                ]
            ]
        ];

        $client = $this->createMockClient($requestedUrl, 200, $responseData);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $response = $sdk->offers()->execute();

        $this->assertInstanceOf(OffersPageResponse::class, $response);
        $this->assertEquals(1, $response->getOffers()->count());
        $this->assertEquals('165563085', $response->getOffers()->toArray()[0]->getOfferId());
    }

    public function testRequest(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $customUrl = 'https://horizon.stellar.org/offers?cursor=test&limit=10';
        $response = $sdk->offers()->request($customUrl);

        $this->assertInstanceOf(OffersPageResponse::class, $response);
        $this->assertEquals($customUrl, $requestedUrl);
    }

    public function testMultipleFilters(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sellingAsset = Asset::native();
        $buyingAsset = Asset::createNonNativeAsset('USD', 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX');
        $sellerId = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';

        $sdk->offers()
            ->forSellingAsset($sellingAsset)
            ->forBuyingAsset($buyingAsset)
            ->forSeller($sellerId)
            ->limit(50)
            ->order('desc')
            ->cursor('now')
            ->execute();

        $this->assertStringContainsString('selling_asset_type=native', $requestedUrl);
        $this->assertStringContainsString('buying_asset_type=credit_alphanum4', $requestedUrl);
        $this->assertStringContainsString('buying_asset_code=USD', $requestedUrl);
        $this->assertStringContainsString('buying_asset_issuer=GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $requestedUrl);
        $this->assertStringContainsString('seller=' . $sellerId, $requestedUrl);
        $this->assertStringContainsString('limit=50', $requestedUrl);
        $this->assertStringContainsString('order=desc', $requestedUrl);
        $this->assertStringContainsString('cursor=now', $requestedUrl);
    }

    public function testForAccountWithMultipleFilters(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $accountId = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';

        $sdk->offers()
            ->forAccount($accountId)
            ->limit(25)
            ->order('asc')
            ->execute();

        $this->assertStringContainsString('accounts/' . $accountId . '/offers', $requestedUrl);
        $this->assertStringContainsString('limit=25', $requestedUrl);
        $this->assertStringContainsString('order=asc', $requestedUrl);
    }

    public function testForSponsorAndSeller(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sponsorId = 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6';
        $sellerId = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';

        $sdk->offers()
            ->forSponsor($sponsorId)
            ->forSeller($sellerId)
            ->execute();

        $this->assertStringContainsString('sponsor=' . $sponsorId, $requestedUrl);
        $this->assertStringContainsString('seller=' . $sellerId, $requestedUrl);
    }

    public function testBaseUrl(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->offers()->execute();

        $this->assertStringContainsString('offers', $requestedUrl);
    }
}
