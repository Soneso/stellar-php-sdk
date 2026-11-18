<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\SEP\WebAuth;

use DateTime;
use ErrorException;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use InvalidArgumentException;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Response;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\ManageDataOperation;
use Soneso\StellarSDK\ManageDataOperationBuilder;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperation;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeRequestErrorResponse;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeRequestBuilder;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeResponse;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidHomeDomain;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidMemoType;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidMemoValue;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidOperationType;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidSeqNr;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidSignature;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidSourceAccount;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidTimeBounds;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidWebAuthDomain;
use Soneso\StellarSDK\SEP\WebAuth\SubmitCompletedChallengeErrorResponseException;
use Soneso\StellarSDK\SEP\WebAuth\SubmitCompletedChallengeTimeoutResponseException;
use Soneso\StellarSDK\SEP\WebAuth\SubmitCompletedChallengeUnknownResponseException;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;
use Soneso\StellarSDK\TimeBounds;
use Soneso\StellarSDK\TransactionBuilder;


class WebAuthTest extends TestCase
{
    private string $domain = "place.domain.com";
    private string $authServer = "https://api.stellar.org/auth";
    private string $serverAccountId = "GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP";
    private string $serverSecretSeed = "SAWDHXQG6ROJSU4QGCW7NSTYFHPTPIVC2NC7QKVTO7PZCSO2WEBGM54W";
    private KeyPair $serverKeyPair;
    private string $clientAccountId = "GB4L7JUU5DENUXYH3ANTLVYQL66KQLDDJTN5SF7MWEDGWSGUA375V44V";
    private string $clientSecretSeed = "SBAYNYLQFXVLVAHW4BXDQYNJLMDQMZ5NQDDOHVJD3PTBAUIJRNRK5LGX";
    private string $clientAccountIdM = "MB4L7JUU5DENUXYH3ANTLVYQL66KQLDDJTN5SF7MWEDGWSGUA375UAAAAAAACMICQP7P4";
    private int $testMemo = 19989123;
    private string $wrongServerSecretSeed = "SAT4GUGO2N7RVVVD2TSL7TZ6T5A6PM7PJD5NUGQI5DDH67XO4KNO2QOW";
    private string $successJWTToken = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJHQTZVSVhYUEVXWUZJTE5VSVdBQzM3WTRRUEVaTVFWREpIREtWV0ZaSjJLQ1dVQklVNUlYWk5EQSIsImp0aSI6IjE0NGQzNjdiY2IwZTcyY2FiZmRiZGU2MGVhZTBhZDczM2NjNjVkMmE2NTg3MDgzZGFiM2Q2MTZmODg1MTkwMjQiLCJpc3MiOiJodHRwczovL2ZsYXBweS1iaXJkLWRhcHAuZmlyZWJhc2VhcHAuY29tLyIsImlhdCI6MTUzNDI1Nzk5NCwiZXhwIjoxNTM0MzQ0Mzk0fQ.8nbB83Z6vGBgC1X9r3N6oQCFTBzDiITAfCJasRft0z0";

    protected function setUp() : void {
        $this->serverKeyPair = KeyPair::fromSeed($this->serverSecretSeed);
    }

    private function validFirstManageDataOp(string $accountId) : ManageDataOperation {
        $muxedAccount = MuxedAccount::fromAccountId($accountId);
        $builder = (new ManageDataOperationBuilder($this->domain . " auth", random_bytes(64)))->setMuxedSourceAccount($muxedAccount);
        return $builder->build();
    }

    private function validSecondManageDataOp() : ManageDataOperation {
        $builder = (new ManageDataOperationBuilder("web_auth_domain", "api.stellar.org"))->setSourceAccount($this->serverAccountId);
        return $builder->build();
    }

    private function invalidWebAuthOp() : ManageDataOperation {
        $builder = (new ManageDataOperationBuilder("web_auth_domain", "api.fake.org"))->setSourceAccount($this->serverAccountId);
        return $builder->build();
    }

    private function secondManageDataOpInvalidSourceAccount() : ManageDataOperation {
        $builder = (new ManageDataOperationBuilder("web_auth_domain", "api.stellar.org"))->setSourceAccount($this->clientAccountId);
        return $builder->build();
    }

    private function invalidClientDomainManageDataOp() : ManageDataOperation {
        $builder = (new ManageDataOperationBuilder("client_domain", "place.client.com"))->setSourceAccount($this->serverAccountId);
        return $builder->build();
    }

    private function validClientDomainManageDataOp(string $clientDomainAccountId) : ManageDataOperation {
        $builder = (new ManageDataOperationBuilder("client_domain", "place.client.com"))->setSourceAccount($clientDomainAccountId);
        return $builder->build();
    }

    private function memoForId(int|string|null $id = null) : Memo {
        if ($id !== null) {
            return Memo::id($id);
        }
        return Memo::none();
    }

