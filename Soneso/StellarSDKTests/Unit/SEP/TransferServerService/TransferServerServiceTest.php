<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\SEP\TransferServerService;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Soneso\StellarSDK\SEP\StandardKYCFields\FinancialAccountKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\OrganizationKYCFields;
use Soneso\StellarSDK\SEP\TransferServerService\AnchorField;
use Soneso\StellarSDK\SEP\TransferServerService\AnchorTransactionRequest;
use Soneso\StellarSDK\SEP\TransferServerService\AnchorTransactionsRequest;
use Soneso\StellarSDK\SEP\TransferServerService\CustomerInformationNeededException;
use Soneso\StellarSDK\SEP\TransferServerService\CustomerInformationStatusException;
use Soneso\StellarSDK\SEP\TransferServerService\DepositAsset;
use Soneso\StellarSDK\SEP\TransferServerService\DepositExchangeAsset;
use Soneso\StellarSDK\SEP\TransferServerService\DepositExchangeRequest;
use Soneso\StellarSDK\SEP\TransferServerService\DepositRequest;
use Soneso\StellarSDK\SEP\TransferServerService\FeeRequest;
use Soneso\StellarSDK\SEP\TransferServerService\PatchTransactionRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;
use Soneso\StellarSDK\SEP\TransferServerService\WithdrawAsset;
use Soneso\StellarSDK\SEP\TransferServerService\WithdrawExchangeRequest;
use Soneso\StellarSDK\SEP\TransferServerService\WithdrawRequest;
use function PHPUnit\Framework\assertNotNull;

class TransferServerServiceTest extends TestCase
{
    private string $serviceAddress = "http://api.stellar.org/transfer";
    private string $accountId = "GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP";
    private string $jwtToken = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJHQTZVSVhYUEVXWUZJTE5VSVdBQzM3WTRRUEVaTVFWREpIREtWV0ZaSjJLQ1dVQklVNUlYWk5EQSIsImp0aSI6IjE0NGQzNjdiY2IwZTcyY2FiZmRiZGU2MGVhZTBhZDczM2NjNjVkMmE2NTg3MDgzZGFiM2Q2MTZmODg1MTkwMjQiLCJpc3MiOiJodHRwczovL2ZsYXBweS1iaXJkLWRhcHAuZmlyZWJhc2VhcHAuY29tLyIsImlhdCI6MTUzNDI1Nzk5NCwiZXhwIjoxNTM0MzQ0Mzk0fQ.8nbB83Z6vGBgC1X9r3N6oQCFTBzDiITAfCJasRft0z0";

    private function requestInfo() : string {
        return "{  \"deposit\": {    \"USD\": {      \"enabled\": true,      \"authentication_required\": true,      \"min_amount\": 0.1,      \"max_amount\": 1000,      \"fields\": {        \"email_address\" : {          \"description\": \"your email address for transaction status updates\",          \"optional\": true        },        \"amount\" : {          \"description\": \"amount in USD that you plan to deposit\"        },        \"country_code\": {          \"description\": \"The ISO 3166-1 alpha-3 code of the user's current address\",          \"choices\": [\"USA\", \"PRI\"]        },        \"type\" : {          \"description\": \"type of deposit to make\",          \"choices\": [\"SEPA\", \"SWIFT\", \"cash\"]        }      }    },    \"ETH\": {      \"enabled\": true,      \"authentication_required\": false    }  },  \"deposit-exchange\": {    \"USD\": {      \"authentication_required\": true,      \"fields\": {        \"email_address\" : {          \"description\": \"your email address for transaction status updates\",          \"optional\": true        },        \"amount\" : {          \"description\": \"amount in USD that you plan to deposit\"        },        \"country_code\": {          \"description\": \"The ISO 3166-1 alpha-3 code of the user's current address\",          \"choices\": [\"USA\", \"PRI\"]        },        \"type\" : {          \"description\": \"type of deposit to make\",          \"choices\": [\"SEPA\", \"SWIFT\", \"cash\"]        }      }    }  },  \"withdraw\": {    \"USD\": {      \"enabled\": true,      \"authentication_required\": true,      \"min_amount\": 0.1,      \"max_amount\": 1000,      \"types\": {        \"bank_account\": {          \"fields\": {              \"dest\": {\"description\": \"your bank account number\" },              \"dest_extra\": { \"description\": \"your routing number\" },              \"bank_branch\": { \"description\": \"address of your bank branch\" },              \"phone_number\": { \"description\": \"your phone number in case there's an issue\" },              \"country_code\": {                \"description\": \"The ISO 3166-1 alpha-3 code of the user's current address\",                \"choices\": [\"USA\", \"PRI\"]              }          }        },        \"cash\": {          \"fields\": {            \"dest\": {              \"description\": \"your email address. Your cashout PIN will be sent here. If not provided, your account's default email will be used\",              \"optional\": true            }          }        }      }    },    \"ETH\": {      \"enabled\": false    }  },  \"withdraw-exchange\": {    \"USD\": {      \"authentication_required\": true,      \"min_amount\": 0.1,      \"max_amount\": 1000,      \"types\": {        \"bank_account\": {          \"fields\": {              \"dest\": {\"description\": \"your bank account number\" },              \"dest_extra\": { \"description\": \"your routing number\" },              \"bank_branch\": { \"description\": \"address of your bank branch\" },              \"phone_number\": { \"description\": \"your phone number in case there's an issue\" },              \"country_code\": {                \"description\": \"The ISO 3166-1 alpha-3 code of the user's current address\",                \"choices\": [\"USA\", \"PRI\"]              }          }        },        \"cash\": {          \"fields\": {            \"dest\": {              \"description\": \"your email address. Your cashout PIN will be sent here. If not provided, your account's default email will be used\",              \"optional\": true            }          }        }      }    }  },  \"fee\": {    \"enabled\": false,    \"description\": \"Fees vary from 3 to 7 percent based on the the assets transacted and method by which funds are delivered to or collected by the anchor.\"  },  \"transactions\": {    \"enabled\": true,    \"authentication_required\": true  },  \"transaction\": {    \"enabled\": false,    \"authentication_required\": true  },  \"features\": {    \"account_creation\": true,    \"claimable_balances\": true  }}";
    }

