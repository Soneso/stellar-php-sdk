<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\SEP\Recovery;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\SEP\Recovery\RecoveryService;
use Soneso\StellarSDK\SEP\Recovery\SEP30AccountResponse;
use Soneso\StellarSDK\SEP\Recovery\SEP30AccountsResponse;
use Soneso\StellarSDK\SEP\Recovery\SEP30AuthMethod;
use Soneso\StellarSDK\SEP\Recovery\SEP30BadRequestResponseException;
use Soneso\StellarSDK\SEP\Recovery\SEP30ConflictResponseException;
use Soneso\StellarSDK\SEP\Recovery\SEP30NotFoundResponseException;
use Soneso\StellarSDK\SEP\Recovery\SEP30Request;
use Soneso\StellarSDK\SEP\Recovery\SEP30RequestIdentity;
use Soneso\StellarSDK\SEP\Recovery\SEP30ResponseIdentity;
use Soneso\StellarSDK\SEP\Recovery\SEP30ResponseSigner;
use Soneso\StellarSDK\SEP\Recovery\SEP30SignatureResponse;
use Soneso\StellarSDK\SEP\Recovery\SEP30UnauthorizedResponseException;
use Soneso\StellarSDK\SEP\Recovery\SEP30UnknownResponseException;

/**
 * Unit tests for SEP-30 Recovery classes
 */
class RecoveryTest extends TestCase
{
    private const TEST_ACCOUNT = 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H';
    private const TEST_SIGNER = 'GBQECQVAS2FJ7DLCUXDASZAJQLWPXNTCR2FXSCTV2ATHWKIE6T7MKBU4';
    private const TEST_JWT = 'eyJhbGciOiJFZERTQSIsInR5cCI6IkpXVCJ9.test';
    private const TEST_SERVICE_URL = 'https://recovery.example.com';

    // ==================== SEP30AuthMethod Tests ====================

    public function testAuthMethodConstructor(): void
    {
        $authMethod = new SEP30AuthMethod('stellar_address', self::TEST_ACCOUNT);

        $this->assertEquals('stellar_address', $authMethod->type);
        $this->assertEquals(self::TEST_ACCOUNT, $authMethod->value);
    }

    public function testAuthMethodGettersSetters(): void
    {
        $authMethod = new SEP30AuthMethod('phone_number', '+10000000001');

        $this->assertEquals('phone_number', $authMethod->getType());
        $this->assertEquals('+10000000001', $authMethod->getValue());

        $authMethod->setType('email');
        $authMethod->setValue('user@example.com');

        $this->assertEquals('email', $authMethod->getType());
        $this->assertEquals('user@example.com', $authMethod->getValue());
    }

    public function testAuthMethodToJson(): void
    {
        $authMethod = new SEP30AuthMethod('stellar_address', self::TEST_ACCOUNT);
        $json = $authMethod->toJson();

        $this->assertArrayHasKey('type', $json);
        $this->assertArrayHasKey('value', $json);
        $this->assertEquals('stellar_address', $json['type']);
        $this->assertEquals(self::TEST_ACCOUNT, $json['value']);
    }

    // ==================== SEP30RequestIdentity Tests ====================

    public function testRequestIdentityConstructor(): void
    {
        $authMethod = new SEP30AuthMethod('email', 'user@example.com');
        $identity = new SEP30RequestIdentity('owner', [$authMethod]);

        $this->assertEquals('owner', $identity->role);
        $this->assertCount(1, $identity->authMethods);
    }

    public function testRequestIdentityGettersSetters(): void
    {
        $authMethod1 = new SEP30AuthMethod('phone_number', '+10000000001');
        $identity = new SEP30RequestIdentity('owner', [$authMethod1]);

        $this->assertEquals('owner', $identity->getRole());
        $this->assertCount(1, $identity->getAuthMethods());

        $authMethod2 = new SEP30AuthMethod('email', 'user@example.com');
        $identity->setRole('sender');
        $identity->setAuthMethods([$authMethod1, $authMethod2]);

        $this->assertEquals('sender', $identity->getRole());
        $this->assertCount(2, $identity->getAuthMethods());
    }

