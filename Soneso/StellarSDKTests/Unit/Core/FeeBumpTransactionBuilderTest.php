<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Core;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\CreateAccountOperation;
use Soneso\StellarSDK\FeeBumpTransaction;
use Soneso\StellarSDK\FeeBumpTransactionBuilder;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\TransactionBuilder;

/**
 * Unit tests for FeeBumpTransactionBuilder
 *
 * Tests constructor, setters, build validation, and exception cases.
 */
class FeeBumpTransactionBuilderTest extends TestCase
{
    private const TEST_ACCOUNT_ID = 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H';
    private const TEST_DESTINATION = 'GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3DANUBER3WPR';
    private const TEST_FEE_PAYER = 'GBB4JST32UWKOLBER5LPGFDSQ5CSYDKXZWCOKIIF4BXSPMCRGQFU6WBA';

    private function createInnerTransaction(int $baseFee = 100): \Soneso\StellarSDK\Transaction
    {
        $account = new Account(self::TEST_ACCOUNT_ID, new BigInteger(1000));
        $operation = new CreateAccountOperation(self::TEST_DESTINATION, "100");

        return (new TransactionBuilder($account))
            ->addOperation($operation)
            ->setMaxOperationFee($baseFee)
            ->build();
    }

    public function testConstructor(): void
    {
        $innerTx = $this->createInnerTransaction();
        $builder = new FeeBumpTransactionBuilder($innerTx);

        $this->assertInstanceOf(FeeBumpTransactionBuilder::class, $builder);
    }

    public function testSetFeeAccount(): void
    {
        $innerTx = $this->createInnerTransaction();
        $builder = new FeeBumpTransactionBuilder($innerTx);

        $result = $builder->setFeeAccount(self::TEST_FEE_PAYER);

        $this->assertInstanceOf(FeeBumpTransactionBuilder::class, $result);
        $this->assertSame($builder, $result); // Fluent interface
    }

    public function testSetMuxedFeeAccount(): void
    {
        $innerTx = $this->createInnerTransaction();
        $builder = new FeeBumpTransactionBuilder($innerTx);

        $muxedAccount = MuxedAccount::fromAccountId(self::TEST_FEE_PAYER);
        $result = $builder->setMuxedFeeAccount($muxedAccount);

        $this->assertInstanceOf(FeeBumpTransactionBuilder::class, $result);
        $this->assertSame($builder, $result); // Fluent interface
    }

    public function testSetBaseFee(): void
    {
        $innerTx = $this->createInnerTransaction();
        $builder = new FeeBumpTransactionBuilder($innerTx);

        $result = $builder->setBaseFee(200);

        $this->assertInstanceOf(FeeBumpTransactionBuilder::class, $result);
        $this->assertSame($builder, $result); // Fluent interface
    }

    public function testBuildSuccess(): void
    {
        $innerTx = $this->createInnerTransaction();
        $builder = new FeeBumpTransactionBuilder($innerTx);

        $feeBumpTx = $builder
            ->setFeeAccount(self::TEST_FEE_PAYER)
            ->setBaseFee(200)
            ->build();

        $this->assertInstanceOf(FeeBumpTransaction::class, $feeBumpTx);
    }

    public function testBuildWithMuxedFeeAccount(): void
    {
        $innerTx = $this->createInnerTransaction();
        $builder = new FeeBumpTransactionBuilder($innerTx);

        $muxedAccount = MuxedAccount::fromAccountId(self::TEST_FEE_PAYER);
        $feeBumpTx = $builder
            ->setMuxedFeeAccount($muxedAccount)
            ->setBaseFee(200)
            ->build();

        $this->assertInstanceOf(FeeBumpTransaction::class, $feeBumpTx);
    }

    public function testBuildWithoutFeeAccountThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("fee account has to be set");

        $innerTx = $this->createInnerTransaction();
        $builder = new FeeBumpTransactionBuilder($innerTx);

        $builder->setBaseFee(200)->build();
    }

    public function testBuildWithoutBaseFeeThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("base fee has to be set");

        $innerTx = $this->createInnerTransaction();
        $builder = new FeeBumpTransactionBuilder($innerTx);

        $builder->setFeeAccount(self::TEST_FEE_PAYER)->build();
    }

    public function testSetBaseFeeMinimumThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("base fee can not be smaller than");

        $innerTx = $this->createInnerTransaction();
        $builder = new FeeBumpTransactionBuilder($innerTx);

        $builder->setBaseFee(50); // Below minimum of 100
    }

    public function testSetBaseFeeLowerThanInnerThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("base fee cannot be lower than provided inner transaction base fee");

        // Create inner transaction with base fee of 500
        $innerTx = $this->createInnerTransaction(500);
        $builder = new FeeBumpTransactionBuilder($innerTx);

        // Try to set base fee lower than inner's base fee
        $builder->setBaseFee(200);
    }

    public function testFluentInterface(): void
    {
        $innerTx = $this->createInnerTransaction();

        $feeBumpTx = (new FeeBumpTransactionBuilder($innerTx))
            ->setFeeAccount(self::TEST_FEE_PAYER)
            ->setBaseFee(200)
            ->build();

        $this->assertInstanceOf(FeeBumpTransaction::class, $feeBumpTx);
    }

    public function testSetBaseFeeEqualToInner(): void
    {
        // Create inner transaction with base fee of 100
        $innerTx = $this->createInnerTransaction(100);

        // Set base fee equal to inner's base fee (should succeed)
        $feeBumpTx = (new FeeBumpTransactionBuilder($innerTx))
            ->setFeeAccount(self::TEST_FEE_PAYER)
            ->setBaseFee(100)
            ->build();

        $this->assertInstanceOf(FeeBumpTransaction::class, $feeBumpTx);
    }

    public function testSetBaseFeeHigherThanInner(): void
    {
        // Create inner transaction with base fee of 100
        $innerTx = $this->createInnerTransaction(100);

        // Set base fee higher than inner's base fee
        $feeBumpTx = (new FeeBumpTransactionBuilder($innerTx))
            ->setFeeAccount(self::TEST_FEE_PAYER)
            ->setBaseFee(500)
            ->build();

        $this->assertInstanceOf(FeeBumpTransaction::class, $feeBumpTx);
    }
}
