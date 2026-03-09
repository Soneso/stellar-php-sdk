<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use PHPUnit\Framework\TestCase;
use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrAccountMergeOperation;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrBumpSequenceOperation;
use Soneso\StellarSDK\Xdr\XdrChangeTrustAsset;
use Soneso\StellarSDK\Xdr\XdrChangeTrustOperation;
use Soneso\StellarSDK\Xdr\XdrCreateAccountOperation;
use Soneso\StellarSDK\Xdr\XdrCreatePassiveSellOfferOperation;
use Soneso\StellarSDK\Xdr\XdrDataValue;
use Soneso\StellarSDK\Xdr\XdrManageBuyOfferOperation;
use Soneso\StellarSDK\Xdr\XdrManageDataOperation;
use Soneso\StellarSDK\Xdr\XdrManageSellOfferOperation;
use Soneso\StellarSDK\Xdr\XdrMuxedAccount;
use Soneso\StellarSDK\Xdr\XdrOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrPaymentOperation;
use Soneso\StellarSDK\Xdr\XdrPrice;
use Soneso\StellarSDK\Xdr\XdrSequenceNumber;
use Soneso\StellarSDK\Xdr\XdrSetOptionsOperation;
use Soneso\StellarSDK\Xdr\XdrSigner;
use Soneso\StellarSDK\Xdr\XdrSignerKey;
use Soneso\StellarSDK\Xdr\XdrSignerKeyType;

class XdrOperationTest extends TestCase
{
    private const ACCOUNT_ID_1 = "GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H";
    private const ACCOUNT_ID_2 = "GC5SIC4E3V56VOHJ3OZAX5SJDTWY52JYI2AFK6PUGSXFVRJQYQXXZBZF";
    private const ED25519_1 = "3132333435363738393031323334353637383930313233343536373839303132";
    private const ED25519_2 = "3233343536373839303132333435363738393031323334353637383930313233";

    /**
     * Test SetOptionsOperation encode/decode roundtrip with minimal fields
     */
    public function testSetOptionsOperationMinimalRoundtrip(): void
    {
        $operation = new XdrSetOptionsOperation();
        $encoded = $operation->encode();
        $decoded = XdrSetOptionsOperation::decode(new XdrBuffer($encoded));

        $this->assertNull($decoded->getInflationDest());
        $this->assertNull($decoded->getClearFlags());
        $this->assertNull($decoded->getSetFlags());
        $this->assertNull($decoded->getMasterWeight());
        $this->assertNull($decoded->getLowThreshold());
        $this->assertNull($decoded->getMedThreshold());
        $this->assertNull($decoded->getHighThreshold());
        $this->assertNull($decoded->getHomeDomain());
        $this->assertNull($decoded->getSigner());
    }

    /**
     * Test SetOptionsOperation encode/decode roundtrip with all fields
     */
    public function testSetOptionsOperationFullRoundtrip(): void
    {
        $operation = new XdrSetOptionsOperation();
        $operation->setInflationDest(XdrAccountID::fromAccountId(self::ACCOUNT_ID_1));
        $operation->setClearFlags(1);
        $operation->setSetFlags(2);
        $operation->setMasterWeight(100);
        $operation->setLowThreshold(10);
        $operation->setMedThreshold(20);
        $operation->setHighThreshold(30);
        $operation->setHomeDomain("stellar.org");

        $signerKey = new XdrSignerKey();
        $signerKey->setType(new XdrSignerKeyType(XdrSignerKeyType::ED25519));
        $signerKey->setEd25519(hex2bin(self::ED25519_1));
        $signer = new XdrSigner($signerKey, 50);
        $operation->setSigner($signer);

        $encoded = $operation->encode();
        $decoded = XdrSetOptionsOperation::decode(new XdrBuffer($encoded));

        $this->assertEquals(self::ACCOUNT_ID_1, $decoded->getInflationDest()->getAccountId());
        $this->assertEquals(1, $decoded->getClearFlags());
        $this->assertEquals(2, $decoded->getSetFlags());
        $this->assertEquals(100, $decoded->getMasterWeight());
        $this->assertEquals(10, $decoded->getLowThreshold());
        $this->assertEquals(20, $decoded->getMedThreshold());
        $this->assertEquals(30, $decoded->getHighThreshold());
        $this->assertEquals("stellar.org", $decoded->getHomeDomain());
        $this->assertEquals(self::ED25519_1, bin2hex($decoded->getSigner()->getKey()->getEd25519()));
        $this->assertEquals(50, $decoded->getSigner()->getWeight());
    }

