<?php  declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Soneso\StellarSDK\SEP\Recovery\SEP30AuthMethod;
use Soneso\StellarSDK\SEP\Recovery\RecoveryService;
use Soneso\StellarSDK\SEP\Recovery\SEP30BadRequestResponseException;
use Soneso\StellarSDK\SEP\Recovery\SEP30ConflictResponseException;
use Soneso\StellarSDK\SEP\Recovery\SEP30NotFoundResponseException;
use Soneso\StellarSDK\SEP\Recovery\SEP30Request;
use Soneso\StellarSDK\SEP\Recovery\SEP30RequestIdentity;
use Soneso\StellarSDK\SEP\Recovery\SEP30UnauthorizedResponseException;


class SEP030Test extends TestCase
{

    private string $serviceAddress = "http://api.stellar.org/recovery";
    private string $addressA = "GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP";
    private string $signingAddress = "GDRUPBJM7YIJ2NUNAIQJDJ2DQ2JDERY5SJVJVMM6MGE4UBDAMXBHARIA";
    private string $jwtToken = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJHQTZVSVhYUEVXWUZJTE5VSVdBQzM3WTRRUEVaTVFWREpIREtWV0ZaSjJLQ1dVQklVNUlYWk5EQSIsImp0aSI6IjE0NGQzNjdiY2IwZTcyY2FiZmRiZGU2MGVhZTBhZDczM2NjNjVkMmE2NTg3MDgzZGFiM2Q2MTZmODg1MTkwMjQiLCJpc3MiOiJodHRwczovL2ZsYXBweS1iaXJkLWRhcHAuZmlyZWJhc2VhcHAuY29tLyIsImlhdCI6MTUzNDI1Nzk5NCwiZXhwIjoxNTM0MzQ0Mzk0fQ.8nbB83Z6vGBgC1X9r3N6oQCFTBzDiITAfCJasRft0z0";

    private SEP30AuthMethod $senderAddrAuth;
    private SEP30AuthMethod $senderPhoneAuth;
    private SEP30AuthMethod $senderEmailAuth;

    private SEP30AuthMethod $receiverAddrAuth;
    private SEP30AuthMethod $receiverPhoneAuth;
    private SEP30AuthMethod $receiverEmailAuth;

    private SEP30RequestIdentity $senderIdentity;
    private SEP30RequestIdentity $receiverIdentity;

    private string $registerSuccess = "{  \"address\": \"GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP\",  \"identities\": [    { \"role\": \"sender\" },    { \"role\": \"receiver\" }  ],  \"signers\": [    { \"key\": \"GDRUPBJM7YIJ2NUNAIQJDJ2DQ2JDERY5SJVJVMM6MGE4UBDAMXBHARIA\" }  ]}";
    private string $detailSuccess = "{  \"address\": \"GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP\",  \"identities\": [    { \"role\": \"sender\", \"authenticated\": true },    { \"role\": \"receiver\" }  ],  \"signers\": [    { \"key\": \"GDRUPBJM7YIJ2NUNAIQJDJ2DQ2JDERY5SJVJVMM6MGE4UBDAMXBHARIA\" }  ]}";
    private string $signSuccess = "{  \"signature\": \"YpVelqPYVKxb8pH08s5AKsYTPwQhbaeSlgcktqwAKsYTPwQhbaeS\",  \"network_passphrase\": \"Test SDF Network ; September 2015\"}";
    private string $listSuccess = "{  \"accounts\": [    {      \"address\": \"GBND3FJRQBNFJ4ACERGEXUXU4RKK3ZV2N3FRRFU3ONYU6SJUN6EZXPTD\",      \"identities\": [        {\"role\": \"owner\",  \"authenticated\": true }      ],      \"signers\": [        { \"key\": \"GBTPAH6NWK25GESZYJ3XWPTNQUIMYNK7VU7R4NSTMZXOEKCOBKJVJ2XY\" }      ]    },    {      \"address\": \"GA7BLNSL55T2UAON5DYLQHJTR43IPT2O4QG6PAMSNLJJL7JMXKZYYVFJ\",      \"identities\": [        { \"role\": \"sender\", \"authenticated\": true },        { \"role\": \"receiver\" }     ],      \"signers\": [        { \"key\": \"GAOCJE4737GYN2EGCGWPNNCDVDKX7XKC4UKOKIF7CRRYIFLPZLH3U3UN\" }      ]    },    {      \"address\": \"GD62WD2XTOCAENMB34FB2SEW6JHPB7AFYQAJ5OCQ3TYRW5MOJXLKGTMM\",      \"identities\": [        { \"role\": \"sender\" },        { \"role\": \"receiver\", \"authenticated\": true }     ],      \"signers\": [        { \"key\": \"GDFPM46I2L2DXB3TWAKPMLUMEW226WXLRWJNS4QHXXKJXEUW3M6OAFBY\" }      ]    }  ]}";