    private function requestFee() : string {
        return "{\"fee\": 0.013}";
    }

    private function requestBankDeposit() : string {
        return "{  \"id\": \"9421871e-0623-4356-b7b5-5996da122f3e\",  \"instructions\": {    \"organization.bank_number\": {      \"value\": \"121122676\",      \"description\": \"US bank routing number\"    },    \"organization.bank_account_number\": {      \"value\": \"13719713158835300\",      \"description\": \"US bank account number\"    }  },  \"how\": \"Make a payment to Bank: 121122676 Account: 13719713158835300\"}";
    }

    private function requestBTCDeposit() : string {
        return "{  \"id\": \"9421871e-0623-4356-b7b5-5996da122f3e\",  \"instructions\": {    \"organization.crypto_address\": {      \"value\": \"1Nh7uHdvY6fNwtQtM1G5EZAFPLC33B59rB\",      \"description\": \"Bitcoin address\"    }  },  \"how\": \"Make a payment to Bitcoin address 1Nh7uHdvY6fNwtQtM1G5EZAFPLC33B59rB\",  \"fee_fixed\": 0.0002}";
    }

    private function requestRippleDeposit() : string {
        return "{  \"id\": \"9421871e-0623-4356-b7b5-5996da122f3e\",  \"instructions\": {    \"organization.crypto_address\": {      \"value\": \"rNXEkKCxvfLcM1h4HJkaj2FtmYuAWrHGbf\",      \"description\": \"Ripple address\"    },    \"organization.crypto_memo\": {      \"value\": \"88\",      \"description\": \"Ripple tag\"    }  },  \"how\": \"Make a payment to Ripple address rNXEkKCxvfLcM1h4HJkaj2FtmYuAWrHGbf with tag 88\",  \"eta\": 60,  \"fee_percent\": 0.1,  \"extra_info\": {    \"message\": \"You must include the tag. If the amount is more than 1000 XRP, deposit will take 24h to complete.\"  }}";
    }

    private function requestMXNDeposit() : string {
        return "{  \"id\": \"9421871e-0623-4356-b7b5-5996da122f3e\",  \"instructions\": {    \"organization.clabe_number\": {      \"value\": \"646180111803859359\",      \"description\": \"CLABE number\"    }  },  \"how\": \"Make a payment to Bank: STP Account: 646180111803859359\",  \"eta\": 1800}";
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
        return "{  \"transactions\": [    {      \"id\": \"82fhs729f63dh0v4\",      \"kind\": \"deposit\",      \"status\": \"pending_external\",      \"status_eta\": 3600,      \"external_transaction_id\": \"2dd16cb409513026fbe7defc0c6f826c2d2c65c3da993f747d09bf7dafd31093\",      \"amount_in\": \"18.34\",      \"amount_out\": \"18.24\",      \"amount_fee\": \"0.1\",      \"started_at\": \"2017-03-20T17:05:32Z\"    },    {      \"id\": \"52fys79f63dh3v2\",      \"kind\": \"deposit-exchange\",      \"status\": \"pending_anchor\",      \"status_eta\": 3600,      \"external_transaction_id\": \"2dd16cb409513026fbe7defc0c6f826c2d2c65c3da993f747d09bf7dafd31093\",      \"amount_in\": \"500\",      \"amount_in_asset\": \"iso4217:BRL\",      \"amount_out\": \"100\",      \"amount_out_asset\": \"stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN\",      \"amount_fee\": \"0.1\",      \"amount_fee_asset\": \"iso4217:BRL\",      \"started_at\": \"2021-06-11T17:05:32Z\"    },    {      \"id\": \"82fhs729f63dh0v4\",      \"kind\": \"withdrawal\",      \"status\": \"completed\",      \"amount_in\": \"510\",      \"amount_out\": \"490\",      \"amount_fee\": \"5\",      \"started_at\": \"2017-03-20T17:00:02Z\",      \"completed_at\": \"2017-03-20T17:09:58Z\",      \"stellar_transaction_id\": \"17a670bc424ff5ce3b386dbfaae9990b66a2a37b4fbe51547e8794962a3f9e6a\",      \"external_transaction_id\": \"1238234\",      \"withdraw_anchor_account\": \"GBANAGOAXH5ONSBI2I6I5LHP2TCRHWMZIAMGUQH2TNKQNCOGJ7GC3ZOL\",      \"withdraw_memo\": \"186384\",      \"withdraw_memo_type\": \"id\",      \"refunds\": {        \"amount_refunded\": \"10\",        \"amount_fee\": \"5\",        \"payments\": [          {            \"id\": \"b9d0b2292c4e09e8eb22d036171491e87b8d2086bf8b265874c8d182cb9c9020\",            \"id_type\": \"stellar\",            \"amount\": \"10\",            \"fee\": \"5\"          }        ]      }    },    {      \"id\": \"72fhs729f63dh0v1\",      \"kind\": \"deposit\",      \"status\": \"completed\",      \"amount_in\": \"510\",      \"amount_out\": \"490\",      \"amount_fee\": \"5\",      \"started_at\": \"2017-03-20T17:00:02Z\",      \"completed_at\": \"2017-03-20T17:09:58Z\",      \"stellar_transaction_id\": \"17a670bc424ff5ce3b386dbfaae9990b66a2a37b4fbe51547e8794962a3f9e6a\",      \"external_transaction_id\": \"1238234\",      \"from\": \"AJ3845SAD\",      \"to\": \"GBITQ4YAFKD2372TNAMNHQ4JV5VS3BYKRK4QQR6FOLAR7XAHC3RVGVVJ\",      \"refunds\": {        \"amount_refunded\": \"10\",        \"amount_fee\": \"5\",        \"payments\": [          {            \"id\": \"104201\",            \"id_type\": \"external\",            \"amount\": \"10\",            \"fee\": \"5\"          }        ]      }    },    {      \"id\": \"52fys79f63dh3v1\",      \"kind\": \"withdrawal\",      \"status\": \"pending_transaction_info_update\",      \"amount_in\": \"750.00\",      \"amount_out\": null,      \"amount_fee\": null,      \"started_at\": \"2017-03-20T17:00:02Z\",      \"required_info_message\": \"We were unable to send funds to the provided bank account. Bank error: 'Account does not exist'. Please provide the correct bank account address.\",      \"required_info_updates\": {        \"transaction\": {          \"dest\": {\"description\": \"your bank account number\" },          \"dest_extra\": { \"description\": \"your routing number\" }        }      }    },    {      \"id\": \"52fys79f63dh3v2\",      \"kind\": \"withdrawal-exchange\",      \"status\": \"pending_anchor\",      \"status_eta\": 3600,      \"stellar_transaction_id\": \"17a670bc424ff5ce3b386dbfaae9990b66a2a37b4fbe51547e8794962a3f9e6a\",      \"amount_in\": \"100\",      \"amount_in_asset\": \"stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN\",      \"amount_out\": \"500\",      \"amount_out_asset\": \"iso4217:BRL\",      \"amount_fee\": \"0.1\",      \"amount_fee_asset\": \"stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN\",      \"started_at\": \"2021-06-11T17:05:32Z\"    }  ]}";
    }

