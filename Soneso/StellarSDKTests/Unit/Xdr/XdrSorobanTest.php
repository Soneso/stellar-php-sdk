<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrContractExecutable;
use Soneso\StellarSDK\Xdr\XdrContractExecutableType;
use Soneso\StellarSDK\Xdr\XdrContractIDPreimage;
use Soneso\StellarSDK\Xdr\XdrContractIDPreimageType;
use Soneso\StellarSDK\Xdr\XdrCreateContractArgs;
use Soneso\StellarSDK\Xdr\XdrCreateContractArgsV2;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;
use Soneso\StellarSDK\Xdr\XdrInvokeContractArgs;
use Soneso\StellarSDK\Xdr\XdrInvokeHostFunctionOp;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCAddressType;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrSorobanAddressCredentials;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedFunction;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedFunctionType;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedInvocation;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentials;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType;
use Soneso\StellarSDK\Xdr\XdrSorobanResources;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionDataExt;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizationEntry;

/**
 * Unit tests for Soroban-specific XDR classes using encode/decode round-trip testing.
 * Tests cover core Soroban transaction and authorization structures.
 */
class XdrSorobanTest extends TestCase
{
    private const TEST_CONTRACT_ID = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';
    private const TEST_ACCOUNT_ED25519 = 'fedcba9876543210fedcba9876543210fedcba9876543210fedcba9876543210';
    private const TEST_WASM_ID = 'abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789';
    private const TEST_SALT = 'ef00000000000000000000000000000000000000000000000000000000000000';

    /**
     * Test XdrSorobanCredentials with address credentials.
     */
    public function testSorobanCredentialsAddressRoundTrip(): void
    {
        $address = $this->createTestSCAddress();
        $nonce = 12345;
        $signatureExpirationLedger = 1000000;
        $signature = $this->createTestSCValBool(true);

        $addressCredentials = new XdrSorobanAddressCredentials(
            $address,
            $nonce,
            $signatureExpirationLedger,
            $signature
        );

        $credentials = XdrSorobanCredentials::forAddressCredentials($addressCredentials);
        $encoded = $credentials->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrSorobanCredentials::decode($xdrBuffer);

        $this->assertEquals(
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS,
            $decoded->getType()->getValue()
        );
        $this->assertNotNull($decoded->getAddress());
        $this->assertEquals($nonce, $decoded->getAddress()->getNonce());
        $this->assertEquals($signatureExpirationLedger, $decoded->getAddress()->getSignatureExpirationLedger());
    }

    /**
     * Test XdrSorobanResources with zero values.
     */
    public function testSorobanResourcesZeroValues(): void
    {
        $footprint = $this->createTestLedgerFootprint();
        $resources = new XdrSorobanResources($footprint, 0, 0, 0);
        $encoded = $resources->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrSorobanResources::decode($xdrBuffer);

        $this->assertEquals(0, $decoded->getInstructions());
        $this->assertEquals(0, $decoded->getDiskReadBytes());
        $this->assertEquals(0, $decoded->getWriteBytes());
    }

    /**
     * Test XdrSorobanTransactionData base64 conversion.
     */
    public function testSorobanTransactionDataBase64Conversion(): void
    {
        $ext = new XdrSorobanTransactionDataExt(0);
        $footprint = $this->createTestLedgerFootprint();
        $resources = new XdrSorobanResources($footprint, 1000000, 20000, 10000);
        $resourceFee = 10000;

        $sorobanData = new XdrSorobanTransactionData($ext, $resources, $resourceFee);
        $base64 = $sorobanData->toBase64Xdr();
        $this->assertNotEmpty($base64);

        $decoded = XdrSorobanTransactionData::fromBase64Xdr($base64);
        $this->assertEquals($resourceFee, $decoded->getResourceFee());
        $this->assertEquals(1000000, $decoded->getResources()->getInstructions());
    }

    /**
     * Test XdrSorobanAuthorizedFunctionType values.
     */
    public function testSorobanAuthorizedFunctionTypeValues(): void
    {
        $this->assertEquals(0, XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CONTRACT_FN);
        $this->assertEquals(1, XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_HOST_FN);
        $this->assertEquals(2, XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_V2_HOST_FN);
    }

