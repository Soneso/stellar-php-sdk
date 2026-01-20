<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Core;

use Exception;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\AbstractOperation;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\CreateAccountOperation;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\PaymentOperation;
use Soneso\StellarSDK\SetOptionsOperation;
use Soneso\StellarSDK\ChangeTrustOperation;
use Soneso\StellarSDK\ManageDataOperation;
use Soneso\StellarSDK\BumpSequenceOperation;
use Soneso\StellarSDK\AccountMergeOperation;
use Soneso\StellarSDK\Xdr\XdrOperation;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;

class OperationTest extends TestCase
{
    private KeyPair $sourceKeyPair;
    private string $sourceAccountId;

    public function setUp(): void
    {
        error_reporting(E_ALL);
        $this->sourceKeyPair = KeyPair::random();
        $this->sourceAccountId = $this->sourceKeyPair->getAccountId();
    }

    public function testSourceAccountGetterSetter()
    {
        $operation = new CreateAccountOperation("GAKL4XMWLXQKYNYR6ZDVLZT5FXQK3PKC4GZW7OKJX4KQLJKRWBWDXNYK", "100");

        assertNull($operation->getSourceAccount());

        $muxedAccount = MuxedAccount::fromAccountId($this->sourceAccountId);
        $operation->setSourceAccount($muxedAccount);

        assertNotNull($operation->getSourceAccount());
        assertEquals($this->sourceAccountId, $operation->getSourceAccount()->getAccountId());

        $operation->setSourceAccount(null);
        assertNull($operation->getSourceAccount());
    }

    public function testToXdrAmount()
    {
        $amount = AbstractOperation::toXdrAmount("100.5000000");
        assertEquals("1005000000", $amount->toString());

        $amount = AbstractOperation::toXdrAmount("0.0000001");
        assertEquals("1", $amount->toString());

        $amount = AbstractOperation::toXdrAmount("922337203685.4775807");
        assertEquals("9223372036854775807", $amount->toString());
    }

    public function testFromXdrAmount()
    {
        $stroops = new BigInteger("1005000000");
        $amount = AbstractOperation::fromXdrAmount($stroops);
        assertEquals("100.5000000", $amount);

        $stroops = new BigInteger("1");
        $amount = AbstractOperation::fromXdrAmount($stroops);
        assertEquals("0.0000001", $amount);

        $stroops = new BigInteger("9223372036854775807");
        $amount = AbstractOperation::fromXdrAmount($stroops);
        assertEquals("922337203685.4775807", $amount);
    }

    public function testOperationToXdr()
    {
        $destination = "GAKL4XMWLXQKYNYR6ZDVLZT5FXQK3PKC4GZW7OKJX4KQLJKRWBWDXNYK";
        $amount = "100.25";

        $operation = new CreateAccountOperation($destination, $amount);
        $operation->setSourceAccount(MuxedAccount::fromAccountId($this->sourceAccountId));

        $xdr = $operation->toXdr();
        assertNotNull($xdr);
        assertNotNull($xdr->getSourceAccount());
        assertEquals($this->sourceAccountId, MuxedAccount::fromXdr($xdr->getSourceAccount())->getAccountId());
    }

    public function testCreateAccountFromXdr()
    {
        $destination = "GAKL4XMWLXQKYNYR6ZDVLZT5FXQK3PKC4GZW7OKJX4KQLJKRWBWDXNYK";
        $amount = "100.25";

        $operation = new CreateAccountOperation($destination, $amount);
        $operation->setSourceAccount(MuxedAccount::fromAccountId($this->sourceAccountId));

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(CreateAccountOperation::class, get_class($parsed));
        assertEquals($this->sourceAccountId, $parsed->getSourceAccount()->getAccountId());
        assertEquals($destination, $parsed->getDestination());
        assertEquals("100.2500000", $parsed->getStartingBalance());
    }

    public function testPaymentFromXdr()
    {
        $destinationId = "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO";
        $destination = MuxedAccount::fromAccountId($destinationId);
        $asset = Asset::native();
        $amount = "50.0";

        $operation = new PaymentOperation($destination, $asset, $amount);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(PaymentOperation::class, get_class($parsed));
        assertEquals($destinationId, $parsed->getDestination()->getAccountId());
        assertEquals(Asset::TYPE_NATIVE, $parsed->getAsset()->getType());
        assertEquals("50.0000000", $parsed->getAmount());
    }

    public function testSetOptionsFromXdr()
    {
        $inflationDest = "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO";
        $operation = new SetOptionsOperation(
            inflationDestination: $inflationDest,
            clearFlags: 2,
            setFlags: 1,
            masterKeyWeight: 10,
            lowThreshold: 5,
            mediumThreshold: 10,
            highThreshold: 15,
            homeDomain: "example.com"
        );

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(SetOptionsOperation::class, get_class($parsed));
        assertEquals($inflationDest, $parsed->getInflationDestination());
        assertEquals(1, $parsed->getSetFlags());
        assertEquals(2, $parsed->getClearFlags());
        assertEquals(10, $parsed->getMasterKeyWeight());
        assertEquals(5, $parsed->getLowThreshold());
        assertEquals(10, $parsed->getMediumThreshold());
        assertEquals(15, $parsed->getHighThreshold());
        assertEquals("example.com", $parsed->getHomeDomain());
    }

    public function testChangeTrustFromXdr()
    {
        $issuerId = "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO";
        $asset = Asset::createNonNativeAsset("USD", $issuerId);
        $limit = "1000.0000000";

        $operation = new ChangeTrustOperation($asset, $limit);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(ChangeTrustOperation::class, get_class($parsed));
        assertEquals("USD", $parsed->getAsset()->getCode());
        assertEquals($issuerId, $parsed->getAsset()->getIssuer());
        assertEquals($limit, $parsed->getLimit());
    }

    public function testManageDataFromXdr()
    {
        $name = "test_key";
        $value = "test_value";

        $operation = new ManageDataOperation($name, $value);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(ManageDataOperation::class, get_class($parsed));
        assertEquals($name, $parsed->getKey());
        assertEquals($value, $parsed->getValue());
    }

    public function testManageDataDeleteFromXdr()
    {
        $name = "test_key";

        $operation = new ManageDataOperation($name, null);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(ManageDataOperation::class, get_class($parsed));
        assertEquals($name, $parsed->getKey());
        assertNull($parsed->getValue());
    }

    public function testBumpSequenceFromXdr()
    {
        $bumpTo = new BigInteger("123456789");

        $operation = new BumpSequenceOperation($bumpTo);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(BumpSequenceOperation::class, get_class($parsed));
        assertEquals($bumpTo->toString(), $parsed->getBumpTo()->toString());
    }

    public function testAccountMergeFromXdr()
    {
        $destinationId = "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO";
        $destination = MuxedAccount::fromAccountId($destinationId);

        $operation = new AccountMergeOperation($destination);

        $xdr = $operation->toXdr();
        $parsed = AbstractOperation::fromXdr($xdr);

        assertEquals(AccountMergeOperation::class, get_class($parsed));
        assertEquals($destinationId, $parsed->getDestination()->getAccountId());
    }

    public function testOperationWithoutSourceAccount()
    {
        $destination = "GB7TAYRUZGE6TVT7NHP5SMIZRNQA6PLM423EYISAOAP3MKYIQMVYP2JO";
        $operation = new CreateAccountOperation($destination, "100");

        $xdr = $operation->toXdr();
        assertNull($xdr->getSourceAccount());

        $parsed = AbstractOperation::fromXdr($xdr);
        assertNull($parsed->getSourceAccount());
    }
}
