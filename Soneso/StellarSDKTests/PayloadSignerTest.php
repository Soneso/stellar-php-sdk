<?php

namespace Soneso\StellarSDKTests;

use DateTime;
use ParagonIE\ConstantTime\Encoding;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\LedgerBounds;
use Soneso\StellarSDK\SignedPayloadSigner;
use Soneso\StellarSDK\Signer;
use Soneso\StellarSDK\TimeBounds;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionPreconditions;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrPreconditions;

class PayloadSignerTest extends TestCase
{
    private string $seed = "1123740522f11bfef6b3671f51e159ccf589ccf8965262dd5f97d1721d383dd4";

    public function testSignPayloadSigner(): void
    {
        $seedBytes = hex2bin($this->seed);
        $keypair = KeyPair::fromPrivateKey($seedBytes);
        $payload = array(1, 2, 3, 4, 5);
        $payloadStr = implode(array_map("chr", $payload));
        $signature = $keypair->signPayloadDecorated($payloadStr);
        $arr = array(0xFF & 252, 65, 0, 50);
        $this->assertEquals(implode(array_map("chr", $arr)),$signature->getHint());
    }

    public function testSignPayloadSignerLessThanHint(): void
    {
        $seedBytes = hex2bin($this->seed);
        $keypair = KeyPair::fromPrivateKey($seedBytes);
        $payload = array(1, 2, 3);
        $payloadStr = implode(array_map("chr", $payload));
        $signature = $keypair->signPayloadDecorated($payloadStr);
        $arr = array(255, 64, 7, 55);
        $this->assertEquals(implode(array_map("chr", $arr)),$signature->getHint());
    }

    public function testItCreatesSignedPayloadSigner(): void {
        $accountStrKey = "GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ";
        $p16 = "0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f20";
        $payload = base_convert($p16, 16, 2);
        $xdrAccountID = new XdrAccountID($accountStrKey);
        $signedPayloadSigner = new SignedPayloadSigner($xdrAccountID, $payload);
        $signerKey = Signer::signedPayload($signedPayloadSigner);
        $this->assertEquals($signerKey->getSignedPayload()->getPayload(), $signedPayloadSigner->getPayload());
        $pkEd25519 = KeyPair::fromAccountId($signedPayloadSigner->getSignerAccountId()->getAccountId())->getPublicKey();
        $this->assertEquals($signerKey->getSignedPayload()->getEd25519(), $pkEd25519);
    }

    public function testValidSignedPayloadEncode(): void {

        // Valid signed payload with an ed25519 public key and a 32-byte payload.
        $accountStrKey = "GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ";
        $p16 = "0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f20";
        $payload = Encoding::hexDecode($p16);
        $xdrAccountID = new XdrAccountID($accountStrKey);
        $signedPayloadSigner = new SignedPayloadSigner($xdrAccountID, $payload);
        $encodedSignedPayload = StrKey::encodeSignedPayload($signedPayloadSigner);
        $this->assertEquals("PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAQACAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUPB6IBZGM", $encodedSignedPayload);

        // Valid signed payload with an ed25519 public key and a 29-byte payload.
        $p16 = "0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d";
        $payload = Encoding::hexDecode($p16);
        $xdrAccountID = new XdrAccountID($accountStrKey);
        $signedPayloadSigner = new SignedPayloadSigner($xdrAccountID, $payload);
        $encodedSignedPayload = StrKey::encodeSignedPayload($signedPayloadSigner);
        $this->assertEquals("PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAOQCAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUAAAAFGBU", $encodedSignedPayload);
    }

