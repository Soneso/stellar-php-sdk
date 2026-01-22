<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Responses;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\Offers\OfferPriceResponse;
use Soneso\StellarSDK\Responses\OrderBook\OrderBookResponse;
use Soneso\StellarSDK\Responses\OrderBook\OrderBookRowResponse;
use Soneso\StellarSDK\Responses\OrderBook\OrderBookRowsResponse;

/**
 * Unit tests for OrderBook Response classes
 *
 * Tests JSON parsing and getter methods for OrderBookResponse,
 * OrderBookRowResponse, and OrderBookRowsResponse.
 */
class OrderBookResponseTest extends TestCase
{
    // OrderBookResponse Tests

    public function testOrderBookResponseBasic(): void
    {
        $json = [
            'base' => [
                'asset_type' => 'native'
            ],
            'counter' => [
                'asset_type' => 'credit_alphanum4',
                'asset_code' => 'USD',
                'asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
            ],
            'bids' => [
                [
                    'price' => '0.5000000',
                    'amount' => '1000.0000000',
                    'price_r' => [
                        'n' => 1,
                        'd' => 2
                    ]
                ]
            ],
            'asks' => [
                [
                    'price' => '0.5100000',
                    'amount' => '900.0000000',
                    'price_r' => [
                        'n' => 51,
                        'd' => 100
                    ]
                ]
            ]
        ];

        $orderBook = OrderBookResponse::fromJson($json);

        $this->assertInstanceOf(Asset::class, $orderBook->getBase());
        $this->assertEquals('native', $orderBook->getBase()->getType());

        $this->assertInstanceOf(Asset::class, $orderBook->getCounter());
        $this->assertEquals('credit_alphanum4', $orderBook->getCounter()->getType());

        $this->assertInstanceOf(OrderBookRowsResponse::class, $orderBook->getBids());
        $this->assertEquals(1, $orderBook->getBids()->count());

        $this->assertInstanceOf(OrderBookRowsResponse::class, $orderBook->getAsks());
        $this->assertEquals(1, $orderBook->getAsks()->count());
    }

    public function testOrderBookResponseMultipleBidsAndAsks(): void
    {
        $json = [
            'base' => [
                'asset_type' => 'credit_alphanum4',
                'asset_code' => 'BTC',
                'asset_issuer' => 'GATEMHCCKCY67ZUCKTROYN24ZYT5GK4EQZ65JJLDHKHRUZI3EUEKMTCH'
            ],
            'counter' => [
                'asset_type' => 'credit_alphanum4',
                'asset_code' => 'USD',
                'asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
            ],
            'bids' => [
                [
                    'price' => '50000.0000000',
                    'amount' => '10.0000000',
                    'price_r' => ['n' => 50000, 'd' => 1]
                ],
                [
                    'price' => '49500.0000000',
                    'amount' => '15.5000000',
                    'price_r' => ['n' => 49500, 'd' => 1]
                ],
                [
                    'price' => '49000.0000000',
                    'amount' => '20.0000000',
                    'price_r' => ['n' => 49000, 'd' => 1]
                ]
            ],
            'asks' => [
                [
                    'price' => '50100.0000000',
                    'amount' => '8.0000000',
                    'price_r' => ['n' => 50100, 'd' => 1]
                ],
                [
                    'price' => '50500.0000000',
                    'amount' => '12.5000000',
                    'price_r' => ['n' => 50500, 'd' => 1]
                ]
            ]
        ];

        $orderBook = OrderBookResponse::fromJson($json);

        $this->assertEquals(3, $orderBook->getBids()->count());
        $this->assertEquals(2, $orderBook->getAsks()->count());

        $bidsArray = $orderBook->getBids()->toArray();
        $this->assertEquals('50000.0000000', $bidsArray[0]->getPrice());
        $this->assertEquals('10.0000000', $bidsArray[0]->getAmount());
        $this->assertEquals('49500.0000000', $bidsArray[1]->getPrice());
        $this->assertEquals('49000.0000000', $bidsArray[2]->getPrice());

        $asksArray = $orderBook->getAsks()->toArray();
        $this->assertEquals('50100.0000000', $asksArray[0]->getPrice());
        $this->assertEquals('8.0000000', $asksArray[0]->getAmount());
        $this->assertEquals('50500.0000000', $asksArray[1]->getPrice());
    }

