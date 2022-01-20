<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Soneso\StellarSDK\SEP\KYCService\GetCustomerInfoField;
use Soneso\StellarSDK\SEP\KYCService\GetCustomerInfoProvidedField;
use Soneso\StellarSDK\SEP\KYCService\GetCustomerInfoRequest;
use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerCallbackRequest;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerVerificationRequest;

class SEP012Test extends TestCase
{
    private string $serviceAddress = "http://api.stellar.org/kyc";
    private string $customerId = "d1ce2f48-3ff1-495d-9240-7a50d806cfed";
    private string $accountId = "GA6UIXXPEWYFILNUIWAC37Y4QPEZMQVDJHDKVWFZJ2KCWUBIU5IXZNDA";
    private string $jwtToken = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJHQTZVSVhYUEVXWUZJTE5VSVdBQzM3WTRRUEVaTVFWREpIREtWV0ZaSjJLQ1dVQklVNUlYWk5EQSIsImp0aSI6IjE0NGQzNjdiY2IwZTcyY2FiZmRiZGU2MGVhZTBhZDczM2NjNjVkMmE2NTg3MDgzZGFiM2Q2MTZmODg1MTkwMjQiLCJpc3MiOiJodHRwczovL2ZsYXBweS1iaXJkLWRhcHAuZmlyZWJhc2VhcHAuY29tLyIsImlhdCI6MTUzNDI1Nzk5NCwiZXhwIjoxNTM0MzQ0Mzk0fQ.8nbB83Z6vGBgC1X9r3N6oQCFTBzDiITAfCJasRft0z0";

    private function requestGetCustomerSuccess() : string {
        return "{\"id\": \"d1ce2f48-3ff1-495d-9240-7a50d806cfed\",\"status\": \"ACCEPTED\",\"provided_fields\": {   \"first_name\": {      \"description\": \"The customer's first name\",      \"type\": \"string\",      \"status\": \"ACCEPTED\"   },   \"last_name\": {      \"description\": \"The customer's last name\",      \"type\": \"string\",      \"status\": \"ACCEPTED\"   },   \"email_address\": {      \"description\": \"The customer's email address\",      \"type\": \"string\",      \"status\": \"ACCEPTED\"   }}}";
    }

    private function requestGetCustomerNotAllRequiredInfo() : string {
        return "{\"id\": \"d1ce2f48-3ff1-495d-9240-7a50d806cfed\",\"status\": \"NEEDS_INFO\",\"fields\": {   \"mobile_number\": {      \"description\": \"phone number of the customer\",      \"type\": \"string\"   },   \"email_address\": {      \"description\": \"email address of the customer\",      \"type\": \"string\",      \"optional\": true   }},\"provided_fields\": {   \"first_name\": {      \"description\": \"The customer's first name\",      \"type\": \"string\",      \"status\": \"ACCEPTED\"   },   \"last_name\": {      \"description\": \"The customer's last name\",      \"type\": \"string\",      \"status\": \"ACCEPTED\"   }}}";
    }

    private function requestGetCustomerRequiresInfo() : string {
        return "{\"status\": \"NEEDS_INFO\",\"fields\": {   \"email_address\": {      \"description\": \"Email address of the customer\",      \"type\": \"string\",      \"optional\": true   },   \"id_type\": {      \"description\": \"Government issued ID\",      \"type\": \"string\",      \"choices\": [         \"Passport\",         \"Drivers License\",         \"State ID\"      ]   },   \"photo_id_front\": {      \"description\": \"A clear photo of the front of the government issued ID\",      \"type\": \"binary\"  }}}";
    }

    private function requestGetCustomerProcessing() : string {
        return "{ \"id\": \"d1ce2f48-3ff1-495d-9240-7a50d806cfed\", \"status\": \"PROCESSING\", \"message\": \"Photo ID requires manual review. This process typically takes 1-2 business days.\", \"provided_fields\": {   \"photo_id_front\": {      \"description\": \"A clear photo of the front of the government issued ID\",      \"type\": \"binary\",      \"status\": \"PROCESSING\"   } }}";
    }

    private function requestGetCustomerRejected() : string {
        return "{\"id\": \"d1ce2f48-3ff1-495d-9240-7a50d806cfed\",\"status\": \"REJECTED\",\"message\": \"This person is on a sanctions list\"}";
    }

    private function requestGetCustomerRequiresVerification() : string  {
        return "{\"id\": \"d1ce2f48-3ff1-495d-9240-7a50d806cfed\",\"status\": \"NEEDS_INFO\",\"provided_fields\": {   \"mobile_number\": {      \"description\": \"phone number of the customer\",      \"type\": \"string\",      \"status\": \"VERIFICATION_REQUIRED\"   }}}";
    }