    /**
     * Builds a WebAuth backed by the given queued responses and records every outgoing
     * request in $history, so tests can assert on the challenge request parameters.
     *
     * @param array<int, Response> $responses
     * @param array<int, array<string, mixed>> $history
     */
    private function webAuthRecordingRequests(array $responses, array &$history) : WebAuth {
        $handlerStack = HandlerStack::create(new MockHandler($responses));
        $handlerStack->push(Middleware::history($history));
        return new WebAuth(
            $this->authServer,
            $this->serverAccountId,
            $this->domain,
            Network::testnet(),
            new Client(['handler' => $handlerStack]),
        );
    }

    /**
     * Returns the parsed query parameters of the recorded challenge request.
     *
     * @param array<int, array<string, mixed>> $history
     * @return array<string, mixed>
     */
    private function challengeRequestQuery(array $history) : array {
        $this->assertNotEmpty($history);
        parse_str($history[0]['request']->getUri()->getQuery(), $query);
        return $query;
    }

    private function validTimeBounds() : TimeBounds {
        return new TimeBounds((new DateTime)->setTimestamp(time()), (new DateTime)->setTimestamp(time() + 3));
    }

    private function invalidTimeBounds() : TimeBounds {
        return new TimeBounds((new DateTime)->setTimestamp(time() - 700),
            (new DateTime)->setTimestamp(time() - 400));
    }

    private function invalidHomeDomainOp(string $accountId) : ManageDataOperation {
        $muxedAccount = MuxedAccount::fromAccountId($accountId);
        $builder = (new ManageDataOperationBuilder("fake.com" . " auth", random_bytes(64)))->setMuxedSourceAccount($muxedAccount);
        return $builder->build();
    }

    private function invalidOperationType(string $accountId) : PaymentOperation {
        $muxedAccount = MuxedAccount::fromAccountId($accountId);
        $builder = (new PaymentOperationBuilder($this->serverAccountId, Asset::native(), "20.0"))->setMuxedSourceAccount($muxedAccount);
        return $builder->build();
    }

