<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Core;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\CryptoKeyType;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrMuxedAccount;

/**
 * Unit tests for MuxedAccount.
 *
 * The account id and XDR literals are the SEP-0023 test vectors; the base64 XDR
 * literals were produced with the reference stellar-xdr encoder.
 *
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0023.md
 */
class MuxedAccountTest extends TestCase
{
    /** SEP-0023 ed25519 account id. */
    private const ED25519_ACCOUNT_ID = 'GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ';

    /** SEP-0023 muxed account id for ED25519_ACCOUNT_ID with id 0. */
    private const MUXED_ID_ZERO = 'MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUQ';

    /** SEP-0023 muxed account id for ED25519_ACCOUNT_ID with id 9223372036854775808 (2^63). */
    private const MUXED_ID_MAX_INT64_PLUS_ONE = 'MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAAAJLK';

    /** Muxed account id for ED25519_ACCOUNT_ID with id 1234. */
    private const MUXED_ID_1234 = 'MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAE2JUG6';

    private const XDR_ED25519 = 'AAAAAD8MNL+TrQ2ZcdBMzJD3BVEcg4qtlzSkovsNegP8f+ia';
    private const XDR_MUXED_ID_ZERO = 'AAABAAAAAAAAAAAAPww0v5OtDZlx0EzMkPcFURyDiq2XNKSi+w16A/x/6Jo=';
    private const XDR_MUXED_ID_1234 = 'AAABAAAAAAAAAATSPww0v5OtDZlx0EzMkPcFURyDiq2XNKSi+w16A/x/6Jo=';
    private const XDR_MUXED_ID_MAX_INT64_PLUS_ONE = 'AAABAIAAAAAAAAAAPww0v5OtDZlx0EzMkPcFURyDiq2XNKSi+w16A/x/6Jo=';

    public function testFromAccountIdParsesIdZero(): void
    {
        $muxed = MuxedAccount::fromAccountId(self::MUXED_ID_ZERO);

        $this->assertSame(0, $muxed->getId());
        $this->assertSame(self::ED25519_ACCOUNT_ID, $muxed->getEd25519AccountId());
        $this->assertSame(self::MUXED_ID_ZERO, $muxed->getAccountId());
    }

    public function testConstructorWithIdZeroYieldsMuxedAccountId(): void
    {
        $muxed = new MuxedAccount(self::ED25519_ACCOUNT_ID, 0);

        $this->assertSame(self::MUXED_ID_ZERO, $muxed->getAccountId());
    }

    public function testIdZeroEncodesAsMed25519(): void
    {
        $xdr = (new MuxedAccount(self::ED25519_ACCOUNT_ID, 0))->toXdr();

        $this->assertSame(CryptoKeyType::KEY_TYPE_MUXED_ED25519, $xdr->getDiscriminant());
        $this->assertNotNull($xdr->getMed25519());
        $this->assertSame(0, $xdr->getMed25519()->getId());
        $this->assertSame(self::XDR_MUXED_ID_ZERO, base64_encode($xdr->encode()));
    }

    public function testFromXdrPreservesIdZero(): void
    {
        $decoded = XdrMuxedAccount::decode(new XdrBuffer(base64_decode(self::XDR_MUXED_ID_ZERO)));

        $muxed = MuxedAccount::fromXdr($decoded);

        $this->assertSame(0, $muxed->getId());
        $this->assertSame(self::MUXED_ID_ZERO, $muxed->getAccountId());
    }

    public function testNonZeroIdRoundTrips(): void
    {
        $muxed = MuxedAccount::fromAccountId(self::MUXED_ID_1234);

        $this->assertSame(1234, $muxed->getId());
        $this->assertSame(self::ED25519_ACCOUNT_ID, $muxed->getEd25519AccountId());
        $this->assertSame(self::MUXED_ID_1234, $muxed->getAccountId());
        $this->assertSame(CryptoKeyType::KEY_TYPE_MUXED_ED25519, $muxed->toXdr()->getDiscriminant());
        $this->assertSame(self::XDR_MUXED_ID_1234, base64_encode($muxed->toXdr()->encode()));
    }

    public function testConstructorWithNonZeroIdYieldsMuxedAccountId(): void
    {
        $muxed = new MuxedAccount(self::ED25519_ACCOUNT_ID, 1234);

        $this->assertSame(self::MUXED_ID_1234, $muxed->getAccountId());
        $this->assertSame(self::XDR_MUXED_ID_1234, base64_encode($muxed->toXdr()->encode()));
    }

    /**
     * Ids above PHP_INT_MAX are held in a signed 64 bit int, so getId() reports
     * a negative number. The 64 bit wire value is unaffected.
     */
    public function testIdAbovePhpIntMaxRoundTrips(): void
    {
        $muxed = MuxedAccount::fromAccountId(self::MUXED_ID_MAX_INT64_PLUS_ONE);

        $this->assertSame(self::MUXED_ID_MAX_INT64_PLUS_ONE, $muxed->getAccountId());
        $this->assertSame(
            self::XDR_MUXED_ID_MAX_INT64_PLUS_ONE,
            base64_encode($muxed->toXdr()->encode())
        );
    }

    public function testNullIdEncodesAsEd25519(): void
    {
        $muxed = MuxedAccount::fromAccountId(self::ED25519_ACCOUNT_ID);

        $this->assertNull($muxed->getId());
        $this->assertSame(self::ED25519_ACCOUNT_ID, $muxed->getAccountId());
        $this->assertSame(CryptoKeyType::KEY_TYPE_ED25519, $muxed->toXdr()->getDiscriminant());
        $this->assertSame(self::XDR_ED25519, base64_encode($muxed->toXdr()->encode()));
    }

    public function testGetXdrDoesNotExposeInternalState(): void
    {
        $muxed = new MuxedAccount(self::ED25519_ACCOUNT_ID, 0);

        $muxed->getXdr()->getMed25519()->setId(1234);

        // The mutated copy is not this account's state.
        $this->assertSame(0, $muxed->getId());
        $this->assertSame(self::MUXED_ID_ZERO, $muxed->getAccountId());
        $this->assertSame(0, $muxed->getXdr()->getMed25519()->getId());
        $this->assertSame(
            CryptoKeyType::KEY_TYPE_MUXED_ED25519,
            $muxed->getXdr()->getDiscriminant()
        );
        $this->assertSame(self::XDR_MUXED_ID_ZERO, base64_encode($muxed->getXdr()->encode()));
    }

    public function testFromAccountIdRejectsUnknownPrefix(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MuxedAccount::fromAccountId('not-an-account-id');
    }

    public function testConstructorRejectsNonEd25519AccountId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new MuxedAccount(self::MUXED_ID_ZERO, 0);
    }

    /**
     * Operation builders that take a MuxedAccount pass it on as an account id
     * string, so a dropped id would silently retarget the payment at the
     * underlying ed25519 account.
     */
    public function testPaymentOperationKeepsMuxedDestinationWithIdZero(): void
    {
        $destination = new MuxedAccount(self::ED25519_ACCOUNT_ID, 0);

        $operation = PaymentOperationBuilder::forMuxedDestinationAccount(
            $destination,
            Asset::native(),
            '10'
        )->build();

        $this->assertSame(0, $operation->getDestination()->getId());
        $this->assertSame(self::MUXED_ID_ZERO, $operation->getDestination()->getAccountId());
    }
}
