<?php  declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\SEP\CrossBorderPayments;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31BadRequestException;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31CustomerInfoNeededException;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31PostTransactionsRequest;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31TransactionCallbackNotSupportedException;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31TransactionInfoNeededException;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31TransactionNotFoundException;

class CrossBorderPaymentsTest extends TestCase
{
    private string $serviceAddress = "http://api.stellar.org/direct-payment";
    private string $jwtToken = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJHQTZVSVhYUEVXWUZJTE5VSVdBQzM3WTRRUEVaTVFWREpIREtWV0ZaSjJLQ1dVQklVNUlYWk5EQSIsImp0aSI6IjE0NGQzNjdiY2IwZTcyY2FiZmRiZGU2MGVhZTBhZDczM2NjNjVkMmE2NTg3MDgzZGFiM2Q2MTZmODg1MTkwMjQiLCJpc3MiOiJodHRwczovL2ZsYXBweS1iaXJkLWRhcHAuZmlyZWJhc2VhcHAuY29tLyIsImlhdCI6MTUzNDI1Nzk5NCwiZXhwIjoxNTM0MzQ0Mzk0fQ.8nbB83Z6vGBgC1X9r3N6oQCFTBzDiITAfCJasRft0z0";

    private string $infoResponse = "{  \"receive\": {    \"USDC\": {      \"quotes_supported\": true,      \"quotes_required\": false,      \"fee_fixed\": 5,      \"fee_percent\": 1,      \"min_amount\": 0.1,      \"max_amount\": 1000,      \"sep12\": {        \"sender\": {          \"types\": {            \"sep31-sender\": {              \"description\": \"U.S. citizens limited to sending payments of less than $10,000 in value\"            },            \"sep31-large-sender\": {              \"description\": \"U.S. citizens that do not have sending limits\"            },            \"sep31-foreign-sender\": {              \"description\": \"non-U.S. citizens sending payments of less than $10,000 in value\"            }          }        },        \"receiver\": {          \"types\": {            \"sep31-receiver\": {              \"description\": \"U.S. citizens receiving USD\"            },            \"sep31-foreign-receiver\": {              \"description\": \"non-U.S. citizens receiving USD\"            }          }        }      }    }  }}";
    private string $postTransactionsResponse = "{    \"id\": \"82fhs729f63dh0v4\",    \"stellar_account_id\": \"GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H\",    \"stellar_memo\": \"123456789\",    \"stellar_memo_type\": \"id\"}";
    private string $txPendingExternal = "{  \"transaction\": {      \"id\": \"82fhs729f63dh0v4\",      \"status\": \"pending_external\",      \"status_eta\": 3600,      \"status_message\": \"Payment has been initiated via ACH deposit.\",      \"stellar_transaction_id\": \"b9d0b2292c4e09e8eb22d036171491e87b8d2086bf8b265874c8d182cb9c9020\",      \"external_transaction_id\": \"ABCDEFG1234567890\",      \"stellar_account_id\": \"GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H\",      \"stellar_memo\": \"123456789\",      \"stellar_memo_type\": \"id\",      \"amount_in\": \"18.34\",      \"amount_out\": \"18.24\",      \"amount_fee\": \"0.1\",      \"started_at\": \"2017-03-20T17:05:32Z\"    }}";
    private string $txPendingInfoUpdate = "{  \"transaction\": {      \"id\": \"82fhs729f63dh0v4\",      \"status\": \"pending_transaction_info_update\",      \"status_eta\": 3600,      \"stellar_transaction_id\": \"b9d0b2292c4e09e8eb22d036171491e87b8d2086bf8b265874c8d182cb9c9020\",      \"external_transaction_id\": \"ABCDEFG1234567890\",      \"amount_in\": \"18.34\",      \"amount_out\": \"18.24\",      \"amount_fee\": \"0.1\",      \"stellar_account_id\": \"GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H\",      \"stellar_memo\": \"123456789\",      \"stellar_memo_type\": \"id\",      \"started_at\": \"2017-03-20T17:05:32Z\",      \"required_info_message\": \"The bank reported an incorrect account number for the receiver, please ensure the account matches legal documents\",      \"required_info_updates\": {         \"transaction\": {            \"receiver_account_number\": {               \"description\": \"The receiver's bank account number\"            }         }      }    }}";
    private string $txCompleted = "{  \"transaction\": {      \"id\": \"82fhs729f63dh0v4\",      \"status\": \"completed\",      \"amount_in\": \"110\",      \"amount_out\": \"90\",      \"amount_fee\": \"5\",      \"started_at\": \"2017-03-20T17:05:32Z\",      \"stellar_transaction_id\": \"b9d0b2292c4e09e8eb22d036171491e87b8d2086bf8b265874c8d182cb9c9020\",      \"stellar_account_id\": \"GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H\",      \"stellar_memo\": \"123456789\",      \"stellar_memo_type\": \"id\",      \"refunds\": {        \"amount_refunded\": \"10\",        \"amount_fee\": \"5\",        \"payments\": [          {            \"id\": \"54321ab047a193c6fda1c47f5962cbcca8708d79b87089ababd57532c21c5402\",            \"amount\": \"10\",            \"fee\": \"5\"          }        ]      }    }}";
    private string $txPendingExQuoteId = "{  \"transaction\": {    \"id\": \"82fhs729f63dh0v4\",    \"amount_in\": \"100.00\",    \"amount_in_asset\": \"stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN\",    \"amount_out\": \"500.00\",    \"amount_out_asset\": \"iso4217:BRL\",    \"amount_fee\": \"10.00\",    \"amount_fee_asset\": \"stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN\",    \"quote_id\": \"de762cda-a193-4961-861e-57b31fed6eb3\",    \"started_at\": \"2017-03-20T17:05:32Z\",    \"status\": \"pending_external\",    \"status_eta\": 3600,    \"stellar_transaction_id\": \"b9d0b2292c4e09e8eb22d036171491e87b8d2086bf8b265874c8d182cb9c9020\",    \"external_transaction_id\": \"ABCDEFG1234567890\",    \"stellar_account_id\": \"GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H\",    \"stellar_memo\": \"123456789\",    \"stellar_memo_type\": \"id\"  }}";

