<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum;
use Soneso\StellarSDK\StellarSDK;

class PaymentPathsTest extends TestCase
{

    public function testFindPath(): void
    {
        $sdk = StellarSDK::getTestNetInstance();

        $mocAsset = Asset::createNonNativeAsset("MOC", "GAQE4CRL26PIJKHFUY4V3XKPVITTZMNEQQGWLORRIAU3L4GDHYZUUFUN");
        $dacAsset = Asset::createNonNativeAsset("DAC", "GAQE4CRL26PIJKHFUY4V3XKPVITTZMNEQQGWLORRIAU3L4GDHYZUUFUN");
        $zacAsset = Asset::createNonNativeAsset("ZAC", "GAQE4CRL26PIJKHFUY4V3XKPVITTZMNEQQGWLORRIAU3L4GDHYZUUFUN");

        $sourceAccount = "GCNG3WCQTQNNPRM6OWLSR5O6O2WZ5NJA2WAPS3PESZWBJTDBX7XZHAXN";
        $destinationAccount = "GCB5T4D674XOUL2V7VVE3Z2R2OGN344W2ODIQUDSGAXBYCOG76HJC4TM";

        $requestBuilder = $sdk->findPaths()->forSourceAccount($sourceAccount)->forDestinationAccount($destinationAccount)->forDestinationAsset($dacAsset)->forDestinationAmount("100");
        $response = $requestBuilder->execute();
        foreach ($response->getPaths() as $record) {
            $this->assertEquals($mocAsset->getType(), $record->getSourceAssetType());
            $this->assertEquals($mocAsset->getCode(), $record->getSourceAssetCode());
            $this->assertEquals($mocAsset->getIssuer(), $record->getSourceAssetIssuer());
            $this->assertEquals($dacAsset->getType(), $record->getDestinationAssetType());
            $this->assertEquals($dacAsset->getCode(), $record->getDestinationAssetCode());
            $this->assertEquals($dacAsset->getIssuer(), $record->getDestinationAssetIssuer());
            $this->assertEquals("110.0000000", $record->getSourceAmount());
            $this->assertEquals("100.0000000", $record->getDestinationAmount());
            foreach ($record->getPath() as $asset) {
                $this->assertEquals($zacAsset->getType(), $asset->getType());
                if ($asset instanceof AssetTypeCreditAlphanum) {
                    $this->assertEquals($zacAsset->getCode(), $asset->getCode());
                    $this->assertEquals($zacAsset->getIssuer(), $asset->getIssuer());
                } else {
                    $this->fail();
                }
            }
        }
    }

    public function testStrictSendPath(): void
    {
        $sdk = StellarSDK::getTestNetInstance();

        $mocAsset = Asset::createNonNativeAsset("MOC", "GAQE4CRL26PIJKHFUY4V3XKPVITTZMNEQQGWLORRIAU3L4GDHYZUUFUN");
        $dacAsset = Asset::createNonNativeAsset("DAC", "GAQE4CRL26PIJKHFUY4V3XKPVITTZMNEQQGWLORRIAU3L4GDHYZUUFUN");
        $zacAsset = Asset::createNonNativeAsset("ZAC", "GAQE4CRL26PIJKHFUY4V3XKPVITTZMNEQQGWLORRIAU3L4GDHYZUUFUN");
        $destinationAccount = "GCB5T4D674XOUL2V7VVE3Z2R2OGN344W2ODIQUDSGAXBYCOG76HJC4TM";
        $requestBuilder = $sdk->findStrictSendPaths()->forSourceAmount("100")->forSourceAsset($mocAsset)->forDestinationAccount($destinationAccount);
        $response = $requestBuilder->execute();
        foreach($response->getPaths() as $record) {
            $this->assertEquals($mocAsset->getType(), $record->getSourceAssetType());
            $this->assertEquals($mocAsset->getCode(), $record->getSourceAssetCode());
            $this->assertEquals($mocAsset->getIssuer(), $record->getSourceAssetIssuer());
            $this->assertEquals($dacAsset->getType(), $record->getDestinationAssetType());
            $this->assertEquals($dacAsset->getCode(), $record->getDestinationAssetCode());
            $this->assertEquals($dacAsset->getIssuer(), $record->getDestinationAssetIssuer());
            $this->assertEquals("100.0000000", $record->getSourceAmount());
            $this->assertEquals("90.9090909", $record->getDestinationAmount());
            foreach ($record->getPath() as $asset) {
                $this->assertEquals($zacAsset->getType(), $asset->getType());
                if ($asset instanceof AssetTypeCreditAlphanum) {
                    $this->assertEquals($zacAsset->getCode(), $asset->getCode());
                    $this->assertEquals($zacAsset->getIssuer(), $asset->getIssuer());
                } else {
                    $this->fail();
                }
            }
        }

        $requestBuilder = $sdk->findStrictSendPaths()->forSourceAmount("100")->forSourceAsset($mocAsset)->forDestinationAssets([$dacAsset]);
        $response = $requestBuilder->execute();
        foreach($response->getPaths() as $record) {
            $this->assertEquals($mocAsset->getType(), $record->getSourceAssetType());
            $this->assertEquals($mocAsset->getCode(), $record->getSourceAssetCode());
            $this->assertEquals($mocAsset->getIssuer(), $record->getSourceAssetIssuer());
            $this->assertEquals($dacAsset->getType(), $record->getDestinationAssetType());
            $this->assertEquals($dacAsset->getCode(), $record->getDestinationAssetCode());
            $this->assertEquals($dacAsset->getIssuer(), $record->getDestinationAssetIssuer());
            $this->assertEquals("100.0000000", $record->getSourceAmount());
            $this->assertEquals("90.9090909", $record->getDestinationAmount());
            foreach ($record->getPath() as $asset) {
                $this->assertEquals($zacAsset->getType(), $asset->getType());
                if ($asset instanceof AssetTypeCreditAlphanum) {
                    $this->assertEquals($zacAsset->getCode(), $asset->getCode());
                    $this->assertEquals($zacAsset->getIssuer(), $asset->getIssuer());
                } else {
                    $this->fail();
                }
            }
        }
    }