    public function testOrderBookResponseEmptyBidsAndAsks(): void
    {
        $json = [
            'base' => [
                'asset_type' => 'native'
            ],
            'counter' => [
                'asset_type' => 'credit_alphanum4',
                'asset_code' => 'EUR',
                'asset_issuer' => 'GDTNXRLOJD2YEBPKK7KCMR7J33AAG5VZXHAJTHIG736D6LVEFLLLKPDL'
            ],
            'bids' => [],
            'asks' => []
        ];

        $orderBook = OrderBookResponse::fromJson($json);

        $this->assertEquals(0, $orderBook->getBids()->count());
        $this->assertEquals(0, $orderBook->getAsks()->count());
    }

    public function testOrderBookResponseCredit12Assets(): void
    {
        $json = [
            'base' => [
                'asset_type' => 'credit_alphanum12',
                'asset_code' => 'LONGASSETCOD',
                'asset_issuer' => 'GDTNXRLOJD2YEBPKK7KCMR7J33AAG5VZXHAJTHIG736D6LVEFLLLKPDL'
            ],
            'counter' => [
                'asset_type' => 'credit_alphanum12',
                'asset_code' => 'ANOTHERLONGA',
                'asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
            ],
            'bids' => [],
            'asks' => []
        ];

        $orderBook = OrderBookResponse::fromJson($json);

        $this->assertEquals('credit_alphanum12', $orderBook->getBase()->getType());
        $this->assertEquals('credit_alphanum12', $orderBook->getCounter()->getType());
    }

    // OrderBookRowResponse Tests

    public function testOrderBookRowResponseBasic(): void
    {
        $json = [
            'price' => '1.5000000',
            'amount' => '250.0000000',
            'price_r' => [
                'n' => 3,
                'd' => 2
            ]
        ];

        $row = OrderBookRowResponse::fromJson($json);

        $this->assertEquals('1.5000000', $row->getPrice());
        $this->assertEquals('250.0000000', $row->getAmount());

        $this->assertInstanceOf(OfferPriceResponse::class, $row->getPriceR());
        $this->assertEquals(3, $row->getPriceR()->getN());
        $this->assertEquals(2, $row->getPriceR()->getD());
    }

    public function testOrderBookRowResponseHighPrecision(): void
    {
        $json = [
            'price' => '0.0012345',
            'amount' => '100000.0000000',
            'price_r' => [
                'n' => 12345,
                'd' => 10000000
            ]
        ];

        $row = OrderBookRowResponse::fromJson($json);

        $this->assertEquals('0.0012345', $row->getPrice());
        $this->assertEquals('100000.0000000', $row->getAmount());
        $this->assertEquals(12345, $row->getPriceR()->getN());
        $this->assertEquals(10000000, $row->getPriceR()->getD());
    }

    public function testOrderBookRowResponseLargeAmounts(): void
    {
        $json = [
            'price' => '50000.0000000',
            'amount' => '999999.9999999',
            'price_r' => [
                'n' => 50000,
                'd' => 1
            ]
        ];

        $row = OrderBookRowResponse::fromJson($json);

        $this->assertEquals('50000.0000000', $row->getPrice());
        $this->assertEquals('999999.9999999', $row->getAmount());
    }