    public function testGetInfo(): void {

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->infoResponse)
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
        $service = new CrossBorderPaymentsService($this->serviceAddress, $httpClient);
        $response = $service->info($this->jwtToken);
        $assets = $response->receiveAssets;
        $this->assertCount(1, $assets);
        $this->assertArrayHasKey('USDC', $assets);
        $usdc = $assets['USDC'];
        $this->assertNotNull($usdc);
        $this->assertTrue($usdc->quotesSupported);
        $this->assertFalse($usdc->quotesRequired);
        $this->assertEquals(5, $usdc->feeFixed);
        $this->assertEquals(1, $usdc->feePercent);
        $this->assertEquals(0.1, $usdc->minAmount);
        $this->assertEquals(1000, $usdc->maxAmount);
        $sep12Info = $usdc->sep12Info;
        $this->assertNotNull($sep12Info);
        $this->assertCount(3, $sep12Info->senderTypes);
        $this->assertCount(2, $sep12Info->receiverTypes);
        $this->assertArrayHasKey('sep31-sender', $sep12Info->senderTypes);
        $this->assertEquals('U.S. citizens limited to sending payments of less than $10,000 in value',
            $sep12Info->senderTypes['sep31-sender']);
        $this->assertArrayHasKey('sep31-large-sender', $sep12Info->senderTypes);
        $this->assertEquals('U.S. citizens that do not have sending limits',
            $sep12Info->senderTypes['sep31-large-sender']);
        $this->assertArrayHasKey('sep31-foreign-sender', $sep12Info->senderTypes);
        $this->assertEquals('non-U.S. citizens sending payments of less than $10,000 in value',
            $sep12Info->senderTypes['sep31-foreign-sender']);
        $this->assertArrayHasKey('sep31-receiver', $sep12Info->receiverTypes);
        $this->assertEquals('U.S. citizens receiving USD',
            $sep12Info->receiverTypes['sep31-receiver']);
        $this->assertArrayHasKey('sep31-foreign-receiver', $sep12Info->receiverTypes);
        $this->assertEquals('non-U.S. citizens receiving USD',
            $sep12Info->receiverTypes['sep31-foreign-receiver']);
    }

    public function testPostTransactions(): void {

        $fields = ['transaction' => ['xxx' => 'yyy']];
        $mock = new MockHandler([
            new Response(201, ['X-Foo' => 'Bar'], $this->postTransactionsResponse),
            new Response(400, ['X-Foo' => 'Bar'],
                json_encode(['error' => 'customer_info_needed', 'type' => '1234'])),
            new Response(400, ['X-Foo' => 'Bar'],
                json_encode(['error' => 'transaction_info_needed', 'fields' => $fields])),
            new Response(400, ['X-Foo' => 'Bar'],
                json_encode(['error' => 'The amount was above the maximum limit'])),
        ]);

        $amount = 200.03;
        $assetCode = 'USDC';
        $assetIssuer = 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN';
        $destinationAsset = 'iso4217:BRL';
        $quoteId = '1029038383744';
        $senderId = 'GA3PB4ZTDGVP6HQ5DQCJ7MMBO5PAUUWK3V3B27FGNRYIRL7JOA3QDTJG';
        $receiverId = 'GDIWZQ6MS64Q7EL4WIHZMZVJKPMKXGR3YKHOTTPG44GLXDLIGVJ7M57A';
        $refundMemo = '992839283';
        $refundMemoType = 'id';

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) use (
            $amount, $assetCode, $assetIssuer, $destinationAsset, $quoteId, $senderId,
            $receiverId, $refundMemo, $refundMemoType) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("POST", $request->getMethod());
            $body = $request->getBody()->__toString();
            $jsonData = @json_decode($body, true);
            $this->assertEquals($amount, $jsonData['amount']);
            $this->assertEquals($assetCode, $jsonData['asset_code']);
            $this->assertEquals($assetIssuer, $jsonData['asset_issuer']);
            $this->assertEquals($destinationAsset, $jsonData['destination_asset']);
            $this->assertEquals($quoteId, $jsonData['quote_id']);
            $this->assertEquals($senderId, $jsonData['sender_id']);
            $this->assertEquals($receiverId, $jsonData['receiver_id']);
            $this->assertEquals($refundMemo, $jsonData['refund_memo']);
            $this->assertEquals($refundMemoType, $jsonData['refund_memo_type']);

            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $service = new CrossBorderPaymentsService($this->serviceAddress, $httpClient);
        $request = new SEP31PostTransactionsRequest(
            amount: $amount,
            assetCode: $assetCode,
            assetIssuer: $assetIssuer,
            destinationAsset: $destinationAsset,
            quoteId: $quoteId,
            senderId: $senderId,
            receiverId: $receiverId,
            refundMemo: $refundMemo,
            refundMemoType: $refundMemoType,
        );
        $response = $service->postTransactions($request, $this->jwtToken);

        $this->assertEquals('82fhs729f63dh0v4', $response->id);
        $this->assertEquals('GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H',
            $response->stellarAccountId);
        $this->assertEquals('123456789', $response->stellarMemo);
        $this->assertEquals('id', $response->stellarMemoType);

        $thrown = false;
        try {
            $service->postTransactions($request, $this->jwtToken);
        } catch (SEP31CustomerInfoNeededException $e) {
            $this->assertEquals('1234', $e->type);
            $thrown = true;
        }
        $this->assertTrue($thrown);

        $thrown = false;
        try {
            $service->postTransactions($request, $this->jwtToken);
        } catch (SEP31TransactionInfoNeededException $e) {
            $this->assertEquals($fields, $e->fields);
            $thrown = true;
        }
        $this->assertTrue($thrown);

        $thrown = false;
        try {
            $service->postTransactions($request, $this->jwtToken);
        } catch (SEP31BadRequestException $e) {
            $this->assertEquals("The amount was above the maximum limit", $e->getMessage());
            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    public function testGetTransaction(): void {

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->txPendingExternal),
            new Response(200, ['X-Foo' => 'Bar'], $this->txPendingInfoUpdate),
            new Response(200, ['X-Foo' => 'Bar'], $this->txCompleted),
            new Response(200, ['X-Foo' => 'Bar'], $this->txPendingExQuoteId),
            new Response(404, ['X-Foo' => 'Bar'], json_encode(['error' => 'Transaction not found'])),
        ]);

        $id = '82fhs729f63dh0v4';
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
        $service = new CrossBorderPaymentsService($this->serviceAddress, $httpClient);
        $response = $service->getTransaction($id, $this->jwtToken);
        $this->assertEquals($id, $response->id);
        $this->assertEquals('pending_external', $response->status);
        $this->assertEquals(3600, $response->statusEta);
        $this->assertEquals('Payment has been initiated via ACH deposit.', $response->statusMessage);
        $this->assertEquals('b9d0b2292c4e09e8eb22d036171491e87b8d2086bf8b265874c8d182cb9c9020',
            $response->stellarTransactionId);
        $this->assertEquals('ABCDEFG1234567890', $response->externalTransactionId);
        $this->assertEquals('GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H',
            $response->stellarAccountId);
        $this->assertEquals('123456789', $response->stellarMemo);
        $this->assertEquals('id', $response->stellarMemoType);
        $this->assertEquals('18.34', $response->amountIn);
        $this->assertEquals('18.24', $response->amountOut);
        $this->assertEquals('0.1', $response->amountFee);
        $this->assertEquals('2017-03-20T17:05:32Z', $response->startedAt);

        $response = $service->getTransaction($id, $this->jwtToken);
        $this->assertEquals($id, $response->id);
        $this->assertEquals('pending_transaction_info_update', $response->status);
        $this->assertEquals('The bank reported an incorrect account number for the receiver, please ensure the account matches legal documents',
            $response->requiredInfoMessage);
        $txFields = $response->requiredInfoUpdates['transaction'];
        $this->assertArrayHasKey('receiver_account_number', $txFields);
        $this->assertEquals("The receiver's bank account number", $txFields['receiver_account_number']['description']);

        $response = $service->getTransaction($id, $this->jwtToken);
        $this->assertEquals($id, $response->id);
        $this->assertEquals('completed', $response->status);
        $refunds = $response->refunds;
        $this->assertNotNull($refunds);
        $this->assertEquals("10", $refunds->amountRefunded);
        $this->assertEquals("5", $refunds->amountFee);
        $payments = $refunds->payments;
        $this->assertCount(1, $payments);
        $payment = $payments[0];
        $this->assertEquals("54321ab047a193c6fda1c47f5962cbcca8708d79b87089ababd57532c21c5402", $payment->id);
        $this->assertEquals("10", $payment->amount);
        $this->assertEquals("5", $payment->fee);

        $response = $service->getTransaction($id, $this->jwtToken);
        $this->assertEquals($id, $response->id);
        $this->assertEquals('pending_external', $response->status);
        $this->assertEquals('100.00', $response->amountIn);
        $this->assertEquals('stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
            $response->amountInAsset);
        $this->assertEquals('500.00', $response->amountOut);
        $this->assertEquals('iso4217:BRL', $response->amountOutAsset);
        $this->assertEquals('10.00', $response->amountFee);
        $this->assertEquals('stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
            $response->amountFeeAsset);
        $this->assertEquals('GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H',
            $response->stellarAccountId);
        $this->assertEquals('123456789', $response->stellarMemo);
        $this->assertEquals('id', $response->stellarMemoType);

        $thrown = false;
        try {
            $service->getTransaction($id, $this->jwtToken);
        } catch (SEP31TransactionNotFoundException) {
            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    public function testPatchTransactions(): void {

        $id = '82fhs729f63dh0v4';
        $fields = ['transaction' =>
            ['receiver_bank_account' => '12345678901234',
            'receiver_routing_number' => '021000021']
        ];
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], null),
            new Response(404, ['X-Foo' => 'Bar'], json_encode(['error' => 'Transaction not found'])),
            new Response(400, ['X-Foo' => 'Bar'], json_encode(['error' =>
                'Supplied fields do not allow updates, please only try to updates the fields requested'])),
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) use (
            $fields) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("PATCH", $request->getMethod());
            $body = $request->getBody()->__toString();
            $jsonData = @json_decode($body, true);
            $this->assertEquals($fields, $jsonData['fields']);

            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $service = new CrossBorderPaymentsService($this->serviceAddress, $httpClient);

        $service->patchTransaction($id, $fields, $this->jwtToken);

        $thrown = false;
        try {
            $service->patchTransaction($id, $fields, $this->jwtToken);
        } catch (SEP31TransactionNotFoundException) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        $thrown = false;
        try {
            $service->patchTransaction($id, $fields, $this->jwtToken);
        } catch (SEP31BadRequestException $e) {
            $this->assertEquals(
                "Supplied fields do not allow updates, please only try to updates the fields requested",
                $e->getMessage());
            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    public function testPutTransactionsCallback(): void {

        $id = '82fhs729f63dh0v4';
        $callbackUrl = 'https://sendinganchor.com/statusCallback';

        $mock = new MockHandler([
            new Response(204, ['X-Foo' => 'Bar'], null),
            new Response(404, ['X-Foo' => 'Bar'], json_encode(['error' => 'not supported'])),
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) use (
            $callbackUrl) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("PUT", $request->getMethod());
            $body = $request->getBody()->__toString();
            $jsonData = @json_decode($body, true);
            $this->assertEquals($callbackUrl, $jsonData['url']);

            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $service = new CrossBorderPaymentsService($this->serviceAddress, $httpClient);

        $service->putTransactionCallback($id, $callbackUrl, $this->jwtToken);

        $thrown = false;
        try {
            $service->putTransactionCallback($id, $callbackUrl, $this->jwtToken);
        } catch (SEP31TransactionCallbackNotSupportedException) {
            $thrown = true;
        }
        $this->assertTrue($thrown);
    }
}