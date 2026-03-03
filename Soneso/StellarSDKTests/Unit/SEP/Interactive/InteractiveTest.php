<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\SEP\Interactive;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\RequestErrorException;
use Soneso\StellarSDK\SEP\Interactive\SEP24AuthenticationRequiredException;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositAsset;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositRequest;
use Soneso\StellarSDK\SEP\Interactive\SEP24FeeRequest;
use Soneso\StellarSDK\SEP\Interactive\SEP24FeeResponse;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionNotFoundException;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionRequest;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionsRequest;
use Soneso\StellarSDK\SEP\Interactive\SEP24WithdrawAsset;
use Soneso\StellarSDK\SEP\Interactive\SEP24WithdrawRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\FinancialAccountKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\OrganizationKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
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

class InteractiveTest extends TestCase
{
    private string $serviceAddress = "https://api.stellar.org/transfer-sep24/";
    private string $jwtToken = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJHQTZVSVhYUEVXWUZJTE5VSVdBQzM3WTRRUEVaTVFWREpIREtWV0ZaSjJLQ1dVQklVNUlYWk5EQSIsImp0aSI6IjE0NGQzNjdiY2IwZTcyY2FiZmRiZGU2MGVhZTBhZDczM2NjNjVkMmE2NTg3MDgzZGFiM2Q2MTZmODg1MTkwMjQiLCJpc3MiOiJodHRwczovL2ZsYXBweS1iaXJkLWRhcHAuZmlyZWJhc2VhcHAuY29tLyIsImlhdCI6MTUzNDI1Nzk5NCwiZXhwIjoxNTM0MzQ0Mzk0fQ.8nbB83Z6vGBgC1X9r3N6oQCFTBzDiITAfCJasRft0z0";

    private function requestInfo() : string {
        return "{  \"deposit\": {    \"USD\": {      \"enabled\": true,      \"fee_fixed\": 5,      \"fee_percent\": 1,      \"min_amount\": 0.1,      \"max_amount\": 1000    },    \"ETH\": {      \"enabled\": true,      \"fee_fixed\": 0.002,      \"fee_percent\": 0    },    \"native\": {      \"enabled\": true,      \"fee_fixed\": 0.00001,      \"fee_percent\": 0    }  },  \"withdraw\": {    \"USD\": {      \"enabled\": true,      \"fee_minimum\": 5,      \"fee_percent\": 0.5,      \"min_amount\": 0.1,      \"max_amount\": 1000    },    \"ETH\": {      \"enabled\": false    },    \"native\": {      \"enabled\": true    }  },  \"fee\": {    \"enabled\": false  },  \"features\": {    \"account_creation\": true,    \"claimable_balances\": true  }}";
    }

    private function requestFee() : string {
        return "{\"fee\": 0.013}";
    }

    private function requestInteractive() : string {
        return "{  \"type\": \"completed\",  \"url\": \"https://api.example.com/kycflow?account=GACW7NONV43MZIFHCOKCQJAKSJSISSICFVUJ2C6EZIW5773OU3HD64VI\",  \"id\": \"82fhs729f63dh0v4\"}";
    }

