<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Responses;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Responses\Offers\OfferResponse;
use Soneso\StellarSDK\Responses\Offers\OffersPageResponse;
use Soneso\StellarSDK\Responses\Offers\OffersResponse;
use Soneso\StellarSDK\Responses\Offers\OfferPriceResponse;
use Soneso\StellarSDK\Responses\Offers\OfferLinksResponse;
use Soneso\StellarSDK\Asset;

/**
 * Unit tests for all Offer Response classes
 *
 * Tests JSON parsing and getter methods for Offer response classes.
 * Covers OfferResponse (10 methods), OfferPriceResponse (2 methods),
 * OfferLinksResponse (2 methods), OffersResponse (collection methods),
 * and OffersPageResponse (pagination methods).
 */
class OfferResponseTest extends TestCase
{
    // OfferPriceResponse Tests

    public function testOfferPriceResponseFromJson(): void
    {
        $json = [
            'n' => 7,
            'd' => 3
        ];

        $response = OfferPriceResponse::fromJson($json);

        $this->assertEquals(7, $response->getN());
        $this->assertEquals(3, $response->getD());
    }

    public function testOfferPriceResponseWithLargeNumbers(): void
    {
        $json = [
            'n' => 1000000000,
            'd' => 333333333
        ];

        $response = OfferPriceResponse::fromJson($json);

        $this->assertEquals(1000000000, $response->getN());
        $this->assertEquals(333333333, $response->getD());
    }

    public function testOfferPriceResponseWithSmallFraction(): void
    {
        $json = [
            'n' => 1,
            'd' => 100000
        ];

        $response = OfferPriceResponse::fromJson($json);

        $this->assertEquals(1, $response->getN());
        $this->assertEquals(100000, $response->getD());
    }

    public function testOfferPriceResponseWithEqualValues(): void
    {
        $json = [
            'n' => 1,
            'd' => 1
        ];

        $response = OfferPriceResponse::fromJson($json);

        $this->assertEquals(1, $response->getN());
        $this->assertEquals(1, $response->getD());
    }

    // OfferLinksResponse Tests

    public function testOfferLinksResponseFromJson(): void
    {
        $json = [
            'self' => ['href' => 'https://horizon.stellar.org/offers/121693057'],
            'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/GCO2IP3MJNUOKS4PUDI4C7LGGMQDJGXG3COYX3WSB4HHNAHKYV5YL3VC']
        ];

        $response = OfferLinksResponse::fromJson($json);

