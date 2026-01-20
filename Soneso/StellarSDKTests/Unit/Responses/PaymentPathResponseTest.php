<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Responses;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\Link\LinkResponse;
use Soneso\StellarSDK\Responses\PaymentPath\PathAssetsResponse;
use Soneso\StellarSDK\Responses\PaymentPath\PathLinksResponse;
use Soneso\StellarSDK\Responses\PaymentPath\PathResponse;
use Soneso\StellarSDK\Responses\PaymentPath\PathsPageResponse;
use Soneso\StellarSDK\Responses\PaymentPath\PathsResponse;

/**
 * Unit tests for PaymentPath Response classes
 *
 * Tests JSON parsing and getter methods for PathResponse, PathsPageResponse,
 * PathsResponse, PathAssetsResponse, and PathLinksResponse.
 */
class PaymentPathResponseTest extends TestCase
{
    // PathResponse Tests

    public function testPathResponseNativeToCredit(): void
    {
        $json = [
            'source_amount' => '100.0000000',
            'source_asset_type' => 'native',
            'destination_amount' => '95.5000000',
            'destination_asset_type' => 'credit_alphanum4',
            'destination_asset_code' => 'USD',
            'destination_asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX',
            'path' => [],
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/paths/strict-receive',
                    'templated' => false
                ]
            ]
        ];

        $path = PathResponse::fromJson($json);

        $this->assertEquals('100.0000000', $path->getSourceAmount());
        $this->assertEquals('native', $path->getSourceAssetType());
        $this->assertNull($path->getSourceAssetCode());
        $this->assertNull($path->getSourceAssetIssuer());

        $this->assertEquals('95.5000000', $path->getDestinationAmount());
        $this->assertEquals('credit_alphanum4', $path->getDestinationAssetType());
        $this->assertEquals('USD', $path->getDestinationAssetCode());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $path->getDestinationAssetIssuer());

        $this->assertInstanceOf(PathAssetsResponse::class, $path->getPath());
        $this->assertEquals(0, $path->getPath()->count());

        $this->assertInstanceOf(PathLinksResponse::class, $path->getLinks());
    }

    public function testPathResponseCreditToCredit(): void
    {
        $json = [
            'source_amount' => '50.0000000',
            'source_asset_type' => 'credit_alphanum4',
            'source_asset_code' => 'EUR',
            'source_asset_issuer' => 'GDTNXRLOJD2YEBPKK7KCMR7J33AAG5VZXHAJTHIG736D6LVEFLLLKPDL',
            'destination_amount' => '55.2500000',
            'destination_asset_type' => 'credit_alphanum4',
            'destination_asset_code' => 'USD',
            'destination_asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX',
            'path' => [],
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/paths/strict-send',
                    'templated' => false
                ]
            ]
        ];

        $path = PathResponse::fromJson($json);

        $this->assertEquals('50.0000000', $path->getSourceAmount());
        $this->assertEquals('credit_alphanum4', $path->getSourceAssetType());
        $this->assertEquals('EUR', $path->getSourceAssetCode());
        $this->assertEquals('GDTNXRLOJD2YEBPKK7KCMR7J33AAG5VZXHAJTHIG736D6LVEFLLLKPDL', $path->getSourceAssetIssuer());

        $this->assertEquals('55.2500000', $path->getDestinationAmount());
        $this->assertEquals('credit_alphanum4', $path->getDestinationAssetType());
        $this->assertEquals('USD', $path->getDestinationAssetCode());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $path->getDestinationAssetIssuer());
    }

    public function testPathResponseWithIntermediatePath(): void
    {
        $json = [
            'source_amount' => '100.0000000',
            'source_asset_type' => 'credit_alphanum4',
            'source_asset_code' => 'EUR',
            'source_asset_issuer' => 'GDTNXRLOJD2YEBPKK7KCMR7J33AAG5VZXHAJTHIG736D6LVEFLLLKPDL',
            'destination_amount' => '90.0000000',
            'destination_asset_type' => 'credit_alphanum4',
            'destination_asset_code' => 'JPY',
            'destination_asset_issuer' => 'GBVAOIACNSB7OVUXJYC5UE2D4YK2F7A24T7EE5YOMN4CE6GCHUTOUQXM',
            'path' => [
                [
                    'asset_type' => 'native'
                ],
                [
                    'asset_type' => 'credit_alphanum4',
                    'asset_code' => 'USD',
                    'asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
                ]
            ],
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/paths',
                    'templated' => false
                ]
            ]
        ];

        $path = PathResponse::fromJson($json);

        $this->assertEquals('100.0000000', $path->getSourceAmount());
        $this->assertEquals('90.0000000', $path->getDestinationAmount());

        $intermediateAssets = $path->getPath();
        $this->assertInstanceOf(PathAssetsResponse::class, $intermediateAssets);
        $this->assertEquals(2, $intermediateAssets->count());

        $assetsArray = $intermediateAssets->toArray();
        $this->assertCount(2, $assetsArray);
        $this->assertEquals('native', $assetsArray[0]->getType());
        $this->assertEquals('credit_alphanum4', $assetsArray[1]->getType());
    }

    public function testPathResponseCredit12(): void
    {
        $json = [
            'source_amount' => '1000.0000000',
            'source_asset_type' => 'credit_alphanum12',
            'source_asset_code' => 'LONGCOINNAME',
            'source_asset_issuer' => 'GDTNXRLOJD2YEBPKK7KCMR7J33AAG5VZXHAJTHIG736D6LVEFLLLKPDL',
            'destination_amount' => '950.0000000',
            'destination_asset_type' => 'credit_alphanum4',
            'destination_asset_code' => 'BTC',
            'destination_asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX',
            'path' => [],
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/paths',
                    'templated' => false
                ]
            ]
        ];

        $path = PathResponse::fromJson($json);

        $this->assertEquals('credit_alphanum12', $path->getSourceAssetType());
        $this->assertEquals('LONGCOINNAME', $path->getSourceAssetCode());
    }

    // PathLinksResponse Tests

    public function testPathLinksResponseFromJson(): void
    {
        $json = [
            'self' => [
                'href' => 'https://horizon.stellar.org/paths/strict-receive?destination_amount=100&destination_asset_type=native',
                'templated' => false
            ]
        ];

        $links = PathLinksResponse::fromJson($json);

        $this->assertInstanceOf(LinkResponse::class, $links->getSelf());
        $this->assertEquals('https://horizon.stellar.org/paths/strict-receive?destination_amount=100&destination_asset_type=native', $links->getSelf()->getHref());
        $this->assertFalse($links->getSelf()->isTemplated());
    }

    // PathAssetsResponse Tests

    public function testPathAssetsResponseEmpty(): void
    {
        $pathAssets = new PathAssetsResponse();

        $this->assertEquals(0, $pathAssets->count());
        $this->assertEmpty($pathAssets->toArray());
    }

    public function testPathAssetsResponseWithAssets(): void
    {
        $xlm = Asset::native();
        $usd = Asset::createNonNativeAsset('USD', 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX');
        $eur = Asset::createNonNativeAsset('EUR', 'GDTNXRLOJD2YEBPKK7KCMR7J33AAG5VZXHAJTHIG736D6LVEFLLLKPDL');

        $pathAssets = new PathAssetsResponse($xlm, $usd, $eur);

        $this->assertEquals(3, $pathAssets->count());

        $assetsArray = $pathAssets->toArray();
        $this->assertCount(3, $assetsArray);
        $this->assertEquals('native', $assetsArray[0]->getType());
        $this->assertEquals('credit_alphanum4', $assetsArray[1]->getType());
        $this->assertEquals('credit_alphanum4', $assetsArray[2]->getType());
    }

    public function testPathAssetsResponseAdd(): void
    {
        $pathAssets = new PathAssetsResponse();
        $this->assertEquals(0, $pathAssets->count());

        $xlm = Asset::native();
        $pathAssets->add($xlm);
        $this->assertEquals(1, $pathAssets->count());

        $usd = Asset::createNonNativeAsset('USD', 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX');
        $pathAssets->add($usd);
        $this->assertEquals(2, $pathAssets->count());
    }

    public function testPathAssetsResponseIteration(): void
    {
        $xlm = Asset::native();
        $usd = Asset::createNonNativeAsset('USD', 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX');

        $pathAssets = new PathAssetsResponse($xlm, $usd);

        $count = 0;
        foreach ($pathAssets as $asset) {
            $this->assertInstanceOf(Asset::class, $asset);
            $count++;
        }
        $this->assertEquals(2, $count);
    }

    // PathsResponse Tests

    public function testPathsResponseEmpty(): void
    {
        $paths = new PathsResponse();

        $this->assertEquals(0, $paths->count());
        $this->assertEmpty($paths->toArray());
    }

    public function testPathsResponseWithPaths(): void
    {
        $json1 = [
            'source_amount' => '100.0000000',
            'source_asset_type' => 'native',
            'destination_amount' => '95.0000000',
            'destination_asset_type' => 'credit_alphanum4',
            'destination_asset_code' => 'USD',
            'destination_asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX',
            'path' => [],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/paths', 'templated' => false]
            ]
        ];

        $json2 = [
            'source_amount' => '105.0000000',
            'source_asset_type' => 'native',
            'destination_amount' => '95.0000000',
            'destination_asset_type' => 'credit_alphanum4',
            'destination_asset_code' => 'USD',
            'destination_asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX',
            'path' => [
                ['asset_type' => 'credit_alphanum4', 'asset_code' => 'EUR', 'asset_issuer' => 'GDTNXRLOJD2YEBPKK7KCMR7J33AAG5VZXHAJTHIG736D6LVEFLLLKPDL']
            ],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/paths', 'templated' => false]
            ]
        ];

        $path1 = PathResponse::fromJson($json1);
        $path2 = PathResponse::fromJson($json2);

        $paths = new PathsResponse($path1, $path2);

        $this->assertEquals(2, $paths->count());

        $pathsArray = $paths->toArray();
        $this->assertCount(2, $pathsArray);
        $this->assertEquals('100.0000000', $pathsArray[0]->getSourceAmount());
        $this->assertEquals('105.0000000', $pathsArray[1]->getSourceAmount());
    }

    public function testPathsResponseAdd(): void
    {
        $paths = new PathsResponse();
        $this->assertEquals(0, $paths->count());

        $json = [
            'source_amount' => '100.0000000',
            'source_asset_type' => 'native',
            'destination_amount' => '95.0000000',
            'destination_asset_type' => 'credit_alphanum4',
            'destination_asset_code' => 'USD',
            'destination_asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX',
            'path' => [],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/paths', 'templated' => false]
            ]
        ];

        $path = PathResponse::fromJson($json);
        $paths->add($path);

        $this->assertEquals(1, $paths->count());
    }

    public function testPathsResponseIteration(): void
    {
        $json1 = [
            'source_amount' => '100.0000000',
            'source_asset_type' => 'native',
            'destination_amount' => '95.0000000',
            'destination_asset_type' => 'credit_alphanum4',
            'destination_asset_code' => 'USD',
            'destination_asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX',
            'path' => [],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/paths', 'templated' => false]
            ]
        ];

        $path1 = PathResponse::fromJson($json1);
        $paths = new PathsResponse($path1);

        $count = 0;
        foreach ($paths as $path) {
            $this->assertInstanceOf(PathResponse::class, $path);
            $count++;
        }
        $this->assertEquals(1, $count);
    }

    // PathsPageResponse Tests

    public function testPathsPageResponseFromJson(): void
    {
        $json = [
            '_embedded' => [
                'records' => [
                    [
                        'source_amount' => '100.0000000',
                        'source_asset_type' => 'native',
                        'destination_amount' => '95.0000000',
                        'destination_asset_type' => 'credit_alphanum4',
                        'destination_asset_code' => 'USD',
                        'destination_asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX',
                        'path' => [],
                        '_links' => [
                            'self' => ['href' => 'https://horizon.stellar.org/paths', 'templated' => false]
                        ]
                    ],
                    [
                        'source_amount' => '102.5000000',
                        'source_asset_type' => 'native',
                        'destination_amount' => '95.0000000',
                        'destination_asset_type' => 'credit_alphanum4',
                        'destination_asset_code' => 'USD',
                        'destination_asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX',
                        'path' => [
                            ['asset_type' => 'credit_alphanum4', 'asset_code' => 'EUR', 'asset_issuer' => 'GDTNXRLOJD2YEBPKK7KCMR7J33AAG5VZXHAJTHIG736D6LVEFLLLKPDL']
                        ],
                        '_links' => [
                            'self' => ['href' => 'https://horizon.stellar.org/paths', 'templated' => false]
                        ]
                    ]
                ]
            ],
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/paths/strict-receive?destination_amount=95&destination_asset_type=credit_alphanum4&destination_asset_code=USD&destination_asset_issuer=GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX',
                    'templated' => false
                ],
                'next' => [
                    'href' => 'https://horizon.stellar.org/paths/strict-receive?cursor=next&destination_amount=95',
                    'templated' => false
                ],
                'prev' => [
                    'href' => 'https://horizon.stellar.org/paths/strict-receive?cursor=prev&destination_amount=95',
                    'templated' => false
                ]
            ]
        ];

        $page = PathsPageResponse::fromJson($json);

        $this->assertInstanceOf(PathsResponse::class, $page->getPaths());
        $this->assertEquals(2, $page->getPaths()->count());

        $pathsArray = $page->getPaths()->toArray();
        $this->assertEquals('100.0000000', $pathsArray[0]->getSourceAmount());
        $this->assertEquals('102.5000000', $pathsArray[1]->getSourceAmount());

        $this->assertEquals(0, $pathsArray[0]->getPath()->count());
        $this->assertEquals(1, $pathsArray[1]->getPath()->count());
    }

    public function testPathsPageResponseEmptyRecords(): void
    {
        $json = [
            '_embedded' => [
                'records' => []
            ],
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/paths/strict-send',
                    'templated' => false
                ]
            ]
        ];

        $page = PathsPageResponse::fromJson($json);

        $this->assertInstanceOf(PathsResponse::class, $page->getPaths());
        $this->assertEquals(0, $page->getPaths()->count());
    }

    public function testPathsPageResponseComplexPath(): void
    {
        $json = [
            '_embedded' => [
                'records' => [
                    [
                        'source_amount' => '1000.0000000',
                        'source_asset_type' => 'credit_alphanum4',
                        'source_asset_code' => 'BTC',
                        'source_asset_issuer' => 'GATEMHCCKCY67ZUCKTROYN24ZYT5GK4EQZ65JJLDHKHRUZI3EUEKMTCH',
                        'destination_amount' => '50000.0000000',
                        'destination_asset_type' => 'credit_alphanum4',
                        'destination_asset_code' => 'ETH',
                        'destination_asset_issuer' => 'GBSTRH4QOTWNSVA6E4HFERETX4ZLSR3CIUBLK7AXYII277PFJC4BBYOG',
                        'path' => [
                            ['asset_type' => 'native'],
                            ['asset_type' => 'credit_alphanum4', 'asset_code' => 'USD', 'asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'],
                            ['asset_type' => 'credit_alphanum4', 'asset_code' => 'EUR', 'asset_issuer' => 'GDTNXRLOJD2YEBPKK7KCMR7J33AAG5VZXHAJTHIG736D6LVEFLLLKPDL']
                        ],
                        '_links' => [
                            'self' => ['href' => 'https://horizon.stellar.org/paths', 'templated' => false]
                        ]
                    ]
                ]
            ],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/paths', 'templated' => false]
            ]
        ];

        $page = PathsPageResponse::fromJson($json);

        $pathsArray = $page->getPaths()->toArray();
        $this->assertCount(1, $pathsArray);

        $path = $pathsArray[0];
        $this->assertEquals('BTC', $path->getSourceAssetCode());
        $this->assertEquals('ETH', $path->getDestinationAssetCode());

        $intermediatePath = $path->getPath();
        $this->assertEquals(3, $intermediatePath->count());

        $intermediateAssets = $intermediatePath->toArray();
        $this->assertEquals('native', $intermediateAssets[0]->getType());
        $this->assertEquals('credit_alphanum4', $intermediateAssets[1]->getType());
        $this->assertEquals('credit_alphanum4', $intermediateAssets[2]->getType());
    }
}