    private string $transaction = "AAAAAgAAAABswQhbaeSlgckYVKxb8pH08s5tqVVpGXYw1kCpbqv6lQAAAGQAIa4PAAAAAQAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEAAAAAAAAABQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABAAAACWhlbGxvLmNvbQAAAAAAAAAAAAAAAAAAAA==";
    private string $signature = "YpVelqPYVKxb8pH08s5AKsYTPwQhbaeSlgcktqwAKsYTPwQhbaeS";
    private string $networkPassphrase = "Test SDF Network ; September 2015";


    protected function setUp() : void {
        $this->senderAddrAuth = new SEP30AuthMethod("stellar_address", "GBUCAAMD7DYS7226CWUUOZ5Y2QF4JBJWIYU3UWJAFDGJVCR6EU5NJM5H");
        $this->senderPhoneAuth = new SEP30AuthMethod("phone_number", "+10000000001");
        $this->senderEmailAuth = new SEP30AuthMethod("email", "person1@example.com");

        $this->receiverAddrAuth = new SEP30AuthMethod("stellar_address", "GDIL76BC2XGDWLDPXCZVYB3AIZX4MYBN6JUBQPAX5OHRWPSNX3XMLNCS");
        $this->receiverPhoneAuth = new SEP30AuthMethod("phone_number", "+10000000002");
        $this->receiverEmailAuth = new SEP30AuthMethod("email", "person2@example.com");

        $this->senderIdentity = new SEP30RequestIdentity("sender", [$this->senderAddrAuth, $this->senderPhoneAuth, $this->senderEmailAuth]);
        $this->receiverIdentity = new SEP30RequestIdentity("receiver", [$this->receiverAddrAuth, $this->receiverPhoneAuth, $this->receiverEmailAuth]);
    }



    public function testRegisterAccountSuccess(): void {

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->registerSuccess)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("POST", $request->getMethod());
            $body = $request->getBody()->__toString();
            $jsonData = @json_decode($body, true);
            $this->assertCount(2, $jsonData['identities']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $service = new RecoveryService($this->serviceAddress, $httpClient);
        $request = new SEP30Request([$this->senderIdentity, $this->receiverIdentity]);
        $response = $service->registerAccount($this->addressA, $request, $this->jwtToken);

        $this->assertEquals($this->addressA, $response->address);
        $this->assertCount(2, $response->identities);
        $this->assertCount(1, $response->signers);
    }

    public function testUpdateAccountSuccess(): void {

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->registerSuccess)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("PUT", $request->getMethod());
            $body = $request->getBody()->__toString();
            $jsonData = @json_decode($body, true);
            $this->assertCount(2, $jsonData['identities']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $service = new RecoveryService($this->serviceAddress, $httpClient);
        $request = new SEP30Request([$this->senderIdentity, $this->receiverIdentity]);
        $response = $service->updateIdentitiesForAccount($this->addressA, $request, $this->jwtToken);

        $this->assertEquals($this->addressA, $response->address);
        $this->assertCount(2, $response->identities);
        $this->assertCount(1, $response->signers);
    }

    public function testSignSuccess(): void {

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->signSuccess)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("POST", $request->getMethod());
            $body = $request->getBody()->__toString();
            $jsonData = @json_decode($body, true);
            $this->assertEquals($this->transaction, $jsonData['transaction']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $service = new RecoveryService($this->serviceAddress, $httpClient);
        $response = $service->signTransaction($this->addressA, $this->signingAddress, $this->transaction, $this->jwtToken);

        $this->assertEquals($this->signature, $response->signature);
    }

