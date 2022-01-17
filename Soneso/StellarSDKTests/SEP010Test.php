<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use DateTime;
use ErrorException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use InvalidArgumentException;
use phpseclib3\Math\BigInteger;
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
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidHomeDomain;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidMemoType;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidMemoValue;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidOperationType;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidSeqNr;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidSignature;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidSourceAccount;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidTimeBounds;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidWebAuthDomain;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;
use Soneso\StellarSDK\TimeBounds;
use Soneso\StellarSDK\TransactionBuilder;


class SEP010Test extends TestCase
{
    private string $domain = "place.domain.com";
    private string $authServer = "http://api.stellar.org/auth";
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

    private function memoForId(?int $id = null) : Memo {
        if ($id) {
            return Memo::id($id);
        }
        return Memo::none();
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

    private function requestChallengeSuccess(string $accountId, ?int $memo = null) : string {
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
}