    public function testTxPreconditionsConvertToXdr(): void {
        $cond = new TransactionPreconditions();
        $cond->setMinSeqNumber(new BigInteger(91891891));
        $cond->setMinSeqAge(181811);
        $cond->setMinSeqLedgerGap(1991);
        $lb = new LedgerBounds(100,100000);
        $cond->setLedgerBounds($lb);
        $tb = new TimeBounds((new DateTime)->setTimestamp(1651767858), (new DateTime)->setTimestamp(1651967858));
        $cond->setTimeBounds($tb);
        $accountStrKey = "GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ";
        $payloadStr = "0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f20";
        $payload = Encoding::hexDecode($payloadStr);
        $signedPayloadSigner = new SignedPayloadSigner(new XdrAccountID($accountStrKey), $payload);
        $signedKey = Signer::signedPayload($signedPayloadSigner);
        $cond->setExtraSigners([$signedKey]);
        $encoded = $cond->toXdr()->encode();
        $cond2 = TransactionPreconditions::fromXdr(XdrPreconditions::decode(new XdrBuffer($encoded)));
        $this->assertEquals($cond2->getMinSeqNumber(), $cond->getMinSeqNumber());
        $this->assertEquals($cond2->getMinSeqAge(), $cond->getMinSeqAge());
        $this->assertEquals($cond2->getMinSeqLedgerGap(), $cond->getMinSeqLedgerGap());
        $this->assertEquals($cond2->getLedgerBounds()->getMinLedger(), $cond->getLedgerBounds()->getMinLedger());
        $this->assertEquals($cond2->getLedgerBounds()->getMaxLedger(), $cond->getLedgerBounds()->getMaxLedger());
        $this->assertEquals($cond2->getTimeBounds()->getMinTime(), $cond->getTimeBounds()->getMinTime());
        $this->assertEquals($cond2->getTimeBounds()->getMaxTime(), $cond->getTimeBounds()->getMaxTime());
        $this->assertSameSize($cond2->getExtraSigners(), $cond->getExtraSigners());
        $sp1 = $cond->getExtraSigners()[0]->getSignedPayload();
        $sp2 = $cond2->getExtraSigners()[0]->getSignedPayload();
        $this->assertEquals($sp1->getPayload(), $sp2->getPayload());
        $this->assertEquals($sp1->getEd25519(), $sp2->getEd25519());
    }

    public function testTxEnvelopeFromXdr(): void {
        $xdr = "AAAAAgAAAQAAAAAAABODof/acuzxAA9pILE4Qo4ywluEu8QPmzZdt9lqLwuIhryTAAAAZAALmqcAAAAUAAAAAgAAAAEAAAAAYnk1lQAAAABobxaVAAAAAQANnJQAHN7UAAAAAQALmqcAAAAIAAAAAAAAAAEAAAABAAAAAAAAAAAAAAABAAAAAQAAAQAAAAAAABODof/acuzxAA9pILE4Qo4ywluEu8QPmzZdt9lqLwuIhryTAAAAAQAAAQAAAAACTzrbb3aC2IBy/P5SR+6HUM0IKF3u4XY6AiFDhxsJI3NF3+ibAAAAAAAAAAAA5OHAAAAAAAAAAAGIhryTAAAAQCu6e+o3o+skZSo1H8mEjZ0Aw0seyrGjjk+vXmx/PD7RTC2b8RxXF5X/IdCEDiYe/kR8pUBzL1IPsgaVcs0RjQw=";
        $transaction = Transaction::fromEnvelopeBase64XdrString($xdr);
        if ($transaction instanceof Transaction) {
            $cond = $transaction->getPreconditions();
            $this->assertNotNull($cond);
            $this->assertEquals(1652110741, $cond->getTimeBounds()->getMinTime()->getTimestamp());
            $this->assertEquals(1752110741, $cond->getTimeBounds()->getMaxTime()->getTimestamp());
            $this->assertEquals(892052, $cond->getLedgerBounds()->getMinLedger());
            $this->assertEquals(1892052, $cond->getLedgerBounds()->getMaxLedger());
            $this->assertEquals(1, $cond->getMinSeqAge());
            $this->assertEquals(1, $cond->getMinSeqLedgerGap());
            $this->assertCount(0, $cond->getExtraSigners());
        } else {
            $this->fail();
        }

    }
}