    private function requestTransaction() : string {
        return "{  \"transaction\": {    \"id\": \"82fhs729f63dh0v4\",    \"kind\": \"deposit\",    \"status\": \"pending_external\",    \"status_eta\": 3600,    \"external_transaction_id\": \"2dd16cb409513026fbe7defc0c6f826c2d2c65c3da993f747d09bf7dafd31093\",    \"amount_in\": \"18.34\",    \"amount_out\": \"18.24\",    \"amount_fee\": \"0.1\",    \"started_at\": \"2017-03-20T17:05:32Z\"  }}";
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
        $this->assertCount(2, $response->depositAssets);

        $depositAssetUSD = $response->depositAssets["USD"];
        $this->assertNotNull($depositAssetUSD);
        $this->assertTrue($depositAssetUSD instanceof DepositAsset);
        if ($depositAssetUSD instanceof DepositAsset) {
            $this->assertTrue($depositAssetUSD->enabled);
            $this->assertTrue($depositAssetUSD->authenticationRequired);
            $this->assertNull($depositAssetUSD->feeFixed);
            $this->assertNull($depositAssetUSD->feePercent);
            $this->assertEquals(0.1, $depositAssetUSD->minAmount);
            $this->assertEquals(1000.0, $depositAssetUSD->maxAmount);
        }

        $fields = $depositAssetUSD->fields;
        $this->assertNotNull($fields);
        $this->assertCount(4, $fields);
        $emailAddress = $fields["email_address"];
        $this->assertNotNull($emailAddress);
        $this->assertTrue($emailAddress instanceof AnchorField);
        $this->assertEquals("your email address for transaction status updates", $emailAddress->description);
        $this->assertTrue($emailAddress->optional);
        $this->assertTrue(in_array("USA", $fields["country_code"]->choices));
        $this->assertTrue(in_array("SWIFT", $fields["type"]->choices));

        $depositExchangeAssetUSD = $response->depositExchangeAssets["USD"];
        $this->assertNotNull($depositExchangeAssetUSD);
        $this->assertTrue($depositExchangeAssetUSD instanceof DepositExchangeAsset);
        if ($depositExchangeAssetUSD instanceof DepositExchangeAsset) {
            $this->assertFalse($depositExchangeAssetUSD->enabled);
            $this->assertTrue($depositExchangeAssetUSD->authenticationRequired);
        }

        $fields = $depositExchangeAssetUSD->fields;
        $this->assertNotNull($fields);
        $this->assertCount(4, $fields);
        $emailAddress = $fields["email_address"];
        $this->assertNotNull($emailAddress);
        $this->assertTrue($emailAddress instanceof AnchorField);
        $this->assertEquals("your email address for transaction status updates", $emailAddress->description);
        $this->assertTrue($emailAddress->optional);
        $this->assertTrue(in_array("USA", $fields["country_code"]->choices));
        $this->assertTrue(in_array("SWIFT", $fields["type"]->choices));

        $withdrawAssetUSD = $response->withdrawAssets["USD"];
        $this->assertNotNull($withdrawAssetUSD);
        $this->assertTrue($withdrawAssetUSD instanceof WithdrawAsset);
        if ($withdrawAssetUSD instanceof WithdrawAsset) {
            $this->assertTrue($withdrawAssetUSD->enabled);
            $this->assertTrue($withdrawAssetUSD->authenticationRequired);
            $this->assertNull($withdrawAssetUSD->feeFixed);
            $this->assertNull($withdrawAssetUSD->feePercent);
            $this->assertEquals(0.1, $withdrawAssetUSD->minAmount);
            $this->assertEquals(1000.0, $withdrawAssetUSD->maxAmount);
        }

        $types = $withdrawAssetUSD->types;
        $this->assertNotNull($types);
        $this->assertCount(2, $types);
        $bankAccountFields = $types["bank_account"];
        $this->assertNotNull($bankAccountFields);
        $this->assertTrue(in_array("PRI", $bankAccountFields["country_code"]->choices));
        $this->assertTrue($types["cash"]["dest"]->optional);
        $this->assertFalse($response->withdrawAssets["ETH"]->enabled);
        $this->assertFalse($response->withdrawExchangeAssets["USD"]->enabled);
        $this->asserttrue($response->withdrawExchangeAssets["USD"]->authenticationRequired);

        $types = $response->withdrawExchangeAssets["USD"]->types;
        $this->assertNotNull($types);
        $this->assertCount(2, $types);
        $bankAccountFields = $types["bank_account"];
        $this->assertNotNull($bankAccountFields);
        $this->assertTrue(in_array("PRI", $bankAccountFields["country_code"]->choices));
        $this->assertTrue($types["cash"]["dest"]->optional);

        $this->assertFalse($response->feeInfo->enabled);
        $this->assertTrue($response->transactionsInfo->enabled);
        $this->assertTrue($response->transactionsInfo->authenticationRequired);
        $this->assertFalse($response->transactionInfo->enabled);
        $this->assertTrue($response->transactionInfo->authenticationRequired);
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

        $request = new FeeRequest(
            operation: "deposit",
            assetCode: "ETH",
            amount: 2034.09,
            type: "SEPA",
            jwt: $this->jwtToken,
        );

        $response = $transferService->fee($request);
        $this->assertNotNull($response);
        $this->assertEquals(0.013, $response->fee);
    }