    private function requestPutCustomerInfo() : string  {
        return "{\"id\": \"d1ce2f48-3ff1-495d-9240-7a50d806cfed\"}";
    }

    private function requestPutCustomerVerification() : string  {
        return "{\"id\": \"d1ce2f48-3ff1-495d-9240-7a50d806cfed\",\"status\": \"ACCEPTED\",\"provided_fields\": {   \"mobile_number\": {      \"description\": \"phone number of the customer\",      \"type\": \"string\",      \"status\": \"ACCEPTED\"   }}}";
    }

    public function testGetCustomerInfoSuccess(): void {
        $kycService = new KYCService($this->serviceAddress);
        $request = new GetCustomerInfoRequest();
        $request->id = $this->customerId;
        $request->account = $this->accountId;
        $request->jwt = $this->jwtToken;

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestGetCustomerSuccess())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer ".$this->jwtToken, $auth);
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals($this->customerId, $query_array["id"]);
            $this->assertEquals($this->accountId, $query_array["account"]);
            return $request;
        }));

        $kycService->setMockHandlerStack($stack);
        $response = $kycService->getCustomerInfo($request);
        $this->assertNotNull($response);
        $this->assertEquals($this->customerId, $response->getId());
        $this->assertEquals("ACCEPTED", $response->getStatus());
        $this->assertNotNull($response->getProvidedFields());
        $this->assertCount(3, $response->getProvidedFields());
        $providedFields = $response->getProvidedFields();
        $firstName = $providedFields["first_name"];
        if ($firstName instanceof GetCustomerInfoProvidedField) {
            $this->assertEquals("The customer's first name", $firstName->getDescription());
            $this->assertEquals("string", $firstName->getType());
            $this->assertEquals("ACCEPTED", $firstName->getStatus());
        } else {
            $this->fail();
        }
        $lastName = $providedFields["last_name"];
        if ($lastName instanceof GetCustomerInfoProvidedField) {
            $this->assertEquals("The customer's last name", $lastName->getDescription());
            $this->assertEquals("string", $lastName->getType());
            $this->assertEquals("ACCEPTED", $lastName->getStatus());
        } else {
            $this->fail();
        }
        $emailAddress = $providedFields["email_address"];
        if ($emailAddress instanceof GetCustomerInfoProvidedField) {
            $this->assertEquals("The customer's email address", $emailAddress->getDescription());
            $this->assertEquals("string", $emailAddress->getType());
            $this->assertEquals("ACCEPTED", $emailAddress->getStatus());
        } else {
            $this->fail();
        }
    }

    public function testGetCustomerNotAllRequiredInfo(): void {
        $kycService = new KYCService($this->serviceAddress);
        $request = new GetCustomerInfoRequest();
        $request->id = $this->customerId;
        $request->account = $this->accountId;
        $request->jwt = $this->jwtToken;

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestGetCustomerNotAllRequiredInfo())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer ".$this->jwtToken, $auth);
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals($this->customerId, $query_array["id"]);
            $this->assertEquals($this->accountId, $query_array["account"]);
            return $request;
        }));

        $kycService->setMockHandlerStack($stack);
        $response = $kycService->getCustomerInfo($request);
        $this->assertNotNull($response);
        $this->assertEquals($this->customerId, $response->getId());
        $this->assertEquals("NEEDS_INFO", $response->getStatus());
        $this->assertNotNull($response->getFields());
        $this->assertCount(2, $response->getFields());
        $fields = $response->getFields();
        $mobileNr = $fields["mobile_number"];
        if ($mobileNr instanceof GetCustomerInfoField) {
            $this->assertEquals("phone number of the customer", $mobileNr->getDescription());
            $this->assertEquals("string", $mobileNr->getType());
        } else {
            $this->fail();
        }
        $emailAddress = $fields["email_address"];
        if ($emailAddress instanceof GetCustomerInfoField) {
            $this->assertEquals("email address of the customer", $emailAddress->getDescription());
            $this->assertEquals("string", $emailAddress->getType());
            $this->assertTrue($emailAddress->isOptional());
        } else {
            $this->fail();
        }
        $providedFields = $response->getProvidedFields();
        $firstName = $providedFields["first_name"];
        if ($firstName instanceof GetCustomerInfoProvidedField) {
            $this->assertEquals("The customer's first name", $firstName->getDescription());
            $this->assertEquals("string", $firstName->getType());
            $this->assertEquals("ACCEPTED", $firstName->getStatus());
        } else {
            $this->fail();
        }
        $lastName = $providedFields["last_name"];
        if ($lastName instanceof GetCustomerInfoProvidedField) {
            $this->assertEquals("The customer's last name", $lastName->getDescription());
            $this->assertEquals("string", $lastName->getType());
            $this->assertEquals("ACCEPTED", $lastName->getStatus());
        } else {
            $this->fail();
        }
    }
    public function testGetCustomerRequiresInfo(): void
    {
        $kycService = new KYCService($this->serviceAddress);
        $request = new GetCustomerInfoRequest();
        $request->id = $this->customerId;
        $request->account = $this->accountId;
        $request->jwt = $this->jwtToken;

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestGetCustomerRequiresInfo())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals($this->customerId, $query_array["id"]);
            $this->assertEquals($this->accountId, $query_array["account"]);
            return $request;
        }));

        $kycService->setMockHandlerStack($stack);
        $response = $kycService->getCustomerInfo($request);
        $this->assertNotNull($response);
        $this->assertEquals("NEEDS_INFO", $response->getStatus());
        $this->assertNotNull($response->getFields());
        $this->assertCount(3, $response->getFields());
        $fields = $response->getFields();
        $emailAddress = $fields["email_address"];
        if ($emailAddress instanceof GetCustomerInfoField) {
            $this->assertEquals("Email address of the customer", $emailAddress->getDescription());
            $this->assertEquals("string", $emailAddress->getType());
            $this->assertTrue($emailAddress->isOptional());
        } else {
            $this->fail();
        }
        $idType = $fields["id_type"];
        if ($idType instanceof GetCustomerInfoField) {
            $this->assertEquals("Government issued ID", $idType->getDescription());
            $this->assertEquals("string", $idType->getType());
            $choices = $idType->getChoices();
            $this->assertNotNull($choices);
            $this->assertCount(3, $choices);
            $this->assertTrue(in_array("Passport", $choices));
            $this->assertTrue(in_array("Drivers License", $choices));
            $this->assertTrue(in_array("State ID", $choices));
        } else {
            $this->fail();
        }
        $photoIdFront = $fields["photo_id_front"];
        if ($photoIdFront instanceof GetCustomerInfoField) {
            $this->assertEquals("A clear photo of the front of the government issued ID", $photoIdFront->getDescription());
            $this->assertEquals("binary", $photoIdFront->getType());
        } else {
            $this->fail();
        }
    }

    public function testGetCustomerProcessing(): void
    {
        $kycService = new KYCService($this->serviceAddress);
        $request = new GetCustomerInfoRequest();
        $request->id = $this->customerId;
        $request->account = $this->accountId;
        $request->jwt = $this->jwtToken;

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestGetCustomerProcessing())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals($this->customerId, $query_array["id"]);
            $this->assertEquals($this->accountId, $query_array["account"]);
            return $request;
        }));

        $kycService->setMockHandlerStack($stack);
        $response = $kycService->getCustomerInfo($request);
        $this->assertNotNull($response);
        $this->assertEquals($this->customerId, $response->getId());
        $this->assertEquals("PROCESSING", $response->getStatus());
        $this->assertEquals("Photo ID requires manual review. This process typically takes 1-2 business days.", $response->getMessage());

        $providedFields = $response->getProvidedFields();
        $photoIdFront = $providedFields["photo_id_front"];
        if ($photoIdFront instanceof GetCustomerInfoProvidedField) {
            $this->assertEquals("A clear photo of the front of the government issued ID", $photoIdFront->getDescription());
            $this->assertEquals("binary", $photoIdFront->getType());
            $this->assertEquals("PROCESSING", $photoIdFront->getStatus());
        } else {
            $this->fail();
        }
    }

    public function testGetCustomerRejected(): void
    {
        $kycService = new KYCService($this->serviceAddress);
        $request = new GetCustomerInfoRequest();
        $request->id = $this->customerId;
        $request->account = $this->accountId;
        $request->jwt = $this->jwtToken;

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestGetCustomerRejected())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals($this->customerId, $query_array["id"]);
            $this->assertEquals($this->accountId, $query_array["account"]);
            return $request;
        }));

        $kycService->setMockHandlerStack($stack);
        $response = $kycService->getCustomerInfo($request);
        $this->assertNotNull($response);
        $this->assertEquals($this->customerId, $response->getId());
        $this->assertEquals("REJECTED", $response->getStatus());
        $this->assertEquals("This person is on a sanctions list", $response->getMessage());
    }

    public function testGetCustomerRequiresVerification(): void
    {
        $kycService = new KYCService($this->serviceAddress);
        $request = new GetCustomerInfoRequest();
        $request->id = $this->customerId;
        $request->account = $this->accountId;
        $request->jwt = $this->jwtToken;

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestGetCustomerRequiresVerification())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals($this->customerId, $query_array["id"]);
            $this->assertEquals($this->accountId, $query_array["account"]);
            return $request;
        }));

        $kycService->setMockHandlerStack($stack);
        $response = $kycService->getCustomerInfo($request);
        $this->assertNotNull($response);
        $this->assertEquals($this->customerId, $response->getId());
        $this->assertEquals("NEEDS_INFO", $response->getStatus());

        $providedFields = $response->getProvidedFields();
        $mobileNr = $providedFields["mobile_number"];
        if ($mobileNr instanceof GetCustomerInfoProvidedField) {
            $this->assertEquals("phone number of the customer", $mobileNr->getDescription());
            $this->assertEquals("string", $mobileNr->getType());
            $this->assertEquals("VERIFICATION_REQUIRED", $mobileNr->getStatus());
        } else {
            $this->fail();
        }
    }

    public function testPutCustomerInfo(): void
    {
        $kycService = new KYCService($this->serviceAddress);
        $request = new PutCustomerInfoRequest();
        $request->id = $this->customerId;
        $request->account = $this->accountId;
        $request->jwt = $this->jwtToken;

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestPutCustomerInfo())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("PUT", $request->getMethod());
            $body = $request->getBody()->__toString();
            $this->assertTrue(str_contains($body, $this->customerId));
            $this->assertTrue(str_contains($body, $this->accountId));
            return $request;
        }));

        $kycService->setMockHandlerStack($stack);
        $response = $kycService->putCustomerInfo($request);
        $this->assertNotNull($response);
        $this->assertEquals($this->customerId, $response->getId());
    }

    public function testPutCustomerVerification(): void
    {
        $kycService = new KYCService($this->serviceAddress);
        $request = new PutCustomerVerificationRequest();
        $request->id = $this->customerId;
        $request->jwt = $this->jwtToken;
        $fields = array();
        $fields += ["id" => $this->customerId];
        $fields += ["mobile_number_verification" => "2735021"];
        $request->verificationFields = $fields;

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestPutCustomerVerification())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("PUT", $request->getMethod());
            $body = $request->getBody()->__toString();
            $this->assertTrue(str_contains($body, $this->customerId));
            $this->assertTrue(str_contains($body, "mobile_number_verification"));
            $this->assertTrue(str_contains($body, "2735021"));
            return $request;
        }));

        $kycService->setMockHandlerStack($stack);
        $response = $kycService->putCustomerVerification($request);
        $this->assertNotNull($response);
        $this->assertEquals($this->customerId, $response->getId());
        $this->assertEquals("ACCEPTED", $response->getStatus());
        $providedFields = $response->getProvidedFields();
        $mobileNr = $providedFields["mobile_number"];
        if ($mobileNr instanceof GetCustomerInfoProvidedField) {
            $this->assertEquals("phone number of the customer", $mobileNr->getDescription());
            $this->assertEquals("string", $mobileNr->getType());
            $this->assertEquals("ACCEPTED", $mobileNr->getStatus());
        } else {
            $this->fail();
        }
    }

    public function testDeleteCustomer(): void
    {
        $kycService = new KYCService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], "")
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("DELETE", $request->getMethod());
            $this->assertTrue(str_contains($request->getUri()->getPath(), $this->accountId));
            $body = $request->getBody()->__toString();
            $this->assertTrue(str_contains($body, "19191991919"));
            $this->assertTrue(str_contains($body, "memo"));
            $this->assertTrue(str_contains($body, "memo_type"));
            return $request;
        }));

        $kycService->setMockHandlerStack($stack);
        $response = $kycService->deleteCustomer($this->accountId, $this->jwtToken, "19191991919", "id");
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPutCustomerCallback(): void
    {
        $kycService = new KYCService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], "")
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("PUT", $request->getMethod());
            $body = $request->getBody()->__toString();
            $this->assertTrue(str_contains($body, $this->customerId));
            $this->assertTrue(str_contains($body, "https://test.com/callback"));
            $this->assertTrue(str_contains($body, "19191991919"));
            $this->assertTrue(str_contains($body, "memo"));
            $this->assertTrue(str_contains($body, "memo_type"));
            return $request;
        }));

        $request = new PutCustomerCallbackRequest();
        $request->url = "https://test.com/callback";
        $request->id = $this->customerId;
        $request->account = $this->accountId;
        $request->memoType = "id";
        $request->memo = "19191991919";
        $request->jwt = $this->jwtToken;

        $kycService->setMockHandlerStack($stack);
        $response = $kycService->putCustomerCallback($request);
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}