    public function testOrderBookRowResponsePriceRationalFraction(): void
    {
        $json = [
            'price' => '2.3333333',
            'amount' => '1000.0000000',
            'price_r' => [
                'n' => 7,
                'd' => 3
            ]
        ];

        $row = OrderBookRowResponse::fromJson($json);

        $priceR = $row->getPriceR();
        $this->assertEquals(7, $priceR->getN());
        $this->assertEquals(3, $priceR->getD());

        $calculatedPrice = $priceR->getN() / $priceR->getD();
        $this->assertEqualsWithDelta(2.3333333, $calculatedPrice, 0.0000001);
    }

    // OrderBookRowsResponse Tests

    public function testOrderBookRowsResponseEmpty(): void
    {
        $rows = new OrderBookRowsResponse();

        $this->assertEquals(0, $rows->count());
        $this->assertEmpty($rows->toArray());
    }

    public function testOrderBookRowsResponseWithRows(): void
    {
        $json1 = [
            'price' => '1.0000000',
            'amount' => '100.0000000',
            'price_r' => ['n' => 1, 'd' => 1]
        ];

        $json2 = [
            'price' => '1.5000000',
            'amount' => '200.0000000',
            'price_r' => ['n' => 3, 'd' => 2]
        ];

        $json3 = [
            'price' => '2.0000000',
            'amount' => '300.0000000',
            'price_r' => ['n' => 2, 'd' => 1]
        ];

        $row1 = OrderBookRowResponse::fromJson($json1);
        $row2 = OrderBookRowResponse::fromJson($json2);
        $row3 = OrderBookRowResponse::fromJson($json3);

        $rows = new OrderBookRowsResponse($row1, $row2, $row3);

        $this->assertEquals(3, $rows->count());

        $rowsArray = $rows->toArray();
        $this->assertCount(3, $rowsArray);
        $this->assertEquals('1.0000000', $rowsArray[0]->getPrice());
        $this->assertEquals('1.5000000', $rowsArray[1]->getPrice());
        $this->assertEquals('2.0000000', $rowsArray[2]->getPrice());
    }

    public function testOrderBookRowsResponseAdd(): void
    {
        $rows = new OrderBookRowsResponse();
        $this->assertEquals(0, $rows->count());

        $json = [
            'price' => '1.0000000',
            'amount' => '100.0000000',
            'price_r' => ['n' => 1, 'd' => 1]
        ];

        $row = OrderBookRowResponse::fromJson($json);
        $rows->add($row);

        $this->assertEquals(1, $rows->count());

        $json2 = [
            'price' => '2.0000000',
            'amount' => '200.0000000',
            'price_r' => ['n' => 2, 'd' => 1]
        ];

        $row2 = OrderBookRowResponse::fromJson($json2);
        $rows->add($row2);

        $this->assertEquals(2, $rows->count());
    }

    public function testOrderBookRowsResponseIteration(): void
    {
        $json1 = [
            'price' => '1.0000000',
            'amount' => '100.0000000',
            'price_r' => ['n' => 1, 'd' => 1]
        ];

        $json2 = [
            'price' => '2.0000000',
            'amount' => '200.0000000',
            'price_r' => ['n' => 2, 'd' => 1]
        ];

        $row1 = OrderBookRowResponse::fromJson($json1);
        $row2 = OrderBookRowResponse::fromJson($json2);

        $rows = new OrderBookRowsResponse($row1, $row2);

        $count = 0;
        foreach ($rows as $row) {
            $this->assertInstanceOf(OrderBookRowResponse::class, $row);
            $count++;
        }
        $this->assertEquals(2, $count);
    }

    public function testOrderBookRowsResponseCurrent(): void
    {
        $json = [
            'price' => '5.0000000',
            'amount' => '500.0000000',
            'price_r' => ['n' => 5, 'd' => 1]
        ];

        $row = OrderBookRowResponse::fromJson($json);
        $rows = new OrderBookRowsResponse($row);
        $rows->rewind();

        $this->assertInstanceOf(OrderBookRowResponse::class, $rows->current());
        $this->assertEquals('5.0000000', $rows->current()->getPrice());
    }

