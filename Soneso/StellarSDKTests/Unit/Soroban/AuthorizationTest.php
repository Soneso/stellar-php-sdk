<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Soroban;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\AccountEd25519Signature;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Footprint;
use Soneso\StellarSDK\Soroban\SorobanAddressCredentials;
use Soneso\StellarSDK\Soroban\SorobanAuthorizedFunction;
use Soneso\StellarSDK\Soroban\SorobanAuthorizedInvocation;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Soroban\SorobanCredentials;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrCreateContractArgs;
use Soneso\StellarSDK\Xdr\XdrCreateContractArgsV2;
use Soneso\StellarSDK\Xdr\XdrInvokeContractArgs;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizationEntry;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedFunction;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedFunctionType;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedInvocation;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentials;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType;

class AuthorizationTest extends TestCase
{
    private string $testAccountId;
    private string $testContractIdHex;
    private KeyPair $testKeyPair;
    private Network $testNetwork;

    public function setUp(): void
    {
        error_reporting(E_ALL);

        $this->testAccountId = 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H';
        $this->testContractIdHex = '3f0918bf77f7e30fe942e4bc2ce903ffa2d80e7f3e1f82ba58877f0eb73df0b7';
        $this->testKeyPair = KeyPair::random();
        $this->testNetwork = Network::testnet();
    }

    /**
     * Test SorobanAuthorizedFunction creation for contract function
     */
    public function testAuthorizedFunctionForContractFunction(): void
    {
        $address = Address::fromContractId($this->testContractIdHex);
        $functionName = 'transfer';
        $args = [XdrSCVal::forU32(100)];

        $authorizedFunc = SorobanAuthorizedFunction::forContractFunction($address, $functionName, $args);

        $this->assertNotNull($authorizedFunc->getContractFn());
        $this->assertNull($authorizedFunc->getCreateContractHostFn());
        $this->assertNull($authorizedFunc->getCreateContractV2HostFn());

        $contractFn = $authorizedFunc->getContractFn();
        $this->assertEquals($functionName, $contractFn->functionName);
        $this->assertCount(1, $contractFn->args);
    }

    /**
     * Test SorobanAuthorizedFunction XDR encoding and decoding for contract function
     */
    public function testAuthorizedFunctionContractFunctionXdrRoundtrip(): void
    {
        $address = Address::fromContractId($this->testContractIdHex);
        $functionName = 'mint';
        $args = [XdrSCVal::forU64(1000), XdrSCVal::forString('test')];

        $original = SorobanAuthorizedFunction::forContractFunction($address, $functionName, $args);
        $xdr = $original->toXdr();

        $this->assertInstanceOf(XdrSorobanAuthorizedFunction::class, $xdr);
        $this->assertEquals(XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CONTRACT_FN, $xdr->type->value);

        $decoded = SorobanAuthorizedFunction::fromXdr($xdr);

        $this->assertNotNull($decoded->getContractFn());
        $this->assertEquals($functionName, $decoded->getContractFn()->functionName);
        $this->assertCount(2, $decoded->getContractFn()->args);
    }

    /**
     * Test SorobanAuthorizedFunction constructor throws exception for null parameters
     */
    public function testAuthorizedFunctionConstructorThrowsExceptionForNullParams(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid arguments');

        new SorobanAuthorizedFunction();
    }

    /**
     * Test SorobanAuthorizedFunction setters and getters
     */
    public function testAuthorizedFunctionSettersAndGetters(): void
    {
        $address = Address::fromContractId($this->testContractIdHex);
        $contractFn = new XdrInvokeContractArgs($address->toXdr(), 'test_function', []);

        $authorizedFunc = new SorobanAuthorizedFunction($contractFn);

        $this->assertEquals($contractFn, $authorizedFunc->getContractFn());

        $newContractFn = new XdrInvokeContractArgs($address->toXdr(), 'another_function', []);
        $authorizedFunc->setContractFn($newContractFn);

        $this->assertEquals($newContractFn, $authorizedFunc->getContractFn());
    }

    /**
     * Test SorobanAuthorizedInvocation creation
     */
    public function testAuthorizedInvocation(): void
    {
        $address = Address::fromContractId($this->testContractIdHex);
        $function = SorobanAuthorizedFunction::forContractFunction($address, 'transfer', []);

        $invocation = new SorobanAuthorizedInvocation($function);

        $this->assertEquals($function, $invocation->getFunction());
        $this->assertIsArray($invocation->getSubInvocations());
        $this->assertCount(0, $invocation->getSubInvocations());
    }