        $this->assertNotNull($response->getSelf());
        $this->assertEquals('https://horizon.stellar.org/offers/121693057', $response->getSelf()->getHref());
        $this->assertNotNull($response->getOfferMaker());
        $this->assertEquals('https://horizon.stellar.org/accounts/GCO2IP3MJNUOKS4PUDI4C7LGGMQDJGXG3COYX3WSB4HHNAHKYV5YL3VC', $response->getOfferMaker()->getHref());
    }

    public function testOfferLinksResponseWithTemplatedLinks(): void
    {
        $json = [
            'self' => ['href' => 'https://horizon.stellar.org/offers/121693057', 'templated' => false],
            'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/{account_id}', 'templated' => true]
        ];

        $response = OfferLinksResponse::fromJson($json);

        $this->assertNotNull($response->getSelf());
        $this->assertFalse($response->getSelf()->isTemplated());
        $this->assertNotNull($response->getOfferMaker());
        $this->assertTrue($response->getOfferMaker()->isTemplated());
    }

    // OfferResponse Tests - Native Asset

    public function testOfferResponseWithNativeAssets(): void
    {
        $json = [
            'id' => '121693057',
            'paging_token' => '121693057',
            'seller' => 'GCO2IP3MJNUOKS4PUDI4C7LGGMQDJGXG3COYX3WSB4HHNAHKYV5YL3VC',
            'selling' => [
                'asset_type' => 'native'
            ],
            'buying' => [
                'asset_type' => 'native'
            ],
            'amount' => '100.0000000',
            'price' => '0.5000000',
            'price_r' => [
                'n' => 1,
                'd' => 2
            ],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/offers/121693057'],
                'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/GCO2IP3MJNUOKS4PUDI4C7LGGMQDJGXG3COYX3WSB4HHNAHKYV5YL3VC']
            ]
        ];

        $response = OfferResponse::fromJson($json);

        $this->assertInstanceOf(OfferLinksResponse::class, $response->getLinks());
        $this->assertEquals('121693057', $response->getOfferId());
        $this->assertEquals('121693057', $response->getPagingToken());
        $this->assertEquals('GCO2IP3MJNUOKS4PUDI4C7LGGMQDJGXG3COYX3WSB4HHNAHKYV5YL3VC', $response->getSeller());
        $this->assertInstanceOf(Asset::class, $response->getSelling());
        $this->assertEquals('native', $response->getSelling()->getType());
        $this->assertInstanceOf(Asset::class, $response->getBuying());
        $this->assertEquals('native', $response->getBuying()->getType());
        $this->assertEquals('100.0000000', $response->getAmount());
        $this->assertEquals('0.5000000', $response->getPrice());
        $this->assertInstanceOf(OfferPriceResponse::class, $response->getPriceR());
        $this->assertEquals(1, $response->getPriceR()->getN());
        $this->assertEquals(2, $response->getPriceR()->getD());
        $this->assertNull($response->getSponsor());
    }

    public function testOfferResponseWithCreditAlphanum4Assets(): void
    {
        $json = [
            'id' => '121693058',
            'paging_token' => '121693058',
            'seller' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX',
            'selling' => [
                'asset_type' => 'credit_alphanum4',
                'asset_code' => 'USD',
                'asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
            ],
            'buying' => [
                'asset_type' => 'native'
            ],
            'amount' => '500.0000000',
            'price' => '1.2500000',
            'price_r' => [
                'n' => 5,
                'd' => 4
            ],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/offers/121693058'],
                'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX']
            ]
        ];

        $response = OfferResponse::fromJson($json);

        $this->assertEquals('121693058', $response->getOfferId());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getSeller());
        $this->assertEquals('credit_alphanum4', $response->getSelling()->getType());
        $this->assertEquals('USD', $response->getSelling()->getCode());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getSelling()->getIssuer());
        $this->assertEquals('native', $response->getBuying()->getType());
        $this->assertEquals('500.0000000', $response->getAmount());
        $this->assertEquals('1.2500000', $response->getPrice());
        $this->assertEquals(5, $response->getPriceR()->getN());
        $this->assertEquals(4, $response->getPriceR()->getD());
    }

    public function testOfferResponseWithCreditAlphanum12Assets(): void
    {
        $json = [
            'id' => '121693059',
            'paging_token' => '121693059',
            'seller' => 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
            'selling' => [
                'asset_type' => 'credit_alphanum12',
                'asset_code' => 'LONGCOINNAME',
                'asset_issuer' => 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN'
            ],
            'buying' => [
                'asset_type' => 'credit_alphanum4',
                'asset_code' => 'EUR',
                'asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
            ],
            'amount' => '1000.0000000',
            'price' => '2.5000000',
            'price_r' => [
                'n' => 5,
                'd' => 2
            ],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/offers/121693059'],
                'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN']
            ]
        ];

        $response = OfferResponse::fromJson($json);

        $this->assertEquals('121693059', $response->getOfferId());
        $this->assertEquals('GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN', $response->getSeller());
        $this->assertEquals('credit_alphanum12', $response->getSelling()->getType());
        $this->assertEquals('LONGCOINNAME', $response->getSelling()->getCode());
        $this->assertEquals('GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN', $response->getSelling()->getIssuer());
        $this->assertEquals('credit_alphanum4', $response->getBuying()->getType());
        $this->assertEquals('EUR', $response->getBuying()->getCode());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getBuying()->getIssuer());
        $this->assertEquals('1000.0000000', $response->getAmount());
        $this->assertEquals('2.5000000', $response->getPrice());
    }

    public function testOfferResponseWithSponsor(): void
    {
        $json = [
            'id' => '121693060',
            'paging_token' => '121693060',
            'seller' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'selling' => [
                'asset_type' => 'native'
            ],
            'buying' => [
                'asset_type' => 'credit_alphanum4',
                'asset_code' => 'USDC',
                'asset_issuer' => 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN'
            ],
            'amount' => '250.0000000',
            'price' => '1.0000000',
            'price_r' => [
                'n' => 1,
                'd' => 1
            ],
            'sponsor' => 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6',
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/offers/121693060'],
                'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7']
            ]
        ];

        $response = OfferResponse::fromJson($json);

        $this->assertEquals('121693060', $response->getOfferId());
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $response->getSeller());
        $this->assertEquals('GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6', $response->getSponsor());
        $this->assertEquals('250.0000000', $response->getAmount());
        $this->assertEquals('1.0000000', $response->getPrice());
    }

    public function testOfferResponseWithLargeAmount(): void
    {
        $json = [
            'id' => '121693061',
            'paging_token' => '121693061',
            'seller' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'selling' => [
                'asset_type' => 'native'
            ],
            'buying' => [
                'asset_type' => 'native'
            ],
            'amount' => '922337203685.4775807',
            'price' => '1.0000000',
            'price_r' => [
                'n' => 1,
                'd' => 1
            ],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/offers/121693061'],
                'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7']
            ]
        ];

        $response = OfferResponse::fromJson($json);

        $this->assertEquals('922337203685.4775807', $response->getAmount());
    }

    public function testOfferResponseWithVerySmallAmount(): void
    {
        $json = [
            'id' => '121693062',
            'paging_token' => '121693062',
            'seller' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'selling' => [
                'asset_type' => 'native'
            ],
            'buying' => [
                'asset_type' => 'native'
            ],
            'amount' => '0.0000001',
            'price' => '1.0000000',
            'price_r' => [
                'n' => 1,
                'd' => 1
            ],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/offers/121693062'],
                'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7']
            ]
        ];

        $response = OfferResponse::fromJson($json);

        $this->assertEquals('0.0000001', $response->getAmount());
    }

    public function testOfferResponseWithHighPrice(): void
    {
        $json = [
            'id' => '121693063',
            'paging_token' => '121693063',
            'seller' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'selling' => [
                'asset_type' => 'native'
            ],
            'buying' => [
                'asset_type' => 'credit_alphanum4',
                'asset_code' => 'BTC',
                'asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
            ],
            'amount' => '100.0000000',
            'price' => '50000.0000000',
            'price_r' => [
                'n' => 50000,
                'd' => 1
            ],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/offers/121693063'],
                'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7']
            ]
        ];

        $response = OfferResponse::fromJson($json);

        $this->assertEquals('50000.0000000', $response->getPrice());
        $this->assertEquals(50000, $response->getPriceR()->getN());
        $this->assertEquals(1, $response->getPriceR()->getD());
    }

    public function testOfferResponseWithVeryLowPrice(): void
    {
        $json = [
            'id' => '121693064',
            'paging_token' => '121693064',
            'seller' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'selling' => [
                'asset_type' => 'credit_alphanum4',
                'asset_code' => 'SHIB',
                'asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
            ],
            'buying' => [
                'asset_type' => 'native'
            ],
            'amount' => '1000000.0000000',
            'price' => '0.0000001',
            'price_r' => [
                'n' => 1,
                'd' => 10000000
            ],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/offers/121693064'],
                'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7']
            ]
        ];

        $response = OfferResponse::fromJson($json);

        $this->assertEquals('0.0000001', $response->getPrice());
        $this->assertEquals(1, $response->getPriceR()->getN());
        $this->assertEquals(10000000, $response->getPriceR()->getD());
    }

    // OffersResponse Tests

    public function testOffersResponseCollection(): void
    {
        $offer1 = OfferResponse::fromJson([
            'id' => '1',
            'paging_token' => '1',
            'seller' => 'GA1',
            'selling' => ['asset_type' => 'native'],
            'buying' => ['asset_type' => 'native'],
            'amount' => '100.0000000',
            'price' => '1.0000000',
            'price_r' => ['n' => 1, 'd' => 1],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/offers/1'],
                'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/GA1']
            ]
        ]);

        $offer2 = OfferResponse::fromJson([
            'id' => '2',
            'paging_token' => '2',
            'seller' => 'GA2',
            'selling' => ['asset_type' => 'native'],
            'buying' => ['asset_type' => 'native'],
            'amount' => '200.0000000',
            'price' => '2.0000000',
            'price_r' => ['n' => 2, 'd' => 1],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/offers/2'],
                'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/GA2']
            ]
        ]);

        $collection = new OffersResponse($offer1, $offer2);

        $this->assertEquals(2, $collection->count());

        $array = $collection->toArray();
        $this->assertCount(2, $array);
        $this->assertInstanceOf(OfferResponse::class, $array[0]);
        $this->assertInstanceOf(OfferResponse::class, $array[1]);
        $this->assertEquals('1', $array[0]->getOfferId());
        $this->assertEquals('2', $array[1]->getOfferId());

        $offer3 = OfferResponse::fromJson([
            'id' => '3',
            'paging_token' => '3',
            'seller' => 'GA3',
            'selling' => ['asset_type' => 'native'],
            'buying' => ['asset_type' => 'native'],
            'amount' => '300.0000000',
            'price' => '3.0000000',
            'price_r' => ['n' => 3, 'd' => 1],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/offers/3'],
                'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/GA3']
            ]
        ]);

        $collection->add($offer3);
        $this->assertEquals(3, $collection->count());

        $iteratedIds = [];
        foreach ($collection as $offer) {
            $iteratedIds[] = $offer->getOfferId();
        }
        $this->assertEquals(['1', '2', '3'], $iteratedIds);
    }

    public function testOffersResponseEmptyCollection(): void
    {
        $collection = new OffersResponse();

        $this->assertEquals(0, $collection->count());
        $this->assertEmpty($collection->toArray());
    }

    public function testOffersResponseIteration(): void
    {
        $offer1 = OfferResponse::fromJson([
            'id' => '100',
            'paging_token' => '100',
            'seller' => 'GA100',
            'selling' => ['asset_type' => 'native'],
            'buying' => ['asset_type' => 'native'],
            'amount' => '100.0000000',
            'price' => '1.0000000',
            'price_r' => ['n' => 1, 'd' => 1],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/offers/100'],
                'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/GA100']
            ]
        ]);

        $offer2 = OfferResponse::fromJson([
            'id' => '200',
            'paging_token' => '200',
            'seller' => 'GA200',
            'selling' => ['asset_type' => 'native'],
            'buying' => ['asset_type' => 'native'],
            'amount' => '200.0000000',
            'price' => '2.0000000',
            'price_r' => ['n' => 2, 'd' => 1],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/offers/200'],
                'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/GA200']
            ]
        ]);

        $collection = new OffersResponse($offer1, $offer2);

        $count = 0;
        foreach ($collection as $offer) {
            $this->assertInstanceOf(OfferResponse::class, $offer);
            $count++;
        }
        $this->assertEquals(2, $count);
    }

    // OffersPageResponse Tests

    public function testOffersPageResponseFromJson(): void
    {
        $json = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/offers?cursor=&limit=2&order=asc'],
                'next' => ['href' => 'https://horizon.stellar.org/offers?cursor=121693058&limit=2&order=asc'],
                'prev' => ['href' => 'https://horizon.stellar.org/offers?cursor=121693057&limit=2&order=desc']
            ],
            '_embedded' => [
                'records' => [
                    [
                        'id' => '121693057',
                        'paging_token' => '121693057',
                        'seller' => 'GCO2IP3MJNUOKS4PUDI4C7LGGMQDJGXG3COYX3WSB4HHNAHKYV5YL3VC',
                        'selling' => ['asset_type' => 'native'],
                        'buying' => [
                            'asset_type' => 'credit_alphanum4',
                            'asset_code' => 'USD',
                            'asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
                        ],
                        'amount' => '100.0000000',
                        'price' => '1.2500000',
                        'price_r' => ['n' => 5, 'd' => 4],
                        '_links' => [
                            'self' => ['href' => 'https://horizon.stellar.org/offers/121693057'],
                            'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/GCO2IP3MJNUOKS4PUDI4C7LGGMQDJGXG3COYX3WSB4HHNAHKYV5YL3VC']
                        ]
                    ],
                    [
                        'id' => '121693058',
                        'paging_token' => '121693058',
                        'seller' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
                        'selling' => [
                            'asset_type' => 'credit_alphanum4',
                            'asset_code' => 'EUR',
                            'asset_issuer' => 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN'
                        ],
                        'buying' => ['asset_type' => 'native'],
                        'amount' => '200.0000000',
                        'price' => '0.8000000',
                        'price_r' => ['n' => 4, 'd' => 5],
                        'sponsor' => 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6',
                        '_links' => [
                            'self' => ['href' => 'https://horizon.stellar.org/offers/121693058'],
                            'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7']
                        ]
                    ]
                ]
            ]
        ];

        $response = OffersPageResponse::fromJson($json);

        $this->assertInstanceOf(OffersResponse::class, $response->getOffers());
        $this->assertEquals(2, $response->getOffers()->count());

        $offers = $response->getOffers()->toArray();
        $this->assertEquals('121693057', $offers[0]->getOfferId());
        $this->assertEquals('GCO2IP3MJNUOKS4PUDI4C7LGGMQDJGXG3COYX3WSB4HHNAHKYV5YL3VC', $offers[0]->getSeller());
        $this->assertEquals('100.0000000', $offers[0]->getAmount());
        $this->assertNull($offers[0]->getSponsor());

        $this->assertEquals('121693058', $offers[1]->getOfferId());
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $offers[1]->getSeller());
        $this->assertEquals('200.0000000', $offers[1]->getAmount());
        $this->assertEquals('GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6', $offers[1]->getSponsor());

        $this->assertEquals('https://horizon.stellar.org/offers?cursor=121693058&limit=2&order=asc', $response->getLinks()->getNext()->getHref());
        $this->assertEquals('https://horizon.stellar.org/offers?cursor=121693057&limit=2&order=desc', $response->getLinks()->getPrev()->getHref());
    }

    public function testOffersPageResponseWithEmptyRecords(): void
    {
        $json = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/offers?limit=10'],
                'next' => ['href' => 'https://horizon.stellar.org/offers?cursor=&limit=10'],
                'prev' => ['href' => 'https://horizon.stellar.org/offers?cursor=&limit=10']
            ],
            '_embedded' => [
                'records' => []
            ]
        ];

        $response = OffersPageResponse::fromJson($json);

        $this->assertInstanceOf(OffersResponse::class, $response->getOffers());
        $this->assertEquals(0, $response->getOffers()->count());
    }

    public function testOffersPageResponseWithSingleRecord(): void
    {
        $json = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/offers?limit=1'],
                'next' => ['href' => 'https://horizon.stellar.org/offers?cursor=121693057&limit=1'],
                'prev' => ['href' => 'https://horizon.stellar.org/offers?cursor=121693057&limit=1']
            ],
            '_embedded' => [
                'records' => [
                    [
                        'id' => '121693057',
                        'paging_token' => '121693057',
                        'seller' => 'GCO2IP3MJNUOKS4PUDI4C7LGGMQDJGXG3COYX3WSB4HHNAHKYV5YL3VC',
                        'selling' => ['asset_type' => 'native'],
                        'buying' => [
                            'asset_type' => 'credit_alphanum4',
                            'asset_code' => 'USD',
                            'asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
                        ],
                        'amount' => '100.0000000',
                        'price' => '1.0000000',
                        'price_r' => ['n' => 1, 'd' => 1],
                        '_links' => [
                            'self' => ['href' => 'https://horizon.stellar.org/offers/121693057'],
                            'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/GCO2IP3MJNUOKS4PUDI4C7LGGMQDJGXG3COYX3WSB4HHNAHKYV5YL3VC']
                        ]
                    ]
                ]
            ]
        ];

        $response = OffersPageResponse::fromJson($json);

        $this->assertEquals(1, $response->getOffers()->count());

        $offers = $response->getOffers()->toArray();
        $this->assertEquals('121693057', $offers[0]->getOfferId());
        $this->assertEquals('100.0000000', $offers[0]->getAmount());
    }

    public function testOffersPageResponseWithMultipleAssetTypes(): void
    {
        $json = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/offers?limit=3'],
                'next' => ['href' => 'https://horizon.stellar.org/offers?cursor=3&limit=3'],
                'prev' => ['href' => 'https://horizon.stellar.org/offers?cursor=1&limit=3']
            ],
            '_embedded' => [
                'records' => [
                    [
                        'id' => '1',
                        'paging_token' => '1',
                        'seller' => 'GA1',
                        'selling' => ['asset_type' => 'native'],
                        'buying' => ['asset_type' => 'native'],
                        'amount' => '100.0000000',
                        'price' => '1.0000000',
                        'price_r' => ['n' => 1, 'd' => 1],
                        '_links' => [
                            'self' => ['href' => 'https://horizon.stellar.org/offers/1'],
                            'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/GA1']
                        ]
                    ],
                    [
                        'id' => '2',
                        'paging_token' => '2',
                        'seller' => 'GA2',
                        'selling' => [
                            'asset_type' => 'credit_alphanum4',
                            'asset_code' => 'USD',
                            'asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
                        ],
                        'buying' => ['asset_type' => 'native'],
                        'amount' => '200.0000000',
                        'price' => '1.0000000',
                        'price_r' => ['n' => 1, 'd' => 1],
                        '_links' => [
                            'self' => ['href' => 'https://horizon.stellar.org/offers/2'],
                            'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/GA2']
                        ]
                    ],
                    [
                        'id' => '3',
                        'paging_token' => '3',
                        'seller' => 'GA3',
                        'selling' => [
                            'asset_type' => 'credit_alphanum12',
                            'asset_code' => 'STELLARTOKEN',
                            'asset_issuer' => 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN'
                        ],
                        'buying' => [
                            'asset_type' => 'credit_alphanum4',
                            'asset_code' => 'EUR',
                            'asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
                        ],
                        'amount' => '300.0000000',
                        'price' => '1.0000000',
                        'price_r' => ['n' => 1, 'd' => 1],
                        '_links' => [
                            'self' => ['href' => 'https://horizon.stellar.org/offers/3'],
                            'offer_maker' => ['href' => 'https://horizon.stellar.org/accounts/GA3']
                        ]
                    ]
                ]
            ]
        ];

        $response = OffersPageResponse::fromJson($json);

        $this->assertEquals(3, $response->getOffers()->count());

        $offers = $response->getOffers()->toArray();
        $this->assertEquals('native', $offers[0]->getSelling()->getType());
        $this->assertEquals('credit_alphanum4', $offers[1]->getSelling()->getType());
        $this->assertEquals('USD', $offers[1]->getSelling()->getCode());
        $this->assertEquals('credit_alphanum12', $offers[2]->getSelling()->getType());
        $this->assertEquals('STELLARTOKEN', $offers[2]->getSelling()->getCode());
    }

    public function testOffersPageResponsePaginationLinks(): void
    {
        $json = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/offers?order=asc&limit=10&cursor='],
                'next' => ['href' => 'https://horizon.stellar.org/offers?order=asc&limit=10&cursor=next_cursor'],
                'prev' => ['href' => 'https://horizon.stellar.org/offers?order=desc&limit=10&cursor=prev_cursor']
            ],
            '_embedded' => [
                'records' => []
            ]
        ];

        $response = OffersPageResponse::fromJson($json);

        $this->assertNotNull($response->getLinks()->getSelf());
        $this->assertNotNull($response->getLinks()->getNext());
        $this->assertNotNull($response->getLinks()->getPrev());
        $this->assertEquals('https://horizon.stellar.org/offers?order=asc&limit=10&cursor=', $response->getLinks()->getSelf()->getHref());
        $this->assertEquals('https://horizon.stellar.org/offers?order=asc&limit=10&cursor=next_cursor', $response->getLinks()->getNext()->getHref());
        $this->assertEquals('https://horizon.stellar.org/offers?order=desc&limit=10&cursor=prev_cursor', $response->getLinks()->getPrev()->getHref());
    }
}
