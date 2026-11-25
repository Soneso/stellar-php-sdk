<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\SEP\WebAuth;

use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use InvalidArgumentException;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use Soneso\StellarSDK\AbstractOperation;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\FeeBumpTransactionBuilder;
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
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationError;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidHomeDomain;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidMemoType;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidMemoValue;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidOperationType;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidSeqNr;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidSignature;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidSourceAccount;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidTimeBounds;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidWebAuthDomain;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorMemoAndMuxedAccount;
use Soneso\StellarSDK\SEP\WebAuth\SubmitCompletedChallengeErrorResponseException;
use Soneso\StellarSDK\SEP\WebAuth\SubmitCompletedChallengeTimeoutResponseException;
use Soneso\StellarSDK\SEP\WebAuth\SubmitCompletedChallengeUnknownResponseException;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;
use Soneso\StellarSDK\TimeBounds;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\TransactionPreconditions;
use Soneso\StellarSDK\Xdr\XdrDecoratedSignature;
use Soneso\StellarSDK\Xdr\XdrEnvelopeType;
use Soneso\StellarSDK\Xdr\XdrPreconditions;
use Soneso\StellarSDK\Xdr\XdrPreconditionsV2;
use Soneso\StellarSDK\Xdr\XdrPreconditionType;
use Soneso\StellarSDK\Xdr\XdrSequenceNumber;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrTransactionV0;
use Soneso\StellarSDK\Xdr\XdrTransactionV0Envelope;


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

    private function validFirstManageDataOp(string $accountId, ?string $homeDomain = null) : ManageDataOperation {
        $muxedAccount = MuxedAccount::fromAccountId($accountId);
        $builder = (new ManageDataOperationBuilder(($homeDomain ?? $this->domain) . " auth", base64_encode(random_bytes(48))))->setMuxedSourceAccount($muxedAccount);
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
     * @param array<int, Response|\Throwable> $responses
     * @param array<int, array<string, mixed>> $history
     * @param Network|null $network Network to use for challenge signatures.
     */
    private function webAuthRecordingRequests(array $responses, array &$history, ?Network $network = null) : WebAuth {
        $handlerStack = HandlerStack::create(new MockHandler($responses));
        $handlerStack->push(Middleware::history($history));
        return new WebAuth(
            $this->authServer,
            $this->serverAccountId,
            $this->domain,
            $network ?? Network::testnet(),
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

    /** @return array<string, array{int|string|null, ?string, ?string, bool}> */
    public static function authorizationClaimCases(): array
    {
        return [
            'account only' => [null, null, null, false],
            'all parameters' => [42, 'home + domain', 'place.client.com', false],
            'zero parameters' => [0, '0', '0', false],
            'maximum memo and empty home' => ['18446744073709551615', '', null, false],
            'muxed account' => [null, null, null, true],
        ];
    }

    #[DataProvider('authorizationClaimCases')]
    public function testAuthorizationKeyPairSignsExactRequestClaims(
        int|string|null $memo, ?string $homeDomain, ?string $clientDomain, bool $muxed
    ): void {
        $user = KeyPair::random();
        $account = $muxed ? (new MuxedAccount($user->getAccountId(), 123))->getAccountId() : $user->getAccountId();
        $domain = KeyPair::random();
        $authorizationSigner = $clientDomain === null ? $user : $domain;
        $this->authServer .= '?route=a%20b&flag';
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $this->requestChallengeSuccess($account, $memo,
                $homeDomain === '' ? null : $homeDomain)),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $before = time();
        $webAuth->jwtToken($account, [$user], $memo, $homeDomain, $clientDomain,
            $clientDomain === null ? null : $domain, null, $authorizationSigner);
        $after = time();
        $this->assertRequestMethods($history, ['GET', 'POST']);
        $header = $history[0]['request']->getHeaderLine('Authorization');
        $this->assertTrue(str_starts_with($header, 'Bearer '));
        $segments = explode('.', substr($header, 7));
        $this->assertCount(3, $segments);
        foreach ($segments as $segment) {
            $this->assertTrue(preg_match('/\A[A-Za-z0-9_-]+\z/', $segment) === 1);
        }
        $decode = static fn(string $value): string => base64_decode(strtr($value, '-_', '+/'), true);
        $this->assertSame(['alg' => 'EdDSA'], json_decode($decode($segments[0]), true));
        $payload = $decode($segments[1]);
        $this->assertTrue(str_contains($payload, '"web_auth_endpoint":"https://api.stellar.org/auth?route=a%20b&flag"'));
        $this->assertFalse(str_contains($payload, '\\/'));
        $claims = json_decode($payload, true);
        $this->assertIsInt($claims['iat']);
        $this->assertIsInt($claims['exp']);
        $this->assertGreaterThanOrEqual($before, $claims['iat']);
        $this->assertLessThanOrEqual($after, $claims['iat']);
        $this->assertSame($claims['iat'] + 900, $claims['exp']);
        $expected = ['account' => $account];
        if ($memo !== null) {
            $expected['memo'] = (string)$memo;
        }
        if ($homeDomain !== null && $homeDomain !== '') {
            $expected['home_domain'] = $homeDomain;
        }
        if ($clientDomain !== null) {
            $expected['client_domain'] = $clientDomain;
        }
        unset($claims['iat'], $claims['exp']);
        $this->assertSame(['web_auth_endpoint' => $this->authServer] + $expected, $claims);
        $this->assertSame(['route' => 'a b', 'flag' => ''] + $expected, $this->challengeRequestQuery($history));
        $this->assertSame($this->authServer . '&' . http_build_query($expected, '', '&', PHP_QUERY_RFC3986),
            (string)$history[0]['request']->getUri());
        $this->assertTrue(KeyPair::fromAccountId($authorizationSigner->getAccountId())->verifySignature(
            $decode($segments[2]), $segments[0] . '.' . $segments[1]));
        $this->assertFalse($history[1]['request']->hasHeader('Authorization'));
        $this->assertSame($this->authServer, (string)$history[1]['request']->getUri());
    }

    /** @return array<string, array{bool}> */
    public static function authorizationClientAuthentication(): array
    {
        return ['header' => [false], 'basic auth' => [true]];
    }

    #[DataProvider('authorizationClientAuthentication')]
    public function testAuthorizationCallableIsVerbatimAndConfinedToChallenge(bool $basicAuth): void
    {
        $user = KeyPair::random();
        $domain = KeyPair::random();
        $history = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], 'WEB_AUTH_ENDPOINT="' . $this->authServer . '"' . "\n" . 'SIGNING_KEY="' . $this->serverAccountId . '"'),
            new Response(200, [], $this->requestChallengeValidClientDomainOpSourceAccount($user->getAccountId(), $domain->getAccountId())),
            new Response(200, [], 'SIGNING_KEY="' . $domain->getAccountId() . '"'),
            new Response(200, [], $this->requestJWTSuccess()),
            new Response(200, [], $this->requestChallengeSuccess($user->getAccountId())),
            new Response(200, [], $this->requestJWTSuccess()),
        ]));
        $stack->push(Middleware::history($history));
        $client = new class(['handler' => $stack, 'allow_redirects' => false,
            'auth' => $basicAuth ? ['test-user', 'test-password'] : null,
            'headers' => ['authorization' => 'caller-authorization', 'X-Caller' => 'preserved',
                'x-client-name' => 'caller-name', 'x-client-version' => 'caller-version']]) extends Client {
            public int $sendCount = 0;

            public function send(\Psr\Http\Message\RequestInterface $request, array $options = []): \Psr\Http\Message\ResponseInterface
            {
                $this->sendCount++;
                return parent::send($request, $options);
            }
        };
        $originalConfig = $client->getConfig();
        $webAuth = WebAuth::fromDomain($this->domain, Network::testnet(), $client);
        $received = null;
        $before = time();
        $webAuth->jwtToken($user->getAccountId(), [$user], clientDomain: 'place.client.com',
            clientDomainSigningCallback: static function (string $xdr) use ($domain): string {
                $tx = Transaction::fromEnvelopeBase64XdrString($xdr);
                $tx->sign($domain, Network::testnet());
                return $tx->toEnvelopeXdrBase64();
            }, authorizationSigner: static function (array $claims) use (&$received): string {
                $received = $claims;
                return 'opaque-remote-answer';
            });
        $this->assertIsInt($received['iat']);
        $this->assertIsInt($received['exp']);
        $this->assertGreaterThanOrEqual($before, $received['iat']);
        $this->assertLessThanOrEqual(time(), $received['iat']);
        $this->assertSame($received['iat'] + 900, $received['exp']);
        unset($received['iat'], $received['exp']);
        $this->assertSame(['web_auth_endpoint' => $this->authServer,
            'account' => $user->getAccountId(), 'client_domain' => 'place.client.com'], $received);
        $webAuth->jwtToken($user->getAccountId(), [$user]);
        $this->assertRequestMethods($history, ['GET', 'GET', 'GET', 'POST', 'GET', 'POST']);
        $defaultAuthorization = $basicAuth ? 'Basic ' . base64_encode('test-user:test-password') : 'caller-authorization';
        foreach ($history as $index => $entry) {
            $this->assertTrue($entry['request']->getHeaderLine('Authorization') ===
                ($index === 1 ? 'Bearer opaque-remote-answer' : $defaultAuthorization));
            $this->assertCount(1, $entry['request']->getHeader('Authorization'));
            $isGet = $entry['request']->getMethod() === 'GET';
            $this->assertSame($isGet ? 'stellar_php_sdk' : 'caller-name', $entry['request']->getHeaderLine('X-Client-Name'));
            $this->assertSame($isGet ? \Soneso\StellarSDK\StellarSDK::VERSION_NR : 'caller-version',
                $entry['request']->getHeaderLine('X-Client-Version'));
            $this->assertSame('preserved', $entry['request']->getHeaderLine('X-Caller'));
            $this->assertFalse($entry['options']['allow_redirects']);
        }
        $this->assertSame(4, $client->sendCount);
        $this->assertTrue($originalConfig === $client->getConfig());
    }

    /** @return array<string, array{mixed}> */
    public static function invalidAuthorizationAnswers(): array
    {
        return ['empty' => [''], 'null' => [null], 'integer' => [1], 'false' => [false], 'array' => [[]]];
    }

    #[DataProvider('invalidAuthorizationAnswers')]
    public function testAuthorizationInvalidCallableAnswerFailsBeforeRequest(mixed $answer): void
    {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([], $history);
        $user = KeyPair::random();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Authorization signer must return a non-empty string');
        try {
            $webAuth->jwtToken($user->getAccountId(), [$user],
                authorizationSigner: static fn(array $claims): mixed => $answer);
        } finally {
            $this->assertRequestMethods($history, []);
        }
    }

    public function testAuthorizationInvalidHeaderValueFailsWithoutLeakingToken(): void
    {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([], $history);
        $user = KeyPair::random();
        $answer = bin2hex(random_bytes(16)) . "\n" . bin2hex(random_bytes(16));
        try {
            $webAuth->jwtToken($user->getAccountId(), [$user],
                authorizationSigner: static fn(array $claims): string => $answer);
            $this->fail('Invalid header value must be rejected');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue($e->getMessage() === 'Authorization signer returned an invalid header value');
            foreach (explode("\n", $answer) as $part) {
                $this->assertFalse(str_contains($e->getMessage(), $part));
            }
            $this->assertNull($e->getPrevious());
        } finally {
            $this->assertRequestMethods($history, []);
        }
    }

    public function testAuthorizationPublicOnlyKeyFailsBeforeRequest(): void
    {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([], $history);
        $user = KeyPair::random();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Authorization signer must have a private key');
        try {
            $webAuth->jwtToken($user->getAccountId(), [$user],
                authorizationSigner: KeyPair::fromAccountId($user->getAccountId()));
        } finally {
            $this->assertRequestMethods($history, []);
        }
    }

    public function testAuthorizationCallableAcceptsZeroWithoutClientDomain(): void
    {
        $user = KeyPair::random();
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $this->requestChallengeSuccess($user->getAccountId(), 0, '0')),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $webAuth->jwtToken($user->getAccountId(), [$user], memo: 0, homeDomain: '0',
            authorizationSigner: function (array $claims) use ($user): string {
                $this->assertIsInt($claims['iat']);
                $this->assertSame($claims['iat'] + 900, $claims['exp']);
                unset($claims['iat'], $claims['exp']);
                $this->assertSame(['web_auth_endpoint' => $this->authServer,
                    'account' => $user->getAccountId(), 'memo' => '0', 'home_domain' => '0'], $claims);
                return '0';
            });
        $this->assertRequestMethods($history, ['GET', 'POST']);
        $this->assertTrue($history[0]['request']->getHeaderLine('Authorization') === 'Bearer 0');
        $this->assertFalse($history[1]['request']->hasHeader('Authorization'));
    }

    private function invalidTimeBounds() : TimeBounds {
        return new TimeBounds((new DateTime)->setTimestamp(time() - 700),
            (new DateTime)->setTimestamp(time() - 400));
    }

    private function invalidHomeDomainOp(string $accountId) : ManageDataOperation {
        $muxedAccount = MuxedAccount::fromAccountId($accountId);
        $builder = (new ManageDataOperationBuilder("fake.com" . " auth", base64_encode(random_bytes(48))))->setMuxedSourceAccount($muxedAccount);
        return $builder->build();
    }

    private function invalidOperationType(string $accountId) : PaymentOperation {
        $muxedAccount = MuxedAccount::fromAccountId($accountId);
        $builder = (new PaymentOperationBuilder($this->serverAccountId, Asset::native(), "20.0"))->setMuxedSourceAccount($muxedAccount);
        return $builder->build();
    }

    private function requestChallengeSuccess(string $accountId, int|string|null $memo = null, ?string $homeDomain = null) : string {
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
                ->addOperation($this::validFirstManageDataOp($accountId, $homeDomain))
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

    public function testChallengeAndSigningCallbackRejectTrailingBytes(): void
    {
        foreach ([false, true] as $tailAtSigning) {
            $domainKey = KeyPair::random();
            $body = json_decode($this->requestChallengeValidClientDomainOpSourceAccount($this->clientAccountId, $domainKey->getAccountId()), true);
            $valid = $body['transaction'];
            if (!$tailAtSigning) {
                $body['transaction'] = base64_encode(base64_decode($valid) . "\x01");
            }
            $history = [];
            $webAuth = $this->webAuthRecordingRequests([
                new Response(200, [], json_encode($body)),
                new Response(200, [], json_encode(['token' => $this->successJWTToken])),
            ], $history);
            $callback = function (string $envelope) use ($tailAtSigning, $valid): string {
                $this->assertTrue($tailAtSigning, 'Challenge validation must reject before signing');
                $this->assertSame($valid, $envelope);
                return base64_encode(base64_decode($envelope) . "\x01");
            };
            try {
                $webAuth->jwtToken($this->clientAccountId, [KeyPair::fromSeed($this->clientSecretSeed)],
                    clientDomain: 'place.client.com', clientDomainKeyPair: KeyPair::fromAccountId($domainKey->getAccountId()),
                    clientDomainSigningCallback: $callback);
                $this->fail('Accepted trailing bytes');
            } catch (InvalidArgumentException $e) {
                $this->assertSame(XdrTransactionEnvelope::class . ' leaves 1 of ' . (strlen(base64_decode($valid)) + 1) . ' bytes unread', $e->getMessage());
                $this->assertCount(1, $history);
            }
        }
    }


    /**
     * @param array<int, array<string, mixed>> $history
     * @param array<string> $expected
     */
    private function assertRequestMethods(array $history, array $expected): void
    {
        $this->assertSame($expected, array_map(
            static fn(array $entry): string => $entry['request']->getMethod(), $history,
        ), 'Only the expected requests may be sent; rejected challenges must send no POST');
    }

    private function signerRecordingCalls(KeyPair $key, int &$calls): KeyPair
    {
        $signer = $this->createMock(KeyPair::class);
        $signer->method('getPrivateKey')->willReturn($key->getPrivateKey());
        $signer->method('signDecorated')->willReturnCallback(
            static function (string $hash) use ($key, &$calls): XdrDecoratedSignature {
                $calls++;
                return $key->signDecorated($hash);
            },
        );
        return $signer;
    }

    /**
     * @param array<int, array<string, mixed>> $history
     * @param class-string<\Exception> $exceptionClass
     * @param array<string> $methods
     */
    private function assertRejectedWithoutSigningOrPost(
        callable $authenticate, array &$history, int &$signCalls,
        string $exceptionClass, string $message, array $methods, ?int $code = null,
    ): void {
        $error = null;
        try {
            $authenticate();
        } catch (\Exception $e) {
            $error = $e;
        }
        $this->assertRequestMethods($history, $methods);
        $this->assertSame(0, $signCalls, 'The user must not be asked to sign a rejected challenge');
        $this->assertNotNull($error, 'The challenge must be rejected');
        $this->assertSame($exceptionClass, $error::class);
        $this->assertSame($message, $error->getMessage());
        if ($code !== null) $this->assertSame($code, $error->getCode());
    }

    /** @return array<string, array{string}> */
    public static function rejectedCallbackAnswers(): array
    {
        return array_combine($cases = [
            'payment', 'payment by reference', 'changed fee',
            'invalid base64', 'truncated XDR', 'wrong envelope type',
            'null', 'false', 'object', 'V0 envelope', 'V2 time bounds',
        ], array_map(static fn(string $case): array => [$case], $cases));
    }

    #[DataProvider('rejectedCallbackAnswers')]
    public function testCallbackRejectsUnsafeAnswerBeforeUserSigning(string $case): void
    {
        $user = KeyPair::random();
        $domain = KeyPair::random();
        $clientDomain = 'place.client.com';
        $body = $this->requestChallengeValidClientDomainOpSourceAccount($user->getAccountId(), $domain->getAccountId());
        $responses = [new Response(200, [], $body)];
        $responses[] = new Response(200, [], 'SIGNING_KEY="' . $domain->getAccountId() . '"');
        $responses[] = new Response(200, [], $this->requestJWTSuccess());
        $history = [];
        $webAuth = $this->webAuthRecordingRequests($responses, $history);
        $calls = 0;
        $signer = $this->signerRecordingCalls($user, $calls);
        $callback = function (string &$argument) use ($case, $user, $domain): mixed {
            $transaction = Transaction::fromEnvelopeBase64XdrString($argument);
            if (str_starts_with($case, 'payment')) {
                $payment = (new TransactionBuilder(new Account($user->getAccountId(), new BigInteger(41))))
                    ->addOperation((new PaymentOperationBuilder($this->serverAccountId, Asset::native(), '1'))->build())
                    ->build()->toEnvelopeXdrBase64();
                if ($case === 'payment by reference') {
                    $argument = $payment;
                }
                return $payment;
            }
            switch ($case) {
                case 'changed fee':
                    $transaction->setFee(1234);
                    break;
                case 'invalid base64':
                    return '!';
                case 'null':
                    return null;
                case 'false':
                    return false;
                case 'object':
                    return new \stdClass();
                case 'truncated XDR':
                    return base64_encode(substr(base64_decode($argument), 0, -1));
                case 'wrong envelope type':
                    return (new FeeBumpTransactionBuilder($transaction))
                        ->setFeeAccount($this->serverAccountId)->setBaseFee(200)->build()->toEnvelopeXdrBase64();
                case 'V0 envelope':
                    $transaction->sign($domain, Network::testnet());
                    $tx = $transaction->toXdr();
                    // The typed transaction API preserves decoded V0 envelopes but does not construct them.
                    $envelope = new XdrTransactionEnvelope(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0));
                    $envelope->setV0(new XdrTransactionV0Envelope(new XdrTransactionV0(
                        $tx->getSourceAccount()->getEd25519(), $tx->getSequenceNumber(), $tx->getOperations(),
                        $tx->getFee(), $tx->getMemo(), $tx->getTimeBounds(),
                    ), $transaction->getSignatures()));
                    return base64_encode($envelope->encode());
                case 'V2 time bounds':
                    $envelope = $transaction->toEnvelopeXdr();
                    $tx = $envelope->getV1()->getTx();
                    $this->assertSame(XdrPreconditionType::TIME, $tx->getPreconditions()->getType()->getValue());
                    // The typed API selects PRECOND_TIME when only time bounds are set.
                    $preconditions = new XdrPreconditions(XdrPreconditionType::V2());
                    $preconditions->setV2(new XdrPreconditionsV2(0, 0, [], $tx->getTimeBounds()));
                    $tx->setPreconditions($preconditions);
                    $transaction = Transaction::fromEnvelopeXdr($envelope);
                    $this->assertSame(XdrPreconditionType::V2, $transaction->toXdr()->getPreconditions()->getType()->getValue());
                    $transaction->sign($domain, Network::testnet());
                    break;
            }
            return $transaction->toEnvelopeXdrBase64();
        };
        $exception = ChallengeValidationError::class;
        $message = 'Client domain signing callback returned a different transaction';
        if (in_array($case, ['invalid base64', 'null', 'false', 'object', 'truncated XDR'], true)) {
            $exception = InvalidArgumentException::class;
            $message = $case === 'truncated XDR'
                ? 'Unexpected end of XDR data' : 'Invalid base64-encoded transaction envelope';
        } elseif (in_array($case, ['wrong envelope type', 'V0 envelope'], true)) {
            $exception = ChallengeValidationError::class;
            $message = 'Invalid transaction type';
        }
        $this->assertRejectedWithoutSigningOrPost(
            fn() => $webAuth->jwtToken($user->getAccountId(), [$signer],
                clientDomain: $clientDomain, clientDomainSigningCallback: $callback),
            $history, $calls, $exception, $message,
            ['GET', 'GET'],
        );
    }

    /** @return array<string, array{string}> */
    public static function invalidDiscoveredClientDomainKeys(): array
    {
        return ['muxed' => ['muxed'], 'contract' => ['contract'], 'arbitrary' => ['arbitrary'], 'empty' => ['empty']];
    }

    #[DataProvider('invalidDiscoveredClientDomainKeys')]
    public function testDiscoveredClientDomainKeyMustBeGAccount(string $case): void
    {
        $domain = KeyPair::random();
        $published = match ($case) {
            'muxed' => (new MuxedAccount($domain->getAccountId(), 123))->getAccountId(),
            'contract' => StrKey::encodeContractId(str_repeat("\0", 32)),
            'empty' => '',
            default => 'not-an-account',
        };
        $source = $case === 'muxed' ? MuxedAccount::fromAccountId($published) : new MuxedAccount($domain->getAccountId());
        $challenge = (new TransactionBuilder(new Account($this->serverAccountId, new BigInteger(-1))))
            ->addOperation($this->validFirstManageDataOp($this->clientAccountId))
            ->addOperation((new ManageDataOperationBuilder('client_domain', 'place.client.com'))->setMuxedSourceAccount($source)->build())
            ->setTimeBounds($this->validTimeBounds())->build();
        $challenge->sign($this->serverKeyPair, Network::testnet());
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], json_encode(['transaction' => $challenge->toEnvelopeXdrBase64()])),
            new Response(200, [], 'SIGNING_KEY="' . $published . '"'),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $calls = 0;
        $callbackCalls = 0;
        $signer = $this->signerRecordingCalls(KeyPair::fromSeed($this->clientSecretSeed), $calls);
        $authenticate = function () use ($webAuth, $signer, &$callbackCalls): string {
            return $webAuth->jwtToken($this->clientAccountId, [$signer], clientDomain: 'place.client.com',
                clientDomainSigningCallback: static function (string $xdr) use (&$callbackCalls): string {
                    $callbackCalls++;
                    return $xdr;
                });
        };
        $this->assertRejectedWithoutSigningOrPost($authenticate, $history, $calls,
            InvalidArgumentException::class, 'Invalid client domain: Signing key must be a valid G... account ID', ['GET', 'GET']);
        $this->assertSame(0, $callbackCalls);
    }

    public function testClientDomainCallbackSignsAndPostsChallenge(): void
    {
        $user = KeyPair::random();
        $domain = KeyPair::random();
        $body = $this->requestChallengeValidClientDomainOpSourceAccount($user->getAccountId(), $domain->getAccountId());
        $challenge = Transaction::fromEnvelopeBase64XdrString(json_decode($body, true)['transaction']);
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $body),
            new Response(200, [], 'SIGNING_KEY="' . $domain->getAccountId() . '"'),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $calls = 0;
        $signer = $this->signerRecordingCalls($user, $calls);
        $jwt = $webAuth->jwtToken($user->getAccountId(), [$signer],
            clientDomain: 'place.client.com',
            clientDomainSigningCallback: static function (string $argument) use ($domain): string {
                $transaction = Transaction::fromEnvelopeBase64XdrString($argument);
                $transaction->sign($domain, Network::testnet());
                return $transaction->toEnvelopeXdrBase64();
            });

        $this->assertSame($this->successJWTToken, $jwt);
        $this->assertSame(1, $calls);
        $this->assertRequestMethods($history, ['GET', 'GET', 'POST']);
        $this->assertSame('https://place.client.com/.well-known/stellar.toml', (string)$history[1]['request']->getUri());
        $posted = XdrTransactionEnvelope::fromEnvelopeBase64XdrString(
            json_decode((string)$history[2]['request']->getBody(), true)['transaction'],
        );
        $this->assertSame($challenge->toXdr()->encode(), $posted->getV1()->getTx()->encode());
        $signatures = $posted->getV1()->getSignatures();
        $this->assertCount(3, $signatures);
        $this->assertSame($challenge->getSignatures()[0]->encode(), $signatures[0]->encode());
        $this->assertSame($domain->getHint(), $signatures[1]->getHint());
        $this->assertTrue($domain->verifySignature($signatures[1]->getSignature(), $challenge->hash(Network::testnet())));
        $this->assertSame($user->getHint(), $signatures[2]->getHint());
        $this->assertTrue($user->verifySignature($signatures[2]->getSignature(), $challenge->hash(Network::testnet())));
    }

    /** @return array<string, array{string}> */
    public static function acceptedCallbackSignatures(): array
    {
        return array_combine($cases = [
            'honest', 'server signature removed', 'duplicate domain signature', 'overlapping signers',
            'same signature different hint', 'no client_domain operation', 'without clientDomain',
            'local key only', 'local key and callback', 'domain key is server', 'domain key is user',
            'payment argument with original answer',
        ], array_map(static fn(string $case): array => [$case], $cases));
    }

    #[DataProvider('acceptedCallbackSignatures')]
    public function testClientDomainSigningPreservesChallengeAndSignatureOrder(string $case): void
    {
        $user = KeyPair::random();
        $domain = match ($case) {
            'domain key is server' => $this->serverKeyPair,
            'domain key is user' => $user,
            default => KeyPair::random(),
        };
        $clientDomain = $case === 'without clientDomain' ? null : 'place.client.com';
        $localKey = in_array($case, ['local key only', 'local key and callback'], true) ? $domain : null;
        $body = in_array($case, ['no client_domain operation', 'without clientDomain'], true)
            ? $this->requestChallengeSuccess($user->getAccountId())
            : $this->requestChallengeValidClientDomainOpSourceAccount($user->getAccountId(), $domain->getAccountId());
        $challenge = Transaction::fromEnvelopeBase64XdrString(json_decode($body, true)['transaction']);
        $hash = $challenge->hash(Network::testnet());
        $responses = [new Response(200, [], $body)];
        $lookup = $clientDomain !== null && $localKey === null && $case !== 'no client_domain operation';
        if ($lookup) {
            $responses[] = new Response(200, [], 'SIGNING_KEY="' . $domain->getAccountId() . '"');
        }
        $responses[] = new Response(200, [], $this->requestJWTSuccess());
        $history = [];
        $webAuth = $this->webAuthRecordingRequests($responses, $history);
        $callbackCalls = 0;
        $callback = function (string &$argument) use ($case, $user, $domain, &$callbackCalls): string {
            $callbackCalls++;
            $transaction = Transaction::fromEnvelopeBase64XdrString($argument);
            if ($case === 'server signature removed') {
                $transaction->setSignatures([]);
            }
            if ($case === 'domain key is server') {
                return $transaction->toEnvelopeXdrBase64();
            }
            if ($case === 'same signature different hint') {
                $signature = $domain->signDecorated($transaction->hash(Network::testnet()));
                $signature->setHint($domain->getHint() ^ "\xff\xff\xff\xff");
                $transaction->setSignatures([...$transaction->getSignatures(), $signature]);
            }
            $transaction->sign($domain, Network::testnet());
            if ($case === 'duplicate domain signature' || $case === 'overlapping signers') {
                $transaction->sign($domain, Network::testnet());
            }
            if ($case === 'overlapping signers') {
                $transaction->sign($user, Network::testnet());
            }
            if ($case === 'payment argument with original answer') {
                $argument = (new TransactionBuilder(new Account($user->getAccountId(), new BigInteger(41))))
                    ->addOperation((new PaymentOperationBuilder($this->serverAccountId, Asset::native(), '1'))->build())
                    ->build()->toEnvelopeXdrBase64();
            }
            return $transaction->toEnvelopeXdrBase64();
        };
        $signers = $case === 'overlapping signers' ? [$user, $domain, $user, $this->serverKeyPair] : [$user];
        $jwt = $webAuth->jwtToken($user->getAccountId(), $signers, clientDomain: $clientDomain,
            clientDomainKeyPair: $localKey, clientDomainSigningCallback: $case === 'local key only' ? null : $callback);
        $this->assertSame($this->successJWTToken, $jwt);
        $this->assertSame(in_array($case, ['no client_domain operation', 'without clientDomain', 'local key only', 'local key and callback'], true) ? 0 : 1, $callbackCalls);
        $this->assertRequestMethods($history, $lookup ? ['GET', 'GET', 'POST'] : ['GET', 'POST']);
        $query = $this->challengeRequestQuery($history);
        if ($clientDomain === null) {
            $this->assertArrayNotHasKey('client_domain', $query);
        } else {
            $this->assertSame('place.client.com', $query['client_domain']);
        }
        if ($lookup) {
            $this->assertSame('https://place.client.com/.well-known/stellar.toml', (string)$history[1]['request']->getUri());
        }
        $posted = XdrTransactionEnvelope::fromEnvelopeBase64XdrString(
            json_decode((string)$history[array_key_last($history)]['request']->getBody(), true)['transaction'],
        );
        $this->assertTrue(
            $challenge->toEnvelopeXdr()->getV1()->getTx()->encode() === $posted->getV1()->getTx()->encode(),
            'The posted transaction body must be byte-identical to the challenge body',
        );
        $signatures = $posted->getV1()->getSignatures();
        $expectedKeys = [$this->serverKeyPair, $domain, $user];
        $expectedCount = 3;
        if (in_array($case, ['no client_domain operation', 'without clientDomain', 'domain key is server', 'domain key is user'], true)) {
            $expectedKeys = [$this->serverKeyPair, $user];
            $expectedCount = 2;
        } elseif ($localKey !== null) {
            $expectedKeys = [$this->serverKeyPair, $user, $domain];
        } elseif ($case === 'same signature different hint') {
            $expectedKeys = [$this->serverKeyPair, $domain, $domain, $user];
            $expectedCount = 4;
        }
        $this->assertCount($expectedCount, $signatures);
        $this->assertSame($challenge->getSignatures()[0]->encode(), $signatures[0]->encode());
        foreach ($expectedKeys as $index => $key) {
            $hint = $case === 'same signature different hint' && $index === 1
                ? $key->getHint() ^ "\xff\xff\xff\xff" : $key->getHint();
            $this->assertSame($hint, $signatures[$index]->getHint());
            $this->assertTrue($key->verifySignature($signatures[$index]->getSignature(), $hash),
                'Every posted signature must verify over the original challenge hash');
        }
    }

    /** @return array<string, array{int, bool, bool}> */
    public static function callbackSignatureLimits(): array
    {
        return [
            'exactly 20' => [18, true, false],
            '20 with overlapping user' => [19, true, true],
            '21 after merging' => [19, false, false],
            '21 before user signing' => [20, false, false],
        ];
    }

    #[DataProvider('callbackSignatureLimits')]
    public function testCallbackSignatureLimit(int $callbackCount, bool $accepted, bool $overlappingUser): void
    {
        $user = KeyPair::random();
        $domain = KeyPair::random();
        $body = $this->requestChallengeValidClientDomainOpSourceAccount($user->getAccountId(), $domain->getAccountId());
        $challenge = Transaction::fromEnvelopeBase64XdrString(json_decode($body, true)['transaction']);
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $body),
            new Response(200, [], 'SIGNING_KEY="' . $domain->getAccountId() . '"'),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $callbackSigners = [];
        for ($i = 0; $i < $callbackCount; $i++) {
            $callbackSigners[] = KeyPair::random();
        }
        $callbackSigners[0] = $domain;
        if ($overlappingUser) {
            $callbackSigners[$callbackCount - 1] = $user;
        }
        $callback = static function (string $argument) use ($callbackSigners): string {
            $transaction = Transaction::fromEnvelopeBase64XdrString($argument);
            $transaction->setSignatures([]);
            foreach ($callbackSigners as $key) {
                $transaction->sign($key, Network::testnet());
            }
            return $transaction->toEnvelopeXdrBase64();
        };
        if (!$accepted) {
            $calls = 0;
            $signer = $this->signerRecordingCalls($user, $calls);
            $error = null;
            try {
                $webAuth->jwtToken($user->getAccountId(), [$signer], clientDomain: 'place.client.com', clientDomainSigningCallback: $callback);
            } catch (\Exception $e) {
                $error = $e;
            }
            $this->assertRequestMethods($history, ['GET', 'GET']);
            if ($callbackCount === 20) {
                $this->assertSame(0, $calls, 'The merged callback signatures already exceed the limit before user signing');
            }
            $this->assertInstanceOf(ChallengeValidationErrorInvalidSignature::class, $error);
            $this->assertSame('Invalid transaction envelope, more than 20 signatures', $error->getMessage());
            return;
        }
        $this->assertSame($this->successJWTToken,
            $webAuth->jwtToken($user->getAccountId(), [$user], clientDomain: 'place.client.com', clientDomainSigningCallback: $callback));
        $this->assertRequestMethods($history, ['GET', 'GET', 'POST']);
        $posted = Transaction::fromEnvelopeBase64XdrString(json_decode((string)$history[2]['request']->getBody(), true)['transaction']);
        $this->assertCount(20, $posted->getSignatures());
        $expectedKeys = $overlappingUser ? [$this->serverKeyPair, ...$callbackSigners] : [$this->serverKeyPair, ...$callbackSigners, $user];
        foreach ($expectedKeys as $index => $key) {
            $this->assertSame($key->getHint(), $posted->getSignatures()[$index]->getHint());
            $this->assertTrue($key->verifySignature($posted->getSignatures()[$index]->getSignature(), $challenge->hash(Network::testnet())));
        }
    }

    /** @return array<string, array{?string, bool, bool, bool?}> */
    public static function domainSigningConditions(): array
    {
        return [
            'ignored domain with key' => ['unavailable.client.com', true, false],
            'public key without domain' => [null, true, false, true],
            'ignored domain with callback' => ['unavailable.client.com', false, false],
            'key without domain' => [null, true, false],
            'callback without domain' => [null, false, false],
            'zero domain with key' => ['0', true, true],
            'zero domain with callback' => ['0', false, true],
        ];
    }

    #[DataProvider('domainSigningConditions')]
    public function testDomainSigningRequiresRequestedDomainAndOperation(?string $domainName, bool $local, bool $operation, bool $publicOnly = false): void
    {
        $user = KeyPair::random();
        $domain = KeyPair::random();
        $body = $operation
            ? $this->requestChallengeValidClientDomainOpSourceAccount($user->getAccountId(), $domain->getAccountId())
            : $this->requestChallengeSuccess($user->getAccountId());
        $history = [];
        $respond = function ($request) use ($domain, $operation, $local, $body): Response {
            if ($request->getUri()->getPath() === '/.well-known/stellar.toml') {
                return $operation && !$local
                    ? new Response(200, [], 'SIGNING_KEY="' . $domain->getAccountId() . '"')
                    : new Response(404);
            }
            return new Response(200, [], $request->getMethod() === 'POST' ? $this->requestJWTSuccess() : $body);
        };
        $stack = HandlerStack::create(new MockHandler([$respond, $respond, $respond]));
        $stack->push(Middleware::history($history));
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain,
            Network::testnet(), new Client(['handler' => $stack]));
        $calls = 0;
        $callback = static function (string $argument) use ($domain, &$calls): string {
            $calls++;
            $transaction = Transaction::fromEnvelopeBase64XdrString($argument);
            $transaction->sign($domain, Network::testnet());
            return $transaction->toEnvelopeXdrBase64();
        };
        $webAuth->jwtToken($user->getAccountId(), [$user], clientDomain: $domainName,
            clientDomainKeyPair: $local ? ($publicOnly ? KeyPair::fromAccountId($domain->getAccountId()) : $domain) : null,
            clientDomainSigningCallback: $local ? null : $callback);
        $this->assertSame($operation && !$local ? 1 : 0, $calls);
        $this->assertRequestMethods($history, $operation && !$local ? ['GET', 'GET', 'POST'] : ['GET', 'POST']);
        $query = $this->challengeRequestQuery($history);
        if ($domainName === null) {
            $this->assertArrayNotHasKey('client_domain', $query);
        } else {
            $this->assertSame($domainName, $query['client_domain']);
        }
        $posted = Transaction::fromEnvelopeBase64XdrString(json_decode(
            (string)$history[array_key_last($history)]['request']->getBody(), true)['transaction']);
        $this->assertCount($operation ? 3 : 2, $posted->getSignatures());
        $keys = $operation ? ($local ? [$this->serverKeyPair, $user, $domain] : [$this->serverKeyPair, $domain, $user])
            : [$this->serverKeyPair, $user];
        foreach ($keys as $index => $key) {
            $this->assertSame($key->getHint(), $posted->getSignatures()[$index]->getHint());
            $this->assertTrue($key->verifySignature($posted->getSignatures()[$index]->getSignature(), $posted->hash(Network::testnet())));
        }
    }

    /** @return array<string, array{string, bool, string}> */
    public static function invalidRequestedDomains(): array
    {
        return [
            'empty with key' => ['', true, 'Client domain must not be empty'],
            'empty without key' => ['', false, 'Client domain must not be empty'],
            'zero without signer' => ['0', false, 'Client domain key pair or Client domain signing callback is missing'],
        ];
    }

    #[DataProvider('invalidRequestedDomains')]
    public function testInvalidClientDomainRejectedBeforeRequest(string $domainName, bool $withKey, string $message): void
    {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([], $history);
        $user = KeyPair::random();
        $calls = 0;
        $signer = $this->signerRecordingCalls($user, $calls);
        $this->assertRejectedWithoutSigningOrPost(
            fn() => $webAuth->jwtToken($user->getAccountId(), [$signer], clientDomain: $domainName,
                clientDomainKeyPair: $withKey ? KeyPair::random() : null),
            $history, $calls, InvalidArgumentException::class, $message, []);
    }

    /** @return array<string, array{int, ?string}> */
    public static function unsuccessfulChallengeStatuses(): array
    {
        return ['created' => [201, null], 'no content' => [204, null], 'bad request' => [400, 'Denied'],
            'zero error' => [201, '0'], 'empty error' => [403, ''], 'server error' => [500, 'Unavailable']];
    }

    #[DataProvider('unsuccessfulChallengeStatuses')]
    public function testChallengeRequiresStatus200(int $status, ?string $error): void
    {
        $user = KeyPair::random();
        $body = $error === null ? $this->requestChallengeSuccess($user->getAccountId()) : json_encode(['error' => $error]);
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([new Response($status, [], $body)], $history);
        $calls = 0;
        $signer = $this->signerRecordingCalls($user, $calls);
        $this->assertRejectedWithoutSigningOrPost(fn() => $webAuth->jwtToken($user->getAccountId(), [$signer]),
            $history, $calls, ChallengeRequestErrorResponse::class,
            $error ?? "Unexpected challenge response status: $status", ['GET'], $status);
    }

    /** @return array<string, array{int, ?string, class-string<\Exception>, string}> */
    public static function unsuccessfulTokenStatuses(): array
    {
        return [
            'created with token' => [201, null, SubmitCompletedChallengeUnknownResponseException::class, '{"token":"test-placeholder"}'],
            'bad request with token' => [400, null, SubmitCompletedChallengeErrorResponseException::class, 'an unknown error occurred'],
            'bad request error' => [400, 'Denied', SubmitCompletedChallengeErrorResponseException::class, 'Denied'],
            'bad request zero error' => [400, '0', SubmitCompletedChallengeErrorResponseException::class, '0'],
        ];
    }

    #[DataProvider('unsuccessfulTokenStatuses')]
    public function testTokenRequiresStatus200(int $status, ?string $error, string $exception, string $message): void
    {
        $user = KeyPair::random();
        $history = [];
        $body = $error === null ? '{"token":"test-placeholder"}' : json_encode(['error' => $error]);
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $this->requestChallengeSuccess($user->getAccountId())), new Response($status, [], $body),
        ], $history);
        $this->expectException($exception);
        $this->expectExceptionMessage($message);
        if ($status === 201) $this->expectExceptionCode(201);
        try {
            $webAuth->jwtToken($user->getAccountId(), [$user]);
        } finally {
            $this->assertRequestMethods($history, ['GET', 'POST']);
        }
    }

    public function testEndpointQueryIsMergedForGetAndUnchangedForPost(): void
    {
        $this->authServer .= '?a=1&label=a%20b%2Bc&tag=x&tag=y';
        $user = KeyPair::random();
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $this->requestChallengeSuccess($user->getAccountId(), 0, 'home + domain')),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $webAuth->jwtToken($user->getAccountId(), [$user], memo: 0, homeDomain: 'home + domain',
            clientDomain: '0', clientDomainKeyPair: KeyPair::random());
        $this->assertSame($this->authServer . '&account=' . $user->getAccountId()
            . '&memo=0&home_domain=home%20%2B%20domain&client_domain=0', (string)$history[0]['request']->getUri());
        $this->assertSame($this->authServer, (string)$history[1]['request']->getUri());
    }

    /** @return array<string, array{string, string}> */
    public static function reservedEndpointParameters(): array
    {
        $cases = [];
        foreach (['account', 'memo', 'home_domain', 'client_domain'] as $name) {
            foreach (['execute', 'buildUrl', 'query parameters', 'web auth'] as $entry) {
                $cases["$entry $name"] = [$name, $entry];
            }
        }
        return $cases;
    }

    #[DataProvider('reservedEndpointParameters')]
    public function testEndpointRejectsReservedQueryParameters(string $name, string $entry): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([]));
        $stack->push(Middleware::history($history));
        $client = new Client(['handler' => $stack]);
        $endpoint = $this->authServer . '?a=1&' . $name . '=old';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Endpoint query must not contain request parameter '$name'");
        try {
            if ($entry === 'web auth') {
                (new WebAuth($endpoint, $this->serverAccountId, $this->domain, Network::testnet(), $client))
                    ->jwtToken($this->clientAccountId, [KeyPair::random()]);
            } else {
                $builder = new ChallengeRequestBuilder($endpoint, $client);
                $builder->forAccountId($this->clientAccountId);
                if ($entry === 'query parameters') $builder->forQueryParameters(['memo' => '0']);
                if ($entry === 'buildUrl') $builder->buildUrl();
                else $builder->execute();
            }
        } finally {
            $this->assertRequestMethods($history, []);
        }
    }

    /** @return array<string, array{bool}> */
    public static function completeChallengeUrls(): array
    {
        return ['built URL' => [true], 'caller URL' => [false]];
    }

    #[DataProvider('completeChallengeUrls')]
    public function testBuilderRequestSendsCompleteUrlUnchanged(bool $built): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], '{"transaction":"fixture"}')]));
        $stack->push(Middleware::history($history));
        $builder = new ChallengeRequestBuilder($this->authServer . '?a=1', new Client(['handler' => $stack]));
        $builder->forAccountId($this->clientAccountId)->forClientDomain('0');
        $url = $built ? $builder->buildUrl() : $this->authServer . '?account=' . $this->clientAccountId;
        $response = $builder->request($url);
        $this->assertSame('fixture', $response->getTransaction());
        $this->assertRequestMethods($history, ['GET']);
        $this->assertSame($built
            ? $this->authServer . '?a=1&account=' . $this->clientAccountId . '&client_domain=0'
            : $this->authServer . '?account=' . $this->clientAccountId,
            (string)$history[0]['request']->getUri());
    }

    /** @return array<string, array{string, array<string, mixed>, string}> */
    public static function endpointQueryBytes(): array
    {
        return [
            'numeric key' => ['https://auth.example.com/?1=v', ['home_domain' => 'a b+c'],
                'https://auth.example.com/?1=v&home_domain=a%20b%2Bc'],
            'literal plus' => ['https://auth.example.com/?k=a+b', ['client_domain' => '0'],
                'https://auth.example.com/?k=a+b&client_domain=0'],
            'null omitted' => ['https://auth.example.com/', ['home_domain' => 'wallet', 'memo' => null],
                'https://auth.example.com/?home_domain=wallet'],
            'only null' => ['https://auth.example.com/?1=v&k=a+b', ['memo' => null],
                'https://auth.example.com/?1=v&k=a+b'],
        ];
    }

    #[DataProvider('endpointQueryBytes')]
    public function testBuilderPreservesEndpointQueryBytes(string $endpoint, array $parameters, string $expected): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], '{"transaction":"fixture"}')]));
        $stack->push(Middleware::history($history));
        $builder = new ChallengeRequestBuilder($endpoint, new Client(['handler' => $stack]));
        $builder->forQueryParameters($parameters)->forAccountId($this->clientAccountId);
        $expected .= '&account=' . $this->clientAccountId;
        $this->assertSame($expected, $builder->buildUrl());
        $this->assertSame('fixture', $builder->execute()->getTransaction());
        $this->assertRequestMethods($history, ['GET']);
        $this->assertSame($expected, (string)$history[0]['request']->getUri());
    }

    public function testBuilderPreservesBracketedArrayQueryParameters(): void
    {
        $client = new Client(['handler' => new MockHandler([])]);
        $builder = (new ChallengeRequestBuilder('https://auth.example.com/', $client))
            ->forAccountId($this->clientAccountId)
            ->forQueryParameters(['extra' => ['a', 'b'], 'space' => 'a b', 'omitted' => null, 'zero' => '0']);
        $this->assertSame(
            'https://auth.example.com/?account=GB4L7JUU5DENUXYH3ANTLVYQL66KQLDDJTN5SF7MWEDGWSGUA375V44V&extra%5B0%5D=a&extra%5B1%5D=b&space=a%20b&zero=0',
            $builder->buildUrl()
        );

        $builder = (new ChallengeRequestBuilder('https://auth.example.com/?existing=a+b#fragment', $client))
            ->forAccountId($this->clientAccountId)
            ->forQueryParameters(['extra' => ['outer' => ['inner' => 'a b']]]);
        $this->assertSame(
            'https://auth.example.com/?existing=a+b&account=GB4L7JUU5DENUXYH3ANTLVYQL66KQLDDJTN5SF7MWEDGWSGUA375V44V&extra%5Bouter%5D%5Binner%5D=a%20b#fragment',
            $builder->buildUrl()
        );

        $builder = (new ChallengeRequestBuilder('https://auth.example.com/', $client))
            ->forAccountId($this->clientAccountId)
            ->forQueryParameters(['extra' => []]);
        $this->assertSame(
            'https://auth.example.com/?account=GB4L7JUU5DENUXYH3ANTLVYQL66KQLDDJTN5SF7MWEDGWSGUA375V44V',
            $builder->buildUrl()
        );
    }

    #[DataProvider('unsuccessfulChallengeStatuses')]
    public function testBuilderPreservesHttpExceptionAndStatus(int $status, ?string $error): void
    {
        foreach (['request', 'execute'] as $entry) {
            $history = [];
            $body = $error === null ? '{"transaction":"fixture"}' : json_encode(['error' => $error]);
            $response = new Response($status, [], $body);
            $stack = HandlerStack::create(new MockHandler([$response]));
            $stack->push(Middleware::history($history));
            $builder = new ChallengeRequestBuilder($this->authServer, new Client(['handler' => $stack]));
            $builder->forAccountId($this->clientAccountId);
            $url = $this->authServer . '?account=' . $this->clientAccountId;
            try {
                if ($entry === 'request') $builder->request($url);
                else $builder->execute();
                $this->fail('Expected HorizonRequestException');
            } catch (HorizonRequestException $e) {
                $this->assertSame($error ?? "Unexpected challenge response status: $status", $e->getMessage());
                $this->assertSame($status, $e->getStatusCode());
                $this->assertSame($response, $e->getHttpResponse());
                $this->assertSame($url, $e->getRequestedUrl());
                $this->assertSame('GET', $e->getHttpMethod());
            } finally {
                $this->assertRequestMethods($history, ['GET']);
            }
        }
    }

    public function testRequestedDomainOperationRequiresDiscoveredSigningKey(): void
    {
        $user = KeyPair::random();
        $domain = KeyPair::random();
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $this->requestChallengeValidClientDomainOpSourceAccount($user->getAccountId(), $domain->getAccountId())),
            new Response(200, [], 'VERSION="2.0.0"'),
        ], $history);
        $calls = 0;
        $signer = $this->signerRecordingCalls($user, $calls);
        $callback = function (string $argument): string {
            $this->fail('Domain signing must not start without a discovered key');
        };
        $this->assertRejectedWithoutSigningOrPost(
            fn() => $webAuth->jwtToken($user->getAccountId(), [$signer], clientDomain: 'place.client.com',
                clientDomainSigningCallback: $callback),
            $history, $calls, InvalidArgumentException::class,
            'Invalid client domain: Could not find signing key in stellar.toml', ['GET', 'GET']);
    }

    /** @return array<string, array{bool}> */
    public static function failedTransfersWithHttp200(): array
    {
        return [
            'RequestException with valid challenge' => [false],
            'BadResponseException with integer Horizon detail' => [true],
        ];
    }

    #[DataProvider('failedTransfersWithHttp200')]
    public function testBuilderRejectsFailedTransferWithHttp200(bool $badResponse): void
    {
        $url = $this->authServer . '?account=' . $this->clientAccountId;
        $response = new Response(200, [], $badResponse
            ? '{"type":"x","detail":5}' : $this->requestChallengeSuccess($this->clientAccountId));
        $original = $badResponse
            ? new BadResponseException('Transfer failed', new Request('GET', $url), $response)
            : new RequestException('Transfer failed', new Request('GET', $url), $response);
        $builder = new ChallengeRequestBuilder($this->authServer, new Client([
            'handler' => HandlerStack::create(new MockHandler([$original])),
        ]));

        try {
            $builder->request($url);
            $this->fail('A failed transfer must not return a challenge');
        } catch (HorizonRequestException $e) {
            $this->assertSame(HorizonRequestException::class, $e::class);
            $this->assertSame('Transfer failed', $e->getMessage());
            $this->assertSame(0, $e->getCode());
            $this->assertSame(200, $e->getStatusCode());
            $this->assertSame($response, $e->getHttpResponse());
            $this->assertNull($e->getHorizonErrorResponse());
            $this->assertSame(\RuntimeException::class, $e->getPrevious()::class);
            $this->assertSame('Transfer failed', $e->getPrevious()->getMessage());
            $this->assertSame(0, $e->getPrevious()->getCode());
            $this->assertSame($original, $e->getPrevious()->getPrevious());
        }
    }

    public function testJwtTokenRejectsFailedChallengeTransferWithHttp200(): void
    {
        $response = new Response(200, [], $this->requestChallengeSuccess($this->clientAccountId));
        $original = new RequestException('Transfer failed', new Request('GET', $this->authServer), $response);
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            $original,
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);

        try {
            $webAuth->jwtToken($this->clientAccountId, [KeyPair::fromSeed($this->clientSecretSeed)]);
            $this->fail('A failed challenge transfer must not return a token');
        } catch (ChallengeRequestErrorResponse $e) {
            $this->assertSame('Transfer failed', $e->getMessage());
            $this->assertSame(200, $e->getCode());
            $previous = $e->getPrevious();
            $this->assertInstanceOf(HorizonRequestException::class, $previous);
            $this->assertSame('Transfer failed', $previous->getMessage());
            $this->assertSame(0, $previous->getCode());
            $this->assertSame(200, $previous->getStatusCode());
            $this->assertSame($response, $previous->getHttpResponse());
            $this->assertNull($previous->getHorizonErrorResponse());
            $this->assertSame(\RuntimeException::class, $previous->getPrevious()::class);
            $this->assertSame($original, $previous->getPrevious()->getPrevious());
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
    }

    public function testBuilderPreservesTransportExceptionWrapping(): void
    {
        $history = [];
        $failure = new ConnectException('Connection unavailable', new Request('GET', $this->authServer));
        $stack = HandlerStack::create(new MockHandler([$failure]));
        $stack->push(Middleware::history($history));
        $builder = new ChallengeRequestBuilder($this->authServer, new Client(['handler' => $stack]));
        $this->expectException(HorizonRequestException::class);
        $this->expectExceptionMessage('Connection unavailable');
        try {
            $builder->forAccountId($this->clientAccountId)->execute();
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
    }

    /** @return array<string, array{bool}> */
    public static function builderDomainSetters(): array
    {
        return ['named setter' => [false], 'query parameters' => [true]];
    }

    #[DataProvider('builderDomainSetters')]
    public function testBuilderRejectsEmptyClientDomain(bool $generic): void
    {
        $builder = new ChallengeRequestBuilder($this->authServer, new Client(['handler' => new MockHandler([])]));
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Client domain must not be empty');
        if ($generic) $builder->forQueryParameters(['client_domain' => '']);
        else $builder->forClientDomain('');
    }

    public function testSigningWithoutCallbackDeduplicatesSignaturesAndPreservesBytes(): void
    {
        $user = KeyPair::random();
        $body = $this->requestChallengeSuccess($user->getAccountId());
        $expected = Transaction::fromEnvelopeBase64XdrString(json_decode($body, true)['transaction']);
        foreach ([$user] as $key) {
            $expected->sign($key, Network::testnet());
        }
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $body), new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $this->assertSame($this->successJWTToken, $webAuth->jwtToken($user->getAccountId(), [$user, $this->serverKeyPair, $user]));
        $this->assertRequestMethods($history, ['GET', 'POST']);
        $this->assertSame($expected->toEnvelopeXdrBase64(), json_decode((string)$history[1]['request']->getBody(), true)['transaction']);
    }

    private function requestChallengeWithSourceAndNonce(MuxedAccount $source, ?string $nonce): string
    {
        $original = Transaction::fromEnvelopeBase64XdrString(json_decode($this->requestChallengeSuccess($this->clientAccountId), true)['transaction']);
        $transaction = new Transaction($source, new BigInteger(0), [
            (new ManageDataOperationBuilder('place.domain.com auth', $nonce))->setSourceAccount($this->clientAccountId)->build(),
            $this->validSecondManageDataOp(),
        ], preconditions: $original->getPreconditions());
        $transaction->sign($this->serverKeyPair, Network::testnet());
        return json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);
    }

    /** @return array<string, array{string}> */
    public static function invalidTransactionSources(): array
    {
        return ['another account' => ['other'], 'user account' => ['user'], 'muxed server zero' => ['muxed zero'], 'muxed server nonzero' => ['muxed nonzero']];
    }

    #[DataProvider('invalidTransactionSources')]
    public function testChallengeRejectsInvalidTransactionSource(string $case): void
    {
        $source = match ($case) {
            'user' => new MuxedAccount($this->clientAccountId),
            'muxed zero' => new MuxedAccount($this->serverAccountId, 0),
            'muxed nonzero' => new MuxedAccount($this->serverAccountId, 7),
            default => new MuxedAccount(KeyPair::random()->getAccountId()),
        };
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $this->requestChallengeWithSourceAndNonce($source, str_repeat('a', 64))),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $calls = 0;
        $signer = $this->signerRecordingCalls(KeyPair::random(), $calls);
        $this->assertRejectedWithoutSigningOrPost(
            fn() => $webAuth->jwtToken($this->clientAccountId, [$signer]), $history, $calls,
            ChallengeValidationErrorInvalidSourceAccount::class,
            'Invalid transaction source account, expected the server signing key', ['GET'],
        );
    }


    /** @return array<string, array{?string}> */
    public static function invalidChallengeNonces(): array
    {
        return [
            'missing' => [null], 'empty' => [''], 'one byte' => ['a'],
            'not base64' => [str_repeat('!', 64)],
            'whitespace in 64 bytes' => [str_repeat('a', 63) . ' '],
            'base64url in 64 bytes' => [str_repeat('-_', 32)],
            // 47 bytes encode to 64 base64 characters with one trailing '='.
            'padded 47 bytes' => [base64_encode(str_repeat('a', 47))],
        ];
    }

    #[DataProvider('invalidChallengeNonces')]
    public function testChallengeRejectsInvalidNonce(?string $nonce): void
    {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $this->requestChallengeWithSourceAndNonce(new MuxedAccount($this->serverAccountId), $nonce)),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $calls = 0;
        $signer = $this->signerRecordingCalls(KeyPair::random(), $calls);
        $this->assertRejectedWithoutSigningOrPost(
            fn() => $webAuth->jwtToken($this->clientAccountId, [$signer]), $history, $calls,
            ChallengeValidationError::class,
            'Invalid nonce in operation[0]: expected 64 base64 bytes encoding 48 bytes', ['GET'],
        );
    }

    /** @return array<string, array{string}> */
    public static function validChallengeNonces(): array
    {
        return [
            'standard base64 punctuation' => [str_repeat('+/', 32)],
            'hexadecimal characters' => [str_repeat('0123456789abcdef', 4)],
            'random bytes encoded' => [base64_encode(random_bytes(48))],
        ];
    }

    #[DataProvider('validChallengeNonces')]
    public function testChallengeAcceptsValidNonce(string $nonce): void
    {
        $this->assertSame(64, strlen($nonce));
        $this->assertSame(48, strlen(base64_decode($nonce, true)));
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $this->requestChallengeWithSourceAndNonce(new MuxedAccount($this->serverAccountId), $nonce)),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $this->assertSame($this->successJWTToken, $webAuth->jwtToken($this->clientAccountId, [KeyPair::fromSeed($this->clientSecretSeed)]));
        $this->assertRequestMethods($history, ['GET', 'POST']);
    }

    /** @return array<string, array{int}> */
    public static function unrequestedMemoIds(): array
    {
        return ['nonzero memo' => [200], 'zero memo' => [0]];
    }

    #[DataProvider('unrequestedMemoIds')]
    public function testChallengeRejectsUnrequestedMemo(int $memo): void
    {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $this->requestChallengeSuccess($this->clientAccountId, $memo)),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $calls = 0;
        $signer = $this->signerRecordingCalls(KeyPair::fromSeed($this->clientSecretSeed), $calls);

        $this->assertRejectedWithoutSigningOrPost(
            fn() => $webAuth->jwtToken($this->clientAccountId, [$signer]), $history, $calls,
            ChallengeValidationErrorInvalidMemoValue::class,
            'Challenge carries a memo that was not requested', ['GET'],
        );
        $this->assertArrayNotHasKey('memo', $this->challengeRequestQuery($history));
    }

    #[DataProvider('unrequestedMemoIds')]
    public function testChallengeRejectsUnrequestedMemoForMuxedAccount(int $memo): void
    {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $this->requestChallengeSuccess($this->clientAccountIdM, $memo)),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $calls = 0;
        $signer = $this->signerRecordingCalls(KeyPair::fromSeed($this->clientSecretSeed), $calls);

        $this->assertRejectedWithoutSigningOrPost(
            fn() => $webAuth->jwtToken($this->clientAccountIdM, [$signer]), $history, $calls,
            ChallengeValidationErrorMemoAndMuxedAccount::class,
            'Memo and muxed account (M...) found', ['GET'],
        );
        $this->assertArrayNotHasKey('memo', $this->challengeRequestQuery($history));
    }

    /** @return array<string, array{string}> */
    public static function requestedHomeDomains(): array {
        return [
            'different domain' => ['fake.com'],
            'zero string' => ['0'],
        ];
    }

    #[DataProvider('requestedHomeDomains')]
    public function testRequestedHomeDomainIsSentAndValidated(string $homeDomain): void {
        $history = [];
        $challenge = $homeDomain === 'fake.com'
            ? $this->requestChallengeInvalidHomeDomain($this->clientAccountId)
            : $this->requestChallengeSuccess($this->clientAccountId, homeDomain: '0');
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $challenge),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);

        try {
            $this->assertSame($this->successJWTToken,
                $webAuth->jwtToken($this->clientAccountId, [$userKeyPair], homeDomain: $homeDomain));
        } finally {
            $this->assertSame($homeDomain, $this->challengeRequestQuery($history)['home_domain'] ?? null);
        }
        $this->assertCount(2, $history);
        $this->assertSame('GET', $history[0]['request']->getMethod());
        $this->assertSame('POST', $history[1]['request']->getMethod());
    }

    #[DataProvider('requestedHomeDomains')]
    public function testRequestedHomeDomainRejectsConstructorDomain(string $homeDomain): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $this->requestChallengeSuccess($this->clientAccountId)),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $calls = 0;
        $userKeyPair = $this->signerRecordingCalls(KeyPair::fromSeed($this->clientSecretSeed), $calls);

        try {
            $webAuth->jwtToken($this->clientAccountId, [$userKeyPair], homeDomain: $homeDomain);
            $this->fail('Expected a home domain mismatch');
        } catch (ChallengeValidationErrorInvalidHomeDomain $e) {
            $this->assertSame('invalid home domain in operation[0]', $e->getMessage());
        }
        $this->assertSame(0, $calls, 'The user must not be asked to sign a challenge for the wrong home domain');
        $this->assertSame($homeDomain, $this->challengeRequestQuery($history)['home_domain'] ?? null);
        $this->assertCount(1, $history);
        $this->assertSame('GET', $history[0]['request']->getMethod());
    }

    /** @return array<string, array{string|null}> */
    public static function constructorHomeDomains(): array {
        return [
            'null' => [null],
            'empty string' => [''],
            'explicit constructor domain' => ['place.domain.com'],
        ];
    }

    #[DataProvider('constructorHomeDomains')]
    public function testConstructorHomeDomainIsValidated(?string $homeDomain): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $this->requestChallengeSuccess($this->clientAccountId)),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);

        $this->assertSame($this->successJWTToken,
            $webAuth->jwtToken($this->clientAccountId, [$userKeyPair], homeDomain: $homeDomain));
        $query = $this->challengeRequestQuery($history);
        if ($homeDomain === 'place.domain.com') {
            $this->assertSame('place.domain.com', $query['home_domain']);
        } else {
            $this->assertArrayNotHasKey('home_domain', $query);
        }
        $this->assertCount(2, $history);
        $this->assertSame('GET', $history[0]['request']->getMethod());
        $this->assertSame('POST', $history[1]['request']->getMethod());
    }

    public function testMemoSuccess(): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeSuccess($this->clientAccountId, $this->testMemo)),
            new Response(200, ['X-Foo' => 'Bar'], $this->requestJWTSuccess())
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair], $this->testMemo);
        $this->assertSame($this->successJWTToken, $jwtToken);
        $this->assertRequestMethods($history, ['GET', 'POST']);
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
            $this->assertSame('Invalid memo id: expected an unsigned 64-bit integer', $e->getMessage());
            $this->assertInstanceOf(InvalidArgumentException::class, $e->getPrevious());
            $this->assertSame($reason, $e->getPrevious()->getMessage());
        }
        $this->assertSame([], $history);
    }

    #[DataProvider('memoIdValues')]
    public function testForMemoUnsignedDecimalQuery(int|string $memo): void {
        $builder = new ChallengeRequestBuilder($this->authServer, new Client());
        $builder->forAccountId($this->clientAccountId);
        $this->assertSame($builder, $builder->forMemo($memo));
        $this->assertSame($this->authServer . '?account=' . $this->clientAccountId . '&memo=' . $memo, $builder->buildUrl());
    }

    #[DataProvider('invalidMemoIds')]
    public function testForMemoRejectsInvalidId(int|string $memo, string $reason): void {
        $builder = new ChallengeRequestBuilder($this->authServer, new Client());
        $builder->forAccountId($this->clientAccountId);
        try {
            $builder->forMemo($memo);
            $this->fail('Expected an invalid memo');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Invalid memo id: expected an unsigned 64-bit integer', $e->getMessage());
            $this->assertInstanceOf(InvalidArgumentException::class, $e->getPrevious());
            $this->assertSame($reason, $e->getPrevious()->getMessage());
        }
        $this->assertSame($this->authServer . '?account=' . $this->clientAccountId, $builder->buildUrl());
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
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(400, ['Content-Length' => 0])
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $this->expectException(ChallengeRequestErrorResponse::class);
        $this->expectExceptionMessage('Unexpected challenge response status: 400');
        try {
            $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
    }


    public function testGetChallengeInvalidMemoType(): void
    {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $this->requestChallengeInvalidMemoType($this->clientAccountId)),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $calls = 0;
        $signer = $this->signerRecordingCalls(KeyPair::fromSeed($this->clientSecretSeed), $calls);

        $this->assertRejectedWithoutSigningOrPost(
            fn() => $webAuth->jwtToken($this->clientAccountId, [$signer]), $history, $calls,
            ChallengeValidationErrorInvalidMemoType::class,
            'invalid memo type', ['GET'],
        );
    }

    public function testGetChallengeInvalidMemoValue(): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInvalidMemoValue($this->clientAccountId))
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $this->expectException(ChallengeValidationErrorInvalidMemoValue::class);
        $this->expectExceptionMessage('invalid memo value');
        try {
            $webAuth->jwtToken($userAccountId, [$userKeyPair], $this->testMemo);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
    }

    public function testGetChallengeInvalidSequenceNumber(): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInvalidSequenceNumber($this->clientAccountIdM))
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $this->expectException(ChallengeValidationErrorInvalidSeqNr::class);
        $this->expectExceptionMessage('Invalid transaction, sequence number not 0');
        try {
            $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
    }

    public function testGetChallengeInvalidFirstOpSource(): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInvalidFirstOpSourceAccount())
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $this->expectException(ChallengeValidationErrorInvalidSourceAccount::class);
        $this->expectExceptionMessage('invalid source account in operation[0]');
        try {
            $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
    }

    public function testGetChallengeInvalidSecondOpSource(): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInvalidSecondOpSourceAccount($this->clientAccountId))
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $this->expectException(ChallengeValidationErrorInvalidSourceAccount::class);
        $this->expectExceptionMessage('invalid source account in operation[1]');
        try {
            $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
    }

    public function testGetChallengeInvalidHomeDomain(): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInvalidHomeDomain($this->clientAccountId))
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $this->expectException(ChallengeValidationErrorInvalidHomeDomain::class);
        $this->expectExceptionMessage('invalid home domain in operation[0]');
        try {
            $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
    }

    public function testGetChallengeInvalidWebAuthDomain(): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInvalidWebAuth($this->clientAccountId))
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $this->expectException(ChallengeValidationErrorInvalidWebAuthDomain::class);
        $this->expectExceptionMessage('invalid web auth domain in operation[1]');
        try {
            $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
    }

    public function testGetChallengeInvalidTimeBounds(): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInvalidTimeBounds($this->clientAccountId))
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $this->expectException(ChallengeValidationErrorInvalidTimeBounds::class);
        $this->expectExceptionMessage('Invalid transaction, invalid time bounds');
        try {
            $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
    }

    public function testGetChallengeMissingTimeBounds(): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeMissingTimeBounds($this->clientAccountId))
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $this->expectException(ChallengeValidationErrorInvalidTimeBounds::class);
        $this->expectExceptionMessage('Invalid transaction, missing time bounds');
        try {
            $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
    }

    public function testGetChallengeInfiniteTimeBounds(): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInfiniteTimeBounds($this->clientAccountId))
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $this->expectException(ChallengeValidationErrorInvalidTimeBounds::class);
        $this->expectExceptionMessage('Invalid transaction, requires non-infinite time bounds');
        try {
            $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
    }

    public function testMemoWithMuxedAccountRejected(): void
    {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([], $history);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('memo cannot be used if accountId is a muxed account');
        try {
            $webAuth->jwtToken($this->clientAccountIdM, [KeyPair::fromSeed($this->clientSecretSeed)], $this->testMemo);
        } finally {
            $this->assertRequestMethods($history, []);
        }
    }

    public function testGetChallengeInvalidBase64(): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], json_encode(['transaction' => '@@@not-valid-base64@@@']))
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64-encoded challenge transaction');
        try {
            $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
    }

    public function testGetChallengeMissingMemo(): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeSuccess($this->clientAccountId))
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $this->expectException(ChallengeValidationErrorInvalidMemoValue::class);
        $this->expectExceptionMessage('missing memo');
        try {
            $webAuth->jwtToken($userAccountId, [$userKeyPair], $this->testMemo);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
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
        $this->assertRequestMethods($history, ['GET', 'POST']);
        $this->assertFalse($history[0]['request']->hasHeader('Authorization'));
        $this->assertFalse($history[1]['request']->hasHeader('Authorization'));
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
        $this->assertRequestMethods($history, ['GET', 'POST']);
    }

    public function testSubmitChallengeErrorResponse(): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeSuccess($this->clientAccountId)),
            new Response(400, ['X-Foo' => 'Bar'], $this->requestJWTError())
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $this->expectException(SubmitCompletedChallengeErrorResponseException::class);
        $this->expectExceptionMessage('The provided transaction is not valid');
        try {
            $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } finally {
            $this->assertRequestMethods($history, ['GET', 'POST']);
        }
    }

    public function testSubmitChallengeMalformedJson(): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeSuccess($this->clientAccountId)),
            new Response(200, ['X-Foo' => 'Bar'], '{not valid json')
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $this->expectException(SubmitCompletedChallengeErrorResponseException::class);
        $this->expectExceptionMessage('Error in json_decode: Syntax error');
        try {
            $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } finally {
            $this->assertRequestMethods($history, ['GET', 'POST']);
        }
    }

    public function testSubmitChallengeNoTokenNoError(): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeSuccess($this->clientAccountId)),
            new Response(200, ['X-Foo' => 'Bar'], json_encode(['unexpected' => 'value']))
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $this->expectException(SubmitCompletedChallengeErrorResponseException::class);
        $this->expectExceptionMessage('an unknown error occurred');
        try {
            $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } finally {
            $this->assertRequestMethods($history, ['GET', 'POST']);
        }
    }

    public function testSubmitChallengeTimeout(): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeSuccess($this->clientAccountId)),
            new Response(504, ['X-Foo' => 'Bar'], '')
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $this->expectException(SubmitCompletedChallengeTimeoutResponseException::class);
        $this->expectExceptionMessageMatches('/^$/D');
        try {
            $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } finally {
            $this->assertRequestMethods($history, ['GET', 'POST']);
        }
    }

    public function testSubmitChallengeUnknownResponse(): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeSuccess($this->clientAccountId)),
            new Response(503, ['X-Foo' => 'Bar'], 'service unavailable')
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $this->expectException(SubmitCompletedChallengeUnknownResponseException::class);
        $this->expectExceptionMessage('service unavailable');
        try {
            $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } finally {
            $this->assertRequestMethods($history, ['GET', 'POST']);
        }
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
        $history = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], $toml)
        ]));
        $stack->push(Middleware::history($history));
        $client = new Client(['handler' => $stack]);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No WEB_AUTH_ENDPOINT found in stellar.toml');
        try {
            WebAuth::fromDomain('place.domain.com', Network::testnet(), $client);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
    }

    public function testFromDomainMissingSigningKey(): void {
        $toml = 'WEB_AUTH_ENDPOINT="https://auth.place.domain.com/auth"';
        $history = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], $toml)
        ]));
        $stack->push(Middleware::history($history));
        $client = new Client(['handler' => $stack]);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No auth server SIGNING_KEY found in stellar.toml');
        try {
            WebAuth::fromDomain('place.domain.com', Network::testnet(), $client);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
    }

    public function testGetChallengeInvalidOperationType(): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInvalidOperationType($this->clientAccountId))
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $this->expectException(ChallengeValidationErrorInvalidOperationType::class);
        $this->expectExceptionMessage('invalid type of operation 2');
        try {
            $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
    }

    public function testGetChallengeInvalidSignature(): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInvalidSignature($this->clientAccountId))
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $this->expectException(ChallengeValidationErrorInvalidSignature::class);
        $this->expectExceptionMessage('Invalid transaction envelope, invalid signature');
        try {
            $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
    }

    /** @return array<string, array{string, bool}> */
    public static function challengeSignatureLists(): array
    {
        return [
            'server first' => ['server first', true],
            'server second' => ['server second', true],
            'invalid matching hint first' => ['invalid matching hint first', true],
            'foreign matching hint only' => ['foreign matching hint only', false],
            'no signatures' => ['no signatures', false],
            'server signature with wrong hint' => ['server signature with wrong hint', false],
        ];
    }

    #[DataProvider('challengeSignatureLists')]
    public function testChallengeFindsServerSignatureByHintAndPreservesOthers(string $case, bool $valid): void
    {
        $user = KeyPair::fromSeed($this->clientSecretSeed);
        $body = json_decode($this->requestChallengeMultipleSignature($user->getAccountId()), true);
        $challenge = Transaction::fromEnvelopeBase64XdrString($body['transaction']);
        [$server, $foreign] = $challenge->getSignatures();
        $collision = new XdrDecoratedSignature($server->getHint(), $foreign->getSignature());
        $signatures = match ($case) {
            'server first' => [$server, $foreign],
            'server second' => [$foreign, $server],
            'invalid matching hint first' => [$collision, $server],
            'foreign matching hint only' => [$collision],
            'no signatures' => [],
            'server signature with wrong hint' => [new XdrDecoratedSignature($foreign->getHint(), $server->getSignature())],
        };
        $envelope = $challenge->toEnvelopeXdr();
        $envelope->getV1()->setSignatures($signatures);
        $body['transaction'] = base64_encode($envelope->encode());
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], json_encode($body)),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $calls = 0;
        $signer = $this->signerRecordingCalls($user, $calls);
        if (!$valid) {
            $this->assertRejectedWithoutSigningOrPost(
                fn() => $webAuth->jwtToken($user->getAccountId(), [$signer]), $history, $calls,
                ChallengeValidationErrorInvalidSignature::class,
                'Invalid transaction envelope, invalid signature', ['GET'],
            );
            return;
        }
        $this->assertSame($this->successJWTToken, $webAuth->jwtToken($user->getAccountId(), [$signer]));
        $this->assertSame(1, $calls);
        $this->assertRequestMethods($history, ['GET', 'POST']);
        $posted = XdrTransactionEnvelope::fromEnvelopeBase64XdrString(
            json_decode((string)$history[1]['request']->getBody(), true)['transaction'],
        );
        $this->assertSame($envelope->getV1()->getTx()->encode(), $posted->getV1()->getTx()->encode());
        $actual = $posted->getV1()->getSignatures();
        $this->assertCount(3, $actual);
        $this->assertSame($signatures[0]->encode(), $actual[0]->encode());
        $this->assertSame($signatures[1]->encode(), $actual[1]->encode());
        $this->assertSame($user->getHint(), $actual[2]->getHint());
        $this->assertTrue($user->verifySignature($actual[2]->getSignature(), $challenge->hash(Network::testnet())));
    }

    /** @return array<string, array{bool, string, class-string<\Exception>, string}> */
    public static function invalidChallengeEnvelopeForms(): array
    {
        $cases = [
            'zero operations' => [ChallengeValidationError::class, 'invalid number of operations (0)'],
            'undecodable typed operation' => [ChallengeValidationErrorInvalidOperationType::class, 'invalid type of operation 0'],
            'sequence before operations' => [ChallengeValidationErrorInvalidSeqNr::class, 'Invalid transaction, sequence number not 0'],
            'source before operations' => [ChallengeValidationErrorInvalidSourceAccount::class, 'Invalid transaction source account, expected the server signing key'],
            'memo before operations' => [ChallengeValidationErrorInvalidMemoType::class, 'invalid memo type'],
            'operation source before type' => [ChallengeValidationErrorInvalidSourceAccount::class, 'invalid source account in operation[0]'],
        ];
        $result = [];
        foreach ([false, true] as $v0) {
            foreach ($cases as $case => [$exception, $message]) {
                $result[($v0 ? 'V0 ' : 'V1 ') . $case] = [$v0, $case, $exception, $message];
            }
        }
        return $result;
    }

    #[DataProvider('invalidChallengeEnvelopeForms')]
    public function testChallengeValidationMatchesAcrossEnvelopeForms(
        bool $v0, string $case, string $exceptionClass, string $message,
    ): void {
        $user = KeyPair::random();
        $body = json_decode($this->requestChallengeSuccess($user->getAccountId()), true);
        $envelope = XdrTransactionEnvelope::fromEnvelopeBase64XdrString($body['transaction']);
        $tx = $envelope->getV1()->getTx();
        $tx->setOperations([]);
        if (in_array($case, ['undecodable typed operation', 'operation source before type'], true)) {
            $operation = (new PaymentOperationBuilder($this->serverAccountId,
                Asset::createNonNativeAsset('USD', $this->serverAccountId), '1'))
                ->setSourceAccount($user->getAccountId())->build()->toXdr();
            // Empty credit asset codes decode as XDR but cannot become typed assets.
            $operation->getBody()->getPaymentOp()->getAsset()->getAlphaNum4()->setAssetCode('');
            $tx->setOperations([$operation]);
            if ($case === 'operation source before type') {
                $operation->setSourceAccount(null);
            }
        } elseif ($case === 'sequence before operations') {
            $tx->setSequenceNumber(new XdrSequenceNumber(new BigInteger(1)));
            $tx->setSourceAccount(MuxedAccount::fromAccountId($user->getAccountId())->toXdr());
            $tx->setMemo(Memo::text('invalid')->toXdr());
        } elseif ($case === 'source before operations') {
            $tx->setSourceAccount(MuxedAccount::fromAccountId($user->getAccountId())->toXdr());
            $tx->setMemo(Memo::text('invalid')->toXdr());
        } elseif ($case === 'memo before operations') {
            $tx->setMemo(Memo::text('invalid')->toXdr());
        }
        if ($v0) {
            $envelope = new XdrTransactionEnvelope(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0));
            $envelope->setV0(new XdrTransactionV0Envelope(new XdrTransactionV0(
                $tx->getSourceAccount()->getEd25519(), $tx->getSequenceNumber(), $tx->getOperations(),
                $tx->getFee(), $tx->getMemo(), $tx->getTimeBounds(),
            ), []));
        }
        $body['transaction'] = base64_encode($envelope->encode());
        $decoded = XdrTransactionEnvelope::fromEnvelopeBase64XdrString($body['transaction']);
        if ($case === 'undecodable typed operation') {
            $operations = ($v0 ? $decoded->getV0() : $decoded->getV1())->getTx()->getOperations();
            try {
                AbstractOperation::fromXdr($operations[0]);
                $this->fail('The fixture must fail typed operation conversion');
            } catch (\RuntimeException $e) {
                $this->assertSame('invalid asset code length: 0', $e->getMessage());
            }
        }
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([new Response(200, [], json_encode($body))], $history);
        $calls = 0;
        $signer = $this->signerRecordingCalls($user, $calls);
        $this->assertRejectedWithoutSigningOrPost(
            fn() => $webAuth->jwtToken($user->getAccountId(), [$signer]), $history, $calls,
            $exceptionClass, $message, ['GET'],
        );
    }

    /** @return array<string, array{bool, bool}> */
    public static function v0ChallengeFlows(): array
    {
        return ['local' => [false, false], 'callback' => [true, false], 'tampered callback' => [true, true]];
    }

    #[DataProvider('v0ChallengeFlows')]
    public function testV0ChallengePreservesEnvelopeBodyAndNetworkSignatures(bool $withCallback, bool $tamper): void
    {
        $user = KeyPair::random();
        $domain = KeyPair::random();
        $body = json_decode($withCallback
            ? $this->requestChallengeValidClientDomainOpSourceAccount($user->getAccountId(), $domain->getAccountId())
            : $this->requestChallengeSuccess($user->getAccountId()), true);
        $transaction = Transaction::fromEnvelopeBase64XdrString($body['transaction']);
        $tx = $transaction->toXdr();
        // The typed API preserves V0 envelopes but only constructs V1 envelopes.
        $envelope = new XdrTransactionEnvelope(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0));
        $envelope->setV0(new XdrTransactionV0Envelope(new XdrTransactionV0(
            $tx->getSourceAccount()->getEd25519(), $tx->getSequenceNumber(), $tx->getOperations(),
            $tx->getFee(), $tx->getMemo(), $tx->getTimeBounds(),
        ), $transaction->getSignatures()));
        $body['transaction'] = base64_encode($envelope->encode());
        // V0 is signed over the V1-form payload, including the ENVELOPE_TYPE_TX tag (2).
        $hash = hash('sha256', hash('sha256', Network::testnet()->getNetworkPassphrase(), true)
            . pack('N', 2) . $tx->encode(), true);
        $callbackCalls = 0;
        $callback = function (string &$argument) use ($body, $domain, $tamper, &$callbackCalls): string {
            $callbackCalls++;
            $this->assertSame($body['transaction'], $argument);
            $signed = Transaction::fromEnvelopeBase64XdrString($argument);
            if ($tamper) {
                $signed->setFee(1234);
            }
            $signed->sign($domain, Network::testnet());
            $argument = $signed->toEnvelopeXdrBase64();
            return $argument;
        };
        $history = [];
        $responses = [new Response(200, [], json_encode($body))];
        if ($withCallback) {
            $responses[] = new Response(200, [], 'SIGNING_KEY="' . $domain->getAccountId() . '"');
        }
        $responses[] = new Response(200, [], $this->requestJWTSuccess());
        $webAuth = $this->webAuthRecordingRequests($responses, $history);
        $calls = 0;
        $signer = $this->signerRecordingCalls($user, $calls);
        $authenticate = fn() => $webAuth->jwtToken($user->getAccountId(), [$signer],
            clientDomain: $withCallback ? 'place.client.com' : null,
            clientDomainSigningCallback: $withCallback ? $callback : null);
        if ($tamper) {
            $this->assertRejectedWithoutSigningOrPost($authenticate, $history, $calls,
                ChallengeValidationError::class, 'Client domain signing callback returned a different transaction',
                ['GET', 'GET']);
            $this->assertSame(1, $callbackCalls);
            return;
        }
        $this->assertSame($this->successJWTToken, $authenticate());
        $this->assertSame($withCallback ? 1 : 0, $callbackCalls);
        $this->assertSame(1, $calls);
        $this->assertRequestMethods($history, $withCallback ? ['GET', 'GET', 'POST'] : ['GET', 'POST']);
        $posted = XdrTransactionEnvelope::fromEnvelopeBase64XdrString(
            json_decode((string)$history[$withCallback ? 2 : 1]['request']->getBody(), true)['transaction'],
        );
        $this->assertSame(XdrEnvelopeType::ENVELOPE_TYPE_TX_V0, $posted->getType()->getValue());
        $this->assertSame($envelope->getV0()->getTx()->encode(), $posted->getV0()->getTx()->encode());
        $signatures = $posted->getV0()->getSignatures();
        $keys = $withCallback ? [$this->serverKeyPair, $domain, $user] : [$this->serverKeyPair, $user];
        $this->assertCount($withCallback ? 3 : 2, $signatures);
        $this->assertSame($transaction->getSignatures()[0]->encode(), $signatures[0]->encode());
        foreach ($keys as $index => $key) {
            $this->assertSame($key->getHint(), $signatures[$index]->getHint());
            $this->assertTrue($key->verifySignature($signatures[$index]->getSignature(), $hash));
        }
    }

    public function testFeeBumpChallengeIsRejectedBeforeSigning(): void
    {
        $user = KeyPair::random();
        $body = json_decode($this->requestChallengeSuccess($user->getAccountId()), true);
        $transaction = Transaction::fromEnvelopeBase64XdrString($body['transaction']);
        $body['transaction'] = (new FeeBumpTransactionBuilder($transaction))
            ->setFeeAccount($this->serverAccountId)->setBaseFee(200)->build()->toEnvelopeXdrBase64();
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([new Response(200, [], json_encode($body))], $history);
        $calls = 0;
        $signer = $this->signerRecordingCalls($user, $calls);
        $this->assertRejectedWithoutSigningOrPost(
            fn() => $webAuth->jwtToken($user->getAccountId(), [$signer]), $history, $calls,
            ChallengeValidationError::class, 'Invalid transaction type received in challenge', ['GET'],
        );
    }

    public function testMissingTransactionTakesPrecedenceOverDifferentNetwork(): void
    {
        $user = KeyPair::random();
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([new Response(200, [], json_encode([
            'network_passphrase' => 'Public Global Stellar Network ; September 2015',
        ]))], $history);
        $calls = 0;
        $signer = $this->signerRecordingCalls($user, $calls);
        $this->assertRejectedWithoutSigningOrPost(
            fn() => $webAuth->jwtToken($user->getAccountId(), [$signer]), $history, $calls,
            ChallengeRequestErrorResponse::class, 'Error parsing challenge response', ['GET'],
        );
    }

    /** @return array<string, array{mixed, bool, bool}> */
    public static function advertisedNetworkPassphrases(): array
    {
        return [
            'absent' => [null, false, false], 'null' => [null, false, true],
            'empty' => ['', false, true], 'number' => [123, false, true], 'array' => [[], false, true],
            'equal' => ['Test SDF Network ; September 2015', false, true],
            'different' => ['Public Global Stellar Network ; September 2015', false, true],
            'zero' => ['0', false, true],
            'different before validation' => ['Public Global Stellar Network ; September 2015', true, true],
        ];
    }

    #[DataProvider('advertisedNetworkPassphrases')]
    public function testAdvertisedNetworkPassphraseIsCheckedBeforeSigning(mixed $passphrase, bool $invalidBody, bool $includeField): void
    {
        $user = KeyPair::random();
        $body = json_decode($this->requestChallengeSuccess($user->getAccountId()), true);
        if ($includeField) {
            $body['network_passphrase'] = $passphrase;
        }
        $this->assertSame(is_string($passphrase) ? $passphrase : null,
            ChallengeResponse::fromJson($body)->getNetworkPassphrase());
        if ($invalidBody) {
            $body['transaction'] = '!';
        }
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], json_encode($body)), new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $calls = 0;
        $signer = $this->signerRecordingCalls($user, $calls);
        if (is_string($passphrase) && $passphrase !== '' && $passphrase !== 'Test SDF Network ; September 2015') {
            $this->assertRejectedWithoutSigningOrPost(
                fn() => $webAuth->jwtToken($user->getAccountId(), [$signer]), $history, $calls,
                ChallengeValidationError::class,
                'Challenge network passphrase "' . $passphrase . '" does not match configured network passphrase "Test SDF Network ; September 2015"',
                ['GET'],
            );
            return;
        }
        $this->assertSame($this->successJWTToken, $webAuth->jwtToken($user->getAccountId(), [$signer]));
        $this->assertSame(1, $calls);
        $this->assertRequestMethods($history, ['GET', 'POST']);
    }

    public function testGetChallengeInvalidClientDomainSourceAccount(): void {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeInvalidClientDomainOpSourceAccount($this->clientAccountId))
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $this->expectException(ChallengeValidationErrorInvalidSourceAccount::class);
        $this->expectExceptionMessage('invalid source account in operation[2]');
        try {
            $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
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

    public function testChallengeWithinValidTimeWindow(): void
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

        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $challengeResponse)
        ], $history);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);

        $this->expectException(ChallengeValidationErrorInvalidTimeBounds::class);
        $this->expectExceptionMessage('Invalid transaction, invalid time bounds');
        try {
            $webAuth->jwtToken($this->clientAccountId, [$userKeyPair]);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
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
        $envelope = XdrTransactionEnvelope::fromEnvelopeBase64XdrString(
            json_decode($this->requestChallengeSuccess($this->clientAccountId), true)['transaction']);
        // The typed builder rejects zero operations, so construct this invalid server response in XDR.
        $envelope->getV1()->getTx()->setOperations([]);
        $envelope->getV1()->setSignatures([$this->serverKeyPair->signDecorated(
            hash('sha256', hash('sha256', Network::testnet()->getNetworkPassphrase(), true)
                . pack('N', XdrEnvelopeType::ENVELOPE_TYPE_TX) . $envelope->getV1()->getTx()->encode(), true))]);
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], json_encode(['transaction' => base64_encode($envelope->encode())])),
        ], $history);
        $this->expectException(ChallengeValidationError::class);
        $this->expectExceptionMessage('invalid number of operations (0)');
        try {
            $webAuth->jwtToken($this->clientAccountId, [KeyPair::fromSeed($this->clientSecretSeed)]);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
    }

    public function testChallengeWithDifferentNetworkPassphrase(): void
    {
        $transactionAccount = new Account($this->serverAccountId, new BigInteger(-1));
        $transaction = (new TransactionBuilder($transactionAccount))
            ->addOperation($this::validFirstManageDataOp($this->clientAccountId))
            ->addOperation($this::validSecondManageDataOp())
            ->setTimeBounds($this::validTimeBounds())
            ->build();
        $transaction->sign($this->serverKeyPair, Network::testnet());
        $challengeResponse = json_encode(['transaction' => $transaction->toEnvelopeXdrBase64()]);

        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $challengeResponse)
        ], $history, Network::public());
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);

        $this->expectException(ChallengeValidationErrorInvalidSignature::class);
        $this->expectExceptionMessage('Invalid transaction envelope, invalid signature');
        try {
            $webAuth->jwtToken($this->clientAccountId, [$userKeyPair]);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
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

        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, ['X-Foo' => 'Bar'], $challengeResponse)
        ], $history);
        $webAuth->setGracePeriod(0);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);

        $this->expectException(ChallengeValidationErrorInvalidTimeBounds::class);
        $this->expectExceptionMessage('Invalid transaction, invalid time bounds');
        try {
            $webAuth->jwtToken($this->clientAccountId, [$userKeyPair]);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
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
                (new DateTime)->setTimestamp($now - 700),
                (new DateTime)->setTimestamp($now - 450)
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

    /** @return array<string, array{string}> */
    public static function invalidAccountInputs(): array
    {
        return array_combine($cases = ['seed', 'empty', 'checksum', 'contract'],
            array_map(static fn(string $case): array => [$case], $cases));
    }

    #[DataProvider('invalidAccountInputs')]
    public function testInvalidAccountNeverReachesTransport(string $case): void
    {
        $account = match ($case) {
            'seed' => KeyPair::random()->getSecretSeed(),
            'empty' => '',
            'checksum' => substr($this->clientAccountId, 0, -1) . 'A',
            default => \Soneso\StellarSDK\Crypto\StrKey::encodeContractId(str_repeat('a', 32)),
        };
        foreach (['jwt', 'account', 'parameters', 'url'] as $entry) {
            $history = [];
            $stack = HandlerStack::create(new MockHandler([new Response(200, [], '{}')]));
            $stack->push(Middleware::history($history));
            $client = new Client(['handler' => $stack]);
            $builder = new ChallengeRequestBuilder($this->authServer, $client);
            $error = null;
            try {
                match ($entry) {
                    'jwt' => (new WebAuth($this->authServer, $this->serverAccountId, $this->domain,
                        Network::testnet(), $client))->jwtToken($account, [KeyPair::random()]),
                    'account' => $builder->forAccountId($account)->execute(),
                    'parameters' => $builder->forQueryParameters(['account' => $account])->execute(),
                    default => $builder->request($this->authServer . '?account=' . rawurlencode($account)),
                };
            } catch (\Throwable $e) {
                $error = $e;
            }
            // Do not include the rejected input or recorded request in assertion output.
            $this->assertSame(0, count($history), $entry . ' must reject before transport');
            $this->assertInstanceOf(InvalidArgumentException::class, $error);
            $this->assertSame('Client account must be a valid G... or M... account ID', $error->getMessage());
        }
    }

    /** @return array<string, array{string}> */
    public static function invalidSigners(): array
    {
        return ['wrong type' => ['string'], 'public only' => ['public']];
    }

    #[DataProvider('invalidSigners')]
    public function testInvalidSignersRejectedBeforeGet(string $case): void
    {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([new Response(200, [], '{}')], $history);
        $signers = match ($case) {
            'string' => [KeyPair::random(), 'not a signer'],
            default => [KeyPair::fromAccountId($this->clientAccountId)],
        };
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Every client signer must be a KeyPair with a private key');
        try {
            $webAuth->jwtToken($this->clientAccountId, $signers);
        } finally {
            $this->assertRequestMethods($history, []);
        }
    }

    public function testGetTransportFailureHasNormalException(): void
    {
        $history = [];
        $cause = new ConnectException('Connection unavailable', new Request('GET', $this->authServer));
        $webAuth = $this->webAuthRecordingRequests([$cause], $history);
        $calls = 0;
        $signer = $this->signerRecordingCalls(KeyPair::random(), $calls);
        try {
            $webAuth->jwtToken($this->clientAccountId, [$signer]);
            $this->fail('Connection failure must be reported');
        } catch (ChallengeRequestErrorResponse $e) {
            $this->assertSame('Connection unavailable', $e->getMessage());
            $this->assertSame(0, $e->getCode());
            $this->assertInstanceOf(HorizonRequestException::class, $e->getPrevious());
            $this->assertSame($cause, $e->getPrevious()->getPrevious());
        } finally {
            $this->assertSame(0, $calls);
            $this->assertRequestMethods($history, ['GET']);
        }
    }

    /** @return array<string, array{string}> */
    public static function redirectRequests(): array
    {
        return ['challenge' => ['GET'], 'token' => ['POST'], 'discovery' => ['discovery']];
    }

    #[DataProvider('redirectRequests')]
    public function testHttpsDowngradeRedirectIsBlocked(string $phase): void
    {
        $history = [];
        $responses = $phase === 'POST'
            ? [new Response(200, [], $this->requestChallengeSuccess($this->clientAccountId))] : [];
        $responses[] = new Response(307, ['Location' => 'http://localhost/leak']);
        $responses[] = new Response(200, [], '{}');
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($history));
        $client = new Client(['handler' => $stack, 'allow_redirects' => ['protocols' => ['http', 'https']]]);
        $this->expectException(match ($phase) {
            'GET' => ChallengeRequestErrorResponse::class,
            'POST' => \GuzzleHttp\Exception\BadResponseException::class,
            default => Exception::class,
        });
        $this->expectExceptionMessage($phase === 'GET' ? 'Unexpected challenge response status: 307'
            : 'Redirect URI, http://localhost/leak, does not use one of the allowed redirect protocols: https');
        try {
            if ($phase === 'discovery') {
                WebAuth::fromDomain($this->domain, Network::testnet(), $client);
            } else {
                (new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet(), $client))
                    ->jwtToken($this->clientAccountId, [KeyPair::fromSeed($this->clientSecretSeed)]);
            }
        } finally {
            $this->assertRequestMethods($history, $phase === 'POST' ? ['GET', 'POST'] : ['GET']);
        }
    }

    /** @return array<string, array{int, bool}> */
    public static function localSignatureCounts(): array
    {
        return ['several' => [3, true], 'twenty total' => [19, true], 'twenty one total' => [20, false]];
    }

    #[DataProvider('localSignatureCounts')]
    public function testLocalSignatureLimitAndSeveralSigners(int $count, bool $accepted): void
    {
        $signers = [];
        for ($i = 0; $i < $count; $i++) $signers[] = KeyPair::random();
        $history = [];
        $body = $this->requestChallengeSuccess($this->clientAccountId);
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $body), new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        if (!$accepted) {
            $this->expectException(ChallengeValidationErrorInvalidSignature::class);
            $this->expectExceptionMessage('Invalid transaction envelope, more than 20 signatures');
        }
        try {
            $result = $webAuth->jwtToken($this->clientAccountId, $signers);
        } finally {
            $this->assertRequestMethods($history, $accepted ? ['GET', 'POST'] : ['GET']);
        }
        $this->assertSame($this->successJWTToken, $result);
        $posted = Transaction::fromEnvelopeBase64XdrString(json_decode((string)$history[1]['request']->getBody(), true)['transaction']);
        $challenge = Transaction::fromEnvelopeBase64XdrString(json_decode($body, true)['transaction']);
        $this->assertSame($challenge->toXdr()->encode(), $posted->toXdr()->encode());
        $this->assertCount($count + 1, $posted->getSignatures());
        foreach ([$this->serverKeyPair, ...$signers] as $i => $key) {
            $this->assertSame($key->getHint(), $posted->getSignatures()[$i]->getHint());
            $this->assertTrue($key->verifySignature($posted->getSignatures()[$i]->getSignature(), $challenge->hash(Network::testnet())));
        }
    }

    public function testReversedTimeBoundsRejectedWithinGrace(): void
    {
        $history = [];
        $body = $this->requestChallengeWithTimeBounds(new TimeBounds(
            (new DateTime())->setTimestamp(time() + 30), (new DateTime())->setTimestamp(time() - 30)));
        $webAuth = $this->webAuthRecordingRequests([new Response(200, [], $body)], $history);
        $this->expectException(ChallengeValidationErrorInvalidTimeBounds::class);
        $this->expectExceptionMessage('Invalid transaction, invalid time bounds');
        try {
            $webAuth->jwtToken($this->clientAccountId, [KeyPair::fromSeed($this->clientSecretSeed)]);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
        }
    }

    /** @return array<string, array{string, string, bool}> */
    public static function webAuthHostCases(): array
    {
        return ['mixed case' => ['https://API.Stellar.ORG/auth', 'api.STELLAR.org', true],
            'port endpoint host only' => ['https://API.Stellar.ORG:8443/auth', 'api.STELLAR.org', true],
            'port endpoint host and port' => ['https://API.Stellar.ORG:8443/auth', 'api.STELLAR.org:8443', true],
            'explicit default port' => ['https://api.stellar.org:443/auth', 'api.stellar.org:443', true],
            'port endpoint wrong host' => ['https://api.stellar.org:8443/auth', 'other.example.com:8443', false],
            'port endpoint wrong port' => ['https://api.stellar.org:8443/auth', 'api.stellar.org:8444', false],
            'no port endpoint with port' => ['https://api.stellar.org/auth', 'api.stellar.org:443', false],
            'IPv6 host' => ['https://[2001:db8::1]/auth', '[2001:db8::1]', true],
            'IPv6 explicit port host only' => ['https://[2001:db8::1]:8443/auth', '[2001:db8::1]', true],
            'IPv6 explicit port' => ['https://[2001:db8::1]:8443/auth', '[2001:db8::1]:8443', true],
            'IPv6 wrong port' => ['https://[2001:db8::1]:8443/auth', '[2001:db8::1]:8444', false],
            'IPv6 absent port' => ['https://[2001:db8::1]/auth', '[2001:db8::1]:443', false],
            'IPv6 different spelling' => ['https://[2001:db8::1]/auth', '[2001:db8:0:0:0:0:0:1]', false],
            'numeric strings' => ['https://0e1/auth', '0e2', false]];
    }

    #[DataProvider('webAuthHostCases')]
    public function testWebAuthHostUsesStrictCaseInsensitiveComparison(string $endpoint, string $host, bool $accepted): void
    {
        $builder = (new TransactionBuilder(new Account($this->serverAccountId, new BigInteger(-1))))
            ->addOperation($this->validFirstManageDataOp($this->clientAccountId))
            ->addOperation((new ManageDataOperationBuilder('web_auth_domain', $host))->setSourceAccount($this->serverAccountId)->build())
            ->setTimeBounds($this->validTimeBounds());
        $challenge = $builder->build();
        $challenge->sign($this->serverKeyPair, Network::testnet());
        $history = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode(['transaction' => $challenge->toEnvelopeXdrBase64()])),
            new Response(200, [], $this->requestJWTSuccess()),
        ]));
        $stack->push(Middleware::history($history));
        $webAuth = new WebAuth($endpoint, $this->serverAccountId, $this->domain, Network::testnet(), new Client(['handler' => $stack]));
        if (!$accepted) {
            $this->expectException(ChallengeValidationErrorInvalidWebAuthDomain::class);
            $this->expectExceptionMessage('invalid web auth domain in operation[1]');
        }
        try {
            $result = $webAuth->jwtToken($this->clientAccountId, [KeyPair::fromSeed($this->clientSecretSeed)]);
        } finally {
            $this->assertRequestMethods($history, $accepted ? ['GET', 'POST'] : ['GET']);
        }
        $this->assertSame($this->successJWTToken, $result);
    }

    /** @return array<string, array{string}> */
    public static function invalidServerKeys(): array
    {
        return ['muxed' => ['muxed'], 'seed' => ['seed'], 'empty' => ['empty'], 'checksum' => ['checksum']];
    }

    #[DataProvider('invalidServerKeys')]
    public function testConstructorRejectsInvalidServerKey(string $case): void
    {
        $key = match ($case) {
            'muxed' => $this->clientAccountIdM,
            'seed' => KeyPair::random()->getSecretSeed(),
            'empty' => '',
            default => substr($this->serverAccountId, 0, -1) . 'A',
        };
        $history = [];
        $stack = HandlerStack::create(new MockHandler([]));
        $stack->push(Middleware::history($history));
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Server signing key must be a valid G... account ID');
        try {
            new WebAuth($this->authServer, $key, $this->domain, Network::testnet(), new Client(['handler' => $stack]));
        } finally {
            $this->assertRequestMethods($history, []);
        }
    }

    /** @return array<string, array{string, string}> */
    public static function invalidBuilderRequests(): array
    {
        $cases = [];
        foreach (['fluent', 'parameters', 'url'] as $entry) {
            foreach (['muxed memo', 'negative memo', 'missing account', 'repeated account', 'array account', 'array memo', 'empty domain'] as $case) {
                if ($entry === 'fluent' && in_array($case, ['repeated account', 'array account', 'array memo', 'empty domain'], true)) continue;
                $cases[$entry . ' ' . $case] = [$entry, $case];
            }
        }
        $cases['url bare memo'] = ['url', 'bare memo'];
        return $cases;
    }

    #[DataProvider('invalidBuilderRequests')]
    public function testBuilderRejectsInvalidAccountMemoBeforeSend(string $entry, string $case): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], '{}')]));
        $stack->push(Middleware::history($history));
        $builder = new ChallengeRequestBuilder($this->authServer, new Client(['handler' => $stack]));
        $parameters = match ($case) {
            'muxed memo' => ['account' => $this->clientAccountIdM, 'memo' => '0'],
            'negative memo' => ['account' => $this->clientAccountId, 'memo' => '-1'],
            'array memo' => ['account' => $this->clientAccountId, 'memo' => ['0', '1']],
            'bare memo' => ['account' => $this->clientAccountIdM],
            'empty domain' => ['account' => $this->clientAccountId, 'client_domain' => ''],
            'repeated account', 'array account' => ['account' => [$this->clientAccountId, $this->clientAccountIdM]],
            default => [],
        };
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(match ($case) {
            'muxed memo', 'bare memo' => 'memo cannot be used if accountId is a muxed account',
            'negative memo', 'array memo' => 'Invalid memo id',
            'empty domain' => 'Client domain must not be empty',
            default => 'Client account must be a valid G... or M... account ID',
        });
        try {
            if ($entry === 'url') {
                $query = $case === 'repeated account' ? 'account=' . $this->clientAccountId . '&account=' . $this->clientAccountIdM
                    : http_build_query($parameters);
                if ($case === 'bare memo') $query .= '&memo';
                if ($case === 'array memo') $query = 'account=' . $this->clientAccountId . '&memo=0&memo=1';
                $builder->request($this->authServer . '?' . $query);
            } elseif ($entry === 'parameters') {
                $builder->forQueryParameters($parameters)->execute();
            } else {
                if (isset($parameters['account'])) $builder->forAccountId($parameters['account']);
                if (isset($parameters['memo'])) $builder->forMemo($parameters['memo']);
                $builder->execute();
            }
        } finally {
            $this->assertRequestMethods($history, []);
        }
    }

    /** @return array<string, array{string, string, string}> */
    public static function rejectedOperationCases(): array
    {
        return [
            'first payment' => ['payment', ChallengeValidationErrorInvalidOperationType::class, 'invalid type of operation 0'],
            'missing source' => ['missing source', ChallengeValidationErrorInvalidSourceAccount::class, 'invalid source account in operation[0]'],
            'muxed instead of G' => ['muxed', ChallengeValidationErrorInvalidSourceAccount::class, 'invalid source account in operation[0]'],
            'unrequested domain' => ['unrequested', ChallengeValidationErrorInvalidSourceAccount::class, 'invalid source account in operation[1]'],
            'wrong requested domain source' => ['requested', ChallengeValidationErrorInvalidSourceAccount::class, 'invalid source account in operation[1]'],
            'null web auth domain' => ['null host', ChallengeValidationErrorInvalidWebAuthDomain::class, 'invalid web auth domain in operation[1]'],
        ];
    }

    #[DataProvider('rejectedOperationCases')]
    public function testOperationValidationRejectsBeforeSigning(string $case, string $exception, string $message): void
    {
        $domain = KeyPair::random();
        $first = match ($case) {
            'payment' => $this->invalidOperationType($this->clientAccountId),
            'missing source' => (new ManageDataOperationBuilder($this->domain . ' auth', base64_encode(random_bytes(48))))->build(),
            'muxed' => $this->validFirstManageDataOp($this->clientAccountIdM),
            default => $this->validFirstManageDataOp($this->clientAccountId),
        };
        $second = match ($case) {
            'unrequested' => $this->validClientDomainManageDataOp($domain->getAccountId()),
            'requested' => $this->validClientDomainManageDataOp($this->serverAccountId),
            'null host' => (new ManageDataOperationBuilder('web_auth_domain', null))->setSourceAccount($this->serverAccountId)->build(),
            default => $this->validSecondManageDataOp(),
        };
        $challenge = (new TransactionBuilder(new Account($this->serverAccountId, new BigInteger(-1))))
            ->addOperation($first)->addOperation($second)->setTimeBounds($this->validTimeBounds())->build();
        $challenge->sign($this->serverKeyPair, Network::testnet());
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], json_encode(['transaction' => $challenge->toEnvelopeXdrBase64()])),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $calls = 0;
        $signer = $this->signerRecordingCalls(KeyPair::fromSeed($this->clientSecretSeed), $calls);
        $this->assertRejectedWithoutSigningOrPost(
            fn() => $webAuth->jwtToken($this->clientAccountId, [$signer],
                clientDomain: $case === 'requested' ? 'place.client.com' : null, clientDomainKeyPair: $domain),
            $history, $calls, $exception, $message, ['GET']);
    }

    /** @return array<string, array{bool}> */
    public static function repeatedClientDomainSources(): array
    {
        return ['matching sources' => [true], 'wrong second source' => [false]];
    }

    #[DataProvider('repeatedClientDomainSources')]
    public function testRepeatedClientDomainOperationsResolveOnceAndCheckEverySource(bool $matching): void
    {
        $domain = KeyPair::random();
        $challenge = (new TransactionBuilder(new Account($this->serverAccountId, new BigInteger(-1))))
            ->addOperation($this->validFirstManageDataOp($this->clientAccountId))
            ->addOperation($this->validClientDomainManageDataOp($domain->getAccountId()))
            ->addOperation($this->validClientDomainManageDataOp($matching ? $domain->getAccountId() : $this->serverAccountId))
            ->setTimeBounds($this->validTimeBounds())->build();
        $challenge->sign($this->serverKeyPair, Network::testnet());
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], json_encode(['transaction' => $challenge->toEnvelopeXdrBase64()])),
            new Response(200, [], 'SIGNING_KEY="' . $domain->getAccountId() . '"'),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $calls = 0;
        $callbackCalls = 0;
        $signer = $this->signerRecordingCalls(KeyPair::fromSeed($this->clientSecretSeed), $calls);
        $authenticate = function () use ($webAuth, $signer, $domain, &$callbackCalls): string {
            return $webAuth->jwtToken($this->clientAccountId, [$signer],
                clientDomain: 'place.client.com',
                clientDomainSigningCallback: function (string $xdr) use ($domain, &$callbackCalls): string {
                    $callbackCalls++;
                    $transaction = Transaction::fromEnvelopeBase64XdrString($xdr);
                    $transaction->sign($domain, Network::testnet());
                    return $transaction->toEnvelopeXdrBase64();
                });
        };
        if (!$matching) {
            $this->assertRejectedWithoutSigningOrPost($authenticate, $history, $calls,
                ChallengeValidationErrorInvalidSourceAccount::class, 'invalid source account in operation[2]', ['GET', 'GET']);
            $this->assertSame(0, $callbackCalls);
        } else {
            $this->assertTrue($this->successJWTToken === $authenticate());
            $this->assertSame(1, $calls);
            $this->assertSame(1, $callbackCalls);
            $this->assertRequestMethods($history, ['GET', 'GET', 'POST']);
        }
        $this->assertSame('https://place.client.com/.well-known/stellar.toml', (string)$history[1]['request']->getUri());
        $this->assertCount(1, array_filter($history, static fn(array $exchange): bool =>
            $exchange['request']->getUri()->getPath() === '/.well-known/stellar.toml'));
    }

    /** @return array<string, array{bool, class-string<Exception>, string}> */
    public static function laterInvalidWebAuthDomains(): array
    {
        return [
            'wrong value' => [false, ChallengeValidationErrorInvalidWebAuthDomain::class, 'invalid web auth domain in operation[2]'],
            'wrong source' => [true, ChallengeValidationErrorInvalidSourceAccount::class, 'invalid source account in operation[2]'],
        ];
    }

    #[DataProvider('laterInvalidWebAuthDomains')]
    public function testEveryWebAuthDomainOperationIsValidated(bool $wrongSource, string $exception, string $message): void
    {
        $challenge = (new TransactionBuilder(new Account($this->serverAccountId, new BigInteger(-1))))
            ->addOperation($this->validFirstManageDataOp($this->clientAccountId))
            ->addOperation($this->validSecondManageDataOp())
            ->addOperation($wrongSource ? $this->secondManageDataOpInvalidSourceAccount() : $this->invalidWebAuthOp())
            ->setTimeBounds($this->validTimeBounds())->build();
        $challenge->sign($this->serverKeyPair, Network::testnet());
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], json_encode(['transaction' => $challenge->toEnvelopeXdrBase64()])),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $calls = 0;
        $signer = $this->signerRecordingCalls(KeyPair::fromSeed($this->clientSecretSeed), $calls);
        $this->assertRejectedWithoutSigningOrPost(
            fn() => $webAuth->jwtToken($this->clientAccountId, [$signer]), $history, $calls,
            $exception, $message, ['GET']);
    }

    public function testServerExtensionWithoutWebAuthDomainIsAccepted(): void
    {
        $challenge = (new TransactionBuilder(new Account($this->serverAccountId, new BigInteger(-1))))
            ->addOperation($this->validFirstManageDataOp($this->clientAccountId))
            ->addOperation((new ManageDataOperationBuilder('future_extension', 'value'))->setSourceAccount($this->serverAccountId)->build())
            ->setTimeBounds($this->validTimeBounds())->build();
        $challenge->sign($this->serverKeyPair, Network::testnet());
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], json_encode(['transaction' => $challenge->toEnvelopeXdrBase64()])),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $this->assertSame($this->successJWTToken,
            $webAuth->jwtToken($this->clientAccountId, [KeyPair::fromSeed($this->clientSecretSeed)]));
        $this->assertRequestMethods($history, ['GET', 'POST']);
        $posted = Transaction::fromEnvelopeBase64XdrString(json_decode((string)$history[1]['request']->getBody(), true)['transaction']);
        $this->assertSame($challenge->toXdr()->encode(), $posted->toXdr()->encode());
        $this->assertCount(2, $posted->getSignatures());
        $this->assertTrue(KeyPair::fromAccountId($this->clientAccountId)->verifySignature(
            $posted->getSignatures()[1]->getSignature(), $challenge->hash(Network::testnet())));
    }

    /** @return array<string, array{bool}> */
    public static function incomingV2TimeBounds(): array
    {
        return ['valid time bounds' => [true], 'absent time bounds' => [false]];
    }

    #[DataProvider('incomingV2TimeBounds')]
    public function testIncomingV2ChallengeTimeBounds(bool $withTimeBounds): void
    {
        $preconditions = new TransactionPreconditions();
        // A V2-only field selects PRECOND_V2 through the typed transaction API.
        $preconditions->setMinSeqLedgerGap(1);
        if ($withTimeBounds) $preconditions->setTimeBounds($this->validTimeBounds());
        $challenge = (new TransactionBuilder(new Account($this->serverAccountId, new BigInteger(-1))))
            ->addOperation($this->validFirstManageDataOp($this->clientAccountId))
            ->setPreconditions($preconditions)->build();
        $this->assertSame(XdrPreconditionType::V2, $challenge->toXdr()->getPreconditions()->getType()->getValue());
        $challenge->sign($this->serverKeyPair, Network::testnet());
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], json_encode(['transaction' => $challenge->toEnvelopeXdrBase64()])),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $calls = 0;
        $user = KeyPair::fromSeed($this->clientSecretSeed);
        $signer = $this->signerRecordingCalls($user, $calls);
        if (!$withTimeBounds) {
            $this->assertRejectedWithoutSigningOrPost(
                fn() => $webAuth->jwtToken($this->clientAccountId, [$signer]), $history, $calls,
                ChallengeValidationErrorInvalidTimeBounds::class, 'Invalid transaction, missing time bounds', ['GET']);
            return;
        }
        $this->assertTrue($this->successJWTToken === $webAuth->jwtToken($this->clientAccountId, [$signer]));
        $this->assertSame(1, $calls);
        $this->assertRequestMethods($history, ['GET', 'POST']);
        $posted = Transaction::fromEnvelopeBase64XdrString(json_decode((string)$history[1]['request']->getBody(), true)['transaction']);
        $this->assertSame($challenge->toXdr()->encode(), $posted->toXdr()->encode());
        $this->assertCount(2, $posted->getSignatures());
        $this->assertTrue($user->verifySignature($posted->getSignatures()[1]->getSignature(), $challenge->hash(Network::testnet())));
    }

    public function testFutureMinimumUsesConfiguredGrace(): void
    {
        $now = time();
        // Keep both runs well inside the same interval so clock jitter cannot decide the result.
        $body = $this->requestChallengeWithTimeBounds(new TimeBounds(
            (new DateTime())->setTimestamp($now + 150), (new DateTime())->setTimestamp($now + 600)));
        foreach ([300, 0] as $grace) {
            $history = [];
            $webAuth = $this->webAuthRecordingRequests([
                new Response(200, [], $body), new Response(200, [], $this->requestJWTSuccess()),
            ], $history);
            $webAuth->setGracePeriod($grace);
            $calls = 0;
            $signer = $this->signerRecordingCalls(KeyPair::fromSeed($this->clientSecretSeed), $calls);
            if ($grace === 0) {
                $this->assertRejectedWithoutSigningOrPost(
                    fn() => $webAuth->jwtToken($this->clientAccountId, [$signer]), $history, $calls,
                    ChallengeValidationErrorInvalidTimeBounds::class, 'Invalid transaction, invalid time bounds', ['GET']);
            } else {
                $this->assertTrue($this->successJWTToken === $webAuth->jwtToken($this->clientAccountId, [$signer]));
                $this->assertSame(1, $calls);
                $this->assertRequestMethods($history, ['GET', 'POST']);
            }
        }
    }

    public function testFutureTimeBoundsRejectedBeforeSigning(): void
    {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([new Response(200, [], $this->requestChallengeWithTimeBounds(
            new TimeBounds((new DateTime())->setTimestamp(time() + 600), (new DateTime())->setTimestamp(time() + 900)))),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $calls = 0;
        $signer = $this->signerRecordingCalls(KeyPair::random(), $calls);
        $this->assertRejectedWithoutSigningOrPost(
            fn() => $webAuth->jwtToken($this->clientAccountId, [$signer]), $history, $calls,
            ChallengeValidationErrorInvalidTimeBounds::class, 'Invalid transaction, invalid time bounds', ['GET']);
    }

    public function testAuthorizedCrossOriginRedirectStripsBearerHeader(): void
    {
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(302, ['Location' => 'https://other.example.com/challenge']),
            new Response(200, [], $this->requestChallengeSuccess($this->clientAccountId)),
            new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $this->assertTrue($this->successJWTToken === $webAuth->jwtToken($this->clientAccountId,
            [KeyPair::fromSeed($this->clientSecretSeed)], authorizationSigner: KeyPair::random()));
        $this->assertRequestMethods($history, ['GET', 'GET', 'POST']);
        $this->assertTrue(str_starts_with($history[0]['request']->getHeaderLine('Authorization'), 'Bearer '));
        $this->assertSame('https://other.example.com/challenge', (string)$history[1]['request']->getUri());
        $this->assertFalse($history[1]['request']->hasHeader('Authorization'));
        $this->assertFalse($history[2]['request']->hasHeader('Authorization'));
    }

    /** @return array<string, array{int, bool, bool}> */
    public static function tokenPostRedirects(): array
    {
        return [
            '301 default success' => [301, false, true],
            '302 default success' => [302, false, true],
            '301 strict success' => [301, true, true],
            '302 strict success' => [302, true, true],
            '301 default error' => [301, false, false],
            '302 default error' => [302, false, false],
            '301 strict error' => [301, true, false],
            '302 strict error' => [302, true, false],
        ];
    }

    #[DataProvider('tokenPostRedirects')]
    public function testTokenPostRedirectPreservesCallerMethodPolicy(int $status, bool $strict, bool $success): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], $this->requestChallengeSuccess($this->clientAccountId)),
            new Response($status, ['Location' => 'https://api.stellar.org/token']),
            $success ? new Response(200, [], $this->requestJWTSuccess()) : new Response(400, [], '{"error":"denied"}'),
        ]));
        $stack->push(Middleware::history($history));
        $client = new Client(['handler' => $stack, 'allow_redirects' => $strict ? ['strict' => true] : true]);
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet(), $client);
        try {
            $token = $webAuth->jwtToken($this->clientAccountId, [KeyPair::fromSeed($this->clientSecretSeed)]);
            $this->assertTrue($success);
            $this->assertTrue($this->successJWTToken === $token);
        } catch (SubmitCompletedChallengeErrorResponseException $e) {
            $this->assertFalse($success);
            $this->assertSame('denied', $e->getMessage());
        }
        $this->assertRequestMethods($history, $strict ? ['GET', 'POST', 'POST'] : ['GET', 'POST', 'GET']);
        $this->assertSame('https://api.stellar.org/token', (string)$history[2]['request']->getUri());
        $this->assertArrayHasKey('transaction', json_decode((string)$history[1]['request']->getBody(), true));
        $this->assertSame($strict ? (string)$history[1]['request']->getBody() : '', (string)$history[2]['request']->getBody());
    }

    public function testDiscoveryAndHttpsRedirectsPreserveAuthenticationFlow(): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(302, ['Location' => 'https://config.example.com/stellar.toml']),
            new Response(200, [], 'WEB_AUTH_ENDPOINT="' . $this->authServer . '"' . "\n" . 'SIGNING_KEY="' . $this->serverAccountId . '"'),
            new Response(307, ['Location' => 'https://api.stellar.org/challenge']),
            new Response(200, [], $this->requestChallengeSuccess($this->clientAccountId)),
            new Response(307, ['Location' => 'https://api.stellar.org/token']),
            new Response(200, [], $this->requestJWTSuccess()),
        ]));
        $stack->push(Middleware::history($history));
        $client = new Client(['handler' => $stack, 'allow_redirects' => true]);
        $webAuth = WebAuth::fromDomain($this->domain, Network::testnet(), $client);
        $this->assertSame($this->successJWTToken,
            $webAuth->jwtToken($this->clientAccountId, [KeyPair::fromSeed($this->clientSecretSeed)]));
        $this->assertRequestMethods($history, ['GET', 'GET', 'GET', 'GET', 'POST', 'POST']);
        $this->assertSame('https://place.domain.com/.well-known/stellar.toml', (string)$history[0]['request']->getUri());
        $this->assertSame($this->authServer . '?account=' . $this->clientAccountId, (string)$history[2]['request']->getUri());
        $this->assertSame($this->authServer, (string)$history[4]['request']->getUri());
        $this->assertSame((string)$history[4]['request']->getBody(), (string)$history[5]['request']->getBody());
    }

    /** @return array<string, array{string, bool}> */
    public static function discoveryFailures(): array
    {
        return ['home status' => ['status', false], 'home connection' => ['connection', false],
            'client status' => ['status', true], 'client connection' => ['connection', true]];
    }

    #[DataProvider('discoveryFailures')]
    public function testDiscoveryFailureUsesNormalError(string $failure, bool $clientDomain): void
    {
        $history = [];
        $domain = KeyPair::random();
        $responses = $clientDomain ? [new Response(200, [],
            $this->requestChallengeValidClientDomainOpSourceAccount($this->clientAccountId, $domain->getAccountId()))] : [];
        $responses[] = $failure === 'status' ? new Response(404) : new ConnectException('Discovery unavailable',
            new Request('GET', 'https://place.domain.com/.well-known/stellar.toml'));
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($history));
        $client = new Client(['handler' => $stack, 'http_errors' => false]);
        $this->expectException($clientDomain ? InvalidArgumentException::class : Exception::class);
        $this->expectExceptionMessage(($clientDomain ? 'Invalid client domain: ' : '') . 'Stellar toml not found. '
            . ($failure === 'status' ? 'Response status code 404' : 'Discovery unavailable'));
        try {
            if ($clientDomain) {
                $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet(), $client);
                $webAuth->jwtToken($this->clientAccountId, [KeyPair::fromSeed($this->clientSecretSeed)],
                    clientDomain: 'place.domain.com', clientDomainSigningCallback: function (): string {
                        $this->fail('Discovery failure must prevent callback invocation');
                    });
            } else {
                WebAuth::fromDomain($this->domain, Network::testnet(), $client);
            }
        } finally {
            $this->assertRequestMethods($history, $clientDomain ? ['GET', 'GET'] : ['GET']);
        }
    }

    public function testLocalhostHttpAndDisabledRedirectsRemainSupported(): void
    {
        $challenge = (new TransactionBuilder(new Account($this->serverAccountId, new BigInteger(-1))))
            ->addOperation($this->validFirstManageDataOp($this->clientAccountId))
            ->setTimeBounds($this->validTimeBounds())->build();
        $challenge->sign($this->serverKeyPair, Network::testnet());
        foreach (['http://localhost/auth', $this->authServer] as $endpoint) {
            $history = [];
            $stack = HandlerStack::create(new MockHandler([
                new Response(200, [], 'WEB_AUTH_ENDPOINT="' . $endpoint . '"' . "\n" . 'SIGNING_KEY="' . $this->serverAccountId . '"'),
                new Response(200, [], json_encode(['transaction' => $challenge->toEnvelopeXdrBase64()])),
                new Response(200, [], $this->requestJWTSuccess()),
            ]));
            $stack->push(Middleware::history($history));
            $client = new Client(['handler' => $stack, 'allow_redirects' => false]);
            $webAuth = WebAuth::fromDomain($this->domain, Network::testnet(), $client);
            $this->assertSame($this->successJWTToken,
                $webAuth->jwtToken($this->clientAccountId, [KeyPair::fromSeed($this->clientSecretSeed)]));
            $this->assertRequestMethods($history, ['GET', 'GET', 'POST']);
            foreach ($history as $exchange) $this->assertFalse($exchange['options']['allow_redirects']);
            $this->assertSame($endpoint . '?account=' . $this->clientAccountId, (string)$history[1]['request']->getUri());
            $this->assertSame($endpoint, (string)$history[2]['request']->getUri());
        }
    }

    /** @return array<string, array{mixed, mixed}> */
    public static function redirectSettings(): array
    {
        return [
            'null' => [null, null],
            'false' => [false, false],
            'empty array' => [[], []],
            'no protocols' => [['protocols' => []], false],
            'http only' => [['protocols' => ['http']], false],
            'mixed protocols' => [['protocols' => ['http', 'https']], ['https']],
            'other settings' => [['max' => 2, 'strict' => true, 'referer' => true, 'track_redirects' => true], ['https']],
        ];
    }

    /** @return array<int, array{mixed, mixed, bool}> */
    public static function authorizationRedirectSettings(): array
    {
        return array_map(static fn(array $case): array => [...$case, true], array_values(self::redirectSettings()));
    }

    #[DataProvider('redirectSettings')]
    #[DataProvider('authorizationRedirectSettings')]
    public function testAllHttpsRequestsPreserveRedirectSettings(mixed $settings, mixed $protocols, bool $authorize = false): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], 'WEB_AUTH_ENDPOINT="' . $this->authServer . '"' . "\n" . 'SIGNING_KEY="' . $this->serverAccountId . '"'),
            new Response(200, [], $this->requestChallengeSuccess($this->clientAccountId)),
            new Response(200, [], $this->requestJWTSuccess()),
        ]));
        $stack->push(Middleware::history($history));
        $client = new Client(['handler' => $stack, 'allow_redirects' => $settings]);
        $webAuth = WebAuth::fromDomain($this->domain, Network::testnet(), $client);
        $this->assertSame($this->successJWTToken,
            $webAuth->jwtToken($this->clientAccountId, [KeyPair::fromSeed($this->clientSecretSeed)],
                authorizationSigner: $authorize ? static fn(array $claims): string => 'opaque-answer' : null));
        $this->assertRequestMethods($history, ['GET', 'GET', 'POST']);
        $this->assertSame($authorize, $history[1]['request']->hasHeader('Authorization'));
        foreach ($history as $exchange) {
            $actual = $exchange['options']['allow_redirects'] ?? null;
            if ($settings === null || $settings === false || $settings === []) {
                $this->assertSame($settings, $actual);
            } elseif ($protocols === false) {
                $this->assertFalse($actual);
            } else {
                $this->assertSame($protocols, $actual['protocols']);
                foreach (['max' => 5, 'strict' => false, 'referer' => false, 'track_redirects' => false] as $key => $default) {
                    $this->assertSame($settings[$key] ?? $default, $actual[$key]);
                }
            }
        }
        if (is_array($settings) && $protocols === false) {
            foreach (['discovery', 'GET', 'POST'] as $phase) {
                $redirectHistory = [];
                $responses = $phase === 'POST'
                    ? [new Response(200, [], $this->requestChallengeSuccess($this->clientAccountId))] : [];
                $responses[] = new Response(302, ['Location' => 'https://other.example.com/destination']);
                $responses[] = new Response(200, [], '{}');
                $redirectStack = HandlerStack::create(new MockHandler($responses));
                $redirectStack->push(Middleware::history($redirectHistory));
                $redirectClient = new Client(['handler' => $redirectStack, 'allow_redirects' => $settings]);
                try {
                    if ($phase === 'discovery') {
                        WebAuth::fromDomain($this->domain, Network::testnet(), $redirectClient);
                    } else {
                        (new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet(), $redirectClient))
                            ->jwtToken($this->clientAccountId, [KeyPair::fromSeed($this->clientSecretSeed)],
                                authorizationSigner: $authorize ? static fn(array $claims): string => 'opaque-answer' : null);
                    }
                    $this->fail('A disabled redirect must reach the unexpected-status handler');
                } catch (Exception $e) {
                    $this->assertSame(match ($phase) {
                        'discovery' => Exception::class,
                        'GET' => ChallengeRequestErrorResponse::class,
                        'POST' => SubmitCompletedChallengeUnknownResponseException::class,
                    }, $e::class);
                    $this->assertSame(match ($phase) {
                        'discovery' => 'Stellar toml not found. Response status code 302',
                        'GET' => 'Unexpected challenge response status: 302',
                        'POST' => '',
                    }, $e->getMessage());
                    if ($phase === 'POST') $this->assertSame(302, $e->getCode());
                }
                $this->assertRequestMethods($redirectHistory, $phase === 'POST' ? ['GET', 'POST'] : ['GET']);
                foreach ($redirectHistory as $exchange) {
                    $this->assertFalse($exchange['options']['allow_redirects']);
                    $this->assertNotSame('other.example.com', $exchange['request']->getUri()->getHost());
                }
            }
        }
    }

    /** @return array<string, array{string, string}> */
    public static function refusedRedirectBodies(): array
    {
        return [
            'Horizon detail text' => ['{"type":"x","detail":"TEXT"}', 'Unexpected challenge response status: 302'],
            'Horizon detail missing' => ['{"type":"x"}', 'Unexpected challenge response status: 302'],
            'Horizon detail integer' => ['{"type":"x","detail":5}', 'Unexpected challenge response status: 302'],
            'SEP-10 error' => ['{"error":"denied"}', 'denied'],
        ];
    }

    #[DataProvider('refusedRedirectBodies')]
    public function testRefusedChallengeRedirectUsesNormalStatusHandling(string $body, string $message): void
    {
        // Both relative and absolute requests must use the same response path as disabled redirects.
        foreach ([false, true] as $relative) {
            foreach ([false, true] as $redirects) {
                $history = [];
                $response = new Response(302, ['Location' => 'http://other.example.com/challenge'], $body);
                $stack = HandlerStack::create(new MockHandler([
                    $response,
                    new Response(200, [], '{"transaction":"unused"}'),
                ]));
                $stack->push(Middleware::history($history));
                $builder = new ChallengeRequestBuilder($this->authServer, new Client([
                    'handler' => $stack, 'base_uri' => 'https://api.stellar.org/path/', 'allow_redirects' => $redirects,
                ]));
                try {
                    $builder->request(($relative ? '../auth' : $this->authServer) . '?account=' . $this->clientAccountId);
                    $this->fail('A redirect response must be rejected');
                } catch (HorizonRequestException $e) {
                    $this->assertSame($message, $e->getMessage());
                    $this->assertSame(302, $e->getStatusCode());
                    $this->assertNull($e->getHorizonErrorResponse());
                    $this->assertSame($response, $e->getHttpResponse());
                    $this->assertSame(\UnexpectedValueException::class, $e->getPrevious()::class);
                }
                $this->assertRequestMethods($history, ['GET']);
                $this->assertSame('api.stellar.org', $history[0]['request']->getUri()->getHost());
            }
        }
    }

    public function testBuilderRelativeHttpsRequestRejectsDowngrade(): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(307, ['Location' => 'http://localhost/leak']),
        ]));
        $stack->push(Middleware::history($history));
        $builder = new ChallengeRequestBuilder('auth', new Client([
            'handler' => $stack, 'base_uri' => 'https://api.stellar.org/path/',
        ]));
        $this->expectException(HorizonRequestException::class);
        $this->expectExceptionMessage('Unexpected challenge response status: 307');
        try {
            $builder->request('../auth?account=' . $this->clientAccountId);
        } finally {
            $this->assertRequestMethods($history, ['GET']);
            $this->assertSame($this->authServer . '?account=' . $this->clientAccountId, (string)$history[0]['request']->getUri());
        }
    }

    public function testBuilderRelativeHttpRequestFollowsHttpRedirect(): void
    {
        $history = [];
        $body = $this->requestChallengeSuccess($this->clientAccountId);
        $stack = HandlerStack::create(new MockHandler([
            new Response(307, ['Location' => 'http://localhost/challenge']), new Response(200, [], $body),
        ]));
        $stack->push(Middleware::history($history));
        $builder = new ChallengeRequestBuilder('../auth', new Client([
            'handler' => $stack, 'base_uri' => 'http://localhost/path/',
        ]));
        $response = $builder->forAccountId($this->clientAccountId)->execute();
        $this->assertSame(json_decode($body, true)['transaction'], $response->getTransaction());
        $this->assertRequestMethods($history, ['GET', 'GET']);
        $this->assertSame('http://localhost/auth?account=' . $this->clientAccountId, (string)$history[0]['request']->getUri());
        $this->assertSame('http://localhost/challenge', (string)$history[1]['request']->getUri());
    }

    public function testLocalhostHttpTokenPostFollowsHttpRedirect(): void
    {
        $history = [];
        $challenge = (new TransactionBuilder(new Account($this->serverAccountId, new BigInteger(-1))))
            ->addOperation($this->validFirstManageDataOp($this->clientAccountId))
            ->setTimeBounds($this->validTimeBounds())->build();
        $challenge->sign($this->serverKeyPair, Network::testnet());
        $stack = HandlerStack::create(new MockHandler([
            new Response(307, ['Location' => 'http://localhost/challenge']),
            new Response(200, [], json_encode(['transaction' => $challenge->toEnvelopeXdrBase64()])),
            new Response(307, ['Location' => 'http://localhost/token']),
            new Response(200, [], $this->requestJWTSuccess()),
        ]));
        $stack->push(Middleware::history($history));
        $webAuth = new WebAuth('http://localhost/auth', $this->serverAccountId, $this->domain,
            Network::testnet(), new Client(['handler' => $stack]));
        $this->assertSame($this->successJWTToken,
            $webAuth->jwtToken($this->clientAccountId, [KeyPair::fromSeed($this->clientSecretSeed)]));
        $this->assertRequestMethods($history, ['GET', 'GET', 'POST', 'POST']);
        $this->assertSame('http://localhost/challenge', (string)$history[1]['request']->getUri());
        $this->assertSame('http://localhost/token', (string)$history[3]['request']->getUri());
        $this->assertSame((string)$history[2]['request']->getBody(), (string)$history[3]['request']->getBody());
    }

    public function testBuilderPreservesPublicHttpRequest(): void
    {
        $history = [];
        $body = $this->requestChallengeSuccess($this->clientAccountId);
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], $body)]));
        $stack->push(Middleware::history($history));
        $builder = new ChallengeRequestBuilder('http://api.stellar.org/auth', new Client(['handler' => $stack]));
        $response = $builder->forAccountId($this->clientAccountId)->execute();
        $this->assertSame(json_decode($body, true)['transaction'], $response->getTransaction());
        $this->assertSame('http://api.stellar.org/auth?account=' . $this->clientAccountId, (string)$history[0]['request']->getUri());
    }

    /** @return array<string, array{string}> */
    public static function malformedBuilderEntries(): array
    {
        return ['request' => ['request'], 'buildUrl' => ['buildUrl']];
    }

    #[DataProvider('malformedBuilderEntries')]
    public function testBuilderMalformedUrlDoesNotExposeInput(string $entry): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([]));
        $stack->push(Middleware::history($history));
        $url = 'https://example.com:invalid/sensitive-input';
        $builder = new ChallengeRequestBuilder($url, new Client(['handler' => $stack]));
        try {
            if ($entry === 'request') $builder->request($url);
            else $builder->forAccountId($this->clientAccountId)->buildUrl();
            $this->fail('Malformed URL must be rejected');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Invalid challenge request URL', $e->getMessage());
            $this->assertNull($e->getPrevious());
        } finally {
            $this->assertRequestMethods($history, []);
        }
    }

    public function testBuilderRejectsBareClientDomain(): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([]));
        $stack->push(Middleware::history($history));
        $builder = new ChallengeRequestBuilder($this->authServer, new Client(['handler' => $stack]));
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Client domain must not be empty');
        try {
            $builder->request($this->authServer . '?account=' . $this->clientAccountId . '&client_domain');
        } finally {
            $this->assertRequestMethods($history, []);
        }
    }

    /** @return array<string, array{bool}> */
    public static function publicOnlyDomainChallenges(): array
    {
        return ['domain operation' => [true], 'ignored domain' => [false]];
    }

    #[DataProvider('publicOnlyDomainChallenges')]
    public function testRequiredPublicOnlyClientDomainKeyRejectedBeforeRequest(bool $operation): void
    {
        $history = [];
        $domain = KeyPair::random();
        $body = $operation
            ? $this->requestChallengeValidClientDomainOpSourceAccount($this->clientAccountId, $domain->getAccountId())
            : $this->requestChallengeSuccess($this->clientAccountId);
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $body), new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $calls = 0;
        $signer = $this->signerRecordingCalls(KeyPair::random(), $calls);
        $this->assertRejectedWithoutSigningOrPost(
            fn() => $webAuth->jwtToken($this->clientAccountId, [$signer], clientDomain: 'wallet.example.com',
                clientDomainKeyPair: KeyPair::fromAccountId($domain->getAccountId())), $history, $calls,
            InvalidArgumentException::class, 'Client domain signer must have a private key', []);
    }

    public function testEmptySignerListSubmitsOnlyServerSignature(): void
    {
        $history = [];
        $body = $this->requestChallengeSuccess($this->clientAccountId);
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $body), new Response(200, [], '{"token":"test-placeholder"}'),
        ], $history);
        $this->assertTrue($webAuth->jwtToken($this->clientAccountId, []) === 'test-placeholder');
        $this->assertRequestMethods($history, ['GET', 'POST']);
        $postedXdr = json_decode((string)$history[1]['request']->getBody(), true)['transaction'];
        $this->assertSame(json_decode($body, true)['transaction'], $postedXdr);
        $posted = Transaction::fromEnvelopeBase64XdrString($postedXdr);
        $this->assertCount(1, $posted->getSignatures());
        $this->assertTrue($this->serverKeyPair->verifySignature(
            $posted->getSignatures()[0]->getSignature(), $posted->hash(Network::testnet())));
    }

    /** @return array<string, array{string, ?string}> */
    public static function builderAuthorizationTokens(): array
    {
        return [
            'empty' => ['', 'Authorization signer must return a non-empty string'],
            'spaces' => ['   ', 'Authorization signer must return a non-empty string'],
            'tabs' => ["\t\t", 'Authorization signer must return a non-empty string'],
            'CR' => ["opaque\rvalue", 'Authorization signer returned an invalid header value'],
            'LF' => ["opaque\nvalue", 'Authorization signer returned an invalid header value'],
            'NUL' => ["opaque\0value", 'Authorization signer returned an invalid header value'],
            'DEL' => ["opaque\x7fvalue", 'Authorization signer returned an invalid header value'],
            'valid' => ['opaque-value', null],
            'zero' => ['0', null],
            'header bytes' => ["opaque\tvalue \x80\xff", null],
        ];
    }

    #[DataProvider('builderAuthorizationTokens')]
    public function testBuilderValidatesAuthorizationBeforeRequest(string $token, ?string $error): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], '{"transaction":"challenge"}')]));
        $stack->push(Middleware::history($history));
        $builder = new ChallengeRequestBuilder($this->authServer, new Client(['handler' => $stack]));
        try {
            $response = $builder->request($this->authServer . '?account=' . $this->clientAccountId, $token);
            $this->assertNull($error, 'Invalid authorization must be rejected');
            $this->assertSame('challenge', $response->getTransaction());
            $this->assertTrue($history[0]['request']->getHeaderLine('Authorization') === 'Bearer ' . $token);
        } catch (InvalidArgumentException $e) {
            $this->assertNotNull($error, 'Valid authorization must be accepted');
            $this->assertTrue($e->getMessage() === $error);
            if ($token !== '') {
                $this->assertFalse(str_contains($e->getMessage(), $token));
            }
            $this->assertNull($e->getPrevious());
        } finally {
            $this->assertRequestMethods($history, $error === null ? ['GET'] : []);
        }
    }

    /** @return array<string, array{bool, ?string, bool, int, int}> */
    public static function domainKeyWithCallback(): array
    {
        return [
            'private key signs locally' => [false, 'place.client.com', true, 0, 3],
            'public key delegates signing' => [true, 'place.client.com', true, 1, 3],
            'private key with ignored domain' => [false, 'place.client.com', false, 0, 2],
            'public key with ignored domain' => [true, 'place.client.com', false, 0, 2],
            'private key without domain' => [false, null, false, 0, 2],
            'public key without domain' => [true, null, false, 0, 2],
        ];
    }

    #[DataProvider('domainKeyWithCallback')]
    public function testDomainKeySelectsSigningPathWithoutDiscovery(
        bool $publicOnly, ?string $clientDomain, bool $operation, int $expectedCalls, int $expectedSignatures
    ): void {
        $user = KeyPair::random();
        $domain = KeyPair::random();
        $history = [];
        $body = $operation
            ? $this->requestChallengeValidClientDomainOpSourceAccount($user->getAccountId(), $domain->getAccountId())
            : $this->requestChallengeSuccess($user->getAccountId());
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $body),
            new Response(200, [], '{"token":"test-placeholder"}'),
        ], $history);
        $calls = 0;
        $result = $webAuth->jwtToken($user->getAccountId(), [$user], clientDomain: $clientDomain,
            clientDomainKeyPair: $publicOnly ? KeyPair::fromAccountId($domain->getAccountId()) : $domain,
            clientDomainSigningCallback: static function (string $argument) use ($domain, &$calls): string {
                $calls++;
                $transaction = Transaction::fromEnvelopeBase64XdrString($argument);
                $transaction->sign($domain, Network::testnet());
                return $transaction->toEnvelopeXdrBase64();
            });
        $this->assertTrue($result === 'test-placeholder');
        $this->assertSame($expectedCalls, $calls);
        $this->assertRequestMethods($history, ['GET', 'POST']);
        $this->assertSame('/auth', $history[0]['request']->getUri()->getPath());
        $posted = Transaction::fromEnvelopeBase64XdrString(json_decode((string)$history[1]['request']->getBody(), true)['transaction']);
        $this->assertCount($expectedSignatures, $posted->getSignatures());
        $keys = $operation ? ($publicOnly ? [$this->serverKeyPair, $domain, $user] : [$this->serverKeyPair, $user, $domain])
            : [$this->serverKeyPair, $user];
        foreach ($keys as $index => $key) {
            $this->assertSame($key->getHint(), $posted->getSignatures()[$index]->getHint());
            $this->assertTrue($key->verifySignature($posted->getSignatures()[$index]->getSignature(), $posted->hash(Network::testnet())));
        }
    }

    /** @return array<string, array{int, string, ?string, ?string}> */
    public static function emptyTokenResponseFields(): array
    {
        return [
            'empty error with token' => [200, '{"error":"","token":"test-placeholder"}', 'test-placeholder', null],
            'zero token' => [200, '{"token":"0"}', '0', null],
            'error wins over token' => [200, '{"error":"Denied","token":"test-placeholder"}', null, 'Denied'],
            'zero error wins over token' => [200, '{"error":"0","token":"test-placeholder"}', null, '0'],
            'empty token' => [200, '{"token":""}', null, 'an unknown error occurred'],
            'empty token and error' => [200, '{"token":"","error":""}', null, 'an unknown error occurred'],
            'empty error without token' => [200, '{"error":""}', null, 'an unknown error occurred'],
            'absent fields' => [200, '{}', null, 'an unknown error occurred'],
            'bad request empty error' => [400, '{"error":""}', null, 'an unknown error occurred'],
            'bad request empty error with token' => [400, '{"error":"","token":"0"}', null, 'an unknown error occurred'],
        ];
    }

    #[DataProvider('emptyTokenResponseFields')]
    public function testTokenResponseDistinguishesEmptyFieldsFromZero(int $status, string $body, ?string $token, ?string $error): void
    {
        $history = [];
        $user = KeyPair::random();
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $this->requestChallengeSuccess($user->getAccountId())), new Response($status, [], $body),
        ], $history);
        if ($error !== null) {
            $this->expectException(SubmitCompletedChallengeErrorResponseException::class);
            $this->expectExceptionMessage($error);
        }
        try {
            $result = $webAuth->jwtToken($user->getAccountId(), [$user]);
            $this->assertTrue($result === $token);
        } finally {
            $this->assertRequestMethods($history, ['GET', 'POST']);
        }
    }

    public function testTokenPostTransportFailureKeepsGuzzleException(): void
    {
        $history = [];
        $cause = new ConnectException('Token endpoint unavailable', new Request('POST', $this->authServer));
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $this->requestChallengeSuccess($this->clientAccountId)), $cause,
        ], $history);
        try {
            $webAuth->jwtToken($this->clientAccountId, [KeyPair::random()]);
            $this->fail('Connection failure must be reported');
        } catch (ConnectException $e) {
            $this->assertSame($cause, $e);
            $this->assertSame('Token endpoint unavailable', $e->getMessage());
        } finally {
            $this->assertRequestMethods($history, ['GET', 'POST']);
        }
    }

}