    /**
     * Test SorobanAuthorizedInvocation with sub-invocations
     */
    public function testAuthorizedInvocationWithSubInvocations(): void
    {
        $address = Address::fromContractId($this->testContractIdHex);
        $mainFunction = SorobanAuthorizedFunction::forContractFunction($address, 'main', []);
        $subFunction1 = SorobanAuthorizedFunction::forContractFunction($address, 'sub1', []);
        $subFunction2 = SorobanAuthorizedFunction::forContractFunction($address, 'sub2', []);

        $subInvocation1 = new SorobanAuthorizedInvocation($subFunction1);
        $subInvocation2 = new SorobanAuthorizedInvocation($subFunction2);

        $mainInvocation = new SorobanAuthorizedInvocation($mainFunction, [$subInvocation1, $subInvocation2]);

        $this->assertCount(2, $mainInvocation->getSubInvocations());
        $this->assertEquals($subInvocation1, $mainInvocation->getSubInvocations()[0]);
        $this->assertEquals($subInvocation2, $mainInvocation->getSubInvocations()[1]);
    }

    /**
     * Test SorobanAuthorizedInvocation XDR encoding and decoding
     */
    public function testAuthorizedInvocationXdrRoundtrip(): void
    {
        $address = Address::fromContractId($this->testContractIdHex);
        $function = SorobanAuthorizedFunction::forContractFunction($address, 'transfer', [XdrSCVal::forU32(100)]);
        $subFunction = SorobanAuthorizedFunction::forContractFunction($address, 'check_balance', []);
        $subInvocation = new SorobanAuthorizedInvocation($subFunction);

        $original = new SorobanAuthorizedInvocation($function, [$subInvocation]);
        $xdr = $original->toXdr();

        $this->assertInstanceOf(XdrSorobanAuthorizedInvocation::class, $xdr);

        $decoded = SorobanAuthorizedInvocation::fromXdr($xdr);

        $this->assertNotNull($decoded->getFunction());
        $this->assertCount(1, $decoded->getSubInvocations());
    }

    /**
     * Test SorobanCredentials for source account
     */
    public function testCredentialsForSourceAccount(): void
    {
        $credentials = SorobanCredentials::forSourceAccount();

        $this->assertNull($credentials->getAddressCredentials());

        $xdr = $credentials->toXdr();
        $this->assertEquals(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT, $xdr->type->value);
    }

    /**
     * Test SorobanCredentials for address
     */
    public function testCredentialsForAddress(): void
    {
        $address = Address::fromAccountId($this->testAccountId);
        $nonce = 12345;
        $expirationLedger = 67890;
        $signature = XdrSCVal::forVec([]);

        $credentials = SorobanCredentials::forAddress($address, $nonce, $expirationLedger, $signature);

        $this->assertNotNull($credentials->getAddressCredentials());
        $addressCreds = $credentials->getAddressCredentials();
        $this->assertEquals($address->getAccountId(), $addressCreds->getAddress()->getAccountId());
        $this->assertEquals($nonce, $addressCreds->getNonce());
        $this->assertEquals($expirationLedger, $addressCreds->getSignatureExpirationLedger());
    }

    /**
     * Test SorobanCredentials XDR encoding and decoding for source account
     */
    public function testCredentialsSourceAccountXdrRoundtrip(): void
    {
        $original = SorobanCredentials::forSourceAccount();
        $xdr = $original->toXdr();

        $this->assertInstanceOf(XdrSorobanCredentials::class, $xdr);
        $this->assertEquals(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT, $xdr->type->value);

        $decoded = SorobanCredentials::fromXdr($xdr);

        $this->assertNull($decoded->getAddressCredentials());
    }

