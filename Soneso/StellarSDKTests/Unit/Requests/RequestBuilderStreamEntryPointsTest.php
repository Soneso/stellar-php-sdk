<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as HttpResponse;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Soneso\StellarSDK\Responses\Effects\AccountDebitedEffectResponse;
use Soneso\StellarSDK\Responses\Ledger\LedgerResponse;
use Soneso\StellarSDK\Responses\Operations\AccountMergeOperationResponse;
use Soneso\StellarSDK\Responses\Operations\CreateAccountOperationResponse;
use Soneso\StellarSDK\Responses\Operations\InvokeHostFunctionOperationResponse;
use Soneso\StellarSDK\Responses\Operations\OperationResponse;
use Soneso\StellarSDK\Responses\Operations\PathPaymentStrictReceiveOperationResponse;
use Soneso\StellarSDK\Responses\Operations\PathPaymentStrictSendOperationResponse;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;
use Soneso\StellarSDK\Responses\Transaction\TransactionResponse;
use Soneso\StellarSDK\StellarSDK;

/**
 * Tests the stream() entry points of the effects, ledgers, operations, payments
 * and transactions request builders against a mocked Horizon event stream.
 *
 * RequestBuilder::getAndStream reconnects in an endless loop and returns only
 * when an exception leaves it, so every callback throws StreamStoppedByTest
 * after the first event. PHPUnit runs no test code after an expected
 * exception, so the assertions run inside the callback.
 */
class RequestBuilderStreamEntryPointsTest extends TestCase
{
    private const HORIZON_URL = 'https://horizon-testnet.stellar.org';
    private const STOP_MESSAGE = 'stream stopped by the test';
    private const TRANSACTION_ACCOUNT_ID = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
    private const TRANSACTION_HASH = '5ebd5c0af4385500b53dd63b0ef5f6e8feef1a7e1c0b5a8e6e7f3f84c1f7f1c4';
    private const SENDER_ACCOUNT_ID = 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H';
    private const RECEIVER_ACCOUNT_ID = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
    private const USDC_ISSUER_ID = 'GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5';

    private MockHandler $mockHandler;

    public function testEffectsStreamDeliversParsedEffect(): void
    {
        $sdk = $this->createStreamingSdk(self::effectRecord());
        $this->expectStreamStop();

        $sdk->effects()->cursor('now')->stream(function (mixed $effect): void {
            $this->assertInstanceOf(AccountDebitedEffectResponse::class, $effect);
            $this->assertSame('0000123456789-0', $effect->getEffectId());
            $this->stopStream('/effects');
        });
    }

    public function testLedgersStreamDeliversParsedLedger(): void
    {
        $sdk = $this->createStreamingSdk(self::ledgerRecord());
        $this->expectStreamStop();

        $sdk->ledgers()->cursor('now')->stream(function (mixed $ledger): void {
            $this->assertInstanceOf(LedgerResponse::class, $ledger);
            $this->assertSame(
                '743a1f566e87e2fc02e5d88ba8da2b33a4f2f83d6483261a7a1f1b650dc4859e',
                $ledger->getHash()
            );
            $this->stopStream('/ledgers');
        });
    }

    public function testOperationsStreamDeliversParsedOperation(): void
    {
        $sdk = $this->createStreamingSdk(self::operationRecord());
        $this->expectStreamStop();

        $sdk->operations()->cursor('now')->stream(function (mixed $operation): void {
            $this->assertInstanceOf(PaymentOperationResponse::class, $operation);
            $this->assertSame('123456789', $operation->getOperationId());
            $this->stopStream('/operations');
        });
    }

    public function testPaymentsStreamDeliversParsedPayment(): void
    {
        $sdk = $this->createStreamingSdk(self::paymentRecord());
        $this->expectStreamStop();

        $sdk->payments()->cursor('now')->stream(function (mixed $payment): void {
            $this->assertInstanceOf(PaymentOperationResponse::class, $payment);
            $this->assertSame('123456789', $payment->getOperationId());
            $this->stopStream('/payments');
        });
    }

