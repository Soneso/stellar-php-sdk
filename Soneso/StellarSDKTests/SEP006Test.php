<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Soneso\StellarSDK\SEP\TransferServerService\AnchorField;
use Soneso\StellarSDK\SEP\TransferServerService\AnchorTransactionRequest;
use Soneso\StellarSDK\SEP\TransferServerService\AnchorTransactionsRequest;
use Soneso\StellarSDK\SEP\TransferServerService\CustomerInformationNeededException;
use Soneso\StellarSDK\SEP\TransferServerService\CustomerInformationStatusException;
use Soneso\StellarSDK\SEP\TransferServerService\DepositAsset;
use Soneso\StellarSDK\SEP\TransferServerService\DepositRequest;
use Soneso\StellarSDK\SEP\TransferServerService\FeeRequest;
use Soneso\StellarSDK\SEP\TransferServerService\PatchTransactionRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;
use Soneso\StellarSDK\SEP\TransferServerService\WithdrawAsset;
use Soneso\StellarSDK\SEP\TransferServerService\WithdrawRequest;

class SEP006Test extends TestCase
{
    private string $serviceAddress = "http://api.stellar.org/transfer";
    private string $accountId = "GA6UIXXPEWYFILNUIWAC37Y4QPEZMQVDJHDKVWFZJ2KCWUBIU5IXZNDA";
    private string $jwtToken = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJHQTZVSVhYUEVXWUZJTE5VSVdBQzM3WTRRUEVaTVFWREpIREtWV0ZaSjJLQ1dVQklVNUlYWk5EQSIsImp0aSI6IjE0NGQzNjdiY2IwZTcyY2FiZmRiZGU2MGVhZTBhZDczM2NjNjVkMmE2NTg3MDgzZGFiM2Q2MTZmODg1MTkwMjQiLCJpc3MiOiJodHRwczovL2ZsYXBweS1iaXJkLWRhcHAuZmlyZWJhc2VhcHAuY29tLyIsImlhdCI6MTUzNDI1Nzk5NCwiZXhwIjoxNTM0MzQ0Mzk0fQ.8nbB83Z6vGBgC1X9r3N6oQCFTBzDiITAfCJasRft0z0";

    private function requestInfo() : string {
        return "{\"deposit\": {  \"USD\": {    \"enabled\": true,    \"authentication_required\": true,    \"fee_fixed\": 5,    \"fee_percent\": 1,    \"min_amount\": 0.1,    \"max_amount\": 1000,    \"fields\": {      \"email_address\" : {        \"description\": \"your email address for transaction status updates\",        \"optional\": true      },      \"amount\" : {        \"description\": \"amount in USD that you plan to deposit\"      },      \"country_code\": {        \"description\": \"The ISO 3166-1 alpha-3 code of the user's current address\",        \"choices\": [\"USA\", \"PRI\"]      },      \"type\" : {        \"description\": \"type of deposit to make\",        \"choices\": [\"SEPA\", \"SWIFT\", \"cash\"]      }    }  },  \"ETH\": {    \"enabled\": true,    \"authentication_required\": false,    \"fee_fixed\": 0.002,    \"fee_percent\": 0  }},\"withdraw\": {  \"USD\": {    \"enabled\": true,    \"authentication_required\": true,    \"fee_fixed\": 5,    \"fee_percent\": 0,    \"min_amount\": 0.1,    \"max_amount\": 1000,    \"types\": {      \"bank_account\": {        \"fields\": {            \"dest\": {\"description\": \"your bank account number\" },            \"dest_extra\": { \"description\": \"your routing number\" },            \"bank_branch\": { \"description\": \"address of your bank branch\" },            \"phone_number\": { \"description\": \"your phone number in case there's an issue\" },            \"country_code\": {               \"description\": \"The ISO 3166-1 alpha-3 code of the user's current address\",              \"choices\": [\"USA\", \"PRI\"]            }        }      },      \"cash\": {        \"fields\": {          \"dest\": {             \"description\": \"your email address. Your cashout PIN will be sent here. If not provided, your account's default email will be used\",            \"optional\": true          }        }      }    }  },  \"ETH\": {    \"enabled\": false  }},\"fee\": {  \"enabled\": false},\"transactions\": {  \"enabled\": true,   \"authentication_required\": true},\"transaction\": {  \"enabled\": false,  \"authentication_required\": true}}";
    }