    /**
     * Test XdrSorobanAuthorizedFunction with create contract.
     */
    public function testSorobanAuthorizedFunctionCreateContractRoundTrip(): void
    {
        $createArgs = $this->createTestCreateContractArgs();
        $authorizedFunction = XdrSorobanAuthorizedFunction::forCreateContractArgs($createArgs);

        $encoded = $authorizedFunction->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrSorobanAuthorizedFunction::decode($xdrBuffer);

        $this->assertEquals(
            XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_HOST_FN,
            $decoded->getType()->getValue()
        );
        $this->assertNotNull($decoded->getCreateContractHostFn());
    }

    /**
     * Test XdrSorobanAuthorizedInvocation with nested sub-invocations.
     */
    public function testSorobanAuthorizedInvocationWithSubInvocations(): void
    {
        $invokeArgs1 = $this->createTestInvokeContractArgs();
        $authorizedFunction1 = XdrSorobanAuthorizedFunction::forInvokeContractArgs($invokeArgs1);

        $invokeArgs2 = $this->createTestInvokeContractArgs();
        $authorizedFunction2 = XdrSorobanAuthorizedFunction::forInvokeContractArgs($invokeArgs2);

        $subInvocation = new XdrSorobanAuthorizedInvocation($authorizedFunction2, []);
        $subInvocations = [$subInvocation];

        $invocation = new XdrSorobanAuthorizedInvocation($authorizedFunction1, $subInvocations);
        $encoded = $invocation->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrSorobanAuthorizedInvocation::decode($xdrBuffer);

        $this->assertNotNull($decoded->getFunction());
        $this->assertCount(1, $decoded->getSubInvocations());
    }

    /**
     * Test XdrContractIDPreimageType values.
     */
    public function testContractIDPreimageTypeValues(): void
    {
        $this->assertEquals(0, XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS);
        $this->assertEquals(1, XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ASSET);
    }

    /**
     * Test XdrContractIDPreimage base64 conversion.
     */
    public function testContractIDPreimageBase64Conversion(): void
    {
        $address = $this->createTestSCAddress();
        $preimage = XdrContractIDPreimage::forAddress($address, self::TEST_SALT);

        $base64 = $preimage->toBase64Xdr();
        $this->assertNotEmpty($base64);

        $decoded = XdrContractIDPreimage::fromBase64Xdr($base64);
        $this->assertEquals(
            XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS,
            $decoded->getType()->getValue()
        );
    }

    /**
     * Test XdrContractExecutableType values.
     */
    public function testContractExecutableTypeValues(): void
    {
        $this->assertEquals(0, XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM);
        $this->assertEquals(1, XdrContractExecutableType::CONTRACT_EXECUTABLE_STELLAR_ASSET);
    }

    /**
     * Test XdrContractExecutable with WASM type round-trip.
     */
    public function testContractExecutableWasmRoundTrip(): void
    {
        $executable = XdrContractExecutable::forWasmId(self::TEST_WASM_ID);
        $encoded = $executable->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrContractExecutable::decode($xdrBuffer);

        $this->assertEquals(
            XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM,
            $decoded->getType()->getValue()
        );
        $this->assertNotNull($decoded->getWasmIdHex());
        $this->assertEquals(64, strlen($decoded->getWasmIdHex()));
    }

    /**
     * Test XdrContractExecutable with Stellar Asset type round-trip.
     */
    public function testContractExecutableStellarAssetRoundTrip(): void
    {
        $executable = XdrContractExecutable::forToken();
        $encoded = $executable->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrContractExecutable::decode($xdrBuffer);

        $this->assertEquals(
            XdrContractExecutableType::CONTRACT_EXECUTABLE_STELLAR_ASSET,
            $decoded->getType()->getValue()
        );
        $this->assertNull($decoded->getWasmIdHex());
    }

    /**
     * Test XdrHostFunctionType values.
     */
    public function testHostFunctionTypeValues(): void
    {
        $this->assertEquals(0, XdrHostFunctionType::HOST_FUNCTION_TYPE_INVOKE_CONTRACT);
        $this->assertEquals(1, XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT);
        $this->assertEquals(2, XdrHostFunctionType::HOST_FUNCTION_TYPE_UPLOAD_CONTRACT_WASM);
        $this->assertEquals(3, XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT_V2);
    }