    // Integration Tests

    public function testOrderBookResponseFullIntegration(): void
    {
        $json = [
            'base' => [
                'asset_type' => 'credit_alphanum4',
                'asset_code' => 'EUR',
                'asset_issuer' => 'GDTNXRLOJD2YEBPKK7KCMR7J33AAG5VZXHAJTHIG736D6LVEFLLLKPDL'
            ],
            'counter' => [
                'asset_type' => 'credit_alphanum4',
                'asset_code' => 'USD',
                'asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
            ],
            'bids' => [
                [
                    'price' => '1.1000000',
                    'amount' => '1000.0000000',
                    'price_r' => ['n' => 11, 'd' => 10]
                ],
                [
                    'price' => '1.0900000',
                    'amount' => '2000.0000000',
                    'price_r' => ['n' => 109, 'd' => 100]
                ]
            ],
            'asks' => [
                [
                    'price' => '1.1100000',
                    'amount' => '1500.0000000',
                    'price_r' => ['n' => 111, 'd' => 100]
                ],
                [
                    'price' => '1.1200000',
                    'amount' => '2500.0000000',
                    'price_r' => ['n' => 112, 'd' => 100]
                ]
            ]
        ];

        $orderBook = OrderBookResponse::fromJson($json);

        $this->assertEquals('credit_alphanum4', $orderBook->getBase()->getType());
        $this->assertEquals('credit_alphanum4', $orderBook->getCounter()->getType());

        $bids = $orderBook->getBids();
        $this->assertEquals(2, $bids->count());

        $bidCount = 0;
        foreach ($bids as $bid) {
            $this->assertInstanceOf(OrderBookRowResponse::class, $bid);
            $this->assertInstanceOf(OfferPriceResponse::class, $bid->getPriceR());
            $bidCount++;
        }
        $this->assertEquals(2, $bidCount);

        $asks = $orderBook->getAsks();
        $this->assertEquals(2, $asks->count());

        $askCount = 0;
        foreach ($asks as $ask) {
            $this->assertInstanceOf(OrderBookRowResponse::class, $ask);
            $this->assertInstanceOf(OfferPriceResponse::class, $ask->getPriceR());
            $askCount++;
        }
        $this->assertEquals(2, $askCount);
    }

    public function testOrderBookResponseDeepOrderBook(): void
    {
        $json = [
            'base' => ['asset_type' => 'native'],
            'counter' => [
                'asset_type' => 'credit_alphanum4',
                'asset_code' => 'USDC',
                'asset_issuer' => 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN'
            ],
            'bids' => [],
            'asks' => []
        ];

        for ($i = 0; $i < 50; $i++) {
            $json['bids'][] = [
                'price' => sprintf('0.%04d000', 5000 - $i),
                'amount' => sprintf('%d.0000000', 100 + $i),
                'price_r' => ['n' => 5000 - $i, 'd' => 10000]
            ];

            $json['asks'][] = [
                'price' => sprintf('0.%04d000', 5100 + $i),
                'amount' => sprintf('%d.0000000', 100 + $i),
                'price_r' => ['n' => 5100 + $i, 'd' => 10000]
            ];
        }

        $orderBook = OrderBookResponse::fromJson($json);

        $this->assertEquals(50, $orderBook->getBids()->count());
        $this->assertEquals(50, $orderBook->getAsks()->count());

        $bidsArray = $orderBook->getBids()->toArray();
        $this->assertCount(50, $bidsArray);
        $this->assertEquals('0.5000000', $bidsArray[0]->getPrice());
        $this->assertEquals('100.0000000', $bidsArray[0]->getAmount());

        $asksArray = $orderBook->getAsks()->toArray();
        $this->assertCount(50, $asksArray);
        $this->assertEquals('0.5100000', $asksArray[0]->getPrice());
        $this->assertEquals('100.0000000', $asksArray[0]->getAmount());
    }
}