    /**
     * Test SorobanCredentials XDR encoding and decoding for address
     */
    public function testCredentialsAddressXdrRoundtrip(): void
    {
        $address = Address::fromAccountId($this->testAccountId);
        $nonce = 54321;
        $expirationLedger = 98765;
        $signature = XdrSCVal::forVec([]);

        $original = SorobanCredentials::forAddress($address, $nonce, $expirationLedger, $signature);
        $xdr = $original->toXdr();

        $this->assertInstanceOf(XdrSorobanCredentials::class, $xdr);
        $this->assertEquals(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS, $xdr->type->value);

        $decoded = SorobanCredentials::fromXdr($xdr);

        $this->assertNotNull($decoded->getAddressCredentials());
        $this->assertEquals($nonce, $decoded->getAddressCredentials()->getNonce());
        $this->assertEquals($expirationLedger, $decoded->getAddressCredentials()->getSignatureExpirationLedger());
    }

    /**
     * Test SorobanAddressCredentials creation and getters
     */
    public function testAddressCredentials(): void
    {
        $address = Address::fromAccountId($this->testAccountId);
        $nonce = 11111;
        $expirationLedger = 22222;
        $signature = XdrSCVal::forVec([]);

        $credentials = new SorobanAddressCredentials($address, $nonce, $expirationLedger, $signature);

        $this->assertEquals($address, $credentials->getAddress());
        $this->assertEquals($nonce, $credentials->getNonce());
        $this->assertEquals($expirationLedger, $credentials->getSignatureExpirationLedger());
        $this->assertEquals($signature, $credentials->getSignature());
    }

    /**
     * Test SorobanAddressCredentials setters
     */
    public function testAddressCredentialsSetters(): void
    {
        $address1 = Address::fromAccountId($this->testAccountId);
        $address2 = Address::fromContractId($this->testContractIdHex);
        $signature1 = XdrSCVal::forVec([]);
        $signature2 = XdrSCVal::forVec([XdrSCVal::forU32(123)]);

        $credentials = new SorobanAddressCredentials($address1, 100, 200, $signature1);

        $credentials->setAddress($address2);
        $credentials->setNonce(300);
        $credentials->setSignatureExpirationLedger(400);
        $credentials->setSignature($signature2);

        $this->assertEquals($address2, $credentials->getAddress());
        $this->assertEquals(300, $credentials->getNonce());
        $this->assertEquals(400, $credentials->getSignatureExpirationLedger());
        $this->assertEquals($signature2, $credentials->getSignature());
    }

    /**
     * Test SorobanAuthorizationEntry creation
     */
    public function testAuthorizationEntry(): void
    {
        $credentials = SorobanCredentials::forSourceAccount();
        $address = Address::fromContractId($this->testContractIdHex);
        $function = SorobanAuthorizedFunction::forContractFunction($address, 'transfer', []);
        $invocation = new SorobanAuthorizedInvocation($function);

        $entry = new SorobanAuthorizationEntry($credentials, $invocation);

        $this->assertEquals($credentials, $entry->getCredentials());
        $this->assertEquals($invocation, $entry->getRootInvocation());
    }

    /**
     * Test SorobanAuthorizationEntry XDR encoding and decoding
     */
    public function testAuthorizationEntryXdrRoundtrip(): void
    {
        $credentials = SorobanCredentials::forSourceAccount();
        $address = Address::fromContractId($this->testContractIdHex);
        $function = SorobanAuthorizedFunction::forContractFunction($address, 'mint', [XdrSCVal::forU64(500)]);
        $invocation = new SorobanAuthorizedInvocation($function);

        $original = new SorobanAuthorizationEntry($credentials, $invocation);
        $xdr = $original->toXdr();

        $this->assertInstanceOf(XdrSorobanAuthorizationEntry::class, $xdr);

        $decoded = SorobanAuthorizationEntry::fromXdr($xdr);

        $this->assertNotNull($decoded->getCredentials());
        $this->assertNotNull($decoded->getRootInvocation());
        $this->assertNull($decoded->getCredentials()->getAddressCredentials());
    }

    /**
     * Test SorobanAuthorizationEntry base64 XDR encoding and decoding
     */
    public function testAuthorizationEntryBase64XdrRoundtrip(): void
    {
        $credentials = SorobanCredentials::forSourceAccount();
        $address = Address::fromContractId($this->testContractIdHex);
        $function = SorobanAuthorizedFunction::forContractFunction($address, 'test', []);
        $invocation = new SorobanAuthorizedInvocation($function);

        $original = new SorobanAuthorizationEntry($credentials, $invocation);
        $base64Xdr = $original->toBase64Xdr();

        $this->assertIsString($base64Xdr);
        $this->assertNotEmpty($base64Xdr);

        $decoded = SorobanAuthorizationEntry::fromBase64Xdr($base64Xdr);

        $this->assertInstanceOf(SorobanAuthorizationEntry::class, $decoded);
        $this->assertNotNull($decoded->getCredentials());
        $this->assertNotNull($decoded->getRootInvocation());
    }