    private function requestFee() : string {
        return "{\"fee\": 0.013}";
    }

    private function requestBTCDeposit() : string {
        return "{\"how\" : \"1Nh7uHdvY6fNwtQtM1G5EZAFPLC33B59rB\",\"id\": \"9421871e-0623-4356-b7b5-5996da122f3e\",\"fee_fixed\" : 0.0002}";
    }

    private function requestRippleDeposit() : string {
        return "{\"how\" : \"Ripple address: rNXEkKCxvfLcM1h4HJkaj2FtmYuAWrHGbf tag: 88\",\"id\": \"9421871e-0623-4356-b7b5-5996da122f3e\",\"eta\": 60,\"fee_percent\" : 0.1,\"extra_info\": {  \"message\": \"You must include the tag. If the amount is more than 1000 XRP, deposit will take 24h to complete.\"}}";
    }

    private function requestMXNDeposit() : string {
        return "{\"how\" : \"Make a payment to Bank: STP Account: 646180111803859359\",\"id\": \"9421871e-0623-4356-b7b5-5996da122f3e\",\"eta\": 1800}";
    }

    private function requestWithdrawSuccess() : string {
        return "{\"account_id\": \"GCIBUCGPOHWMMMFPFTDWBSVHQRT4DIBJ7AD6BZJYDITBK2LCVBYW7HUQ\",\"memo_type\": \"id\",\"memo\": \"123\",\"id\": \"9421871e-0623-4356-b7b5-5996da122f3e\"}";
    }

    private function requestCustomerInformationNeeded() : string {
        return "{\"type\": \"non_interactive_customer_info_needed\",\"fields\" : [\"family_name\", \"given_name\", \"address\", \"tax_id\"]}";
    }

    private function requestCustomerInformationStatus() : string {
        return "{\"type\": \"customer_info_status\",\"status\": \"denied\",\"more_info_url\": \"https:\/\/api.example.com\/kycstatus?account=GACW7NONV43MZIFHCOKCQJAKSJSISSICFVUJ2C6EZIW5773OU3HD64VI\"}";
    }

    private function requestTransactions() : string {
        return "{\"transactions\": [  {    \"id\": \"82fhs729f63dh0v4\",    \"kind\": \"deposit\",    \"status\": \"pending_external\",    \"status_eta\": 3600,    \"external_transaction_id\": \"2dd16cb409513026fbe7defc0c6f826c2d2c65c3da993f747d09bf7dafd31093\",    \"amount_in\": \"18.34\",    \"amount_out\": \"18.24\",    \"amount_fee\": \"0.1\",    \"started_at\": \"2017-03-20T17:05:32Z\"  },  {    \"id\": \"82fhs729f63dh0v4\",    \"kind\": \"withdrawal\",    \"status\": \"completed\",    \"amount_in\": \"500\",    \"amount_out\": \"495\",    \"amount_fee\": \"3\",    \"started_at\": \"2017-03-20T17:00:02Z\",    \"completed_at\": \"2017-03-20T17:09:58Z\",    \"stellar_transaction_id\": \"17a670bc424ff5ce3b386dbfaae9990b66a2a37b4fbe51547e8794962a3f9e6a\",    \"external_transaction_id\": \"2dd16cb409513026fbe7defc0c6f826c2d2c65c3da993f747d09bf7dafd31093\"  },  {    \"id\": \"52fys79f63dh3v1\",    \"kind\": \"withdrawal\",    \"status\": \"pending_transaction_info_update\",    \"amount_in\": \"750.00\",    \"amount_out\": null,    \"amount_fee\": null,    \"started_at\": \"2017-03-20T17:00:02Z\",    \"required_info_message\": \"We were unable to send funds to the provided bank account. Bank error: 'Account does not exist'. Please provide the correct bank account address.\",    \"required_info_updates\": {      \"transaction\": {        \"dest\": {\"description\": \"your bank account number\" },        \"dest_extra\": { \"description\": \"your routing number\" }      }    }  }]}";
    }

