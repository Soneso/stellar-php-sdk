<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrClaimAtom;
use Soneso\StellarSDK\Xdr\XdrClaimAtomType;
use Soneso\StellarSDK\Xdr\XdrClaimOfferAtom;
use Soneso\StellarSDK\Xdr\XdrEncoder;
use Soneso\StellarSDK\Xdr\XdrMuxedAccount;
use Soneso\StellarSDK\Xdr\XdrPathPaymentResultSuccess;
use Soneso\StellarSDK\Xdr\XdrPathPaymentStrictSendResult;
use Soneso\StellarSDK\Xdr\XdrPathPaymentStrictSendResultCode;
use Soneso\StellarSDK\Xdr\XdrSimplePaymentResult;

/**
 * Unit tests for XdrPathPaymentResultSuccess and XdrPathPaymentStrictSendResult
 *
 * Tests encode/decode round-trips for path payment result types.
 */
class XdrPathPaymentResultTest extends TestCase
{
    private const TEST_ACCOUNT_ID = 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H';
    private const TEST_ACCOUNT_ID_2 = 'GBFUYQUPRAG2YHKBBQSWKHFZRH5N4NIWKK3OVMUDLF7R6453BN4OUAVR';

    // XdrPathPaymentResultSuccess Tests

    public function testPathPaymentResultSuccessEncodeDecodeRoundTripEmpty(): void
    {
        // Create via decode first since there's no constructor
        $last = $this->createSimplePaymentResult();
        $offers = [];

        // Encode manually and decode
        $bytes = $this->encodePathPaymentResultSuccess($offers, $last);
        $decoded = XdrPathPaymentResultSuccess::decode(new XdrBuffer($bytes));

        $this->assertEmpty($decoded->getOffers());
        $this->assertNotNull($decoded->getLast());

        // Re-encode and verify
        $reEncoded = $decoded->encode();
        $this->assertEquals($bytes, $reEncoded);
    }

    public function testPathPaymentResultSuccessEncodeDecodeRoundTripWithOffers(): void
    {
        $last = $this->createSimplePaymentResult();
        $offer = $this->createClaimAtom();
        $offers = [$offer];

        // Encode manually and decode
        $bytes = $this->encodePathPaymentResultSuccess($offers, $last);
        $decoded = XdrPathPaymentResultSuccess::decode(new XdrBuffer($bytes));

        $this->assertCount(1, $decoded->getOffers());
        $this->assertNotNull($decoded->getLast());

        // Re-encode and verify
        $reEncoded = $decoded->encode();
        $this->assertEquals($bytes, $reEncoded);
    }

    public function testPathPaymentResultSuccessEncodeDecodeRoundTripMultipleOffers(): void
    {
        $last = $this->createSimplePaymentResult();
        $offers = [
            $this->createClaimAtom(),
            $this->createClaimAtom(),
            $this->createClaimAtom(),
        ];

        // Encode manually and decode
        $bytes = $this->encodePathPaymentResultSuccess($offers, $last);
        $decoded = XdrPathPaymentResultSuccess::decode(new XdrBuffer($bytes));

        $this->assertCount(3, $decoded->getOffers());

        // Re-encode and verify
        $reEncoded = $decoded->encode();
        $this->assertEquals($bytes, $reEncoded);
    }

    public function testPathPaymentResultSuccessGetters(): void
    {
        $last = $this->createSimplePaymentResult();
        $offer = $this->createClaimAtom();
        $offers = [$offer];

        $bytes = $this->encodePathPaymentResultSuccess($offers, $last);
        $decoded = XdrPathPaymentResultSuccess::decode(new XdrBuffer($bytes));

        $this->assertIsArray($decoded->getOffers());
        $this->assertInstanceOf(XdrClaimAtom::class, $decoded->getOffers()[0]);
        $this->assertInstanceOf(XdrSimplePaymentResult::class, $decoded->getLast());
    }

    // XdrPathPaymentStrictSendResult Tests

    public function testPathPaymentStrictSendResultSuccessEncodeDecodeRoundTrip(): void
    {
        $last = $this->createSimplePaymentResult();
        $offers = [$this->createClaimAtom()];

        // Create the success result manually
        $successBytes = $this->encodePathPaymentResultSuccess($offers, $last);

        // Create the strict send result with success code
        $code = new XdrPathPaymentStrictSendResultCode(XdrPathPaymentStrictSendResultCode::SUCCESS);
        $bytes = $code->encode() . $successBytes;

        $decoded = XdrPathPaymentStrictSendResult::decode(new XdrBuffer($bytes));

        $this->assertEquals(XdrPathPaymentStrictSendResultCode::SUCCESS, $decoded->getCode()->getValue());
        $this->assertNotNull($decoded->getSuccess());
        $this->assertNull($decoded->getNoIssuer());

        // Re-encode and verify
        $reEncoded = $decoded->encode();
        $this->assertEquals($bytes, $reEncoded);
    }