    public function testRequestIdentityToJson(): void
    {
        $authMethod1 = new SEP30AuthMethod('phone_number', '+10000000001');
        $authMethod2 = new SEP30AuthMethod('email', 'user@example.com');
        $identity = new SEP30RequestIdentity('owner', [$authMethod1, $authMethod2]);

        $json = $identity->toJson();

        $this->assertArrayHasKey('role', $json);
        $this->assertArrayHasKey('auth_methods', $json);
        $this->assertEquals('owner', $json['role']);
        $this->assertCount(2, $json['auth_methods']);
        $this->assertEquals('phone_number', $json['auth_methods'][0]['type']);
        $this->assertEquals('email', $json['auth_methods'][1]['type']);
    }

    // ==================== SEP30Request Tests ====================

    public function testRequestConstructor(): void
    {
        $authMethod = new SEP30AuthMethod('email', 'user@example.com');
        $identity = new SEP30RequestIdentity('owner', [$authMethod]);
        $request = new SEP30Request([$identity]);

        $this->assertCount(1, $request->identities);
    }

    public function testRequestGettersSetters(): void
    {
        $authMethod = new SEP30AuthMethod('email', 'user@example.com');
        $identity1 = new SEP30RequestIdentity('owner', [$authMethod]);
        $request = new SEP30Request([$identity1]);

        $this->assertCount(1, $request->getIdentities());

        $identity2 = new SEP30RequestIdentity('sender', [$authMethod]);
        $request->setIdentities([$identity1, $identity2]);

        $this->assertCount(2, $request->getIdentities());
    }

    public function testRequestToJson(): void
    {
        $authMethod = new SEP30AuthMethod('stellar_address', self::TEST_ACCOUNT);
        $identity = new SEP30RequestIdentity('owner', [$authMethod]);
        $request = new SEP30Request([$identity]);

        $json = $request->toJson();

        $this->assertArrayHasKey('identities', $json);
        $this->assertCount(1, $json['identities']);
        $this->assertEquals('owner', $json['identities'][0]['role']);
        $this->assertEquals('stellar_address', $json['identities'][0]['auth_methods'][0]['type']);
    }

    // ==================== SEP30ResponseSigner Tests ====================

    public function testResponseSignerConstructor(): void
    {
        $signer = new SEP30ResponseSigner(self::TEST_SIGNER);

        $this->assertEquals(self::TEST_SIGNER, $signer->key);
    }

    public function testResponseSignerFromJson(): void
    {
        $json = ['key' => self::TEST_SIGNER];
        $signer = SEP30ResponseSigner::fromJson($json);

        $this->assertEquals(self::TEST_SIGNER, $signer->getKey());
    }

    public function testResponseSignerGettersSetters(): void
    {
        $signer = new SEP30ResponseSigner(self::TEST_SIGNER);

        $this->assertEquals(self::TEST_SIGNER, $signer->getKey());

        $signer->setKey(self::TEST_ACCOUNT);
        $this->assertEquals(self::TEST_ACCOUNT, $signer->getKey());
    }

    // ==================== SEP30ResponseIdentity Tests ====================

    public function testResponseIdentityConstructor(): void
    {
        $identity = new SEP30ResponseIdentity('owner', true);

        $this->assertEquals('owner', $identity->role);
        $this->assertTrue($identity->authenticated);
    }

    public function testResponseIdentityFromJson(): void
    {
        $json = ['role' => 'owner', 'authenticated' => true];
        $identity = SEP30ResponseIdentity::fromJson($json);

        $this->assertEquals('owner', $identity->getRole());
        $this->assertTrue($identity->getAuthenticated());
    }

    public function testResponseIdentityFromJsonWithoutAuthenticated(): void
    {
        $json = ['role' => 'sender'];
        $identity = SEP30ResponseIdentity::fromJson($json);

        $this->assertEquals('sender', $identity->getRole());
        $this->assertNull($identity->getAuthenticated());
    }

    public function testResponseIdentityGettersSetters(): void
    {
        $identity = new SEP30ResponseIdentity('owner');

        $this->assertEquals('owner', $identity->getRole());
        $this->assertNull($identity->getAuthenticated());

        $identity->setRole('receiver');
        $identity->setAuthenticated(false);

        $this->assertEquals('receiver', $identity->getRole());
        $this->assertFalse($identity->getAuthenticated());
    }