    public function testPaymentsStreamDeliversStellarAssetContractTransfer(): void
    {
        $sdk = $this->createStreamingSdk(self::paymentsRecord('5287612393054209', 'invoke_host_function', 24, [
            'function' => 'HostFunctionTypeHostFunctionTypeInvokeContract',
            'parameters' => [
                ['value' => 'AAAAEgAAAAHXkotywnA8z+r365/0701QSlWouXn8m0UOoshCtNHOYQ==', 'type' => 'Address'],
                ['value' => 'AAAADwAAAAh0cmFuc2Zlcg==', 'type' => 'Sym'],
                ['value' => 'AAAAEgAAAAAAAAAAYvwdC9CRsrYcDdZWNGsqaNfTR8bywsjubQRHAlb8Bfc=', 'type' => 'Address'],
                ['value' => 'AAAAEgAAAAAAAAAAAZRyYo7njrknFNItA5CWPCTZJ+oAmZlIbokfrC2qnCE=', 'type' => 'Address'],
                ['value' => 'AAAACgAAAAAAAAAAAAAAAAX14QA=', 'type' => 'I128'],
            ],
            'address' => '', 'salt' => '',
            'asset_balance_changes' => [[
                'asset_type' => 'native',
                'type' => 'transfer',
                'from' => self::SENDER_ACCOUNT_ID,
                'to' => self::RECEIVER_ACCOUNT_ID,
                'amount' => '10.0000000',
            ]],
        ]));
        $this->expectStreamStop();

        $sdk->payments()->cursor('now')->stream(function (mixed $payment): void {
            $this->assertInstanceOf(InvokeHostFunctionOperationResponse::class, $payment);
            $this->assertSame('5287612393054209', $payment->getOperationId());
            $this->assertSame('HostFunctionTypeHostFunctionTypeInvokeContract', $payment->getFunction());
            $changes = $payment->getAssetBalanceChanges()->toArray();
            $this->assertCount(1, $changes);
            $this->assertSame('native', $changes[0]->getAssetType());
            $this->assertSame('transfer', $changes[0]->getType());
            $this->assertSame(self::SENDER_ACCOUNT_ID, $changes[0]->getFrom());
            $this->assertSame(self::RECEIVER_ACCOUNT_ID, $changes[0]->getTo());
            $this->assertSame('10.0000000', $changes[0]->getAmount());
            $this->stopStream('/payments');
        });
    }

    public function testPaymentsStreamDeliversEachClassicPaymentType(): void
    {
        $accounts = ['from' => self::SENDER_ACCOUNT_ID, 'to' => self::RECEIVER_ACCOUNT_ID];
        $usdcToNative = $accounts + ['asset_type' => 'native', 'source_asset_type' => 'credit_alphanum4',
            'source_asset_code' => 'USDC', 'source_asset_issuer' => self::USDC_ISSUER_ID, 'path' => []];
        $sdk = $this->createStreamingSdk(
            self::paymentsRecord('5287612393050113', 'create_account', 0, ['starting_balance' => '10000.0000000',
                'funder' => self::SENDER_ACCOUNT_ID, 'account' => self::RECEIVER_ACCOUNT_ID]),
            self::paymentsRecord('5287612393050114', 'payment', 1, $accounts + ['asset_type' => 'native',
                'amount' => '25.0000000']),
            self::paymentsRecord('5287612393050115', 'account_merge', 8, ['account' => self::RECEIVER_ACCOUNT_ID,
                'into' => self::SENDER_ACCOUNT_ID]),
            self::paymentsRecord('5287612393050116', 'path_payment_strict_send', 13, $usdcToNative + [
                'amount' => '98.1000000', 'source_amount' => '10.0000000', 'destination_min' => '95.0000000']),
            self::paymentsRecord('5287612393050117', 'path_payment_strict_receive', 2, $usdcToNative + [
                'amount' => '100.0000000', 'source_amount' => '10.2000000', 'source_max' => '10.5000000']),
        );
        $this->expectStreamStop();

        $received = [];
        $sdk->payments()->cursor('now')->stream(function (mixed $payment) use (&$received): void {
            $received[] = $payment;
            if (count($received) < 5) {
                return;
            }
            $classes = array_map(fn (OperationResponse $r): string => $r::class, $received);
            $this->assertSame([CreateAccountOperationResponse::class, PaymentOperationResponse::class,
                AccountMergeOperationResponse::class, PathPaymentStrictSendOperationResponse::class,
                PathPaymentStrictReceiveOperationResponse::class], $classes);
            $this->assertSame('10000.0000000', $received[0]->getStartingBalance());
            $this->assertSame('25.0000000', $received[1]->getAmount());
            $this->assertSame(self::SENDER_ACCOUNT_ID, $received[2]->getInto());
            $this->assertSame('95.0000000', $received[3]->getDestinationMin());
            $this->assertSame('10.5000000', $received[4]->getSourceMax());
            $this->stopStream('/payments');
        });
    }