    /**
     * Test SorobanAuthorizationEntry signing
     */
    public function testAuthorizationEntrySigning(): void
    {
        $signer = $this->testKeyPair;
        $address = Address::fromAccountId($signer->getAccountId());
        $nonce = 999;
        $expirationLedger = 1000;
        $signature = XdrSCVal::forVec([]);

        $credentials = SorobanCredentials::forAddress($address, $nonce, $expirationLedger, $signature);
        $contractAddress = Address::fromContractId($this->testContractIdHex);
        $function = SorobanAuthorizedFunction::forContractFunction($contractAddress, 'transfer', []);
        $invocation = new SorobanAuthorizedInvocation($function);

        $entry = new SorobanAuthorizationEntry($credentials, $invocation);

        $entry->sign($signer, $this->testNetwork);

        $addressCreds = $entry->getCredentials()->getAddressCredentials();
        $this->assertNotNull($addressCreds);
        $this->assertNotNull($addressCreds->getSignature()->vec);
        $this->assertGreaterThan(0, count($addressCreds->getSignature()->vec));
    }

    /**
     * Test SorobanAuthorizationEntry signing throws exception without address credentials
     */
    public function testAuthorizationEntrySigningThrowsExceptionWithoutAddressCredentials(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no soroban address credentials found');

        $credentials = SorobanCredentials::forSourceAccount();
        $address = Address::fromContractId($this->testContractIdHex);
        $function = SorobanAuthorizedFunction::forContractFunction($address, 'test', []);
        $invocation = new SorobanAuthorizedInvocation($function);

        $entry = new SorobanAuthorizationEntry($credentials, $invocation);
        $entry->sign($this->testKeyPair, $this->testNetwork);
    }

    /**
     * Test AccountEd25519Signature creation
     */
    public function testAccountEd25519Signature(): void
    {
        $publicKey = $this->testKeyPair->getPublicKey();
        $signatureBytes = random_bytes(64);

        $signature = new AccountEd25519Signature($publicKey, $signatureBytes);

        $this->assertEquals($publicKey, $signature->getPublicKey());
        $this->assertEquals($signatureBytes, $signature->getSignatureBytes());
    }

    /**
     * Test AccountEd25519Signature to XdrSCVal
     */
    public function testAccountEd25519SignatureToXdrSCVal(): void
    {
        $publicKey = $this->testKeyPair->getPublicKey();
        $signatureBytes = random_bytes(64);

        $signature = new AccountEd25519Signature($publicKey, $signatureBytes);
        $scVal = $signature->toXdrSCVal();

        $this->assertInstanceOf(XdrSCVal::class, $scVal);
        $this->assertNotNull($scVal->map);
        $this->assertCount(2, $scVal->map);
    }

    /**
     * Test AccountEd25519Signature setters
     */
    public function testAccountEd25519SignatureSetters(): void
    {
        $publicKey1 = random_bytes(32);
        $publicKey2 = random_bytes(32);
        $signatureBytes1 = random_bytes(64);
        $signatureBytes2 = random_bytes(64);

        $signature = new AccountEd25519Signature($publicKey1, $signatureBytes1);

        $signature->setPublicKey($publicKey2);
        $signature->setSignatureBytes($signatureBytes2);

        $this->assertEquals($publicKey2, $signature->getPublicKey());
        $this->assertEquals($signatureBytes2, $signature->getSignatureBytes());
    }

    /**
     * Test Footprint creation from XDR
     */
    public function testFootprintFromXdr(): void
    {
        $xdrFootprint = new XdrLedgerFootprint([], []);
        $footprint = new Footprint($xdrFootprint);

        $this->assertInstanceOf(Footprint::class, $footprint);
        $this->assertEquals($xdrFootprint, $footprint->xdrFootprint);
    }

    /**
     * Test Footprint empty footprint
     */
    public function testEmptyFootprint(): void
    {
        $footprint = Footprint::emptyFootprint();

        $this->assertInstanceOf(Footprint::class, $footprint);
        $this->assertCount(0, $footprint->xdrFootprint->readOnly);
        $this->assertCount(0, $footprint->xdrFootprint->readWrite);
    }