    // ==================== SEP30SignatureResponse Tests ====================

    public function testSignatureResponseConstructor(): void
    {
        $signature = new SEP30SignatureResponse('base64sig==', 'Test SDF Network ; September 2015');

        $this->assertEquals('base64sig==', $signature->signature);
        $this->assertEquals('Test SDF Network ; September 2015', $signature->networkPassphrase);
    }

    public function testSignatureResponseFromJson(): void
    {
        $json = [
            'signature' => 'YWJjZGVm',
            'network_passphrase' => 'Test SDF Network ; September 2015'
        ];
        $signature = SEP30SignatureResponse::fromJson($json);

        $this->assertEquals('YWJjZGVm', $signature->getSignature());
        $this->assertEquals('Test SDF Network ; September 2015', $signature->getNetworkPassphrase());
    }

    public function testSignatureResponseGettersSetters(): void
    {
        $signature = new SEP30SignatureResponse('sig1', 'network1');

        $this->assertEquals('sig1', $signature->getSignature());
        $this->assertEquals('network1', $signature->getNetworkPassphrase());

        $signature->setSignature('sig2');
        $signature->setNetworkPassphrase('network2');

        $this->assertEquals('sig2', $signature->getSignature());
        $this->assertEquals('network2', $signature->getNetworkPassphrase());
    }

    // ==================== SEP30AccountResponse Tests ====================

    public function testAccountResponseConstructor(): void
    {
        $identity = new SEP30ResponseIdentity('owner', true);
        $signer = new SEP30ResponseSigner(self::TEST_SIGNER);
        $response = new SEP30AccountResponse(self::TEST_ACCOUNT, [$identity], [$signer]);

        $this->assertEquals(self::TEST_ACCOUNT, $response->address);
        $this->assertCount(1, $response->identities);
        $this->assertCount(1, $response->signers);
    }

    public function testAccountResponseFromJson(): void
    {
        $json = [
            'address' => self::TEST_ACCOUNT,
            'identities' => [
                ['role' => 'owner', 'authenticated' => true],
                ['role' => 'sender', 'authenticated' => false]
            ],
            'signers' => [
                ['key' => self::TEST_SIGNER],
                ['key' => self::TEST_ACCOUNT]
            ]
        ];

        $response = SEP30AccountResponse::fromJson($json);

        $this->assertEquals(self::TEST_ACCOUNT, $response->getAddress());
        $this->assertCount(2, $response->getIdentities());
        $this->assertCount(2, $response->getSigners());

        $this->assertEquals('owner', $response->getIdentities()[0]->getRole());
        $this->assertTrue($response->getIdentities()[0]->getAuthenticated());
        $this->assertEquals('sender', $response->getIdentities()[1]->getRole());

        $this->assertEquals(self::TEST_SIGNER, $response->getSigners()[0]->getKey());
    }

    public function testAccountResponseGettersSetters(): void
    {
        $identity1 = new SEP30ResponseIdentity('owner');
        $signer1 = new SEP30ResponseSigner(self::TEST_SIGNER);
        $response = new SEP30AccountResponse(self::TEST_ACCOUNT, [$identity1], [$signer1]);

        $this->assertEquals(self::TEST_ACCOUNT, $response->getAddress());

        $identity2 = new SEP30ResponseIdentity('sender');
        $signer2 = new SEP30ResponseSigner(self::TEST_ACCOUNT);

        $response->setAddress(self::TEST_SIGNER);
        $response->setIdentities([$identity1, $identity2]);
        $response->setSigners([$signer1, $signer2]);

        $this->assertEquals(self::TEST_SIGNER, $response->getAddress());
        $this->assertCount(2, $response->getIdentities());
        $this->assertCount(2, $response->getSigners());
    }

    // ==================== SEP30AccountsResponse Tests ====================

    public function testAccountsResponseConstructor(): void
    {
        $identity = new SEP30ResponseIdentity('owner');
        $signer = new SEP30ResponseSigner(self::TEST_SIGNER);
        $account = new SEP30AccountResponse(self::TEST_ACCOUNT, [$identity], [$signer]);
        $response = new SEP30AccountsResponse([$account]);

        $this->assertCount(1, $response->accounts);
    }