    public function testDepositBankPayment(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestBankDeposit())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("USD", $query_array["asset_code"]);
            $this->assertEquals($this->accountId, $query_array["account"]);
            $this->assertEquals("123.123", $query_array["amount"]);
            $this->assertEquals('test', $query_array["extra_field"]);
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);
        $request = new DepositRequest(
            assetCode: "USD",
            account: $this->accountId,
            amount: "123.123",
            extraFields:  ['extra_field' => 'test'],
            jwt: $this->jwtToken,
        );
        $response = $transferService->deposit($request);
        $this->assertNotNull($response);
        $this->assertEquals("Make a payment to Bank: 121122676 Account: 13719713158835300", $response->how);
        $this->assertEquals("9421871e-0623-4356-b7b5-5996da122f3e", $response->id);

        $instructions = $response->instructions;
        assertNotNull($instructions);
        $bankNumberKey = OrganizationKYCFields::KEY_PREFIX . FinancialAccountKYCFields::BANK_ACCOUNT_NUMBER_KEY;
        $this->assertArrayHasKey($bankNumberKey, $instructions);
        $bankNumberInstruction = $instructions[$bankNumberKey];
        assertNotNull($bankNumberInstruction);
        $this->assertEquals("13719713158835300", $bankNumberInstruction->value);
        $this->assertEquals("US bank account number", $bankNumberInstruction->description);
    }

    public function testDepositBTC(): void
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
        $request = new DepositRequest(
            assetCode: "BTC",
            account: $this->accountId,
            amount: "3.123",
            jwt: $this->jwtToken,
        );
        $response = $transferService->deposit($request);
        $this->assertNotNull($response);
        $this->assertEquals("Make a payment to Bitcoin address 1Nh7uHdvY6fNwtQtM1G5EZAFPLC33B59rB", $response->how);
        $this->assertEquals("9421871e-0623-4356-b7b5-5996da122f3e", $response->id);
        $this->assertEquals(0.0002, $response->feeFixed);

        $instructions = $response->instructions;
        assertNotNull($instructions);
        $cryptoAddressKey = OrganizationKYCFields::KEY_PREFIX . FinancialAccountKYCFields::CRYPTO_ADDRESS_KEY;
        $this->assertArrayHasKey($cryptoAddressKey, $instructions);
        $cryptoAddressInstruction = $instructions[$cryptoAddressKey];
        assertNotNull($cryptoAddressInstruction);
        $this->assertEquals("1Nh7uHdvY6fNwtQtM1G5EZAFPLC33B59rB", $cryptoAddressInstruction->value);
        $this->assertEquals("Bitcoin address", $cryptoAddressInstruction->description);
    }

    public function testDepositRipple(): void
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
        $request = new DepositRequest(
            assetCode: "XRP",
            account: $this->accountId,
            amount: "300.0",
            jwt: $this->jwtToken,
        );
        $response = $transferService->deposit($request);
        $this->assertNotNull($response);
        $this->assertEquals(
            "Make a payment to Ripple address rNXEkKCxvfLcM1h4HJkaj2FtmYuAWrHGbf with tag 88",
            $response->how
        );
        $this->assertEquals("9421871e-0623-4356-b7b5-5996da122f3e", $response->id);
        $this->assertEquals(60, $response->eta);
        $this->assertEquals(0.1, $response->feePercent);
        $this->assertEquals(
            "You must include the tag. If the amount is more than 1000 XRP, deposit will take 24h to complete.",
            $response->extraInfo->message
        );

        $instructions = $response->instructions;
        assertNotNull($instructions);

        $cryptoAddressKey = OrganizationKYCFields::KEY_PREFIX . FinancialAccountKYCFields::CRYPTO_ADDRESS_KEY;
        $this->assertArrayHasKey($cryptoAddressKey, $instructions);
        $cryptoAddressInstruction = $instructions[$cryptoAddressKey];
        assertNotNull($cryptoAddressInstruction);
        $this->assertEquals("rNXEkKCxvfLcM1h4HJkaj2FtmYuAWrHGbf", $cryptoAddressInstruction->value);
        $this->assertEquals("Ripple address", $cryptoAddressInstruction->description);

        $cryptoMemoKey = OrganizationKYCFields::KEY_PREFIX . FinancialAccountKYCFields::CRYPTO_MEMO_KEY;
        $this->assertArrayHasKey($cryptoMemoKey, $instructions);
        $cryptoMemoInstruction = $instructions[$cryptoMemoKey];
        assertNotNull($cryptoMemoInstruction);
        $this->assertEquals("88", $cryptoMemoInstruction->value);
        $this->assertEquals("Ripple tag", $cryptoMemoInstruction->description);

    }

    public function testDepositMXN(): void
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
        $request = new DepositRequest(
            assetCode: "MXN",
            account: $this->accountId,
            amount: "120.0",
            jwt: $this->jwtToken,
        );
        $response = $transferService->deposit($request);
        $this->assertNotNull($response);
        $this->assertEquals(
            "Make a payment to Bank: STP Account: 646180111803859359",
            $response->how
        );
        $this->assertEquals("9421871e-0623-4356-b7b5-5996da122f3e", $response->id);
        $this->assertEquals(1800, $response->eta);

        $instructions = $response->instructions;
        assertNotNull($instructions);

        $clabeNumberKey = OrganizationKYCFields::KEY_PREFIX . FinancialAccountKYCFields::CLABE_NUMBER_KEY;
        $this->assertArrayHasKey($clabeNumberKey, $instructions);
        $clabeNumberInstruction = $instructions[$clabeNumberKey];
        assertNotNull($clabeNumberInstruction);
        $this->assertEquals("646180111803859359", $clabeNumberInstruction->value);
        $this->assertEquals("CLABE number", $clabeNumberInstruction->description);

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
            $this->assertEquals("120.0", $query_array["amount"]);
            $this->assertEquals($this->accountId, $query_array["account"]);
            $this->assertEquals("test", $query_array["extra_field"]);
            $this->assertEquals(
                "GCTTGO5ABSTHABXWL2FMHPZ2XFOZDXJYJN5CKFRKXMPAAWZW3Y3JZ3JK",
                $query_array["dest"]
            );
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);
        $request = new WithdrawRequest(
            assetCode: "XLM",
            type: "crypto",
            dest: "GCTTGO5ABSTHABXWL2FMHPZ2XFOZDXJYJN5CKFRKXMPAAWZW3Y3JZ3JK",
            account: $this->accountId,
            amount: "120.0",
            extraFields: ['extra_field' => 'test'],
            jwt: $this->jwtToken,
        );

        $response = $transferService->withdraw($request);
        $this->assertNotNull($response);
        $this->assertEquals("GCIBUCGPOHWMMMFPFTDWBSVHQRT4DIBJ7AD6BZJYDITBK2LCVBYW7HUQ", $response->accountId);
        $this->assertEquals("id", $response->memoType);
        $this->assertEquals("123", $response->memo);
        $this->assertEquals("9421871e-0623-4356-b7b5-5996da122f3e", $response->id);
    }

    public function testDepositExchange(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestBankDeposit())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            self::assertStringContainsString('deposit-exchange', $request->getUri()->getPath());
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("XYZ", $query_array["destination_asset"]);
            $this->assertEquals(
                'GCIBUCGPOHWMMMFPFTDWBSVHQRT4DIBJ7AD6BZJYDITBK2LCVBYW7HUQ',
                $query_array["account"]
            );
            $this->assertEquals("iso4217:USD", $query_array["source_asset"]);
            $this->assertEquals("999", $query_array["location_id"]);
            $this->assertEquals("282837", $query_array["quote_id"]);
            $this->assertEquals("100", $query_array["amount"]);
            $this->assertEquals("test", $query_array["extra_field"]);
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);
        $request = new DepositExchangeRequest(
            destinationAsset: 'XYZ',
            sourceAsset: 'iso4217:USD',
            amount: '100',
            account: 'GCIBUCGPOHWMMMFPFTDWBSVHQRT4DIBJ7AD6BZJYDITBK2LCVBYW7HUQ',
            quoteId: '282837',
            locationId: '999',
            extraFields: ['extra_field' => 'test'],
            jwt: $this->jwtToken,
        );
        $response = $transferService->depositExchange($request);
        $this->assertNotNull($response);
        $this->assertEquals("Make a payment to Bank: 121122676 Account: 13719713158835300", $response->how);
        $this->assertEquals("9421871e-0623-4356-b7b5-5996da122f3e", $response->id);

        $instructions = $response->instructions;
        assertNotNull($instructions);
        $bankNumberKey = OrganizationKYCFields::KEY_PREFIX . FinancialAccountKYCFields::BANK_ACCOUNT_NUMBER_KEY;
        $this->assertArrayHasKey($bankNumberKey, $instructions);
        $bankNumberInstruction = $instructions[$bankNumberKey];
        assertNotNull($bankNumberInstruction);
        $this->assertEquals("13719713158835300", $bankNumberInstruction->value);
        $this->assertEquals("US bank account number", $bankNumberInstruction->description);
    }

    public function testWithdrawExchange(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestWithdrawSuccess())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            self::assertStringContainsString('withdraw-exchange', $request->getUri()->getPath());
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("XYZ", $query_array["source_asset"]);
            $this->assertEquals("iso4217:USD", $query_array["destination_asset"]);
            $this->assertEquals("bank_account", $query_array["type"]);
            $this->assertEquals("700", $query_array["amount"]);
            $this->assertEquals("282837", $query_array["quote_id"]);
            $this->assertEquals("999", $query_array["location_id"]);
            $this->assertEquals("test", $query_array["extra_field"]);
            return $request;
        }));

        $transferService->setMockHandlerStack($stack);
        $request = new WithdrawExchangeRequest(
            sourceAsset: 'XYZ',
            destinationAsset: 'iso4217:USD',
            amount: '700',
            type: 'bank_account',
            quoteId: '282837',
            locationId: '999',
            extraFields: ['extra_field' => 'test'],
            jwt: $this->jwtToken,
        );

        $response = $transferService->withdrawExchange($request);
        $this->assertNotNull($response);
        $this->assertEquals("GCIBUCGPOHWMMMFPFTDWBSVHQRT4DIBJ7AD6BZJYDITBK2LCVBYW7HUQ", $response->accountId);
        $this->assertEquals("id", $response->memoType);
        $this->assertEquals("123", $response->memo);
        $this->assertEquals("9421871e-0623-4356-b7b5-5996da122f3e", $response->id);
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
        $request = new DepositRequest(
            assetCode: "MXN",
            account: $this->accountId,
            amount: "120.0",
            jwt: $this->jwtToken,);
        $thrown = false;
        try {
            $response = $transferService->deposit($request);
        } catch (CustomerInformationNeededException $e) {
            $thrown = true;
            $response = $e->response;
            $this->assertContains("tax_id", $response->fields);
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
        $request = new WithdrawRequest(assetCode: "XLM",
            type: "crypto",
            dest: "GCTTGO5ABSTHABXWL2FMHPZ2XFOZDXJYJN5CKFRKXMPAAWZW3Y3JZ3JK",
            account: $this->accountId,
            amount: "120.0",
            jwt: $this->jwtToken,
        );
        $thrown = false;
        try {
            $response = $transferService->withdraw($request);
        } catch (CustomerInformationNeededException $e) {
            $thrown = true;
            $response = $e->response;
            $this->assertContains("tax_id", $response->fields);
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
        $request = new DepositRequest(
            assetCode: "MXN",
            account: $this->accountId,
            amount: "120.0",
            jwt: $this->jwtToken,
        );
        $thrown = false;
        try {
            $response = $transferService->deposit($request);
        } catch (CustomerInformationStatusException $e) {
            $thrown = true;
            $response = $e->response;
            $this->assertEquals("denied", $response->status);
            $this->assertEquals("https://api.example.com/kycstatus?account=GACW7NONV43MZIFHCOKCQJAKSJSISSICFVUJ2C6EZIW5773OU3HD64VI", $response->moreInfoUrl);
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
        $request = new WithdrawRequest(assetCode: "XLM",
            type: "crypto",
            dest: "GCTTGO5ABSTHABXWL2FMHPZ2XFOZDXJYJN5CKFRKXMPAAWZW3Y3JZ3JK",
            account: $this->accountId,
            amount: "120.0",
            jwt: $this->jwtToken,
        );
        $thrown = false;
        try {
            $response = $transferService->withdraw($request);
        } catch (CustomerInformationStatusException $e) {
            $thrown = true;
            $response = $e->response;
            $this->assertEquals("denied", $response->status);
            $this->assertEquals("https://api.example.com/kycstatus?account=GACW7NONV43MZIFHCOKCQJAKSJSISSICFVUJ2C6EZIW5773OU3HD64VI", $response->moreInfoUrl);
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
        $request = new AnchorTransactionsRequest(
            assetCode: "XLM",
            account: "GCTTGO5ABSTHABXWL2FMHPZ2XFOZDXJYJN5CKFRKXMPAAWZW3Y3JZ3JK",
            jwt: $this->jwtToken,
        );

        $response = $transferService->transactions($request);
        $this->assertNotNull($response);
        $this->assertCount(6, $response->transactions);
        $this->assertEquals("82fhs729f63dh0v4", $response->transactions[0]->id);
        $this->assertEquals("deposit", $response->transactions[0]->kind);
        $this->assertEquals("pending_external", $response->transactions[0]->status);
        $this->assertEquals(3600, $response->transactions[0]->statusEta);
        $this->assertEquals("2dd16cb409513026fbe7defc0c6f826c2d2c65c3da993f747d09bf7dafd31093",
            $response->transactions[0]->externalTransactionId);
        $this->assertEquals("18.34", $response->transactions[0]->amountIn);
        $this->assertEquals("18.24", $response->transactions[0]->amountOut);
        $this->assertEquals("0.1", $response->transactions[0]->amountFee);
        $this->assertEquals("2017-03-20T17:05:32Z", $response->transactions[0]->startedAt);

        $transaction = $response->transactions[1];
        $this->assertEquals("52fys79f63dh3v2", $transaction->id);
        $this->assertEquals("deposit-exchange", $transaction->kind);
        $this->assertEquals("pending_anchor", $transaction->status);
        $this->assertEquals(3600, $transaction->statusEta);
        $this->assertEquals("2dd16cb409513026fbe7defc0c6f826c2d2c65c3da993f747d09bf7dafd31093",
            $transaction->externalTransactionId);
        $this->assertEquals("500", $transaction->amountIn);
        $this->assertEquals("iso4217:BRL", $transaction->amountInAsset);
        $this->assertEquals("100", $transaction->amountOut);
        $this->assertEquals("stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN",
            $transaction->amountOutAsset);
        $this->assertEquals("0.1", $transaction->amountFee);
        $this->assertEquals("iso4217:BRL", $transaction->amountFeeAsset);
        $this->assertEquals("2021-06-11T17:05:32Z", $transaction->startedAt);

        $transaction = $response->transactions[2];
        $this->assertEquals("82fhs729f63dh0v4", $transaction->id);
        $this->assertEquals("withdrawal", $transaction->kind);
        $this->assertEquals("completed", $transaction->status);
        $this->assertNull($transaction->statusEta);
        $this->assertEquals("510", $transaction->amountIn);
        $this->assertNull($transaction->amountInAsset);
        $this->assertEquals("490", $transaction->amountOut);
        $this->assertNull($transaction->amountOutAsset);
        $this->assertEquals("5", $transaction->amountFee);
        $this->assertNull($transaction->amountFeeAsset);
        $this->assertEquals("17a670bc424ff5ce3b386dbfaae9990b66a2a37b4fbe51547e8794962a3f9e6a",
            $transaction->stellarTransactionId);
        $this->assertEquals("1238234", $transaction->externalTransactionId);
        $this->assertEquals("GBANAGOAXH5ONSBI2I6I5LHP2TCRHWMZIAMGUQH2TNKQNCOGJ7GC3ZOL",
            $transaction->withdrawAnchorAccount);
        $this->assertEquals("186384", $transaction->withdrawMemo);
        $this->assertEquals("id", $transaction->withdrawMemoType);

        $refunds = $transaction->refunds;
        $this->assertEquals("10", $refunds->amountRefunded);
        $this->assertEquals("5", $refunds->amountFee);
        $payments = $refunds->payments;
        $this->assertCount(1, $payments);
        $payment = $payments[0];
        $this->assertEquals("b9d0b2292c4e09e8eb22d036171491e87b8d2086bf8b265874c8d182cb9c9020", $payment->id);
        $this->assertEquals("stellar", $payment->idType);
        $this->assertEquals("10", $payment->amount);
        $this->assertEquals("5", $payment->fee);

        $transaction = $response->transactions[3];
        $this->assertEquals("72fhs729f63dh0v1", $transaction->id);
        $this->assertEquals("deposit", $transaction->kind);
        $this->assertEquals("completed", $transaction->status);
        $this->assertNull($transaction->statusEta);
        $this->assertEquals("510", $transaction->amountIn);
        $this->assertNull($transaction->amountInAsset);
        $this->assertEquals("490", $transaction->amountOut);
        $this->assertNull($transaction->amountOutAsset);
        $this->assertEquals("5", $transaction->amountFee);
        $this->assertNull($transaction->amountFeeAsset);
        $this->assertEquals("2017-03-20T17:00:02Z", $transaction->startedAt);
        $this->assertEquals("2017-03-20T17:09:58Z", $transaction->completedAt);
        $this->assertEquals("17a670bc424ff5ce3b386dbfaae9990b66a2a37b4fbe51547e8794962a3f9e6a",
            $transaction->stellarTransactionId);
        $this->assertEquals("1238234", $transaction->externalTransactionId);
        $this->assertEquals("AJ3845SAD", $transaction->from);
        $this->assertEquals("GBITQ4YAFKD2372TNAMNHQ4JV5VS3BYKRK4QQR6FOLAR7XAHC3RVGVVJ", $transaction->to);

        $refunds = $transaction->refunds;
        $this->assertEquals("10", $refunds->amountRefunded);
        $this->assertEquals("5", $refunds->amountFee);
        $payments = $refunds->payments;
        $this->assertCount(1, $payments);
        $payment = $payments[0];
        $this->assertEquals("104201", $payment->id);
        $this->assertEquals("external", $payment->idType);
        $this->assertEquals("10", $payment->amount);
        $this->assertEquals("5", $payment->fee);

        $transaction = $response->transactions[4];
        $this->assertEquals("52fys79f63dh3v1", $transaction->id);
        $this->assertEquals("withdrawal", $transaction->kind);
        $this->assertEquals("pending_transaction_info_update", $transaction->status);
        $this->assertNull($transaction->statusEta);
        $this->assertEquals("750.00", $transaction->amountIn);
        $this->assertNull($transaction->amountInAsset);
        $this->assertNull($transaction->amountOut);
        $this->assertNull($transaction->amountOutAsset);
        $this->assertNull($transaction->amountFee);
        $this->assertNull($transaction->amountFeeAsset);
        $this->assertEquals("2017-03-20T17:00:02Z", $transaction->startedAt);
        $this->assertNull($transaction->completedAt);
        $this->assertEquals("We were unable to send funds to the provided bank account. Bank error: 'Account does not exist'. Please provide the correct bank account address.",
            $transaction->requiredInfoMessage);

        $requiredInfoUpdates = $transaction->requiredInfoUpdates;
        $this->assertCount(2, $requiredInfoUpdates);
        $dest = $requiredInfoUpdates['dest'];
        $this->assertEquals("your bank account number", $dest->description);
        $destExtra = $requiredInfoUpdates['dest_extra'];
        $this->assertEquals("your routing number", $destExtra->description);

        $transaction = $response->transactions[5];
        $this->assertEquals("52fys79f63dh3v2", $transaction->id);
        $this->assertEquals("withdrawal-exchange", $transaction->kind);
        $this->assertEquals("pending_anchor", $transaction->status);
        $this->assertEquals(3600, $transaction->statusEta);
        $this->assertEquals("100", $transaction->amountIn);
        $this->assertEquals("stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN",
            $transaction->amountInAsset);
        $this->assertEquals("500", $transaction->amountOut);
        $this->assertEquals("iso4217:BRL", $transaction->amountOutAsset);
        $this->assertEquals("0.1", $transaction->amountFee);
        $this->assertEquals("stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN",
            $transaction->amountFeeAsset);
        $this->assertEquals("2021-06-11T17:05:32Z", $transaction->startedAt);

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
        $request->stellarTransactionId = "17a670bc424ff5ce3b386dbfaae9990b66a2a37b4fbe51547e8794962a3f9e6a";
        $response = $transferService->transaction($request);
        $this->assertNotNull($response);
        $this->assertEquals("82fhs729f63dh0v4", $response->transaction->id);
        $this->assertEquals("deposit", $response->transaction->kind);
        $this->assertEquals("pending_external", $response->transaction->status);
        $this->assertEquals(3600, $response->transaction->statusEta);
        $this->assertEquals("2dd16cb409513026fbe7defc0c6f826c2d2c65c3da993f747d09bf7dafd31093",
            $response->transaction->externalTransactionId);
        $this->assertEquals("18.34", $response->transaction->amountIn);
        $this->assertEquals("18.24", $response->transaction->amountOut);
        $this->assertEquals("0.1", $response->transaction->amountFee);
        $this->assertEquals("2017-03-20T17:05:32Z", $response->transaction->startedAt);
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


        $fields = array();
        $fields["dest"] = "12345678901234";
        $fields["dest_extra"] = "021000021";
        $request = new PatchTransactionRequest(
            id: "82fhs729f63dh0v4",
            fields: $fields,
            jwt: $this->jwtToken,
        );
        $response = $transferService->patchTransaction($request);
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    // Edge Case Tests - Error Responses and Timeout Handling

    public function testInfoWithBadRequestError(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $errorResponse = json_encode(['error' => 'Invalid request']);
        $mock = new MockHandler([
            new Response(400, [], $errorResponse)
        ]);

        $transferService->setMockHandlerStack(HandlerStack::create($mock));

        $this->expectException(\Exception::class);
        $transferService->info($this->jwtToken);
    }

    public function testInfoWithUnauthorizedError(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $errorResponse = json_encode(['error' => 'Unauthorized']);
        $mock = new MockHandler([
            new Response(401, [], $errorResponse)
        ]);

        $transferService->setMockHandlerStack(HandlerStack::create($mock));

        $this->expectException(\Exception::class);
        $transferService->info($this->jwtToken);
    }

    public function testInfoWithServerError(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $errorResponse = json_encode(['error' => 'Internal server error']);
        $mock = new MockHandler([
            new Response(500, [], $errorResponse)
        ]);

        $transferService->setMockHandlerStack(HandlerStack::create($mock));

        $this->expectException(\Exception::class);
        $transferService->info($this->jwtToken);
    }

    public function testDepositWithNotFoundError(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $errorResponse = json_encode(['error' => 'Asset not found']);
        $mock = new MockHandler([
            new Response(404, [], $errorResponse)
        ]);

        $transferService->setMockHandlerStack(HandlerStack::create($mock));

        $request = new DepositRequest(
            assetCode: "UNKNOWN",
            account: $this->accountId,
            jwt: $this->jwtToken
        );

        $this->expectException(\Exception::class);
        $transferService->deposit($request);
    }

    public function testWithdrawWithConflictError(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $errorResponse = json_encode(['error' => 'Transaction already exists']);
        $mock = new MockHandler([
            new Response(409, [], $errorResponse)
        ]);

        $transferService->setMockHandlerStack(HandlerStack::create($mock));

        $request = new WithdrawRequest(
            assetCode: "XLM",
            type: "crypto",
            dest: "GCTTGO5ABSTHABXWL2FMHPZ2XFOZDXJYJN5CKFRKXMPAAWZW3Y3JZ3JK",
            account: $this->accountId,
            jwt: $this->jwtToken
        );

        $this->expectException(\Exception::class);
        $transferService->withdraw($request);
    }

    public function testTransactionsWithMalformedJsonResponse(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $malformedJson = '{"transactions": [{"id": "test", "invalid_json';
        $mock = new MockHandler([
            new Response(200, [], $malformedJson)
        ]);

        $transferService->setMockHandlerStack(HandlerStack::create($mock));

        $request = new AnchorTransactionsRequest(
            assetCode: "XLM",
            account: $this->accountId,
            jwt: $this->jwtToken
        );

        $this->expectException(\Exception::class);
        $transferService->transactions($request);
    }

    public function testFeeWithMissingRequiredField(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $errorResponse = json_encode(['error' => 'Missing required parameter: operation']);
        $mock = new MockHandler([
            new Response(400, [], $errorResponse)
        ]);

        $transferService->setMockHandlerStack(HandlerStack::create($mock));

        $request = new FeeRequest(
            operation: "",
            assetCode: "USD",
            amount: 100.0,
            jwt: $this->jwtToken
        );

        $this->expectException(\Exception::class);
        $transferService->fee($request);
    }

    public function testDepositExchangeWithInvalidQuoteId(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $errorResponse = json_encode(['error' => 'Invalid or expired quote_id']);
        $mock = new MockHandler([
            new Response(400, [], $errorResponse)
        ]);

        $transferService->setMockHandlerStack(HandlerStack::create($mock));

        $request = new DepositExchangeRequest(
            destinationAsset: 'XYZ',
            sourceAsset: 'iso4217:USD',
            amount: '100',
            account: $this->accountId,
            quoteId: 'invalid_quote',
            jwt: $this->jwtToken
        );

        $this->expectException(\Exception::class);
        $transferService->depositExchange($request);
    }

    public function testWithdrawExchangeWithUnsupportedAsset(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $errorResponse = json_encode(['error' => 'Asset pair not supported for exchange']);
        $mock = new MockHandler([
            new Response(400, [], $errorResponse)
        ]);

        $transferService->setMockHandlerStack(HandlerStack::create($mock));

        $request = new WithdrawExchangeRequest(
            sourceAsset: 'UNSUPPORTED',
            destinationAsset: 'iso4217:USD',
            amount: '100',
            type: 'bank_account',
            jwt: $this->jwtToken
        );

        $this->expectException(\Exception::class);
        $transferService->withdrawExchange($request);
    }

    public function testTransactionWithEmptyResponse(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $emptyResponse = json_encode([]);
        $mock = new MockHandler([
            new Response(200, [], $emptyResponse)
        ]);

        $transferService->setMockHandlerStack(HandlerStack::create($mock));

        $request = new AnchorTransactionRequest();
        $request->id = "nonexistent_id";
        $request->jwt = $this->jwtToken;

        $this->expectException(\Throwable::class);
        $transferService->transaction($request);
    }

    public function testPatchTransactionWithForbiddenError(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $errorResponse = json_encode(['error' => 'Transaction cannot be modified in current state']);
        $mock = new MockHandler([
            new Response(403, [], $errorResponse)
        ]);

        $transferService->setMockHandlerStack(HandlerStack::create($mock));

        $fields = ['dest' => '12345'];
        $request = new PatchTransactionRequest(
            id: "completed_tx_id",
            fields: $fields,
            jwt: $this->jwtToken
        );

        $this->expectException(\Exception::class);
        $transferService->patchTransaction($request);
    }

    public function testDepositWithRateLimitError(): void
    {
        $transferService = new TransferServerService($this->serviceAddress);
        $errorResponse = json_encode(['error' => 'Rate limit exceeded']);
        $mock = new MockHandler([
            new Response(429, [], $errorResponse)
        ]);

        $transferService->setMockHandlerStack(HandlerStack::create($mock));

        $request = new DepositRequest(
            assetCode: "USD",
            account: $this->accountId,
            jwt: $this->jwtToken
        );

        $this->expectException(\Exception::class);
        $transferService->deposit($request);
    }
}