    public function testTransactionsStreamDeliversParsedTransaction(): void
    {
        $sdk = $this->createStreamingSdk(self::transactionRecord());
        $this->expectStreamStop();

        $sdk->transactions()->cursor('now')->stream(function (mixed $transaction): void {
            $this->assertInstanceOf(TransactionResponse::class, $transaction);
            $this->assertSame(self::TRANSACTION_HASH, $transaction->getHash());
            $this->stopStream('/transactions');
        });
    }

    /**
     * Returns an SDK whose client answers the first request with an event
     * stream that holds the Horizon handshake and one data event per record;
     * a second request finds the mock queue empty and fails.
     *
     * @param array<string, mixed> ...$events The Horizon records sent as data events, in order.
     */
    private function createStreamingSdk(array ...$events): StellarSDK
    {
        $body = 'data: "hello"' . "\n";
        foreach ($events as $event) {
            $body .= 'data: ' . json_encode($event, JSON_THROW_ON_ERROR) . "\n";
        }
        $this->mockHandler = new MockHandler([
            new HttpResponse(200, ['Content-Type' => 'text/event-stream'], $body),
        ]);
        $client = new Client([
            'handler' => HandlerStack::create($this->mockHandler),
            'base_uri' => self::HORIZON_URL,
        ]);
        $sdk = new StellarSDK(self::HORIZON_URL);
        $sdk->setHttpClient($client);
        return $sdk;
    }

    private function expectStreamStop(): void
    {
        $this->expectException(StreamStoppedByTest::class);
        $this->expectExceptionMessage(self::STOP_MESSAGE);
    }

    /**
     * Asserts that the stream request was an event-stream GET on the given path
     * from cursor "now", then leaves the stream loop.
     */
    private function stopStream(string $expectedPath): never
    {
        $request = $this->mockHandler->getLastRequest();
        $this->assertNotNull($request);
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('text/event-stream', $request->getHeaderLine('Accept'));
        $this->assertSame($expectedPath, $request->getUri()->getPath());
        $this->assertSame('cursor=now', $request->getUri()->getQuery());

        throw new StreamStoppedByTest(self::STOP_MESSAGE);
    }

