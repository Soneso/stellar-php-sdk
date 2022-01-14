<?php

namespace Soneso\StellarSDKTests;

use DateTime;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\ManageDataOperation;
use Soneso\StellarSDK\ManageDataOperationBuilder;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
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

    private function memoForId(?int $id) : Memo {
        if ($id) {
            return Memo::id($id);
        }
        return Memo::none();
    }

    private function validTimeBounds() : TimeBounds {
        $currentTime = round(microtime(true));
        return new TimeBounds((new DateTime)->setTimestamp($currentTime), (new DateTime)->setTimestamp($currentTime + 3));
    }

    private function invalidTimeBounds() : TimeBounds {
        $currentTime = round(microtime(true));
        return new TimeBounds((new DateTime)->setTimestamp($currentTime - 700),
            (new DateTime)->setTimestamp($currentTime - 400));
    }

    private function requestChallengeSuccess(string $accountId, ?int $memo) : string {
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

    public function testDefaultSuccess(): void {
        $webAuth = new WebAuth($this->authServer, $this->serverAccountId, $this->domain, Network::testnet());
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestChallengeSuccess($this->clientAccountId, 200)),
        ]);
        $webAuth->setMockHandler($mock);
        $userKeyPair = KeyPair::fromSeed($this->clientSecretSeed);
        $userAccountId = $userKeyPair->getAccountId();
        $jwtToken = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        $this->assertEquals($this->successJWTToken, $jwtToken);
    }

}