    /**
     * Test XdrHostFunction with invoke contract type.
     */
    public function testHostFunctionInvokeContractRoundTrip(): void
    {
        $invokeArgs = $this->createTestInvokeContractArgs();
        $hostFunction = XdrHostFunction::forInvokingContractWithArgs($invokeArgs);

        $encoded = $hostFunction->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrHostFunction::decode($xdrBuffer);

        $this->assertEquals(
            XdrHostFunctionType::HOST_FUNCTION_TYPE_INVOKE_CONTRACT,
            $decoded->getType()->getValue()
        );
        $this->assertNotNull($decoded->getInvokeContract());
        $this->assertEquals('test_function', $decoded->getInvokeContract()->getFunctionName());
    }

    /**
     * Test XdrHostFunction with upload WASM type.
     */
    public function testHostFunctionUploadWasmRoundTrip(): void
    {
        $wasmBytes = str_repeat("\x00", 1024);
        $hostFunction = XdrHostFunction::forUploadContractWasm($wasmBytes);

        $encoded = $hostFunction->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrHostFunction::decode($xdrBuffer);

        $this->assertEquals(
            XdrHostFunctionType::HOST_FUNCTION_TYPE_UPLOAD_CONTRACT_WASM,
            $decoded->getType()->getValue()
        );
        $this->assertNotNull($decoded->getWasm());
    }

    /**
     * Test XdrHostFunction with create contract type.
     */
    public function testHostFunctionCreateContractRoundTrip(): void
    {
        $address = $this->createTestSCAddress();
        $salt = hex2bin(self::TEST_SALT);
        $hostFunction = XdrHostFunction::forCreatingContract($address, self::TEST_WASM_ID, $salt);

        $encoded = $hostFunction->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrHostFunction::decode($xdrBuffer);

        $this->assertEquals(
            XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT,
            $decoded->getType()->getValue()
        );
        $this->assertNotNull($decoded->getCreateContract());
    }

    /**
     * Test XdrHostFunction with deploy SAC from asset.
     */
    public function testHostFunctionDeploySACWithAssetRoundTrip(): void
    {
        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $hostFunction = XdrHostFunction::forDeploySACWithAsset($asset);

        $encoded = $hostFunction->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrHostFunction::decode($xdrBuffer);

        $this->assertEquals(
            XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT,
            $decoded->getType()->getValue()
        );
        $this->assertNotNull($decoded->getCreateContract());
    }

    /**
     * Test XdrInvokeContractArgs encode/decode round-trip.
     */
    public function testInvokeContractArgsRoundTrip(): void
    {
        $contractAddress = $this->createTestSCAddress();
        $functionName = 'transfer';
        $args = [
            $this->createTestSCValU32(100),
            $this->createTestSCValBool(true),
        ];

        $invokeArgs = new XdrInvokeContractArgs($contractAddress, $functionName, $args);
        $encoded = $invokeArgs->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrInvokeContractArgs::decode($xdrBuffer);

        $this->assertEquals($functionName, $decoded->getFunctionName());
        $this->assertCount(2, $decoded->getArgs());
        $this->assertNotNull($decoded->getContractAddress());
    }

    /**
     * Test XdrInvokeContractArgs with empty arguments.
     */
    public function testInvokeContractArgsEmptyArgsRoundTrip(): void
    {
        $contractAddress = $this->createTestSCAddress();
        $functionName = 'no_args_function';
        $args = [];

        $invokeArgs = new XdrInvokeContractArgs($contractAddress, $functionName, $args);
        $encoded = $invokeArgs->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrInvokeContractArgs::decode($xdrBuffer);

        $this->assertEquals($functionName, $decoded->getFunctionName());
        $this->assertCount(0, $decoded->getArgs());
    }

    /**
     * Test XdrInvokeHostFunctionOp encode/decode round-trip.
     */
    public function testInvokeHostFunctionOpRoundTrip(): void
    {
        $invokeArgs = $this->createTestInvokeContractArgs();
        $hostFunction = XdrHostFunction::forInvokingContractWithArgs($invokeArgs);
        $auth = [];

        $op = new XdrInvokeHostFunctionOp($hostFunction, $auth);
        $encoded = $op->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrInvokeHostFunctionOp::decode($xdrBuffer);

        $this->assertNotNull($decoded->getHostFunction());
        $this->assertIsArray($decoded->getAuth());
        $this->assertCount(0, $decoded->getAuth());
    }