    public function testAccountDetailsSuccess(): void {

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->detailSuccess)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("GET", $request->getMethod());
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $service = new RecoveryService($this->serviceAddress, $httpClient);
        $response = $service->accountDetails($this->addressA, $this->jwtToken);

        $this->assertEquals($this->addressA, $response->address);
        $this->assertCount(2, $response->identities);
        $this->assertCount(1, $response->signers);
        $this->assertTrue($response->identities[0]->authenticated);
    }

    public function testAccountDeleteSuccess(): void {

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->detailSuccess)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("DELETE", $request->getMethod());
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $service = new RecoveryService($this->serviceAddress, $httpClient);
        $response = $service->deleteAccount($this->addressA, $this->jwtToken);

        $this->assertEquals($this->addressA, $response->address);
        $this->assertCount(2, $response->identities);
        $this->assertCount(1, $response->signers);
        $this->assertTrue($response->identities[0]->authenticated);
    }

    public function testListAccountsSuccess(): void {

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->listSuccess)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("GET", $request->getMethod());
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("GA5TKKASNJZGZAP6FH65HO77CST7CJNYRTW4YPBNPXYMZAHHMTHDZKDQ", $query_array["after"]);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $service = new RecoveryService($this->serviceAddress, $httpClient);
        $response = $service->accounts($this->jwtToken, after: "GA5TKKASNJZGZAP6FH65HO77CST7CJNYRTW4YPBNPXYMZAHHMTHDZKDQ");

        $this->assertCount(3, $response->accounts);
    }

    public function testBadRequest(): void {

        $mock = new MockHandler([
            new Response(400, ['X-Foo' => 'Bar'], '{"error": "Bad request"}')
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("POST", $request->getMethod());
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $service = new RecoveryService($this->serviceAddress, $httpClient);
        $request = new SEP30Request([$this->senderIdentity, $this->receiverIdentity]);
        $thrown = false;
        try {
            $response = $service->registerAccount($this->addressA, $request, $this->jwtToken);
        } catch (SEP30BadRequestResponseException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    public function testUnauthorized(): void {

        $mock = new MockHandler([
            new Response(401, ['X-Foo' => 'Bar'], '{"error": "Unauthorized"}')
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("POST", $request->getMethod());
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $service = new RecoveryService($this->serviceAddress, $httpClient);
        $request = new SEP30Request([$this->senderIdentity, $this->receiverIdentity]);
        $thrown = false;
        try {
            $response = $service->registerAccount($this->addressA, $request, $this->jwtToken);
        } catch (SEP30UnauthorizedResponseException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    public function testNotFound(): void {

        $mock = new MockHandler([
            new Response(404, ['X-Foo' => 'Bar'], '{"error": "Not found"}')
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("POST", $request->getMethod());
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $service = new RecoveryService($this->serviceAddress, $httpClient);
        $request = new SEP30Request([$this->senderIdentity, $this->receiverIdentity]);
        $thrown = false;
        try {
            $response = $service->registerAccount($this->addressA, $request, $this->jwtToken);
        } catch (SEP30NotFoundResponseException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    public function testConflict(): void {

        $mock = new MockHandler([
            new Response(409, ['X-Foo' => 'Bar'], '{"error": "Not found"}')
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("POST", $request->getMethod());
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $service = new RecoveryService($this->serviceAddress, $httpClient);
        $request = new SEP30Request([$this->senderIdentity, $this->receiverIdentity]);
        $thrown = false;
        try {
            $response = $service->registerAccount($this->addressA, $request, $this->jwtToken);
        } catch (SEP30ConflictResponseException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    // Multi-Signer Scenario Tests

    public function testRegisterAccountWithMultipleSigners(): void
    {
        $multiSignerResponse = "{  \"address\": \"GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP\",  \"identities\": [    { \"role\": \"sender\" },    { \"role\": \"receiver\" }  ],  \"signers\": [    { \"key\": \"GDRUPBJM7YIJ2NUNAIQJDJ2DQ2JDERY5SJVJVMM6MGE4UBDAMXBHARIA\" },    { \"key\": \"GBTPAH6NWK25GESZYJ3XWPTNQUIMYNK7VU7R4NSTMZXOEKCOBKJVJ2XY\" },    { \"key\": \"GAOCJE4737GYN2EGCGWPNNCDVDKX7XKC4UKOKIF7CRRYIFLPZLH3U3UN\" }  ]}";

        $mock = new MockHandler([
            new Response(200, [], $multiSignerResponse)
        ]);

        $httpClient = new Client(['handler' => HandlerStack::create($mock)]);
        $service = new RecoveryService($this->serviceAddress, $httpClient);
        $request = new SEP30Request([$this->senderIdentity, $this->receiverIdentity]);
        $response = $service->registerAccount($this->addressA, $request, $this->jwtToken);

        $this->assertEquals($this->addressA, $response->address);
        $this->assertCount(2, $response->identities);
        $this->assertCount(3, $response->signers);
        $this->assertEquals("GDRUPBJM7YIJ2NUNAIQJDJ2DQ2JDERY5SJVJVMM6MGE4UBDAMXBHARIA", $response->signers[0]->key);
        $this->assertEquals("GBTPAH6NWK25GESZYJ3XWPTNQUIMYNK7VU7R4NSTMZXOEKCOBKJVJ2XY", $response->signers[1]->key);
        $this->assertEquals("GAOCJE4737GYN2EGCGWPNNCDVDKX7XKC4UKOKIF7CRRYIFLPZLH3U3UN", $response->signers[2]->key);
    }

    public function testSignTransactionWithMultipleSigners(): void
    {
        $signResponse1 = "{  \"signature\": \"YpVelqPYVKxb8pH08s5AKsYTPwQhbaeSlgcktqwAKsYTPwQhbaeS\",  \"network_passphrase\": \"Test SDF Network ; September 2015\"}";
        $signResponse2 = "{  \"signature\": \"XqWfmrQZWLyc9qI19t6BLtZUQxRicfTmhdslurxBLtZUQxRicfTm\",  \"network_passphrase\": \"Test SDF Network ; September 2015\"}";

        $mock = new MockHandler([
            new Response(200, [], $signResponse1),
            new Response(200, [], $signResponse2)
        ]);

        $httpClient = new Client(['handler' => HandlerStack::create($mock)]);
        $service = new RecoveryService($this->serviceAddress, $httpClient);

        $signingAddress1 = "GDRUPBJM7YIJ2NUNAIQJDJ2DQ2JDERY5SJVJVMM6MGE4UBDAMXBHARIA";
        $signingAddress2 = "GBTPAH6NWK25GESZYJ3XWPTNQUIMYNK7VU7R4NSTMZXOEKCOBKJVJ2XY";

        $response1 = $service->signTransaction($this->addressA, $signingAddress1, $this->transaction, $this->jwtToken);
        $response2 = $service->signTransaction($this->addressA, $signingAddress2, $this->transaction, $this->jwtToken);

        $this->assertEquals("YpVelqPYVKxb8pH08s5AKsYTPwQhbaeSlgcktqwAKsYTPwQhbaeS", $response1->signature);
        $this->assertEquals("XqWfmrQZWLyc9qI19t6BLtZUQxRicfTmhdslurxBLtZUQxRicfTm", $response2->signature);
        $this->assertEquals($this->networkPassphrase, $response1->networkPassphrase);
        $this->assertEquals($this->networkPassphrase, $response2->networkPassphrase);
    }

    public function testAccountDetailsWithMultipleIdentitiesAndSigners(): void
    {
        $multipleDetailsResponse = "{  \"address\": \"GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP\",  \"identities\": [    { \"role\": \"sender\", \"authenticated\": true },    { \"role\": \"receiver\", \"authenticated\": false },    { \"role\": \"owner\", \"authenticated\": true }  ],  \"signers\": [    { \"key\": \"GDRUPBJM7YIJ2NUNAIQJDJ2DQ2JDERY5SJVJVMM6MGE4UBDAMXBHARIA\" },    { \"key\": \"GBTPAH6NWK25GESZYJ3XWPTNQUIMYNK7VU7R4NSTMZXOEKCOBKJVJ2XY\" }  ]}";

        $mock = new MockHandler([
            new Response(200, [], $multipleDetailsResponse)
        ]);

        $httpClient = new Client(['handler' => HandlerStack::create($mock)]);
        $service = new RecoveryService($this->serviceAddress, $httpClient);
        $response = $service->accountDetails($this->addressA, $this->jwtToken);

        $this->assertEquals($this->addressA, $response->address);
        $this->assertCount(3, $response->identities);
        $this->assertCount(2, $response->signers);

        $this->assertEquals("sender", $response->identities[0]->role);
        $this->assertTrue($response->identities[0]->authenticated);

        $this->assertEquals("receiver", $response->identities[1]->role);
        $this->assertFalse($response->identities[1]->authenticated);

        $this->assertEquals("owner", $response->identities[2]->role);
        $this->assertTrue($response->identities[2]->authenticated);
    }

    public function testUpdateIdentitiesWithMultipleAuthMethods(): void
    {
        $multiAuthResponse = "{  \"address\": \"GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP\",  \"identities\": [    { \"role\": \"sender\" },    { \"role\": \"receiver\" },    { \"role\": \"owner\" }  ],  \"signers\": [    { \"key\": \"GDRUPBJM7YIJ2NUNAIQJDJ2DQ2JDERY5SJVJVMM6MGE4UBDAMXBHARIA\" }  ]}";

        $mock = new MockHandler([
            new Response(200, [], $multiAuthResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $body = $request->getBody()->__toString();
            $jsonData = @json_decode($body, true);
            $this->assertCount(3, $jsonData['identities']);

            $senderIdentity = $jsonData['identities'][0];
            $this->assertEquals('sender', $senderIdentity['role']);
            $this->assertGreaterThan(1, count($senderIdentity['auth_methods']));

            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $service = new RecoveryService($this->serviceAddress, $httpClient);

        $ownerAddrAuth = new SEP30AuthMethod("stellar_address", "GBOWNER123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890AB");
        $ownerEmailAuth = new SEP30AuthMethod("email", "owner@example.com");
        $ownerIdentity = new SEP30RequestIdentity("owner", [$ownerAddrAuth, $ownerEmailAuth]);

        $request = new SEP30Request([$this->senderIdentity, $this->receiverIdentity, $ownerIdentity]);
        $response = $service->updateIdentitiesForAccount($this->addressA, $request, $this->jwtToken);

        $this->assertEquals($this->addressA, $response->address);
        $this->assertCount(3, $response->identities);
    }

    public function testListAccountsWithMultipleSignersPerAccount(): void
    {
        $multiSignerListResponse = "{  \"accounts\": [    {      \"address\": \"GBND3FJRQBNFJ4ACERGEXUXU4RKK3ZV2N3FRRFU3ONYU6SJUN6EZXPTD\",      \"identities\": [        {\"role\": \"owner\",  \"authenticated\": true }      ],      \"signers\": [        { \"key\": \"GBTPAH6NWK25GESZYJ3XWPTNQUIMYNK7VU7R4NSTMZXOEKCOBKJVJ2XY\" },        { \"key\": \"GAOCJE4737GYN2EGCGWPNNCDVDKX7XKC4UKOKIF7CRRYIFLPZLH3U3UN\" },        { \"key\": \"GDFPM46I2L2DXB3TWAKPMLUMEW226WXLRWJNS4QHXXKJXEUW3M6OAFBY\" }      ]    },    {      \"address\": \"GA7BLNSL55T2UAON5DYLQHJTR43IPT2O4QG6PAMSNLJJL7JMXKZYYVFJ\",      \"identities\": [        { \"role\": \"sender\", \"authenticated\": true },        { \"role\": \"receiver\" }     ],      \"signers\": [        { \"key\": \"GAOCJE4737GYN2EGCGWPNNCDVDKX7XKC4UKOKIF7CRRYIFLPZLH3U3UN\" },        { \"key\": \"GDFPM46I2L2DXB3TWAKPMLUMEW226WXLRWJNS4QHXXKJXEUW3M6OAFBY\" }      ]    }  ]}";

        $mock = new MockHandler([
            new Response(200, [], $multiSignerListResponse)
        ]);

        $httpClient = new Client(['handler' => HandlerStack::create($mock)]);
        $service = new RecoveryService($this->serviceAddress, $httpClient);
        $response = $service->accounts($this->jwtToken);

        $this->assertCount(2, $response->accounts);

        $firstAccount = $response->accounts[0];
        $this->assertEquals("GBND3FJRQBNFJ4ACERGEXUXU4RKK3ZV2N3FRRFU3ONYU6SJUN6EZXPTD", $firstAccount->address);
        $this->assertCount(3, $firstAccount->signers);

        $secondAccount = $response->accounts[1];
        $this->assertEquals("GA7BLNSL55T2UAON5DYLQHJTR43IPT2O4QG6PAMSNLJJL7JMXKZYYVFJ", $secondAccount->address);
        $this->assertCount(2, $secondAccount->signers);
    }

    public function testRegisterAccountWithSingleIdentityMultipleAuthMethods(): void
    {
        $multiAuthMethodResponse = "{  \"address\": \"GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP\",  \"identities\": [    { \"role\": \"owner\" }  ],  \"signers\": [    { \"key\": \"GDRUPBJM7YIJ2NUNAIQJDJ2DQ2JDERY5SJVJVMM6MGE4UBDAMXBHARIA\" }  ]}";

        $mock = new MockHandler([
            new Response(200, [], $multiAuthMethodResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $body = $request->getBody()->__toString();
            $jsonData = @json_decode($body, true);
            $this->assertCount(1, $jsonData['identities']);

            $ownerIdentity = $jsonData['identities'][0];
            $this->assertEquals('owner', $ownerIdentity['role']);
            $this->assertCount(4, $ownerIdentity['auth_methods']);

            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $service = new RecoveryService($this->serviceAddress, $httpClient);

        $ownerAddrAuth = new SEP30AuthMethod("stellar_address", "GBOWNER123456789");
        $ownerPhoneAuth = new SEP30AuthMethod("phone_number", "+1234567890");
        $ownerEmailAuth = new SEP30AuthMethod("email", "owner@example.com");
        $ownerSmsAuth = new SEP30AuthMethod("sms", "+9876543210");
        $ownerIdentity = new SEP30RequestIdentity("owner", [$ownerAddrAuth, $ownerPhoneAuth, $ownerEmailAuth, $ownerSmsAuth]);

        $request = new SEP30Request([$ownerIdentity]);
        $response = $service->registerAccount($this->addressA, $request, $this->jwtToken);

        $this->assertEquals($this->addressA, $response->address);
        $this->assertCount(1, $response->identities);
    }

    public function testSignerKeyRotationScenario(): void
    {
        $initialResponse = "{  \"address\": \"GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP\",  \"identities\": [    { \"role\": \"owner\" }  ],  \"signers\": [    { \"key\": \"GDRUPBJM7YIJ2NUNAIQJDJ2DQ2JDERY5SJVJVMM6MGE4UBDAMXBHARIA\" }  ]}";
        $updatedResponse = "{  \"address\": \"GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP\",  \"identities\": [    { \"role\": \"owner\" }  ],  \"signers\": [    { \"key\": \"GBTPAH6NWK25GESZYJ3XWPTNQUIMYNK7VU7R4NSTMZXOEKCOBKJVJ2XY\" }  ]}";

        $mock = new MockHandler([
            new Response(200, [], $initialResponse),
            new Response(200, [], $updatedResponse)
        ]);

        $httpClient = new Client(['handler' => HandlerStack::create($mock)]);
        $service = new RecoveryService($this->serviceAddress, $httpClient);

        $initialDetails = $service->accountDetails($this->addressA, $this->jwtToken);
        $this->assertCount(1, $initialDetails->signers);
        $this->assertEquals("GDRUPBJM7YIJ2NUNAIQJDJ2DQ2JDERY5SJVJVMM6MGE4UBDAMXBHARIA", $initialDetails->signers[0]->key);

        $updatedDetails = $service->accountDetails($this->addressA, $this->jwtToken);
        $this->assertCount(1, $updatedDetails->signers);
        $this->assertEquals("GBTPAH6NWK25GESZYJ3XWPTNQUIMYNK7VU7R4NSTMZXOEKCOBKJVJ2XY", $updatedDetails->signers[0]->key);
    }
}