    /**
     * Test ChangeTrustOperation encode/decode roundtrip
     */
    public function testChangeTrustOperationRoundtrip(): void
    {
        $changeTrustAsset = new XdrChangeTrustAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $limit = new BigInteger("922337203685477580");

        $operation = new XdrChangeTrustOperation($changeTrustAsset, $limit);
        $encoded = $operation->encode();
        $decoded = XdrChangeTrustOperation::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrAssetType::ASSET_TYPE_NATIVE, $decoded->getLine()->getType()->getValue());
        $this->assertEquals($limit->toString(), $decoded->getLimit()->toString());
    }

    /**
     * Test AccountMergeOperation encode/decode roundtrip
     */
    public function testAccountMergeOperationRoundtrip(): void
    {
        $destination = new XdrMuxedAccount(hex2bin(self::ED25519_2));

        $operation = new XdrAccountMergeOperation($destination);
        $encoded = $operation->encode();
        $decoded = XdrAccountMergeOperation::decode(new XdrBuffer($encoded));

        $this->assertEquals(self::ED25519_2, bin2hex($decoded->getDestination()->getEd25519()));
    }

    /**
     * Test ManageDataOperation encode/decode roundtrip with value
     */
    public function testManageDataOperationWithValueRoundtrip(): void
    {
        $key = "testkey";
        $value = new XdrDataValue("testvalue");

        $operation = new XdrManageDataOperation($key, $value);
        $encoded = $operation->encode();
        $decoded = XdrManageDataOperation::decode(new XdrBuffer($encoded));

        $this->assertEquals($key, $decoded->getKey());
        $this->assertEquals("testvalue", $decoded->getValue()->getValue());
    }

    /**
     * Test ManageDataOperation encode/decode roundtrip with null value
     */
    public function testManageDataOperationWithNullValueRoundtrip(): void
    {
        $key = "deletekey";
        $value = new XdrDataValue(null);

        $operation = new XdrManageDataOperation($key, $value);
        $encoded = $operation->encode();
        $decoded = XdrManageDataOperation::decode(new XdrBuffer($encoded));

        $this->assertEquals($key, $decoded->getKey());
        $this->assertNull($decoded->getValue()->getValue());
    }

    /**
     * Test BumpSequenceOperation encode/decode roundtrip
     */
    public function testBumpSequenceOperationRoundtrip(): void
    {
        $bumpTo = new XdrSequenceNumber(new BigInteger("9999999999"));

        $operation = new XdrBumpSequenceOperation($bumpTo);
        $encoded = $operation->encode();
        $decoded = XdrBumpSequenceOperation::decode(new XdrBuffer($encoded));

        $this->assertEquals("9999999999", $decoded->getBumpTo()->sequenceNumber->toString());
    }

    /**
     * Test XdrOperationBody encode/decode roundtrip with CreateAccount
     */
    public function testOperationBodyCreateAccountRoundtrip(): void
    {
        $type = new XdrOperationType(XdrOperationType::CREATE_ACCOUNT);
        $body = new XdrOperationBody($type);

        $destination = XdrAccountID::fromAccountId(self::ACCOUNT_ID_1);
        $startingBalance = new BigInteger("10000000000");
        $createAccountOp = new XdrCreateAccountOperation($destination, $startingBalance);
        $body->setCreateAccountOp($createAccountOp);

        $encoded = $body->encode();
        $decoded = XdrOperationBody::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrOperationType::CREATE_ACCOUNT, $decoded->getType()->getValue());
        $this->assertEquals(self::ACCOUNT_ID_1, $decoded->getCreateAccountOp()->getDestination()->getAccountId());
        $this->assertEquals($startingBalance->toString(), $decoded->getCreateAccountOp()->getStartingBalance()->toString());
    }

    /**
     * Test XdrOperation encode/decode roundtrip without source account
     */
    public function testOperationWithoutSourceAccountRoundtrip(): void
    {
        $type = new XdrOperationType(XdrOperationType::CREATE_ACCOUNT);
        $body = new XdrOperationBody($type);

        $destination = XdrAccountID::fromAccountId(self::ACCOUNT_ID_1);
        $startingBalance = new BigInteger("10000000000");
        $createAccountOp = new XdrCreateAccountOperation($destination, $startingBalance);
        $body->setCreateAccountOp($createAccountOp);

        $operation = new XdrOperation($body);
        $encoded = $operation->encode();
        $decoded = XdrOperation::decode(new XdrBuffer($encoded));

        $this->assertNull($decoded->getSourceAccount());
        $this->assertEquals(XdrOperationType::CREATE_ACCOUNT, $decoded->getBody()->getType()->getValue());
    }

    /**
     * Test XdrOperation encode/decode roundtrip with source account
     */
    public function testOperationWithSourceAccountRoundtrip(): void
    {
        $type = new XdrOperationType(XdrOperationType::PAYMENT);
        $body = new XdrOperationBody($type);

        $destination = new XdrMuxedAccount(hex2bin(self::ED25519_2));
        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $amount = new BigInteger("5000000000");
        $paymentOp = new XdrPaymentOperation($destination, $asset, $amount);
        $body->setPaymentOp($paymentOp);

        $sourceAccount = new XdrMuxedAccount(hex2bin(self::ED25519_1));
        $operation = new XdrOperation($body, $sourceAccount);
        $encoded = $operation->encode();
        $decoded = XdrOperation::decode(new XdrBuffer($encoded));

        $this->assertNotNull($decoded->getSourceAccount());
        $this->assertEquals(self::ED25519_1, bin2hex($decoded->getSourceAccount()->getEd25519()));
        $this->assertEquals(XdrOperationType::PAYMENT, $decoded->getBody()->getType()->getValue());
    }

    /**
     * Test multiple operation types in sequence
     */
    public function testMultipleOperationTypesSequence(): void
    {
        $operations = [
            $this->createCreateAccountOperation(),
            $this->createPaymentOperation(),
            $this->createManageSellOfferOperation(),
            $this->createSetOptionsOperation(),
            $this->createBumpSequenceOperation(),
        ];

        foreach ($operations as $operation) {
            $encoded = $operation->encode();
            $decoded = XdrOperation::decode(new XdrBuffer($encoded));

            $this->assertEquals(
                $operation->getBody()->getType()->getValue(),
                $decoded->getBody()->getType()->getValue()
            );
        }
    }

    /**
     * Test operation body with all operation types
     */
    public function testOperationBodyAllTypes(): void
    {
        $typesToTest = [
            XdrOperationType::CREATE_ACCOUNT,
            XdrOperationType::PAYMENT,
            XdrOperationType::MANAGE_SELL_OFFER,
            XdrOperationType::MANAGE_BUY_OFFER,
            XdrOperationType::CREATE_PASSIVE_SELL_OFFER,
            XdrOperationType::SET_OPTIONS,
            XdrOperationType::CHANGE_TRUST,
            XdrOperationType::ACCOUNT_MERGE,
            XdrOperationType::MANAGE_DATA,
            XdrOperationType::BUMP_SEQUENCE,
        ];

        foreach ($typesToTest as $typeValue) {
            $body = $this->createOperationBodyForType($typeValue);
            $encoded = $body->encode();
            $decoded = XdrOperationBody::decode(new XdrBuffer($encoded));

            $this->assertEquals($typeValue, $decoded->getType()->getValue(),
                "Failed for operation type: $typeValue");
        }
    }

    private function createCreateAccountOperation(): XdrOperation
    {
        $type = new XdrOperationType(XdrOperationType::CREATE_ACCOUNT);
        $body = new XdrOperationBody($type);
        $destination = XdrAccountID::fromAccountId(self::ACCOUNT_ID_1);
        $startingBalance = new BigInteger("10000000000");
        $createAccountOp = new XdrCreateAccountOperation($destination, $startingBalance);
        $body->setCreateAccountOp($createAccountOp);
        return new XdrOperation($body);
    }

    private function createPaymentOperation(): XdrOperation
    {
        $type = new XdrOperationType(XdrOperationType::PAYMENT);
        $body = new XdrOperationBody($type);
        $destination = new XdrMuxedAccount(hex2bin(self::ED25519_1));
        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $amount = new BigInteger("5000000000");
        $paymentOp = new XdrPaymentOperation($destination, $asset, $amount);
        $body->setPaymentOp($paymentOp);
        return new XdrOperation($body);
    }

    private function createManageSellOfferOperation(): XdrOperation
    {
        $type = new XdrOperationType(XdrOperationType::MANAGE_SELL_OFFER);
        $body = new XdrOperationBody($type);
        $selling = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $buying = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $amount = new BigInteger("100000000");
        $price = new XdrPrice(100, 99);
        $offerId = 12345;
        $manageSellOfferOp = new XdrManageSellOfferOperation($selling, $buying, $amount, $price, $offerId);
        $body->setManageSellOfferOp($manageSellOfferOp);
        return new XdrOperation($body);
    }

    private function createSetOptionsOperation(): XdrOperation
    {
        $type = new XdrOperationType(XdrOperationType::SET_OPTIONS);
        $body = new XdrOperationBody($type);
        $setOptionsOp = new XdrSetOptionsOperation();
        $setOptionsOp->setMasterWeight(100);
        $body->setSetOptionsOp($setOptionsOp);
        return new XdrOperation($body);
    }

    private function createBumpSequenceOperation(): XdrOperation
    {
        $type = new XdrOperationType(XdrOperationType::BUMP_SEQUENCE);
        $body = new XdrOperationBody($type);
        $bumpTo = new XdrSequenceNumber(new BigInteger("9999999999"));
        $bumpSequenceOp = new XdrBumpSequenceOperation($bumpTo);
        $body->setBumpSequenceOp($bumpSequenceOp);
        return new XdrOperation($body);
    }

    private function createOperationBodyForType(int $typeValue): XdrOperationBody
    {
        $type = new XdrOperationType($typeValue);
        $body = new XdrOperationBody($type);

        switch ($typeValue) {
            case XdrOperationType::CREATE_ACCOUNT:
                $destination = XdrAccountID::fromAccountId(self::ACCOUNT_ID_1);
                $startingBalance = new BigInteger("10000000000");
                $body->setCreateAccountOp(new XdrCreateAccountOperation($destination, $startingBalance));
                break;

            case XdrOperationType::PAYMENT:
                $destination = new XdrMuxedAccount(hex2bin(self::ED25519_1));
                $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
                $amount = new BigInteger("5000000000");
                $body->setPaymentOp(new XdrPaymentOperation($destination, $asset, $amount));
                break;

            case XdrOperationType::MANAGE_SELL_OFFER:
                $selling = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
                $buying = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
                $amount = new BigInteger("100000000");
                $price = new XdrPrice(100, 99);
                $body->setManageSellOfferOp(new XdrManageSellOfferOperation($selling, $buying, $amount, $price, 0));
                break;

            case XdrOperationType::MANAGE_BUY_OFFER:
                $selling = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
                $buying = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
                $amount = new BigInteger("200000000");
                $price = new XdrPrice(99, 100);
                $body->setManageBuyOfferOp(new XdrManageBuyOfferOperation($selling, $buying, $amount, $price, 0));
                break;

            case XdrOperationType::CREATE_PASSIVE_SELL_OFFER:
                $selling = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
                $buying = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
                $amount = new BigInteger("150000000");
                $price = new XdrPrice(50, 49);
                $body->setCreatePassiveSellOfferOp(new XdrCreatePassiveSellOfferOperation($selling, $buying, $amount, $price));
                break;

            case XdrOperationType::SET_OPTIONS:
                $body->setSetOptionsOp(new XdrSetOptionsOperation());
                break;

            case XdrOperationType::CHANGE_TRUST:
                $changeTrustAsset = new XdrChangeTrustAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
                $limit = new BigInteger("922337203685477580");
                $body->setChangeTrustOp(new XdrChangeTrustOperation($changeTrustAsset, $limit));
                break;

            case XdrOperationType::ACCOUNT_MERGE:
                $destination = new XdrMuxedAccount(hex2bin(self::ED25519_2));
                $body->setAccountMergeOp(new XdrAccountMergeOperation($destination));
                break;

            case XdrOperationType::MANAGE_DATA:
                $key = "testkey";
                $value = new XdrDataValue("testvalue");
                $body->setManageDataOperation(new XdrManageDataOperation($key, $value));
                break;

            case XdrOperationType::BUMP_SEQUENCE:
                $bumpTo = new XdrSequenceNumber(new BigInteger("9999999999"));
                $body->setBumpSequenceOp(new XdrBumpSequenceOperation($bumpTo));
                break;
        }

        return $body;
    }
}
