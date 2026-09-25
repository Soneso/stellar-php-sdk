<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Core;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\AbstractTransaction;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\AccountMergeOperationBuilder;
use Soneso\StellarSDK\AllowTrustOperationBuilder;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\AccountRequiresMemoException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\FeeBumpTransaction;
use Soneso\StellarSDK\FeeBumpTransactionBuilder;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PathPaymentStrictReceiveOperationBuilder;
use Soneso\StellarSDK\PathPaymentStrictSendOperationBuilder;
use Soneso\StellarSDK\PaymentOperation;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Responses\Transaction\SubmitAsyncTransactionResponse;
use Soneso\StellarSDK\Responses\Transaction\SubmitTransactionResponse;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrEnvelopeType;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;

/**
 * Unit tests for the SEP-0029 memo requirement check of StellarSDK
 *
 * Covers the check that the four submit methods run before posting a transaction, the
 * AccountRequiresMemoException they raise, and the public checkMemoRequired() pre-check.
 * Horizon is replaced by a Guzzle mock queue that is consumed in order: a queue holding
 * fewer responses than the code requests fails the test, which is how these tests assert
 * that no account lookup happens. The requests the SDK sends are recorded, so tests can
 * also assert which requests were made and which envelope was posted.
 *
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0029.md
 */
class StellarSDKMemoRequiredTest extends TestCase
{
    private const TEST_TRANSACTION_HASH = 'a12b3c4d5e6f7890abcdef1234567890abcdef1234567890abcdef1234567890';