    private function requestTransactions() : string {
        return "{  \"transactions\": [    {      \"id\": \"82fhs729f63dh0v4\",      \"kind\": \"deposit\",      \"status\": \"pending_external\",      \"status_eta\": 3600,      \"external_transaction_id\": \"2dd16cb409513026fbe7defc0c6f826c2d2c65c3da993f747d09bf7dafd31093\",      \"more_info_url\": \"https://youranchor.com/tx/242523523\",      \"amount_in\": \"18.34\",      \"amount_out\": \"18.24\",      \"amount_fee\": \"0.1\",      \"started_at\": \"2017-03-20T17:05:32Z\",      \"claimable_balance_id\": null    },    {      \"id\": \"82fhs729f63dh0v4\",      \"kind\": \"withdrawal\",      \"status\": \"completed\",      \"amount_in\": \"510\",      \"amount_out\": \"490\",      \"amount_fee\": \"5\",      \"started_at\": \"2017-03-20T17:00:02Z\",      \"completed_at\": \"2017-03-20T17:09:58Z\",      \"updated_at\": \"2017-03-20T17:09:58Z\",      \"more_info_url\": \"https://youranchor.com/tx/242523523\",      \"stellar_transaction_id\": \"17a670bc424ff5ce3b386dbfaae9990b66a2a37b4fbe51547e8794962a3f9e6a\",      \"external_transaction_id\": \"1941491\",      \"withdraw_anchor_account\": \"GBANAGOAXH5ONSBI2I6I5LHP2TCRHWMZIAMGUQH2TNKQNCOGJ7GC3ZOL\",      \"withdraw_memo\": \"186384\",      \"withdraw_memo_type\": \"id\",      \"refunds\": {        \"amount_refunded\": \"10\",        \"amount_fee\": \"5\",        \"payments\": [          {            \"id\": \"b9d0b2292c4e09e8eb22d036171491e87b8d2086bf8b265874c8d182cb9c9020\",            \"id_type\": \"stellar\",            \"amount\": \"10\",            \"fee\": \"5\"          }        ]      }    },    {      \"id\": \"92fhs729f63dh0v3\",      \"kind\": \"deposit\",      \"status\": \"completed\",      \"amount_in\": \"510\",      \"amount_out\": \"490\",      \"amount_fee\": \"5\",      \"started_at\": \"2017-03-20T17:00:02Z\",      \"completed_at\": \"2017-03-20T17:09:58Z\",      \"updated_at\": \"2017-03-20T17:09:58Z\",      \"more_info_url\": \"https://youranchor.com/tx/242523526\",      \"stellar_transaction_id\": \"17a670bc424ff5ce3b386dbfaae9990b66a2a37b4fbe51547e8794962a3f9e6a\",      \"external_transaction_id\": \"1947101\",      \"refunds\": {        \"amount_refunded\": \"10\",        \"amount_fee\": \"5\",        \"payments\": [          {            \"id\": \"1937103\",            \"id_type\": \"external\",            \"amount\": \"10\",            \"fee\": \"5\"          }        ]      }    },    {      \"id\": \"92fhs729f63dh0v3\",      \"kind\": \"deposit\",      \"status\": \"pending_anchor\",      \"amount_in\": \"510\",      \"amount_out\": \"490\",      \"amount_fee\": \"5\",      \"started_at\": \"2017-03-20T17:00:02Z\",      \"updated_at\": \"2017-03-20T17:05:58Z\",      \"more_info_url\": \"https://youranchor.com/tx/242523526\",      \"stellar_transaction_id\": \"17a670bc424ff5ce3b386dbfaae9990b66a2a37b4fbe51547e8794962a3f9e6a\",      \"external_transaction_id\": \"1947101\",      \"refunds\": {        \"amount_refunded\": \"10\",        \"amount_fee\": \"5\",        \"payments\": [          {            \"id\": \"1937103\",            \"id_type\": \"external\",            \"amount\": \"10\",            \"fee\": \"5\"          }        ]      }    }  ]}";
    }

    private function requestTransaction() : string {
        return "{  \"transaction\": {      \"id\": \"82fhs729f63dh0v4\",      \"kind\": \"withdrawal\",      \"status\": \"completed\",      \"amount_in\": \"510\",      \"amount_out\": \"490\",      \"amount_fee\": \"5\",      \"started_at\": \"2017-03-20T17:00:02Z\",      \"completed_at\": \"2017-03-20T17:09:58Z\",      \"updated_at\": \"2017-03-20T17:09:58Z\",      \"more_info_url\": \"https://youranchor.com/tx/242523523\",      \"stellar_transaction_id\": \"17a670bc424ff5ce3b386dbfaae9990b66a2a37b4fbe51547e8794962a3f9e6a\",      \"external_transaction_id\": \"1941491\",      \"withdraw_anchor_account\": \"GBANAGOAXH5ONSBI2I6I5LHP2TCRHWMZIAMGUQH2TNKQNCOGJ7GC3ZOL\",      \"withdraw_memo\": \"186384\",      \"withdraw_memo_type\": \"id\",      \"refunds\": {        \"amount_refunded\": \"10\",        \"amount_fee\": \"5\",        \"payments\": [          {            \"id\": \"b9d0b2292c4e09e8eb22d036171491e87b8d2086bf8b265874c8d182cb9c9020\",            \"id_type\": \"stellar\",            \"amount\": \"10\",            \"fee\": \"5\"          }        ]      }    }}";
    }