    /**
     * Test Footprint base64 XDR encoding and decoding
     */
    public function testFootprintBase64XdrRoundtrip(): void
    {
        $original = Footprint::emptyFootprint();
        $base64Xdr = $original->toBase64Xdr();

        $this->assertIsString($base64Xdr);
        $this->assertNotEmpty($base64Xdr);

        $decoded = Footprint::fromBase64Xdr($base64Xdr);

        $this->assertInstanceOf(Footprint::class, $decoded);
        $this->assertCount(0, $decoded->xdrFootprint->readOnly);
        $this->assertCount(0, $decoded->xdrFootprint->readWrite);
    }

    /**
     * Test Footprint getting contract code ledger key
     */
    public function testFootprintGetContractCodeLedgerKey(): void
    {
        $footprint = Footprint::emptyFootprint();

        $contractCodeKey = $footprint->getContractCodeLedgerKey();
        $this->assertNull($contractCodeKey);

        $contractCodeXdrKey = $footprint->getContractCodeXdrLedgerKey();
        $this->assertNull($contractCodeXdrKey);
    }

    /**
     * Test Footprint getting contract data ledger key
     */
    public function testFootprintGetContractDataLedgerKey(): void
    {
        $footprint = Footprint::emptyFootprint();

        $contractDataKey = $footprint->getContractDataLedgerKey();
        $this->assertNull($contractDataKey);

        $contractDataXdrKey = $footprint->getContractDataXdrLedgerKey();
        $this->assertNull($contractDataXdrKey);
    }

    /**
     * Test SorobanAuthorizedInvocation setters
     */
    public function testAuthorizedInvocationSetters(): void
    {
        $address = Address::fromContractId($this->testContractIdHex);
        $function1 = SorobanAuthorizedFunction::forContractFunction($address, 'func1', []);
        $function2 = SorobanAuthorizedFunction::forContractFunction($address, 'func2', []);
        $subInvocation = new SorobanAuthorizedInvocation($function2);

        $invocation = new SorobanAuthorizedInvocation($function1);

        $invocation->setFunction($function2);
        $invocation->setSubInvocations([$subInvocation]);

        $this->assertEquals($function2, $invocation->getFunction());
        $this->assertCount(1, $invocation->getSubInvocations());
    }

    /**
     * Test SorobanAuthorizationEntry setters
     */
    public function testAuthorizationEntrySetters(): void
    {
        $credentials1 = SorobanCredentials::forSourceAccount();
        $address1 = Address::fromAccountId($this->testAccountId);
        $credentials2 = SorobanCredentials::forAddress($address1, 100, 200, XdrSCVal::forVec([]));

        $address = Address::fromContractId($this->testContractIdHex);
        $function1 = SorobanAuthorizedFunction::forContractFunction($address, 'func1', []);
        $function2 = SorobanAuthorizedFunction::forContractFunction($address, 'func2', []);
        $invocation1 = new SorobanAuthorizedInvocation($function1);
        $invocation2 = new SorobanAuthorizedInvocation($function2);

        $entry = new SorobanAuthorizationEntry($credentials1, $invocation1);

        $entry->setCredentials($credentials2);
        $entry->setRootInvocation($invocation2);

        $this->assertEquals($credentials2, $entry->getCredentials());
        $this->assertEquals($invocation2, $entry->getRootInvocation());
    }

    /**
     * Test SorobanCredentials setters
     */
    public function testCredentialsSetters(): void
    {
        $address = Address::fromAccountId($this->testAccountId);
        $addressCreds = new SorobanAddressCredentials($address, 100, 200, XdrSCVal::forVec([]));

        $credentials = SorobanCredentials::forSourceAccount();
        $credentials->setAddressCredentials($addressCreds);

        $this->assertEquals($addressCreds, $credentials->getAddressCredentials());
    }

    /**
     * Test SorobanCredentials forAddressCredentials factory method
     */
    public function testCredentialsForAddressCredentials(): void
    {
        $address = Address::fromAccountId($this->testAccountId);
        $addressCreds = new SorobanAddressCredentials($address, 555, 666, XdrSCVal::forVec([]));

        $credentials = SorobanCredentials::forAddressCredentials($addressCreds);

        $this->assertEquals($addressCreds, $credentials->getAddressCredentials());
    }
}