    /**
     * @return array<string, mixed>
     */
    private static function effectRecord(): array
    {
        return [
            'id' => '0000123456789-0',
            'paging_token' => '123456789-0',
            'account' => 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H',
            'type' => 'account_debited',
            'type_i' => 3,
            'created_at' => '2025-01-20T10:00:00Z',
            'asset_type' => 'native',
            'amount' => '100.0000000',
            '_links' => [
                'operation' => ['href' => 'https://horizon-testnet.stellar.org/operations/123456789'],
                'succeeds' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=desc&cursor=123456789-0'],
                'precedes' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=asc&cursor=123456789-0']
            ]
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function ledgerRecord(): array
    {
        return [
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/ledgers/123456'],
                'transactions' => ['href' => 'https://horizon-testnet.stellar.org/ledgers/123456/transactions{?cursor,limit,order}', 'templated' => true],
                'operations' => ['href' => 'https://horizon-testnet.stellar.org/ledgers/123456/operations{?cursor,limit,order}', 'templated' => true],
                'payments' => ['href' => 'https://horizon-testnet.stellar.org/ledgers/123456/payments{?cursor,limit,order}', 'templated' => true],
                'effects' => ['href' => 'https://horizon-testnet.stellar.org/ledgers/123456/effects{?cursor,limit,order}', 'templated' => true]
            ],
            'id' => '743a1f566e87e2fc02e5d88ba8da2b33a4f2f83d6483261a7a1f1b650dc4859e',
            'paging_token' => '529811640377344',
            'hash' => '743a1f566e87e2fc02e5d88ba8da2b33a4f2f83d6483261a7a1f1b650dc4859e',
            'prev_hash' => 'c9e60dc2a9c42fc91c9ef0c81ad01dc64ef6ff2eb41aa51ba85c0ff63bf54d05',
            'sequence' => 123456,
            'successful_transaction_count' => 15,
            'failed_transaction_count' => 0,
            'operation_count' => 45,
            'tx_set_operation_count' => 45,
            'closed_at' => '2024-01-15T10:30:00Z',
            'total_coins' => '105443902087.3472865',
            'fee_pool' => '1871136.0723929',
            'base_fee_in_stroops' => 100,
            'base_reserve_in_stroops' => 5000000,
            'max_tx_set_size' => 1000,
            'protocol_version' => 20,
            'header_xdr' => 'AAAAFMnmDcKpxC/JHJ7wyBrQHcZO9v8utBqlG6hcD/Y79U0F'
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function operationRecord(): array
    {
        return [
            'id' => '123456789',
            'paging_token' => '123456789',
            'transaction_successful' => true,
            'source_account' => 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H',
            'type' => 'payment',
            'type_i' => 1,
            'created_at' => '2025-01-20T10:00:00Z',
            'transaction_hash' => 'abc123def456',
            'asset_type' => 'native',
            'from' => 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H',
            'to' => 'GBQECQVAS2FJ7DLCUXDASZAJQLWPXNTCR2FXSCTV2ATHWKIE6T7MKBU4',
            'amount' => '100.0000000',
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/operations/123456789'],
                'transaction' => ['href' => 'https://horizon-testnet.stellar.org/transactions/abc123def456'],
                'effects' => ['href' => 'https://horizon-testnet.stellar.org/operations/123456789/effects'],
                'succeeds' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=desc&cursor=123456789'],
                'precedes' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=asc&cursor=123456789']
            ]
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function paymentRecord(): array
    {
        return [
            'id' => '123456789',
            'paging_token' => '1234567890',
            'transaction_successful' => true,
            'source_account' => 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H',
            'type' => 'payment',
            'type_i' => 1,
            'created_at' => '2025-01-20T10:00:00Z',
            'transaction_hash' => 'abc123def456',
            'asset_type' => 'native',
            'from' => 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H',
            'to' => 'GBQECQVAS2FJ7DLCUXDASZAJQLWPXNTCR2FXSCTV2ATHWKIE6T7MKBU4',
            'amount' => '100.0000000',
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/operations/1234567890'],
                'transaction' => ['href' => 'https://horizon-testnet.stellar.org/transactions/abc123def456'],
                'effects' => ['href' => 'https://horizon-testnet.stellar.org/operations/1234567890/effects'],
                'succeeds' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=desc&cursor=1234567890'],
                'precedes' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=asc&cursor=1234567890']
            ]
        ];
    }

    /**
     * Returns a /payments record in the shape Horizon sends: the common
     * operation fields followed by the fields of the operation type.
     *
     * @param array<string, mixed> $details The fields of the operation type.
     * @return array<string, mixed>
     */
    private static function paymentsRecord(string $id, string $type, int $typeI, array $details): array
    {
        $url = self::HORIZON_URL . '/';
        return [
            '_links' => ['self' => ['href' => $url . 'operations/' . $id],
                'transaction' => ['href' => $url . 'transactions/' . self::TRANSACTION_HASH],
                'effects' => ['href' => $url . 'operations/' . $id . '/effects'],
                'succeeds' => ['href' => $url . 'effects?order=desc&cursor=' . $id],
                'precedes' => ['href' => $url . 'effects?order=asc&cursor=' . $id]],
            'id' => $id,
            'paging_token' => $id,
            'transaction_successful' => true,
            'source_account' => self::SENDER_ACCOUNT_ID,
            'type' => $type,
            'type_i' => $typeI,
            'created_at' => '2026-10-22T10:00:00Z',
            'transaction_hash' => self::TRANSACTION_HASH,
        ] + $details;
    }

    /**
     * @return array<string, mixed>
     */
    private static function transactionRecord(): array
    {
        return [
            'memo' => 'Test transaction',
            'memo_bytes' => 'VGVzdCB0cmFuc2FjdGlvbg==',
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/transactions/' . self::TRANSACTION_HASH],
                'account' => ['href' => 'https://horizon-testnet.stellar.org/accounts/' . self::TRANSACTION_ACCOUNT_ID],
                'ledger' => ['href' => 'https://horizon-testnet.stellar.org/ledgers/52429011'],
                'operations' => ['href' => 'https://horizon-testnet.stellar.org/transactions/' . self::TRANSACTION_HASH . '/operations{?cursor,limit,order}', 'templated' => true],
                'effects' => ['href' => 'https://horizon-testnet.stellar.org/transactions/' . self::TRANSACTION_HASH . '/effects{?cursor,limit,order}', 'templated' => true],
                'precedes' => ['href' => 'https://horizon-testnet.stellar.org/transactions?order=asc&cursor=225180887607500800'],
                'succeeds' => ['href' => 'https://horizon-testnet.stellar.org/transactions?order=desc&cursor=225180887607500800'],
                'transaction' => ['href' => 'https://horizon-testnet.stellar.org/transactions/' . self::TRANSACTION_HASH]
            ],
            'id' => self::TRANSACTION_HASH,
            'paging_token' => '225180887607500800',
            'successful' => true,
            'hash' => self::TRANSACTION_HASH,
            'ledger' => 52429011,
            'created_at' => '2024-07-05T05:51:31Z',
            'source_account' => 'GANGI6CEX7L52QPPH5MK2SDZE5WDESSO24HIMVHO5FCIGKQWKLAF5E7O',
            'source_account_sequence' => '224884019467125027',
            'fee_account' => 'GANGI6CEX7L52QPPH5MK2SDZE5WDESSO24HIMVHO5FCIGKQWKLAF5E7O',
            'fee_charged' => '100',
            'max_fee' => '100',
            'operation_count' => 1,
            'envelope_xdr' => 'AAAAAgAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAGQDHvLTAAABIwAAAAEAAAAAAAAAAAAAAABmh4pDAAAAAQAAABgwLDA3NSUgRGFpbHkgZm9yIEhvbGRlcnMAAAABAAAAAQAAAABDkt3qvAkFIBzwQNUTIuVYO6lakWIP/qYVmqhwqWJiJwAAAAEAAAAALKh2/uxMx/7OQG16N5OsdpWPl0ZGSeDIVpWQqsOV2wIAAAABSFVOAAAAAABiq8tUUivvOui45p7YvZkJysGWP0Yf8WEC8im2+3vr5AAAAABaG1GYAAAAAAAAAAKpYmInAAAAQLmth39Fjo8TC05wn5ZOAw4lou2rkxAaK6k16lHYXlEcsYHZ/d+ga5bCgO9KV/sbKaZAUCC9KvFIplXkXffBxQ0WUsBeAAAAQC2w45T3S24shkJ7uyRl/P5xD86Xfi7qTYxmb8uh8PEcwlb5oqbnJcTlUV2uJs2+gzMlijNtAbrCm6wO+1YsJQ4=',
            'result_xdr' => 'AAAAAAAAAGQAAAAAAAAAAQAAAAAAAAABAAAAAAAAAAA=',
            'result_meta_xdr' => 'AAAAAwAAAAAAAAACAAAAAwMgANMAAAAAAAAAABpkeES/191B7z9YrUh5J2wySk7XDoZU7ulEgyoWUsBeAAAAAAHJUdQDHvLTAAABIgAAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAgAAAAAAAAAAAAAAAAAAAAMAAAAAAyAAyAAAAABmh4mjAAAAAAAAAAEDIADTAAAAAAAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAAAByVHUAx7y0wAAASMAAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAAAAAAAAAAAAAAAAAADAAAAAAMgANMAAAAAZoeJ4wAAAAAAAAABAAAABAAAAAMDIADTAAAAAQAAAABDkt3qvAkFIBzwQNUTIuVYO6lakWIP/qYVmqhwqWJiJwAAAAFIVU4AAAAAAGKry1RSK+866Ljmnti9mQnKwZY/Rh/xYQLyKbb7e+vkAAKTCBRhynx//////////wAAAAEAAAABAAAA1xsdUZsAAAAAAAAAAAAAAAAAAAAAAAAAAQMgANMAAAABAAAAAEOS3eq8CQUgHPBA1RMi5Vg7qVqRYg/+phWaqHCpYmInAAAAAUhVTgAAAAAAYqvLVFIr7zrouOae2L2ZCcrBlj9GH/FhAvIptvt76+QAApMHukZ45H//////////AAAAAQAAAAEAAADXGx1RmwAAAAAAAAAAAAAAAAAAAAAAAAADAx/LTQAAAAEAAAAALKh2/uxMx/7OQG16N5OsdpWPl0ZGSeDIVpWQqsOV2wIAAAABSFVOAAAAAABiq8tUUivvOui45p7YvZkJysGWP0Yf8WEC8im2+3vr5AAAAdVOSfYYf/////////8AAAABAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEDIADTAAAAAQAAAAAsqHb+7EzH/s5AbXo3k6x2lY+XRkZJ4MhWlZCqw5XbAgAAAAFIVU4AAAAAAGKry1RSK+866Ljmnti9mQnKwZY/Rh/xYQLyKbb7e+vkAAAB1ahlR7B//////////wAAAAEAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
            'fee_meta_xdr' => 'AAAAAgAAAAMDIADIAAAAAAAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAAAByVI4Ax7y0wAAASIAAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAAAAAAAAAAAAAAAAAADAAAAAAMgAMgAAAAAZoeJowAAAAAAAAABAyAA0wAAAAAAAAAAGmR4RL/X3UHvP1itSHknbDJKTtcOhlTu6USDKhZSwF4AAAAAAclR1AMe8tMAAAEiAAAAAAAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAACAAAAAAAAAAAAAAAAAAAAAwAAAAADIADIAAAAAGaHiaMAAAAA',
            'memo_type' => 'text',
            'signatures' => [
                'ua2Hf0WOjxMLTnCflk4DDiWi7auTEBorqTXqUdheURyxgdn936BrlsKA70pX+xsppkBQIL0q8UimVeRd98HFDQ==',
                'LbDjlPdLbiyGQnu7JGX8/nEPzpd+LupNjGZvy6Hw8RzCVvmipuclxOVRXa4mzb6DMyWKM20BusKbrA77ViwlDg=='
            ],
            'valid_after' => '1970-01-01T00:00:00Z',
            'valid_before' => '2024-07-05T05:53:07Z',
            'preconditions' => [
                'timebounds' => [
                    'min_time' => '0',
                    'max_time' => '1720158787'
                ]
            ]
        ];
    }
}

/**
 * Thrown by the stream callbacks of RequestBuilderStreamEntryPointsTest to
 * leave the reconnect loop of RequestBuilder::getAndStream.
 */
final class StreamStoppedByTest extends RuntimeException
{
}
