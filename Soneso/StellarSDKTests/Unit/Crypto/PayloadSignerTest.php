<?php

namespace Soneso\StellarSDKTests\Unit\Crypto;

use DateTime;
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

    private const ACCOUNT_ID = "GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ";

    /** The 32-byte ed25519 public key behind ACCOUNT_ID and behind both muxed ids below. */
    private const ED25519_HEX = "3f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a";

    /** ACCOUNT_ID muxed with multiplexing id 1 and 2: same key, two different addresses. */
    private const MUXED_ID_1 = "MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAAGZFQ";
    private const MUXED_ID_2 = "MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAALIWQ";

    private const MUXED_MESSAGE = "a signed payload signer takes an ed25519 account id (G...), not a muxed account id (M...)";

    public function testConstructorEnforcesPayloadBounds(): void
    {
        $accountId = self::ACCOUNT_ID;

        try {
            SignedPayloadSigner::fromAccountId($accountId, "");
            $this->fail("A zero-length payload should raise at construction");
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString("invalid payload length 0, must be between 1 and 64 bytes", $e->getMessage());
        }

        try {
            SignedPayloadSigner::fromAccountId($accountId, str_repeat("\x01", 65));
            $this->fail("A 65-byte payload should raise at construction");
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString("invalid payload length 65", $e->getMessage());
        }

        // The boundaries are inclusive: 1 and 64 bytes construct fine.
        $this->assertSame("\x01", SignedPayloadSigner::fromAccountId($accountId, "\x01")->getPayload());
        $this->assertSame(64, strlen(SignedPayloadSigner::fromAccountId($accountId, str_repeat("\x02", 64))->getPayload()));
    }

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

    public function testSignPayloadSignerHintComputation(): void
    {
        $seedBytes = hex2bin($this->seed);
        $keypair = KeyPair::fromPrivateKey($seedBytes);
        $keyHint = $keypair->getHint();
        // The hint is the last 4 bytes of the payload (the whole payload, right
        // padded with zeros, when shorter than 4) XORed byte-for-byte with the
        // key hint (CAP-40). Covers the empty edge, the sub-4-byte padding, the
        // 4-byte boundary, and the longer-than-4 slice, with high bytes to
        // confirm binary safety.
        $source = "\xff\x80\x01\x7f\x00\xfe\x42\x99";
        foreach ([0, 1, 2, 3, 4, 8] as $len) {
            $payload = substr($source, 0, $len);
            $payloadHint = $len >= 4
                ? substr($payload, $len - 4, 4)
                : $payload . str_repeat("\x00", 4 - $len);
            $expected = '';
            for ($i = 0; $i < 4; $i++) {
                $expected .= chr(ord($payloadHint[$i]) ^ ord($keyHint[$i]));
            }
            $signature = $keypair->signPayloadDecorated($payload);
            $this->assertEquals($expected, $signature->getHint(), "hint mismatch for payload length $len");
        }
        // An empty payload leaves the key hint unchanged.
        $this->assertEquals($keyHint, $keypair->signPayloadDecorated("")->getHint());
    }

    public function testItCreatesSignedPayloadSigner(): void {
        $accountStrKey = self::ACCOUNT_ID;
        $p16 = "0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f20";
        $payload = hex2bin($p16);
        $xdrAccountID = new XdrAccountID($accountStrKey);
        $signedPayloadSigner = new SignedPayloadSigner($xdrAccountID, $payload);
        $signerKey = Signer::signedPayload($signedPayloadSigner);
        $this->assertEquals($signerKey->getSignedPayload()->getPayload(), $signedPayloadSigner->getPayload());
        $this->assertEquals(self::ED25519_HEX, bin2hex($signerKey->getSignedPayload()->getEd25519()));
    }

    /**
     * CAP-40 gives the signer a bare ed25519 public key, so a muxed account id has
     * to be refused: its multiplexing id has nowhere to go, and two customers on
     * one underlying key would otherwise share a single signer.
     */
    public function testMuxedAccountIdIsRejectedOnEveryConstructionRoute(): void {
        $payload = hex2bin("0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f20");

        foreach ([self::MUXED_ID_1, self::MUXED_ID_2] as $muxedAccountId) {
            try {
                SignedPayloadSigner::fromAccountId($muxedAccountId, $payload);
                $this->fail("fromAccountId accepted the muxed account id $muxedAccountId");
            } catch (\InvalidArgumentException $e) {
                $this->assertSame(self::MUXED_MESSAGE, $e->getMessage());
            }

            try {
                new SignedPayloadSigner(new XdrAccountID($muxedAccountId), $payload);
                $this->fail("The constructor accepted the muxed account id $muxedAccountId");
            } catch (\InvalidArgumentException $e) {
                $this->assertSame(self::MUXED_MESSAGE, $e->getMessage());
            }
        }

        // The ed25519 account id the two muxed addresses share is accepted, and
        // yields that key.
        $signer = SignedPayloadSigner::fromAccountId(self::ACCOUNT_ID, $payload);
        $this->assertSame(self::ACCOUNT_ID, $signer->getSignerAccountId()->getAccountId());
        $this->assertSame(
            self::ED25519_HEX,
            bin2hex(Signer::signedPayload($signer)->getSignedPayload()->getEd25519())
        );

        // The remaining two routes take raw key bytes, so they reach the same
        // ed25519 account id and cannot express a muxed one.
        $fromBytes = SignedPayloadSigner::fromPublicKey(hex2bin(self::ED25519_HEX), $payload);
        $this->assertSame(self::ACCOUNT_ID, $fromBytes->getSignerAccountId()->getAccountId());
        $decoded = StrKey::decodeSignedPayload(StrKey::encodeSignedPayload($signer));
        $this->assertSame(self::ACCOUNT_ID, $decoded->getSignerAccountId()->getAccountId());
    }

    /**
     * The two consumers of a signer decode its account id strictly: each refuses a
     * muxed account id rather than reading the key it multiplexes. The constructor
     * is what normally keeps an M address out of a signer, so reflection stands in
     * for a caller that reaches the field directly.
     */
    public function testSignerConsumersDecodeTheAccountIdStrictly(): void {
        $payload = hex2bin("0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f20");
        $signer = SignedPayloadSigner::fromAccountId(self::ACCOUNT_ID, $payload);

        $accountIdField = new \ReflectionProperty(XdrAccountID::class, 'accountId');
        $accountIdField->setAccessible(true);
        $accountIdField->setValue($signer->getSignerAccountId(), self::MUXED_ID_1);

        try {
            StrKey::encodeSignedPayload($signer);
            $this->fail("StrKey::encodeSignedPayload demuxed a muxed account id");
        } catch (\InvalidArgumentException $e) {
            $this->assertSame("G-strkey must be 56 characters long, 69 characters given", $e->getMessage());
        }

        try {
            Signer::signedPayload($signer);
            $this->fail("Signer::signedPayload demuxed a muxed account id");
        } catch (\InvalidArgumentException $e) {
            $this->assertSame("G-strkey must be 56 characters long, 69 characters given", $e->getMessage());
        }
    }

    public function testSignedPayloadSignerRejectsANonEd25519AccountId(): void {
        $payload = hex2bin("0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f20");
        $contractId = "CA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAXE";

        foreach (["", "not-a-strkey", $contractId] as $accountId) {
            try {
                SignedPayloadSigner::fromAccountId($accountId, $payload);
                $this->fail("fromAccountId accepted the non-ed25519 account id \"$accountId\"");
            } catch (\InvalidArgumentException $e) {
                $this->assertSame("invalid ed25519 account id: $accountId", $e->getMessage());
            }
        }

        // A seed passed where an account id belongs must not reach the message,
        // which travels to logs.
        $seed = "SDJHRQF4GCMIIKAAAQ6IHY42X73FQFLHUULAPSKKD4DFDM7UXWWCRHBE";
        try {
            SignedPayloadSigner::fromAccountId($seed, $payload);
            $this->fail("fromAccountId accepted a secret seed");
        } catch (\InvalidArgumentException $e) {
            $this->assertSame(
                "a signed payload signer takes an ed25519 account id (G...), not a secret seed (S...)",
                $e->getMessage()
            );
            $this->assertStringNotContainsString($seed, $e->getMessage());
        }
    }

    public function testValidSignedPayloadEncode(): void {

        // Valid signed payload with an ed25519 public key and a 32-byte payload.
        $accountStrKey = self::ACCOUNT_ID;
        $p16 = "0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f20";
        $payload = hex2bin($p16);
        $xdrAccountID = new XdrAccountID($accountStrKey);
        $signedPayloadSigner = new SignedPayloadSigner($xdrAccountID, $payload);
        $encodedSignedPayload = StrKey::encodeSignedPayload($signedPayloadSigner);
        $this->assertEquals("PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAQACAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUPB6IBZGM", $encodedSignedPayload);

        // Valid signed payload with an ed25519 public key and a 29-byte payload.
        $p16 = "0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d";
        $payload = hex2bin($p16);
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
        $accountStrKey = self::ACCOUNT_ID;
        $payloadStr = "0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f20";
        $payload = hex2bin($payloadStr);
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