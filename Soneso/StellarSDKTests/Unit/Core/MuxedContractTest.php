<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Core;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\MuxedContract;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCAddressType;

/**
 * Unit tests for MuxedContract.
 *
 * The muxed contract ids are the SEP-0023 test vectors; the one for the maximum id
 * was rendered by the reference stellar-xdr tool.
 *
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0023.md
 */
class MuxedContractTest extends TestCase
{
    private const CONTRACT_ID = 'CA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUWDA';
    private const CONTRACT_HASH_HEX = '3f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a';

    /** CONTRACT_ID with id 0. */
    private const MUXED_ID_ZERO = 'WA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAAAWWC';

    /** CONTRACT_ID with id 9223372036854775808 (2^63), held as PHP_INT_MIN. */
    private const MUXED_ID_MAX_INT64_PLUS_ONE = 'WA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAACWJY';

    /** CONTRACT_ID with id 18446744073709551615 (2^64 - 1), held as -1. */
    private const MUXED_ID_MAX_UINT64 = 'WA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJV7777777777777GMO';

    /** CA3D5KRY... with id 123456. */
    private const MUXED_ID_123456 = 'WA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAAAAAAAAAPCIA6IG';

    public function testConstructionFromContractAndId(): void
    {
        $this->assertSame(self::MUXED_ID_ZERO, (new MuxedContract(self::CONTRACT_ID, 0))->getAddress());
        $this->assertSame(
            self::MUXED_ID_MAX_INT64_PLUS_ONE,
            (new MuxedContract(self::CONTRACT_ID, PHP_INT_MIN))->getAddress()
        );
        $this->assertSame(self::MUXED_ID_MAX_UINT64, (new MuxedContract(self::CONTRACT_HASH_HEX, -1))->getAddress());
        $this->assertSame(
            self::MUXED_ID_123456,
            (new MuxedContract('CA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAXE', 123456))->getAddress()
        );
    }

    public function testFromAddressReadsAMuxedContractId(): void
    {
        $zero = MuxedContract::fromAddress(self::MUXED_ID_ZERO);
        $this->assertSame(self::CONTRACT_ID, $zero->getContractId());
        $this->assertSame(0, $zero->getId());

        $max = MuxedContract::fromAddress(self::MUXED_ID_MAX_UINT64);
        $this->assertSame(self::CONTRACT_ID, $max->getContractId());
        $this->assertSame(-1, $max->getId());
        $this->assertSame(self::MUXED_ID_MAX_UINT64, $max->getAddress());
    }

    public function testFromAddressReadsAContractIdWithoutId(): void
    {
        $contract = MuxedContract::fromAddress(self::CONTRACT_ID);

        $this->assertSame(self::CONTRACT_ID, $contract->getContractId());
        $this->assertNull($contract->getId());
        $this->assertSame(self::CONTRACT_ID, $contract->getAddress());
    }

    public function testXdrArmsRoundTrip(): void
    {
        $muxed = (new MuxedContract(self::CONTRACT_ID, PHP_INT_MIN))->toXdrSCAddress();
        $this->assertSame(XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_CONTRACT, $muxed->type->value);
        $this->assertSame(PHP_INT_MIN, $muxed->muxedContract->id);
        $this->assertSame(self::CONTRACT_HASH_HEX, bin2hex($muxed->muxedContract->contractId));
        $this->assertSame(PHP_INT_MIN, MuxedContract::fromXdrSCAddress($muxed)->getId());

        $contract = (new MuxedContract(self::CONTRACT_ID))->toXdrSCAddress();
        $this->assertSame(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT, $contract->type->value);
        $this->assertSame(self::CONTRACT_HASH_HEX, $contract->getCanonicalContractIdHex());
        $fromContract = MuxedContract::fromXdrSCAddress($contract);
        $this->assertSame(self::CONTRACT_ID, $fromContract->getContractId());
        $this->assertNull($fromContract->getId());
    }

    public function testToAddressUsesTheMatchingType(): void
    {
        $muxed = (new MuxedContract(self::CONTRACT_ID, 0))->toAddress();
        $this->assertSame(Address::TYPE_MUXED_CONTRACT, $muxed->getType());
        $this->assertSame(self::MUXED_ID_ZERO, $muxed->getMuxedContractId());

        $contract = (new MuxedContract(self::CONTRACT_ID))->toAddress();
        $this->assertSame(Address::TYPE_CONTRACT, $contract->getType());
        $this->assertSame(self::CONTRACT_HASH_HEX, $contract->getContractId());
    }

    public function testFromXdrSCAddressRejectsAnotherArm(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected the contract or muxed contract arm of an SCAddress, got SC_ADDRESS_TYPE_MUXED_ACCOUNT'
        );
        MuxedContract::fromXdrSCAddress(
            XdrSCAddress::forAccountId('MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAAAJLK')
        );
    }

    public function testFromAddressRejectsAnotherStrkey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected a contract (C...) or muxed contract (W...) address, got: '
                . 'GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ'
        );
        MuxedContract::fromAddress('GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ');
    }

    public function testConstructorRejectsAnInvalidContractIdNamingIt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid contract id "CA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUWDB": invalid checksum in encoded data'
        );
        new MuxedContract('CA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUWDB', 1);
    }
}