    public function testAccountsResponseFromJson(): void
    {
        $json = [
            'accounts' => [
                [
                    'address' => self::TEST_ACCOUNT,
                    'identities' => [['role' => 'owner']],
                    'signers' => [['key' => self::TEST_SIGNER]]
                ],
                [
                    'address' => self::TEST_SIGNER,
                    'identities' => [['role' => 'sender']],
                    'signers' => [['key' => self::TEST_ACCOUNT]]
                ]
            ]
        ];

        $response = SEP30AccountsResponse::fromJson($json);

        $this->assertCount(2, $response->getAccounts());
        $this->assertEquals(self::TEST_ACCOUNT, $response->getAccounts()[0]->getAddress());
        $this->assertEquals(self::TEST_SIGNER, $response->getAccounts()[1]->getAddress());
    }

    public function testAccountsResponseGettersSetters(): void
    {
        $account1 = new SEP30AccountResponse(self::TEST_ACCOUNT, [], []);
        $response = new SEP30AccountsResponse([$account1]);

        $this->assertCount(1, $response->getAccounts());

        $account2 = new SEP30AccountResponse(self::TEST_SIGNER, [], []);
        $response->setAccounts([$account1, $account2]);

        $this->assertCount(2, $response->getAccounts());
    }

    // ==================== RecoveryService Tests ====================

    private function createMockedClient(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        return new Client(['handler' => $handlerStack]);
    }

    private function getSampleAccountResponseJson(): string
    {
        return json_encode([
            'address' => self::TEST_ACCOUNT,
            'identities' => [
                ['role' => 'owner', 'authenticated' => true]
            ],
            'signers' => [
                ['key' => self::TEST_SIGNER]
            ]
        ]);
    }

    private function getSampleAccountsResponseJson(): string
    {
        return json_encode([
            'accounts' => [
                [
                    'address' => self::TEST_ACCOUNT,
                    'identities' => [['role' => 'owner']],
                    'signers' => [['key' => self::TEST_SIGNER]]
                ]
            ]
        ]);
    }

    private function getSampleSignatureResponseJson(): string
    {
        return json_encode([
            'signature' => 'YWJjZGVmZ2hpamtsbW5vcA==',
            'network_passphrase' => 'Test SDF Network ; September 2015'
        ]);
    }

    public function testRecoveryServiceConstructor(): void
    {
        $service = new RecoveryService(self::TEST_SERVICE_URL);
        $this->assertInstanceOf(RecoveryService::class, $service);
    }

    public function testRecoveryServiceConstructorWithTrailingSlash(): void
    {
        $service = new RecoveryService(self::TEST_SERVICE_URL . '/');
        $this->assertInstanceOf(RecoveryService::class, $service);
    }

    public function testRecoveryServiceConstructorWithCustomClient(): void
    {
        $client = $this->createMockedClient([]);
        $service = new RecoveryService(self::TEST_SERVICE_URL, $client);
        $this->assertInstanceOf(RecoveryService::class, $service);
    }