    private function requestEmptyTransaction() : string {
        return "{  \"transactions\": []}";
    }


    public function testInfo(): void {
        $transferService = new InteractiveService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestInfo())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("en", $query_array["lang"]);
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);
        $response = $transferService->info("en");
        $this->assertNotNull($response);
        $this->assertCount(3, $response->getDepositAssets());

        $depositAssetUSD = $response->getDepositAssets()["USD"];
        $this->assertNotNull($depositAssetUSD);
        $this->assertTrue($depositAssetUSD instanceof SEP24DepositAsset);
        if ($depositAssetUSD instanceof SEP24DepositAsset) {
            $this->assertTrue($depositAssetUSD->enabled);
            $this->assertEquals(5.0, $depositAssetUSD->feeFixed);
            $this->assertEquals(1.0, $depositAssetUSD->feePercent);
            $this->assertNull($depositAssetUSD->feeMinimum);
            $this->assertEquals(0.1, $depositAssetUSD->minAmount);
            $this->assertEquals(1000.0, $depositAssetUSD->maxAmount);
        }

        $depositAssetETH = $response->getDepositAssets()["ETH"];
        $this->assertNotNull($depositAssetETH);
        $this->assertTrue($depositAssetETH instanceof SEP24DepositAsset);
        if ($depositAssetUSD instanceof SEP24DepositAsset) {
            $this->assertTrue($depositAssetETH->enabled);
            $this->assertEquals(0.002, $depositAssetETH->feeFixed);
            $this->assertEquals(0.0, $depositAssetETH->feePercent);
            $this->assertNull($depositAssetETH->feeMinimum);
            $this->assertNull($depositAssetETH->minAmount);
            $this->assertNull($depositAssetETH->maxAmount);
        }

        $depositAssetNative = $response->getDepositAssets()["native"];
        $this->assertNotNull($depositAssetNative);
        $this->assertTrue($depositAssetNative instanceof SEP24DepositAsset);
        if ($depositAssetNative instanceof SEP24DepositAsset) {
            $this->assertTrue($depositAssetNative->enabled);
            $this->assertEquals(0.00001, $depositAssetNative->feeFixed);
            $this->assertEquals(0.0, $depositAssetNative->feePercent);
            $this->assertNull($depositAssetNative->feeMinimum);
            $this->assertNull($depositAssetNative->minAmount);
            $this->assertNull($depositAssetNative->maxAmount);
        }


        $withdrawAssetUSD = $response->getWithdrawAssets()["USD"];
        $this->assertNotNull($withdrawAssetUSD);
        $this->assertTrue($withdrawAssetUSD instanceof SEP24WithdrawAsset);
        if ($withdrawAssetUSD instanceof SEP24WithdrawAsset) {
            $this->assertTrue($withdrawAssetUSD->enabled);
            $this->assertEquals(5.0, $withdrawAssetUSD->feeMinimum);
            $this->assertEquals(0.5, $withdrawAssetUSD->feePercent);
            $this->assertNull($withdrawAssetUSD->feeFixed);
            $this->assertEquals(0.1, $withdrawAssetUSD->minAmount);
            $this->assertEquals(1000.0, $withdrawAssetUSD->maxAmount);
        }

        $withdrawAssetETH = $response->getWithdrawAssets()["ETH"];
        $this->assertNotNull($withdrawAssetETH);
        $this->assertTrue($withdrawAssetETH instanceof SEP24WithdrawAsset);
        if ($withdrawAssetETH instanceof SEP24WithdrawAsset) {
            $this->assertFalse($withdrawAssetETH->enabled);
        }

        $withdrawAssetNative = $response->getWithdrawAssets()["native"];
        $this->assertNotNull($withdrawAssetNative);
        $this->assertTrue($withdrawAssetNative instanceof SEP24WithdrawAsset);
        if ($withdrawAssetNative instanceof SEP24WithdrawAsset) {
            $this->assertTrue($withdrawAssetNative->enabled);
        }

        $this->assertFalse($response->feeEndpointInfo->enabled);
        $this->assertTrue($response->featureFlags->accountCreation);
        $this->assertTrue($response->featureFlags->claimableBalances);
    }

    public function testFee(): void
    {
        $transferService = new InteractiveService($this->serviceAddress);
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

        $request = new SEP24FeeRequest("deposit", "ETH", 2034.09, "SEPA", $this->jwtToken);
        $response = $transferService->fee($request);
        $this->assertNotNull($response);
        $this->assertEquals(0.013, $response->getFee());
    }

    public function testDepositSEP24(): void
    {
        $transferService = new InteractiveService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestInteractive())
        ]);

        $request = new SEP24DepositRequest();
        $request->jwt = $this->jwtToken;
        $request->assetCode = "USD";

        $kycFields = new StandardKYCFields();
        $naturalPersonKycFields = new NaturalPersonKYCFields();
        $naturalPersonKycFields->firstName = 'John';
        $naturalPersonKycFields->lastName ='Doe';
        $financialAccountFields = new FinancialAccountKYCFields();
        $financialAccountFields->bankAccountNumber = '1982937837864';
        $naturalPersonKycFields->financialAccountKYCFields = $financialAccountFields;
        $kycFields->naturalPersonKYCFields = $naturalPersonKycFields;

        $orgKycFields = new OrganizationKYCFields();
        $orgKycFields->name = 'My LLC';
        $orgFinancialFields = new FinancialAccountKYCFields();
        $orgFinancialFields->clabeNumber = '9999999';
        $orgKycFields->financialAccountKYCFields = $orgFinancialFields;
        $kycFields->organizationKYCFields = $orgKycFields;

        $request->kycFields = $kycFields;

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("POST", $request->getMethod());
            $body = $request->getBody()->__toString();
            $this->assertTrue(str_contains($body, "asset_code"));
            $this->assertTrue(str_contains($body, "USD"));
            $this->assertTrue(str_contains($body, 'John'));
            $this->assertTrue(str_contains($body, 'Doe'));
            $this->assertTrue(str_contains($body, '1982937837864'));
            $this->assertTrue(str_contains($body, 'My LLC'));
            $this->assertTrue(str_contains($body, '9999999'));
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);

        $response = $transferService->deposit($request);
        $this->assertNotNull($response);
        $this->assertEquals("82fhs729f63dh0v4", $response->id);
        $this->assertEquals("completed", $response->type);
        $this->assertEquals("https://api.example.com/kycflow?account=GACW7NONV43MZIFHCOKCQJAKSJSISSICFVUJ2C6EZIW5773OU3HD64VI", $response->url);
    }

    public function testWithdrawSEP24(): void
    {
        $transferService = new InteractiveService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestInteractive())
        ]);

        $request = new SEP24WithdrawRequest();
        $request->jwt = $this->jwtToken;
        $request->assetCode = "USD";

        $kycFields = new StandardKYCFields();
        $naturalPersonKycFields = new NaturalPersonKYCFields();
        $naturalPersonKycFields->firstName = 'John';
        $naturalPersonKycFields->lastName ='Doe';
        $financialAccountFields = new FinancialAccountKYCFields();
        $financialAccountFields->bankAccountNumber = '1982937837864';
        $naturalPersonKycFields->financialAccountKYCFields = $financialAccountFields;
        $kycFields->naturalPersonKYCFields = $naturalPersonKycFields;

        $orgKycFields = new OrganizationKYCFields();
        $orgKycFields->name = 'My LLC';
        $orgFinancialFields = new FinancialAccountKYCFields();
        $orgFinancialFields->clabeNumber = '9999999';
        $orgKycFields->financialAccountKYCFields = $orgFinancialFields;
        $kycFields->organizationKYCFields = $orgKycFields;

        $request->kycFields = $kycFields;

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("POST", $request->getMethod());
            $body = $request->getBody()->__toString();
            $this->assertTrue(str_contains($body, "asset_code"));
            $this->assertTrue(str_contains($body, "USD"));
            $this->assertTrue(str_contains($body, 'John'));
            $this->assertTrue(str_contains($body, 'Doe'));
            $this->assertTrue(str_contains($body, '1982937837864'));
            $this->assertTrue(str_contains($body, 'My LLC'));
            $this->assertTrue(str_contains($body, '9999999'));
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);
        $response = $transferService->withdraw($request);
        $this->assertNotNull($response);
        $this->assertEquals("82fhs729f63dh0v4", $response->id);
        $this->assertEquals("completed", $response->type);
        $this->assertEquals("https://api.example.com/kycflow?account=GACW7NONV43MZIFHCOKCQJAKSJSISSICFVUJ2C6EZIW5773OU3HD64VI", $response->url);
    }

    public function testGetTransactions(): void
    {
        $transferService = new InteractiveService($this->serviceAddress);
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
            $this->assertEquals("ETH", $query_array["asset_code"]);
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);
        $request = new SEP24TransactionsRequest();
        $request->jwt = $this->jwtToken;
        $request->assetCode = "ETH";
        $response = $transferService->transactions($request);
        $this->assertNotNull($response);
        $this->assertCount(4, $response->getTransactions());


        $this->assertEquals("82fhs729f63dh0v4", $response->getTransactions()[0]->getId());
        $this->assertEquals("deposit", $response->getTransactions()[0]->getKind());
        $this->assertEquals("pending_external", $response->getTransactions()[0]->getStatus());
        $this->assertEquals(3600, $response->getTransactions()[0]->getStatusEta());
        $this->assertEquals("2dd16cb409513026fbe7defc0c6f826c2d2c65c3da993f747d09bf7dafd31093", $response->getTransactions()[0]->getExternalTransactionId());
        $this->assertEquals("18.34", $response->getTransactions()[0]->getAmountIn());
        $this->assertEquals("18.24", $response->getTransactions()[0]->getAmountOut());
        $this->assertEquals("0.1", $response->getTransactions()[0]->getAmountFee());
        $this->assertEquals("2017-03-20T17:05:32Z", $response->getTransactions()[0]->getStartedAt());
        $this->assertNull($response->getTransactions()[0]->getClaimableBalanceId());

    }

    public function testGetTransaction(): void
    {
        $transferService = new InteractiveService($this->serviceAddress);
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
            $this->assertEquals("17a670bc424ff5ce3b386dbfaae9990b66a2a37b4fbe51547e8794962a3f9e6a", $query_array["stellar_transaction_id"]);
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);
        $request = new SEP24TransactionRequest();
        $request->jwt = $this->jwtToken;
        $request->stellarTransactionId = "17a670bc424ff5ce3b386dbfaae9990b66a2a37b4fbe51547e8794962a3f9e6a";
        $response = $transferService->transaction($request);
        $this->assertNotNull($response);
        $this->assertEquals("82fhs729f63dh0v4", $response->getTransaction()->getId());
        $this->assertEquals("withdrawal", $response->getTransaction()->getKind());
        $this->assertEquals("completed", $response->getTransaction()->getStatus());
        $this->assertEquals("510", $response->getTransaction()->getAmountIn());
        $this->assertEquals("490", $response->getTransaction()->getAmountOut());
        $this->assertEquals("5", $response->getTransaction()->getAmountFee());
        $this->assertEquals("2017-03-20T17:00:02Z", $response->getTransaction()->getStartedAt());
        $this->assertEquals("2017-03-20T17:09:58Z", $response->getTransaction()->getCompletedAt());
        $this->assertEquals("2017-03-20T17:09:58Z", $response->getTransaction()->getUpdatedAt());
        $this->assertEquals("https://youranchor.com/tx/242523523", $response->getTransaction()->getMoreInfoUrl());
        $this->assertEquals("17a670bc424ff5ce3b386dbfaae9990b66a2a37b4fbe51547e8794962a3f9e6a", $response->getTransaction()->getStellarTransactionId());
        $this->assertEquals("1941491", $response->getTransaction()->getExternalTransactionId());
        $this->assertEquals("GBANAGOAXH5ONSBI2I6I5LHP2TCRHWMZIAMGUQH2TNKQNCOGJ7GC3ZOL", $response->getTransaction()->getWithdrawAnchorAccount());
        $this->assertEquals("186384", $response->getTransaction()->getWithdrawMemo());
        $this->assertEquals("id", $response->getTransaction()->getWithdrawMemoType());

        $this->assertEquals("10", $response->getTransaction()->getRefunds()->amountRefunded);
        $this->assertEquals("5", $response->getTransaction()->getRefunds()->amountFee);

        $refundPayments = $response->getTransaction()->getRefunds()->getPayments();
        $this->assertCount(1, $refundPayments);

        $refundPayment = $refundPayments[0];
        $this->assertEquals("b9d0b2292c4e09e8eb22d036171491e87b8d2086bf8b265874c8d182cb9c9020", $refundPayment->getId());
        $this->assertEquals("stellar", $refundPayment->getIdType());
        $this->assertEquals("10", $refundPayment->getAmount());
        $this->assertEquals("5", $refundPayment->getFee());
    }

    public function testGetEmptyTransactions(): void
    {
        $transferService = new InteractiveService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestEmptyTransaction())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("ETH", $query_array["asset_code"]);
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);
        $request = new SEP24TransactionsRequest();
        $request->jwt = $this->jwtToken;
        $request->assetCode = "ETH";
        $response = $transferService->transactions($request);
        $this->assertNotNull($response);
        $this->assertCount(0, $response->getTransactions());
    }

    public function testNotFoundTransaction(): void
    {
        $transferService = new InteractiveService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(404, ['X-Foo' => 'Bar'], "{\"error\": \"not found\"}")
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("17a670bc424ff5ce3b386dbfaae9990b66a2a37b4fbe51547e8794962a3f9e6a", $query_array["stellar_transaction_id"]);
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);
        $request = new SEP24TransactionRequest();
        $request->jwt = $this->jwtToken;
        $request->stellarTransactionId = "17a670bc424ff5ce3b386dbfaae9990b66a2a37b4fbe51547e8794962a3f9e6a";

        $thrown = false;
        try {
            $response = $transferService->transaction($request);
        } catch (SEP24TransactionNotFoundException $e) {
            $thrown = true;
        }

        $this->assertTrue($thrown);
    }

    public function testForbidden(): void
    {
        $transferService = new InteractiveService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(403, ['X-Foo' => 'Bar'], "{\"type\": \"authentication_required\"}")
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("deposit", $query_array["operation"]);
            $this->assertEquals("SEPA", $query_array["type"]);
            $this->assertEquals("ETH", $query_array["asset_code"]);
            $this->assertEquals(2034.09, $query_array["amount"]);
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);

        $request = new SEP24FeeRequest("deposit", "ETH", 2034.09, "SEPA");


        $thrown = false;
        try {
            $response = $transferService->fee($request);
        } catch (SEP24AuthenticationRequiredException $e) {
            $thrown = true;
        }

        $this->assertTrue($thrown);
    }

    public function testRequestError(): void
    {
        $transferService = new InteractiveService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(400, ['X-Foo' => 'Bar'], "{\"error\": \"This anchor doesn't support the given currency code: ETH\"}")
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("deposit", $query_array["operation"]);
            $this->assertEquals("SEPA", $query_array["type"]);
            $this->assertEquals("ETH", $query_array["asset_code"]);
            $this->assertEquals(2034.09, $query_array["amount"]);
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);

        $request = new SEP24FeeRequest("deposit", "ETH", 2034.09, "SEPA");


        $thrown = false;
        try {
            $response = $transferService->fee($request);
        } catch (RequestErrorException $e) {
            $thrown = true;
        }

        $this->assertTrue($thrown);
    }
}