    private function requestTransaction() : string {
        return "{\"transaction\": {    \"id\": \"82fhs729f63dh0v4\",    \"kind\": \"deposit\",    \"status\": \"pending_external\",    \"status_eta\": 3600,    \"external_transaction_id\": \"2dd16cb409513026fbe7defc0c6f826c2d2c65c3da993f747d09bf7dafd31093\",    \"amount_in\": \"18.34\",    \"amount_out\": \"18.24\",    \"amount_fee\": \"0.1\",    \"started_at\": \"2017-03-20T17:05:32Z\"  }}";
    }

    public function testInfo(): void {
        $transferService = new TransferServerService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestInfo())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer ".$this->jwtToken, $auth);
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("de", $query_array["lang"]);
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);
        $response = $transferService->info($this->jwtToken,"de");
        $this->assertNotNull($response);
        $this->assertCount(2, $response->getDepositAssets());

        $depositAssetUSD = $response->getDepositAssets()["USD"];
        $this->assertNotNull($depositAssetUSD);
        $this->assertTrue($depositAssetUSD instanceof DepositAsset);
        if ($depositAssetUSD instanceof DepositAsset) {
            $this->assertTrue($depositAssetUSD->getEnabled());
            $this->assertTrue($depositAssetUSD->getAuthenticationRequired());
            $this->assertEquals(5.0, $depositAssetUSD->getFeeFixed());
            $this->assertEquals(1.0, $depositAssetUSD->getFeePercent());
            $this->assertEquals(0.1, $depositAssetUSD->getMinAmount());
            $this->assertEquals(1000.0, $depositAssetUSD->getMaxAmount());
        }
        $dUSDfields = $depositAssetUSD->getFields();
        $this->assertNotNull($dUSDfields);
        $this->assertCount(4, $dUSDfields);
        $emailAddress = $dUSDfields["email_address"];
        $this->assertNotNull($emailAddress);
        $this->assertTrue($emailAddress instanceof AnchorField);
        if ($emailAddress instanceof AnchorField) {
            $this->assertEquals("your email address for transaction status updates", $emailAddress->getDescription());
            $this->assertTrue($emailAddress->getOptional());
        }
        $this->assertTrue(in_array("USA", $dUSDfields["country_code"]->getChoices()));
        $this->assertTrue(in_array("SWIFT", $dUSDfields["type"]->getChoices()));

        $withdrawAssetUSD = $response->getWithdrawAssets()["USD"];
        $this->assertNotNull($withdrawAssetUSD);
        $this->assertTrue($withdrawAssetUSD instanceof WithdrawAsset);
        if ($withdrawAssetUSD instanceof WithdrawAsset) {
            $this->assertTrue($withdrawAssetUSD->getEnabled());
            $this->assertTrue($withdrawAssetUSD->getAuthenticationRequired());
            $this->assertEquals(5.0, $withdrawAssetUSD->getFeeFixed());
            $this->assertEquals(0.0, $withdrawAssetUSD->getFeePercent());
            $this->assertEquals(0.1, $withdrawAssetUSD->getMinAmount());
            $this->assertEquals(1000.0, $withdrawAssetUSD->getMaxAmount());
        }
        $types = $withdrawAssetUSD->getTypes();
        $this->assertNotNull($types);
        $this->assertCount(2, $types);
        $bankAccountFields = $types["bank_account"];
        $this->assertNotNull($bankAccountFields);
        $this->assertTrue(in_array("PRI", $bankAccountFields["country_code"]->getChoices()));
        $this->assertTrue($types["cash"]["dest"]->getOptional());
        $this->assertFalse($response->getWithdrawAssets()["ETH"]->getEnabled());
        $this->assertFalse($response->getFeeInfo()->isEnabled());
        $this->assertTrue($response->getTransactionsInfo()->isEnabled());
        $this->assertTrue($response->getTransactionsInfo()->getAuthenticationRequired());
        $this->assertFalse($response->getTransactionInfo()->isEnabled());
        $this->assertTrue($response->getTransactionInfo()->getAuthenticationRequired());
    }

    public function testFee(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestFee())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("deposit", $query_array["operation"]);
            $this->assertEquals("SEPA", $query_array["type"]);
            $this->assertEquals("ETH", $query_array["asset_code"]);
            $this->assertEquals(2034.09, $query_array["amount"]);
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);

        $request = new FeeRequest();
        $request->jwt = $this->jwtToken;
        $request->operation = "deposit";
        $request->type = "SEPA";
        $request->assetCode = "ETH";
        $request->amount = 2034.09;
        $response = $transferService->fee($request);
        $this->assertNotNull($response);
        $this->assertEquals(0.013, $response->getFee());
    }

    public function testDepositBTCSuccess(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestBTCDeposit())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("BTC", $query_array["asset_code"]);
            $this->assertEquals($this->accountId, $query_array["account"]);
            $this->assertEquals("3.123", $query_array["amount"]);
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);
        $request = new DepositRequest();
        $request->jwt = $this->jwtToken;
        $request->assetCode = "BTC";
        $request->account = $this->accountId;
        $request->amount = "3.123";
        $response = $transferService->deposit($request);
        $this->assertNotNull($response);
        $this->assertEquals("1Nh7uHdvY6fNwtQtM1G5EZAFPLC33B59rB", $response->getHow());
        $this->assertEquals("9421871e-0623-4356-b7b5-5996da122f3e", $response->getId());
        $this->assertEquals(0.0002, $response->getFeeFixed());
    }

    public function testDepositRippleSuccess(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestRippleDeposit())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("XRP", $query_array["asset_code"]);
            $this->assertEquals($this->accountId, $query_array["account"]);
            $this->assertEquals("300.0", $query_array["amount"]);
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);
        $request = new DepositRequest();
        $request->jwt = $this->jwtToken;
        $request->assetCode = "XRP";
        $request->account = $this->accountId;
        $request->amount = "300.0";
        $response = $transferService->deposit($request);
        $this->assertNotNull($response);
        $this->assertEquals("Ripple address: rNXEkKCxvfLcM1h4HJkaj2FtmYuAWrHGbf tag: 88", $response->getHow());
        $this->assertEquals("9421871e-0623-4356-b7b5-5996da122f3e", $response->getId());
        $this->assertEquals(60, $response->getEta());
        $this->assertEquals(0.1, $response->getFeePercent());
        $this->assertEquals("You must include the tag. If the amount is more than 1000 XRP, deposit will take 24h to complete.", $response->getExtraInfo()["message"]);
    }

    public function testDepositMXNSuccess(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestMXNDeposit())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("MXN", $query_array["asset_code"]);
            $this->assertEquals($this->accountId, $query_array["account"]);
            $this->assertEquals("120.0", $query_array["amount"]);
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);
        $request = new DepositRequest();
        $request->jwt = $this->jwtToken;
        $request->assetCode = "MXN";
        $request->account = $this->accountId;
        $request->amount = "120.0";
        $response = $transferService->deposit($request);
        $this->assertNotNull($response);
        $this->assertEquals("Make a payment to Bank: STP Account: 646180111803859359", $response->getHow());
        $this->assertEquals("9421871e-0623-4356-b7b5-5996da122f3e", $response->getId());
        $this->assertEquals(1800, $response->getEta());
    }

    public function testWithdrawSuccess(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestWithdrawSuccess())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("XLM", $query_array["asset_code"]);
            $this->assertEquals("crypto", $query_array["type"]);
            $this->assertEquals($this->accountId, $query_array["account"]);
            $this->assertEquals("120.0", $query_array["amount"]);
            $this->assertEquals("GCTTGO5ABSTHABXWL2FMHPZ2XFOZDXJYJN5CKFRKXMPAAWZW3Y3JZ3JK", $query_array["dest"]);
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);
        $request = new WithdrawRequest();
        $request->jwt = $this->jwtToken;
        $request->assetCode = "XLM";
        $request->type = "crypto";
        $request->dest = "GCTTGO5ABSTHABXWL2FMHPZ2XFOZDXJYJN5CKFRKXMPAAWZW3Y3JZ3JK";
        $request->account = $this->accountId;
        $request->amount = "120.0";
        $response = $transferService->withdraw($request);
        $this->assertNotNull($response);
        $this->assertEquals("GCIBUCGPOHWMMMFPFTDWBSVHQRT4DIBJ7AD6BZJYDITBK2LCVBYW7HUQ", $response->getAccountId());
        $this->assertEquals("id", $response->getMemoType());
        $this->assertEquals("123", $response->getMemo());
        $this->assertEquals("9421871e-0623-4356-b7b5-5996da122f3e", $response->getId());
    }

    public function testDepositCustomerInformationNeeded(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(403, ['X-Foo' => 'Bar'], $this->requestCustomerInformationNeeded())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("MXN", $query_array["asset_code"]);
            $this->assertEquals($this->accountId, $query_array["account"]);
            $this->assertEquals("120.0", $query_array["amount"]);
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);
        $request = new DepositRequest();
        $request->jwt = $this->jwtToken;
        $request->assetCode = "MXN";
        $request->account = $this->accountId;
        $request->amount = "120.0";
        $thrown = false;
        try {
            $response = $transferService->deposit($request);
        } catch (CustomerInformationNeededException $e) {
            $thrown = true;
            $response = $e->getResponse();
            $this->assertContains("tax_id", $response->getFields());
        }
        $this->assertTrue($thrown);
    }

    public function testWithdrawCustomerInformationNeeded(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(403, ['X-Foo' => 'Bar'], $this->requestCustomerInformationNeeded())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("XLM", $query_array["asset_code"]);
            $this->assertEquals("crypto", $query_array["type"]);
            $this->assertEquals($this->accountId, $query_array["account"]);
            $this->assertEquals("120.0", $query_array["amount"]);
            $this->assertEquals("GCTTGO5ABSTHABXWL2FMHPZ2XFOZDXJYJN5CKFRKXMPAAWZW3Y3JZ3JK", $query_array["dest"]);
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);
        $request = new WithdrawRequest();
        $request->jwt = $this->jwtToken;
        $request->assetCode = "XLM";
        $request->type = "crypto";
        $request->dest = "GCTTGO5ABSTHABXWL2FMHPZ2XFOZDXJYJN5CKFRKXMPAAWZW3Y3JZ3JK";
        $request->account = $this->accountId;
        $request->amount = "120.0";
        $thrown = false;
        try {
            $response = $transferService->withdraw($request);
        } catch (CustomerInformationNeededException $e) {
            $thrown = true;
            $response = $e->getResponse();
            $this->assertContains("tax_id", $response->getFields());
        }
        $this->assertTrue($thrown);
    }

    public function testDepositCustomerInformationStatus(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(403, ['X-Foo' => 'Bar'], $this->requestCustomerInformationStatus())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("MXN", $query_array["asset_code"]);
            $this->assertEquals($this->accountId, $query_array["account"]);
            $this->assertEquals("120.0", $query_array["amount"]);
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);
        $request = new DepositRequest();
        $request->jwt = $this->jwtToken;
        $request->assetCode = "MXN";
        $request->account = $this->accountId;
        $request->amount = "120.0";
        $thrown = false;
        try {
            $response = $transferService->deposit($request);
        } catch (CustomerInformationStatusException $e) {
            $thrown = true;
            $response = $e->getResponse();
            $this->assertEquals("denied", $response->getStatus());
            $this->assertEquals("https://api.example.com/kycstatus?account=GACW7NONV43MZIFHCOKCQJAKSJSISSICFVUJ2C6EZIW5773OU3HD64VI", $response->getMoreInfoUrl());
        }
        $this->assertTrue($thrown);
    }

    public function testWithdrawCustomerInformationStatus(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(403, ['X-Foo' => 'Bar'], $this->requestCustomerInformationStatus())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("XLM", $query_array["asset_code"]);
            $this->assertEquals("crypto", $query_array["type"]);
            $this->assertEquals($this->accountId, $query_array["account"]);
            $this->assertEquals("120.0", $query_array["amount"]);
            $this->assertEquals("GCTTGO5ABSTHABXWL2FMHPZ2XFOZDXJYJN5CKFRKXMPAAWZW3Y3JZ3JK", $query_array["dest"]);
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);
        $request = new WithdrawRequest();
        $request->jwt = $this->jwtToken;
        $request->assetCode = "XLM";
        $request->type = "crypto";
        $request->dest = "GCTTGO5ABSTHABXWL2FMHPZ2XFOZDXJYJN5CKFRKXMPAAWZW3Y3JZ3JK";
        $request->account = $this->accountId;
        $request->amount = "120.0";
        $thrown = false;
        try {
            $response = $transferService->withdraw($request);
        } catch (CustomerInformationStatusException $e) {
            $thrown = true;
            $response = $e->getResponse();
            $this->assertEquals("denied", $response->getStatus());
            $this->assertEquals("https://api.example.com/kycstatus?account=GACW7NONV43MZIFHCOKCQJAKSJSISSICFVUJ2C6EZIW5773OU3HD64VI", $response->getMoreInfoUrl());
        }
        $this->assertTrue($thrown);
    }

    public function testGetTransactions(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestTransactions())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("XLM", $query_array["asset_code"]);
            $this->assertEquals("GCTTGO5ABSTHABXWL2FMHPZ2XFOZDXJYJN5CKFRKXMPAAWZW3Y3JZ3JK", $query_array["account"]);
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);
        $request = new AnchorTransactionsRequest();
        $request->jwt = $this->jwtToken;
        $request->assetCode = "XLM";
        $request->account = "GCTTGO5ABSTHABXWL2FMHPZ2XFOZDXJYJN5CKFRKXMPAAWZW3Y3JZ3JK";
        $response = $transferService->transactions($request);
        $this->assertNotNull($response);
        $this->assertCount(3, $response->getTransactions());
        $this->assertEquals("82fhs729f63dh0v4", $response->getTransactions()[0]->getId());
        $this->assertEquals("deposit", $response->getTransactions()[0]->getKind());
        $this->assertEquals("pending_external", $response->getTransactions()[0]->getStatus());
        $this->assertEquals(3600, $response->getTransactions()[0]->getStatusEta());
        $this->assertEquals("2dd16cb409513026fbe7defc0c6f826c2d2c65c3da993f747d09bf7dafd31093", $response->getTransactions()[0]->getExternalTransactionId());
        $this->assertEquals("18.34", $response->getTransactions()[0]->getAmountIn());
        $this->assertEquals("18.24", $response->getTransactions()[0]->getAmountOut());
        $this->assertEquals("0.1", $response->getTransactions()[0]->getAmountFee());
        $this->assertEquals("2017-03-20T17:05:32Z", $response->getTransactions()[0]->getStartedAt());
        $last = $response->getTransactions()[count($response->getTransactions()) -1];
        $this->assertEquals("We were unable to send funds to the provided bank account. Bank error: 'Account does not exist'. Please provide the correct bank account address.",
            $last->getRequiredInfoMessage());
        $this->assertEquals("your bank account number", $last->getRequiredInfoUpdates()["dest"]->getDescription());
    }

    public function testGetTransaction(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestTransaction())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("82fhs729f63dh0v4", $query_array["id"]);
            $this->assertEquals("17a670bc424ff5ce3b386dbfaae9990b66a2a37b4fbe51547e8794962a3f9e6a", $query_array["stellar_transaction_id"]);
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);
        $request = new AnchorTransactionRequest();
        $request->jwt = $this->jwtToken;
        $request->id = "82fhs729f63dh0v4";
        $request->stallarTransactionId = "17a670bc424ff5ce3b386dbfaae9990b66a2a37b4fbe51547e8794962a3f9e6a";
        $response = $transferService->transaction($request);
        $this->assertNotNull($response);
        $this->assertEquals("82fhs729f63dh0v4", $response->getTransaction()->getId());
        $this->assertEquals("deposit", $response->getTransaction()->getKind());
        $this->assertEquals("pending_external", $response->getTransaction()->getStatus());
        $this->assertEquals(3600, $response->getTransaction()->getStatusEta());
        $this->assertEquals("2dd16cb409513026fbe7defc0c6f826c2d2c65c3da993f747d09bf7dafd31093", $response->getTransaction()->getExternalTransactionId());
        $this->assertEquals("18.34", $response->getTransaction()->getAmountIn());
        $this->assertEquals("18.24", $response->getTransaction()->getAmountOut());
        $this->assertEquals("0.1", $response->getTransaction()->getAmountFee());
        $this->assertEquals("2017-03-20T17:05:32Z", $response->getTransaction()->getStartedAt());
    }

    public function testPatchTransaction(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestTransaction())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $body = $request->getBody()->__toString();
            $jsonData = json_decode($body, true);
            $this->assertTrue(str_contains("12345678901234", $jsonData["dest"]));
            $this->assertTrue(str_contains("021000021", $jsonData["dest_extra"]));
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);
        $request = new PatchTransactionRequest();
        $request->jwt = $this->jwtToken;
        $request->id = "82fhs729f63dh0v4";
        $request->fields = array();
        $request->fields["dest"] = "12345678901234";
        $request->fields["dest_extra"] = "021000021";
        $response = $transferService->patchTransaction($request);
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}