    public function testRegisterAccount(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleAccountResponseJson())
        ]);
        $service = new RecoveryService(self::TEST_SERVICE_URL, $client);

        $authMethod = new SEP30AuthMethod('email', 'user@example.com');
        $identity = new SEP30RequestIdentity('owner', [$authMethod]);
        $request = new SEP30Request([$identity]);

        $response = $service->registerAccount(self::TEST_ACCOUNT, $request, self::TEST_JWT);

        $this->assertInstanceOf(SEP30AccountResponse::class, $response);
        $this->assertEquals(self::TEST_ACCOUNT, $response->getAddress());
    }

    public function testRegisterAccountBadRequest(): void
    {
        $this->expectException(SEP30BadRequestResponseException::class);

        $client = $this->createMockedClient([
            new Response(400, [], json_encode(['error' => 'Invalid request']))
        ]);
        $service = new RecoveryService(self::TEST_SERVICE_URL, $client);

        $request = new SEP30Request([]);
        $service->registerAccount(self::TEST_ACCOUNT, $request, self::TEST_JWT);
    }

    public function testRegisterAccountUnauthorized(): void
    {
        $this->expectException(SEP30UnauthorizedResponseException::class);

        $client = $this->createMockedClient([
            new Response(401, [], json_encode(['error' => 'Unauthorized']))
        ]);
        $service = new RecoveryService(self::TEST_SERVICE_URL, $client);

        $request = new SEP30Request([]);
        $service->registerAccount(self::TEST_ACCOUNT, $request, self::TEST_JWT);
    }

    public function testRegisterAccountNotFound(): void
    {
        $this->expectException(SEP30NotFoundResponseException::class);

        $client = $this->createMockedClient([
            new Response(404, [], json_encode(['error' => 'Not found']))
        ]);
        $service = new RecoveryService(self::TEST_SERVICE_URL, $client);

        $request = new SEP30Request([]);
        $service->registerAccount(self::TEST_ACCOUNT, $request, self::TEST_JWT);
    }

    public function testRegisterAccountConflict(): void
    {
        $this->expectException(SEP30ConflictResponseException::class);

        $client = $this->createMockedClient([
            new Response(409, [], json_encode(['error' => 'Already registered']))
        ]);
        $service = new RecoveryService(self::TEST_SERVICE_URL, $client);

        $request = new SEP30Request([]);
        $service->registerAccount(self::TEST_ACCOUNT, $request, self::TEST_JWT);
    }

    public function testRegisterAccountUnknownError(): void
    {
        $this->expectException(SEP30UnknownResponseException::class);

        $client = $this->createMockedClient([
            new Response(500, [], json_encode(['error' => 'Server error']))
        ]);
        $service = new RecoveryService(self::TEST_SERVICE_URL, $client);

        $request = new SEP30Request([]);
        $service->registerAccount(self::TEST_ACCOUNT, $request, self::TEST_JWT);
    }

    public function testUpdateIdentitiesForAccount(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleAccountResponseJson())
        ]);
        $service = new RecoveryService(self::TEST_SERVICE_URL, $client);

        $authMethod = new SEP30AuthMethod('phone_number', '+10000000001');
        $identity = new SEP30RequestIdentity('owner', [$authMethod]);
        $request = new SEP30Request([$identity]);

        $response = $service->updateIdentitiesForAccount(self::TEST_ACCOUNT, $request, self::TEST_JWT);

        $this->assertInstanceOf(SEP30AccountResponse::class, $response);
        $this->assertEquals(self::TEST_ACCOUNT, $response->getAddress());
    }

    public function testUpdateIdentitiesForAccountErrors(): void
    {
        // Test 400 error
        $client = $this->createMockedClient([new Response(400, [], '{"error":"Bad request"}')]);
        $service = new RecoveryService(self::TEST_SERVICE_URL, $client);
        $request = new SEP30Request([]);

        try {
            $service->updateIdentitiesForAccount(self::TEST_ACCOUNT, $request, self::TEST_JWT);
            $this->fail('Expected exception');
        } catch (SEP30BadRequestResponseException $e) {
            $this->assertStringContainsString('Bad request', $e->getMessage());
        }
    }

    public function testSignTransaction(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleSignatureResponseJson())
        ]);
        $service = new RecoveryService(self::TEST_SERVICE_URL, $client);

        $response = $service->signTransaction(
            self::TEST_ACCOUNT,
            self::TEST_SIGNER,
            'AAAA...base64txn...',
            self::TEST_JWT
        );

        $this->assertInstanceOf(SEP30SignatureResponse::class, $response);
        $this->assertEquals('YWJjZGVmZ2hpamtsbW5vcA==', $response->getSignature());
        $this->assertEquals('Test SDF Network ; September 2015', $response->getNetworkPassphrase());
    }

    public function testSignTransactionErrors(): void
    {
        // Test 401 error
        $client = $this->createMockedClient([new Response(401, [], '{"error":"Unauthorized"}')]);
        $service = new RecoveryService(self::TEST_SERVICE_URL, $client);

        try {
            $service->signTransaction(self::TEST_ACCOUNT, self::TEST_SIGNER, 'tx', self::TEST_JWT);
            $this->fail('Expected exception');
        } catch (SEP30UnauthorizedResponseException $e) {
            $this->assertStringContainsString('Unauthorized', $e->getMessage());
        }
    }

    public function testAccountDetails(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleAccountResponseJson())
        ]);
        $service = new RecoveryService(self::TEST_SERVICE_URL, $client);

        $response = $service->accountDetails(self::TEST_ACCOUNT, self::TEST_JWT);

        $this->assertInstanceOf(SEP30AccountResponse::class, $response);
        $this->assertEquals(self::TEST_ACCOUNT, $response->getAddress());
        $this->assertCount(1, $response->getIdentities());
        $this->assertTrue($response->getIdentities()[0]->getAuthenticated());
    }

    public function testAccountDetailsNotFound(): void
    {
        $this->expectException(SEP30NotFoundResponseException::class);

        $client = $this->createMockedClient([
            new Response(404, [], 'Account not found')
        ]);
        $service = new RecoveryService(self::TEST_SERVICE_URL, $client);

        $service->accountDetails(self::TEST_ACCOUNT, self::TEST_JWT);
    }

    public function testDeleteAccount(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleAccountResponseJson())
        ]);
        $service = new RecoveryService(self::TEST_SERVICE_URL, $client);

        $response = $service->deleteAccount(self::TEST_ACCOUNT, self::TEST_JWT);

        $this->assertInstanceOf(SEP30AccountResponse::class, $response);
        $this->assertEquals(self::TEST_ACCOUNT, $response->getAddress());
    }

    public function testDeleteAccountConflict(): void
    {
        $this->expectException(SEP30ConflictResponseException::class);

        $client = $this->createMockedClient([
            new Response(409, [], '{"error":"Conflict"}')
        ]);
        $service = new RecoveryService(self::TEST_SERVICE_URL, $client);

        $service->deleteAccount(self::TEST_ACCOUNT, self::TEST_JWT);
    }

    public function testAccounts(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleAccountsResponseJson())
        ]);
        $service = new RecoveryService(self::TEST_SERVICE_URL, $client);

        $response = $service->accounts(self::TEST_JWT);

        $this->assertInstanceOf(SEP30AccountsResponse::class, $response);
        $this->assertCount(1, $response->getAccounts());
        $this->assertEquals(self::TEST_ACCOUNT, $response->getAccounts()[0]->getAddress());
    }

    public function testAccountsWithPagination(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleAccountsResponseJson())
        ]);
        $service = new RecoveryService(self::TEST_SERVICE_URL, $client);

        $response = $service->accounts(self::TEST_JWT, self::TEST_ACCOUNT);

        $this->assertInstanceOf(SEP30AccountsResponse::class, $response);
    }

    public function testAccountsUnknownError(): void
    {
        $this->expectException(SEP30UnknownResponseException::class);

        $client = $this->createMockedClient([
            new Response(503, [], 'Service unavailable')
        ]);
        $service = new RecoveryService(self::TEST_SERVICE_URL, $client);

        $service->accounts(self::TEST_JWT);
    }

    public function testErrorResponseWithoutJsonError(): void
    {
        // Test error response without 'error' key in JSON
        $client = $this->createMockedClient([
            new Response(400, [], 'Plain text error')
        ]);
        $service = new RecoveryService(self::TEST_SERVICE_URL, $client);

        try {
            $service->accountDetails(self::TEST_ACCOUNT, self::TEST_JWT);
            $this->fail('Expected exception');
        } catch (SEP30BadRequestResponseException $e) {
            $this->assertEquals('Plain text error', $e->getMessage());
        }
    }

    public function testServiceUrlWithoutTrailingSlash(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleAccountResponseJson())
        ]);
        $service = new RecoveryService('https://recovery.example.com', $client);

        $response = $service->accountDetails(self::TEST_ACCOUNT, self::TEST_JWT);

        $this->assertInstanceOf(SEP30AccountResponse::class, $response);
    }

    public function testServiceUrlWithTrailingSlash(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleAccountResponseJson())
        ]);
        $service = new RecoveryService('https://recovery.example.com/', $client);

        $response = $service->accountDetails(self::TEST_ACCOUNT, self::TEST_JWT);

        $this->assertInstanceOf(SEP30AccountResponse::class, $response);
    }
}