    private function requestChallengeSuccess(string $accountId, int|string|null $memo = null) : string {
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
                ->addOperation($this::validFirstManageDataOp($accountId))
                ->addOperation($this::validSecondManageDataOp())
                ->addMemo($this::memoForId($memo))
                ->setTimeBounds($this::validTimeBounds())
                ->build();
        $transaction->sign($this->serverKeyPair, Network::testnet());
        return json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);
    }

    private function requestChallengeInvalidMemoType(string $accountId) : string {
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
            ->addOperation($this::validFirstManageDataOp($accountId))
            ->addOperation($this::validSecondManageDataOp())
            ->addMemo(Memo::text("blue sky"))
            ->setTimeBounds($this::validTimeBounds())
            ->build();
        $transaction->sign($this->serverKeyPair, Network::testnet());
        return json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);
    }

    private function requestChallengeInvalidMemoValue(string $accountId) : string {
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
            ->addOperation($this::validFirstManageDataOp($accountId))
            ->addOperation($this::validSecondManageDataOp())
            ->addMemo($this::memoForId($this->testMemo - 200))
            ->setTimeBounds($this::validTimeBounds())
            ->build();
        $transaction->sign($this->serverKeyPair, Network::testnet());
        return json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);
    }

    private function requestChallengeInvalidSequenceNumber(string $accountId) : string {
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(29299292));
        $transaction = (new TransactionBuilder($transactionAccount))
            ->addOperation($this::validFirstManageDataOp($accountId))
            ->addOperation($this::validSecondManageDataOp())
            ->addMemo(Memo::none())
            ->setTimeBounds($this::validTimeBounds())
            ->build();
        $transaction->sign($this->serverKeyPair, Network::testnet());
        return json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);
    }

    private function requestChallengeInvalidFirstOpSourceAccount() : string {
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
            ->addOperation($this::validFirstManageDataOp($this->serverAccountId))
            ->addOperation($this::validSecondManageDataOp())
            ->addMemo(Memo::none())
            ->setTimeBounds($this::validTimeBounds())
            ->build();
        $transaction->sign($this->serverKeyPair, Network::testnet());
        return json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);
    }

    private function requestChallengeInvalidSecondOpSourceAccount(string $accountId) : string {
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
            ->addOperation($this::validFirstManageDataOp($accountId))
            ->addOperation($this::secondManageDataOpInvalidSourceAccount())
            ->addMemo(Memo::none())
            ->setTimeBounds($this::validTimeBounds())
            ->build();
        $transaction->sign($this->serverKeyPair, Network::testnet());
        return json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);
    }

    private function requestChallengeInvalidHomeDomain(string $accountId) : string {
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
            ->addOperation($this::invalidHomeDomainOp($accountId))
            ->addOperation($this::validSecondManageDataOp())
            ->addMemo(Memo::none())
            ->setTimeBounds($this::validTimeBounds())
            ->build();
        $transaction->sign($this->serverKeyPair, Network::testnet());
        return json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);
    }

    private function requestChallengeInvalidWebAuth(string $accountId) : string {
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
            ->addOperation($this::validFirstManageDataOp($accountId))
            ->addOperation($this::invalidWebAuthOp())
            ->addMemo(Memo::none())
            ->setTimeBounds($this::validTimeBounds())
            ->build();
        $transaction->sign($this->serverKeyPair, Network::testnet());
        return json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);
    }

    private function requestChallengeInvalidTimeBounds(string $accountId) : string {
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
            ->addOperation($this::validFirstManageDataOp($accountId))
            ->addOperation($this::validSecondManageDataOp())
            ->addMemo(Memo::none())
            ->setTimeBounds($this::invalidTimeBounds())
            ->build();
        $transaction->sign($this->serverKeyPair, Network::testnet());
        return json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);
    }

    private function requestChallengeMissingTimeBounds(string $accountId) : string {
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
            ->addOperation($this::validFirstManageDataOp($accountId))
            ->addOperation($this::validSecondManageDataOp())
            ->addMemo(Memo::none())
            ->build();
        $transaction->sign($this->serverKeyPair, Network::testnet());
        return json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);
    }

    private function infiniteTimeBounds() : TimeBounds {
        return new TimeBounds((new DateTime)->setTimestamp(time()), (new DateTime)->setTimestamp(0));
    }

    private function requestChallengeInfiniteTimeBounds(string $accountId) : string {
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
            ->addOperation($this::validFirstManageDataOp($accountId))
            ->addOperation($this::validSecondManageDataOp())
            ->addMemo(Memo::none())
            ->setTimeBounds($this::infiniteTimeBounds())
            ->build();
        $transaction->sign($this->serverKeyPair, Network::testnet());
        return json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);
    }

    private function requestChallengeInvalidOperationType(string $accountId) : string {
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
            ->addOperation($this::validFirstManageDataOp($accountId))
            ->addOperation($this::validSecondManageDataOp())
            ->addOperation($this::invalidOperationType($accountId))
            ->addMemo(Memo::none())
            ->setTimeBounds($this::validTimeBounds())
            ->build();
        $transaction->sign($this->serverKeyPair, Network::testnet());
        return json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);
    }

    private function requestChallengeInvalidSignature(string $accountId) : string {
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
            ->addOperation($this::validFirstManageDataOp($accountId))
            ->addOperation($this::validSecondManageDataOp())
            ->addMemo(Memo::none())
            ->setTimeBounds($this::validTimeBounds())
            ->build();
        $kp = KeyPair::fromSeed($this->wrongServerSecretSeed);
        $transaction->sign($kp, Network::testnet());
        return json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);
    }

    private function requestChallengeMultipleSignature(string $accountId) : string {
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
            ->addOperation($this::validFirstManageDataOp($accountId))
            ->addOperation($this::validSecondManageDataOp())
            ->addMemo(Memo::none())
            ->setTimeBounds($this::validTimeBounds())
            ->build();
        $transaction->sign($this->serverKeyPair, Network::testnet());
        $kp = KeyPair::fromSeed($this->wrongServerSecretSeed);
        $transaction->sign($kp, Network::testnet());
        return json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);
    }

    private function requestChallengeInvalidClientDomainOpSourceAccount(string $accountId) : string {
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
            ->addOperation($this::validFirstManageDataOp($accountId))
            ->addOperation($this::validSecondManageDataOp())
            ->addOperation($this::invalidClientDomainManageDataOp())
            ->addMemo(Memo::none())
            ->setTimeBounds($this::validTimeBounds())
            ->build();
        $transaction->sign($this->serverKeyPair, Network::testnet());
        return json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);
    }

    private function requestChallengeValidClientDomainOpSourceAccount(string $accountId, string $clientDomainAccountId) : string {
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
            ->addOperation($this::validFirstManageDataOp($accountId))
            ->addOperation($this::validSecondManageDataOp())
            ->addOperation($this::validClientDomainManageDataOp($clientDomainAccountId))
            ->addMemo(Memo::none())
            ->setTimeBounds($this::validTimeBounds())
            ->build();
        $transaction->sign($this->serverKeyPair, Network::testnet());
        return json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);
    }

    private function requestJWTSuccess() : string{
        return json_encode(['token' => $this->successJWTToken]);
    }

    private function requestJWTError() : string{
        return json_encode(['error' => 'The provided transaction is not valid']);
    }

    public function testDefaultSuccess(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeSuccess($this->clientAccountId, 200)),
            new Response(200, ['X-Foo' => 'Bar'], $this->requestJWTSuccess())
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        $this->assertEquals($this->successJWTToken, $jwtToken);
    }

    public function testMemoSuccess(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeSuccess($this->clientAccountId, $this->testMemo)),
            new Response(200, ['X-Foo' => 'Bar'], $this->requestJWTSuccess())
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair], $this->testMemo);
        $this->assertEquals($this->successJWTToken, $jwtToken);
    }

    /** @return array<array{int|string}> */
    public static function memoIdValues(): array {
        return [[PHP_INT_MAX], ['0'], ['19989123'],
            ['18446744073709551615'], ['9223372036854775808']];
    }

    #[DataProvider('memoIdValues')]
    public function testUnsignedMemoIsSentAndValidated(int|string $memo): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $this->requestChallengeSuccess($this->clientAccountId, $memo)),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);

        $this->assertSame($this->successJWTToken,
            $webAuth->jwtToken($this->clientAccountId, [$userKeyPair], $memo));
        $this->assertSame((string)$memo, $this->challengeRequestQuery($history)['memo']);
        $this->assertCount(2, $history);
    }

    /** @return array<array{string, string|null}> */
    public static function mismatchedUnsignedMemos(): array {
        return [
            ['18446744073709551615', '9223372036854775808'],
            ['9223372036854775808', '18446744073709551615'],
            ['18446744073709551615', null],
            ['9223372036854775808', null],
        ];
    }

    #[DataProvider('mismatchedUnsignedMemos')]
    public function testUnsignedMemoMismatchRejected(string $memo, ?string $challengeMemo): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $this->requestChallengeSuccess($this->clientAccountId, $challengeMemo)),
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);

        try {
            $webAuth->jwtToken($this->clientAccountId, [$userKeyPair], $memo);
            $this->fail('Expected a memo mismatch');
        } catch (ChallengeValidationErrorInvalidMemoValue $e) {
            $this->assertSame($challengeMemo === null ? 'missing memo' : 'invalid memo value', $e->getMessage());
        }
        $this->assertSame($memo, $this->challengeRequestQuery($history)['memo']);
        $this->assertCount(1, $history);
    }

    /** @return array<array{int|string, string}> */
    public static function invalidMemoIds(): array {
        return [
            [-1, 'value cannot be negative'],
            ['abc', 'id memo value must be an unsigned decimal integer'],
            ['18446744073709551616', 'value cannot be larger than 18446744073709551615'],
        ];
    }

    #[DataProvider('invalidMemoIds')]
    public function testInvalidMemoRejectedBeforeRequest(int|string $memo, string $reason): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $this->requestChallengeSuccess($this->clientAccountId)),
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);

        try {
            $webAuth->jwtToken($this->clientAccountId, [$userKeyPair], $memo);
            $this->fail('Expected an invalid memo');
        } catch (InvalidArgumentException $e) {
            $this->assertSame("Invalid memo id '$memo': $reason", $e->getMessage());
        }
        $this->assertSame([], $history);
    }

    #[DataProvider('memoIdValues')]
    public function testForMemoUnsignedDecimalQuery(int|string $memo): void {
        $builder = new ChallengeRequestBuilder($this->authServer, new Client());
        $this->assertSame($builder, $builder->forMemo($memo));
        $this->assertSame($this->authServer . '?memo=' . $memo, $builder->buildUrl());
    }

    #[DataProvider('invalidMemoIds')]
    public function testForMemoRejectsInvalidId(int|string $memo, string $reason): void {
        $builder = new ChallengeRequestBuilder($this->authServer, new Client());
        try {
            $builder->forMemo($memo);
            $this->fail('Expected an invalid memo');
        } catch (InvalidArgumentException $e) {
            $this->assertSame("Invalid memo id '$memo': $reason", $e->getMessage());
        }
        $this->assertSame($this->authServer . '?', $builder->buildUrl());
    }

    public function testMuxedSuccess(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeSuccess($this->clientAccountIdM)),
            new Response(200, ['X-Foo' => 'Bar'], $this->requestJWTSuccess())
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $jwtToken = $webAuth->jwtToken($this->clientAccountIdM, [$userKeyPair]);
        $this->assertEquals($this->successJWTToken, $jwtToken);
    }

    public function testGetChallengeFailure(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(400, ['Content-Length' => 0])
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } catch (GuzzleException $e) {
        } catch (ErrorException $e) {
            if ($e instanceof ChallengeRequestErrorResponse) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testInvalidAddedMemoAndMuxed(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($this->clientAccountIdM, [$userKeyPair], $this->testMemo);
        } catch (InvalidArgumentException $e) {
            $exception = true;
        }
        $this->assertTrue($exception);
    }

    public function testGetChallengeInvalidMemoType(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInvalidMemoType($this->clientAccountId))
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } catch (GuzzleException $e) {
        } catch (ErrorException $e) {
            if ($e instanceof ChallengeValidationErrorInvalidMemoType) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testGetChallengeInvalidMemoValue(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInvalidMemoValue($this->clientAccountId))
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair], $this->testMemo);
        } catch (GuzzleException $e) {
        } catch (ErrorException $e) {
            if ($e instanceof ChallengeValidationErrorInvalidMemoValue) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testGetChallengeInvalidSequenceNumber(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInvalidSequenceNumber($this->clientAccountIdM))
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } catch (GuzzleException $e) {
        } catch (ErrorException $e) {
            if ($e instanceof ChallengeValidationErrorInvalidSeqNr) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testGetChallengeInvalidFirstOpSource(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInvalidFirstOpSourceAccount())
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } catch (GuzzleException $e) {
        } catch (ErrorException $e) {
            if ($e instanceof ChallengeValidationErrorInvalidSourceAccount) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testGetChallengeInvalidSecondOpSource(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInvalidSecondOpSourceAccount($this->clientAccountId))
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } catch (GuzzleException $e) {
        } catch (ErrorException $e) {
            if ($e instanceof ChallengeValidationErrorInvalidSourceAccount) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testGetChallengeInvalidHomeDomain(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInvalidHomeDomain($this->clientAccountId))
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } catch (GuzzleException $e) {
        } catch (ErrorException $e) {
            if ($e instanceof ChallengeValidationErrorInvalidHomeDomain) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testGetChallengeInvalidWebAuthDomain(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInvalidWebAuth($this->clientAccountId))
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } catch (GuzzleException $e) {
        } catch (ErrorException $e) {
            if ($e instanceof ChallengeValidationErrorInvalidWebAuthDomain) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testGetChallengeInvalidTimeBounds(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInvalidTimeBounds($this->clientAccountId))
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } catch (GuzzleException $e) {
        } catch (ErrorException $e) {
            if ($e instanceof ChallengeValidationErrorInvalidTimeBounds) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testGetChallengeMissingTimeBounds(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeMissingTimeBounds($this->clientAccountId))
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } catch (GuzzleException $e) {
        } catch (ErrorException $e) {
            if ($e instanceof ChallengeValidationErrorInvalidTimeBounds) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testGetChallengeInfiniteTimeBounds(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInfiniteTimeBounds($this->clientAccountId))
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } catch (GuzzleException $e) {
        } catch (ErrorException $e) {
            if ($e instanceof ChallengeValidationErrorInvalidTimeBounds) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testMemoWithMuxedAccountRejected(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $exception = false;
        try {
            $webAuth->jwtToken($this->clientAccountIdM, [$userKeyPair], $this->testMemo);
        } catch (GuzzleException $e) {
        } catch (InvalidArgumentException $e) {
            $exception = true;
        }
        $this->assertTrue($exception);
    }

    public function testGetChallengeInvalidBase64(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode(['transaction' => '@@@not-valid-base64@@@']))
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } catch (GuzzleException $e) {
        } catch (InvalidArgumentException $e) {
            $exception = true;
        }
        $this->assertTrue($exception);
    }

    public function testGetChallengeMissingMemo(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeSuccess($this->clientAccountId))
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair], $this->testMemo);
        } catch (GuzzleException $e) {
        } catch (ErrorException $e) {
            if ($e instanceof ChallengeValidationErrorInvalidMemoValue) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    // Memo id 0 tests. Zero is a valid SEP-10 client memo identifying a user of a
    // shared account, so it must be treated exactly like any other memo id and only
    // null may mean "no memo".

    public function testMemoZeroIsSentAndValidated(): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeSuccess($this->clientAccountId, 0)),
            new Response(200, ['X-Foo' => 'Bar'], $this->requestJWTSuccess()),
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);

        $jwtToken = $webAuth->jwtToken($this->clientAccountId, [$userKeyPair], 0);

        $query = $this->challengeRequestQuery($history);
        $this->assertArrayHasKey('memo', $query);
        $this->assertSame('0', $query['memo']);
        $this->assertSame($this->successJWTToken, $jwtToken);
    }

    public function testMemoZeroWithMuxedAccountRejected(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        // Empty queue: reaching the challenge request at all is a failure.
        $webAuth->setMockHandler(new MockHandler([]));
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('memo cannot be used if accountId is a muxed account');
        $webAuth->jwtToken($this->clientAccountIdM, [$userKeyPair], 0);
    }

    public function testMemoZeroMismatchedChallengeMemoRejected(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $webAuth->setMockHandler(new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeSuccess($this->clientAccountId, 7)),
            new Response(200, ['X-Foo' => 'Bar'], $this->requestJWTSuccess()),
        ]));
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);

        $this->expectException(ChallengeValidationErrorInvalidMemoValue::class);
        $this->expectExceptionMessage('invalid memo value');
        $webAuth->jwtToken($this->clientAccountId, [$userKeyPair], 0);
    }

    public function testMemoZeroMissingChallengeMemoRejected(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $webAuth->setMockHandler(new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeSuccess($this->clientAccountId)),
            new Response(200, ['X-Foo' => 'Bar'], $this->requestJWTSuccess()),
        ]));
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);

        $this->expectException(ChallengeValidationErrorInvalidMemoValue::class);
        $this->expectExceptionMessage('missing memo');
        $webAuth->jwtToken($this->clientAccountId, [$userKeyPair], 0);
    }

    public function testNullMemoOmitsMemoRequestParameter(): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeSuccess($this->clientAccountId)),
            new Response(200, ['X-Foo' => 'Bar'], $this->requestJWTSuccess()),
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);

        $jwtToken = $webAuth->jwtToken($this->clientAccountId, [$userKeyPair]);

        $this->assertArrayNotHasKey('memo', $this->challengeRequestQuery($history));
        $this->assertSame($this->successJWTToken, $jwtToken);
    }

    public function testSubmitChallengeErrorResponse(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeSuccess($this->clientAccountId, 200)),
            new Response(400, ['X-Foo' => 'Bar'], $this->requestJWTError())
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } catch (GuzzleException $e) {
        } catch (ErrorException $e) {
            if ($e instanceof SubmitCompletedChallengeErrorResponseException) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testSubmitChallengeMalformedJson(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeSuccess($this->clientAccountId, 200)),
            new Response(200, ['X-Foo' => 'Bar'], '{not valid json')
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } catch (GuzzleException $e) {
        } catch (ErrorException $e) {
            if ($e instanceof SubmitCompletedChallengeErrorResponseException) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testSubmitChallengeNoTokenNoError(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeSuccess($this->clientAccountId, 200)),
            new Response(200, ['X-Foo' => 'Bar'], json_encode(['unexpected' => 'value']))
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } catch (GuzzleException $e) {
        } catch (ErrorException $e) {
            if ($e instanceof SubmitCompletedChallengeErrorResponseException) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testSubmitChallengeTimeout(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeSuccess($this->clientAccountId, 200)),
            new Response(504, ['X-Foo' => 'Bar'], '')
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } catch (GuzzleException $e) {
        } catch (ErrorException $e) {
            if ($e instanceof SubmitCompletedChallengeTimeoutResponseException) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testSubmitChallengeUnknownResponse(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeSuccess($this->clientAccountId, 200)),
            new Response(503, ['X-Foo' => 'Bar'], 'service unavailable')
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } catch (GuzzleException $e) {
        } catch (ErrorException $e) {
            if ($e instanceof SubmitCompletedChallengeUnknownResponseException) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    /** @return array<string, array{string}> */
    public static function challengeBodiesWithoutStringTransaction(): array {
        return [
            'empty object' => ['{}'],
            'null transaction' => ['{"transaction": null}'],
            'int transaction' => ['{"transaction": 123}'],
            'array transaction' => ['{"transaction": ["a"]}'],
        ];
    }

    #[DataProvider('challengeBodiesWithoutStringTransaction')]
    public function testChallengeWithoutStringTransactionRejected(string $body): void {
        $this->assertNull(ChallengeResponse::fromJson(json_decode($body, true))->getTransaction());

        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $webAuth->setMockHandler(new MockHandler([new Response(200, [], $body)]));
        try {
            $webAuth->jwtToken($this->clientAccountId, [KeyPair::fromSeed($this->clientSecretSeed)]);
            $this->fail('Expected ChallengeRequestErrorResponse');
        } catch (ChallengeRequestErrorResponse $e) {
            $this->assertSame('Error parsing challenge response', $e->getMessage());
        }
    }

    /** @return array<string, array{string, string}> */
    public static function challengeBodiesNotObject(): array {
        return [
            'string' => ['"abc"', 'string'],
            'number' => ['42', 'int'],
            'boolean' => ['true', 'bool'],
            'null' => ['null', 'null'],
        ];
    }

    #[DataProvider('challengeBodiesNotObject')]
    public function testChallengeBodyNotObjectRejected(string $body, string $type): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $webAuth->setMockHandler(new MockHandler([new Response(200, [], $body)]));
        try {
            $webAuth->jwtToken($this->clientAccountId, [KeyPair::fromSeed($this->clientSecretSeed)]);
            $this->fail('Expected ChallengeRequestErrorResponse');
        } catch (ChallengeRequestErrorResponse $e) {
            $this->assertSame("Response body must be a JSON object or array, got $type", $e->getMessage());
            $this->assertSame(200, $e->getCode());
        }
    }

    /** @return array<string, array{string, string}> */
    public static function malformedTokenBodies(): array {
        return [
            'int token' => ['{"token": 123}', 'an unknown error occurred'],
            'array token' => ['{"token": ["a"]}', 'an unknown error occurred'],
            'int error' => ['{"error": 5}', 'an unknown error occurred'],
            'string body' => ['"abc"', 'Token response body must be a JSON object or array, got string'],
            'number body' => ['42', 'Token response body must be a JSON object or array, got int'],
            'null body' => ['null', 'Token response body must be a JSON object or array, got null'],
        ];
    }

    #[DataProvider('malformedTokenBodies')]
    public function testMalformedTokenResponseRejected(string $body, string $message): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $webAuth->setMockHandler(new MockHandler([
            new Response(200, [], $this->requestChallengeSuccess($this->clientAccountId)),
            new Response(200, [], $body),
        ]));
        try {
            $webAuth->jwtToken($this->clientAccountId, [KeyPair::fromSeed($this->clientSecretSeed)]);
            $this->fail('Expected SubmitCompletedChallengeErrorResponseException');
        } catch (SubmitCompletedChallengeErrorResponseException $e) {
            $this->assertSame($message, $e->getMessage());
        }
    }

    public function testFromDomainMissingWebAuthEndpoint(): void {
        $toml = 'SIGNING_KEY="' . $this->serverAccountId . '"';
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([
            new Response(200, [], $toml)
        ]))]);
        $exception = false;
        try {
            WebAuth::fromDomain('place.domain.com', Network::testnet(), $client);
        } catch (GuzzleException $e) {
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'WEB_AUTH_ENDPOINT')) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testFromDomainMissingSigningKey(): void {
        $toml = 'WEB_AUTH_ENDPOINT="https://auth.place.domain.com/auth"';
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([
            new Response(200, [], $toml)
        ]))]);
        $exception = false;
        try {
            WebAuth::fromDomain('place.domain.com', Network::testnet(), $client);
        } catch (GuzzleException $e) {
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'SIGNING_KEY')) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testGetChallengeInvalidOperationType(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInvalidOperationType($this->clientAccountId))
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } catch (GuzzleException $e) {
        } catch (ErrorException $e) {
            if ($e instanceof ChallengeValidationErrorInvalidOperationType) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testGetChallengeInvalidSignature(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInvalidSignature($this->clientAccountId))
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } catch (GuzzleException $e) {
        } catch (ErrorException $e) {
            if ($e instanceof ChallengeValidationErrorInvalidSignature) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testGetChallengeTooManySignatures(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeMultipleSignature($this->clientAccountId))
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } catch (GuzzleException $e) {
        } catch (ErrorException $e) {
            if ($e instanceof ChallengeValidationErrorInvalidSignature) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testGetChallengeInvalidClientDomainSourceAccount(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInvalidClientDomainOpSourceAccount($this->clientAccountId))
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $exception = false;
        try {
            $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } catch (GuzzleException $e) {
        } catch (ErrorException $e) {
            if ($e instanceof ChallengeValidationErrorInvalidSourceAccount) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testGetChallengeValidClientDomainSourceAccount(): void {
        $clientDomainAccountKeyPair = KeyPair::random();
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeValidClientDomainOpSourceAccount($this->clientAccountId, $clientDomainAccountKeyPair->getAccountId())),
            new Response(200, ['X-Foo' => 'Bar'], $this->requestJWTSuccess())
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair], clientDomain:"place.domain.com", clientDomainKeyPair: $clientDomainAccountKeyPair);
        $this->assertEquals($this->successJWTToken, $jwtToken);
    }

    // Edge Case Tests - Token Expiration and Challenge Validation

    public function testChallengeExpirationAtLowerBound(): void
    {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $now = time();
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
            ->addOperation($this::validFirstManageDataOp($this->clientAccountId))
            ->addOperation($this::validSecondManageDataOp())
            ->addMemo(Memo::none())
            ->setTimeBounds(new TimeBounds(
                (new DateTime)->setTimestamp($now - 1),
                (new DateTime)->setTimestamp($now + 1)
            ))
            ->build();
        $transaction->sign($this->serverKeyPair, Network::testnet());
        $challengeResponse = json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $challengeResponse),
            new Response(200, ['X-Foo' => 'Bar'], $this->requestJWTSuccess())
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $jwtToken = $webAuth->jwtToken($this->clientAccountId, [$userKeyPair]);
        $this->assertEquals($this->successJWTToken, $jwtToken);
    }

    public function testChallengeWithExpiredMinTime(): void
    {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $now = time();
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
            ->addOperation($this::validFirstManageDataOp($this->clientAccountId))
            ->addOperation($this::validSecondManageDataOp())
            ->addMemo(Memo::none())
            ->setTimeBounds(new TimeBounds(
                (new DateTime)->setTimestamp($now - 700),
                (new DateTime)->setTimestamp($now - 400)
            ))
            ->build();
        $transaction->sign($this->serverKeyPair, Network::testnet());
        $challengeResponse = json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $challengeResponse)
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);

        $exception = false;
        try {
            $webAuth->jwtToken($this->clientAccountId, [$userKeyPair]);
        } catch (ErrorException $e) {
            if ($e instanceof ChallengeValidationErrorInvalidTimeBounds) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testChallengeTimeBoundsAtOrAbove2Pow63AreRejected(): void
    {
        $now = time();
        $cases = [
            'minTime 2^63' => new TimeBounds(
                (new DateTime)->setTimestamp(PHP_INT_MIN),
                (new DateTime)->setTimestamp($now + 3)
            ),
            'maxTime 2^64-1' => new TimeBounds(
                (new DateTime)->setTimestamp($now),
                (new DateTime)->setTimestamp(-1)
            ),
        ];
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        foreach ($cases as $name => $timeBounds) {
            $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
            $webAuth->setMockHandler(new MockHandler([
                new Response(200, [], $this->requestChallengeWithTimeBounds($timeBounds)),
                new Response(200, [], $this->requestJWTSuccess()),
            ]));
            try {
                $webAuth->jwtToken($this->clientAccountId, [$userKeyPair]);
                $this->fail('Accepted a challenge with ' . $name);
            } catch (ChallengeValidationErrorInvalidTimeBounds $e) {
                $this->assertSame('Invalid transaction, invalid time bounds', $e->getMessage());
            }
        }

        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $webAuth->setMockHandler(new MockHandler([
            new Response(200, [], $this->requestChallengeWithTimeBounds($this->validTimeBounds())),
            new Response(200, [], $this->requestJWTSuccess()),
        ]));
        $this->assertSame($this->successJWTToken, $webAuth->jwtToken($this->clientAccountId, [$userKeyPair]));
    }

    private function requestChallengeWithTimeBounds(TimeBounds $timeBounds) : string {
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
            ->addOperation($this::validFirstManageDataOp($this->clientAccountId))
            ->addOperation($this::validSecondManageDataOp())
            ->addMemo(Memo::none())
            ->setTimeBounds($timeBounds)
            ->build();
        $transaction->sign($this->serverKeyPair, Network::testnet());
        return json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);
    }

    public function testChallengeValidationWithEmptyOperations(): void
    {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());

        $exception = false;
        $exceptionMessage = '';
        try {
            $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
            $transaction = (new TransactionBuilder($transactionAccount))
                ->setTimeBounds($this::validTimeBounds())
                ->build();
        } catch (\InvalidArgumentException $e) {
            $exception = true;
            $exceptionMessage = $e->getMessage();
        }

        $this->assertTrue($exception);
        $this->assertStringContainsString('operation', $exceptionMessage);
    }

    public function testChallengeWithDifferentNetworkPassphrase(): void
    {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::public());
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
            ->addOperation($this::validFirstManageDataOp($this->clientAccountId))
            ->addOperation($this::validSecondManageDataOp())
            ->setTimeBounds($this::validTimeBounds())
            ->build();
        $transaction->sign($this->serverKeyPair, Network::testnet());
        $challengeResponse = json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $challengeResponse)
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);

        $exception = false;
        try {
            $webAuth->jwtToken($this->clientAccountId, [$userKeyPair]);
        } catch (ErrorException $e) {
            if ($e instanceof ChallengeValidationErrorInvalidSignature) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    // Grace Period Tests

    public function testSetGracePeriodRejectsNegative(): void
    {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Grace period must be non-negative');
        $webAuth->setGracePeriod(-1);
    }

    public function testSetGracePeriodZeroRejectsExpiredChallenge(): void
    {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $webAuth->setGracePeriod(0);
        $now = time();
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
            ->addOperation($this::validFirstManageDataOp($this->clientAccountId))
            ->addOperation($this::validSecondManageDataOp())
            ->addMemo(Memo::none())
            ->setTimeBounds(new TimeBounds(
                (new DateTime)->setTimestamp($now - 2),
                (new DateTime)->setTimestamp($now - 1)
            ))
            ->build();
        $transaction->sign($this->serverKeyPair, Network::testnet());
        $challengeResponse = json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $challengeResponse)
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);

        $exception = false;
        try {
            $webAuth->jwtToken($this->clientAccountId, [$userKeyPair]);
        } catch (ErrorException $e) {
            if ($e instanceof ChallengeValidationErrorInvalidTimeBounds) {
                $exception = true;
            }
        }
        $this->assertTrue($exception);
    }

    public function testSetGracePeriodLargeAcceptsOlderChallenge(): void
    {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $webAuth->setGracePeriod(600);
        $now = time();
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
            ->addOperation($this::validFirstManageDataOp($this->clientAccountId))
            ->addOperation($this::validSecondManageDataOp())
            ->addMemo(Memo::none())
            ->setTimeBounds(new TimeBounds(
                (new DateTime)->setTimestamp($now - 400),
                (new DateTime)->setTimestamp($now - 100)
            ))
            ->build();
        $transaction->sign($this->serverKeyPair, Network::testnet());
        $challengeResponse = json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $challengeResponse),
            new Response(200, ['X-Foo' => 'Bar'], $this->requestJWTSuccess())
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $jwtToken = $webAuth->jwtToken($this->clientAccountId, [$userKeyPair]);
        $this->assertEquals($this->successJWTToken, $jwtToken);
    }

    // HTTPS Enforcement Tests

    public function testConstructorRejectsHttpUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service URL must use HTTPS');
        new WebAuth('http://api.stellar.org/auth', $this->serverAccountId, $this->domain, Network::testnet());
    }

}