    /**
     * Requests sent since the latest createMockedSdk() call, by any SDK this test created, in order.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $requestHistory = [];

    /**
     * Creates an SDK whose http client answers from the given queue of responses.
     *
     * @param array<Response> $responses the responses to return, in request order.
     */
    private function createMockedSdk(array $responses): StellarSDK
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $this->requestHistory = [];
        $handlerStack->push(Middleware::history($this->requestHistory));
        $client = new Client(['handler' => $handlerStack]);

        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $sdk->setHttpClient($client);
        return $sdk;
    }

    /**
     * Builds a minimal account JSON response for the given account id.
     * Data values are base64-encoded, as Horizon returns them.
     *
     * @param array<string, string> $data raw (non-encoded) data entries.
     */
    private function accountJson(string $accountId, array $data = []): string
    {
        $encodedData = [];
        foreach ($data as $k => $v) {
            $encodedData[$k] = base64_encode($v);
        }
        return json_encode([
            '_links' => ['self' => ['href' => 'https://horizon-testnet.stellar.org/accounts/' . $accountId]],
            'id' => $accountId,
            'account_id' => $accountId,
            'sequence' => '123456789012',
            'subentry_count' => 0,
            'last_modified_ledger' => 1234567,
            'last_modified_time' => '2024-01-20T12:00:00Z',
            'thresholds' => ['low_threshold' => 0, 'med_threshold' => 0, 'high_threshold' => 0],
            'flags' => ['auth_required' => false, 'auth_revocable' => false, 'auth_immutable' => false, 'auth_clawback_enabled' => false],
            'balances' => [['balance' => '100.0000000', 'asset_type' => 'native']],
            'signers' => [['key' => $accountId, 'weight' => 1, 'type' => 'ed25519_public_key']],
            'data' => (object) $encodedData,
            'num_sponsoring' => 0,
            'num_sponsored' => 0,
            'paging_token' => $accountId,
        ]);
    }

    /**
     * Builds the account response Horizon returns for a destination account.
     *
     * @param array<string, string> $data raw (non-encoded) data entries.
     */
    private function accountResponse(string $accountId, array $data = []): Response
    {
        return new Response(200, [], $this->accountJson($accountId, $data));
    }

    /**
     * Builds the account response for a destination that requires a memo.
     */
    private function memoRequiredAccountResponse(string $accountId): Response
    {
        return $this->accountResponse($accountId, ['config.memo_required' => '1']);
    }

    /**
     * Helper method to get sample submit transaction JSON response
     */
    private function getSampleSubmitTransactionJson(): string
    {
        return json_encode([
            'hash' => self::TEST_TRANSACTION_HASH,
            'ledger' => 123456,
            'envelope_xdr' => 'AAAAAgAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAGQDHvLTAAABIwAAAAEAAAAAAAAAAAAAAABmh4pDAAAAAQAAABgwLDA3NSUgRGFpbHkgZm9yIEhvbGRlcnMAAAABAAAAAQAAAABDkt3qvAkFIBzwQNUTIuVYO6lakWIP/qYVmqhwqWJiJwAAAAEAAAAALKh2/uxMx/7OQG16N5OsdpWPl0ZGSeDIVpWQqsOV2wIAAAABSFVOAAAAAABiq8tUUivvOui45p7YvZkJysGWP0Yf8WEC8im2+3vr5AAAAABaG1GYAAAAAAAAAAKpYmInAAAAQLmth39Fjo8TC05wn5ZOAw4lou2rkxAaK6k16lHYXlEcsYHZ/d+ga5bCgO9KV/sbKaZAUCC9KvFIplXkXffBxQ0WUsBeAAAAQC2w45T3S24shkJ7uyRl/P5xD86Xfi7qTYxmb8uh8PEcwlb5oqbnJcTlUV2uJs2+gzMlijNtAbrCm6wO+1YsJQ4=',
            'result_xdr' => 'AAAAAAAAAGQAAAAAAAAAAQAAAAAAAAABAAAAAAAAAAA=',
        ]);
    }

    /**
     * Helper method to get sample submit async transaction JSON response
     */
    private function getSampleSubmitAsyncTransactionJson(): string
    {
        return json_encode([
            'tx_status' => 'PENDING',
            'hash' => self::TEST_TRANSACTION_HASH,
            'error_result_xdr' => null,
        ]);
    }

    /**
     * Builds the successful response of the synchronous submit endpoint.
     */
    private function submitResponse(): Response
    {
        return new Response(200, [], $this->getSampleSubmitTransactionJson());
    }

    /**
     * Builds the successful response of the asynchronous submit endpoint.
     */
    private function asyncSubmitResponse(): Response
    {
        return new Response(200, [], $this->getSampleSubmitAsyncTransactionJson());
    }

    /**
     * Builds the response Horizon returns for an account it does not know.
     */
    private function notFoundResponse(): Response
    {
        return new Response(404, [], '{"type":"https://stellar.org/horizon-errors/not_found","title":"Resource Missing","status":404,"detail":"The resource at the url requested was not found."}');
    }

    /**
     * Builds the response Horizon returns when it refuses to answer a request.
     */
    private function forbiddenResponse(): Response
    {
        return new Response(403, [], '{"type":"https://stellar.org/horizon-errors/forbidden","title":"Forbidden","status":403,"detail":"Forbidden."}');
    }

    /**
     * Builds the response Horizon returns for an envelope it cannot decode.
     */
    private function transactionMalformedResponse(): Response
    {
        return new Response(400, [], '{"type":"https://stellar.org/horizon-errors/transaction_malformed","title":"Transaction Malformed","status":400,"detail":"Horizon could not decode the transaction envelope in this request."}');
    }

    /**
     * Builds a memo-less transaction paying one native lumen amount to each given destination.
     */
    private function paymentTransaction(string $sourceAccountId, string ...$destinationAccountIds): Transaction
    {
        $builder = new TransactionBuilder(new Account($sourceAccountId, new BigInteger('123')));
        foreach ($destinationAccountIds as $destinationAccountId) {
            $builder->addOperation(
                (new PaymentOperationBuilder($destinationAccountId, Asset::native(), '10'))->build()
            );
        }
        return $builder->build();
    }

    /**
     * Builds a memo-less transaction whose three operations name three different destinations:
     * a strict send path payment to the first, a strict receive path payment to the second
     * and an account merge into the third.
     */
    private function mixedDestinationTransaction(string $sourceAccountId, string $first, string $second, string $third): Transaction
    {
        $builder = new TransactionBuilder(new Account($sourceAccountId, new BigInteger('123')));
        $builder->addOperation(
            (new PathPaymentStrictSendOperationBuilder(Asset::native(), '10', $first, Asset::native(), '9'))->build()
        );
        $builder->addOperation(
            (new PathPaymentStrictReceiveOperationBuilder(Asset::native(), '11', $second, Asset::native(), '10'))->build()
        );
        $builder->addOperation((new AccountMergeOperationBuilder($third))->build());
        return $builder->build();
    }

    /**
     * Wraps the given transaction in a fee bump transaction paid for by a random account.
     */
    private function feeBump(Transaction $inner): FeeBumpTransaction
    {
        return (new FeeBumpTransactionBuilder($inner))
            ->setBaseFee(200)
            ->setFeeAccount(KeyPair::random()->getAccountId())
            ->build();
    }

    /**
     * The message AccountRequiresMemoException carries for the given destination and operation.
     */
    private function expectedMessage(string $accountId, int $operationIndex): string
    {
        return 'Destination account ' . $accountId . ' of operation ' . $operationIndex
            . ' requires a memo in the transaction.';
    }

    /**
     * Asserts that the only request sent was one lookup of the given account.
     */
    private function assertOnlyAccountLookup(string $accountId): void
    {
        $this->assertCount(1, $this->requestHistory);
        $request = $this->requestHistory[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('accounts/' . $accountId, $request->getUri()->getPath());
    }

    /**
     * Asserts that the only request sent was the submission of the given envelope to the given path.
     *
     * @param string $path the request path relative to the Horizon base url, e.g. "transactions".
     */
    private function assertOnlySubmission(string $path, string $envelope): void
    {
        $this->assertCount(1, $this->requestHistory);
        $request = $this->requestHistory[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame($path, $request->getUri()->getPath());
        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(['tx' => $envelope], $query);
    }

    /**
     * Builds a fee bump envelope whose inner transaction type is ENVELOPE_TYPE_TX_V0 instead of
     * ENVELOPE_TYPE_TX. The fee bump inner transaction union has no arm for ENVELOPE_TYPE_TX_V0,
     * so the envelope does not decode.
     */
    private function feeBumpEnvelopeWithUnknownInnerType(): string
    {
        $inner = $this->paymentTransaction(KeyPair::random()->getAccountId(), KeyPair::random()->getAccountId());
        $bytes = base64_decode($this->feeBump($inner)->toEnvelopeXdrBase64());
        // Envelope type (4 bytes), non-muxed fee account (4 + 32) and fee (8) precede the inner transaction type.
        $innerTypeOffset = 48;
        $this->assertSame(pack('N', XdrEnvelopeType::ENVELOPE_TYPE_TX), substr($bytes, $innerTypeOffset, 4));
        // The unknown inner type carries no body; the fee bump extension (v0) and an empty signature list follow.
        return base64_encode(substr($bytes, 0, $innerTypeOffset)
            . pack('N', XdrEnvelopeType::ENVELOPE_TYPE_TX_V0)
            . pack('N', 0)
            . pack('N', 0));
    }

    /**
     * Builds a memo-less transaction holding one allow trust operation for the given asset code.
     */
    private function allowTrustTransaction(string $assetCode): Transaction
    {
        return (new TransactionBuilder(new Account(KeyPair::random()->getAccountId(), new BigInteger('123'))))
            ->addOperation((new AllowTrustOperationBuilder(KeyPair::random()->getAccountId(), $assetCode, true, false))->build())
            ->build();
    }

    /**
     * Builds an envelope whose allow trust operation names ASSET_TYPE_NATIVE instead of an
     * alphanumeric credit asset. The allow trust asset union has no arm for the native asset,
     * so the envelope does not decode.
     */
    private function allowTrustEnvelopeWithNativeAsset(): string
    {
        $bytes = base64_decode($this->allowTrustTransaction('ABC')->toEnvelopeXdrBase64());
        // Envelope type, source account (4 + 32), fee, sequence number (8), preconditions, memo,
        // operation count, operation source flag, operation type and trustor (4 + 32) precede the asset.
        $assetTypeOffset = 108;
        $this->assertSame(pack('N', XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4) . "ABC\x00", substr($bytes, $assetTypeOffset, 8));
        // The native asset carries no code, so its type replaces the type and the 4-byte code.
        return base64_encode(substr_replace($bytes, pack('N', XdrAssetType::ASSET_TYPE_NATIVE), $assetTypeOffset, 8));
    }

    /**
     * Asserts that the given call reports a memo requirement for the expected account and operation.
     *
     * @param callable $call the submission or check expected to raise the exception.
     */
    private function assertReportsMemoRequired(callable $call, string $expectedAccountId, int $expectedOperationIndex): void
    {
        try {
            $call();
            $this->fail('AccountRequiresMemoException was not thrown');
        } catch (AccountRequiresMemoException $e) {
            $this->assertSame($expectedAccountId, $e->getAccountId());
            $this->assertSame($expectedOperationIndex, $e->getOperationIndex());
            $this->assertSame($this->expectedMessage($expectedAccountId, $expectedOperationIndex), $e->getMessage());
        }
    }

    public function testSubmitTransactionThrowsWhenDestinationRequiresMemo(): void
    {
        $source = KeyPair::random()->getAccountId();
        $destination = KeyPair::random()->getAccountId();
        // The queue holds no submit response: posting the transaction would exhaust it and fail.
        $sdk = $this->createMockedSdk([
            $this->memoRequiredAccountResponse($destination),
        ]);
        $transaction = $this->paymentTransaction($source, $destination);

        $this->assertReportsMemoRequired(
            function () use ($sdk, $transaction) {
                $sdk->submitTransaction($transaction);
            },
            $destination,
            0
        );
    }

    public function testSubmitTransactionSubmitsWhenNoDestinationRequiresMemo(): void
    {
        $source = KeyPair::random()->getAccountId();
        $destination = KeyPair::random()->getAccountId();
        $sdk = $this->createMockedSdk([
            $this->accountResponse($destination),
            $this->submitResponse(),
        ]);

        $response = $sdk->submitTransaction($this->paymentTransaction($source, $destination));

        $this->assertInstanceOf(SubmitTransactionResponse::class, $response);
        $this->assertSame(self::TEST_TRANSACTION_HASH, $response->getHash());
    }

    public function testSubmitTransactionSubmitsWhenTheEntryHoldsAnotherValue(): void
    {
        $source = KeyPair::random()->getAccountId();
        $destination = KeyPair::random()->getAccountId();

        // The decoded value decides, not the presence of the key, and it has to be the
        // one-character string 1. Every value below differs from it as a string, while
        // '1.0', '01' and ' 1' all equal it once PHP compares them as numbers.
        foreach (['0', '1.0', '01', ' 1'] as $value) {
            $sdk = $this->createMockedSdk([
                $this->accountResponse($destination, ['config.memo_required' => $value]),
                $this->submitResponse(),
            ]);

            $response = $sdk->submitTransaction($this->paymentTransaction($source, $destination));

            $this->assertSame(self::TEST_TRANSACTION_HASH, $response->getHash());
        }
    }

    public function testSubmitTransactionSkipsCheckWhenAsked(): void
    {
        $source = KeyPair::random()->getAccountId();
        $destination = KeyPair::random()->getAccountId();
        // Only the submit response is queued: a lookup would consume it as an account body and fail.
        $sdk = $this->createMockedSdk([$this->submitResponse()]);

        $response = $sdk->submitTransaction(
            $this->paymentTransaction($source, $destination),
            skipMemoRequiredCheck: true
        );

        $this->assertSame(self::TEST_TRANSACTION_HASH, $response->getHash());
    }

    public function testSubmitTransactionMakesNoLookupWhenMemoPresent(): void
    {
        $source = KeyPair::random()->getAccountId();
        $destination = KeyPair::random()->getAccountId();

        // An id memo of 0 is a memo as much as a text memo is.
        foreach ([Memo::text('hello'), Memo::id(0)] as $memo) {
            $sdk = $this->createMockedSdk([$this->submitResponse()]);
            $builder = new TransactionBuilder(new Account($source, new BigInteger('123')));
            $builder->addOperation((new PaymentOperationBuilder($destination, Asset::native(), '10'))->build());
            $builder->addMemo($memo);

            $response = $sdk->submitTransaction($builder->build());

            $this->assertSame(self::TEST_TRANSACTION_HASH, $response->getHash());
        }
    }

    public function testSubmitTransactionMakesNoLookupWithoutDestinationOperations(): void
    {
        $source = KeyPair::random()->getAccountId();
        $newAccount = KeyPair::random()->getAccountId();
        $sdk = $this->createMockedSdk([$this->submitResponse()]);

        $builder = new TransactionBuilder(new Account($source, new BigInteger('123')));
        $builder->addOperation((new CreateAccountOperationBuilder($newAccount, '10'))->build());

        $response = $sdk->submitTransaction($builder->build());

        $this->assertSame(self::TEST_TRANSACTION_HASH, $response->getHash());
    }

    public function testSubmitTransactionReportsIndexOfTheOffendingOperation(): void
    {
        $source = KeyPair::random()->getAccountId();
        $accountA = KeyPair::random()->getAccountId();
        $newAccount = KeyPair::random()->getAccountId();
        $accountB = KeyPair::random()->getAccountId();

        $builder = new TransactionBuilder(new Account($source, new BigInteger('123')));
        // Operation 0 pays A, operation 1 creates an account and names no destination,
        // operation 2 pays B. The index counts all three.
        $builder->addOperation((new PaymentOperationBuilder($accountA, Asset::native(), '10'))->build());
        $builder->addOperation((new CreateAccountOperationBuilder($newAccount, '10'))->build());
        $builder->addOperation((new PaymentOperationBuilder($accountB, Asset::native(), '10'))->build());
        $transaction = $builder->build();

        $sdk = $this->createMockedSdk([
            $this->accountResponse($accountA),
            $this->memoRequiredAccountResponse($accountB),
        ]);

        $this->assertReportsMemoRequired(
            function () use ($sdk, $transaction) {
                $sdk->submitTransaction($transaction);
            },
            $accountB,
            2
        );
    }

    public function testSubmitTransactionChecksEveryQualifyingOperationType(): void
    {
        $source = KeyPair::random()->getAccountId();
        $accountA = KeyPair::random()->getAccountId();
        $accountB = KeyPair::random()->getAccountId();
        $accountC = KeyPair::random()->getAccountId();

        // Operation 0 path pays A strict send, operation 1 path pays B strict receive,
        // operation 2 merges into C.
        $sdk = $this->createMockedSdk([
            $this->accountResponse($accountA),
            $this->accountResponse($accountB),
            $this->memoRequiredAccountResponse($accountC),
        ]);
        $transaction = $this->mixedDestinationTransaction($source, $accountA, $accountB, $accountC);

        $this->assertReportsMemoRequired(
            function () use ($sdk, $transaction) {
                $sdk->submitTransaction($transaction);
            },
            $accountC,
            2
        );

        // The first destination already requires a memo, so exactly one lookup happens.
        $sdk = $this->createMockedSdk([
            $this->memoRequiredAccountResponse($accountA),
        ]);
        $transaction = $this->mixedDestinationTransaction($source, $accountA, $accountB, $accountC);

        $this->assertReportsMemoRequired(
            function () use ($sdk, $transaction) {
                $sdk->submitTransaction($transaction);
            },
            $accountA,
            0
        );
    }

    public function testSubmitTransactionQueriesRepeatedDestinationOnce(): void
    {
        $source = KeyPair::random()->getAccountId();
        $destination = KeyPair::random()->getAccountId();

        // Two operations pay the same account, but only one account response is queued:
        // a second lookup would read the submit body as an account body and fail.
        $sdk = $this->createMockedSdk([
            $this->accountResponse($destination),
            $this->submitResponse(),
        ]);

        $response = $sdk->submitTransaction($this->paymentTransaction($source, $destination, $destination));

        $this->assertSame(self::TEST_TRANSACTION_HASH, $response->getHash());

        // The first operation naming the account owns the reported index.
        $sdk = $this->createMockedSdk([
            $this->memoRequiredAccountResponse($destination),
        ]);
        $transaction = $this->paymentTransaction($source, $destination, $destination);

        $this->assertReportsMemoRequired(
            function () use ($sdk, $transaction) {
                $sdk->submitTransaction($transaction);
            },
            $destination,
            0
        );
    }

    public function testSubmitTransactionSkipsMuxedDestinations(): void
    {
        $source = KeyPair::random()->getAccountId();
        // A muxed destination carries its own multiplexing id, so no lookup happens.
        // An id of 0 is a valid multiplexing id.
        $sdk = $this->createMockedSdk([$this->submitResponse()]);

        $builder = new TransactionBuilder(new Account($source, new BigInteger('123')));
        foreach ([0, 1234] as $muxedId) {
            $destination = new MuxedAccount(KeyPair::random()->getAccountId(), $muxedId);
            $builder->addOperation(new PaymentOperation($destination, Asset::native(), '10'));
        }

        $response = $sdk->submitTransaction($builder->build());

        $this->assertSame(self::TEST_TRANSACTION_HASH, $response->getHash());
    }

    public function testSubmitTransactionCountsSkippedMuxedDestinationsInTheOperationIndex(): void
    {
        $source = KeyPair::random()->getAccountId();
        $accountB = KeyPair::random()->getAccountId();

        // Operation 0 pays a muxed destination and is looked up for nobody, operation 1
        // pays B. The index counts both operations, so B is reported as operation 1.
        $builder = new TransactionBuilder(new Account($source, new BigInteger('123')));
        $builder->addOperation(
            new PaymentOperation(new MuxedAccount(KeyPair::random()->getAccountId(), 0), Asset::native(), '10')
        );
        $builder->addOperation((new PaymentOperationBuilder($accountB, Asset::native(), '10'))->build());
        $transaction = $builder->build();

        $sdk = $this->createMockedSdk([
            $this->memoRequiredAccountResponse($accountB),
        ]);

        $this->assertReportsMemoRequired(
            function () use ($sdk, $transaction) {
                $sdk->submitTransaction($transaction);
            },
            $accountB,
            1
        );
    }

    public function testSubmitTransactionChecksInnerTransactionOfFeeBump(): void
    {
        $source = KeyPair::random()->getAccountId();
        $destination = KeyPair::random()->getAccountId();

        $sdk = $this->createMockedSdk([
            $this->memoRequiredAccountResponse($destination),
        ]);
        $feeBump = $this->feeBump($this->paymentTransaction($source, $destination));

        $this->assertReportsMemoRequired(
            function () use ($sdk, $feeBump) {
                $sdk->submitTransaction($feeBump);
            },
            $destination,
            0
        );

        // The memo of the inner transaction is the memo the check reads.
        $sdk = $this->createMockedSdk([$this->submitResponse()]);
        $builder = new TransactionBuilder(new Account($source, new BigInteger('123')));
        $builder->addOperation((new PaymentOperationBuilder($destination, Asset::native(), '10'))->build());
        $builder->addMemo(Memo::text('hello'));

        $response = $sdk->submitTransaction($this->feeBump($builder->build()));

        $this->assertSame(self::TEST_TRANSACTION_HASH, $response->getHash());
    }

    public function testSubmitTransactionSkipsDestinationHorizonDoesNotKnow(): void
    {
        $source = KeyPair::random()->getAccountId();
        $destination = KeyPair::random()->getAccountId();

        $sdk = $this->createMockedSdk([
            $this->notFoundResponse(),
            $this->submitResponse(),
        ]);

        $response = $sdk->submitTransaction($this->paymentTransaction($source, $destination));

        $this->assertSame(self::TEST_TRANSACTION_HASH, $response->getHash());

        // Operation 0 pays an account Horizon does not know, operation 1 pays an account
        // that requires a memo.
        $accountA = KeyPair::random()->getAccountId();
        $accountB = KeyPair::random()->getAccountId();
        $sdk = $this->createMockedSdk([
            $this->notFoundResponse(),
            $this->memoRequiredAccountResponse($accountB),
        ]);
        $transaction = $this->paymentTransaction($source, $accountA, $accountB);

        $this->assertReportsMemoRequired(
            function () use ($sdk, $transaction) {
                $sdk->submitTransaction($transaction);
            },
            $accountB,
            1
        );
    }

    public function testSubmitTransactionPropagatesOtherLookupErrors(): void
    {
        $source = KeyPair::random()->getAccountId();
        $destination = KeyPair::random()->getAccountId();
        $sdk = $this->createMockedSdk([$this->forbiddenResponse()]);
        $transaction = $this->paymentTransaction($source, $destination);

        try {
            $sdk->submitTransaction($transaction);
            $this->fail('HorizonRequestException was not thrown');
        } catch (HorizonRequestException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function testSubmitAsyncTransactionThrowsWhenDestinationRequiresMemo(): void
    {
        $source = KeyPair::random()->getAccountId();
        $destination = KeyPair::random()->getAccountId();
        $sdk = $this->createMockedSdk([
            $this->memoRequiredAccountResponse($destination),
        ]);
        $transaction = $this->paymentTransaction($source, $destination);

        $this->assertReportsMemoRequired(
            function () use ($sdk, $transaction) {
                $sdk->submitAsyncTransaction($transaction);
            },
            $destination,
            0
        );
    }

    public function testSubmitAsyncTransactionSkipsCheckWhenAsked(): void
    {
        $source = KeyPair::random()->getAccountId();
        $destination = KeyPair::random()->getAccountId();
        $sdk = $this->createMockedSdk([$this->asyncSubmitResponse()]);

        $response = $sdk->submitAsyncTransaction(
            $this->paymentTransaction($source, $destination),
            skipMemoRequiredCheck: true
        );

        $this->assertInstanceOf(SubmitAsyncTransactionResponse::class, $response);
        $this->assertSame(self::TEST_TRANSACTION_HASH, $response->hash);
        $this->assertSame('PENDING', $response->txStatus);
    }

    public function testSubmitTransactionEnvelopeXdrBase64ThrowsWhenDestinationRequiresMemo(): void
    {
        $source = KeyPair::random()->getAccountId();
        $destination = KeyPair::random()->getAccountId();
        $sdk = $this->createMockedSdk([
            $this->memoRequiredAccountResponse($destination),
        ]);
        $envelope = $this->paymentTransaction($source, $destination)->toEnvelopeXdrBase64();

        $this->assertReportsMemoRequired(
            function () use ($sdk, $envelope) {
                $sdk->submitTransactionEnvelopeXdrBase64($envelope);
            },
            $destination,
            0
        );
        $this->assertOnlyAccountLookup($destination);
    }

    public function testSubmitTransactionEnvelopeXdrBase64SkipsCheckWhenAsked(): void
    {
        $source = KeyPair::random()->getAccountId();
        $destination = KeyPair::random()->getAccountId();
        $sdk = $this->createMockedSdk([$this->submitResponse()]);
        $envelope = $this->paymentTransaction($source, $destination)->toEnvelopeXdrBase64();

        $response = $sdk->submitTransactionEnvelopeXdrBase64($envelope, skipMemoRequiredCheck: true);

        $this->assertSame(self::TEST_TRANSACTION_HASH, $response->getHash());
        $this->assertOnlySubmission('transactions', $envelope);
    }

    public function testSubmitTransactionEnvelopeXdrBase64ChecksInnerTransactionOfFeeBumpEnvelope(): void
    {
        $source = KeyPair::random()->getAccountId();
        $destination = KeyPair::random()->getAccountId();
        $sdk = $this->createMockedSdk([
            $this->memoRequiredAccountResponse($destination),
        ]);
        $envelope = $this->feeBump($this->paymentTransaction($source, $destination))->toEnvelopeXdrBase64();

        $this->assertReportsMemoRequired(
            function () use ($sdk, $envelope) {
                $sdk->submitTransactionEnvelopeXdrBase64($envelope);
            },
            $destination,
            0
        );
        $this->assertOnlyAccountLookup($destination);
    }

    public function testSubmitAsyncTransactionEnvelopeXdrBase64ThrowsWhenDestinationRequiresMemo(): void
    {
        $source = KeyPair::random()->getAccountId();
        $destination = KeyPair::random()->getAccountId();
        $sdk = $this->createMockedSdk([
            $this->memoRequiredAccountResponse($destination),
        ]);
        $envelope = $this->paymentTransaction($source, $destination)->toEnvelopeXdrBase64();

        $this->assertReportsMemoRequired(
            function () use ($sdk, $envelope) {
                $sdk->submitAsyncTransactionEnvelopeXdrBase64($envelope);
            },
            $destination,
            0
        );
        $this->assertOnlyAccountLookup($destination);
    }

    public function testSubmitAsyncTransactionEnvelopeXdrBase64SkipsCheckWhenAsked(): void
    {
        $source = KeyPair::random()->getAccountId();
        $destination = KeyPair::random()->getAccountId();
        $sdk = $this->createMockedSdk([$this->asyncSubmitResponse()]);
        $envelope = $this->paymentTransaction($source, $destination)->toEnvelopeXdrBase64();

        $response = $sdk->submitAsyncTransactionEnvelopeXdrBase64($envelope, skipMemoRequiredCheck: true);

        $this->assertSame(self::TEST_TRANSACTION_HASH, $response->hash);
        $this->assertOnlySubmission('transactions_async', $envelope);
    }

    public function testFeeBumpEnvelopeWithUnknownInnerTypeIsUndecodable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown XdrFeeBumpTransactionInnerTx discriminant: ' . XdrEnvelopeType::ENVELOPE_TYPE_TX_V0);

        AbstractTransaction::fromEnvelopeBase64XdrString($this->feeBumpEnvelopeWithUnknownInnerType());
    }

    public function testSubmitTransactionEnvelopeXdrBase64SubmitsUndecodableEnvelopeUnchecked(): void
    {
        foreach ($this->undecodableEnvelopes() as $envelope) {
            $sdk = $this->createMockedSdk([$this->transactionMalformedResponse()]);

            try {
                $sdk->submitTransactionEnvelopeXdrBase64($envelope);
                $this->fail('HorizonRequestException was not thrown');
            } catch (HorizonRequestException $e) {
                $this->assertSame(400, $e->getStatusCode());
            }
            $this->assertOnlySubmission('transactions', $envelope);
        }
    }

    public function testSubmitAsyncTransactionEnvelopeXdrBase64SubmitsUndecodableEnvelopeUnchecked(): void
    {
        foreach ($this->undecodableEnvelopes() as $envelope) {
            $sdk = $this->createMockedSdk([$this->transactionMalformedResponse()]);

            try {
                $sdk->submitAsyncTransactionEnvelopeXdrBase64($envelope);
                $this->fail('HorizonRequestException was not thrown');
            } catch (HorizonRequestException $e) {
                $this->assertSame(400, $e->getStatusCode());
            }
            $this->assertOnlySubmission('transactions_async', $envelope);
        }
    }

    /**
     * Envelope strings the SDK cannot decode: invalid base64, bytes that are no envelope, a
     * truncated envelope, a fee bump envelope with an unknown inner transaction type, and an
     * allow trust operation whose asset is not an alphanumeric credit asset.
     *
     * @return array<string>
     */
    private function undecodableEnvelopes(): array
    {
        return [
            'not*base64',
            base64_encode('garbage'),
            base64_encode("\x00\x00\x00"),
            $this->feeBumpEnvelopeWithUnknownInnerType(),
            $this->allowTrustEnvelopeWithNativeAsset(),
        ];
    }

    public function testSubmitTransactionEnvelopeXdrBase64SubmitsAllowTrustForAssetCodeZero(): void
    {
        $envelope = $this->allowTrustTransaction('0')->toEnvelopeXdrBase64();
        $sdk = $this->createMockedSdk([$this->submitResponse()]);

        $response = $sdk->submitTransactionEnvelopeXdrBase64($envelope);

        $this->assertSame(self::TEST_TRANSACTION_HASH, $response->getHash());
        $this->assertOnlySubmission('transactions', $envelope);
    }

    public function testCheckMemoRequiredChecksInnerTransactionOfFeeBump(): void
    {
        $source = KeyPair::random()->getAccountId();
        $destination = KeyPair::random()->getAccountId();
        $sdk = $this->createMockedSdk([
            $this->memoRequiredAccountResponse($destination),
        ]);

        $result = $sdk->checkMemoRequired($this->feeBump($this->paymentTransaction($source, $destination)));

        $this->assertSame($destination, $result);
    }

    public function testCheckMemoRequiredSkipsDestinationHorizonDoesNotKnow(): void
    {
        $source = KeyPair::random()->getAccountId();
        $accountA = KeyPair::random()->getAccountId();
        $accountB = KeyPair::random()->getAccountId();
        $sdk = $this->createMockedSdk([
            $this->notFoundResponse(),
            $this->memoRequiredAccountResponse($accountB),
        ]);

        $result = $sdk->checkMemoRequired($this->paymentTransaction($source, $accountA, $accountB));

        $this->assertSame($accountB, $result);

        // A single destination Horizon does not know leaves nothing to report.
        $sdk = $this->createMockedSdk([$this->notFoundResponse()]);

        $this->assertFalse($sdk->checkMemoRequired($this->paymentTransaction($source, $accountA)));
    }

    public function testCheckMemoRequiredReturnsTheAccountTheSubmitExceptionCarries(): void
    {
        $source = KeyPair::random()->getAccountId();
        $accountA = KeyPair::random()->getAccountId();
        $accountB = KeyPair::random()->getAccountId();

        $checkSdk = $this->createMockedSdk([
            $this->accountResponse($accountA),
            $this->memoRequiredAccountResponse($accountB),
        ]);
        $submitSdk = $this->createMockedSdk([
            $this->accountResponse($accountA),
            $this->memoRequiredAccountResponse($accountB),
        ]);

        $result = $checkSdk->checkMemoRequired($this->paymentTransaction($source, $accountA, $accountB));

        try {
            $submitSdk->submitTransaction($this->paymentTransaction($source, $accountA, $accountB));
            $this->fail('AccountRequiresMemoException was not thrown');
        } catch (AccountRequiresMemoException $e) {
            $this->assertSame($e->getAccountId(), $result);
            $this->assertSame($accountB, $e->getAccountId());
            $this->assertSame(1, $e->getOperationIndex());
        }
    }

    public function testCheckMemoRequiredReturnsFalseForOtherTransactionTypes(): void
    {
        // Empty mock queue: a transaction that is neither a Transaction nor a fee bump
        // around one holds no operations the check can read, so no lookup must happen.
        $sdk = $this->createMockedSdk([]);

        $transaction = new class extends AbstractTransaction {
            public function signatureBase(Network $network) : string {
                throw new \LogicException('the memo required check does not sign');
            }

            public function toEnvelopeXdr() : XdrTransactionEnvelope {
                throw new \LogicException('the memo required check does not encode');
            }
        };

        $this->assertFalse($sdk->checkMemoRequired($transaction));
    }

    public function testAccountRequiresMemoExceptionCarriesAccountAndIndex(): void
    {
        $accountId = KeyPair::random()->getAccountId();

        $exception = new AccountRequiresMemoException($accountId, 3);

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame($accountId, $exception->getAccountId());
        $this->assertSame(3, $exception->getOperationIndex());
        $this->assertSame(
            'Destination account ' . $accountId . ' of operation 3 requires a memo in the transaction.',
            $exception->getMessage()
        );
    }
}