    public function testPathPaymentStrictSendResultNoIssuerEncodeDecodeRoundTrip(): void
    {
        // Create a native asset for noIssuer
        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));

        // Create the strict send result with NO_ISSUER code
        $code = new XdrPathPaymentStrictSendResultCode(XdrPathPaymentStrictSendResultCode::NO_ISSUER);
        $bytes = $code->encode() . $asset->encode();

        $decoded = XdrPathPaymentStrictSendResult::decode(new XdrBuffer($bytes));

        $this->assertEquals(XdrPathPaymentStrictSendResultCode::NO_ISSUER, $decoded->getCode()->getValue());
        $this->assertNull($decoded->getSuccess());
        $this->assertNotNull($decoded->getNoIssuer());
        $this->assertEquals(XdrAssetType::ASSET_TYPE_NATIVE, $decoded->getNoIssuer()->getType()->getValue());

        // Re-encode and verify
        $reEncoded = $decoded->encode();
        $this->assertEquals($bytes, $reEncoded);
    }

    public function testPathPaymentStrictSendResultMalformedEncodeDecodeRoundTrip(): void
    {
        // Create the strict send result with MALFORMED code (no extra data)
        $code = new XdrPathPaymentStrictSendResultCode(XdrPathPaymentStrictSendResultCode::MALFORMED);
        $bytes = $code->encode();

        $decoded = XdrPathPaymentStrictSendResult::decode(new XdrBuffer($bytes));

        $this->assertEquals(XdrPathPaymentStrictSendResultCode::MALFORMED, $decoded->getCode()->getValue());
        $this->assertNull($decoded->getSuccess());
        $this->assertNull($decoded->getNoIssuer());

        // Re-encode and verify
        $reEncoded = $decoded->encode();
        $this->assertEquals($bytes, $reEncoded);
    }

    public function testPathPaymentStrictSendResultUnderfundedEncodeDecodeRoundTrip(): void
    {
        $code = new XdrPathPaymentStrictSendResultCode(XdrPathPaymentStrictSendResultCode::UNDERFUNDED);
        $bytes = $code->encode();

        $decoded = XdrPathPaymentStrictSendResult::decode(new XdrBuffer($bytes));

        $this->assertEquals(XdrPathPaymentStrictSendResultCode::UNDERFUNDED, $decoded->getCode()->getValue());
        $this->assertNull($decoded->getSuccess());
        $this->assertNull($decoded->getNoIssuer());

        $reEncoded = $decoded->encode();
        $this->assertEquals($bytes, $reEncoded);
    }

    public function testPathPaymentStrictSendResultGetters(): void
    {
        // Test getCode
        $code = new XdrPathPaymentStrictSendResultCode(XdrPathPaymentStrictSendResultCode::TOO_FEW_OFFERS);
        $bytes = $code->encode();

        $decoded = XdrPathPaymentStrictSendResult::decode(new XdrBuffer($bytes));

        $this->assertInstanceOf(XdrPathPaymentStrictSendResultCode::class, $decoded->getCode());
        $this->assertEquals(XdrPathPaymentStrictSendResultCode::TOO_FEW_OFFERS, $decoded->getCode()->getValue());
    }

    // Helper Methods

    private function createSimplePaymentResult(): XdrSimplePaymentResult
    {
        // Decode the account ID to get raw public key bytes
        $publicKeyBytes = StrKey::decodeAccountId(self::TEST_ACCOUNT_ID);
        $muxedAccount = new XdrMuxedAccount($publicKeyBytes);

        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $amount = new BigInteger(1000000);

        $bytes = $muxedAccount->encode() . $asset->encode();
        $bytes .= XdrEncoder::bigInteger64($amount);

        return XdrSimplePaymentResult::decode(new XdrBuffer($bytes));
    }

    private function createClaimAtom(): XdrClaimAtom
    {
        $accountId = XdrAccountID::fromAccountId(self::TEST_ACCOUNT_ID_2);
        $offerId = 12345;
        $assetSold = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $amountSold = new BigInteger(500000);
        $assetBought = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $amountBought = new BigInteger(600000);

        $claimOfferAtom = new XdrClaimOfferAtom(
            $accountId,
            $offerId,
            $assetSold,
            $amountSold,
            $assetBought,
            $amountBought
        );

        // Encode the type and data, then decode to create the XdrClaimAtom
        $type = new XdrClaimAtomType(XdrClaimAtomType::ORDER_BOOK);
        $bytes = $type->encode() . $claimOfferAtom->encode();

        return XdrClaimAtom::decode(new XdrBuffer($bytes));
    }

    private function encodePathPaymentResultSuccess(array $offers, XdrSimplePaymentResult $last): string
    {
        $bytes = XdrEncoder::integer32(count($offers));
        foreach ($offers as $offer) {
            $bytes .= $offer->encode();
        }
        $bytes .= $last->encode();
        return $bytes;
    }
}