    public function testStrictReceivePath(): void
    {
        $sdk = StellarSDK::getTestNetInstance();

        $mocAsset = Asset::createNonNativeAsset("MOC", "GAQE4CRL26PIJKHFUY4V3XKPVITTZMNEQQGWLORRIAU3L4GDHYZUUFUN");
        $dacAsset = Asset::createNonNativeAsset("DAC", "GAQE4CRL26PIJKHFUY4V3XKPVITTZMNEQQGWLORRIAU3L4GDHYZUUFUN");
        $zacAsset = Asset::createNonNativeAsset("ZAC", "GAQE4CRL26PIJKHFUY4V3XKPVITTZMNEQQGWLORRIAU3L4GDHYZUUFUN");
        $sourceAccount = "GCNG3WCQTQNNPRM6OWLSR5O6O2WZ5NJA2WAPS3PESZWBJTDBX7XZHAXN";
        $requestBuilder = $sdk->findStrictReceivePaths()->forDestinationAmount("100")->forDestinationAsset($dacAsset)->forSourceAccount($sourceAccount);
        $response = $requestBuilder->execute();
        foreach($response->getPaths() as $record) {
            $this->assertEquals($mocAsset->getType(), $record->getSourceAssetType());
            $this->assertEquals($mocAsset->getCode(), $record->getSourceAssetCode());
            $this->assertEquals($mocAsset->getIssuer(), $record->getSourceAssetIssuer());
            $this->assertEquals($dacAsset->getType(), $record->getDestinationAssetType());
            $this->assertEquals($dacAsset->getCode(), $record->getDestinationAssetCode());
            $this->assertEquals($dacAsset->getIssuer(), $record->getDestinationAssetIssuer());
            $this->assertEquals("110.0000000", $record->getSourceAmount());
            $this->assertEquals("100.0000000", $record->getDestinationAmount());
            foreach ($record->getPath() as $asset) {
                $this->assertEquals($zacAsset->getType(), $asset->getType());
                if ($asset instanceof AssetTypeCreditAlphanum) {
                    $this->assertEquals($zacAsset->getCode(), $asset->getCode());
                    $this->assertEquals($zacAsset->getIssuer(), $asset->getIssuer());
                } else {
                    $this->fail();
                }
            }
        }

        $requestBuilder = $sdk->findStrictReceivePaths()->forDestinationAmount("100")->forDestinationAsset($dacAsset)->forSourceAssets([$mocAsset]);
        $response = $requestBuilder->execute();
        foreach($response->getPaths() as $record) {
            $this->assertEquals($mocAsset->getType(), $record->getSourceAssetType());
            $this->assertEquals($mocAsset->getCode(), $record->getSourceAssetCode());
            $this->assertEquals($mocAsset->getIssuer(), $record->getSourceAssetIssuer());
            $this->assertEquals($dacAsset->getType(), $record->getDestinationAssetType());
            $this->assertEquals($dacAsset->getCode(), $record->getDestinationAssetCode());
            $this->assertEquals($dacAsset->getIssuer(), $record->getDestinationAssetIssuer());
            $this->assertEquals("110.0000000", $record->getSourceAmount());
            $this->assertEquals("100.0000000", $record->getDestinationAmount());
            foreach ($record->getPath() as $asset) {
                $this->assertEquals($zacAsset->getType(), $asset->getType());
                if ($asset instanceof AssetTypeCreditAlphanum) {
                    $this->assertEquals($zacAsset->getCode(), $asset->getCode());
                    $this->assertEquals($zacAsset->getIssuer(), $asset->getIssuer());
                } else {
                    $this->fail();
                }
            }
        }
    }
}