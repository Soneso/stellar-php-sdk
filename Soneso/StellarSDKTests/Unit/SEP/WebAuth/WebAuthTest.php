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
                    clientDomain: 'place.client.com', clientDomainKeyPair: $domainKey, clientDomainSigningCallback: $callback);
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
        string $exceptionClass, string $message, array $methods,
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
    }

    /** @return array<string, array{string}> */
    public static function rejectedCallbackAnswers(): array
    {
        return array_combine($cases = [
            'payment', 'payment by reference', 'payment without clientDomain', 'changed fee',
            'unchanged', 'all signatures removed', 'wrong key', 'other network',
            'malformed signature', 'wrong hint', 'invalid base64', 'truncated XDR', 'wrong envelope type',
            'null', 'false', 'object', 'V0 envelope', 'V2 time bounds',
        ], array_map(static fn(string $case): array => [$case], $cases));
    }

    #[DataProvider('rejectedCallbackAnswers')]
    public function testCallbackRejectsUnsafeAnswerBeforeUserSigning(string $case): void
    {
        $user = KeyPair::random();
        $domain = KeyPair::random();
        $clientDomain = $case === 'payment without clientDomain' ? null : 'place.client.com';
        $body = $clientDomain === null
            ? $this->requestChallengeSuccess($user->getAccountId())
            : $this->requestChallengeValidClientDomainOpSourceAccount($user->getAccountId(), $domain->getAccountId());
        $responses = [new Response(200, [], $body)];
        if ($clientDomain !== null) {
            $responses[] = new Response(200, [], 'SIGNING_KEY="' . $domain->getAccountId() . '"');
        }
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
                case 'all signatures removed':
                    $transaction->setSignatures([]);
                    break;
                case 'wrong key':
                    $transaction->sign(KeyPair::random(), Network::testnet());
                    break;
                case 'other network':
                    $transaction->sign($domain, Network::public());
                    break;
                case 'malformed signature':
                    $transaction->setSignatures([
                        ...$transaction->getSignatures(), new XdrDecoratedSignature($domain->getHint(), 'x'),
                    ]);
                    break;
                case 'wrong hint':
                    $signature = $domain->signDecorated($transaction->hash(Network::testnet()));
                    $signature->setHint($domain->getHint() ^ "\xff\xff\xff\xff");
                    $transaction->setSignatures([...$transaction->getSignatures(), $signature]);
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
        $exception = ChallengeValidationErrorInvalidSignature::class;
        $message = 'Invalid client domain signature';
        if (str_starts_with($case, 'payment') || in_array($case, ['changed fee', 'V2 time bounds'], true)) {
            $exception = ChallengeValidationError::class;
            $message = 'Client domain signing callback returned a different transaction';
        } elseif (in_array($case, ['invalid base64', 'null', 'false', 'object', 'truncated XDR'], true)) {
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
            $clientDomain === null ? ['GET'] : ['GET', 'GET'],
        );
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
        $lookup = $clientDomain !== null && $localKey === null;
        if ($lookup) {
            $responses[] = new Response(200, [], 'SIGNING_KEY="' . $domain->getAccountId() . '"');
        }
        $responses[] = new Response(200, [], $this->requestJWTSuccess());
        $history = [];
        $webAuth = $this->webAuthRecordingRequests($responses, $history);
        $callback = function (string &$argument) use ($case, $user, $domain): string {
            $transaction = Transaction::fromEnvelopeBase64XdrString($argument);
            if (in_array($case, ['server signature removed', 'without clientDomain'], true)) {
                $transaction->setSignatures([]);
            }
            if (in_array($case, ['no client_domain operation', 'without clientDomain', 'local key and callback', 'domain key is server'], true)) {
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
        $body = $this->requestChallengeSuccess($user->getAccountId());
        $challenge = Transaction::fromEnvelopeBase64XdrString(json_decode($body, true)['transaction']);
        $history = [];
        $webAuth = $this->webAuthRecordingRequests([
            new Response(200, [], $body), new Response(200, [], $this->requestJWTSuccess()),
        ], $history);
        $callbackSigners = [];
        for ($i = 0; $i < $callbackCount; $i++) {
            $callbackSigners[] = KeyPair::random();
        }
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
                $webAuth->jwtToken($user->getAccountId(), [$signer], clientDomainSigningCallback: $callback);
            } catch (\Exception $e) {
                $error = $e;
            }
            $this->assertRequestMethods($history, ['GET']);
            if ($callbackCount === 20) {
                $this->assertSame(0, $calls, 'The merged callback signatures already exceed the limit before user signing');
            }
            $this->assertInstanceOf(ChallengeValidationErrorInvalidSignature::class, $error);
            $this->assertSame('Invalid transaction envelope, more than 20 signatures', $error->getMessage());
            return;
        }
        $this->assertSame($this->successJWTToken,
            $webAuth->jwtToken($user->getAccountId(), [$user], clientDomainSigningCallback: $callback));
        $this->assertRequestMethods($history, ['GET', 'POST']);
        $posted = Transaction::fromEnvelopeBase64XdrString(json_decode((string)$history[1]['request']->getBody(), true)['transaction']);
        $this->assertCount(20, $posted->getSignatures());
        $expectedKeys = $overlappingUser ? [$this->serverKeyPair, ...$callbackSigners] : [$this->serverKeyPair, ...$callbackSigners, $user];
        foreach ($expectedKeys as $index => $key) {
            $this->assertSame($key->getHint(), $posted->getSignatures()[$index]->getHint());
            $this->assertTrue($key->verifySignature($posted->getSignatures()[$index]->getSignature(), $challenge->hash(Network::testnet())));
        }
    }

    public function testSigningWithoutCallbackPreservesDuplicateSignaturesAndBytes(): void
    {
        $user = KeyPair::random();
        $body = $this->requestChallengeSuccess($user->getAccountId());
        $expected = Transaction::fromEnvelopeBase64XdrString(json_decode($body, true)['transaction']);
        foreach ([$user, $this->serverKeyPair, $user] as $key) {
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

    public function testChallengeRejectsMuxedSourceWithMuxedServerSigningKey(): void
    {
        $source = new MuxedAccount($this->serverAccountId, 0);
        $preconditions = new TransactionPreconditions();
        $preconditions->setTimeBounds($this->validTimeBounds());
        $challenge = new Transaction($source, new BigInteger(0), [
            $this->validFirstManageDataOp($this->clientAccountId),
        ], preconditions: $preconditions);
        $challenge->sign($this->serverKeyPair, Network::testnet());
        $history = [];
        $handlerStack = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode(['transaction' => $challenge->toEnvelopeXdrBase64()])),
            new Response(200, [], $this->requestJWTSuccess()),
        ]));
        $handlerStack->push(Middleware::history($history));
        $webAuth = new WebAuth($this->authServer, $source->getAccountId(), $this->domain, Network::testnet(),
            new Client(['handler' => $handlerStack]));
        $calls = 0;
        $signer = $this->signerRecordingCalls(KeyPair::fromSeed($this->clientSecretSeed), $calls);
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