    /**
     * Test XdrInvokeHostFunctionOp with multiple auth entries.
     */
    public function testInvokeHostFunctionOpWithAuthRoundTrip(): void
    {
        $invokeArgs = $this->createTestInvokeContractArgs();
        $hostFunction = XdrHostFunction::forInvokingContractWithArgs($invokeArgs);

        $credentials = XdrSorobanCredentials::forSourceAccount();
        $authorizedFunction = XdrSorobanAuthorizedFunction::forInvokeContractArgs($invokeArgs);
        $invocation = new XdrSorobanAuthorizedInvocation($authorizedFunction, []);

        $authEntry = new XdrSorobanAuthorizationEntry($credentials, $invocation);
        $auth = [$authEntry];

        $op = new XdrInvokeHostFunctionOp($hostFunction, $auth);
        $encoded = $op->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrInvokeHostFunctionOp::decode($xdrBuffer);

        $this->assertNotNull($decoded->getHostFunction());
        $this->assertCount(1, $decoded->getAuth());
    }

    /**
     * Test complex Soroban transaction with all components.
     */
    public function testComplexSorobanTransactionFlow(): void
    {
        $ext = new XdrSorobanTransactionDataExt(0);
        $footprint = $this->createTestLedgerFootprint();
        $resources = new XdrSorobanResources($footprint, 10000000, 200000, 100000);
        $resourceFee = 100000;
        $sorobanData = new XdrSorobanTransactionData($ext, $resources, $resourceFee);

        $invokeArgs = $this->createTestInvokeContractArgs();
        $hostFunction = XdrHostFunction::forInvokingContractWithArgs($invokeArgs);

        $credentials = XdrSorobanCredentials::forSourceAccount();
        $authorizedFunction = XdrSorobanAuthorizedFunction::forInvokeContractArgs($invokeArgs);
        $invocation = new XdrSorobanAuthorizedInvocation($authorizedFunction, []);
        $authEntry = new XdrSorobanAuthorizationEntry($credentials, $invocation);

        $op = new XdrInvokeHostFunctionOp($hostFunction, [$authEntry]);

        $encoded = $sorobanData->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrSorobanTransactionData::decode($xdrBuffer);
        $this->assertEquals($resourceFee, $decoded->getResourceFee());

        $encoded = $op->encode();
        $xdrBuffer = new XdrBuffer($encoded);
        $decoded = XdrInvokeHostFunctionOp::decode($xdrBuffer);
        $this->assertNotNull($decoded->getHostFunction());
    }

    /**
     * Helper: Create a test SCAddress.
     */
    private function createTestSCAddress(): XdrSCAddress
    {
        return XdrSCAddress::forContractId(self::TEST_CONTRACT_ID);
    }

    /**
     * Helper: Create a test SCVal with U32 type.
     */
    private function createTestSCValU32(int $value): XdrSCVal
    {
        $scVal = new XdrSCVal(new XdrSCValType(XdrSCValType::SCV_U32));
        $scVal->u32 = $value;
        return $scVal;
    }

    /**
     * Helper: Create a test SCVal with Bool type.
     */
    private function createTestSCValBool(bool $value): XdrSCVal
    {
        $scVal = new XdrSCVal(new XdrSCValType(XdrSCValType::SCV_BOOL));
        $scVal->b = $value;
        return $scVal;
    }

    /**
     * Helper: Create a test LedgerFootprint.
     */
    private function createTestLedgerFootprint(): XdrLedgerFootprint
    {
        $readOnly = [];
        $readWrite = [];
        return new XdrLedgerFootprint($readOnly, $readWrite);
    }

    /**
     * Helper: Create test InvokeContractArgs.
     */
    private function createTestInvokeContractArgs(): XdrInvokeContractArgs
    {
        $contractAddress = $this->createTestSCAddress();
        $functionName = 'test_function';
        $args = [$this->createTestSCValU32(42)];
        return new XdrInvokeContractArgs($contractAddress, $functionName, $args);
    }

    /**
     * Helper: Create test CreateContractArgs.
     */
    private function createTestCreateContractArgs(): XdrCreateContractArgs
    {
        $address = $this->createTestSCAddress();
        $preimage = XdrContractIDPreimage::forAddress($address, self::TEST_SALT);
        $executable = XdrContractExecutable::forWasmId(self::TEST_WASM_ID);
        return new XdrCreateContractArgs($preimage, $executable);
    }
}
