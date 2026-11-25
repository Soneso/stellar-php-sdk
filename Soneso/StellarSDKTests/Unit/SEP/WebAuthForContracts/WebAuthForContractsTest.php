<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\SEP\WebAuthForContracts;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use ReflectionProperty;
use RuntimeException;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperation;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeRequestBuilder;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeRequestErrorResponse;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeResponse;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationError;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidAccount;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidArgs;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidContractAddress;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidFunctionName;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidHomeDomain;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidNetworkPassphrase;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidNonce;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidServerSignature;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidWebAuthDomain;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorMissingClientEntry;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorMissingServerEntry;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorSimulationFailed;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorSubInvocationsFound;
use Soneso\StellarSDK\SEP\WebAuthForContracts\SubmitContractChallengeErrorResponseException;
use Soneso\StellarSDK\SEP\WebAuthForContracts\SubmitContractChallengeTimeoutResponseException;
use Soneso\StellarSDK\SEP\WebAuthForContracts\SubmitContractChallengeUnknownResponseException;
use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;
use Soneso\StellarSDK\Soroban\AccountEd25519Signature;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Requests\SimulateTransactionRequest;
use Soneso\StellarSDK\Soroban\Responses\GetLatestLedgerResponse;
use Soneso\StellarSDK\Soroban\Responses\SimulateTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SorobanRpcErrorResponse;
use Soneso\StellarSDK\Soroban\SorobanAddressCredentials;
use Soneso\StellarSDK\Soroban\SorobanAuthorizedFunction;
use Soneso\StellarSDK\Soroban\SorobanAuthorizedInvocation;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Soroban\SorobanCredentials;
use Soneso\StellarSDK\Soroban\SorobanDelegateDescriptor;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\Util\Hash;
use Soneso\StellarSDK\Xdr\XdrContractDataDurability;
use Soneso\StellarSDK\Xdr\XdrEncoder;
use Soneso\StellarSDK\Xdr\XdrInvokeContractArgs;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCAddressType;
use Soneso\StellarSDK\Xdr\XdrSCMapEntry;
use Soneso\StellarSDK\Xdr\XdrSCNonceKey;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizationEntries;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizationEntry;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType;
use Soneso\StellarSDK\Xdr\XdrSorobanResources;
use Soneso\StellarSDK\Xdr\XdrSorobanResourcesExtV0;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionDataExt;

/**
 * Unit tests for SEP-45 Web Authentication for Contract Accounts
 *
 * This test suite validates the SEP-45 authentication flow using mocked HTTP responses.
 * Tests include:
 * - Challenge request and response handling
 * - Authorization entry validation (contract address, function name, args)
 * - Server signature verification
 * - Client signing of authorization entries
 * - JWT token retrieval
 * - Error handling for all validation failures
 *
 * @package Soneso\StellarSDKTests\Unit
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Specification
 */
class WebAuthForContractsTest extends TestCase
{
    private string $domain = "example.stellar.org";
    private string $authServer = "https://auth.example.stellar.org";
    private string $serverAccountId = "GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP";
    private string $serverSecretSeed = "SAWDHXQG6ROJSU4QGCW7NSTYFHPTPIVC2NC7QKVTO7PZCSO2WEBGM54W";
    private KeyPair $serverKeyPair;
    private string $clientContractId = "CDZJIDQW5WTPAZ64PGIJGVEIDNK72LL3LKUZWG3G6GWXYQKI2JNIVFNV";
    private string $webAuthContractId = "CA7A3N2BB35XMTFPAYWVZEF4TEYXW7DAEWDXJNQGUPR5SWSM2UVZCJM2";
    private string $successJWTToken = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJDRFpKSURRVzVXVFBBWjY0UEdJSkdWRUlETks3MkxMM0xLVVpXRzNHNkdXWFlRS0kySkFJVkZOViIsImlzcyI6ImV4YW1wbGUuc3RlbGxhci5vcmciLCJpYXQiOjE3Mzc3NjAwMDAsImV4cCI6MTczNzc2MzYwMH0.test";

    protected function setUp(): void
    {
        $this->serverKeyPair = KeyPair::fromSeed($this->serverSecretSeed);
    }

    /**
     * Creates a mocked Soroban RPC server whose getLatestLedger and simulateTransaction always
     * answer, so expiration lookup and simulation never reach the network.
     */
    private function createMockSorobanServer(?SimulateTransactionResponse $simulationResponse = null): SorobanServer
    {
        $latestLedger = new GetLatestLedgerResponse([]);
        $latestLedger->sequence = 123456;
        $server = $this->createMock(SorobanServer::class);
        $server->method('getLatestLedger')->willReturn($latestLedger);
        $server->method('simulateTransaction')->willReturn(
            $simulationResponse ?? $this->buildDefaultSimulationResponse()
        );
        return $server;
    }

    private function buildNonceContractDataKey(
        string $addressId,
        int $nonce = 42,
        ?XdrContractDataDurability $durability = null
    ): XdrLedgerKey {
        $address = Address::fromAnyId($addressId);
        $this->assertNotNull($address, "Valid address expected for nonce key: $addressId");
        return XdrLedgerKey::forContractData(
            $address->toXdr(),
            XdrSCVal::forLedgerNonceKey(new XdrSCNonceKey($nonce)),
            $durability ?? XdrContractDataDurability::TEMPORARY()
        );
    }

    private function buildContractInstanceKey(
        string $contractId,
        ?XdrContractDataDurability $durability = null
    ): XdrLedgerKey {
        return XdrLedgerKey::forContractData(
            XdrSCAddress::forContractId($contractId),
            XdrSCVal::forLedgerKeyContractInstance(),
            $durability ?? XdrContractDataDurability::PERSISTENT()
        );
    }

    /**
     * @param array<XdrLedgerKey> $readWriteKeys
     * @param array<XdrLedgerKey> $readOnlyKeys
     * @param array<string, mixed>|null $rpcError
     * @param array<int>|null $archivedSorobanEntries When non-null, sets transactionData.ext discriminant 1 with these indices
     */
    private function buildSimulationResponse(
        array $readWriteKeys = [],
        array $readOnlyKeys = [],
        ?string $resultError = null,
        ?array $rpcError = null,
        bool $includeTransactionData = true,
        ?array $archivedSorobanEntries = null
    ): SimulateTransactionResponse {
        if ($rpcError !== null) {
            return SimulateTransactionResponse::fromJson([
                'jsonrpc' => '2.0',
                'id' => 1,
                'error' => $rpcError,
            ]);
        }

        $result = ['latestLedger' => 123456];
        if ($resultError !== null) {
            $result['error'] = $resultError;
        }
        if ($includeTransactionData) {
            if ($archivedSorobanEntries !== null) {
                $ext = new XdrSorobanTransactionDataExt(1);
                $ext->resourceExt = new XdrSorobanResourcesExtV0($archivedSorobanEntries);
            } else {
                $ext = new XdrSorobanTransactionDataExt(0);
            }
            $txData = new XdrSorobanTransactionData(
                $ext,
                new XdrSorobanResources(
                    new XdrLedgerFootprint($readOnlyKeys, $readWriteKeys),
                    100000,
                    1000,
                    1000
                ),
                50000
            );
            $result['transactionData'] = $txData->toBase64Xdr();
        }

        return SimulateTransactionResponse::fromJson([
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => $result,
        ]);
    }

    /**
     * @param array<string> $extraNonceAddresses
     */
    private function buildDefaultSimulationResponse(array $extraNonceAddresses = []): SimulateTransactionResponse
    {
        $readWrite = [
            $this->buildNonceContractDataKey($this->clientContractId, 12346),
            $this->buildNonceContractDataKey($this->serverAccountId, 12345),
        ];
        foreach ($extraNonceAddresses as $address) {
            $readWrite[] = $this->buildNonceContractDataKey($address, 12347);
        }
        return $this->buildSimulationResponse(
            $readWrite,
            [$this->buildContractInstanceKey($this->webAuthContractId)]
        );
    }

    /**
     * Helper to build a valid challenge with proper authorization entries
     */
    private function buildValidChallenge(
        string $clientAccountId,
        string $homeDomain,
        string $webAuthDomain,
        string $webAuthDomainAccount,
        string $nonce,
        ?string $clientDomain = null,
        ?string $clientDomainAccount = null,
        bool $signServerEntry = true
    ): string {
        $entries = [];

        // Build args map
        $argsMap = $this->buildArgsMap(
            $clientAccountId,
            $homeDomain,
            $webAuthDomain,
            $webAuthDomainAccount,
            $nonce,
            $clientDomain,
            $clientDomainAccount
        );

        // Create server entry
        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId,
            $this->webAuthContractId,
            "web_auth_verify",
            $argsMap,
            12345, // nonce
            1000000 // expiration ledger
        );

        if ($signServerEntry) {
            $serverEntry->sign($this->serverKeyPair, Network::testnet());
        }
        $entries[] = $serverEntry;

        // Create client entry
        $clientEntry = $this->buildAuthEntry(
            $clientAccountId,
            $this->webAuthContractId,
            "web_auth_verify",
            $argsMap,
            12346, // nonce
            1000000 // expiration ledger
        );
        $entries[] = $clientEntry;

        // Create client domain entry if needed
        if ($clientDomainAccount !== null) {
            $clientDomainEntry = $this->buildAuthEntry(
                $clientDomainAccount,
                $this->webAuthContractId,
                "web_auth_verify",
                $argsMap,
                12347, // nonce
                1000000 // expiration ledger
            );
            $entries[] = $clientDomainEntry;
        }

        return $this->encodeAuthEntries($entries);
    }

    /**
     * Helper to build args map as XdrSCVal
     */
    private function buildArgsMap(
        string $account,
        string $homeDomain,
        string $webAuthDomain,
        string $webAuthDomainAccount,
        string $nonce,
        ?string $clientDomain = null,
        ?string $clientDomainAccount = null
    ): XdrSCVal {
        $mapEntries = [];

        $mapEntries[] = new XdrSCMapEntry(
            XdrSCVal::forSymbol("account"),
            XdrSCVal::forString($account)
        );
        $mapEntries[] = new XdrSCMapEntry(
            XdrSCVal::forSymbol("home_domain"),
            XdrSCVal::forString($homeDomain)
        );
        $mapEntries[] = new XdrSCMapEntry(
            XdrSCVal::forSymbol("web_auth_domain"),
            XdrSCVal::forString($webAuthDomain)
        );
        $mapEntries[] = new XdrSCMapEntry(
            XdrSCVal::forSymbol("web_auth_domain_account"),
            XdrSCVal::forString($webAuthDomainAccount)
        );
        $mapEntries[] = new XdrSCMapEntry(
            XdrSCVal::forSymbol("nonce"),
            XdrSCVal::forString($nonce)
        );

        if ($clientDomain !== null) {
            $mapEntries[] = new XdrSCMapEntry(
                XdrSCVal::forSymbol("client_domain"),
                XdrSCVal::forString($clientDomain)
            );
        }

        if ($clientDomainAccount !== null) {
            $mapEntries[] = new XdrSCMapEntry(
                XdrSCVal::forSymbol("client_domain_account"),
                XdrSCVal::forString($clientDomainAccount)
            );
        }

        return XdrSCVal::forMap($mapEntries);
    }

    /**
     * Helper to build a single authorization entry
     */
    private function buildAuthEntry(
        string $credentialsAddress,
        string $contractId,
        string $functionName,
        XdrSCVal $argsMap,
        int $nonce,
        int $expirationLedger,
        ?XdrSCVal $signature = null
    ): SorobanAuthorizationEntry {
        $address = Address::fromAnyId($credentialsAddress);
        if ($address === null) {
            throw new Exception("Invalid address: $credentialsAddress");
        }

        $credentials = new SorobanCredentials(
            new SorobanAddressCredentials(
                $address,
                $nonce,
                $expirationLedger,
                $signature ?? XdrSCVal::forVoid()
            )
        );

        $contractAddress = Address::fromContractId(StrKey::decodeContractIdHex($contractId));
        $contractFn = new XdrInvokeContractArgs(
            $contractAddress->toXdr(),
            $functionName,
            [$argsMap]
        );

        $function = new SorobanAuthorizedFunction($contractFn);
        $invocation = new SorobanAuthorizedInvocation($function, []);

        return new SorobanAuthorizationEntry($credentials, $invocation);
    }

    /**
     * Helper to encode authorization entries to base64 XDR
     */
    private function encodeAuthEntries(array $entries): string
    {
        $bytes = '';
        $bytes .= XdrEncoder::unsignedInteger32(count($entries));
        foreach ($entries as $entry) {
            $bytes .= $entry->toXdr()->encode();
        }
        return base64_encode($bytes);
    }


    /**
     * Test successful authentication flow
     */
    public function testDefaultSuccess(): void
    {
        $nonce = "test_nonce_" . time();
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            "auth.example.stellar.org",
            $this->serverAccountId,
            $nonce
        );

        $challengeResponse = new Response(200, [], json_encode([
            'authorization_entries' => $challengeXdr,
            'network_passphrase' => 'Test SDF Network ; September 2015'
        ]));

        $tokenResponse = new Response(200, [], json_encode([
            'token' => $this->successJWTToken
        ]));

        $mock = new MockHandler([$challengeResponse, $tokenResponse]);

        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );
        $expectedServerEntryXdr = $webAuth->decodeAuthorizationEntries($challengeXdr)[0]->toBase64Xdr();
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $clientSigner = KeyPair::random();
        $token = $webAuth->jwtToken(
            $this->clientContractId,
            [$clientSigner],
            $this->domain
        );

        $this->assertEquals($this->successJWTToken, $token);
        $this->assertCount(0, $mock);
        $postRequest = $mock->getLastRequest();
        $this->assertSame('POST', $postRequest->getMethod());
        $this->assertSame($this->authServer, (string) $postRequest->getUri());
        parse_str((string) $postRequest->getBody(), $postBody);
        $submitted = $webAuth->decodeAuthorizationEntries($postBody['authorization_entries']);
        $this->assertCount(2, $submitted);
        $this->assertSame($expectedServerEntryXdr, $submitted[0]->toBase64Xdr());
        $clientCreds = $submitted[1]->credentials->getAddressCredentials();
        $this->assertSame(123466, $clientCreds->signatureExpirationLedger);
        $this->assertCount(1, $clientCreds->signature->vec);
        $sigBytes = $this->extractFirstSignatureBytes($clientCreds->signature);
        $payload = Hash::generate($submitted[1]->buildPreimage(Network::testnet())->encode());
        $this->assertTrue($clientSigner->verifySignature($sigBytes, $payload));
    }

    /**
     * Test successful authentication flow using default home domain
     */
    public function testDefaultHomeDomainSuccess(): void
    {
        $nonce = "test_nonce_" . time();
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            "auth.example.stellar.org",
            $this->serverAccountId,
            $nonce
        );

        $requests = [];
        $responses = [
            new Response(200, [], json_encode([
                'authorization_entries' => $challengeXdr,
                'network_passphrase' => 'Test SDF Network ; September 2015'
            ])),
            new Response(200, [], json_encode([
                'token' => $this->successJWTToken
            ])),
        ];
        $handler = function (RequestInterface $request) use (&$requests, &$responses): PromiseInterface {
            $requests[] = [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'body' => (string) $request->getBody(),
            ];
            return Create::promiseFor(array_shift($responses));
        };
        $httpClient = new Client(['handler' => HandlerStack::create($handler)]);

        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet(),
            $httpClient
        );
        $expectedServerEntryXdr = $webAuth->decodeAuthorizationEntries($challengeXdr)[0]->toBase64Xdr();
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $clientSigner = KeyPair::random();
        // Note: not passing $homeDomain parameter - should default to $this->domain
        $token = $webAuth->jwtToken(
            $this->clientContractId,
            [$clientSigner]
        );

        $this->assertEquals($this->successJWTToken, $token);
        $this->assertCount(2, $requests);
        $this->assertSame('GET', $requests[0]['method']);
        $this->assertStringContainsString('home_domain=' . $this->domain, $requests[0]['uri']);
        $this->assertSame('POST', $requests[1]['method']);
        parse_str($requests[1]['body'], $postBody);
        $submitted = $webAuth->decodeAuthorizationEntries($postBody['authorization_entries']);
        $this->assertCount(2, $submitted);
        $this->assertSame($expectedServerEntryXdr, $submitted[0]->toBase64Xdr());
        $clientCreds = $submitted[1]->credentials->getAddressCredentials();
        $this->assertSame(123466, $clientCreds->signatureExpirationLedger);
        $this->assertCount(1, $clientCreds->signature->vec);
        $sigBytes = $this->extractFirstSignatureBytes($clientCreds->signature);
        $payload = Hash::generate($submitted[1]->buildPreimage(Network::testnet())->encode());
        $this->assertTrue($clientSigner->verifySignature($sigBytes, $payload));
    }

    /**
     * Test validation error: invalid contract address
     */
    public function testInvalidContractAddress(): void
    {
        $this->expectException(ContractChallengeValidationErrorInvalidContractAddress::class);

        $wrongContractId = "CCJCTOZFKPNTFLMORB7RBNKDQU42PBKGVTI4DIWVEMUCXRHWCYXGRRV7";
        $nonce = "test_nonce_" . time();

        $entries = [];
        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            "auth.example.stellar.org",
            $this->serverAccountId,
            $nonce
        );

        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId,
            $wrongContractId, // Wrong contract!
            "web_auth_verify",
            $argsMap,
            12345,
            1000000
        );
        $serverEntry->sign($this->serverKeyPair, Network::testnet());
        $entries[] = $serverEntry;

        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId,
            $wrongContractId,
            "web_auth_verify",
            $argsMap,
            12346,
            1000000
        );
        $entries[] = $clientEntry;

        $challengeXdr = $this->encodeAuthEntries($entries);
        $challengeResponse = new Response(200, [], json_encode([
            'authorization_entries' => $challengeXdr,
        ]));

        $mock = new MockHandler([$challengeResponse]);
        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $clientSigner = KeyPair::random();
        $webAuth->jwtToken(
            $this->clientContractId,
            [$clientSigner],
            $this->domain
        );
    }

    /**
     * Test validation error: invalid function name
     */
    public function testInvalidFunctionName(): void
    {
        $this->expectException(ContractChallengeValidationErrorInvalidFunctionName::class);

        $nonce = "test_nonce_" . time();
        $entries = [];
        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            "auth.example.stellar.org",
            $this->serverAccountId,
            $nonce
        );

        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId,
            $this->webAuthContractId,
            "wrong_function", // Wrong function name!
            $argsMap,
            12345,
            1000000
        );
        $serverEntry->sign($this->serverKeyPair, Network::testnet());
        $entries[] = $serverEntry;

        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId,
            $this->webAuthContractId,
            "wrong_function",
            $argsMap,
            12346,
            1000000
        );
        $entries[] = $clientEntry;

        $challengeXdr = $this->encodeAuthEntries($entries);
        $challengeResponse = new Response(200, [], json_encode([
            'authorization_entries' => $challengeXdr,
        ]));

        $mock = new MockHandler([$challengeResponse]);
        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $clientSigner = KeyPair::random();
        $webAuth->jwtToken(
            $this->clientContractId,
            [$clientSigner],
            $this->domain
        );
    }

    /**
     * Test validation error: missing server entry
     */
    public function testMissingServerEntry(): void
    {
        $this->expectException(ContractChallengeValidationErrorMissingServerEntry::class);

        $nonce = "test_nonce_" . time();
        $entries = [];
        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            "auth.example.stellar.org",
            $this->serverAccountId,
            $nonce
        );

        // Only client entry, no server entry
        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId,
            $this->webAuthContractId,
            "web_auth_verify",
            $argsMap,
            12346,
            1000000
        );
        $entries[] = $clientEntry;

        $challengeXdr = $this->encodeAuthEntries($entries);
        $challengeResponse = new Response(200, [], json_encode([
            'authorization_entries' => $challengeXdr,
        ]));

        $mock = new MockHandler([$challengeResponse]);
        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $clientSigner = KeyPair::random();
        $webAuth->jwtToken(
            $this->clientContractId,
            [$clientSigner],
            $this->domain
        );
    }

    /**
     * Test validation error: missing client entry
     */
    public function testMissingClientEntry(): void
    {
        $this->expectException(ContractChallengeValidationErrorMissingClientEntry::class);

        $nonce = "test_nonce_" . time();
        $entries = [];
        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            "auth.example.stellar.org",
            $this->serverAccountId,
            $nonce
        );

        // Only server entry, no client entry
        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId,
            $this->webAuthContractId,
            "web_auth_verify",
            $argsMap,
            12345,
            1000000
        );
        $serverEntry->sign($this->serverKeyPair, Network::testnet());
        $entries[] = $serverEntry;

        $challengeXdr = $this->encodeAuthEntries($entries);
        $challengeResponse = new Response(200, [], json_encode([
            'authorization_entries' => $challengeXdr,
        ]));

        $mock = new MockHandler([$challengeResponse]);
        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $clientSigner = KeyPair::random();
        $webAuth->jwtToken(
            $this->clientContractId,
            [$clientSigner],
            $this->domain
        );
    }

    /**
     * Test validation error: invalid server signature
     */
    public function testInvalidServerSignature(): void
    {
        $this->expectException(ContractChallengeValidationErrorInvalidServerSignature::class);

        $nonce = "test_nonce_" . time();
        $entries = [];
        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            "auth.example.stellar.org",
            $this->serverAccountId,
            $nonce
        );

        // Server entry with no signature (invalid)
        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId,
            $this->webAuthContractId,
            "web_auth_verify",
            $argsMap,
            12345,
            1000000
        );
        // Don't sign the server entry - this will fail validation
        $entries[] = $serverEntry;

        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId,
            $this->webAuthContractId,
            "web_auth_verify",
            $argsMap,
            12346,
            1000000
        );
        $entries[] = $clientEntry;

        $challengeXdr = $this->encodeAuthEntries($entries);
        $challengeResponse = new Response(200, [], json_encode([
            'authorization_entries' => $challengeXdr,
        ]));

        $mock = new MockHandler([$challengeResponse]);
        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $clientSigner = KeyPair::random();
        $webAuth->jwtToken(
            $this->clientContractId,
            [$clientSigner],
            $this->domain
        );
    }

    /**
     * Test validation error: sub-invocations found
     */
    public function testSubInvocationsFound(): void
    {
        $this->expectException(ContractChallengeValidationErrorSubInvocationsFound::class);

        $nonce = "test_nonce_" . time();
        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            "auth.example.stellar.org",
            $this->serverAccountId,
            $nonce
        );

        // Create a sub-invocation (security critical - not allowed)
        $contractAddress = Address::fromContractId(StrKey::decodeContractIdHex($this->webAuthContractId));
        $contractFn = new XdrInvokeContractArgs(
            $contractAddress->toXdr(),
            "some_other_function",
            [$argsMap]
        );
        $subFunction = new SorobanAuthorizedFunction($contractFn);
        $subInvocation = new SorobanAuthorizedInvocation($subFunction, []);

        // Build server entry with sub-invocation
        $address = Address::fromAnyId($this->serverAccountId);
        $credentials = new SorobanCredentials(
            new SorobanAddressCredentials(
                $address,
                12345,
                1000000,
                XdrSCVal::forVec([])
            )
        );

        $mainContractFn = new XdrInvokeContractArgs(
            $contractAddress->toXdr(),
            "web_auth_verify",
            [$argsMap]
        );
        $mainFunction = new SorobanAuthorizedFunction($mainContractFn);
        $mainInvocation = new SorobanAuthorizedInvocation($mainFunction, [$subInvocation]); // Add sub-invocation

        $serverEntry = new SorobanAuthorizationEntry($credentials, $mainInvocation);
        $serverEntry->sign($this->serverKeyPair, Network::testnet());

        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId,
            $this->webAuthContractId,
            "web_auth_verify",
            $argsMap,
            12346,
            1000000
        );

        $entries = [$serverEntry, $clientEntry];
        $challengeXdr = $this->encodeAuthEntries($entries);
        $challengeResponse = new Response(200, [], json_encode([
            'authorization_entries' => $challengeXdr,
        ]));

        $mock = new MockHandler([$challengeResponse]);
        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $clientSigner = KeyPair::random();
        $webAuth->jwtToken(
            $this->clientContractId,
            [$clientSigner],
            $this->domain
        );
    }

    /**
     * Test validation error: invalid nonce (inconsistent across entries)
     */
    public function testInvalidNonce(): void
    {
        $this->expectException(ContractChallengeValidationErrorInvalidNonce::class);

        $nonce1 = "test_nonce_1";
        $nonce2 = "test_nonce_2"; // Different nonce

        $entries = [];

        // Server entry with nonce1
        $argsMap1 = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            "auth.example.stellar.org",
            $this->serverAccountId,
            $nonce1
        );
        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId,
            $this->webAuthContractId,
            "web_auth_verify",
            $argsMap1,
            12345,
            1000000
        );
        $serverEntry->sign($this->serverKeyPair, Network::testnet());
        $entries[] = $serverEntry;

        // Client entry with nonce2 (different!)
        $argsMap2 = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            "auth.example.stellar.org",
            $this->serverAccountId,
            $nonce2
        );
        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId,
            $this->webAuthContractId,
            "web_auth_verify",
            $argsMap2,
            12346,
            1000000
        );
        $entries[] = $clientEntry;

        $challengeXdr = $this->encodeAuthEntries($entries);
        $challengeResponse = new Response(200, [], json_encode([
            'authorization_entries' => $challengeXdr,
        ]));

        $mock = new MockHandler([$challengeResponse]);
        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $clientSigner = KeyPair::random();
        $webAuth->jwtToken(
            $this->clientContractId,
            [$clientSigner],
            $this->domain
        );
    }

    /**
     * Test validation error: invalid home_domain
     */
    public function testInvalidHomeDomain(): void
    {
        $this->expectException(ContractChallengeValidationErrorInvalidHomeDomain::class);

        $nonce = "test_nonce_" . time();
        $wrongHomeDomain = "wrong.domain.com";

        $entries = [];
        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $wrongHomeDomain, // Wrong domain
            "auth.example.stellar.org",
            $this->serverAccountId,
            $nonce
        );

        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId,
            $this->webAuthContractId,
            "web_auth_verify",
            $argsMap,
            12345,
            1000000
        );
        $serverEntry->sign($this->serverKeyPair, Network::testnet());
        $entries[] = $serverEntry;

        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId,
            $this->webAuthContractId,
            "web_auth_verify",
            $argsMap,
            12346,
            1000000
        );
        $entries[] = $clientEntry;

        $challengeXdr = $this->encodeAuthEntries($entries);
        $challengeResponse = new Response(200, [], json_encode([
            'authorization_entries' => $challengeXdr,
        ]));

        $mock = new MockHandler([$challengeResponse]);
        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $clientSigner = KeyPair::random();
        $webAuth->jwtToken(
            $this->clientContractId,
            [$clientSigner],
            $this->domain
        );
    }

    /**
     * Test validation error: invalid web_auth_domain
     */
    public function testInvalidWebAuthDomain(): void
    {
        $this->expectException(ContractChallengeValidationErrorInvalidWebAuthDomain::class);

        $nonce = "test_nonce_" . time();
        $wrongWebAuthDomain = "wrong.auth.stellar.org";

        $entries = [];
        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            $wrongWebAuthDomain, // Wrong web auth domain
            $this->serverAccountId,
            $nonce
        );

        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId,
            $this->webAuthContractId,
            "web_auth_verify",
            $argsMap,
            12345,
            1000000
        );
        $serverEntry->sign($this->serverKeyPair, Network::testnet());
        $entries[] = $serverEntry;

        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId,
            $this->webAuthContractId,
            "web_auth_verify",
            $argsMap,
            12346,
            1000000
        );
        $entries[] = $clientEntry;

        $challengeXdr = $this->encodeAuthEntries($entries);
        $challengeResponse = new Response(200, [], json_encode([
            'authorization_entries' => $challengeXdr,
        ]));

        $mock = new MockHandler([$challengeResponse]);
        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $clientSigner = KeyPair::random();
        $webAuth->jwtToken(
            $this->clientContractId,
            [$clientSigner],
            $this->domain
        );
    }

    /**
     * Test validation error: invalid account
     */
    public function testInvalidAccount(): void
    {
        $this->expectException(ContractChallengeValidationErrorInvalidAccount::class);

        $nonce = "test_nonce_" . time();
        $wrongClientAccount = "CBMKBASJGUKV26JB55OKZW3G3PGQ4C7PLRH6L2RW74PYUTE22Y4KFW56";

        $entries = [];
        $argsMap = $this->buildArgsMap(
            $wrongClientAccount, // Wrong client account
            $this->domain,
            "auth.example.stellar.org",
            $this->serverAccountId,
            $nonce
        );

        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId,
            $this->webAuthContractId,
            "web_auth_verify",
            $argsMap,
            12345,
            1000000
        );
        $serverEntry->sign($this->serverKeyPair, Network::testnet());
        $entries[] = $serverEntry;

        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId,
            $this->webAuthContractId,
            "web_auth_verify",
            $argsMap,
            12346,
            1000000
        );
        $entries[] = $clientEntry;

        $challengeXdr = $this->encodeAuthEntries($entries);
        $challengeResponse = new Response(200, [], json_encode([
            'authorization_entries' => $challengeXdr,
        ]));

        $mock = new MockHandler([$challengeResponse]);
        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $clientSigner = KeyPair::random();
        $webAuth->jwtToken(
            $this->clientContractId,
            [$clientSigner],
            $this->domain
        );
    }

    /**
     * Test successful authentication flow with client domain verification
     */
    public function testClientDomainSuccess(): void
    {
        $nonce = "test_nonce_" . time();
        $clientDomain = "client.example.com";
        $clientDomainKeyPair = KeyPair::random();
        $clientDomainAccount = $clientDomainKeyPair->getAccountId();

        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            "auth.example.stellar.org",
            $this->serverAccountId,
            $nonce,
            $clientDomain,
            $clientDomainAccount
        );

        $challengeResponse = new Response(200, [], json_encode([
            'authorization_entries' => $challengeXdr,
            'network_passphrase' => 'Test SDF Network ; September 2015'
        ]));

        $tokenResponse = new Response(200, [], json_encode([
            'token' => $this->successJWTToken
        ]));

        $mock = new MockHandler([$challengeResponse, $tokenResponse]);

        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );
        $expectedServerEntryXdr = $webAuth->decodeAuthorizationEntries($challengeXdr)[0]->toBase64Xdr();
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $clientSigner = KeyPair::random();
        $token = $webAuth->jwtToken(
            $this->clientContractId,
            [$clientSigner],
            $this->domain,
            $clientDomain,
            $clientDomainKeyPair
        );

        $this->assertEquals($this->successJWTToken, $token);
        $this->assertCount(0, $mock);
        $postRequest = $mock->getLastRequest();
        $this->assertSame('POST', $postRequest->getMethod());
        parse_str((string) $postRequest->getBody(), $postBody);
        $submitted = $webAuth->decodeAuthorizationEntries($postBody['authorization_entries']);
        $this->assertCount(3, $submitted);
        $this->assertSame($expectedServerEntryXdr, $submitted[0]->toBase64Xdr());

        $clientCreds = $submitted[1]->credentials->getAddressCredentials();
        $this->assertSame(123466, $clientCreds->signatureExpirationLedger);
        $this->assertCount(1, $clientCreds->signature->vec);
        $this->assertTrue($clientSigner->verifySignature(
            $this->extractFirstSignatureBytes($clientCreds->signature),
            Hash::generate($submitted[1]->buildPreimage(Network::testnet())->encode())
        ));

        $cdCreds = $submitted[2]->credentials->getAddressCredentials();
        $this->assertSame(123466, $cdCreds->signatureExpirationLedger);
        $this->assertCount(1, $cdCreds->signature->vec);
        $this->assertTrue($clientDomainKeyPair->verifySignature(
            $this->extractFirstSignatureBytes($cdCreds->signature),
            Hash::generate($submitted[2]->buildPreimage(Network::testnet())->encode())
        ));
    }

    /**
     * Test signing challenge entries built the way js-stellar-sdk builds them
     *
     * js-stellar-sdk writes an empty signature vector and expiration ledger 0 into unsigned
     * entries. Signing stamps the requested expiration on the client and client domain entries
     * and appends one signature that verifies against each entry's preimage.
     */
    public function testSignAuthorizationEntriesStampsPlaceholderEntriesFromJsStellarSdk(): void
    {
        $clientSigner = KeyPair::random();
        $clientDomainKeyPair = KeyPair::random();
        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            "auth.example.stellar.org",
            $this->serverAccountId,
            "test_nonce",
            "client.example.com",
            $clientDomainKeyPair->getAccountId()
        );
        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, "web_auth_verify", $argsMap, 12346, 0, XdrSCVal::forVec([])
        );
        $clientDomainEntry = $this->buildAuthEntry(
            $clientDomainKeyPair->getAccountId(), $this->webAuthContractId, "web_auth_verify", $argsMap, 12347, 0, XdrSCVal::forVec([])
        );

        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );
        $webAuth->setMockHandler(new MockHandler([]));

        $signed = $webAuth->signAuthorizationEntries(
            [$clientEntry, $clientDomainEntry],
            $this->clientContractId,
            [$clientSigner],
            500,
            $clientDomainKeyPair
        );

        $this->assertCount(2, $signed);
        foreach ([[$signed[0], $clientSigner], [$signed[1], $clientDomainKeyPair]] as [$entry, $signer]) {
            $credentials = $entry->credentials->getAddressCredentials();
            $this->assertSame(500, $credentials->signatureExpirationLedger);
            $this->assertCount(1, $credentials->signature->vec);
            $signature = null;
            foreach ($credentials->signature->vec[0]->map ?? [] as $mapEntry) {
                if ($mapEntry->key->sym === 'signature') {
                    $signature = $mapEntry->val->bytes?->getValue();
                }
            }
            $this->assertNotNull($signature, 'The signature element must carry a signature field');
            $hash = Hash::generate($entry->buildPreimage(Network::testnet())->encode());
            $this->assertTrue($signer->verifySignature($signature, $hash));
        }
    }

    /**
     * Test successful authentication flow with client domain signing callback
     *
     * This test validates that a client domain can be signed using a callback function
     * instead of providing the keypair directly. The callback receives a single
     * SorobanAuthorizationEntry (the client domain entry) and must return it signed.
     *
     * When using clientDomainSigningCallback without clientDomainKeyPair, the jwtToken
     * method fetches the stellar.toml from the client domain to get the signing key.
     * This test properly mocks the stellar.toml fetch to test the complete callback flow.
     *
     * Mock response order (matching actual code execution order):
     * 1. Challenge response (authorization entries from the server)
     * 2. stellar.toml fetch (returns SIGNING_KEY for the client domain)
     * 3. Token response (JWT token)
     */
    public function testClientDomainCallbackSuccess(): void
    {
        $nonce = "test_nonce_" . time();
        $clientDomain = "client.example.com";
        $clientDomainKeyPair = KeyPair::random();
        $clientDomainAccount = $clientDomainKeyPair->getAccountId();

        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            "auth.example.stellar.org",
            $this->serverAccountId,
            $nonce,
            $clientDomain,
            $clientDomainAccount
        );

        // Mock response 1: Challenge response from auth server
        $challengeResponse = new Response(200, [], json_encode([
            'authorization_entries' => $challengeXdr,
            'network_passphrase' => 'Test SDF Network ; September 2015'
        ]));

        // Mock response 2: stellar.toml fetch for client domain signing key
        // This is fetched after the challenge when only clientDomainSigningCallback is provided
        $stellarTomlContent = "SIGNING_KEY = \"" . $clientDomainAccount . "\"";
        $stellarTomlResponse = new Response(200, [], $stellarTomlContent);

        // Mock response 3: Token response from auth server
        $tokenResponse = new Response(200, [], json_encode([
            'token' => $this->successJWTToken
        ]));

        $mock = new MockHandler([$challengeResponse, $stellarTomlResponse, $tokenResponse]);

        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );
        $expectedServerEntryXdr = $webAuth->decodeAuthorizationEntries($challengeXdr)[0]->toBase64Xdr();
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        // Track how many times the callback was invoked
        $callbackCalls = 0;

        // Create a callback that signs the client domain entry
        // The callback receives a single SorobanAuthorizationEntry and returns a signed SorobanAuthorizationEntry
        $callback = function (SorobanAuthorizationEntry $entry) use ($clientDomainKeyPair, &$callbackCalls): SorobanAuthorizationEntry {
            $callbackCalls++;
            // Set signature expiration ledger before signing
            $entry->credentials->addressCredentials->signatureExpirationLedger = 1000000;
            $entry->sign($clientDomainKeyPair, Network::testnet());
            return $entry;
        };

        $clientSigner = KeyPair::random();
        // Only provide clientDomainSigningCallback, NOT clientDomainKeyPair
        // The signing key is fetched from the mocked stellar.toml response
        $token = $webAuth->jwtToken(
            $this->clientContractId,
            [$clientSigner],
            $this->domain,
            $clientDomain,
            clientDomainSigningCallback: $callback
        );

        $this->assertSame($this->successJWTToken, $token);
        $this->assertSame(1, $callbackCalls, 'Client domain signing callback must be invoked once');
        $this->assertCount(0, $mock);
        $postRequest = $mock->getLastRequest();
        $this->assertSame('POST', $postRequest->getMethod());
        parse_str((string) $postRequest->getBody(), $postBody);
        $submitted = $webAuth->decodeAuthorizationEntries($postBody['authorization_entries']);
        $this->assertCount(3, $submitted);
        $this->assertSame($expectedServerEntryXdr, $submitted[0]->toBase64Xdr());

        $clientCreds = $submitted[1]->credentials->getAddressCredentials();
        $this->assertSame(123466, $clientCreds->signatureExpirationLedger);
        $this->assertCount(1, $clientCreds->signature->vec);
        $this->assertTrue($clientSigner->verifySignature(
            $this->extractFirstSignatureBytes($clientCreds->signature),
            Hash::generate($submitted[1]->buildPreimage(Network::testnet())->encode())
        ));

        $cdCreds = $submitted[2]->credentials->getAddressCredentials();
        $this->assertSame(1000000, $cdCreds->signatureExpirationLedger);
        $this->assertCount(1, $cdCreds->signature->vec);
        $this->assertTrue($clientDomainKeyPair->verifySignature(
            $this->extractFirstSignatureBytes($cdCreds->signature),
            Hash::generate($submitted[2]->buildPreimage(Network::testnet())->encode())
        ));
    }

    /** @return array{WebAuthForContracts, MockHandler, array<SorobanAuthorizationEntry>} */
    private function callbackChallenge(
        string $domainAccountId, string $arm = 'ADDRESS', bool $nonVoidSignature = false
    ): array {
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $entries = $webAuth->decodeAuthorizationEntries($this->buildValidChallenge(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org',
            $this->serverAccountId, 'callback-nonce', 'client.example.com', $domainAccountId
        ));
        if ($arm === 'ADDRESS_V2') {
            $entries[2]->credentials = SorobanCredentials::forAddressCredentialsV2(
                $entries[2]->credentials->getAddressCredentials()
            );
        } elseif ($arm === 'ADDRESS_WITH_DELEGATES') {
            $entries[2] = SorobanAuthorizationEntry::withDelegates($entries[2], 1000000, [
                new SorobanDelegateDescriptor($this->clientContractId, XdrSCVal::forBytes('delegate signature'), [
                    new SorobanDelegateDescriptor($this->serverAccountId, XdrSCVal::forBytes('nested signature')),
                ]),
                new SorobanDelegateDescriptor($this->webAuthContractId),
            ]);
        }
        if ($nonVoidSignature) {
            $creds = $entries[2]->credentials->getAddressCredentials();
            $creds->signature = XdrSCVal::forVec([]);
            $entries[2]->credentials->writeBackAddressCredentials($creds);
        }
        $mock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $this->encodeAuthEntries($entries)])),
            new Response(200, [], 'SIGNING_KEY="' . $domainAccountId . '"'),
            new Response(200, [], '{"token":"callback-token"}'),
        ]);
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());
        return [$webAuth, $mock, $entries];
    }

    /** @return array<string, array{string, string, int, bool}> */
    public static function acceptedClientDomainCallbacks(): array
    {
        $cases = [];
        foreach (['ADDRESS', 'ADDRESS_V2', 'ADDRESS_WITH_DELEGATES'] as $arm) {
            foreach (['G', 'C'] as $addressType) {
                foreach ([XdrSCValType::SCV_VOID, XdrSCValType::SCV_BYTES, XdrSCValType::SCV_VEC] as $signatureType) {
                    foreach ([false, true] as $nonVoidSignature) {
                        $cases[$arm . ' ' . $addressType . ' ' . $signatureType . ' ' . (int) $nonVoidSignature] =
                            [$arm, $addressType, $signatureType, $nonVoidSignature];
                    }
                }
            }
        }
        return $cases;
    }

    #[DataProvider('acceptedClientDomainCallbacks')]
    public function testClientDomainCallbackPreservesAnyEncodableSignature(
        string $arm, string $addressType, int $signatureType, bool $nonVoidSignature
    ): void {
        $domainAccountId = $addressType === 'G' ? KeyPair::random()->getAccountId() : $this->webAuthContractId;
        [$webAuth, $mock, $entries] = $this->callbackChallenge($domainAccountId, $arm, $nonVoidSignature);
        $callback = function (SorobanAuthorizationEntry $entry) use ($signatureType, $nonVoidSignature): SorobanAuthorizationEntry {
            $this->assertSame(12347, $entry->credentials->getAddressCredentials()->nonce);
            $this->assertSame(123466, $entry->credentials->getAddressCredentials()->signatureExpirationLedger);
            $creds = $entry->credentials->getAddressCredentials();
            if ($nonVoidSignature) {
                $this->assertSame(XdrSCValType::SCV_VEC, $creds->signature->type->value);
                $this->assertSame([], $creds->signature->vec);
            } else {
                $this->assertSame(XdrSCValType::SCV_VOID, $creds->signature->type->value);
            }
            $creds->signatureExpirationLedger = 123467;
            $creds->signature = match ($signatureType) {
                XdrSCValType::SCV_VOID => XdrSCVal::forVoid(),
                XdrSCValType::SCV_BYTES => XdrSCVal::forBytes('callback signature'),
                XdrSCValType::SCV_VEC => XdrSCVal::forVec([XdrSCVal::forString('arbitrary value')]),
            };
            $entry->credentials->writeBackAddressCredentials($creds);
            return $entry;
        };
        $signed = $webAuth->signAuthorizationEntries($entries, $this->clientContractId, [], 123466,
            clientDomainSigningCallback: $callback, clientDomainAccountId: $domainAccountId);

        $entriesToVerify = [$signed[2]];
        if ($addressType === 'G') {
            $this->assertSame('callback-token', $webAuth->jwtToken(
                $this->clientContractId, [KeyPair::random()], clientDomain: 'client.example.com',
                clientDomainSigningCallback: $callback
            ));
            $this->assertCount(0, $mock);
            $request = $mock->getLastRequest();
            $this->assertSame('POST', $request->getMethod());
            $this->assertSame($this->authServer, (string) $request->getUri());
            parse_str((string) $request->getBody(), $body);
            $submitted = $webAuth->decodeAuthorizationEntries($body['authorization_entries']);
            $this->assertCount(3, $submitted);
            $entriesToVerify[] = $submitted[2];
        }
        foreach ($entriesToVerify as $entry) {
            $this->assertSame(match ($arm) {
                'ADDRESS' => XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS,
                'ADDRESS_V2' => XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2,
                'ADDRESS_WITH_DELEGATES' => XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES,
            }, $entry->credentials->credentialType);
            if ($arm === 'ADDRESS_WITH_DELEGATES') {
                $delegates = $entry->credentials->addressWithDelegates->delegates;
                $this->assertCount(2, $delegates);
                $this->assertSame('CA7A3N2BB35XMTFPAYWVZEF4TEYXW7DAEWDXJNQGUPR5SWSM2UVZCJM2',
                    Address::fromXdr($delegates[0]->address)->toStrKey());
                $this->assertSame(XdrSCValType::SCV_VOID, $delegates[0]->signature->type->value);
                $this->assertSame([], $delegates[0]->nestedDelegates);
                $this->assertSame('CDZJIDQW5WTPAZ64PGIJGVEIDNK72LL3LKUZWG3G6GWXYQKI2JNIVFNV',
                    Address::fromXdr($delegates[1]->address)->toStrKey());
                $this->assertSame(XdrSCValType::SCV_BYTES, $delegates[1]->signature->type->value);
                $this->assertSame('delegate signature', $delegates[1]->signature->bytes->value);
                $this->assertCount(1, $delegates[1]->nestedDelegates);
                $nested = $delegates[1]->nestedDelegates[0];
                $this->assertSame('GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP',
                    Address::fromXdr($nested->address)->toStrKey());
                $this->assertSame(XdrSCValType::SCV_BYTES, $nested->signature->type->value);
                $this->assertSame('nested signature', $nested->signature->bytes->value);
                $this->assertSame([], $nested->nestedDelegates);
            } else {
                $this->assertNull($entry->credentials->addressWithDelegates);
            }
            $creds = $entry->credentials->getAddressCredentials();
            $this->assertSame(12347, $creds->nonce);
            $this->assertSame(123467, $creds->signatureExpirationLedger);
            $this->assertSame($signatureType, $creds->signature->type->value);
            if ($signatureType === XdrSCValType::SCV_BYTES) {
                $this->assertSame('callback signature', $creds->signature->bytes->value);
            } elseif ($signatureType === XdrSCValType::SCV_VEC) {
                $this->assertCount(1, $creds->signature->vec);
                $this->assertSame(XdrSCValType::SCV_STRING, $creds->signature->vec[0]->type->value);
                $this->assertSame('arbitrary value', $creds->signature->vec[0]->str);
            }
        }
    }

    public function testClientDomainCallbackSuppliesExpirationWithoutClientSigners(): void
    {
        [$webAuth, $mock] = $this->callbackChallenge(KeyPair::random()->getAccountId());
        $callback = function (SorobanAuthorizationEntry $entry): SorobanAuthorizationEntry {
            $creds = $entry->credentials->getAddressCredentials();
            $this->assertSame(123466, $creds->signatureExpirationLedger);
            $creds->signatureExpirationLedger = 123468;
            $creds->signature = XdrSCVal::forBytes('remote signature without client signers');
            $entry->credentials->writeBackAddressCredentials($creds);
            return $entry;
        };
        $this->assertSame('callback-token', $webAuth->jwtToken(
            $this->clientContractId, signers: [], clientDomain: 'client.example.com',
            clientDomainSigningCallback: $callback, signatureExpirationLedger: null
        ));
        $this->assertCount(0, $mock);
        $request = $mock->getLastRequest();
        $this->assertSame('POST', $request->getMethod());
        parse_str((string) $request->getBody(), $body);
        $submitted = $webAuth->decodeAuthorizationEntries($body['authorization_entries']);
        $this->assertCount(3, $submitted);
        $clientCreds = $submitted[1]->credentials->getAddressCredentials();
        $this->assertSame(123466, $clientCreds->signatureExpirationLedger);
        $this->assertSame(XdrSCValType::SCV_VOID, $clientCreds->signature->type->value);
        $creds = $submitted[2]->credentials->getAddressCredentials();
        $this->assertSame(123468, $creds->signatureExpirationLedger);
        $this->assertSame(XdrSCValType::SCV_BYTES, $creds->signature->type->value);
        $this->assertSame('remote signature without client signers', $creds->signature->bytes->value);
    }

    /** @return array<string, array{string, string}> */
    public static function rejectedClientDomainCallbacks(): array
    {
        $structural = 'Client domain signing callback returned a different authorization entry';
        return [
            'nonce' => ['nonce', $structural],
            'root argument' => ['root argument', $structural],
            'address' => ['address', $structural],
            'address arm' => ['address arm', $structural],
            'source account arm' => ['source account arm', $structural],
            'mutation and fresh answer' => ['mutation and fresh answer', $structural],
            'changed unsigned entry' => ['changed unsigned entry', $structural],
            'delegate address' => ['delegate address', $structural],
            'delegate signature' => ['delegate signature', $structural],
            'delegate order' => ['delegate order', $structural],
            'nested delegate' => ['nested delegate', $structural],
            'wrong return type' => ['wrong return type', 'Client domain signing callback must return a SorobanAuthorizationEntry object'],
        ];
    }

    #[DataProvider('rejectedClientDomainCallbacks')]
    public function testClientDomainCallbackRejectsAnswerWithoutTokenPost(string $case, string $message): void
    {
        $domainKey = KeyPair::random();
        [$webAuth, $mock] = $this->callbackChallenge(
            $domainKey->getAccountId(), str_contains($case, 'delegate') ? 'ADDRESS_WITH_DELEGATES' : 'ADDRESS'
        );
        $callback = function (SorobanAuthorizationEntry $entry) use ($case, $domainKey): mixed {
            $creds = $entry->credentials->getAddressCredentials();
            switch ($case) {
                case 'nonce':
                case 'changed unsigned entry':
                    $creds->nonce = 98765;
                    break;
                case 'root argument':
                    $entry->rootInvocation->function->contractFn->args[0]->map[0]->val = XdrSCVal::forString('another-account');
                    break;
                case 'address':
                    $creds->address = Address::fromAccountId(KeyPair::random()->getAccountId());
                    break;
                case 'address arm':
                    $entry->credentials = SorobanCredentials::forAddressCredentialsV2($creds);
                    break;
                case 'source account arm':
                    $entry->credentials = SorobanCredentials::forSourceAccount();
                    return $entry;
                case 'mutation and fresh answer':
                    $creds->nonce = 98765;
                    $entry = SorobanAuthorizationEntry::fromBase64Xdr($entry->toBase64Xdr());
                    break;
                case 'delegate address':
                    $entry->credentials->addressWithDelegates->delegates[0]->address = Address::fromAccountId($domainKey->getAccountId())->toXdr();
                    break;
                case 'delegate signature':
                    $entry->credentials->addressWithDelegates->delegates[0]->signature = XdrSCVal::forBytes('changed signature');
                    break;
                case 'delegate order':
                    $entry->credentials->addressWithDelegates->delegates = array_reverse($entry->credentials->addressWithDelegates->delegates);
                    break;
                case 'nested delegate':
                    foreach ($entry->credentials->addressWithDelegates->delegates as $delegate) {
                        $delegate->nestedDelegates = [];
                    }
                    break;
                case 'wrong return type':
                    return null;
            }
            if ($case === 'changed unsigned entry') {
                return $entry;
            }
            $entry->sign($domainKey, Network::testnet());
            return $entry;
        };
        $error = null;
        try {
            $webAuth->jwtToken($this->clientContractId, [KeyPair::random()],
                clientDomain: 'client.example.com', clientDomainSigningCallback: $callback);
        } catch (InvalidArgumentException $e) {
            $error = $e;
        }
        $this->assertCount(1, $mock, 'The token response must remain queued: no token POST');
        $this->assertSame('GET', $mock->getLastRequest()->getMethod());
        $this->assertNotNull($error, 'The callback answer must be rejected');
        $this->assertSame(InvalidArgumentException::class, $error::class);
        $this->assertSame($message, $error->getMessage());
    }

    public function testClientDomainCallbackArgumentIsIndependentOfRetainedEntry(): void
    {
        $domainKey = KeyPair::random();
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $entry = $this->buildAuthEntry($domainKey->getAccountId(), $this->webAuthContractId,
            'web_auth_verify', XdrSCVal::forMap([]), 12347, 1000000);
        $callback = function (SorobanAuthorizationEntry &$argument) use ($domainKey): SorobanAuthorizationEntry {
            $answer = SorobanAuthorizationEntry::fromBase64Xdr($argument->toBase64Xdr());
            $argument->credentials->getAddressCredentials()->nonce = 98765;
            $argument->rootInvocation->function->contractFn->functionName = 'changed';
            $argument = SorobanAuthorizationEntry::fromBase64Xdr($argument->toBase64Xdr());
            $answer->sign($domainKey, Network::testnet());
            return $answer;
        };
        $signed = $webAuth->signAuthorizationEntries([$entry], $this->clientContractId, [], 123466,
            clientDomainSigningCallback: $callback, clientDomainAccountId: $domainKey->getAccountId());
        $this->assertSame(12347, $entry->credentials->getAddressCredentials()->nonce);
        $this->assertSame(123466, $entry->credentials->getAddressCredentials()->signatureExpirationLedger);
        $this->assertSame('web_auth_verify', $entry->rootInvocation->function->contractFn->functionName);
        $this->assertSame(XdrSCValType::SCV_VOID, $entry->credentials->getAddressCredentials()->signature->type->value);
        $this->assertSame(12347, $signed[0]->credentials->getAddressCredentials()->nonce);
        $this->assertCount(1, $signed[0]->credentials->getAddressCredentials()->signature->vec);
    }

    public function testClientDomainCallbackCannotMutateReturnedEntriesAfterSigning(): void
    {
        [$webAuth, , $entries] = $this->callbackChallenge($this->webAuthContractId);
        $retained = null;
        $callback = static function (SorobanAuthorizationEntry $entry) use (&$retained): SorobanAuthorizationEntry {
            $retained = $entry;
            return $entry;
        };
        $signed = $webAuth->signAuthorizationEntries($entries, $this->clientContractId, [], 123466,
            clientDomainSigningCallback: $callback, clientDomainAccountId: $this->webAuthContractId);
        $this->assertNotNull($retained);
        $retained->credentials->getAddressCredentials()->nonce = 98765;
        $this->assertSame(12347, $signed[2]->credentials->getAddressCredentials()->nonce);
        $encoded = SorobanAuthorizationEntry::fromBase64Xdr($signed[2]->toBase64Xdr());
        $this->assertSame(12347, $encoded->credentials->getAddressCredentials()->nonce);
    }

    public function testClientDomainCallbackAnswerIsEncodedOnlyOnce(): void
    {
        [$webAuth, , $entries] = $this->callbackChallenge($this->webAuthContractId);
        $encodings = 0;
        $callback = static function (SorobanAuthorizationEntry $entry) use (&$encodings): SorobanAuthorizationEntry {
            return new class($entry->credentials, $entry->rootInvocation, $encodings) extends SorobanAuthorizationEntry {
                private int $encodings;

                public function __construct(
                    SorobanCredentials $credentials, SorobanAuthorizedInvocation $rootInvocation, int &$encodings
                ) {
                    parent::__construct($credentials, $rootInvocation);
                    $this->encodings =& $encodings;
                }

                public function toXdr(): XdrSorobanAuthorizationEntry
                {
                    if (++$this->encodings > 1) {
                        $this->credentials->getAddressCredentials()->nonce = 98765;
                    }
                    return parent::toXdr();
                }
            };
        };
        $signed = $webAuth->signAuthorizationEntries($entries, $this->clientContractId, [], 123466,
            clientDomainSigningCallback: $callback, clientDomainAccountId: $this->webAuthContractId);
        $this->assertSame(1, $encodings);
        $this->assertSame(SorobanAuthorizationEntry::class, $signed[2]::class);
        $this->assertSame(12347, $signed[2]->credentials->getAddressCredentials()->nonce);
        $encoded = SorobanAuthorizationEntry::fromBase64Xdr($signed[2]->toBase64Xdr());
        $this->assertSame(1, $encodings);
        $this->assertSame(12347, $encoded->credentials->getAddressCredentials()->nonce);
        $this->assertSame(123466, $encoded->credentials->getAddressCredentials()->signatureExpirationLedger);
        $this->assertSame('web_auth_verify', $encoded->rootInvocation->function->contractFn->functionName);
    }

    /** @return array<string, array{string, class-string<\Throwable>, ?string}> */
    public static function malformedClientDomainCallbacks(): array
    {
        return [
            'null bytes payload' => ['null bytes payload', \Error::class, null],
            'undecodable XDR' => ['undecodable XDR', InvalidArgumentException::class, 'Unexpected end of XDR data'],
        ];
    }

    #[DataProvider('malformedClientDomainCallbacks')]
    public function testMalformedClientDomainCallbackRejectsAnswerWithoutTokenPost(
        string $case, string $previousClass, ?string $previousMessage
    ): void {
        $domainAccountId = KeyPair::random()->getAccountId();
        [$webAuth, $mock, $entries] = $this->callbackChallenge($domainAccountId);
        $callback = static function (SorobanAuthorizationEntry $entry) use ($case): SorobanAuthorizationEntry {
            if ($case === 'null bytes payload') {
                $entry->credentials->getAddressCredentials()->signature = new XdrSCVal(
                    new XdrSCValType(XdrSCValType::SCV_BYTES)
                );
                return $entry;
            }
            return new class($entry->credentials, $entry->rootInvocation) extends SorobanAuthorizationEntry {
                public function toBase64Xdr(): string
                {
                    return 'AAAA';
                }
            };
        };
        foreach (['signAuthorizationEntries', 'jwtToken'] as $method) {
            $error = null;
            try {
                if ($method === 'signAuthorizationEntries') {
                    $webAuth->signAuthorizationEntries($entries, $this->clientContractId, [], 123466,
                        clientDomainSigningCallback: $callback, clientDomainAccountId: $domainAccountId);
                } else {
                    $webAuth->jwtToken($this->clientContractId, [KeyPair::random()],
                        clientDomain: 'client.example.com', clientDomainSigningCallback: $callback);
                }
            } catch (\Throwable $e) {
                $error = $e;
            }
            $this->assertNotNull($error);
            $this->assertSame(InvalidArgumentException::class, $error::class);
            $this->assertSame('Client domain signing callback returned an invalid authorization entry', $error->getMessage());
            $this->assertSame(0, $error->getCode());
            $previous = $error->getPrevious();
            $this->assertNotNull($previous);
            $this->assertInstanceOf($previousClass, $previous);
            $this->assertNotSame('', $previous->getMessage());
            if ($previousMessage !== null) {
                $this->assertSame($previousMessage, $previous->getMessage());
            }
        }
        $this->assertCount(1, $mock, 'The token response must remain queued: no token POST');
        $this->assertSame('GET', $mock->getLastRequest()->getMethod());
    }

    public function testContractClientDomainCallbackRequiresOnlyStructuralEquality(): void
    {
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $entry = $this->buildAuthEntry($this->webAuthContractId, $this->webAuthContractId,
            'web_auth_verify', XdrSCVal::forMap([]), 12347, 123466);
        $signed = $webAuth->signAuthorizationEntries([$entry], $this->clientContractId, [], 123466,
            clientDomainSigningCallback: static fn(SorobanAuthorizationEntry $entry): SorobanAuthorizationEntry => $entry,
            clientDomainAccountId: $this->webAuthContractId);
        $this->assertSame($entry->toBase64Xdr(), $signed[0]->toBase64Xdr());
        $this->assertSame(XdrSCValType::SCV_VOID, $signed[0]->credentials->getAddressCredentials()->signature->type->value);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Client domain signing callback returned a different authorization entry');
        $webAuth->signAuthorizationEntries([$entry], $this->clientContractId, [], 123466,
            clientDomainSigningCallback: static function (SorobanAuthorizationEntry $entry): SorobanAuthorizationEntry {
                $entry->credentials->getAddressCredentials()->nonce = 98765;
                return $entry;
            }, clientDomainAccountId: $this->webAuthContractId);
    }

    private function generateCoSignerBeforeServerKey(): KeyPair
    {
        $serverPubKey = $this->serverKeyPair->getPublicKey();
        do {
            $coSigner = KeyPair::random();
        } while (strcmp($coSigner->getPublicKey(), $serverPubKey) >= 0);

        return $coSigner;
    }

    public function testServerSignatureAcceptedInSecondPosition(): void
    {
        $coSigner = $this->generateCoSignerBeforeServerKey();
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $entries = $webAuth->decodeAuthorizationEntries($this->buildValidChallenge(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId, 'multisig-nonce'
        ));
        $serverSignature = $entries[0]->credentials->getAddressCredentials()->signature->vec[0];
        $entries[0]->credentials->getAddressCredentials()->signature = XdrSCVal::forVoid();
        $entries[0]->sign($coSigner, Network::testnet());
        $entries[0]->credentials->getAddressCredentials()->signature->vec[] = $serverSignature;

        // Direct validateChallenge accepts server signature in second position
        $webAuth->validateChallenge($entries, $this->clientContractId, $this->domain);

        // Full jwtToken flow succeeds and preserves both server signatures in the POST
        $expectedServerEntryXdr = $entries[0]->toBase64Xdr();
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'authorization_entries' => $this->encodeAuthEntries($entries),
                'network_passphrase' => 'Test SDF Network ; September 2015',
            ])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $token = $webAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain);
        $this->assertSame($this->successJWTToken, $token);
        $this->assertCount(0, $mock);
        $postRequest = $mock->getLastRequest();
        $this->assertSame('POST', $postRequest->getMethod());
        parse_str((string) $postRequest->getBody(), $body);
        $submitted = $webAuth->decodeAuthorizationEntries($body['authorization_entries']);
        $this->assertCount(2, $submitted);
        $this->assertSame($expectedServerEntryXdr, $submitted[0]->toBase64Xdr());
        $this->assertCount(2, $submitted[0]->credentials->getAddressCredentials()->signature->vec);
    }

    public function testServerSignatureAcceptedInSecondPositionForAddressV2(): void
    {
        $coSigner = $this->generateCoSignerBeforeServerKey();
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $argsMap = $this->buildArgsMap(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId, 'multisig-v2-nonce'
        );
        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12345, 1000000
        );
        $serverEntry->credentials = SorobanCredentials::forAddressCredentialsV2(
            $serverEntry->credentials->getAddressCredentials()
        );
        $serverEntry->sign($coSigner, Network::testnet());
        $serverEntry->sign($this->serverKeyPair, Network::testnet());

        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12346, 1000000
        );
        $clientEntry->credentials = SorobanCredentials::forAddressCredentialsV2(
            $clientEntry->credentials->getAddressCredentials()
        );

        $webAuth->validateChallenge([$serverEntry, $clientEntry], $this->clientContractId, $this->domain);
        $this->assertCount(2, $serverEntry->credentials->getAddressCredentials()->signature->vec);
    }

    /** @return array<string, array{string}> */
    public static function rejectedServerSignatureCases(): array
    {
        return [
            'no element with server key' => ['no_server_key'],
            'server key first with invalid signature' => ['server_key_first_invalid_sig'],
            'server key second with invalid signature' => ['server_key_second_invalid_sig'],
            'server key signed for wrong network' => ['server_key_wrong_network'],
            'server key element missing signature field' => ['server_key_missing_sig_field'],
            'non-map element only' => ['non_map_only'],
        ];
    }

    #[DataProvider('rejectedServerSignatureCases')]
    public function testServerSignatureRejectionsSendNoPost(string $case): void
    {
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $entries = $webAuth->decodeAuthorizationEntries($this->buildValidChallenge(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId, 'sig-reject-nonce',
            signServerEntry: false
        ));

        $serverEntry = $entries[0];
        $creds = $serverEntry->credentials->getAddressCredentials();
        $otherSigner1 = KeyPair::random();
        $otherSigner2 = KeyPair::random();

        switch ($case) {
            case 'no_server_key':
                $serverEntry->sign($otherSigner1, Network::testnet());
                $serverEntry->sign($otherSigner2, Network::testnet());
                break;
            case 'server_key_first_invalid_sig':
                $badSig = new AccountEd25519Signature($this->serverKeyPair->getPublicKey(), str_repeat("\x00", 64));
                $creds->signature = XdrSCVal::forVec([$badSig->toXdrSCVal()]);
                $serverEntry->credentials->writeBackAddressCredentials($creds);
                break;
            case 'server_key_second_invalid_sig':
                $serverEntry->sign($otherSigner1, Network::testnet());
                $badSig = new AccountEd25519Signature($this->serverKeyPair->getPublicKey(), str_repeat("\x00", 64));
                $serverEntry->credentials->getAddressCredentials()->signature->vec[] = $badSig->toXdrSCVal();
                break;
            case 'server_key_wrong_network':
                $serverEntry->sign($otherSigner1, Network::testnet());
                $serverEntry->sign($this->serverKeyPair, Network::public());
                break;
            case 'server_key_missing_sig_field':
                $onlyPubKey = XdrSCVal::forMap([
                    new XdrSCMapEntry(
                        XdrSCVal::forSymbol('public_key'),
                        XdrSCVal::forBytes($this->serverKeyPair->getPublicKey())
                    ),
                ]);
                $creds->signature = XdrSCVal::forVec([$onlyPubKey]);
                $serverEntry->credentials->writeBackAddressCredentials($creds);
                break;
            case 'non_map_only':
                $creds->signature = XdrSCVal::forVec([XdrSCVal::forString('not-a-map')]);
                $serverEntry->credentials->writeBackAddressCredentials($creds);
                break;
        }

        $mock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $this->encodeAuthEntries($entries)])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $error = null;
        try {
            $webAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain);
        } catch (ContractChallengeValidationErrorInvalidServerSignature $e) {
            $error = $e;
        }

        $this->assertNotNull($error, "Case '$case' must throw ContractChallengeValidationErrorInvalidServerSignature");
        $this->assertSame('Server authorization entry has invalid signature', $error->getMessage());
        $this->assertCount(1, $mock, 'Token response must remain queued: no POST sent');
        $this->assertSame('GET', $mock->getLastRequest()->getMethod());
    }

    /**
     * Test validation error: client_domain_account mismatch
     */
    public function testInvalidClientDomainAccount(): void
    {
        $this->expectException(ContractChallengeValidationErrorInvalidArgs::class);

        $nonce = "test_nonce_" . time();
        $clientDomain = "client.example.com";
        $wrongClientDomainAccount = KeyPair::random()->getAccountId();
        $actualClientDomainKeyPair = KeyPair::random();

        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            "auth.example.stellar.org",
            $this->serverAccountId,
            $nonce,
            $clientDomain,
            $wrongClientDomainAccount // Wrong account in challenge
        );

        $challengeResponse = new Response(200, [], json_encode([
            'authorization_entries' => $challengeXdr,
            'network_passphrase' => 'Test SDF Network ; September 2015'
        ]));

        $mock = new MockHandler([$challengeResponse]);

        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $clientSigner = KeyPair::random();
        $webAuth->jwtToken(
            $this->clientContractId,
            [$clientSigner],
            $this->domain,
            $clientDomain,
            $actualClientDomainKeyPair // Different account provided
        );
    }

    /**
     * Test error handling: server returns error on challenge request
     */
    public function testGetChallengeError(): void
    {
        $errorResponse = new Response(400, [], json_encode([
            'error' => 'Invalid account'
        ]));

        $mock = new MockHandler([
            $errorResponse,
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);

        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $clientSigner = KeyPair::random();
        $error = null;
        try {
            $webAuth->jwtToken(
                $this->clientContractId,
                [$clientSigner],
                $this->domain
            );
        } catch (ContractChallengeRequestErrorResponse $e) {
            $error = $e;
        }

        $this->assertNotNull($error, 'Expected ContractChallengeRequestErrorResponse');
        $this->assertSame(400, $error->getHttpStatusCode());
        $this->assertSame('Invalid account', $error->getMessage());
        $this->assertCount(1, $mock, 'Token response must remain queued: no POST sent');
        $this->assertSame('GET', $mock->getLastRequest()->getMethod());
    }

    /** @return array<string, array{string, string}> */
    public static function malformedChallengeFields(): array
    {
        return [
            'authorization_entries' => ['{"authorization_entries": 123}', 'Field authorization_entries must be a string, got int'],
            'null authorization_entries' => ['{"authorization_entries": null}', 'Missing required field: authorization_entries'],
            'authorizationEntries' => ['{"authorizationEntries": ["a"]}', 'Field authorizationEntries must be a string, got array'],
            'network_passphrase' => ['{"authorization_entries": "AAAAAA==", "network_passphrase": 5}', 'Field network_passphrase must be a string, got int'],
            'networkPassphrase' => ['{"authorization_entries": "AAAAAA==", "networkPassphrase": ["a"]}', 'Field networkPassphrase must be a string, got array'],
        ];
    }

    #[DataProvider('malformedChallengeFields')]
    public function testMalformedChallengeFieldRejectedByParser(string $body, string $message): void
    {
        try {
            ContractChallengeResponse::fromJson(json_decode($body, true));
        } catch (Exception $e) {
            $this->assertSame(Exception::class, $e::class);
            $this->assertSame($message, $e->getMessage());
            return;
        }
        $this->fail('Expected Exception');
    }

    public function testNullNetworkPassphraseAccepted(): void
    {
        $response = ContractChallengeResponse::fromJson(json_decode(
            '{"authorization_entries": "AAAAAA==", "network_passphrase": null}', true
        ));
        $this->assertNull($response->getNetworkPassphrase());
        $this->assertSame('AAAAAA==', $response->getAuthorizationEntries());
    }

    public function testInvalidNetworkPassphraseRejectedByJwtToken(): void
    {
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $mock = new MockHandler([new Response(200, [],
            '{"authorization_entries": "AAAAAA==", "network_passphrase": "Public Global Stellar Network ; September 2015"}'
        )]);
        $webAuth->setMockHandler($mock);
        try {
            $webAuth->jwtToken($this->clientContractId, [$this->serverKeyPair]);
        } catch (ContractChallengeValidationErrorInvalidNetworkPassphrase $e) {
            $this->assertSame(
                "Network passphrase mismatch. Expected: 'Test SDF Network ; September 2015', Got: 'Public Global Stellar Network ; September 2015'",
                $e->getMessage()
            );
            $this->assertCount(0, $mock);
            $this->assertSame('GET', $mock->getLastRequest()->getMethod());
            return;
        }
        $this->fail('Expected ContractChallengeValidationErrorInvalidNetworkPassphrase');
    }

    #[DataProvider('malformedChallengeFields')]
    public function testMalformedChallengeFieldRejectedByJwtToken(string $body, string $message): void
    {
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler(new MockHandler([new Response(200, [], $body)]));
        try {
            $webAuth->jwtToken($this->clientContractId, [$this->serverKeyPair]);
            $this->fail('Expected ContractChallengeRequestErrorResponse');
        } catch (ContractChallengeRequestErrorResponse $e) {
            $this->assertSame($message, $e->getMessage());
            $this->assertSame(200, $e->getHttpStatusCode());
        }
    }

    /** @return array<string, array{string, string}> */
    public static function malformedTokenBodies(): array
    {
        return [
            'int token' => ['{"token": 123}', 'An unknown error occurred'],
            'array token' => ['{"token": ["a"]}', 'An unknown error occurred'],
            'int error' => ['{"error": 5}', 'An unknown error occurred'],
            'string body' => ['"abc"', 'Token response body must be a JSON object or array, got string'],
            'number body' => ['42', 'Token response body must be a JSON object or array, got int'],
            'null body' => ['null', 'Token response body must be a JSON object or array, got null'],
        ];
    }

    #[DataProvider('malformedTokenBodies')]
    public function testMalformedTokenResponseRejected(string $body, string $message): void
    {
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler(new MockHandler([new Response(200, [], $body)]));
        try {
            $webAuth->sendSignedChallenge([]);
            $this->fail('Expected SubmitContractChallengeErrorResponseException');
        } catch (SubmitContractChallengeErrorResponseException $e) {
            $this->assertSame($message, $e->getMessage());
        }
    }

    public function testTokenWithNonStringErrorAccepted(): void
    {
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler(new MockHandler([new Response(200, [], '{"token": "jwt", "error": 5}')]));
        $this->assertSame('jwt', $webAuth->sendSignedChallenge([]));
    }

    /**
     * Test error handling: server returns error on token request
     */
    public function testSubmitChallengeError(): void
    {
        $this->expectException(SubmitContractChallengeErrorResponseException::class);

        $nonce = "test_nonce_" . time();
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            "auth.example.stellar.org",
            $this->serverAccountId,
            $nonce
        );

        $challengeResponse = new Response(200, [], json_encode([
            'authorization_entries' => $challengeXdr,
            'network_passphrase' => 'Test SDF Network ; September 2015'
        ]));

        $errorTokenResponse = new Response(400, [], json_encode([
            'error' => 'Invalid signature'
        ]));

        $mock = new MockHandler([$challengeResponse, $errorTokenResponse]);

        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $clientSigner = KeyPair::random();
        $webAuth->jwtToken(
            $this->clientContractId,
            [$clientSigner],
            $this->domain
        );
    }

    /**
     * Test error handling: server timeout (504)
     */
    public function testSubmitChallengeTimeout(): void
    {
        $this->expectException(SubmitContractChallengeTimeoutResponseException::class);

        $nonce = "test_nonce_" . time();
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            "auth.example.stellar.org",
            $this->serverAccountId,
            $nonce
        );

        $challengeResponse = new Response(200, [], json_encode([
            'authorization_entries' => $challengeXdr,
            'network_passphrase' => 'Test SDF Network ; September 2015'
        ]));

        $timeoutResponse = new Response(504, [], 'Gateway Timeout');

        $mock = new MockHandler([$challengeResponse, $timeoutResponse]);

        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $clientSigner = KeyPair::random();
        $webAuth->jwtToken(
            $this->clientContractId,
            [$clientSigner],
            $this->domain
        );
    }

    /**
     * Test jwtToken validation: invalid account format (non-C...) throws before challenge GET
     */
    public function testInvalidAccountFormat(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => 'AAAAAA=='])),
        ]);
        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );
        $webAuth->setMockHandler($mock);

        $clientSigner = KeyPair::random();
        $gAccount = "GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP";
        try {
            $webAuth->jwtToken(
                $gAccount, // G... account, not C...
                [$clientSigner],
                $this->domain
            );
            $this->fail('Expected InvalidArgumentException for G... client account');
        } catch (InvalidArgumentException $e) {
            $this->assertSame("Client account must be a valid contract address (C...)", $e->getMessage());
            $this->assertStringNotContainsString($gAccount, $e->getMessage());
        }
        $this->assertCount(1, $mock, 'Challenge GET must not be sent when client account is invalid');
        $this->assertNull($mock->getLastRequest());
    }

    public function testDecodeAuthorizationEntriesRejectsTrailingBytes(): void
    {
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $this->assertSame([], $webAuth->decodeAuthorizationEntries(base64_encode(pack('N', 0))));
        $this->expectException(ContractChallengeValidationError::class);
        $this->expectExceptionMessage('Failed to decode authorization entries: ' . XdrSorobanAuthorizationEntries::class . ' leaves 1 of 5 bytes unread');
        $webAuth->decodeAuthorizationEntries(base64_encode(pack('N', 0) . "\x01"));
    }

    /**
     * Test authorization entries decoding: an entry count the remaining bytes cannot hold is rejected
     */
    public function testDecodeAuthorizationEntriesRejectsCountBeyondRemainingBytes(): void
    {
        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );

        $this->expectException(ContractChallengeValidationError::class);
        $this->expectExceptionMessage("Failed to decode authorization entries: XDR array count 1 exceeds the maximum of 0 for the 0 remaining bytes");

        $webAuth->decodeAuthorizationEntries(base64_encode(pack('N', 1)));
    }

    /**
     * Test constructor validation: invalid parameters rejected
     */
    public function testConstructorValidation(): void
    {
        // Test invalid webAuthContractId (not C...)
        try {
            new WebAuthForContracts(
                $this->authServer,
                "GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP", // G... instead of C...
                $this->serverAccountId,
                $this->domain,
                Network::testnet()
            );
            $this->fail("Expected InvalidArgumentException for invalid webAuthContractId");
        } catch (InvalidArgumentException $e) {
            $this->assertSame("webAuthContractId must be a valid contract address (C...)", $e->getMessage());
        }

        // Test invalid serverSigningKey (not G...)
        try {
            new WebAuthForContracts(
                $this->authServer,
                $this->webAuthContractId,
                "CCALHRGH5RXIDJDRLPPG4ZX2S563TB2QKKJR4STWKVQCYB6JVPYQXHRG", // C... instead of G...
                $this->domain,
                Network::testnet()
            );
            $this->fail("Expected InvalidArgumentException for invalid serverSigningKey");
        } catch (InvalidArgumentException $e) {
            $this->assertSame("serverSigningKey must be a valid ed25519 account address (G...)", $e->getMessage());
        }

        // Test invalid authEndpoint (not a URL)
        try {
            new WebAuthForContracts(
                "not-a-url",
                $this->webAuthContractId,
                $this->serverAccountId,
                $this->domain,
                Network::testnet()
            );
            $this->fail("Expected InvalidArgumentException for invalid authEndpoint");
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString("Service URL must use HTTPS", $e->getMessage());
        }

        // Test empty serverHomeDomain
        try {
            new WebAuthForContracts(
                $this->authServer,
                $this->webAuthContractId,
                $this->serverAccountId,
                "",
                Network::testnet()
            );
            $this->fail("Expected InvalidArgumentException for empty serverHomeDomain");
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString("serverHomeDomain must not be empty", $e->getMessage());
        }
    }

    /**
     * Test form-urlencoded content type and POST payload
     */
    public function testFormUrlEncodedSuccess(): void
    {
        $nonce = "test_nonce_" . time();
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            "auth.example.stellar.org",
            $this->serverAccountId,
            $nonce
        );

        $challengeResponse = new Response(200, [], json_encode([
            'authorization_entries' => $challengeXdr,
            'network_passphrase' => 'Test SDF Network ; September 2015'
        ]));

        $tokenResponse = new Response(200, [], json_encode([
            'token' => $this->successJWTToken
        ]));

        $mock = new MockHandler([$challengeResponse, $tokenResponse]);

        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );
        $expectedServerEntryXdr = $webAuth->decodeAuthorizationEntries($challengeXdr)[0]->toBase64Xdr();
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());
        $webAuth->setUseFormUrlEncoded(true);

        $clientSigner = KeyPair::random();
        $token = $webAuth->jwtToken(
            $this->clientContractId,
            [$clientSigner],
            $this->domain
        );

        $this->assertEquals($this->successJWTToken, $token);
        $this->assertCount(0, $mock);
        $postRequest = $mock->getLastRequest();
        $this->assertSame('POST', $postRequest->getMethod());
        $this->assertSame($this->authServer, (string) $postRequest->getUri());
        $this->assertStringStartsWith('application/x-www-form-urlencoded', $postRequest->getHeaderLine('Content-Type'));
        $rawBody = (string) $postRequest->getBody();
        $this->assertStringStartsWith('authorization_entries=', $rawBody);
        parse_str($rawBody, $postBody);
        $this->assertSame(['authorization_entries'], array_keys($postBody));
        $submitted = $webAuth->decodeAuthorizationEntries($postBody['authorization_entries']);
        $this->assertCount(2, $submitted);
        $this->assertSame($expectedServerEntryXdr, $submitted[0]->toBase64Xdr());
        $clientCreds = $submitted[1]->credentials->getAddressCredentials();
        $this->assertSame(123466, $clientCreds->signatureExpirationLedger);
        $this->assertCount(1, $clientCreds->signature->vec);
        $this->assertTrue($clientSigner->verifySignature(
            $this->extractFirstSignatureBytes($clientCreds->signature),
            Hash::generate($submitted[1]->buildPreimage(Network::testnet())->encode())
        ));
    }

    public function testJsonModeSuccess(): void
    {
        $nonce = "test_json_nonce";
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            "auth.example.stellar.org",
            $this->serverAccountId,
            $nonce
        );

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'authorization_entries' => $challengeXdr,
                'network_passphrase' => 'Test SDF Network ; September 2015',
            ])),
            new Response(200, [], json_encode([
                'token' => $this->successJWTToken,
            ])),
        ]);

        $webAuth = new WebAuthForContracts(
            $this->authServer,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );
        $expectedServerEntryXdr = $webAuth->decodeAuthorizationEntries($challengeXdr)[0]->toBase64Xdr();
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());
        $webAuth->setUseFormUrlEncoded(false);

        $clientSigner = KeyPair::random();
        $token = $webAuth->jwtToken(
            $this->clientContractId,
            [$clientSigner],
            $this->domain
        );

        $this->assertSame($this->successJWTToken, $token);
        $this->assertCount(0, $mock);
        $postRequest = $mock->getLastRequest();
        $this->assertSame('POST', $postRequest->getMethod());
        $this->assertSame($this->authServer, (string) $postRequest->getUri());
        $this->assertStringStartsWith('application/json', $postRequest->getHeaderLine('Content-Type'));
        $decodedBody = json_decode((string) $postRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($decodedBody);
        $this->assertSame(['authorization_entries'], array_keys($decodedBody));
        $submitted = $webAuth->decodeAuthorizationEntries($decodedBody['authorization_entries']);
        $this->assertCount(2, $submitted);
        $this->assertSame($expectedServerEntryXdr, $submitted[0]->toBase64Xdr());
        $clientCreds = $submitted[1]->credentials->getAddressCredentials();
        $this->assertSame(123466, $clientCreds->signatureExpirationLedger);
        $this->assertCount(1, $clientCreds->signature->vec);
        $this->assertTrue($clientSigner->verifySignature(
            $this->extractFirstSignatureBytes($clientCreds->signature),
            Hash::generate($submitted[1]->buildPreimage(Network::testnet())->encode())
        ));
    }

    /**
     * @return array<string, array{string, class-string<ContractChallengeValidationError>, string}>
     */
    public static function invalidArgsStructureAndConsistencyCases(): array
    {
        return [
            'zero positional args' => [
                'zero_args',
                ContractChallengeValidationErrorInvalidArgs::class,
                'Expected exactly one argument, got 0',
            ],
            'extra positional arg on server entry' => [
                'extra_positional_server',
                ContractChallengeValidationErrorInvalidArgs::class,
                'Expected exactly one argument, got 2',
            ],
            'extra positional arg on client entry' => [
                'extra_positional_client',
                ContractChallengeValidationErrorInvalidArgs::class,
                'Expected exactly one argument, got 2',
            ],
            'non-map argument' => [
                'non_map_arg',
                ContractChallengeValidationErrorInvalidArgs::class,
                'Arguments are not in map format',
            ],
            'non-symbol map key on server entry' => [
                'non_symbol_key_server',
                ContractChallengeValidationErrorInvalidArgs::class,
                'Argument map key must be a symbol',
            ],
            'non-symbol map key on client entry' => [
                'non_symbol_key_client',
                ContractChallengeValidationErrorInvalidArgs::class,
                'Argument map key must be a symbol',
            ],
            'non-string map value on server entry' => [
                'non_string_val_server',
                ContractChallengeValidationErrorInvalidArgs::class,
                "Argument map value for 'extra' must be a string",
            ],
            'non-string map value on client entry' => [
                'non_string_val_client',
                ContractChallengeValidationErrorInvalidArgs::class,
                "Argument map value for 'extra' must be a string",
            ],
            'duplicate known key' => [
                'duplicate_known_key',
                ContractChallengeValidationErrorInvalidArgs::class,
                "Duplicate argument key: 'account'",
            ],
            'duplicate unknown key on client entry' => [
                'duplicate_unknown_key_client',
                ContractChallengeValidationErrorInvalidArgs::class,
                "Duplicate argument key: 'extra'",
            ],
            'unknown key only on client entry' => [
                'unknown_key_only_client',
                ContractChallengeValidationErrorInvalidArgs::class,
                'Arguments are not consistent across authorization entries',
            ],
            'unknown key only on server entry' => [
                'unknown_key_only_server',
                ContractChallengeValidationErrorInvalidArgs::class,
                'Arguments are not consistent across authorization entries',
            ],
            'unknown key value differs across entries' => [
                'unknown_key_value_differs',
                ContractChallengeValidationErrorInvalidArgs::class,
                'Arguments are not consistent across authorization entries',
            ],
            'same count different unknown key names across entries' => [
                'different_unknown_key_names',
                ContractChallengeValidationErrorInvalidArgs::class,
                'Arguments are not consistent across authorization entries',
            ],
            'home_domain differs on client entry' => [
                'home_domain_differs',
                ContractChallengeValidationErrorInvalidArgs::class,
                'Arguments are not consistent across authorization entries',
            ],
            'empty nonce' => [
                'empty_nonce',
                ContractChallengeValidationErrorInvalidNonce::class,
                'Nonce argument is missing or empty',
            ],
            'missing nonce' => [
                'missing_nonce',
                ContractChallengeValidationErrorInvalidNonce::class,
                'Nonce argument is missing or empty',
            ],
            'nonce differs across entries' => [
                'nonce_differs',
                ContractChallengeValidationErrorInvalidNonce::class,
                'Nonce is not consistent across authorization entries',
            ],
            'missing web_auth_domain_account' => [
                'missing_web_auth_domain_account',
                ContractChallengeValidationErrorInvalidArgs::class,
                'Web auth domain account does not match server signing key',
            ],
            'wrong web_auth_domain_account' => [
                'wrong_web_auth_domain_account',
                ContractChallengeValidationErrorInvalidArgs::class,
                'Web auth domain account does not match server signing key',
            ],
        ];
    }

    #[DataProvider('invalidArgsStructureAndConsistencyCases')]
    public function testInvalidArgsStructureAndConsistencyRejectsWithoutPost(
        string $case,
        string $expectedExceptionClass,
        string $expectedMessage
    ): void {
        $baseArgs = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'args-test-nonce'
        );

        $serverArgs = [$baseArgs];
        $clientArgs = [$baseArgs];

        switch ($case) {
            case 'zero_args':
                $serverArgs = [];
                $clientArgs = [];
                break;
            case 'extra_positional_server':
                $serverArgs = [$baseArgs, XdrSCVal::forString('trailing')];
                break;
            case 'extra_positional_client':
                $clientArgs = [$baseArgs, XdrSCVal::forString('trailing')];
                break;
            case 'non_map_arg':
                $serverArgs = [XdrSCVal::forString('not-a-map')];
                $clientArgs = [XdrSCVal::forString('not-a-map')];
                break;
            case 'non_symbol_key_server':
                $badMap = XdrSCVal::forMap(array_merge($baseArgs->map, [
                    new XdrSCMapEntry(XdrSCVal::forString('extra'), XdrSCVal::forString('v')),
                ]));
                $serverArgs = [$badMap];
                $clientArgs = [$badMap];
                break;
            case 'non_symbol_key_client':
                $badMap = XdrSCVal::forMap(array_merge($baseArgs->map, [
                    new XdrSCMapEntry(XdrSCVal::forString('extra'), XdrSCVal::forString('v')),
                ]));
                $clientArgs = [$badMap];
                break;
            case 'non_string_val_server':
                $badMap = XdrSCVal::forMap(array_merge($baseArgs->map, [
                    new XdrSCMapEntry(XdrSCVal::forSymbol('extra'), XdrSCVal::forU32(42)),
                ]));
                $serverArgs = [$badMap];
                $clientArgs = [$badMap];
                break;
            case 'non_string_val_client':
                $badMap = XdrSCVal::forMap(array_merge($baseArgs->map, [
                    new XdrSCMapEntry(XdrSCVal::forSymbol('extra'), XdrSCVal::forU32(42)),
                ]));
                $clientArgs = [$badMap];
                break;
            case 'duplicate_known_key':
                $dupMap = $this->buildRawMap(array_merge($baseArgs->map, [
                    new XdrSCMapEntry(XdrSCVal::forSymbol('account'), XdrSCVal::forString($this->clientContractId)),
                ]));
                $serverArgs = [$dupMap];
                $clientArgs = [$dupMap];
                break;
            case 'duplicate_unknown_key_client':
                $dupMap = $this->buildRawMap(array_merge($baseArgs->map, [
                    new XdrSCMapEntry(XdrSCVal::forSymbol('extra'), XdrSCVal::forString('a')),
                    new XdrSCMapEntry(XdrSCVal::forSymbol('extra'), XdrSCVal::forString('b')),
                ]));
                $clientArgs = [$dupMap];
                break;
            case 'unknown_key_only_client':
                $clientMap = XdrSCVal::forMap(array_merge($baseArgs->map, [
                    new XdrSCMapEntry(XdrSCVal::forSymbol('extra'), XdrSCVal::forString('val')),
                ]));
                $clientArgs = [$clientMap];
                break;
            case 'unknown_key_only_server':
                $serverMap = XdrSCVal::forMap(array_merge($baseArgs->map, [
                    new XdrSCMapEntry(XdrSCVal::forSymbol('extra'), XdrSCVal::forString('val')),
                ]));
                $serverArgs = [$serverMap];
                break;
            case 'unknown_key_value_differs':
                $serverMap = XdrSCVal::forMap(array_merge($baseArgs->map, [
                    new XdrSCMapEntry(XdrSCVal::forSymbol('extra'), XdrSCVal::forString('val1')),
                ]));
                $clientMap = XdrSCVal::forMap(array_merge($baseArgs->map, [
                    new XdrSCMapEntry(XdrSCVal::forSymbol('extra'), XdrSCVal::forString('val2')),
                ]));
                $serverArgs = [$serverMap];
                $clientArgs = [$clientMap];
                break;
            case 'different_unknown_key_names':
                $serverMap = XdrSCVal::forMap(array_merge($baseArgs->map, [
                    new XdrSCMapEntry(XdrSCVal::forSymbol('extra_a'), XdrSCVal::forString('val')),
                ]));
                $clientMap = XdrSCVal::forMap(array_merge($baseArgs->map, [
                    new XdrSCMapEntry(XdrSCVal::forSymbol('extra_b'), XdrSCVal::forString('val')),
                ]));
                $serverArgs = [$serverMap];
                $clientArgs = [$clientMap];
                break;
            case 'home_domain_differs':
                $clientArgs = [$this->buildArgsMap(
                    $this->clientContractId, 'other.stellar.org', 'auth.example.stellar.org',
                    $this->serverAccountId, 'args-test-nonce'
                )];
                break;
            case 'empty_nonce':
                $emptyNonceMap = $this->buildArgsMap(
                    $this->clientContractId, $this->domain, 'auth.example.stellar.org',
                    $this->serverAccountId, ''
                );
                $serverArgs = [$emptyNonceMap];
                $clientArgs = [$emptyNonceMap];
                break;
            case 'missing_nonce':
                $noNonceMap = XdrSCVal::forMap([
                    new XdrSCMapEntry(XdrSCVal::forSymbol('account'), XdrSCVal::forString($this->clientContractId)),
                    new XdrSCMapEntry(XdrSCVal::forSymbol('home_domain'), XdrSCVal::forString($this->domain)),
                    new XdrSCMapEntry(XdrSCVal::forSymbol('web_auth_domain'), XdrSCVal::forString('auth.example.stellar.org')),
                    new XdrSCMapEntry(XdrSCVal::forSymbol('web_auth_domain_account'), XdrSCVal::forString($this->serverAccountId)),
                ]);
                $serverArgs = [$noNonceMap];
                $clientArgs = [$noNonceMap];
                break;
            case 'nonce_differs':
                $clientArgs = [$this->buildArgsMap(
                    $this->clientContractId, $this->domain, 'auth.example.stellar.org',
                    $this->serverAccountId, 'other-nonce'
                )];
                break;
            case 'missing_web_auth_domain_account':
                $noWebAuthDomainAccountMap = XdrSCVal::forMap([
                    new XdrSCMapEntry(XdrSCVal::forSymbol('account'), XdrSCVal::forString($this->clientContractId)),
                    new XdrSCMapEntry(XdrSCVal::forSymbol('home_domain'), XdrSCVal::forString($this->domain)),
                    new XdrSCMapEntry(XdrSCVal::forSymbol('web_auth_domain'), XdrSCVal::forString('auth.example.stellar.org')),
                    new XdrSCMapEntry(XdrSCVal::forSymbol('nonce'), XdrSCVal::forString('args-test-nonce')),
                ]);
                $serverArgs = [$noWebAuthDomainAccountMap];
                $clientArgs = [$noWebAuthDomainAccountMap];
                break;
            case 'wrong_web_auth_domain_account':
                $wrongWebAuthDomainAccountMap = $this->buildArgsMap(
                    $this->clientContractId,
                    $this->domain,
                    'auth.example.stellar.org',
                    KeyPair::random()->getAccountId(),
                    'args-test-nonce'
                );
                $serverArgs = [$wrongWebAuthDomainAccountMap];
                $clientArgs = [$wrongWebAuthDomainAccountMap];
                break;
        }

        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $baseArgs, 12345, 1000000
        );
        $serverEntry->rootInvocation->function->contractFn->args = $serverArgs;
        $serverEntry->sign($this->serverKeyPair, Network::testnet());

        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $baseArgs, 12346, 1000000
        );
        $clientEntry->rootInvocation->function->contractFn->args = $clientArgs;

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'authorization_entries' => $this->encodeAuthEntries([$serverEntry, $clientEntry]),
            ])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);

        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $error = null;
        try {
            $webAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain);
        } catch (ContractChallengeValidationError $e) {
            $error = $e;
        }

        $this->assertNotNull($error, "Case '$case' must throw $expectedExceptionClass");
        $this->assertSame($expectedExceptionClass, $error::class);
        $this->assertSame($expectedMessage, $error->getMessage());
        $this->assertCount(1, $mock, 'Token response must remain queued: no POST sent');
        $this->assertSame('GET', $mock->getLastRequest()->getMethod());
    }

    /**
     * Builds an XdrSCVal map without sorting or duplicate-key rejection, to model raw XDR over the wire.
     *
     * @param array<XdrSCMapEntry> $entries
     */
    private function buildRawMap(array $entries): XdrSCVal
    {
        $val = new XdrSCVal(XdrSCValType::MAP());
        $val->map = $entries;
        return $val;
    }

    public function testMatchingUnknownArgsDifferentMapOrderAndZeroNonceAccepted(): void
    {
        $serverMap = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forSymbol('account'), XdrSCVal::forString($this->clientContractId)),
            new XdrSCMapEntry(XdrSCVal::forSymbol('home_domain'), XdrSCVal::forString($this->domain)),
            new XdrSCMapEntry(XdrSCVal::forSymbol('web_auth_domain'), XdrSCVal::forString('auth.example.stellar.org')),
            new XdrSCMapEntry(XdrSCVal::forSymbol('web_auth_domain_account'), XdrSCVal::forString($this->serverAccountId)),
            new XdrSCMapEntry(XdrSCVal::forSymbol('nonce'), XdrSCVal::forString('0')),
            new XdrSCMapEntry(XdrSCVal::forSymbol('custom_tag'), XdrSCVal::forString('v1')),
        ]);
        $clientMap = $this->buildRawMap(array_reverse($serverMap->map));

        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $serverMap, 12345, 1000000
        );
        $serverEntry->sign($this->serverKeyPair, Network::testnet());
        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $clientMap, 12346, 1000000
        );

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'authorization_entries' => $this->encodeAuthEntries([$serverEntry, $clientEntry]),
            ])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);

        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $expectedServerEntryXdr = $serverEntry->toBase64Xdr();
        $clientSigner = KeyPair::random();
        $token = $webAuth->jwtToken($this->clientContractId, [$clientSigner], $this->domain);
        $this->assertSame($this->successJWTToken, $token);
        $this->assertCount(0, $mock);
        $postRequest = $mock->getLastRequest();
        $this->assertSame('POST', $postRequest->getMethod());
        parse_str((string) $postRequest->getBody(), $postBody);
        $submitted = $webAuth->decodeAuthorizationEntries($postBody['authorization_entries']);
        $this->assertCount(2, $submitted);
        $this->assertSame($expectedServerEntryXdr, $submitted[0]->toBase64Xdr());
        $clientCreds = $submitted[1]->credentials->getAddressCredentials();
        $this->assertSame(123466, $clientCreds->signatureExpirationLedger);
        $this->assertCount(1, $clientCreds->signature->vec);
        $this->assertTrue($clientSigner->verifySignature(
            $this->extractFirstSignatureBytes($clientCreds->signature),
            Hash::generate($submitted[1]->buildPreimage(Network::testnet())->encode())
        ));
    }

    /**
     * @return array<string, array{string, class-string<ContractChallengeValidationError>, string}>
     */
    public static function rejectedClientDomainChallengeCases(): array
    {
        return [
            'requested domain: only client_domain present' => [
                'requested_only_client_domain',
                ContractChallengeValidationErrorInvalidArgs::class,
                'Both client_domain and client_domain_account arguments must be present',
            ],
            'requested domain: only client_domain_account present' => [
                'requested_only_client_domain_account',
                ContractChallengeValidationErrorInvalidArgs::class,
                'Both client_domain and client_domain_account arguments must be present',
            ],
            'requested domain: client_domain mismatch' => [
                'requested_wrong_client_domain',
                ContractChallengeValidationErrorInvalidArgs::class,
                'Client domain argument does not match expected value',
            ],
            'requested domain: missing client domain entry' => [
                'requested_missing_client_domain_entry',
                ContractChallengeValidationErrorMissingClientEntry::class,
                'No authorization entry found for client domain account',
            ],
            'not requested: both client_domain and client_domain_account present' => [
                'unrequested_both',
                ContractChallengeValidationErrorInvalidArgs::class,
                'Challenge contains client domain arguments when no client domain was requested',
            ],
            'not requested: only client_domain present' => [
                'unrequested_only_client_domain',
                ContractChallengeValidationErrorInvalidArgs::class,
                'Challenge contains client domain arguments when no client domain was requested',
            ],
            'not requested: only client_domain_account present' => [
                'unrequested_only_client_domain_account',
                ContractChallengeValidationErrorInvalidArgs::class,
                'Challenge contains client domain arguments when no client domain was requested',
            ],
        ];
    }

    #[DataProvider('rejectedClientDomainChallengeCases')]
    public function testClientDomainChallengeRejectionsSendNoPost(
        string $case,
        string $expectedExceptionClass,
        string $expectedMessage
    ): void {
        $clientDomain = 'client.example.com';
        $clientDomainKeyPair = KeyPair::random();
        $clientDomainAccount = $clientDomainKeyPair->getAccountId();

        $argClientDomain = null;
        $argClientDomainAccount = null;
        $includeClientDomainEntry = false;
        $requestDomain = str_starts_with($case, 'requested_');

        switch ($case) {
            case 'requested_only_client_domain':
                $argClientDomain = $clientDomain;
                $includeClientDomainEntry = true;
                break;
            case 'requested_only_client_domain_account':
                $argClientDomainAccount = $clientDomainAccount;
                $includeClientDomainEntry = true;
                break;
            case 'requested_wrong_client_domain':
                $argClientDomain = 'evil.example.com';
                $argClientDomainAccount = $clientDomainAccount;
                $includeClientDomainEntry = true;
                break;
            case 'requested_missing_client_domain_entry':
                $argClientDomain = $clientDomain;
                $argClientDomainAccount = $clientDomainAccount;
                $includeClientDomainEntry = false;
                break;
            case 'unrequested_both':
                $argClientDomain = $clientDomain;
                $argClientDomainAccount = $clientDomainAccount;
                $includeClientDomainEntry = true;
                break;
            case 'unrequested_only_client_domain':
                $argClientDomain = $clientDomain;
                break;
            case 'unrequested_only_client_domain_account':
                $argClientDomainAccount = $clientDomainAccount;
                break;
        }

        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'cd-reject-nonce',
            $argClientDomain,
            $argClientDomainAccount
        );

        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12345, 1000000
        );
        $serverEntry->sign($this->serverKeyPair, Network::testnet());
        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12346, 1000000
        );
        $entries = [$serverEntry, $clientEntry];
        if ($includeClientDomainEntry) {
            $entries[] = $this->buildAuthEntry(
                $clientDomainAccount, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12347, 1000000
            );
        }

        $mock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $this->encodeAuthEntries($entries)])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);

        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $error = null;
        try {
            $webAuth->jwtToken(
                $this->clientContractId,
                [KeyPair::random()],
                $this->domain,
                $requestDomain ? $clientDomain : null,
                $requestDomain ? $clientDomainKeyPair : null
            );
        } catch (ContractChallengeValidationError $e) {
            $error = $e;
        }

        $this->assertNotNull($error, "Case '$case' must throw $expectedExceptionClass");
        $this->assertSame($expectedExceptionClass, $error::class);
        $this->assertSame($expectedMessage, $error->getMessage());
        $this->assertCount(1, $mock, 'Token response must remain queued: no POST sent');
        $this->assertSame('GET', $mock->getLastRequest()->getMethod());
    }

    public function testClientDomainIgnoredByServerSkipsTomlCallbackAndDomainSigning(): void
    {
        $clientDomain = 'unavailable-client.example.com';
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'ignored-cd-nonce'
        );

        $requests = [];
        $tomlRequested = false;
        $handler = function (RequestInterface $request) use ($challengeXdr, &$requests, &$tomlRequested): PromiseInterface {
            $uri = (string) $request->getUri();
            $requests[] = ['method' => $request->getMethod(), 'uri' => $uri, 'body' => (string) $request->getBody()];
            if (str_contains($uri, 'stellar.toml')) {
                $tomlRequested = true;
                return Create::promiseFor(new Response(500, [], 'TOML unavailable'));
            }
            if ($request->getMethod() === 'GET') {
                return Create::promiseFor(new Response(200, [], json_encode([
                    'authorization_entries' => $challengeXdr,
                    'network_passphrase' => 'Test SDF Network ; September 2015',
                ])));
            }
            return Create::promiseFor(new Response(200, [], json_encode(['token' => $this->successJWTToken])));
        };

        $httpClient = new Client(['handler' => HandlerStack::create($handler)]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $httpClient
        );
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $callbackInvoked = false;
        $callback = function (SorobanAuthorizationEntry $entry) use (&$callbackInvoked): SorobanAuthorizationEntry {
            $callbackInvoked = true;
            return $entry;
        };

        $token = $webAuth->jwtToken(
            $this->clientContractId,
            [KeyPair::random()],
            $this->domain,
            $clientDomain,
            null,
            $callback
        );

        $this->assertSame($this->successJWTToken, $token);
        $this->assertFalse($tomlRequested, 'Client domain stellar.toml must not be requested when server ignored client_domain');
        $this->assertFalse($callbackInvoked, 'Client domain callback must not be invoked when server ignored client_domain');
        $this->assertCount(2, $requests);
        $this->assertSame('GET', $requests[0]['method']);
        $this->assertStringContainsString('client_domain=unavailable-client.example.com', $requests[0]['uri']);
        $this->assertSame('POST', $requests[1]['method']);
        parse_str($requests[1]['body'], $body);
        $submitted = $webAuth->decodeAuthorizationEntries($body['authorization_entries']);
        $this->assertCount(2, $submitted);
    }

    public function testClientDomainIgnoredDecidedFromArgsPairEvenIfExtraEntryExists(): void
    {
        $clientDomain = 'client.example.com';
        $clientDomainKeyPair = KeyPair::random();
        $argsMap = $this->buildArgsMap(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId, 'extra-entry-nonce'
        );
        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12345, 1000000
        );
        $serverEntry->sign($this->serverKeyPair, Network::testnet());
        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12346, 1000000
        );
        $extraDomainEntry = $this->buildAuthEntry(
            $clientDomainKeyPair->getAccountId(), $this->webAuthContractId, 'web_auth_verify', $argsMap, 12347, 1000000
        );

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'authorization_entries' => $this->encodeAuthEntries([$serverEntry, $clientEntry, $extraDomainEntry]),
            ])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);

        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $token = $webAuth->jwtToken(
            $this->clientContractId, [KeyPair::random()], $this->domain, $clientDomain, $clientDomainKeyPair
        );

        $this->assertSame($this->successJWTToken, $token);
        parse_str((string) $mock->getLastRequest()->getBody(), $body);
        $submitted = $webAuth->decodeAuthorizationEntries($body['authorization_entries']);
        $this->assertCount(3, $submitted);
        $this->assertSame(
            XdrSCValType::SCV_VOID,
            $submitted[2]->credentials->getAddressCredentials()->signature->type->value,
            'Client domain entry must remain unsigned when args omit both client_domain keys'
        );
    }

    public function testSameServerAndClientDomainKeySupportedWithLocalKeyPairAndCallback(): void
    {
        $clientDomain = 'same-key.example.com';
        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'same-key-nonce',
            $clientDomain,
            $this->serverAccountId
        );
        // Single entry serves both server and client domain roles
        $sharedEntry = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12345, 1000000
        );
        $sharedEntry->sign($this->serverKeyPair, Network::testnet());
        $expectedSharedEntryXdr = $sharedEntry->toBase64Xdr();

        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12346, 1000000
        );
        $challengeXdr = $this->encodeAuthEntries([$sharedEntry, $clientEntry]);

        // Case 1: Local key pair identical to server key
        $mockKeyPair = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mockKeyPair);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $token = $webAuth->jwtToken(
            $this->clientContractId, [KeyPair::random()], $this->domain, $clientDomain, $this->serverKeyPair
        );
        $this->assertSame($this->successJWTToken, $token);
        $this->assertCount(0, $mockKeyPair);
        parse_str((string) $mockKeyPair->getLastRequest()->getBody(), $body);
        $submitted = $webAuth->decodeAuthorizationEntries($body['authorization_entries']);
        $this->assertCount(2, $submitted);
        $this->assertSame($expectedSharedEntryXdr, $submitted[0]->toBase64Xdr());
        $this->assertCount(1, $submitted[0]->credentials->getAddressCredentials()->signature->vec);
        $this->assertSame(1000000, $submitted[0]->credentials->getAddressCredentials()->signatureExpirationLedger);
        $this->assertCount(1, $submitted[1]->credentials->getAddressCredentials()->signature->vec);

        // Case 2: Callback with stellar.toml advertising the server signing key
        $mockCallback = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(200, [], 'SIGNING_KEY = "' . $this->serverAccountId . '"'),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth->setMockHandler($mockCallback);
        $callbackInvoked = false;
        $callback = function (SorobanAuthorizationEntry $entry) use (&$callbackInvoked): SorobanAuthorizationEntry {
            $callbackInvoked = true;
            return $entry;
        };

        $tokenCb = $webAuth->jwtToken(
            $this->clientContractId, [KeyPair::random()], $this->domain, $clientDomain, null, $callback
        );
        $this->assertSame($this->successJWTToken, $tokenCb);
        $this->assertFalse($callbackInvoked, 'Existing valid server signature must be preserved without calling callback');
        $this->assertCount(0, $mockCallback);
        parse_str((string) $mockCallback->getLastRequest()->getBody(), $bodyCb);
        $submittedCb = $webAuth->decodeAuthorizationEntries($bodyCb['authorization_entries']);
        $this->assertCount(2, $submittedCb);
        $this->assertSame($expectedSharedEntryXdr, $submittedCb[0]->toBase64Xdr());
        $this->assertCount(1, $submittedCb[0]->credentials->getAddressCredentials()->signature->vec);
    }

    public function testSameServerAndClientDomainKeyIgnoredDomainPreservesSignature(): void
    {
        $clientDomain = 'same-key-ignored.example.com';
        $argsMap = $this->buildArgsMap(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId, 'same-key-ign-nonce'
        );
        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12345, 1000000
        );
        $serverEntry->sign($this->serverKeyPair, Network::testnet());
        $expectedServerEntryXdr = $serverEntry->toBase64Xdr();

        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12346, 1000000
        );
        $challengeXdr = $this->encodeAuthEntries([$serverEntry, $clientEntry]);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $token = $webAuth->jwtToken(
            $this->clientContractId, [KeyPair::random()], $this->domain, $clientDomain, $this->serverKeyPair
        );
        $this->assertSame($this->successJWTToken, $token);
        $this->assertCount(0, $mock);
        parse_str((string) $mock->getLastRequest()->getBody(), $body);
        $submitted = $webAuth->decodeAuthorizationEntries($body['authorization_entries']);
        $this->assertCount(2, $submitted);
        $this->assertSame($expectedServerEntryXdr, $submitted[0]->toBase64Xdr());
        $this->assertCount(1, $submitted[0]->credentials->getAddressCredentials()->signature->vec);
        $this->assertSame(1000000, $submitted[0]->credentials->getAddressCredentials()->signatureExpirationLedger);
    }

    public function testClientDomainWithoutKeyPairOrCallbackThrowsBeforeGet(): void
    {
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );

        foreach (['client.example.com', '0'] as $clientDomain) {
            $mock = new MockHandler([
                new Response(200, [], json_encode(['authorization_entries' => 'AAAAAA=='])),
            ]);
            $webAuth->setMockHandler($mock);

            $error = null;
            try {
                $webAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain, $clientDomain);
            } catch (InvalidArgumentException $e) {
                $error = $e;
            }

            $this->assertNotNull($error, "Client domain '$clientDomain' without signing means must throw");
            $this->assertSame('Client domain key pair or client domain signing callback is missing', $error->getMessage());
            $this->assertCount(1, $mock, 'Challenge GET must not be sent when signing options are missing');
            $this->assertNull($mock->getLastRequest());
        }
    }

    public function testValidateChallengeLegacyAndTrailingClientDomainParameter(): void
    {
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $cdKeyPair = KeyPair::random();
        $cdAccountId = $cdKeyPair->getAccountId();

        $entriesWithDomain = $webAuth->decodeAuthorizationEntries($this->buildValidChallenge(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org',
            $this->serverAccountId, 'legacy-cd-nonce', 'any.client.domain', $cdAccountId
        ));

        // Legacy 4-argument caller ($clientDomain null): checks account, ignores client_domain value
        $webAuth->validateChallenge($entriesWithDomain, $this->clientContractId, $this->domain, $cdAccountId);

        // 5-argument caller with matching clientDomain: accepted
        $webAuth->validateChallenge(
            $entriesWithDomain, $this->clientContractId, $this->domain, $cdAccountId, 'any.client.domain'
        );

        // 5-argument caller with mismatched clientDomain: rejected
        try {
            $webAuth->validateChallenge(
                $entriesWithDomain, $this->clientContractId, $this->domain, $cdAccountId, 'expected.client.domain'
            );
            $this->fail('Expected ContractChallengeValidationErrorInvalidArgs for client_domain mismatch');
        } catch (ContractChallengeValidationErrorInvalidArgs $e) {
            $this->assertSame(
                'Client domain argument does not match expected value',
                $e->getMessage()
            );
        }

        // Legacy 4-argument caller with wrong account: rejected
        $otherAccount = KeyPair::random()->getAccountId();
        try {
            $webAuth->validateChallenge($entriesWithDomain, $this->clientContractId, $this->domain, $otherAccount);
            $this->fail('Expected ContractChallengeValidationErrorInvalidArgs for client_domain_account mismatch');
        } catch (ContractChallengeValidationErrorInvalidArgs $e) {
            $this->assertSame(
                'Client domain account does not match expected value',
                $e->getMessage()
            );
        }

        // Legacy 4-argument caller with client_domain_account only (missing client_domain): rejected
        $entriesPartialLegacy = $webAuth->decodeAuthorizationEntries($this->buildValidChallenge(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org',
            $this->serverAccountId, 'legacy-partial-nonce', null, $cdAccountId
        ));
        try {
            $webAuth->validateChallenge($entriesPartialLegacy, $this->clientContractId, $this->domain, $cdAccountId);
            $this->fail('Expected ContractChallengeValidationErrorInvalidArgs when client_domain is absent');
        } catch (ContractChallengeValidationErrorInvalidArgs $e) {
            $this->assertSame(
                'Both client_domain and client_domain_account arguments must be present',
                $e->getMessage()
            );
        }

        // 5-argument caller with clientDomain set and clientDomainAccountId null when challenge has client domain args: rejected
        try {
            $webAuth->validateChallenge(
                $entriesWithDomain, $this->clientContractId, $this->domain, null, 'any.client.domain'
            );
            $this->fail('Expected InvalidArgumentException when clientDomainAccountId is null');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                'clientDomainAccountId is required when validating a challenge with client domain arguments',
                $e->getMessage()
            );
        }

        // Server ignored domain (neither key in args): accepted even when clientDomain/clientDomainAccountId are passed
        $entriesWithoutDomain = $webAuth->decodeAuthorizationEntries($this->buildValidChallenge(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId, 'ignored-nonce'
        ));
        $webAuth->validateChallenge(
            $entriesWithoutDomain, $this->clientContractId, $this->domain, $cdAccountId, 'any.client.domain'
        );
        $webAuth->validateChallenge(
            $entriesWithoutDomain, $this->clientContractId, $this->domain, null, 'any.client.domain'
        );
        $this->assertCount(2, $entriesWithoutDomain);
    }

    public function testSameServerAndClientDomainKeyIgnoredDomainWithCallbackAndUnavailableToml(): void
    {
        $clientDomain = 'same-key-ignored-cb.example.com';
        $argsMap = $this->buildArgsMap(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId, 'same-key-ign-cb-nonce'
        );
        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12345, 1000000
        );
        $serverEntry->sign($this->serverKeyPair, Network::testnet());
        $expectedServerEntryXdr = $serverEntry->toBase64Xdr();

        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12346, 1000000
        );
        $challengeXdr = $this->encodeAuthEntries([$serverEntry, $clientEntry]);

        $tomlRequested = false;
        $postCount = 0;
        $postBody = null;
        $handler = function (RequestInterface $request) use ($challengeXdr, &$tomlRequested, &$postCount, &$postBody): PromiseInterface {
            $uri = (string) $request->getUri();
            if (str_contains($uri, 'stellar.toml')) {
                $tomlRequested = true;
                return Create::promiseFor(new Response(500, [], 'TOML unavailable'));
            }
            if ($request->getMethod() === 'GET') {
                return Create::promiseFor(new Response(200, [], json_encode([
                    'authorization_entries' => $challengeXdr,
                ])));
            }
            $postCount++;
            $postBody = (string) $request->getBody();
            return Create::promiseFor(new Response(200, [], json_encode(['token' => $this->successJWTToken])));
        };

        $httpClient = new Client(['handler' => HandlerStack::create($handler)]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $httpClient
        );
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $callbackInvoked = false;
        $callback = function (SorobanAuthorizationEntry $entry) use (&$callbackInvoked): SorobanAuthorizationEntry {
            $callbackInvoked = true;
            return $entry;
        };

        $token = $webAuth->jwtToken(
            $this->clientContractId, [KeyPair::random()], $this->domain, $clientDomain, null, $callback
        );

        $this->assertSame($this->successJWTToken, $token);
        $this->assertFalse($tomlRequested);
        $this->assertFalse($callbackInvoked);
        $this->assertSame(1, $postCount);
        $this->assertNotNull($postBody);
        parse_str($postBody, $body);
        $submitted = $webAuth->decodeAuthorizationEntries($body['authorization_entries']);
        $this->assertCount(2, $submitted);
        $this->assertSame($expectedServerEntryXdr, $submitted[0]->toBase64Xdr());
        $this->assertCount(1, $submitted[0]->credentials->getAddressCredentials()->signature->vec);
        $this->assertSame(1000000, $submitted[0]->credentials->getAddressCredentials()->signatureExpirationLedger);
        $this->assertCount(1, $submitted[1]->credentials->getAddressCredentials()->signature->vec);
    }

    public function testClientDomainAccountMismatchSendsNoPost(): void
    {
        $clientDomain = 'client.example.com';
        $expectedKeyPair = KeyPair::random();
        $wrongAccount = KeyPair::random()->getAccountId();

        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'cd-acct-mismatch-nonce',
            $clientDomain,
            $wrongAccount
        );

        $mock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $error = null;
        try {
            $webAuth->jwtToken(
                $this->clientContractId, [KeyPair::random()], $this->domain, $clientDomain, $expectedKeyPair
            );
        } catch (ContractChallengeValidationErrorInvalidArgs $e) {
            $error = $e;
        }

        $this->assertNotNull($error);
        $this->assertSame(
            'Client domain account does not match expected value',
            $error->getMessage()
        );
        $this->assertCount(1, $mock, 'Token response must remain queued: no POST sent');
        $this->assertSame('GET', $mock->getLastRequest()->getMethod());
    }

    public function testClientDomainOnlyOnSecondEntryRejectedWithoutTomlOrPost(): void
    {
        $clientDomain = 'client.example.com';
        $cdKeyPair = KeyPair::random();
        $serverMap = $this->buildArgsMap(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId, 'split-cd-nonce'
        );
        $clientMap = $this->buildArgsMap(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId, 'split-cd-nonce',
            $clientDomain, $cdKeyPair->getAccountId()
        );
        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $serverMap, 12345, 1000000
        );
        $serverEntry->sign($this->serverKeyPair, Network::testnet());
        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $clientMap, 12346, 1000000
        );

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'authorization_entries' => $this->encodeAuthEntries([$serverEntry, $clientEntry]),
            ])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $error = null;
        try {
            $webAuth->jwtToken(
                $this->clientContractId, [KeyPair::random()], $this->domain, $clientDomain, null,
                static fn(SorobanAuthorizationEntry $entry): SorobanAuthorizationEntry => $entry
            );
        } catch (ContractChallengeValidationErrorInvalidArgs $e) {
            $error = $e;
        }

        $this->assertNotNull($error);
        $this->assertSame('Arguments are not consistent across authorization entries', $error->getMessage());
        $this->assertCount(1, $mock, 'Token response must remain queued: no TOML request and no POST sent');
        $this->assertSame('GET', $mock->getLastRequest()->getMethod());
    }

    public function testEmptyChallengeWithRequestedClientDomainRejectsWithoutTomlOrPost(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => base64_encode(pack('N', 0))])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);

        $error = null;
        try {
            $webAuth->jwtToken(
                $this->clientContractId, [KeyPair::random()], $this->domain, 'client.example.com', null,
                static fn(SorobanAuthorizationEntry $entry): SorobanAuthorizationEntry => $entry
            );
        } catch (ContractChallengeValidationError $e) {
            $error = $e;
        }

        $this->assertNotNull($error);
        $this->assertSame(ContractChallengeValidationError::class, $error::class);
        $this->assertSame('No authorization entries found', $error->getMessage());
        $this->assertCount(1, $mock, 'No TOML request and no POST sent');
        $this->assertSame('GET', $mock->getLastRequest()->getMethod());
    }

    public function testMalformedArgsWithRequestedClientDomainRejectsWithoutTomlOrPost(): void
    {
        $baseArgs = $this->buildArgsMap(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId, 'malformed-cd-nonce'
        );
        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $baseArgs, 12345, 1000000
        );
        $serverEntry->rootInvocation->function->contractFn->args = [];
        $serverEntry->sign($this->serverKeyPair, Network::testnet());

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'authorization_entries' => $this->encodeAuthEntries([$serverEntry]),
            ])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);

        $error = null;
        try {
            $webAuth->jwtToken(
                $this->clientContractId, [KeyPair::random()], $this->domain, 'client.example.com', null,
                static fn(SorobanAuthorizationEntry $entry): SorobanAuthorizationEntry => $entry
            );
        } catch (ContractChallengeValidationErrorInvalidArgs $e) {
            $error = $e;
        }

        $this->assertNotNull($error);
        $this->assertSame('Expected exactly one argument, got 0', $error->getMessage());
        $this->assertCount(1, $mock, 'No TOML request and no POST sent');
        $this->assertSame('GET', $mock->getLastRequest()->getMethod());
    }

    public function testMalformedInMemoryArgsRejectedByValidateChallenge(): void
    {
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $baseArgs = $this->buildArgsMap(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId, 'inmem-nonce'
        );

        // Non-XdrSCMapEntry element in map
        $entryBadMapMember = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $baseArgs, 12345, 1000000
        );
        $badMap = new XdrSCVal(XdrSCValType::MAP());
        $badMap->map = ['not-an-entry'];
        $entryBadMapMember->rootInvocation->function->contractFn->args = [$badMap];

        try {
            $webAuth->validateChallenge([$entryBadMapMember], $this->clientContractId, $this->domain);
            $this->fail('Expected ContractChallengeValidationErrorInvalidArgs for non-XdrSCMapEntry');
        } catch (ContractChallengeValidationErrorInvalidArgs $e) {
            $this->assertSame('Argument map entry is malformed', $e->getMessage());
        }

        // Non-XdrSCVal element in args
        $entryBadArgVal = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $baseArgs, 12345, 1000000
        );
        $entryBadArgVal->rootInvocation->function->contractFn->args = ['not-scval'];

        try {
            $webAuth->validateChallenge([$entryBadArgVal], $this->clientContractId, $this->domain);
            $this->fail('Expected ContractChallengeValidationErrorInvalidArgs for non-XdrSCVal');
        } catch (ContractChallengeValidationErrorInvalidArgs $e) {
            $this->assertSame('Arguments are not in map format', $e->getMessage());
        }
    }

    public function testDistinctPreSignedClientDomainEntryRunsCallbackAndLocalKeyPairPath(): void
    {
        $clientDomainKeyPair = KeyPair::random();
        $clientDomainAccount = $clientDomainKeyPair->getAccountId();
        $coSigner = KeyPair::random();

        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'distinct-presigned-cd-nonce',
            'client.example.com',
            $clientDomainAccount
        );

        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12345, 1000000
        );
        $serverEntry->sign($this->serverKeyPair, Network::testnet());

        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12346, 1000000
        );

        $clientDomainEntry = $this->buildAuthEntry(
            $clientDomainAccount, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12347, 1000000
        );
        // Pre-sign with the distinct client domain account's own key.
        $clientDomainEntry->sign($clientDomainKeyPair, Network::testnet());
        $preSignedClientDomainXdr = $clientDomainEntry->toBase64Xdr();

        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );

        $callbackCalls = 0;
        $returnedSignatureXdr = null;
        $callback = function (SorobanAuthorizationEntry $entry) use ($coSigner, &$callbackCalls, &$returnedSignatureXdr): SorobanAuthorizationEntry {
            $callbackCalls++;
            $creds = $entry->credentials->getAddressCredentials();
            $this->assertSame(1000000, $creds->signatureExpirationLedger);
            $this->assertCount(1, $creds->signature->vec);
            // Add another signer without changing the expiration ledger.
            $entry->sign($coSigner, Network::testnet());
            $returnedSignatureXdr = $entry->credentials->getAddressCredentials()->signature->toBase64Xdr();
            return $entry;
        };

        $signed = $webAuth->signAuthorizationEntries(
            [$serverEntry, $clientEntry, $clientDomainEntry],
            $this->clientContractId,
            [],
            null,
            null,
            $callback,
            $clientDomainAccount
        );

        $this->assertSame(1, $callbackCalls);
        $this->assertNotNull($returnedSignatureXdr);
        $this->assertCount(3, $signed);

        $returnedCreds = $signed[2]->credentials->getAddressCredentials();
        $this->assertSame(1000000, $returnedCreds->signatureExpirationLedger);
        $this->assertSame($returnedSignatureXdr, $returnedCreds->signature->toBase64Xdr());
        $this->assertCount(2, $returnedCreds->signature->vec);

        $payload = Hash::generate($signed[2]->buildPreimage(Network::testnet())->encode());
        $expectedSigners = [$clientDomainKeyPair, $coSigner];
        foreach ($expectedSigners as $idx => $expectedSigner) {
            $publicKey = null;
            $signature = null;
            foreach ($returnedCreds->signature->vec[$idx]->map ?? [] as $mapEntry) {
                if ($mapEntry->key->sym === 'public_key') {
                    $publicKey = $mapEntry->val->bytes?->value;
                } elseif ($mapEntry->key->sym === 'signature') {
                    $signature = $mapEntry->val->bytes?->value;
                }
            }
            $this->assertSame($expectedSigner->getPublicKey(), $publicKey);
            $this->assertNotNull($signature);
            $this->assertTrue($expectedSigner->verifySignature($signature, $payload));
        }

        // Local keypair path must also not short-circuit for a distinct pre-signed client domain entry.
        $localEntry = SorobanAuthorizationEntry::fromBase64Xdr($preSignedClientDomainXdr);
        try {
            $webAuth->signAuthorizationEntries(
                [$localEntry],
                $this->clientContractId,
                [],
                1000001,
                $clientDomainKeyPair
            );
            $this->fail('Expected InvalidArgumentException when distinct pre-signed client domain entry is re-signed with conflicting expiration');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                'Signature expiration ledger 1000001 conflicts with stored ledger 1000000',
                $e->getMessage()
            );
        }
    }

    /**
     * @return array<string, array{int, string, string}>
     */
    public static function rejectedChallengeGetStatuses(): array
    {
        return [
            '201 with JSON body' => [
                201,
                '{"authorization_entries":"AAAAAA=="}',
                'Unexpected challenge response status: 201',
            ],
            '202 with JSON body' => [
                202,
                '{"authorization_entries":"AAAAAA=="}',
                'Unexpected challenge response status: 202',
            ],
            '204 empty body' => [
                204,
                '',
                'Unexpected challenge response status: 204',
            ],
        ];
    }

    #[DataProvider('rejectedChallengeGetStatuses')]
    public function testChallengeGetNon200StatusesRejectedWithoutPost(
        int $statusCode,
        string $body,
        string $expectedMessage
    ): void {
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'non-200-get-nonce'
        );
        $actualBody = str_replace('AAAAAA==', $challengeXdr, $body);

        $mock = new MockHandler([
            new Response($statusCode, [], $actualBody),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $sorobanServer = $this->createMock(SorobanServer::class);
        $sorobanServer->expects($this->never())->method('getLatestLedger');
        $sorobanServer->expects($this->never())->method('simulateTransaction');

        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($sorobanServer);

        $error = null;
        try {
            $webAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain);
        } catch (ContractChallengeRequestErrorResponse $e) {
            $error = $e;
        }

        $this->assertNotNull($error, "GET status $statusCode must throw ContractChallengeRequestErrorResponse");
        $this->assertSame($statusCode, $error->getHttpStatusCode());
        $this->assertSame($expectedMessage, $error->getMessage());
        if ($actualBody !== '') {
            $this->assertStringNotContainsString($actualBody, $error->getMessage());
        }
        $this->assertCount(1, $mock, 'Token response must remain queued: no POST sent');
        $this->assertSame('GET', $mock->getLastRequest()->getMethod());
    }

    public function testContractChallengeRequestBuilderRejects201Status(): void
    {
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'builder-201-nonce'
        );
        $body = json_encode(['authorization_entries' => $challengeXdr]);
        $response = new Response(201, [], $body);
        $httpClient = new Client(['handler' => HandlerStack::create(new MockHandler([$response]))]);

        $builder = (new ContractChallengeRequestBuilder($this->authServer, $httpClient))
            ->forAccountId($this->clientContractId)
            ->forHomeDomain($this->domain);

        try {
            $builder->execute();
            $this->fail('Expected HorizonRequestException for HTTP 201');
        } catch (HorizonRequestException $e) {
            $this->assertSame(201, $e->getStatusCode());
            $this->assertSame('Unexpected challenge response status: 201', $e->getMessage());
            $this->assertStringNotContainsString($body, $e->getMessage());
            $this->assertSame($response, $e->getHttpResponse());
        }
    }

    public function testTokenPost201WithTokenRejected(): void
    {
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'post-201-nonce'
        );
        $tokenBody = json_encode(['token' => $this->successJWTToken]);
        $mock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(201, [], $tokenBody),
        ]);

        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        try {
            $webAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain);
            $this->fail('Expected SubmitContractChallengeUnknownResponseException for HTTP 201');
        } catch (SubmitContractChallengeUnknownResponseException $e) {
            $this->assertSame(201, $e->getHttpStatusCode());
            $this->assertSame('Unknown response from server (HTTP 201)', $e->getMessage());
            $this->assertStringNotContainsString($this->successJWTToken, $e->getMessage());
            $this->assertStringNotContainsString($tokenBody, $e->getMessage());
            $this->assertSame($tokenBody, $e->getResponseBody());
            $this->assertStringContainsString($this->successJWTToken, $e->getResponseBody());
        }
        $this->assertCount(0, $mock);
        $this->assertSame('POST', $mock->getLastRequest()->getMethod());
    }

    /**
     * @return array<string, array{int, string, string}>
     */
    public static function tokenPost400ErrorCases(): array
    {
        return [
            '400 with token and no error' => [400, '{"token":"leaked-token"}', 'An unknown error occurred'],
            '400 with token and non-string error' => [400, '{"token":"leaked-token","error":5}', 'An unknown error occurred'],
            '400 with error string' => [400, '{"error":"Invalid signature"}', 'Invalid signature'],
            '400 with token and error string' => [400, '{"token":"leaked-token","error":"Challenge expired"}', 'Challenge expired'],
            '400 with zero string error' => [400, '{"error":"0"}', '0'],
            '400 with empty error string' => [400, '{"error":""}', 'An unknown error occurred'],
            '200 with error string' => [200, '{"error":"Challenge invalid"}', 'Challenge invalid'],
            '200 with empty token string' => [200, '{"token":""}', 'An unknown error occurred'],
            '200 with zero string error' => [200, '{"error":"0"}', '0'],
            '200 with token and zero string error' => [200, '{"token":"usable-jwt-token","error":"0"}', '0'],
        ];
    }

    #[DataProvider('tokenPost400ErrorCases')]
    public function testTokenPostErrorAnd400WithTokenRejected(
        int $statusCode,
        string $body,
        string $expectedMessage
    ): void {
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'post-400-nonce'
        );
        $mock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response($statusCode, [], $body),
        ]);

        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        try {
            $webAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain);
            $this->fail('Expected SubmitContractChallengeErrorResponseException');
        } catch (SubmitContractChallengeErrorResponseException $e) {
            $this->assertSame($expectedMessage, $e->getMessage());
        }
        $this->assertCount(0, $mock);
        $this->assertSame('POST', $mock->getLastRequest()->getMethod());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function tokenPost200WithFalsyErrorCases(): array
    {
        return [
            '200 with token and empty error string' => ['{"token":"usable-jwt-token","error":""}', 'usable-jwt-token'],
            '200 with zero string token' => ['{"token":"0"}', '0'],
            '200 with zero string token and empty error string' => ['{"token":"0","error":""}', '0'],
        ];
    }

    #[DataProvider('tokenPost200WithFalsyErrorCases')]
    public function testTokenPost200ReturnsTokenWithFalsyErrorString(string $body, string $expectedToken): void
    {
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler(new MockHandler([new Response(200, [], $body)]));
        $this->assertSame($expectedToken, $webAuth->sendSignedChallenge([]));
    }

    public function testTokenPost500ThrowsUnknownResponseException(): void
    {
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler(new MockHandler([new Response(500, [], 'Internal Server Error')]));

        try {
            $webAuth->sendSignedChallenge([]);
            $this->fail('Expected SubmitContractChallengeUnknownResponseException');
        } catch (SubmitContractChallengeUnknownResponseException $e) {
            $this->assertSame(500, $e->getHttpStatusCode());
            $this->assertSame('Unknown response from server (HTTP 500)', $e->getMessage());
            $this->assertStringNotContainsString('Internal Server Error', $e->getMessage());
            $this->assertSame('Internal Server Error', $e->getResponseBody());
        }
    }

    public function testAuthEndpointWithQueryMergesGetParamsAndKeepsPostUnchanged(): void
    {
        $endpointWithQuery = 'https://auth.example.stellar.org/sep45?tenant=acme&tag=a%20b&a.b=1%2B2';
        $homeDomainWithPort = 'example.stellar.org:8080';
        $clientDomain = 'wallet.example.com';
        $clientDomainKeyPair = KeyPair::random();

        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $homeDomainWithPort,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'query-endpoint-nonce',
            $clientDomain,
            $clientDomainKeyPair->getAccountId()
        );

        $requests = [];
        $handler = function (RequestInterface $request) use ($challengeXdr, &$requests): PromiseInterface {
            $requests[] = ['method' => $request->getMethod(), 'uri' => (string) $request->getUri()];
            if ($request->getMethod() === 'GET') {
                return Create::promiseFor(new Response(200, [], json_encode([
                    'authorization_entries' => $challengeXdr,
                    'network_passphrase' => 'Test SDF Network ; September 2015',
                ])));
            }
            return Create::promiseFor(new Response(200, [], json_encode(['token' => $this->successJWTToken])));
        };

        $httpClient = new Client(['handler' => HandlerStack::create($handler)]);
        $webAuth = new WebAuthForContracts(
            $endpointWithQuery, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $httpClient
        );
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $token = $webAuth->jwtToken(
            $this->clientContractId,
            [KeyPair::random()],
            $homeDomainWithPort,
            $clientDomain,
            $clientDomainKeyPair
        );

        $this->assertSame($this->successJWTToken, $token);
        $this->assertCount(2, $requests);
        $this->assertSame('GET', $requests[0]['method']);
        $this->assertSame(
            'https://auth.example.stellar.org/sep45?tenant=acme&tag=a%20b&a.b=1%2B2'
            . '&account=' . $this->clientContractId
            . '&home_domain=example.stellar.org%3A8080'
            . '&client_domain=wallet.example.com',
            $requests[0]['uri']
        );
        $this->assertSame('POST', $requests[1]['method']);
        $this->assertSame($endpointWithQuery, $requests[1]['uri']);

        // Unrelated query fields without client_domain are preserved in GET and kept unchanged on POST
        $unrelatedQueryEndpoint = 'https://auth.example.stellar.org/sep45?tenant=acme&env=staging';
        $unrelatedChallengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'unrelated-query-nonce'
        );
        $unrelatedRequests = [];
        $unrelatedHandler = function (RequestInterface $request) use ($unrelatedChallengeXdr, &$unrelatedRequests): PromiseInterface {
            $unrelatedRequests[] = ['method' => $request->getMethod(), 'uri' => (string) $request->getUri()];
            if ($request->getMethod() === 'GET') {
                return Create::promiseFor(new Response(200, [], json_encode(['authorization_entries' => $unrelatedChallengeXdr])));
            }
            return Create::promiseFor(new Response(200, [], json_encode(['token' => $this->successJWTToken])));
        };
        $unrelatedWebAuth = new WebAuthForContracts(
            $unrelatedQueryEndpoint,
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet(),
            new Client(['handler' => HandlerStack::create($unrelatedHandler)])
        );
        $unrelatedWebAuth->setSorobanServer($this->createMockSorobanServer());

        $this->assertSame(
            $this->successJWTToken,
            $unrelatedWebAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain)
        );
        $this->assertCount(2, $unrelatedRequests);
        $this->assertSame('GET', $unrelatedRequests[0]['method']);
        $this->assertSame(
            'https://auth.example.stellar.org/sep45?tenant=acme&env=staging'
            . '&account=' . $this->clientContractId
            . '&home_domain=' . $this->domain,
            $unrelatedRequests[0]['uri']
        );
        $this->assertSame('POST', $unrelatedRequests[1]['method']);
        $this->assertSame($unrelatedQueryEndpoint, $unrelatedRequests[1]['uri']);

        // Direct ContractChallengeRequestBuilder edge cases: non-reserved parameter override, trailing &,
        // empty ?, fragment, numeric keys, literal +, null parameter, array parameter
        $builderOverride = (new ContractChallengeRequestBuilder(
            'https://auth.example.stellar.org/sep45?tenant=acme&custom=old&',
            $httpClient
        ))
            ->forAccountId($this->clientContractId)
            ->forHomeDomain($this->domain)
            ->forQueryParameters(['custom' => 'new']);
        $this->assertSame(
            'https://auth.example.stellar.org/sep45?tenant=acme&account=' . $this->clientContractId
            . '&home_domain=example.stellar.org&custom=new',
            $builderOverride->buildUrl()
        );

        $builderEmptyQueryAndFragment = (new ContractChallengeRequestBuilder(
            'https://auth.example.stellar.org/sep45?#section',
            $httpClient
        ))
            ->forAccountId($this->clientContractId)
            ->forHomeDomain($this->domain);
        $this->assertSame(
            'https://auth.example.stellar.org/sep45?account=' . $this->clientContractId . '&home_domain=example.stellar.org#section',
            $builderEmptyQueryAndFragment->buildUrl()
        );

        $builderNumericPlusNullArray = (new ContractChallengeRequestBuilder(
            'https://auth.example.stellar.org/sep45?1=v&tag=a+b#frag',
            $httpClient
        ))
            ->forAccountId($this->clientContractId)
            ->forHomeDomain($this->domain)
            ->forQueryParameters([
                'optional' => null,
                'items' => ['a b', 'c+d'],
            ]);
        $this->assertSame(
            'https://auth.example.stellar.org/sep45?1=v&tag=a+b'
            . '&account=' . $this->clientContractId
            . '&home_domain=example.stellar.org'
            . '&items%5B0%5D=a%20b&items%5B1%5D=c%2Bd#frag',
            $builderNumericPlusNullArray->buildUrl()
        );

        $builderNumericOverride = (new ContractChallengeRequestBuilder(
            'https://auth.example.stellar.org/sep45?1=old&2=keep&custom%5Fkey=old&flag',
            $httpClient
        ))
            ->forAccountId($this->clientContractId)
            ->forHomeDomain($this->domain)
            ->forQueryParameters(['1' => 'new', 'custom_key' => 'new']);
        $this->assertSame(
            'https://auth.example.stellar.org/sep45?2=keep&flag'
            . '&account=' . $this->clientContractId
            . '&home_domain=example.stellar.org&1=new&custom_key=new',
            $builderNumericOverride->buildUrl()
        );

        $builderNullOnly = (new ContractChallengeRequestBuilder(
            'https://auth.example.stellar.org/sep45',
            $httpClient
        ))->forQueryParameters(['optional' => null]);
        $this->assertSame(
            'https://auth.example.stellar.org/sep45',
            $builderNullOnly->buildUrl()
        );
    }

    public function testContractChallengeRequestBuilderRequestSendsGivenUrlUnchanged(): void
    {
        $completeUrl = 'https://auth.example.stellar.org/custom?1=v&tag=a+b&account=' . $this->clientContractId;
        $sentRequests = [];
        $handler = function (RequestInterface $request) use (&$sentRequests): PromiseInterface {
            $sentRequests[] = ['method' => $request->getMethod(), 'uri' => (string) $request->getUri()];
            return Create::promiseFor(new Response(200, [], json_encode([
                'authorization_entries' => 'AAAAAA==',
            ])));
        };
        $httpClient = new Client(['handler' => HandlerStack::create($handler)]);

        $builder = (new ContractChallengeRequestBuilder(
            'https://auth.example.stellar.org/sep45?tenant=acme',
            $httpClient
        ))
            ->forAccountId($this->clientContractId)
            ->forHomeDomain($this->domain);

        $response = $builder->request($completeUrl);

        $this->assertSame('AAAAAA==', $response->getAuthorizationEntries());
        $this->assertCount(1, $sentRequests);
        $this->assertSame('GET', $sentRequests[0]['method']);
        $this->assertSame($completeUrl, $sentRequests[0]['uri']);
    }

    public function testEmptyClientDomainRejectedBeforeAnyRequest(): void
    {
        $clientDomainKeyPair = KeyPair::random();
        $callback = static fn(SorobanAuthorizationEntry $entry): SorobanAuthorizationEntry => $entry;

        $jwtCases = [
            'empty domain with key pair' => [$clientDomainKeyPair, null],
            'empty domain with callback' => [null, $callback],
            'empty domain with neither' => [null, null],
        ];

        foreach ($jwtCases as $label => [$kp, $cb]) {
            $mock = new MockHandler([
                new Response(200, [], json_encode(['authorization_entries' => 'AAAAAA=='])),
            ]);
            $webAuth = new WebAuthForContracts(
                $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
            );
            $webAuth->setMockHandler($mock);

            $error = null;
            try {
                $webAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain, '', $kp, $cb);
            } catch (InvalidArgumentException $e) {
                $error = $e;
            }

            $this->assertNotNull($error, "Case '$label' must throw InvalidArgumentException");
            $this->assertSame('Client domain must not be empty', $error->getMessage());
            $this->assertCount(1, $mock, "Case '$label' must not send any HTTP request");
            $this->assertNull($mock->getLastRequest());
        }

        // getChallenge() also rejects empty clientDomain before sending any request
        $getMock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => 'AAAAAA=='])),
        ]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($getMock);

        try {
            $webAuth->getChallenge($this->clientContractId, $this->domain, '');
            $this->fail('Expected InvalidArgumentException for empty clientDomain in getChallenge()');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Client domain must not be empty', $e->getMessage());
        }
        $this->assertCount(1, $getMock);
        $this->assertNull($getMock->getLastRequest());

        // validateChallenge() also rejects empty clientDomain
        $entries = $webAuth->decodeAuthorizationEntries($this->buildValidChallenge(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId, 'empty-cd-val-nonce'
        ));
        try {
            $webAuth->validateChallenge($entries, $this->clientContractId, $this->domain, $clientDomainKeyPair->getAccountId(), '');
            $this->fail('Expected InvalidArgumentException for empty clientDomain in validateChallenge()');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Client domain must not be empty', $e->getMessage());
        }
    }

    public function testZeroStringClientDomainIsTreatedAsRequestedDomainEverywhere(): void
    {
        $clientDomainKeyPair = KeyPair::random();
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'zero-cd-nonce',
            '0',
            $clientDomainKeyPair->getAccountId()
        );

        $requests = [];
        $handler = function (RequestInterface $request) use ($challengeXdr, &$requests): PromiseInterface {
            $requests[] = [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'body' => (string) $request->getBody(),
            ];
            if ($request->getMethod() === 'GET') {
                return Create::promiseFor(new Response(200, [], json_encode([
                    'authorization_entries' => $challengeXdr,
                ])));
            }
            return Create::promiseFor(new Response(200, [], json_encode(['token' => $this->successJWTToken])));
        };

        $httpClient = new Client(['handler' => HandlerStack::create($handler)]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $httpClient
        );
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $token = $webAuth->jwtToken(
            $this->clientContractId,
            [KeyPair::random()],
            $this->domain,
            '0',
            $clientDomainKeyPair
        );

        $this->assertSame($this->successJWTToken, $token);
        $this->assertCount(2, $requests);
        $this->assertSame(
            $this->authServer . '?account=' . $this->clientContractId . '&home_domain=' . $this->domain . '&client_domain=0',
            $requests[0]['uri']
        );
        parse_str($requests[1]['body'], $postBody);
        $submitted = $webAuth->decodeAuthorizationEntries($postBody['authorization_entries']);
        $this->assertCount(3, $submitted);
        $cdCreds = $submitted[2]->credentials->getAddressCredentials();
        $this->assertSame(123466, $cdCreds->signatureExpirationLedger);
        $this->assertCount(1, $cdCreds->signature->vec);
    }

    public function testClientDomainKeyPairTakesPrecedenceOverCallback(): void
    {
        $clientDomain = 'client.example.com';
        $clientDomainKeyPair = KeyPair::random();
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'both-kp-and-cb-nonce',
            $clientDomain,
            $clientDomainKeyPair->getAccountId()
        );

        $requests = [];
        $tomlRequested = false;
        $handler = function (RequestInterface $request) use ($challengeXdr, &$requests, &$tomlRequested): PromiseInterface {
            $uri = (string) $request->getUri();
            $requests[] = ['method' => $request->getMethod(), 'uri' => $uri, 'body' => (string) $request->getBody()];
            if (str_contains($uri, 'stellar.toml')) {
                $tomlRequested = true;
                return Create::promiseFor(new Response(500, [], 'TOML must not be requested'));
            }
            if ($request->getMethod() === 'GET') {
                return Create::promiseFor(new Response(200, [], json_encode([
                    'authorization_entries' => $challengeXdr,
                ])));
            }
            return Create::promiseFor(new Response(200, [], json_encode(['token' => $this->successJWTToken])));
        };

        $httpClient = new Client(['handler' => HandlerStack::create($handler)]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $httpClient
        );
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $callbackCalls = 0;
        $callback = function (SorobanAuthorizationEntry $entry) use (&$callbackCalls): SorobanAuthorizationEntry {
            $callbackCalls++;
            return $entry;
        };

        $token = $webAuth->jwtToken(
            $this->clientContractId,
            [KeyPair::random()],
            $this->domain,
            $clientDomain,
            $clientDomainKeyPair,
            $callback
        );

        $this->assertSame($this->successJWTToken, $token);
        $this->assertFalse($tomlRequested, 'stellar.toml must not be requested when clientDomainKeyPair is provided');
        $this->assertSame(0, $callbackCalls, 'Callback must not be called when clientDomainKeyPair is provided');
        $this->assertCount(2, $requests);

        parse_str($requests[1]['body'], $postBody);
        $submitted = $webAuth->decodeAuthorizationEntries($postBody['authorization_entries']);
        $this->assertCount(3, $submitted);
        $cdCreds = $submitted[2]->credentials->getAddressCredentials();
        $this->assertSame(123466, $cdCreds->signatureExpirationLedger);
        $this->assertCount(1, $cdCreds->signature->vec);
        $sigBytes = $this->extractFirstSignatureBytes($cdCreds->signature);
        $payload = Hash::generate($submitted[2]->buildPreimage(Network::testnet())->encode());
        $this->assertTrue($clientDomainKeyPair->verifySignature($sigBytes, $payload));

        // Direct signAuthorizationEntries() also lets key pair sign and skips the callback
        $directEntries = $webAuth->decodeAuthorizationEntries($challengeXdr);
        $signedDirect = $webAuth->signAuthorizationEntries(
            $directEntries,
            $this->clientContractId,
            [],
            555000,
            $clientDomainKeyPair,
            $callback,
            $clientDomainKeyPair->getAccountId()
        );
        $this->assertSame(0, $callbackCalls, 'Direct signAuthorizationEntries must not invoke callback when key pair is passed');
        $directCdCreds = $signedDirect[2]->credentials->getAddressCredentials();
        $this->assertSame(555000, $directCdCreds->signatureExpirationLedger);
        $this->assertCount(1, $directCdCreds->signature->vec);
        $directSigBytes = $this->extractFirstSignatureBytes($directCdCreds->signature);
        $directPayload = Hash::generate($signedDirect[2]->buildPreimage(Network::testnet())->encode());
        $this->assertTrue($clientDomainKeyPair->verifySignature($directSigBytes, $directPayload));
    }

    public function testSignatureFreeFlowEmptySignersStampsAutoFilledAndExplicitExpiration(): void
    {
        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'sig-free-nonce'
        );
        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12345, 1000000
        );
        $serverEntry->sign($this->serverKeyPair, Network::testnet());
        $expectedServerEntryXdr = $serverEntry->toBase64Xdr();

        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12346, 0
        );
        $challengeXdr = $this->encodeAuthEntries([$serverEntry, $clientEntry]);

        // Case 1: signatureExpirationLedger is null -> auto-filled to latest + 10 (450010)
        $latestLedger = new GetLatestLedgerResponse([]);
        $latestLedger->sequence = 450000;
        $sorobanServer = $this->createMock(SorobanServer::class);
        $sorobanServer->expects($this->once())->method('getLatestLedger')->willReturn($latestLedger);
        $sorobanServer->expects($this->once())->method('simulateTransaction')->willReturn($this->buildDefaultSimulationResponse());

        $mock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($sorobanServer);

        $token = $webAuth->jwtToken($this->clientContractId, [], $this->domain);
        $this->assertSame($this->successJWTToken, $token);
        $this->assertCount(0, $mock);
        parse_str((string) $mock->getLastRequest()->getBody(), $body);
        $submitted = $webAuth->decodeAuthorizationEntries($body['authorization_entries']);
        $this->assertCount(2, $submitted);
        $this->assertSame($expectedServerEntryXdr, $submitted[0]->toBase64Xdr());
        $clientCreds = $submitted[1]->credentials->getAddressCredentials();
        $this->assertSame(450010, $clientCreds->signatureExpirationLedger);
        $this->assertSame(XdrSCValType::SCV_VOID, $clientCreds->signature->type->value);

        // Case 2: explicit signatureExpirationLedger -> getLatestLedger skipped, simulation still runs, client entry stamped with 777777
        $explicitSorobanServer = $this->createMock(SorobanServer::class);
        $explicitSorobanServer->expects($this->never())->method('getLatestLedger');
        $explicitSorobanServer->expects($this->once())->method('simulateTransaction')->willReturn($this->buildDefaultSimulationResponse());
        $mockExplicit = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth->setMockHandler($mockExplicit);
        $webAuth->setSorobanServer($explicitSorobanServer);

        $tokenExplicit = $webAuth->jwtToken(
            $this->clientContractId, [], $this->domain, signatureExpirationLedger: 777777
        );
        $this->assertSame($this->successJWTToken, $tokenExplicit);
        parse_str((string) $mockExplicit->getLastRequest()->getBody(), $bodyExplicit);
        $submittedExplicit = $webAuth->decodeAuthorizationEntries($bodyExplicit['authorization_entries']);
        $clientCredsExplicit = $submittedExplicit[1]->credentials->getAddressCredentials();
        $this->assertSame(777777, $clientCredsExplicit->signatureExpirationLedger);
        $this->assertSame(XdrSCValType::SCV_VOID, $clientCredsExplicit->signature->type->value);

        // Case 3: pre-signed client entry with empty signers preserves resolveSignatureExpirationLedger() conflict handling
        $preSignedClientEntry = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12346, 1000000
        );
        $preSignedClientEntry->sign(KeyPair::random(), Network::testnet());
        $preSignedXdr = $preSignedClientEntry->toBase64Xdr();

        // Matching expiration succeeds and preserves signature
        $matching = $webAuth->signAuthorizationEntries([$preSignedClientEntry], $this->clientContractId, [], 1000000);
        $this->assertSame($preSignedXdr, $matching[0]->toBase64Xdr());

        // Conflicting expiration throws InvalidArgumentException
        try {
            $webAuth->signAuthorizationEntries(
                [SorobanAuthorizationEntry::fromBase64Xdr($preSignedXdr)],
                $this->clientContractId,
                [],
                450010
            );
            $this->fail('Expected InvalidArgumentException for conflicting expiration on pre-signed client entry');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                'Signature expiration ledger 450010 conflicts with stored ledger 1000000',
                $e->getMessage()
            );
        }
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function latestLedgerFailureSignerCases(): array
    {
        return [
            'with client signer' => [true],
            'with empty signers' => [false],
        ];
    }

    #[DataProvider('latestLedgerFailureSignerCases')]
    public function testLatestLedgerFailureThrowsWithoutSendingPost(bool $withClientSigner): void
    {
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'ledger-fail-nonce'
        );

        $latestLedger = new GetLatestLedgerResponse([]);
        $latestLedger->sequence = null;
        $sorobanServer = $this->createMock(SorobanServer::class);
        $sorobanServer->expects($this->once())->method('getLatestLedger')->willReturn($latestLedger);
        $sorobanServer->expects($this->never())->method('simulateTransaction');

        $mock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($sorobanServer);

        $signers = $withClientSigner ? [KeyPair::random()] : [];
        $error = null;
        try {
            $webAuth->jwtToken($this->clientContractId, $signers, $this->domain);
        } catch (RuntimeException $e) {
            $error = $e;
        }

        $this->assertNotNull($error);
        $this->assertSame('Failed to get current ledger from Soroban RPC', $error->getMessage());
        $this->assertCount(1, $mock, 'Token response must remain queued: no POST sent');
        $this->assertSame('GET', $mock->getLastRequest()->getMethod());
    }

    public function testExpirationAutoFillWithEmptySignersAndClientDomainKeyPair(): void
    {
        $clientDomain = 'client.example.com';
        $clientDomainKeyPair = KeyPair::random();
        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'autofill-cd-kp-nonce',
            $clientDomain,
            $clientDomainKeyPair->getAccountId()
        );
        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12345, 1000000
        );
        $serverEntry->sign($this->serverKeyPair, Network::testnet());
        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12346, 0
        );
        $clientDomainEntry = $this->buildAuthEntry(
            $clientDomainKeyPair->getAccountId(), $this->webAuthContractId, 'web_auth_verify', $argsMap, 12347, 0
        );
        $challengeXdr = $this->encodeAuthEntries([$serverEntry, $clientEntry, $clientDomainEntry]);

        $latestLedger = new GetLatestLedgerResponse([]);
        $latestLedger->sequence = 200000;
        $sorobanServer = $this->createMock(SorobanServer::class);
        $sorobanServer->expects($this->once())->method('getLatestLedger')->willReturn($latestLedger);
        $sorobanServer->expects($this->once())->method('simulateTransaction')
            ->willReturn($this->buildDefaultSimulationResponse([$clientDomainKeyPair->getAccountId()]));

        $mock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($sorobanServer);

        $token = $webAuth->jwtToken(
            $this->clientContractId, [], $this->domain, $clientDomain, $clientDomainKeyPair
        );

        $this->assertSame($this->successJWTToken, $token);
        $this->assertCount(0, $mock);
        parse_str((string) $mock->getLastRequest()->getBody(), $body);
        $submitted = $webAuth->decodeAuthorizationEntries($body['authorization_entries']);
        $this->assertCount(3, $submitted);

        $clientCreds = $submitted[1]->credentials->getAddressCredentials();
        $this->assertSame(200010, $clientCreds->signatureExpirationLedger);
        $this->assertSame(XdrSCValType::SCV_VOID, $clientCreds->signature->type->value);

        $cdCreds = $submitted[2]->credentials->getAddressCredentials();
        $this->assertSame(200010, $cdCreds->signatureExpirationLedger);
        $this->assertCount(1, $cdCreds->signature->vec);
        $sigBytes = $this->extractFirstSignatureBytes($cdCreds->signature);
        $payload = Hash::generate($submitted[2]->buildPreimage(Network::testnet())->encode());
        $this->assertTrue($clientDomainKeyPair->verifySignature($sigBytes, $payload));
    }

    public function testExpirationAutoFillWithEmptySignersAndClientDomainCallback(): void
    {
        $clientDomain = 'client.example.com';
        $clientDomainKeyPair = KeyPair::random();
        $clientDomainAccount = $clientDomainKeyPair->getAccountId();
        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'autofill-cd-cb-nonce',
            $clientDomain,
            $clientDomainAccount
        );
        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12345, 1000000
        );
        $serverEntry->sign($this->serverKeyPair, Network::testnet());
        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12346, 0
        );
        $clientDomainEntry = $this->buildAuthEntry(
            $clientDomainAccount, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12347, 0
        );
        $challengeXdr = $this->encodeAuthEntries([$serverEntry, $clientEntry, $clientDomainEntry]);

        $latestLedger = new GetLatestLedgerResponse([]);
        $latestLedger->sequence = 300000;
        $sorobanServer = $this->createMock(SorobanServer::class);
        $sorobanServer->expects($this->once())->method('getLatestLedger')->willReturn($latestLedger);
        $sorobanServer->expects($this->once())->method('simulateTransaction')
            ->willReturn($this->buildDefaultSimulationResponse([$clientDomainAccount]));

        $mock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(200, [], 'SIGNING_KEY = "' . $clientDomainAccount . '"'),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($sorobanServer);

        $expirationSeenByCallback = null;
        $callback = function (SorobanAuthorizationEntry $entry) use ($clientDomainKeyPair, &$expirationSeenByCallback): SorobanAuthorizationEntry {
            $expirationSeenByCallback = $entry->credentials->getAddressCredentials()->signatureExpirationLedger;
            $entry->sign($clientDomainKeyPair, Network::testnet());
            return $entry;
        };

        $token = $webAuth->jwtToken(
            $this->clientContractId, [], $this->domain, $clientDomain, null, $callback
        );

        $this->assertSame($this->successJWTToken, $token);
        $this->assertSame(300010, $expirationSeenByCallback);
        $this->assertCount(0, $mock);
        parse_str((string) $mock->getLastRequest()->getBody(), $body);
        $submitted = $webAuth->decodeAuthorizationEntries($body['authorization_entries']);
        $this->assertCount(3, $submitted);

        $clientCreds = $submitted[1]->credentials->getAddressCredentials();
        $this->assertSame(300010, $clientCreds->signatureExpirationLedger);
        $this->assertSame(XdrSCValType::SCV_VOID, $clientCreds->signature->type->value);

        $cdCreds = $submitted[2]->credentials->getAddressCredentials();
        $this->assertSame(300010, $cdCreds->signatureExpirationLedger);
        $this->assertCount(1, $cdCreds->signature->vec);
        $sigBytes = $this->extractFirstSignatureBytes($cdCreds->signature);
        $payload = Hash::generate($submitted[2]->buildPreimage(Network::testnet())->encode());
        $this->assertTrue($clientDomainKeyPair->verifySignature($sigBytes, $payload));
    }

    private function extractFirstSignatureBytes(XdrSCVal $signatureVec): string
    {
        foreach ($signatureVec->vec[0]->map ?? [] as $mapEntry) {
            if ($mapEntry->key->sym === 'signature' && $mapEntry->val->bytes !== null) {
                return $mapEntry->val->bytes->getValue();
            }
        }
        $this->fail('Signature element must contain a signature bytes field');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function downgradeRedirectTargets(): array
    {
        return [
            'https to http' => ['http://auth.example.stellar.org/downgraded'],
            'https to ftp' => ['ftp://auth.example.stellar.org/downgraded'],
        ];
    }

    #[DataProvider('downgradeRedirectTargets')]
    public function testChallengeGetRejectsHttpsDowngradeRedirect(string $redirectLocation): void
    {
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'redirect-get-nonce'
        );
        $mock = new MockHandler([
            new Response(302, ['Location' => $redirectLocation]),
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $error = null;
        try {
            $webAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain);
        } catch (ContractChallengeRequestErrorResponse $e) {
            $error = $e;
        }

        $this->assertNotNull($error, "GET downgrade redirect to '$redirectLocation' must be rejected");
        $this->assertSame(302, $error->getHttpStatusCode());
        $this->assertSame('Unexpected challenge response status: 302', $error->getMessage());
        $this->assertCount(2, $mock, 'Redirect target and token responses must remain queued');
        $this->assertSame('https', $mock->getLastRequest()->getUri()->getScheme());

        // Direct ContractChallengeRequestBuilder::request() must also block HTTPS downgrade
        $builderMock = new MockHandler([
            new Response(302, ['Location' => $redirectLocation]),
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
        ]);
        $builderClient = new Client(['handler' => HandlerStack::create($builderMock)]);
        $builder = new ContractChallengeRequestBuilder($this->authServer, $builderClient);

        $builderError = null;
        try {
            $builder->request($this->authServer . '?account=' . $this->clientContractId . '&home_domain=' . $this->domain);
        } catch (HorizonRequestException $e) {
            $builderError = $e;
        }

        $this->assertNotNull($builderError);
        $this->assertSame(302, $builderError->getStatusCode());
        $this->assertSame('Unexpected challenge response status: 302', $builderError->getMessage());
        $this->assertCount(1, $builderMock);
        $this->assertSame('https', $builderMock->getLastRequest()->getUri()->getScheme());
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function postDowngradeRedirectCases(): array
    {
        return [
            '307 https to http' => [307, 'http://auth.example.stellar.org/token'],
            '302 https to http' => [302, 'http://auth.example.stellar.org/token'],
            '307 https to ftp' => [307, 'ftp://auth.example.stellar.org/token'],
        ];
    }

    #[DataProvider('postDowngradeRedirectCases')]
    public function testTokenPostRejectsHttpsDowngradeRedirect(int $statusCode, string $redirectLocation): void
    {
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'redirect-post-nonce'
        );
        $mock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response($statusCode, ['Location' => $redirectLocation]),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $error = null;
        try {
            $webAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain);
        } catch (BadResponseException $e) {
            $error = $e;
        }

        $this->assertNotNull($error, "POST downgrade redirect ($statusCode -> $redirectLocation) must throw BadResponseException");
        $this->assertSame($statusCode, $error->getResponse()->getStatusCode());
        $this->assertStringContainsString('does not use one of the allowed redirect protocols: https', $error->getMessage());
        $this->assertCount(1, $mock, 'Downgraded POST target must not be requested');
        $this->assertSame('https', $mock->getLastRequest()->getUri()->getScheme());
    }

    public function testHttpsToHttpsAndLocalhostHttpRedirectsSucceed(): void
    {
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'https-redirect-ok-nonce'
        );
        $mock = new MockHandler([
            new Response(302, ['Location' => 'https://auth2.example.stellar.org/challenge']),
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(307, ['Location' => 'https://auth2.example.stellar.org/token']),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $token = $webAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain);
        $this->assertSame($this->successJWTToken, $token);
        $this->assertCount(0, $mock);
        $this->assertSame('https://auth2.example.stellar.org/token', (string) $mock->getLastRequest()->getUri());

        // Localhost HTTP endpoint can still follow HTTP redirects
        $localhostChallengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'localhost:8080',
            $this->serverAccountId,
            'localhost-redirect-ok-nonce'
        );
        $localMock = new MockHandler([
            new Response(302, ['Location' => 'http://localhost:8080/v2/challenge']),
            new Response(200, [], json_encode(['authorization_entries' => $localhostChallengeXdr])),
            new Response(307, ['Location' => 'http://localhost:8080/v2/token']),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $localWebAuth = new WebAuthForContracts(
            'http://localhost:8080/sep45', $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $localWebAuth->setMockHandler($localMock);
        $localWebAuth->setSorobanServer($this->createMockSorobanServer());

        $this->assertSame(
            $this->successJWTToken,
            $localWebAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain)
        );
        $this->assertCount(0, $localMock);
        $this->assertSame('http://localhost:8080/v2/token', (string) $localMock->getLastRequest()->getUri());
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function disabledRedirectPolicies(): array
    {
        return [
            'null' => [null],
            'false' => [false],
            'empty array' => [[]],
        ];
    }

    #[DataProvider('disabledRedirectPolicies')]
    public function testHttpsRedirectGuardPreservesDisabledClientRedirectPolicy(mixed $allowRedirects): void
    {
        // GET: HTTPS redirect must NOT be followed when the client disabled redirects
        $getMock = new MockHandler([
            new Response(302, ['Location' => 'https://auth2.example.stellar.org/challenge']),
            new Response(200, [], json_encode(['authorization_entries' => 'AAAAAA=='])),
        ]);
        $getClient = new Client([
            'handler' => HandlerStack::create($getMock),
            'allow_redirects' => $allowRedirects,
        ]);
        $webAuthGet = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $getClient
        );

        try {
            $webAuthGet->getChallenge($this->clientContractId, $this->domain);
            $this->fail('Expected ContractChallengeRequestErrorResponse when redirects are disabled on GET');
        } catch (ContractChallengeRequestErrorResponse $e) {
            $this->assertSame(302, $e->getHttpStatusCode());
            $this->assertSame('Unexpected challenge response status: 302', $e->getMessage());
        }
        $this->assertCount(1, $getMock, 'GET redirect target must not be requested when redirects are disabled');
        $this->assertSame('auth.example.stellar.org', $getMock->getLastRequest()->getUri()->getHost());

        // POST: HTTPS redirect must NOT be followed when the client disabled redirects
        $postMock = new MockHandler([
            new Response(302, ['Location' => 'https://auth2.example.stellar.org/token']),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $postClient = new Client([
            'handler' => HandlerStack::create($postMock),
            'allow_redirects' => $allowRedirects,
        ]);
        $webAuthPost = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $postClient
        );

        try {
            $webAuthPost->sendSignedChallenge([]);
            $this->fail('Expected SubmitContractChallengeUnknownResponseException when redirects are disabled on POST');
        } catch (SubmitContractChallengeUnknownResponseException $e) {
            $this->assertSame(302, $e->getHttpStatusCode());
        }
        $this->assertCount(1, $postMock, 'POST redirect target must not be requested when redirects are disabled');
        $this->assertSame($this->authServer, (string) $postMock->getLastRequest()->getUri());
    }

    /**
     * @return array<string, array{array<string>, string}>
     */
    public static function emptyIntersectionRedirectPolicies(): array
    {
        return [
            'empty protocols to https' => [[], 'https://auth2.example.stellar.org/target'],
            'empty protocols to http' => [[], 'http://auth2.example.stellar.org/target'],
            'http-only protocols to https' => [['http'], 'https://auth2.example.stellar.org/target'],
            'http-only protocols to http' => [['http'], 'http://auth2.example.stellar.org/target'],
        ];
    }

    /**
     * @param array<string> $protocols
     */
    #[DataProvider('emptyIntersectionRedirectPolicies')]
    public function testHttpsRedirectGuardPreservesEmptyProtocolsIntersection(
        array $protocols,
        string $redirectLocation
    ): void {
        // GET: must disable redirects rather than widen or pass an empty protocols list
        $getHistory = [];
        $getMock = new MockHandler([
            new Response(302, ['Location' => $redirectLocation]),
            new Response(200, [], json_encode(['authorization_entries' => 'AAAAAA=='])),
        ]);
        $getStack = HandlerStack::create($getMock);
        $getStack->push(Middleware::history($getHistory));
        $getClient = new Client([
            'handler' => $getStack,
            'allow_redirects' => ['protocols' => $protocols],
        ]);
        $webAuthGet = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $getClient
        );

        try {
            $webAuthGet->getChallenge($this->clientContractId, $this->domain);
            $this->fail('Expected ContractChallengeRequestErrorResponse for empty allowed protocols intersection on GET');
        } catch (ContractChallengeRequestErrorResponse $e) {
            $this->assertSame(302, $e->getHttpStatusCode());
            $this->assertSame('Unexpected challenge response status: 302', $e->getMessage());
        }
        $this->assertCount(1, $getHistory);
        $this->assertFalse($getHistory[0]['options']['allow_redirects']);
        $this->assertSame('auth.example.stellar.org', $getHistory[0]['request']->getUri()->getHost());
        $this->assertCount(1, $getMock, 'GET redirect target must not be requested when protocol intersection is empty');

        // POST: must disable redirects rather than widen or pass an empty protocols list
        $postHistory = [];
        $postMock = new MockHandler([
            new Response(307, ['Location' => $redirectLocation]),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $postStack = HandlerStack::create($postMock);
        $postStack->push(Middleware::history($postHistory));
        $postClient = new Client([
            'handler' => $postStack,
            'allow_redirects' => ['protocols' => $protocols],
        ]);
        $webAuthPost = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $postClient
        );

        try {
            $webAuthPost->sendSignedChallenge([]);
            $this->fail('Expected SubmitContractChallengeUnknownResponseException for empty allowed protocols intersection on POST');
        } catch (SubmitContractChallengeUnknownResponseException $e) {
            $this->assertSame(307, $e->getHttpStatusCode());
            $this->assertSame('Unknown response from server (HTTP 307)', $e->getMessage());
        }
        $this->assertCount(1, $postHistory);
        $this->assertFalse($postHistory[0]['options']['allow_redirects']);
        $this->assertSame($this->authServer, (string) $postHistory[0]['request']->getUri());
        $this->assertCount(1, $postMock, 'POST redirect target must not be requested when protocol intersection is empty');
    }

    public function testHttpsRedirectGuardHandlesBooleanTrueAllowRedirects(): void
    {
        // GET with allow_redirects => true follows HTTPS redirects and blocks HTTP redirects
        $getHttpsMock = new MockHandler([
            new Response(302, ['Location' => 'https://auth2.example.stellar.org/challenge']),
            new Response(200, [], json_encode(['authorization_entries' => 'AAAAAA=='])),
        ]);
        $getHttpsClient = new Client(['handler' => HandlerStack::create($getHttpsMock), 'allow_redirects' => true]);
        $webAuthGetHttps = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $getHttpsClient
        );
        $this->assertSame('AAAAAA==', $webAuthGetHttps->getChallenge($this->clientContractId, $this->domain)->getAuthorizationEntries());
        $this->assertCount(0, $getHttpsMock);

        $getHttpMock = new MockHandler([
            new Response(302, ['Location' => 'http://auth2.example.stellar.org/challenge']),
            new Response(200, [], json_encode(['authorization_entries' => 'AAAAAA=='])),
        ]);
        $getHttpClient = new Client(['handler' => HandlerStack::create($getHttpMock), 'allow_redirects' => true]);
        $webAuthGetHttp = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $getHttpClient
        );
        try {
            $webAuthGetHttp->getChallenge($this->clientContractId, $this->domain);
            $this->fail('Expected ContractChallengeRequestErrorResponse for HTTP downgrade with allow_redirects => true');
        } catch (ContractChallengeRequestErrorResponse $e) {
            $this->assertSame(302, $e->getHttpStatusCode());
            $this->assertSame('Unexpected challenge response status: 302', $e->getMessage());
        }
        $this->assertCount(1, $getHttpMock);

        // POST with allow_redirects => true follows HTTPS redirects and blocks HTTP redirects
        $postHttpsMock = new MockHandler([
            new Response(307, ['Location' => 'https://auth2.example.stellar.org/token']),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $postHttpsClient = new Client(['handler' => HandlerStack::create($postHttpsMock), 'allow_redirects' => true]);
        $webAuthPostHttps = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $postHttpsClient
        );
        $this->assertSame($this->successJWTToken, $webAuthPostHttps->sendSignedChallenge([]));
        $this->assertCount(0, $postHttpsMock);

        $postHttpMock = new MockHandler([
            new Response(307, ['Location' => 'http://auth2.example.stellar.org/token']),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $postHttpClient = new Client(['handler' => HandlerStack::create($postHttpMock), 'allow_redirects' => true]);
        $webAuthPostHttp = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $postHttpClient
        );
        try {
            $webAuthPostHttp->sendSignedChallenge([]);
            $this->fail('Expected BadResponseException for HTTP downgrade on POST with allow_redirects => true');
        } catch (BadResponseException $e) {
            $this->assertSame(307, $e->getResponse()->getStatusCode());
            $this->assertStringContainsString('does not use one of the allowed redirect protocols: https', $e->getMessage());
        }
        $this->assertCount(1, $postHttpMock);
    }

    public function testHttpsRedirectGuardPreservesMaxStrictAndOtherPolicyOptions(): void
    {
        // GET: max, referer, on_redirect, and track_redirects preserved when protocols key is absent
        $getOnRedirectTargets = [];
        $getHistory = [];
        $getMock = new MockHandler([
            new Response(302, ['Location' => 'https://auth2.example.stellar.org/challenge']),
            new Response(200, [], json_encode(['authorization_entries' => 'AAAAAA=='])),
        ]);
        $getStack = HandlerStack::create($getMock);
        $getStack->push(Middleware::history($getHistory));
        $getClient = new Client([
            'handler' => $getStack,
            'allow_redirects' => [
                'max' => 1,
                'referer' => true,
                'track_redirects' => true,
                'on_redirect' => function (RequestInterface $req, ResponseInterface $res, UriInterface $uri) use (&$getOnRedirectTargets): void {
                    $getOnRedirectTargets[] = (string) $uri;
                },
            ],
        ]);
        $webAuthGet = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $getClient
        );
        $this->assertSame('AAAAAA==', $webAuthGet->getChallenge($this->clientContractId, $this->domain)->getAuthorizationEntries());
        $this->assertSame(['https://auth2.example.stellar.org/challenge'], $getOnRedirectTargets);
        $this->assertCount(2, $getHistory);
        $this->assertSame((string) $getHistory[0]['request']->getUri(), $getHistory[1]['request']->getHeaderLine('Referer'));
        $this->assertTrue($getHistory[1]['options']['allow_redirects']['track_redirects']);

        // GET: max => 1 still enforced (2nd redirect fails with TooManyRedirectsException)
        $getMaxMock = new MockHandler([
            new Response(302, ['Location' => 'https://auth2.example.stellar.org/step1']),
            new Response(302, ['Location' => 'https://auth3.example.stellar.org/step2']),
            new Response(200, [], json_encode(['authorization_entries' => 'AAAAAA=='])),
        ]);
        $getMaxClient = new Client([
            'handler' => HandlerStack::create($getMaxMock),
            'allow_redirects' => ['max' => 1],
        ]);
        $webAuthGetMax = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $getMaxClient
        );
        try {
            $webAuthGetMax->getChallenge($this->clientContractId, $this->domain);
            $this->fail('Expected ContractChallengeRequestErrorResponse when exceeding max => 1 on GET');
        } catch (ContractChallengeRequestErrorResponse $e) {
            $this->assertSame(302, $e->getHttpStatusCode());
            $this->assertSame('Unexpected challenge response status: 302', $e->getMessage());
        }
        $this->assertCount(1, $getMaxMock, 'Third response must remain queued after max => 1 exceeded');
        $this->assertSame('https://auth2.example.stellar.org/step1', (string) $getMaxMock->getLastRequest()->getUri());

        // POST: strict => true keeps POST method and body on 302 redirect, and preserves max, referer, track_redirects, on_redirect
        $postOnRedirectTargets = [];
        $postHistory = [];
        $postMock = new MockHandler([
            new Response(302, ['Location' => 'https://auth2.example.stellar.org/token']),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $postStack = HandlerStack::create($postMock);
        $postStack->push(Middleware::history($postHistory));
        $postClient = new Client([
            'handler' => $postStack,
            'allow_redirects' => [
                'max' => 1,
                'strict' => true,
                'referer' => true,
                'track_redirects' => true,
                'protocols' => ['http', 'https'],
                'on_redirect' => function (RequestInterface $req, ResponseInterface $res, UriInterface $uri) use (&$postOnRedirectTargets): void {
                    $postOnRedirectTargets[] = (string) $uri;
                },
            ],
        ]);
        $webAuthPost = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $postClient
        );
        $this->assertSame($this->successJWTToken, $webAuthPost->sendSignedChallenge([]));
        $this->assertSame(['https://auth2.example.stellar.org/token'], $postOnRedirectTargets);
        $this->assertCount(2, $postHistory);
        $this->assertSame('POST', $postHistory[1]['request']->getMethod(), 'strict => true must keep POST on 302 redirect');
        $this->assertSame((string) $postHistory[0]['request']->getBody(), (string) $postHistory[1]['request']->getBody());
        $this->assertSame($this->authServer, $postHistory[1]['request']->getHeaderLine('Referer'));
        $this->assertTrue($postHistory[1]['options']['allow_redirects']['track_redirects']);

        // POST: max => 1 still enforced
        $postMaxMock = new MockHandler([
            new Response(302, ['Location' => 'https://auth2.example.stellar.org/t1']),
            new Response(302, ['Location' => 'https://auth3.example.stellar.org/t2']),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $postMaxClient = new Client([
            'handler' => HandlerStack::create($postMaxMock),
            'allow_redirects' => ['max' => 1, 'strict' => true],
        ]);
        $webAuthPostMax = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $postMaxClient
        );
        try {
            $webAuthPostMax->sendSignedChallenge([]);
            $this->fail('Expected TooManyRedirectsException when exceeding max => 1 on POST');
        } catch (TooManyRedirectsException $e) {
            $this->assertStringContainsString('Will not follow more than 1 redirects', $e->getMessage());
        }
        $this->assertCount(1, $postMaxMock, 'Third response must remain queued after max => 1 exceeded');

        // POST with strict => true and no protocols key still blocks HTTP downgrade
        $postDowngradeMock = new MockHandler([
            new Response(302, ['Location' => 'http://auth2.example.stellar.org/token']),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $postDowngradeClient = new Client([
            'handler' => HandlerStack::create($postDowngradeMock),
            'allow_redirects' => ['max' => 1, 'strict' => true],
        ]);
        $webAuthPostDowngrade = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $postDowngradeClient
        );
        try {
            $webAuthPostDowngrade->sendSignedChallenge([]);
            $this->fail('Expected BadResponseException for HTTP downgrade on POST');
        } catch (BadResponseException $e) {
            $this->assertSame(302, $e->getResponse()->getStatusCode());
            $this->assertStringContainsString('does not use one of the allowed redirect protocols: https', $e->getMessage());
        }
        $this->assertCount(1, $postDowngradeMock);
    }

    public function testChallengeBuilderResolvesRelativeUriAgainstHttpsBaseUriForRedirectGuard(): void
    {
        // Relative URI '/challenge' with HTTPS base_uri must block HTTP downgrade redirect
        $downgradeMock = new MockHandler([
            new Response(302, ['Location' => 'http://auth.example/downgraded']),
            new Response(200, [], json_encode(['authorization_entries' => 'AAAAAA=='])),
        ]);
        $httpsBaseClient = new Client([
            'base_uri' => 'https://auth.example/',
            'handler' => HandlerStack::create($downgradeMock),
        ]);
        $builder = new ContractChallengeRequestBuilder('/challenge', $httpsBaseClient);

        try {
            $builder->request('/challenge');
            $this->fail('Expected HorizonRequestException blocking HTTP redirect from relative URI under HTTPS base_uri');
        } catch (HorizonRequestException $e) {
            $this->assertSame(302, $e->getStatusCode());
            $this->assertSame('Unexpected challenge response status: 302', $e->getMessage());
        }
        $this->assertCount(1, $downgradeMock, 'Downgraded HTTP target must not be requested');
        $this->assertSame('https://auth.example/challenge', (string) $downgradeMock->getLastRequest()->getUri());

        // Relative URI '/challenge' with HTTPS base_uri follows HTTPS redirect
        $httpsOkMock = new MockHandler([
            new Response(302, ['Location' => 'https://auth2.example/challenge']),
            new Response(200, [], json_encode(['authorization_entries' => 'AAAAAA=='])),
        ]);
        $httpsOkClient = new Client([
            'base_uri' => 'https://auth.example/',
            'handler' => HandlerStack::create($httpsOkMock),
        ]);
        $okBuilder = new ContractChallengeRequestBuilder('/challenge', $httpsOkClient);
        $this->assertSame('AAAAAA==', $okBuilder->request('/challenge')->getAuthorizationEntries());
        $this->assertCount(0, $httpsOkMock);
        $this->assertSame('https://auth2.example/challenge', (string) $httpsOkMock->getLastRequest()->getUri());

        // Relative URI '/challenge' with HTTPS base_uri and allow_redirects => false does not follow redirects
        $disabledMock = new MockHandler([
            new Response(302, ['Location' => 'https://auth2.example/challenge']),
            new Response(200, [], json_encode(['authorization_entries' => 'AAAAAA=='])),
        ]);
        $disabledClient = new Client([
            'base_uri' => 'https://auth.example/',
            'allow_redirects' => false,
            'handler' => HandlerStack::create($disabledMock),
        ]);
        $disabledBuilder = new ContractChallengeRequestBuilder('/challenge', $disabledClient);
        try {
            $disabledBuilder->request('/challenge');
            $this->fail('Expected HorizonRequestException when redirects disabled on base_uri client');
        } catch (HorizonRequestException $e) {
            $this->assertSame(302, $e->getStatusCode());
            $this->assertSame('Unexpected challenge response status: 302', $e->getMessage());
        }
        $this->assertCount(1, $disabledMock);

        // Protocol-relative URI without base_uri resolves to HTTP scheme per Guzzle buildUri
        $protoRelMock = new MockHandler([
            new Response(302, ['Location' => 'http://localhost:8080/v2/challenge']),
            new Response(200, [], json_encode(['authorization_entries' => 'AAAAAA=='])),
        ]);
        $protoRelClient = new Client(['handler' => HandlerStack::create($protoRelMock)]);
        $protoRelBuilder = new ContractChallengeRequestBuilder('//localhost:8080/challenge', $protoRelClient);
        $this->assertSame('AAAAAA==', $protoRelBuilder->request('//localhost:8080/challenge')->getAuthorizationEntries());
        $this->assertCount(0, $protoRelMock);

        // Constructor still rejects relative authEndpoint even when HTTP client has an HTTPS base_uri
        try {
            new WebAuthForContracts(
                '/challenge',
                $this->webAuthContractId,
                $this->serverAccountId,
                $this->domain,
                Network::testnet(),
                new Client(['base_uri' => 'https://auth.example/'])
            );
            $this->fail('Expected InvalidArgumentException for relative authEndpoint in constructor');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Service URL must use HTTPS. HTTP is only allowed for localhost.', $e->getMessage());
        }

        // POST with an HTTPS base_uri client still blocks HTTP downgrade redirect
        $postBaseMock = new MockHandler([
            new Response(307, ['Location' => 'http://auth.example.stellar.org/token']),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $postBaseClient = new Client([
            'base_uri' => 'https://other.example/',
            'handler' => HandlerStack::create($postBaseMock),
        ]);
        $webAuthBasePost = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $postBaseClient
        );
        try {
            $webAuthBasePost->sendSignedChallenge([]);
            $this->fail('Expected BadResponseException for HTTP downgrade on POST with base_uri client');
        } catch (BadResponseException $e) {
            $this->assertSame(307, $e->getResponse()->getStatusCode());
            $this->assertStringContainsString('does not use one of the allowed redirect protocols: https', $e->getMessage());
        }
        $this->assertCount(1, $postBaseMock);
    }

    public function testLocalhostHttpEndpointPreservesCallerRedirectPolicy(): void
    {
        $localhostEndpoint = 'http://localhost:8080/sep45';

        // Localhost HTTP with allow_redirects => false must NOT follow HTTP redirects on GET or POST
        $getDisabledMock = new MockHandler([
            new Response(302, ['Location' => 'http://localhost:8080/v2/challenge']),
            new Response(200, [], json_encode(['authorization_entries' => 'AAAAAA=='])),
        ]);
        $getDisabledClient = new Client([
            'handler' => HandlerStack::create($getDisabledMock),
            'allow_redirects' => false,
        ]);
        $webAuthDisabledGet = new WebAuthForContracts(
            $localhostEndpoint, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $getDisabledClient
        );
        try {
            $webAuthDisabledGet->getChallenge($this->clientContractId, $this->domain);
            $this->fail('Expected ContractChallengeRequestErrorResponse on localhost GET when redirects are disabled');
        } catch (ContractChallengeRequestErrorResponse $e) {
            $this->assertSame(302, $e->getHttpStatusCode());
            $this->assertSame('Unexpected challenge response status: 302', $e->getMessage());
        }
        $this->assertCount(1, $getDisabledMock);

        $postDisabledMock = new MockHandler([
            new Response(307, ['Location' => 'http://localhost:8080/v2/token']),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $postDisabledClient = new Client([
            'handler' => HandlerStack::create($postDisabledMock),
            'allow_redirects' => false,
        ]);
        $webAuthDisabledPost = new WebAuthForContracts(
            $localhostEndpoint, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $postDisabledClient
        );
        try {
            $webAuthDisabledPost->sendSignedChallenge([]);
            $this->fail('Expected SubmitContractChallengeUnknownResponseException on localhost POST when redirects are disabled');
        } catch (SubmitContractChallengeUnknownResponseException $e) {
            $this->assertSame(307, $e->getHttpStatusCode());
        }
        $this->assertCount(1, $postDisabledMock);

        // Localhost HTTP with caller policy protocols => ['https'] must block HTTP redirects on GET and POST
        $getHttpsOnlyMock = new MockHandler([
            new Response(302, ['Location' => 'http://localhost:8080/v2/challenge']),
            new Response(200, [], json_encode(['authorization_entries' => 'AAAAAA=='])),
        ]);
        $getHttpsOnlyClient = new Client([
            'handler' => HandlerStack::create($getHttpsOnlyMock),
            'allow_redirects' => ['protocols' => ['https']],
        ]);
        $webAuthHttpsOnlyGet = new WebAuthForContracts(
            $localhostEndpoint, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $getHttpsOnlyClient
        );
        try {
            $webAuthHttpsOnlyGet->getChallenge($this->clientContractId, $this->domain);
            $this->fail('Expected ContractChallengeRequestErrorResponse when localhost client restricts protocols to https');
        } catch (ContractChallengeRequestErrorResponse $e) {
            $this->assertSame(302, $e->getHttpStatusCode());
            $this->assertSame('Unexpected challenge response status: 302', $e->getMessage());
        }
        $this->assertCount(1, $getHttpsOnlyMock);

        $postHttpsOnlyMock = new MockHandler([
            new Response(307, ['Location' => 'http://localhost:8080/v2/token']),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $postHttpsOnlyClient = new Client([
            'handler' => HandlerStack::create($postHttpsOnlyMock),
            'allow_redirects' => ['protocols' => ['https']],
        ]);
        $webAuthHttpsOnlyPost = new WebAuthForContracts(
            $localhostEndpoint, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet(), $postHttpsOnlyClient
        );
        try {
            $webAuthHttpsOnlyPost->sendSignedChallenge([]);
            $this->fail('Expected BadResponseException when localhost POST client restricts protocols to https');
        } catch (BadResponseException $e) {
            $this->assertSame(307, $e->getResponse()->getStatusCode());
            $this->assertStringContainsString('does not use one of the allowed redirect protocols: https', $e->getMessage());
        }
        $this->assertCount(1, $postHttpsOnlyMock);
    }

    public function testDefaultSorobanRpcUrlRestrictedToTestnet(): void
    {
        $rpcProp = new ReflectionProperty(WebAuthForContracts::class, 'sorobanRpcUrl');

        $testnetAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $this->assertSame('https://soroban-testnet.stellar.org', $rpcProp->getValue($testnetAuth));

        foreach ([
            'public network' => [
                Network::public(),
                "sorobanRpcUrl is required for network passphrase 'Public Global Stellar Network ; September 2015'; only testnet has a default",
                'https://rpc.mainnet.example.com',
            ],
            'futurenet' => [
                Network::futurenet(),
                "sorobanRpcUrl is required for network passphrase 'Test SDF Future Network ; October 2022'; only testnet has a default",
                'https://rpc.futurenet.example.com',
            ],
            'custom network' => [
                new Network('Custom Standalone Network ; 2026'),
                "sorobanRpcUrl is required for network passphrase 'Custom Standalone Network ; 2026'; only testnet has a default",
                'https://rpc.custom.example.com',
            ],
        ] as $label => [$network, $expectedMessage, $explicitRpcUrl]) {
            try {
                new WebAuthForContracts(
                    $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, $network
                );
                $this->fail("Expected InvalidArgumentException for missing sorobanRpcUrl on $label");
            } catch (InvalidArgumentException $e) {
                $this->assertSame($expectedMessage, $e->getMessage());
            }

            $withRpc = new WebAuthForContracts(
                $this->authServer,
                $this->webAuthContractId,
                $this->serverAccountId,
                $this->domain,
                $network,
                null,
                $explicitRpcUrl
            );
            $this->assertSame($explicitRpcUrl, $rpcProp->getValue($withRpc));
        }

        // Invalid custom sorobanRpcUrl is rejected
        try {
            new WebAuthForContracts(
                $this->authServer,
                $this->webAuthContractId,
                $this->serverAccountId,
                $this->domain,
                Network::testnet(),
                null,
                'http://rpc.example.com'
            );
            $this->fail('Expected InvalidArgumentException for non-HTTPS sorobanRpcUrl');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Service URL must use HTTPS. HTTP is only allowed for localhost.', $e->getMessage());
        }
    }

    public function testFromDomainDiscoveryOnCustomNetworkWithAndWithoutRpcUrl(): void
    {
        $customNetwork = new Network('Custom Private Network ; 2026');
        $customRpcUrl = 'https://rpc.private.example.com';
        $tomlBody = "WEB_AUTH_FOR_CONTRACTS_ENDPOINT = \"{$this->authServer}\"\n"
            . "WEB_AUTH_CONTRACT_ID = \"{$this->webAuthContractId}\"\n"
            . "SIGNING_KEY = \"{$this->serverAccountId}\"\n";

        // Missing sorobanRpcUrl on custom network fails in constructor
        $failClient = new Client(['handler' => HandlerStack::create(new MockHandler([
            new Response(200, [], $tomlBody),
        ]))]);
        try {
            WebAuthForContracts::fromDomain($this->domain, $customNetwork, $failClient);
            $this->fail('Expected InvalidArgumentException when fromDomain() omits sorobanRpcUrl on custom network');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString($customNetwork->getNetworkPassphrase(), $e->getMessage());
        }

        // Explicit sorobanRpcUrl on custom network succeeds and completes full flow
        $argsMap = $this->buildArgsMap(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId, 'custom-net-nonce'
        );
        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12345, 1000000
        );
        $serverEntry->sign($this->serverKeyPair, $customNetwork);
        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12346, 1000000
        );
        $challengeXdr = $this->encodeAuthEntries([$serverEntry, $clientEntry]);

        $requests = [];
        $responses = [
            new Response(200, [], $tomlBody),
            new Response(200, [], json_encode([
                'authorization_entries' => $challengeXdr,
                'network_passphrase' => $customNetwork->getNetworkPassphrase(),
            ])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ];
        $handler = function (RequestInterface $request) use (&$requests, &$responses): PromiseInterface {
            $requests[] = ['method' => $request->getMethod(), 'uri' => (string) $request->getUri(), 'body' => (string) $request->getBody()];
            return Create::promiseFor(array_shift($responses));
        };
        $httpClient = new Client(['handler' => HandlerStack::create($handler)]);

        $webAuth = WebAuthForContracts::fromDomain($this->domain, $customNetwork, $httpClient, $customRpcUrl);
        $rpcProp = new ReflectionProperty(WebAuthForContracts::class, 'sorobanRpcUrl');
        $this->assertSame($customRpcUrl, $rpcProp->getValue($webAuth));

        $webAuth->setSorobanServer($this->createMockSorobanServer());
        $clientSigner = KeyPair::random();
        $token = $webAuth->jwtToken($this->clientContractId, [$clientSigner]);

        $this->assertSame($this->successJWTToken, $token);
        $this->assertCount(3, $requests);
        $this->assertSame('https://example.stellar.org/.well-known/stellar.toml', $requests[0]['uri']);
        $this->assertSame('POST', $requests[2]['method']);
        parse_str($requests[2]['body'], $postBody);
        $submitted = $webAuth->decodeAuthorizationEntries($postBody['authorization_entries']);
        $this->assertCount(2, $submitted);
        $sigBytes = $this->extractFirstSignatureBytes($submitted[1]->credentials->getAddressCredentials()->signature);
        $payload = Hash::generate($submitted[1]->buildPreimage($customNetwork)->encode());
        $this->assertTrue($clientSigner->verifySignature($sigBytes, $payload));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidContractStrkeys(): array
    {
        return [
            'G account strkey' => ['GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP'],
            'M muxed account strkey' => ['MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAAAJLK'],
            'W muxed contract strkey' => ['WA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAACWJY'],
            'S-prefixed non-seed marker' => ['S-NOT-A-SEED-CONTRACT-MARKER'],
            'non-strkey marker value' => ['UNEXPECTED-CONTRACT-MARKER-VALUE'],
            'C prefix too short' => ['CINVALID'],
            'C prefix bad checksum' => ['CDZJIDQW5WTPAZ64PGIJGVEIDNK72LL3LKUZWG3G6GWXYQKI2JNIVFNA'],
            'empty string' => [''],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidAccountStrkeys(): array
    {
        return [
            'C contract strkey' => ['CA7A3N2BB35XMTFPAYWVZEF4TEYXW7DAEWDXJNQGUPR5SWSM2UVZCJM2'],
            'M muxed account strkey' => ['MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAAAJLK'],
            'S-prefixed non-seed marker' => ['S-NOT-A-SEED-ACCOUNT-MARKER'],
            'non-strkey marker value' => ['UNEXPECTED-ACCOUNT-MARKER-VALUE'],
            'G prefix too short' => ['GINVALID'],
            'G prefix bad checksum' => ['GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBA'],
            'empty string' => [''],
        ];
    }

    #[DataProvider('invalidContractStrkeys')]
    public function testConstructorRejectsInvalidWebAuthContractStrkeyWithoutEchoing(string $badContractId): void
    {
        try {
            new WebAuthForContracts(
                $this->authServer, $badContractId, $this->serverAccountId, $this->domain, Network::testnet()
            );
            $this->fail('Expected InvalidArgumentException for invalid webAuthContractId');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('webAuthContractId must be a valid contract address (C...)', $e->getMessage());
            if ($badContractId !== '') {
                $this->assertStringNotContainsString($badContractId, $e->getMessage());
            }
        }
    }

    #[DataProvider('invalidAccountStrkeys')]
    public function testConstructorRejectsInvalidServerSigningKeyStrkeyWithoutEchoing(string $badSigningKey): void
    {
        try {
            new WebAuthForContracts(
                $this->authServer, $this->webAuthContractId, $badSigningKey, $this->domain, Network::testnet()
            );
            $this->fail('Expected InvalidArgumentException for invalid serverSigningKey');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('serverSigningKey must be a valid ed25519 account address (G...)', $e->getMessage());
            if ($badSigningKey !== '') {
                $this->assertStringNotContainsString($badSigningKey, $e->getMessage());
            }
        }
    }

    #[DataProvider('invalidContractStrkeys')]
    public function testJwtTokenAndGetChallengeRejectInvalidClientAccountIdBeforeGetWithoutEchoing(string $badAccountId): void
    {
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );

        foreach (['jwtToken', 'getChallenge'] as $method) {
            $mock = new MockHandler([
                new Response(200, [], json_encode(['authorization_entries' => 'AAAAAA=='])),
            ]);
            $webAuth->setMockHandler($mock);

            $error = null;
            try {
                if ($method === 'jwtToken') {
                    $webAuth->jwtToken($badAccountId, [KeyPair::random()], $this->domain);
                } else {
                    $webAuth->getChallenge($badAccountId, $this->domain);
                }
            } catch (InvalidArgumentException $e) {
                $error = $e;
            }

            $this->assertNotNull($error, "$method must reject invalid clientAccountId");
            $this->assertSame('Client account must be a valid contract address (C...)', $error->getMessage());
            if ($badAccountId !== '') {
                $this->assertStringNotContainsString($badAccountId, $error->getMessage());
            }
            $this->assertCount(1, $mock, "$method must not send a GET request for invalid clientAccountId");
            $this->assertNull($mock->getLastRequest());
        }
    }

    public function testSignAuthorizationEntriesWithSparseAndAssociativeKeys(): void
    {
        $clientSigner = KeyPair::random();
        $clientDomainKeyPair = KeyPair::random();
        $clientDomainAccount = $clientDomainKeyPair->getAccountId();
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );

        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'sparse-keys-nonce',
            'client.example.com',
            $clientDomainAccount
        );

        // Case 1: out-of-order sparse integer keys where original key of clientDomain (2) collides with another slot
        [$server1, $client1, $domain1] = $webAuth->decodeAuthorizationEntries($challengeXdr);
        $sparseInput = [2 => $domain1, 0 => $server1, 10 => $client1];
        $callbackAddress = null;
        $signedSparse = $webAuth->signAuthorizationEntries(
            $sparseInput,
            $this->clientContractId,
            [$clientSigner],
            400000,
            null,
            function (SorobanAuthorizationEntry $entry) use ($clientDomainKeyPair, &$callbackAddress): SorobanAuthorizationEntry {
                $callbackAddress = $entry->credentials->getAddressCredentials()->address->toStrKey();
                $entry->sign($clientDomainKeyPair, Network::testnet());
                return $entry;
            },
            $clientDomainAccount
        );

        $this->assertSame($clientDomainAccount, $callbackAddress);
        $this->assertTrue(array_is_list($signedSparse));
        $this->assertCount(3, $signedSparse);
        $this->assertSame($clientDomainAccount, $signedSparse[0]->credentials->getAddressCredentials()->address->toStrKey());
        $this->assertSame($this->serverAccountId, $signedSparse[1]->credentials->getAddressCredentials()->address->toStrKey());
        $this->assertSame($this->clientContractId, $signedSparse[2]->credentials->getAddressCredentials()->address->toStrKey());
        $this->assertTrue($clientDomainKeyPair->verifySignature(
            $this->extractFirstSignatureBytes($signedSparse[0]->credentials->getAddressCredentials()->signature),
            Hash::generate($signedSparse[0]->buildPreimage(Network::testnet())->encode())
        ));
        $this->assertTrue($clientSigner->verifySignature(
            $this->extractFirstSignatureBytes($signedSparse[2]->credentials->getAddressCredentials()->signature),
            Hash::generate($signedSparse[2]->buildPreimage(Network::testnet())->encode())
        ));

        // Case 2: associative string keys
        [$server2, $client2, $domain2] = $webAuth->decodeAuthorizationEntries($challengeXdr);
        $assocInput = ['srv' => $server2, 'cli' => $client2, 'dom' => $domain2];
        $assocCallbackAddress = null;
        $signedAssoc = $webAuth->signAuthorizationEntries(
            $assocInput,
            $this->clientContractId,
            [$clientSigner],
            400001,
            null,
            function (SorobanAuthorizationEntry $entry) use ($clientDomainKeyPair, &$assocCallbackAddress): SorobanAuthorizationEntry {
                $assocCallbackAddress = $entry->credentials->getAddressCredentials()->address->toStrKey();
                $entry->sign($clientDomainKeyPair, Network::testnet());
                return $entry;
            },
            $clientDomainAccount
        );

        $this->assertSame($clientDomainAccount, $assocCallbackAddress);
        $this->assertTrue(array_is_list($signedAssoc));
        $this->assertCount(3, $signedAssoc);
        $this->assertSame($this->serverAccountId, $signedAssoc[0]->credentials->getAddressCredentials()->address->toStrKey());
        $this->assertSame($this->clientContractId, $signedAssoc[1]->credentials->getAddressCredentials()->address->toStrKey());
        $this->assertSame($clientDomainAccount, $signedAssoc[2]->credentials->getAddressCredentials()->address->toStrKey());
        $this->assertTrue($clientDomainKeyPair->verifySignature(
            $this->extractFirstSignatureBytes($signedAssoc[2]->credentials->getAddressCredentials()->signature),
            Hash::generate($signedAssoc[2]->buildPreimage(Network::testnet())->encode())
        ));
    }

    public function testZeroStringTruthinessHandlingInHomeDomainAndTomlFields(): void
    {
        // serverHomeDomain "0" must be accepted (empty("0") is true in PHP)
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            '0',
            'auth.example.stellar.org',
            $this->serverAccountId,
            'zero-home-domain-nonce'
        );
        $mock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, '0', Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $this->assertSame($this->successJWTToken, $webAuth->jwtToken($this->clientContractId, [KeyPair::random()]));

        // fromDomain() must treat "0" in TOML fields as present (invalid value), not absent
        $zeroTomlCases = [
            'endpoint is 0' => [
                "WEB_AUTH_FOR_CONTRACTS_ENDPOINT = \"0\"\nWEB_AUTH_CONTRACT_ID = \"{$this->webAuthContractId}\"\nSIGNING_KEY = \"{$this->serverAccountId}\"\n",
                'Service URL must use HTTPS. HTTP is only allowed for localhost.',
            ],
            'contract id is 0' => [
                "WEB_AUTH_FOR_CONTRACTS_ENDPOINT = \"{$this->authServer}\"\nWEB_AUTH_CONTRACT_ID = \"0\"\nSIGNING_KEY = \"{$this->serverAccountId}\"\n",
                'webAuthContractId must be a valid contract address (C...)',
            ],
            'signing key is 0' => [
                "WEB_AUTH_FOR_CONTRACTS_ENDPOINT = \"{$this->authServer}\"\nWEB_AUTH_CONTRACT_ID = \"{$this->webAuthContractId}\"\nSIGNING_KEY = \"0\"\n",
                'serverSigningKey must be a valid ed25519 account address (G...)',
            ],
        ];
        foreach ($zeroTomlCases as $label => [$toml, $expectedMessage]) {
            $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200, [], $toml)]))]);
            try {
                WebAuthForContracts::fromDomain($this->domain, Network::testnet(), $client);
                $this->fail("Expected InvalidArgumentException for $label");
            } catch (InvalidArgumentException $e) {
                $this->assertSame($expectedMessage, $e->getMessage());
            }
        }
    }

    public function testInvalidAndPublicOnlySignersRejectedBeforeGet(): void
    {
        $publicOnlyKeyPair = KeyPair::fromAccountId($this->serverAccountId);
        $validKeyPair = KeyPair::random();
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );

        $markerValue = 'UNEXPECTED-SIGNER-MARKER-VALUE';
        $invalidSignerLists = [
            'string marker element' => [[$markerValue], 'All signers must be KeyPair instances, got string'],
            'integer element' => [[42], 'All signers must be KeyPair instances, got int'],
            'null element' => [[null], 'All signers must be KeyPair instances, got null'],
            'mixed valid and non-KeyPair' => [[$validKeyPair, 'bad'], 'All signers must be KeyPair instances, got string'],
            'public-only KeyPair' => [[$publicOnlyKeyPair], "Signer KeyPair for account {$this->serverAccountId} must include a private key"],
            'mixed valid and public-only KeyPair' => [[$validKeyPair, $publicOnlyKeyPair], "Signer KeyPair for account {$this->serverAccountId} must include a private key"],
        ];

        foreach ($invalidSignerLists as $label => [$signers, $expectedMessage]) {
            $mock = new MockHandler([
                new Response(200, [], json_encode(['authorization_entries' => 'AAAAAA=='])),
            ]);
            $webAuth->setMockHandler($mock);

            $error = null;
            try {
                $webAuth->jwtToken($this->clientContractId, $signers, $this->domain);
            } catch (InvalidArgumentException $e) {
                $error = $e;
            }

            $this->assertNotNull($error, "jwtToken must reject signers case '$label'");
            $this->assertSame($expectedMessage, $error->getMessage());
            $this->assertStringNotContainsString($markerValue, $error->getMessage());
            $this->assertCount(1, $mock, "Case '$label' must not send challenge GET");
            $this->assertNull($mock->getLastRequest());

            // Direct signAuthorizationEntries() must reject the same invalid signers
            $directError = null;
            try {
                $webAuth->signAuthorizationEntries([], $this->clientContractId, $signers, 500000);
            } catch (InvalidArgumentException $e) {
                $directError = $e;
            }
            $this->assertNotNull($directError, "signAuthorizationEntries must reject signers case '$label'");
            $this->assertSame($expectedMessage, $directError->getMessage());
        }
    }

    public function testWebAuthDomainComparedCaseInsensitivelyWithEndpointHost(): void
    {
        $cases = [
            'uppercase endpoint host vs lowercase arg' => [
                'https://Auth.Example.Stellar.Org/sep45',
                'auth.example.stellar.org',
            ],
            'lowercase endpoint host vs uppercase arg' => [
                'https://auth.example.stellar.org/sep45',
                'AUTH.EXAMPLE.STELLAR.ORG',
            ],
            'mixed case host with explicit port' => [
                'https://Auth.Example.Stellar.Org:8443/sep45',
                'auth.example.stellar.org:8443',
            ],
        ];

        foreach ($cases as $label => [$endpoint, $webAuthDomainArg]) {
            $challengeXdr = $this->buildValidChallenge(
                $this->clientContractId,
                $this->domain,
                $webAuthDomainArg,
                $this->serverAccountId,
                'case-host-nonce'
            );
            $mock = new MockHandler([
                new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
                new Response(200, [], json_encode(['token' => $this->successJWTToken])),
            ]);
            $webAuth = new WebAuthForContracts(
                $endpoint, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
            );
            $webAuth->setMockHandler($mock);
            $webAuth->setSorobanServer($this->createMockSorobanServer());

            $this->assertSame(
                $this->successJWTToken,
                $webAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain),
                "Case '$label' must succeed"
            );
        }

        // Mismatched port is still rejected
        $wrongPortChallenge = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org:9443',
            $this->serverAccountId,
            'wrong-port-nonce'
        );
        $webAuthPort = new WebAuthForContracts(
            'https://Auth.Example.Stellar.Org:8443/sep45',
            $this->webAuthContractId,
            $this->serverAccountId,
            $this->domain,
            Network::testnet()
        );
        $entries = $webAuthPort->decodeAuthorizationEntries($wrongPortChallenge);
        try {
            $webAuthPort->validateChallenge($entries, $this->clientContractId, $this->domain);
            $this->fail('Expected ContractChallengeValidationErrorInvalidWebAuthDomain for mismatched port');
        } catch (ContractChallengeValidationErrorInvalidWebAuthDomain $e) {
            $this->assertSame('Web auth domain argument does not match server domain', $e->getMessage());
        }
    }

    public function testChallengeEntriesInOtherOrdersSucceed(): void
    {
        // Case 1: client entry before server entry
        $argsMap = $this->buildArgsMap(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId, 'reorder-2-nonce'
        );
        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12345, 1000000
        );
        $serverEntry->sign($this->serverKeyPair, Network::testnet());
        $expectedServerXdr = $serverEntry->toBase64Xdr();
        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12346, 1000000
        );

        $mock2 = new MockHandler([
            new Response(200, [], json_encode([
                'authorization_entries' => $this->encodeAuthEntries([$clientEntry, $serverEntry]),
            ])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock2);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $clientSigner = KeyPair::random();
        $this->assertSame($this->successJWTToken, $webAuth->jwtToken($this->clientContractId, [$clientSigner], $this->domain));
        $this->assertCount(0, $mock2);
        parse_str((string) $mock2->getLastRequest()->getBody(), $postBody2);
        $submitted2 = $webAuth->decodeAuthorizationEntries($postBody2['authorization_entries']);
        $this->assertCount(2, $submitted2);
        $this->assertSame($this->clientContractId, $submitted2[0]->credentials->getAddressCredentials()->address->toStrKey());
        $this->assertSame($expectedServerXdr, $submitted2[1]->toBase64Xdr());
        $this->assertSame(123466, $submitted2[0]->credentials->getAddressCredentials()->signatureExpirationLedger);
        $this->assertTrue($clientSigner->verifySignature(
            $this->extractFirstSignatureBytes($submitted2[0]->credentials->getAddressCredentials()->signature),
            Hash::generate($submitted2[0]->buildPreimage(Network::testnet())->encode())
        ));

        // Case 2: [clientDomain, client, server] order
        $cdKeyPair = KeyPair::random();
        $argsMap3 = $this->buildArgsMap(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId, 'reorder-3-nonce',
            'client.example.com', $cdKeyPair->getAccountId()
        );
        $serverEntry3 = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $argsMap3, 12345, 1000000
        );
        $serverEntry3->sign($this->serverKeyPair, Network::testnet());
        $expectedServer3Xdr = $serverEntry3->toBase64Xdr();
        $clientEntry3 = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $argsMap3, 12346, 1000000
        );
        $cdEntry3 = $this->buildAuthEntry(
            $cdKeyPair->getAccountId(), $this->webAuthContractId, 'web_auth_verify', $argsMap3, 12347, 1000000
        );

        $mock3 = new MockHandler([
            new Response(200, [], json_encode([
                'authorization_entries' => $this->encodeAuthEntries([$cdEntry3, $clientEntry3, $serverEntry3]),
            ])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth->setMockHandler($mock3);
        $this->assertSame(
            $this->successJWTToken,
            $webAuth->jwtToken($this->clientContractId, [$clientSigner], $this->domain, 'client.example.com', $cdKeyPair)
        );
        $this->assertCount(0, $mock3);
        parse_str((string) $mock3->getLastRequest()->getBody(), $postBody3);
        $submitted3 = $webAuth->decodeAuthorizationEntries($postBody3['authorization_entries']);
        $this->assertCount(3, $submitted3);
        $this->assertSame($cdKeyPair->getAccountId(), $submitted3[0]->credentials->getAddressCredentials()->address->toStrKey());
        $this->assertSame($this->clientContractId, $submitted3[1]->credentials->getAddressCredentials()->address->toStrKey());
        $this->assertSame($expectedServer3Xdr, $submitted3[2]->toBase64Xdr());
        $this->assertTrue($cdKeyPair->verifySignature(
            $this->extractFirstSignatureBytes($submitted3[0]->credentials->getAddressCredentials()->signature),
            Hash::generate($submitted3[0]->buildPreimage(Network::testnet())->encode())
        ));
        $this->assertTrue($clientSigner->verifySignature(
            $this->extractFirstSignatureBytes($submitted3[1]->credentials->getAddressCredentials()->signature),
            Hash::generate($submitted3[1]->buildPreimage(Network::testnet())->encode())
        ));
    }

    public function testMultipleClientSignersSuccess(): void
    {
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'multi-client-signers-nonce'
        );
        $mock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $expectedServerXdr = $webAuth->decodeAuthorizationEntries($challengeXdr)[0]->toBase64Xdr();
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $signer1 = KeyPair::random();
        $signer2 = KeyPair::random();
        $signer3 = KeyPair::random();

        $token = $webAuth->jwtToken($this->clientContractId, [$signer1, $signer2, $signer3], $this->domain);
        $this->assertSame($this->successJWTToken, $token);
        $this->assertCount(0, $mock);

        parse_str((string) $mock->getLastRequest()->getBody(), $postBody);
        $submitted = $webAuth->decodeAuthorizationEntries($postBody['authorization_entries']);
        $this->assertCount(2, $submitted);
        $this->assertSame($expectedServerXdr, $submitted[0]->toBase64Xdr());

        $clientCreds = $submitted[1]->credentials->getAddressCredentials();
        $this->assertSame(123466, $clientCreds->signatureExpirationLedger);
        $this->assertCount(3, $clientCreds->signature->vec);

        $payload = Hash::generate($submitted[1]->buildPreimage(Network::testnet())->encode());
        foreach ([$signer1, $signer2, $signer3] as $idx => $expectedSigner) {
            $pubKey = null;
            $sigBytes = null;
            foreach ($clientCreds->signature->vec[$idx]->map ?? [] as $mapEntry) {
                if ($mapEntry->key->sym === 'public_key') {
                    $pubKey = $mapEntry->val->bytes?->getValue();
                } elseif ($mapEntry->key->sym === 'signature') {
                    $sigBytes = $mapEntry->val->bytes?->getValue();
                }
            }
            $this->assertSame($expectedSigner->getPublicKey(), $pubKey);
            $this->assertNotNull($sigBytes);
            $this->assertTrue($expectedSigner->verifySignature($sigBytes, $payload));
        }
    }

    public function testFromDomainSuccessAndMissingFields(): void
    {
        $tomlBody = "WEB_AUTH_FOR_CONTRACTS_ENDPOINT = \"{$this->authServer}\"\n"
            . "WEB_AUTH_CONTRACT_ID = \"{$this->webAuthContractId}\"\n"
            . "SIGNING_KEY = \"{$this->serverAccountId}\"\n";
        $rpcProp = new ReflectionProperty(WebAuthForContracts::class, 'sorobanRpcUrl');

        // Testnet discovery and full flow
        $testnetChallenge = $this->buildValidChallenge(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId, 'from-domain-testnet'
        );
        $testnetRequests = [];
        $testnetResponses = [
            new Response(200, [], $tomlBody),
            new Response(200, [], json_encode(['authorization_entries' => $testnetChallenge])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ];
        $testnetClient = new Client(['handler' => HandlerStack::create(
            function (RequestInterface $request) use (&$testnetRequests, &$testnetResponses): PromiseInterface {
                $testnetRequests[] = ['method' => $request->getMethod(), 'uri' => (string) $request->getUri()];
                return Create::promiseFor(array_shift($testnetResponses));
            }
        )]);
        $testnetAuth = WebAuthForContracts::fromDomain($this->domain, Network::testnet(), $testnetClient);
        $this->assertSame('https://soroban-testnet.stellar.org', $rpcProp->getValue($testnetAuth));
        $testnetAuth->setSorobanServer($this->createMockSorobanServer());
        $this->assertSame($this->successJWTToken, $testnetAuth->jwtToken($this->clientContractId, [KeyPair::random()]));
        $this->assertSame('https://example.stellar.org/.well-known/stellar.toml', $testnetRequests[0]['uri']);

        // Public network discovery without sorobanRpcUrl throws InvalidArgumentException
        $pubFailClient = new Client(['handler' => HandlerStack::create(new MockHandler([
            new Response(200, [], $tomlBody),
        ]))]);
        try {
            WebAuthForContracts::fromDomain($this->domain, Network::public(), $pubFailClient);
            $this->fail('Expected InvalidArgumentException when fromDomain() omits sorobanRpcUrl on public network');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                "sorobanRpcUrl is required for network passphrase 'Public Global Stellar Network ; September 2015'; only testnet has a default",
                $e->getMessage()
            );
        }

        // Public network discovery with explicit sorobanRpcUrl succeeds and completes full flow
        $pubRpcUrl = 'https://rpc.mainnet.example.com';
        $pubArgs = $this->buildArgsMap(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId, 'from-domain-pubnet'
        );
        $pubServer = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $pubArgs, 12345, 1000000
        );
        $pubServer->sign($this->serverKeyPair, Network::public());
        $pubClient = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $pubArgs, 12346, 1000000
        );
        $pubHttpClient = new Client(['handler' => HandlerStack::create(new MockHandler([
            new Response(200, [], $tomlBody),
            new Response(200, [], json_encode([
                'authorization_entries' => $this->encodeAuthEntries([$pubServer, $pubClient]),
                'network_passphrase' => Network::public()->getNetworkPassphrase(),
            ])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]))]);
        $publicAuth = WebAuthForContracts::fromDomain($this->domain, Network::public(), $pubHttpClient, $pubRpcUrl);
        $this->assertSame($pubRpcUrl, $rpcProp->getValue($publicAuth));
        $publicAuth->setSorobanServer($this->createMockSorobanServer());
        $this->assertSame($this->successJWTToken, $publicAuth->jwtToken($this->clientContractId, [KeyPair::random()]));

        // Missing or empty required fields in stellar.toml
        $missingCases = [
            'missing endpoint' => [
                "WEB_AUTH_CONTRACT_ID = \"{$this->webAuthContractId}\"\nSIGNING_KEY = \"{$this->serverAccountId}\"\n",
                'No WEB_AUTH_FOR_CONTRACTS_ENDPOINT found in stellar.toml',
            ],
            'empty endpoint' => [
                "WEB_AUTH_FOR_CONTRACTS_ENDPOINT = \"\"\nWEB_AUTH_CONTRACT_ID = \"{$this->webAuthContractId}\"\nSIGNING_KEY = \"{$this->serverAccountId}\"\n",
                'No WEB_AUTH_FOR_CONTRACTS_ENDPOINT found in stellar.toml',
            ],
            'missing contract id' => [
                "WEB_AUTH_FOR_CONTRACTS_ENDPOINT = \"{$this->authServer}\"\nSIGNING_KEY = \"{$this->serverAccountId}\"\n",
                'No WEB_AUTH_CONTRACT_ID found in stellar.toml',
            ],
            'empty contract id' => [
                "WEB_AUTH_FOR_CONTRACTS_ENDPOINT = \"{$this->authServer}\"\nWEB_AUTH_CONTRACT_ID = \"\"\nSIGNING_KEY = \"{$this->serverAccountId}\"\n",
                'No WEB_AUTH_CONTRACT_ID found in stellar.toml',
            ],
            'missing signing key' => [
                "WEB_AUTH_FOR_CONTRACTS_ENDPOINT = \"{$this->authServer}\"\nWEB_AUTH_CONTRACT_ID = \"{$this->webAuthContractId}\"\n",
                'No auth server SIGNING_KEY found in stellar.toml',
            ],
            'empty signing key' => [
                "WEB_AUTH_FOR_CONTRACTS_ENDPOINT = \"{$this->authServer}\"\nWEB_AUTH_CONTRACT_ID = \"{$this->webAuthContractId}\"\nSIGNING_KEY = \"\"\n",
                'No auth server SIGNING_KEY found in stellar.toml',
            ],
        ];
        foreach ($missingCases as $label => [$toml, $expectedMessage]) {
            $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200, [], $toml)]))]);
            try {
                WebAuthForContracts::fromDomain($this->domain, Network::testnet(), $client);
                $this->fail("Expected Exception for $label");
            } catch (Exception $e) {
                $this->assertSame($expectedMessage, $e->getMessage());
            }
        }
    }

    public function testInvalidClientDomainTomlAndDomainErrorsSendNoPost(): void
    {
        $clientDomainAccount = KeyPair::random()->getAccountId();
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $callbackCalls = 0;
        $callback = function (SorobanAuthorizationEntry $entry) use (&$callbackCalls): SorobanAuthorizationEntry {
            $callbackCalls++;
            return $entry;
        };

        // Case 1: client domain stellar.toml returns 404
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId,
            'cd-404-nonce', 'client.example.com', $clientDomainAccount
        );
        $mock404 = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(404, [], 'Not Found'),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth->setMockHandler($mock404);
        try {
            $webAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain, 'client.example.com', null, $callback);
            $this->fail('Expected InvalidArgumentException when client domain stellar.toml returns 404');
        } catch (InvalidArgumentException $e) {
            $this->assertStringStartsWith('Invalid client domain: Stellar toml not found.', $e->getMessage());
        }
        $this->assertCount(1, $mock404, 'Token POST must not be sent after TOML 404');
        $this->assertSame(0, $callbackCalls);

        // Case 2 & 3: client domain stellar.toml missing SIGNING_KEY or empty SIGNING_KEY
        foreach (['missing' => 'VERSION = "2.0.0"', 'empty' => 'SIGNING_KEY = ""'] as $label => $tomlContent) {
            $mockNoKey = new MockHandler([
                new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
                new Response(200, [], $tomlContent),
                new Response(200, [], json_encode(['token' => $this->successJWTToken])),
            ]);
            $webAuth->setMockHandler($mockNoKey);
            try {
                $webAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain, 'client.example.com', null, $callback);
                $this->fail("Expected InvalidArgumentException when client domain stellar.toml has $label SIGNING_KEY");
            } catch (InvalidArgumentException $e) {
                $this->assertSame('Invalid client domain: Could not find signing key in stellar.toml', $e->getMessage());
            }
            $this->assertCount(1, $mockNoKey, "Token POST must not be sent for $label SIGNING_KEY");
            $this->assertSame(0, $callbackCalls);
        }

        // Case 4: client domain stellar.toml with SIGNING_KEY = "0" is treated as present (fails G-address validation, not missing)
        $mockZeroKey = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(200, [], 'SIGNING_KEY = "0"'),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth->setMockHandler($mockZeroKey);
        try {
            $webAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain, 'client.example.com', null, $callback);
            $this->fail('Expected InvalidArgumentException for SIGNING_KEY = "0"');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                'Invalid client domain: SIGNING_KEY in stellar.toml must be a valid ed25519 account address (G...)',
                $e->getMessage()
            );
            $this->assertStringNotContainsString('0', $e->getMessage());
        }
        $this->assertCount(1, $mockZeroKey);
        $this->assertSame(0, $callbackCalls);

        // Case 5: unsafe client domain rejected before TOML lookup or POST
        $unsafeDomain = 'client.example.com/evil';
        $unsafeChallenge = $this->buildValidChallenge(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId,
            'cd-unsafe-nonce', $unsafeDomain, $clientDomainAccount
        );
        $mockUnsafe = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $unsafeChallenge])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth->setMockHandler($mockUnsafe);
        try {
            $webAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain, $unsafeDomain, null, $callback);
            $this->fail('Expected InvalidArgumentException for unsafe client domain');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Invalid client domain: Invalid domain: contains unsafe characters', $e->getMessage());
        }
        $this->assertCount(1, $mockUnsafe, 'No TOML request or POST sent for unsafe client domain');
        $this->assertSame(0, $callbackCalls);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function allowedSimulationFootprintCases(): array
    {
        return [
            'empty read_write' => ['empty'],
            'client nonce only with different nonce value' => ['client_nonce_different_value'],
            'server nonce only' => ['server_nonce_only'],
            'client and server nonces' => ['client_and_server'],
            'client, server, and web auth contract instance marked for restoration' => ['client_server_instance'],
            'web auth contract instance marked for restoration only' => ['instance_only'],
            'client, server, and client domain nonces' => ['client_server_cd'],
            'all four contract_data entries with instance marked for restoration' => ['all_four'],
            'nonces plus instance and contract code marked for restoration' => ['client_server_instance_and_code'],
            'client contract instance marked for restoration only' => ['client_instance_only'],
            'nonces plus listed persistent client data entry (symbol key)' => ['client_server_and_client_persistent_data'],
            'nonces plus listed persistent client data entry (vec key)' => ['client_server_and_client_persistent_vec_data'],
            'nonces plus client contract instance and contract code marked for restoration' => ['client_server_client_instance_and_code'],
            'nonces plus client instance, web auth instance, and contract code marked for restoration' => ['client_server_both_instances_and_code'],
            'nonces including client domain plus instance and contract code marked for restoration' => ['all_five_with_code'],
            'contract code marked for restoration only' => ['code_only'],
            'v1 ext with empty archivedSorobanEntries and nonce entries' => ['v1_ext_empty_archived'],
            'arbitrary read_only entries allowed' => ['arbitrary_read_only'],
            'persistent durability on nonce key' => ['persistent_nonce_durability'],
        ];
    }

    #[DataProvider('allowedSimulationFootprintCases')]
    public function testVerifySimulationAllowedFootprintSubsetsSucceed(string $case): void
    {
        $clientSigner = KeyPair::random();
        $clientDomainKeyPair = KeyPair::random();
        $clientDomainAccount = $clientDomainKeyPair->getAccountId();
        $withClientDomain = in_array($case, ['client_server_cd', 'all_four', 'all_five_with_code'], true);

        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'sim-allow-' . $case,
            $withClientDomain ? 'client.example.com' : null,
            $withClientDomain ? $clientDomainAccount : null
        );

        $otherContractId = 'CCJCTOZFKPNTFLMORB7RBNKDQU42PBKGVTI4DIWVEMUCXRHWCYXGRRV7';
        $readWrite = [];
        $readOnly = [];
        $archivedSorobanEntries = null;

        switch ($case) {
            case 'empty':
                break;
            case 'client_nonce_different_value':
                $readWrite = [$this->buildNonceContractDataKey($this->clientContractId, 999999)];
                break;
            case 'server_nonce_only':
                $readWrite = [$this->buildNonceContractDataKey($this->serverAccountId, 12345)];
                break;
            case 'client_and_server':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                ];
                break;
            case 'client_server_instance':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    $this->buildContractInstanceKey($this->webAuthContractId),
                ];
                $archivedSorobanEntries = [2];
                break;
            case 'instance_only':
                $readWrite = [$this->buildContractInstanceKey($this->webAuthContractId)];
                $archivedSorobanEntries = [0];
                break;
            case 'client_server_cd':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    $this->buildNonceContractDataKey($clientDomainAccount, 12347),
                ];
                break;
            case 'all_four':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    $this->buildNonceContractDataKey($clientDomainAccount, 12347),
                    $this->buildContractInstanceKey($this->webAuthContractId),
                ];
                $archivedSorobanEntries = [3];
                break;
            case 'client_server_instance_and_code':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    $this->buildContractInstanceKey($this->webAuthContractId),
                    XdrLedgerKey::forContractCode(str_repeat("\x11", 32)),
                ];
                $archivedSorobanEntries = [2, 3];
                break;
            case 'client_instance_only':
                $readWrite = [$this->buildContractInstanceKey($this->clientContractId)];
                $archivedSorobanEntries = [0];
                break;
            case 'client_server_and_client_persistent_data':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    XdrLedgerKey::forContractData(
                        XdrSCAddress::forContractId($this->clientContractId),
                        XdrSCVal::forSymbol('admin'),
                        XdrContractDataDurability::PERSISTENT()
                    ),
                ];
                $archivedSorobanEntries = [2];
                break;
            case 'client_server_and_client_persistent_vec_data':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    XdrLedgerKey::forContractData(
                        XdrSCAddress::forContractId($this->clientContractId),
                        XdrSCVal::forVec([XdrSCVal::forSymbol('Signer'), XdrSCVal::forU32(1)]),
                        XdrContractDataDurability::PERSISTENT()
                    ),
                ];
                $archivedSorobanEntries = [2];
                break;
            case 'client_server_client_instance_and_code':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    $this->buildContractInstanceKey($this->clientContractId),
                    XdrLedgerKey::forContractCode(str_repeat("\x11", 32)),
                ];
                $archivedSorobanEntries = [2, 3];
                break;
            case 'client_server_both_instances_and_code':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    $this->buildContractInstanceKey($this->clientContractId),
                    $this->buildContractInstanceKey($this->webAuthContractId),
                    XdrLedgerKey::forContractCode(str_repeat("\x11", 32)),
                ];
                $archivedSorobanEntries = [2, 3, 4];
                break;
            case 'all_five_with_code':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    $this->buildNonceContractDataKey($clientDomainAccount, 12347),
                    $this->buildContractInstanceKey($this->webAuthContractId),
                    XdrLedgerKey::forContractCode(str_repeat("\x11", 32)),
                ];
                $archivedSorobanEntries = [3, 4];
                break;
            case 'code_only':
                $readWrite = [XdrLedgerKey::forContractCode(str_repeat("\x11", 32))];
                $archivedSorobanEntries = [0];
                break;
            case 'v1_ext_empty_archived':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                ];
                $archivedSorobanEntries = [];
                break;
            case 'arbitrary_read_only':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                ];
                $readOnly = [
                    XdrLedgerKey::forAccountId($this->serverAccountId),
                    XdrLedgerKey::forContractCode(str_repeat("\x11", 32)),
                    XdrLedgerKey::forTrustLine(
                        $this->serverAccountId,
                        Asset::createNonNativeAsset('USDC', $this->serverAccountId)->toXdr()
                    ),
                    XdrLedgerKey::forContractData(
                        XdrSCAddress::forContractId($otherContractId),
                        XdrSCVal::forSymbol('state'),
                        XdrContractDataDurability::PERSISTENT()
                    ),
                    XdrLedgerKey::forTTL(str_repeat("\x22", 32)),
                ];
                break;
            case 'persistent_nonce_durability':
                $readWrite = [
                    $this->buildNonceContractDataKey(
                        $this->clientContractId,
                        12346,
                        XdrContractDataDurability::PERSISTENT()
                    ),
                ];
                break;
        }

        $simResponse = $this->buildSimulationResponse(
            $readWrite,
            $readOnly,
            archivedSorobanEntries: $archivedSorobanEntries
        );
        $capturedRequest = null;

        $latestLedger = new GetLatestLedgerResponse([]);
        $latestLedger->sequence = 123456;
        $sorobanServer = $this->createMock(SorobanServer::class);
        $sorobanServer->expects($this->once())->method('getLatestLedger')->willReturn($latestLedger);
        $sorobanServer->expects($this->once())
            ->method('simulateTransaction')
            ->willReturnCallback(function (SimulateTransactionRequest $req) use (&$capturedRequest, $simResponse): SimulateTransactionResponse {
                $capturedRequest = $req;
                return $simResponse;
            });

        $mock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);

        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($sorobanServer);

        $token = $webAuth->jwtToken(
            $this->clientContractId,
            [$clientSigner],
            $this->domain,
            $withClientDomain ? 'client.example.com' : null,
            $withClientDomain ? $clientDomainKeyPair : null
        );

        $this->assertSame($this->successJWTToken, $token);
        $this->assertCount(0, $mock);
        $this->assertSame('POST', $mock->getLastRequest()->getMethod());
        parse_str((string) $mock->getLastRequest()->getBody(), $postBody);
        $submittedEntries = $webAuth->decodeAuthorizationEntries($postBody['authorization_entries']);
        $submittedXdrs = array_map(
            static fn (SorobanAuthorizationEntry $entry): string => $entry->toBase64Xdr(),
            $submittedEntries
        );

        $this->assertNotNull($capturedRequest);
        $this->assertSame('enforce', $capturedRequest->getAuthMode());
        $this->assertSame('enforce', $capturedRequest->getRequestParams()['authMode']);
        $decodedTx = Transaction::fromEnvelopeBase64XdrString(
            $capturedRequest->getTransaction()->toEnvelopeXdrBase64()
        );
        $this->assertInstanceOf(Transaction::class, $decodedTx);
        $this->assertSame(
            'GAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWHF',
            $decodedTx->getSourceAccount()->getAccountId()
        );
        $this->assertSame('0', $decodedTx->getSequenceNumber()->toString());
        $this->assertCount(1, $decodedTx->getOperations());

        $op = $decodedTx->getOperations()[0];
        $this->assertInstanceOf(InvokeHostFunctionOperation::class, $op);
        $this->assertInstanceOf(InvokeContractHostFunction::class, $op->function);
        $this->assertSame($this->webAuthContractId, $op->function->contractId);
        $this->assertSame('web_auth_verify', $op->function->functionName);

        $expectedArgsXdrs = array_map(
            static fn (XdrSCVal $arg): string => $arg->toBase64Xdr(),
            $submittedEntries[0]->rootInvocation->function->contractFn->args
        );
        $actualArgsXdrs = array_map(
            static fn (XdrSCVal $arg): string => $arg->toBase64Xdr(),
            $op->function->arguments ?? []
        );
        $this->assertSame($expectedArgsXdrs, $actualArgsXdrs);

        $simulatedAuthXdrs = array_map(
            static fn (SorobanAuthorizationEntry $entry): string => $entry->toBase64Xdr(),
            $op->auth
        );
        $this->assertSame($submittedXdrs, $simulatedAuthXdrs);
        foreach ($op->auth as $authEntry) {
            $this->assertNotNull(
                $authEntry->credentials->getAddressCredentials(),
                'Simulated entries must keep address credentials (never source-account credentials)'
            );
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function forbiddenSimulationFootprintCases(): array
    {
        return [
            'another contract nonce' => ['other_contract_nonce'],
            'another contract symbol' => ['other_contract_symbol'],
            'another contract symbol listed for restoration' => ['other_contract_symbol_listed'],
            'another contract instance' => ['other_contract_instance'],
            'another contract instance listed for restoration' => ['other_contract_instance_listed'],
            'another G account nonce' => ['other_account_nonce'],
            'client account symbol key unlisted (ext discriminant 0)' => ['client_symbol'],
            'client account symbol key unlisted (ext discriminant 1)' => ['client_symbol_unlisted_v1'],
            'temporary client account symbol key listed for restoration' => ['client_symbol_temporary_listed'],
            'client contract instance not listed (ext discriminant 0)' => ['client_instance_unlisted_v0'],
            'client contract instance not listed (ext discriminant 1)' => ['client_instance_unlisted_v1'],
            'temporary client contract instance listed for restoration' => ['client_instance_temporary_listed'],
            'server account symbol key' => ['server_symbol'],
            'server account instance key' => ['server_instance'],
            'web auth contract symbol key' => ['web_auth_symbol'],
            'web auth contract symbol key listed for restoration' => ['web_auth_symbol_listed'],
            'web auth contract nonce key' => ['web_auth_nonce'],
            'web auth contract instance not listed (ext discriminant 0)' => ['web_auth_instance_unlisted_v0'],
            'web auth contract instance not listed (ext discriminant 1)' => ['web_auth_instance_unlisted_v1'],
            'temporary web auth contract instance listed for restoration' => ['web_auth_instance_temporary_listed'],
            'client domain account symbol key' => ['cd_symbol'],
            'client domain account instance key' => ['cd_instance'],
            'client domain account instance key listed for restoration' => ['cd_instance_listed'],
            'client domain nonce when no client domain requested' => ['cd_nonce_not_requested'],
            'client domain nonce when server ignored client domain' => ['cd_nonce_server_ignored'],
            'client domain nonce when server ignored domain with extra unsigned entry' => ['cd_nonce_server_ignored_extra_entry'],
            'contract code with ext discriminant 0' => ['contract_code'],
            'contract code not listed with ext discriminant 1' => ['contract_code_unlisted_v1'],
            'temporary nonce listed in archivedSorobanEntries' => ['temporary_nonce_listed'],
            'out-of-bounds index in archivedSorobanEntries' => ['archived_index_out_of_bounds'],
            'duplicate index in archivedSorobanEntries' => ['archived_index_duplicate'],
            'non-increasing indices in archivedSorobanEntries' => ['archived_index_non_increasing'],
            'account entry' => ['account'],
            'account entry listed for restoration' => ['account_listed'],
            'trustline entry' => ['trustline'],
            'ttl entry' => ['ttl'],
            'allowed entries plus one disallowed entry' => ['allowed_plus_disallowed'],
        ];
    }

    #[DataProvider('forbiddenSimulationFootprintCases')]
    public function testVerifySimulationRejectsForbiddenFootprintEntriesWithoutPost(string $case): void
    {
        $clientSigner = KeyPair::random();
        $clientDomainKeyPair = KeyPair::random();
        $clientDomainAccount = $clientDomainKeyPair->getAccountId();
        $otherGAccount = KeyPair::random()->getAccountId();
        $otherContractId = 'CCJCTOZFKPNTFLMORB7RBNKDQU42PBKGVTI4DIWVEMUCXRHWCYXGRRV7';

        $requestClientDomain = in_array(
            $case,
            ['cd_symbol', 'cd_instance', 'cd_instance_listed', 'cd_nonce_server_ignored', 'cd_nonce_server_ignored_extra_entry'],
            true
        );
        $serverIncludesClientDomain = in_array($case, ['cd_symbol', 'cd_instance', 'cd_instance_listed'], true);

        if ($case === 'cd_nonce_server_ignored_extra_entry') {
            $argsMap = $this->buildArgsMap(
                $this->clientContractId,
                $this->domain,
                'auth.example.stellar.org',
                $this->serverAccountId,
                'sim-reject-' . $case
            );
            $serverEntry = $this->buildAuthEntry(
                $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12345, 1000000
            );
            $serverEntry->sign($this->serverKeyPair, Network::testnet());
            $clientEntry = $this->buildAuthEntry(
                $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12346, 1000000
            );
            $extraCdEntry = $this->buildAuthEntry(
                $clientDomainAccount, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12347, 1000000
            );
            $challengeXdr = $this->encodeAuthEntries([$serverEntry, $clientEntry, $extraCdEntry]);
        } else {
            $challengeXdr = $this->buildValidChallenge(
                $this->clientContractId,
                $this->domain,
                'auth.example.stellar.org',
                $this->serverAccountId,
                'sim-reject-' . $case,
                $serverIncludesClientDomain ? 'client.example.com' : null,
                $serverIncludesClientDomain ? $clientDomainAccount : null
            );
        }

        $readWrite = [];
        $archivedSorobanEntries = null;
        $expectedMessage = '';

        switch ($case) {
            case 'other_contract_nonce':
                $readWrite = [$this->buildNonceContractDataKey($otherContractId, 1)];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed contract data entry for $otherContractId with key type SCV_LEDGER_KEY_NONCE";
                break;
            case 'other_contract_symbol':
                $readWrite = [XdrLedgerKey::forContractData(
                    XdrSCAddress::forContractId($otherContractId),
                    XdrSCVal::forSymbol('balance'),
                    XdrContractDataDurability::PERSISTENT()
                )];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed contract data entry for $otherContractId with key type SCV_SYMBOL";
                break;
            case 'other_contract_symbol_listed':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    XdrLedgerKey::forContractData(
                        XdrSCAddress::forContractId($otherContractId),
                        XdrSCVal::forSymbol('balance'),
                        XdrContractDataDurability::PERSISTENT()
                    ),
                ];
                $archivedSorobanEntries = [2];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed contract data entry for $otherContractId with key type SCV_SYMBOL";
                break;
            case 'other_contract_instance':
                $readWrite = [$this->buildContractInstanceKey($otherContractId)];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed contract data entry for $otherContractId with key type SCV_LEDGER_KEY_CONTRACT_INSTANCE";
                break;
            case 'other_contract_instance_listed':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    $this->buildContractInstanceKey($otherContractId),
                ];
                $archivedSorobanEntries = [2];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed contract data entry for $otherContractId with key type SCV_LEDGER_KEY_CONTRACT_INSTANCE";
                break;
            case 'other_account_nonce':
                $readWrite = [$this->buildNonceContractDataKey($otherGAccount, 1)];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed contract data entry for $otherGAccount with key type SCV_LEDGER_KEY_NONCE";
                break;
            case 'client_symbol':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    XdrLedgerKey::forContractData(
                        XdrSCAddress::forContractId($this->clientContractId),
                        XdrSCVal::forSymbol('admin'),
                        XdrContractDataDurability::PERSISTENT()
                    ),
                ];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed contract data entry for {$this->clientContractId} with key type SCV_SYMBOL";
                break;
            case 'client_symbol_unlisted_v1':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    XdrLedgerKey::forContractData(
                        XdrSCAddress::forContractId($this->clientContractId),
                        XdrSCVal::forSymbol('admin'),
                        XdrContractDataDurability::PERSISTENT()
                    ),
                    XdrLedgerKey::forContractCode(str_repeat("\x11", 32)),
                ];
                $archivedSorobanEntries = [3];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed contract data entry for {$this->clientContractId} with key type SCV_SYMBOL";
                break;
            case 'client_symbol_temporary_listed':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    XdrLedgerKey::forContractData(
                        XdrSCAddress::forContractId($this->clientContractId),
                        XdrSCVal::forSymbol('admin'),
                        XdrContractDataDurability::TEMPORARY()
                    ),
                ];
                $archivedSorobanEntries = [2];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed contract data entry for {$this->clientContractId} with key type SCV_SYMBOL";
                break;
            case 'client_instance_unlisted_v0':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    $this->buildContractInstanceKey($this->clientContractId),
                ];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed CONTRACT_DATA entry for {$this->clientContractId} with key type SCV_LEDGER_KEY_CONTRACT_INSTANCE (not marked for restoration)";
                break;
            case 'client_instance_unlisted_v1':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    $this->buildContractInstanceKey($this->clientContractId),
                    XdrLedgerKey::forContractCode(str_repeat("\x11", 32)),
                ];
                $archivedSorobanEntries = [3];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed CONTRACT_DATA entry for {$this->clientContractId} with key type SCV_LEDGER_KEY_CONTRACT_INSTANCE (not marked for restoration)";
                break;
            case 'client_instance_temporary_listed':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    $this->buildContractInstanceKey($this->clientContractId, XdrContractDataDurability::TEMPORARY()),
                ];
                $archivedSorobanEntries = [2];
                $expectedMessage = 'Transaction simulation read_write footprint contains malformed restoration entry: non-persistent CONTRACT_DATA with key type SCV_LEDGER_KEY_CONTRACT_INSTANCE cannot be marked for restoration';
                break;
            case 'server_symbol':
                $readWrite = [XdrLedgerKey::forContractData(
                    XdrSCAddress::forAccountId($this->serverAccountId),
                    XdrSCVal::forSymbol('state'),
                    XdrContractDataDurability::PERSISTENT()
                )];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed contract data entry for {$this->serverAccountId} with key type SCV_SYMBOL";
                break;
            case 'server_instance':
                $readWrite = [XdrLedgerKey::forContractData(
                    XdrSCAddress::forAccountId($this->serverAccountId),
                    XdrSCVal::forLedgerKeyContractInstance(),
                    XdrContractDataDurability::PERSISTENT()
                )];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed contract data entry for {$this->serverAccountId} with key type SCV_LEDGER_KEY_CONTRACT_INSTANCE";
                break;
            case 'web_auth_symbol':
                $readWrite = [XdrLedgerKey::forContractData(
                    XdrSCAddress::forContractId($this->webAuthContractId),
                    XdrSCVal::forSymbol('config'),
                    XdrContractDataDurability::PERSISTENT()
                )];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed contract data entry for {$this->webAuthContractId} with key type SCV_SYMBOL";
                break;
            case 'web_auth_symbol_listed':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    XdrLedgerKey::forContractData(
                        XdrSCAddress::forContractId($this->webAuthContractId),
                        XdrSCVal::forSymbol('config'),
                        XdrContractDataDurability::PERSISTENT()
                    ),
                ];
                $archivedSorobanEntries = [2];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed contract data entry for {$this->webAuthContractId} with key type SCV_SYMBOL";
                break;
            case 'web_auth_nonce':
                $readWrite = [$this->buildNonceContractDataKey($this->webAuthContractId, 1)];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed contract data entry for {$this->webAuthContractId} with key type SCV_LEDGER_KEY_NONCE";
                break;
            case 'web_auth_instance_unlisted_v0':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    $this->buildContractInstanceKey($this->webAuthContractId),
                ];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed CONTRACT_DATA entry for {$this->webAuthContractId} with key type SCV_LEDGER_KEY_CONTRACT_INSTANCE (not marked for restoration)";
                break;
            case 'web_auth_instance_unlisted_v1':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    $this->buildContractInstanceKey($this->webAuthContractId),
                    XdrLedgerKey::forContractCode(str_repeat("\x11", 32)),
                ];
                $archivedSorobanEntries = [3];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed CONTRACT_DATA entry for {$this->webAuthContractId} with key type SCV_LEDGER_KEY_CONTRACT_INSTANCE (not marked for restoration)";
                break;
            case 'web_auth_instance_temporary_listed':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    $this->buildContractInstanceKey($this->webAuthContractId, XdrContractDataDurability::TEMPORARY()),
                ];
                $archivedSorobanEntries = [2];
                $expectedMessage = 'Transaction simulation read_write footprint contains malformed restoration entry: non-persistent CONTRACT_DATA with key type SCV_LEDGER_KEY_CONTRACT_INSTANCE cannot be marked for restoration';
                break;
            case 'cd_symbol':
                $readWrite = [XdrLedgerKey::forContractData(
                    XdrSCAddress::forAccountId($clientDomainAccount),
                    XdrSCVal::forSymbol('data'),
                    XdrContractDataDurability::PERSISTENT()
                )];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed contract data entry for $clientDomainAccount with key type SCV_SYMBOL";
                break;
            case 'cd_instance':
                $readWrite = [XdrLedgerKey::forContractData(
                    XdrSCAddress::forAccountId($clientDomainAccount),
                    XdrSCVal::forLedgerKeyContractInstance(),
                    XdrContractDataDurability::PERSISTENT()
                )];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed contract data entry for $clientDomainAccount with key type SCV_LEDGER_KEY_CONTRACT_INSTANCE";
                break;
            case 'cd_instance_listed':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    $this->buildNonceContractDataKey($clientDomainAccount, 12347),
                    XdrLedgerKey::forContractData(
                        XdrSCAddress::forAccountId($clientDomainAccount),
                        XdrSCVal::forLedgerKeyContractInstance(),
                        XdrContractDataDurability::PERSISTENT()
                    ),
                ];
                $archivedSorobanEntries = [3];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed contract data entry for $clientDomainAccount with key type SCV_LEDGER_KEY_CONTRACT_INSTANCE";
                break;
            case 'cd_nonce_not_requested':
            case 'cd_nonce_server_ignored':
            case 'cd_nonce_server_ignored_extra_entry':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    $this->buildNonceContractDataKey($clientDomainAccount, 12347),
                ];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed contract data entry for $clientDomainAccount with key type SCV_LEDGER_KEY_NONCE";
                break;
            case 'contract_code':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    XdrLedgerKey::forContractCode(str_repeat("\x11", 32)),
                ];
                $expectedMessage = 'Transaction simulation read_write footprint contains disallowed ledger entry type: CONTRACT_CODE (not marked for restoration)';
                break;
            case 'contract_code_unlisted_v1':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    $this->buildContractInstanceKey($this->webAuthContractId),
                    XdrLedgerKey::forContractCode(str_repeat("\x11", 32)),
                ];
                $archivedSorobanEntries = [2];
                $expectedMessage = 'Transaction simulation read_write footprint contains disallowed ledger entry type: CONTRACT_CODE (not marked for restoration)';
                break;
            case 'temporary_nonce_listed':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                ];
                $archivedSorobanEntries = [0];
                $expectedMessage = 'Transaction simulation read_write footprint contains malformed restoration entry: CONTRACT_DATA with key type SCV_LEDGER_KEY_NONCE cannot be marked for restoration';
                break;
            case 'archived_index_out_of_bounds':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                ];
                $archivedSorobanEntries = [2];
                $expectedMessage = 'Transaction simulation transaction data contains an invalid archivedSorobanEntries index';
                break;
            case 'archived_index_duplicate':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    $this->buildContractInstanceKey($this->webAuthContractId),
                ];
                $archivedSorobanEntries = [2, 2];
                $expectedMessage = 'Transaction simulation transaction data archivedSorobanEntries indices must be strictly increasing';
                break;
            case 'archived_index_non_increasing':
                $readWrite = [
                    $this->buildContractInstanceKey($this->webAuthContractId),
                    XdrLedgerKey::forContractCode(str_repeat("\x11", 32)),
                ];
                $archivedSorobanEntries = [1, 0];
                $expectedMessage = 'Transaction simulation transaction data archivedSorobanEntries indices must be strictly increasing';
                break;
            case 'account':
                $readWrite = [XdrLedgerKey::forAccountId($this->serverAccountId)];
                $expectedMessage = 'Transaction simulation read_write footprint contains disallowed ledger entry type: ACCOUNT';
                break;
            case 'account_listed':
                $readWrite = [XdrLedgerKey::forAccountId($this->serverAccountId)];
                $archivedSorobanEntries = [0];
                $expectedMessage = 'Transaction simulation read_write footprint contains disallowed ledger entry type: ACCOUNT';
                break;
            case 'trustline':
                $readWrite = [XdrLedgerKey::forTrustLine(
                    $this->serverAccountId,
                    Asset::createNonNativeAsset('USDC', $this->serverAccountId)->toXdr()
                )];
                $expectedMessage = 'Transaction simulation read_write footprint contains disallowed ledger entry type: TRUSTLINE';
                break;
            case 'ttl':
                $readWrite = [XdrLedgerKey::forTTL(str_repeat("\x22", 32))];
                $expectedMessage = 'Transaction simulation read_write footprint contains disallowed ledger entry type: TTL';
                break;
            case 'allowed_plus_disallowed':
                $readWrite = [
                    $this->buildNonceContractDataKey($this->clientContractId, 12346),
                    $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    $this->buildContractInstanceKey($this->webAuthContractId),
                    XdrLedgerKey::forContractData(
                        XdrSCAddress::forContractId($otherContractId),
                        XdrSCVal::forSymbol('drain'),
                        XdrContractDataDurability::PERSISTENT()
                    ),
                ];
                $archivedSorobanEntries = [2];
                $expectedMessage = "Transaction simulation read_write footprint contains disallowed contract data entry for $otherContractId with key type SCV_SYMBOL";
                break;
        }

        $mock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);

        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer(
            $this->buildSimulationResponse($readWrite, archivedSorobanEntries: $archivedSorobanEntries)
        ));

        $error = null;
        try {
            $webAuth->jwtToken(
                $this->clientContractId,
                [$clientSigner],
                $this->domain,
                $requestClientDomain ? 'client.example.com' : null,
                $requestClientDomain ? $clientDomainKeyPair : null
            );
        } catch (ContractChallengeValidationErrorSimulationFailed $e) {
            $error = $e;
        }

        $this->assertNotNull($error, "Case '$case' must throw ContractChallengeValidationErrorSimulationFailed");
        $this->assertSame(ContractChallengeValidationErrorSimulationFailed::class, $error::class);
        $this->assertInstanceOf(ContractChallengeValidationError::class, $error);
        $this->assertSame($expectedMessage, $error->getMessage());
        $this->assertNull($error->getSimulationError());
        $this->assertNull($error->getRpcError());
        $this->assertCount(1, $mock, 'Token response must remain queued: no POST sent');
        $this->assertSame('GET', $mock->getLastRequest()->getMethod());
    }

    /**
     * @return array<string, array{string, string, ?string, ?string, bool, ?int, ?string}>
     */
    public static function simulationErrorResponseCases(): array
    {
        return [
            'resultError host error' => [
                'result_error_host',
                'Transaction simulation failed',
                'SIM-RESULT-ERROR-MARKER-DO-NOT-ECHO',
                'HostError: Error(Auth, InvalidAction) SIM-RESULT-ERROR-MARKER-DO-NOT-ECHO',
                false,
                null,
                null,
            ],
            'resultError zero string' => [
                'result_error_zero',
                'Transaction simulation failed',
                '0',
                '0',
                false,
                null,
                null,
            ],
            'resultError empty string' => [
                'result_error_empty',
                'Transaction simulation failed',
                null,
                '',
                false,
                null,
                null,
            ],
            'JSON-RPC error with code and message' => [
                'rpc_error_code_and_message',
                'Transaction simulation RPC error (code -32602)',
                'RPC-ERROR-MESSAGE-MARKER-DO-NOT-ECHO',
                null,
                true,
                -32602,
                'Invalid params RPC-ERROR-MESSAGE-MARKER-DO-NOT-ECHO',
            ],
            'JSON-RPC error with message only' => [
                'rpc_error_message_only',
                'Transaction simulation RPC error',
                'RPC-ERROR-MESSAGE-ONLY-MARKER',
                null,
                true,
                null,
                'Simulation unavailable RPC-ERROR-MESSAGE-ONLY-MARKER',
            ],
            'JSON-RPC error with zero string message' => [
                'rpc_error_zero_message',
                'Transaction simulation RPC error',
                '0',
                null,
                true,
                null,
                '0',
            ],
            'JSON-RPC error with code only' => [
                'rpc_error_code_only',
                'Transaction simulation RPC error (code -32000)',
                null,
                null,
                true,
                -32000,
                null,
            ],
            'JSON-RPC error without code or message' => [
                'rpc_error_empty',
                'Transaction simulation RPC error',
                null,
                null,
                true,
                null,
                null,
            ],
            'missing transactionData' => [
                'missing_tx_data',
                'Transaction simulation response did not include transaction data',
                null,
                null,
                false,
                null,
                null,
            ],
            'empty string transactionData' => [
                'empty_tx_data',
                'Transaction simulation response did not include transaction data',
                null,
                null,
                false,
                null,
                null,
            ],
        ];
    }

    #[DataProvider('simulationErrorResponseCases')]
    public function testVerifySimulationRejectsErrorsAndMissingTransactionDataWithoutPost(
        string $case,
        string $expectedMessage,
        ?string $marker,
        ?string $expectedSimulationError,
        bool $expectRpcError,
        ?int $expectedRpcCode,
        ?string $expectedRpcMessage
    ): void {
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'sim-err-' . $case
        );

        $simResponse = match ($case) {
            'result_error_host' => $this->buildSimulationResponse(
                resultError: 'HostError: Error(Auth, InvalidAction) SIM-RESULT-ERROR-MARKER-DO-NOT-ECHO',
                includeTransactionData: false
            ),
            'result_error_zero' => $this->buildSimulationResponse(
                resultError: '0',
                includeTransactionData: true
            ),
            'result_error_empty' => $this->buildSimulationResponse(
                resultError: '',
                includeTransactionData: true
            ),
            'rpc_error_code_and_message' => $this->buildSimulationResponse(
                rpcError: ['code' => -32602, 'message' => 'Invalid params RPC-ERROR-MESSAGE-MARKER-DO-NOT-ECHO']
            ),
            'rpc_error_message_only' => $this->buildSimulationResponse(
                rpcError: ['message' => 'Simulation unavailable RPC-ERROR-MESSAGE-ONLY-MARKER']
            ),
            'rpc_error_zero_message' => $this->buildSimulationResponse(
                rpcError: ['message' => '0']
            ),
            'rpc_error_code_only' => $this->buildSimulationResponse(
                rpcError: ['code' => -32000]
            ),
            'rpc_error_empty' => $this->buildSimulationResponse(
                rpcError: []
            ),
            'missing_tx_data' => $this->buildSimulationResponse(
                includeTransactionData: false
            ),
            'empty_tx_data' => SimulateTransactionResponse::fromJson([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => [
                    'latestLedger' => 123456,
                    'transactionData' => '',
                ],
            ]),
        };

        $mock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);

        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer($simResponse));

        $error = null;
        try {
            $webAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain);
        } catch (ContractChallengeValidationErrorSimulationFailed $e) {
            $error = $e;
        }

        $this->assertNotNull($error, "Case '$case' must throw ContractChallengeValidationErrorSimulationFailed");
        $this->assertSame(ContractChallengeValidationErrorSimulationFailed::class, $error::class);
        $this->assertSame($expectedMessage, $error->getMessage());
        $this->assertSame($expectedSimulationError, $error->getSimulationError());
        if ($expectRpcError) {
            $rpcError = $error->getRpcError();
            $this->assertInstanceOf(SorobanRpcErrorResponse::class, $rpcError);
            $this->assertSame($expectedRpcCode, $rpcError->getCode());
            $this->assertSame($expectedRpcMessage, $rpcError->getMessage());
        } else {
            $this->assertNull($error->getRpcError());
        }
        if ($marker !== null) {
            for ($cursor = $error; $cursor !== null; $cursor = $cursor->getPrevious()) {
                $this->assertStringNotContainsString($marker, $cursor->getMessage());
            }
        }
        $this->assertNull($error->getPrevious());
        $this->assertCount(1, $mock, 'Token response must remain queued: no POST sent');
        $this->assertSame('GET', $mock->getLastRequest()->getMethod());
    }

    public function testVerifySimulationPropagatesRpcTransportFailureAndWorksWithRealSorobanServer(): void
    {
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'sim-transport-nonce'
        );

        // Case 1: ConnectException propagates directly without sending token POST
        $connectRpcClient = new Client(['handler' => HandlerStack::create(new MockHandler([
            new ConnectException('Connection refused', new Request('POST', 'https://soroban-testnet.stellar.org')),
        ]))]);
        $authMock1 = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth1 = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth1->setMockHandler($authMock1);
        $webAuth1->setSorobanServer(new SorobanServer('https://soroban-testnet.stellar.org', $connectRpcClient));

        $caught1 = null;
        try {
            $webAuth1->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain, signatureExpirationLedger: 500000);
        } catch (ConnectException $e) {
            $caught1 = $e;
        }
        $this->assertInstanceOf(ConnectException::class, $caught1);
        $this->assertSame('Connection refused', $caught1->getMessage());
        $this->assertCount(1, $authMock1, 'No token POST after RPC ConnectException');
        $this->assertSame('GET', $authMock1->getLastRequest()->getMethod());

        // Case 2: HTTP 503 ServerException propagates directly without sending token POST
        $serverErrRpcClient = new Client(['handler' => HandlerStack::create(new MockHandler([
            new Response(503, [], 'Service Unavailable'),
        ]))]);
        $authMock2 = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth2 = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth2->setMockHandler($authMock2);
        $webAuth2->setSorobanServer(new SorobanServer('https://soroban-testnet.stellar.org', $serverErrRpcClient));

        $caught2 = null;
        try {
            $webAuth2->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain, signatureExpirationLedger: 500000);
        } catch (ServerException $e) {
            $caught2 = $e;
        }
        $this->assertInstanceOf(ServerException::class, $caught2);
        $this->assertSame(503, $caught2->getResponse()->getStatusCode());
        $this->assertCount(1, $authMock2, 'No token POST after RPC 503');
        $this->assertSame('GET', $authMock2->getLastRequest()->getMethod());

        // Case 3: Non-JSON RPC body propagates InvalidArgumentException directly without sending token POST
        $badJsonRpcClient = new Client(['handler' => HandlerStack::create(new MockHandler([
            new Response(200, [], 'not-json'),
        ]))]);
        $authMock3 = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth3 = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth3->setMockHandler($authMock3);
        $webAuth3->setSorobanServer(new SorobanServer('https://soroban-testnet.stellar.org', $badJsonRpcClient));

        $caught3 = null;
        try {
            $webAuth3->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain, signatureExpirationLedger: 500000);
        } catch (InvalidArgumentException $e) {
            $caught3 = $e;
        }
        $this->assertInstanceOf(InvalidArgumentException::class, $caught3);
        $this->assertCount(1, $authMock3, 'No token POST after non-JSON RPC body');
        $this->assertSame('GET', $authMock3->getLastRequest()->getMethod());

        // Case 4: End-to-end real SorobanServer JSON-RPC flow inspects actual wire payload
        $txData = new XdrSorobanTransactionData(
            new XdrSorobanTransactionDataExt(0),
            new XdrSorobanResources(
                new XdrLedgerFootprint(
                    [$this->buildContractInstanceKey($this->webAuthContractId)],
                    [
                        $this->buildNonceContractDataKey($this->clientContractId, 12346),
                        $this->buildNonceContractDataKey($this->serverAccountId, 12345),
                    ]
                ),
                100000,
                1000,
                1000
            ),
            50000
        );
        $rpcRequests = [];
        $rpcResponses = [
            new Response(200, [], json_encode([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => ['id' => 'abc', 'protocolVersion' => 23, 'sequence' => 555000],
            ])),
            new Response(200, [], json_encode([
                'jsonrpc' => '2.0',
                'id' => 2,
                'result' => [
                    'latestLedger' => 555000,
                    'transactionData' => $txData->toBase64Xdr(),
                ],
            ])),
        ];
        $rpcClient = new Client(['handler' => HandlerStack::create(
            function (RequestInterface $request) use (&$rpcRequests, &$rpcResponses): PromiseInterface {
                $rpcRequests[] = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
                return Create::promiseFor(array_shift($rpcResponses));
            }
        )]);
        $authMock4 = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth4 = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth4->setMockHandler($authMock4);
        $webAuth4->setSorobanServer(new SorobanServer('https://soroban-testnet.stellar.org', $rpcClient));

        $token = $webAuth4->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain);
        $this->assertSame($this->successJWTToken, $token);
        $this->assertCount(2, $rpcRequests);
        $this->assertSame('getLatestLedger', $rpcRequests[0]['method']);
        $this->assertSame('simulateTransaction', $rpcRequests[1]['method']);
        $this->assertArrayHasKey('transaction', $rpcRequests[1]['params']);
        $this->assertSame('enforce', $rpcRequests[1]['params']['authMode']);

        parse_str((string) $authMock4->getLastRequest()->getBody(), $postBody);
        $postedEntries = $webAuth4->decodeAuthorizationEntries($postBody['authorization_entries']);
        $postedXdrs = array_map(
            static fn (SorobanAuthorizationEntry $entry): string => $entry->toBase64Xdr(),
            $postedEntries
        );

        $wireTx = Transaction::fromEnvelopeBase64XdrString($rpcRequests[1]['params']['transaction']);
        $this->assertInstanceOf(Transaction::class, $wireTx);
        $this->assertSame(
            'GAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWHF',
            $wireTx->getSourceAccount()->getAccountId()
        );
        $this->assertSame('0', $wireTx->getSequenceNumber()->toString());
        $wireOp = $wireTx->getOperations()[0];
        $this->assertInstanceOf(InvokeHostFunctionOperation::class, $wireOp);
        $this->assertSame(
            $postedXdrs,
            array_map(static fn (SorobanAuthorizationEntry $entry): string => $entry->toBase64Xdr(), $wireOp->auth)
        );
    }

    public function testVerifySimulationDirectCallEdgeCases(): void
    {
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );

        // Case 1: Empty entries rejected before calling Soroban RPC
        $unusedRpc = $this->createMock(SorobanServer::class);
        $unusedRpc->expects($this->never())->method('simulateTransaction');
        $webAuth->setSorobanServer($unusedRpc);
        try {
            $webAuth->verifySimulation([], $this->clientContractId);
            $this->fail('Expected ContractChallengeValidationErrorSimulationFailed for empty entries');
        } catch (ContractChallengeValidationErrorSimulationFailed $e) {
            $this->assertSame('No authorization entries to simulate', $e->getMessage());
        }

        // Case 2: Non-contract-function root invocation rejected before calling Soroban RPC
        $argsMap = $this->buildArgsMap(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId, 'edge-nonce'
        );
        $nonContractEntry = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12345, 1000000
        );
        $nonContractEntry->rootInvocation->function->contractFn = null;
        try {
            $webAuth->verifySimulation([$nonContractEntry], $this->clientContractId);
            $this->fail('Expected ContractChallengeValidationErrorSimulationFailed for non-contract function');
        } catch (ContractChallengeValidationErrorSimulationFailed $e) {
            $this->assertSame('Authorization entry is not a contract function', $e->getMessage());
        }

        // Case 3: Direct call rejects client domain nonce when no entry for clientDomainAccountId is in signedEntries
        $clientDomainKeyPair = KeyPair::random();
        $clientDomainAccount = $clientDomainKeyPair->getAccountId();
        $serverEntry = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12345, 1000000
        );
        $serverEntry->sign($this->serverKeyPair, Network::testnet());
        $clientEntry = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12346, 1000000
        );
        $clientEntry->sign(KeyPair::random(), Network::testnet());

        $webAuth->setSorobanServer($this->createMockSorobanServer(
            $this->buildDefaultSimulationResponse([$clientDomainAccount])
        ));
        try {
            $webAuth->verifySimulation([$serverEntry, $clientEntry], $this->clientContractId, $clientDomainAccount);
            $this->fail('Expected ContractChallengeValidationErrorSimulationFailed when client domain entry is absent');
        } catch (ContractChallengeValidationErrorSimulationFailed $e) {
            $this->assertSame(
                "Transaction simulation read_write footprint contains disallowed contract data entry for $clientDomainAccount with key type SCV_LEDGER_KEY_NONCE",
                $e->getMessage()
            );
        }

        // Case 4: Sparse / associative keys in signedEntries succeed and preserve entry order
        $cdEntry = $this->buildAuthEntry(
            $clientDomainAccount, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12347, 1000000
        );
        $cdEntry->sign($clientDomainKeyPair, Network::testnet());
        $capturedSparse = null;
        $sparseSimResponse = $this->buildDefaultSimulationResponse([$clientDomainAccount]);
        $sparseRpc = $this->createMock(SorobanServer::class);
        $sparseRpc->expects($this->once())
            ->method('simulateTransaction')
            ->willReturnCallback(function (SimulateTransactionRequest $req) use (&$capturedSparse, $sparseSimResponse): SimulateTransactionResponse {
                $capturedSparse = $req;
                return $sparseSimResponse;
            });
        $webAuth->setSorobanServer($sparseRpc);

        $webAuth->verifySimulation(
            ['srv' => $serverEntry, 10 => $clientEntry, 'cd' => $cdEntry],
            $this->clientContractId,
            $clientDomainAccount
        );
        $this->assertNotNull($capturedSparse);
        $this->assertSame('enforce', $capturedSparse->getAuthMode());
        $this->assertSame('enforce', $capturedSparse->getRequestParams()['authMode']);
        $sparseTx = Transaction::fromEnvelopeBase64XdrString($capturedSparse->getTransaction()->toEnvelopeXdrBase64());
        $this->assertInstanceOf(Transaction::class, $sparseTx);
        $sparseOp = $sparseTx->getOperations()[0];
        $this->assertInstanceOf(InvokeHostFunctionOperation::class, $sparseOp);
        $this->assertSame(
            [$serverEntry->toBase64Xdr(), $clientEntry->toBase64Xdr(), $cdEntry->toBase64Xdr()],
            array_map(static fn (SorobanAuthorizationEntry $entry): string => $entry->toBase64Xdr(), $sparseOp->auth)
        );

        // Case 5: In-memory malformed non-XdrLedgerKey in readWrite
        $malformedKeyResponse = new SimulateTransactionResponse([]);
        $malformedKeyResponse->transactionData = new XdrSorobanTransactionData(
            new XdrSorobanTransactionDataExt(0),
            new XdrSorobanResources(new XdrLedgerFootprint([], ['not-a-ledger-key']), 100000, 1000, 1000),
            50000
        );
        $webAuth->setSorobanServer($this->createMockSorobanServer($malformedKeyResponse));
        try {
            $webAuth->verifySimulation([$serverEntry, $clientEntry], $this->clientContractId);
            $this->fail('Expected ContractChallengeValidationErrorSimulationFailed for non-XdrLedgerKey');
        } catch (ContractChallengeValidationErrorSimulationFailed $e) {
            $this->assertSame(
                'Transaction simulation read_write footprint contains a malformed ledger key',
                $e->getMessage()
            );
        }

        // Case 6: In-memory CONTRACT_DATA ledger key with null contractData payload
        $nullContractDataKey = new XdrLedgerKey(XdrLedgerEntryType::CONTRACT_DATA());
        $nullContractDataResponse = new SimulateTransactionResponse([]);
        $nullContractDataResponse->transactionData = new XdrSorobanTransactionData(
            new XdrSorobanTransactionDataExt(0),
            new XdrSorobanResources(new XdrLedgerFootprint([], [$nullContractDataKey]), 100000, 1000, 1000),
            50000
        );
        $webAuth->setSorobanServer($this->createMockSorobanServer($nullContractDataResponse));
        try {
            $webAuth->verifySimulation([$serverEntry, $clientEntry], $this->clientContractId);
            $this->fail('Expected ContractChallengeValidationErrorSimulationFailed for null contractData');
        } catch (ContractChallengeValidationErrorSimulationFailed $e) {
            $this->assertSame(
                'Transaction simulation read_write footprint contains disallowed ledger entry type: CONTRACT_DATA',
                $e->getMessage()
            );
        }

        // Case 7: In-memory CONTRACT_DATA key whose XdrSCAddress throws in toStrKey()
        $badAddressKey = XdrLedgerKey::forContractData(
            new XdrSCAddress(new XdrSCAddressType(99)),
            XdrSCVal::forLedgerNonceKey(new XdrSCNonceKey(1)),
            XdrContractDataDurability::TEMPORARY()
        );
        $badAddressResponse = new SimulateTransactionResponse([]);
        $badAddressResponse->transactionData = new XdrSorobanTransactionData(
            new XdrSorobanTransactionDataExt(0),
            new XdrSorobanResources(new XdrLedgerFootprint([], [$badAddressKey]), 100000, 1000, 1000),
            50000
        );
        $webAuth->setSorobanServer($this->createMockSorobanServer($badAddressResponse));
        try {
            $webAuth->verifySimulation([$serverEntry, $clientEntry], $this->clientContractId);
            $this->fail('Expected ContractChallengeValidationErrorSimulationFailed for invalid SCAddress');
        } catch (ContractChallengeValidationErrorSimulationFailed $e) {
            $this->assertSame(
                'Transaction simulation read_write footprint contains an invalid contract data address',
                $e->getMessage()
            );
            $this->assertNotNull($e->getPrevious());
        }

        // Case 8: In-memory CONTRACT_CODE key with null contractCode payload even when index is listed
        $nullContractCodeKey = new XdrLedgerKey(XdrLedgerEntryType::CONTRACT_CODE());
        $nullCodeExt = new XdrSorobanTransactionDataExt(1);
        $nullCodeExt->resourceExt = new XdrSorobanResourcesExtV0([0]);
        $nullCodeResponse = new SimulateTransactionResponse([]);
        $nullCodeResponse->transactionData = new XdrSorobanTransactionData(
            $nullCodeExt,
            new XdrSorobanResources(new XdrLedgerFootprint([], [$nullContractCodeKey]), 100000, 1000, 1000),
            50000
        );
        $webAuth->setSorobanServer($this->createMockSorobanServer($nullCodeResponse));
        try {
            $webAuth->verifySimulation([$serverEntry, $clientEntry], $this->clientContractId);
            $this->fail('Expected ContractChallengeValidationErrorSimulationFailed for null contractCode');
        } catch (ContractChallengeValidationErrorSimulationFailed $e) {
            $this->assertSame(
                'Transaction simulation read_write footprint contains disallowed ledger entry type: CONTRACT_CODE',
                $e->getMessage()
            );
        }

        // Case 9: In-memory ext discriminant 0 ignores any resourceExt payload attached in memory
        $v0WithPayloadExt = new XdrSorobanTransactionDataExt(0);
        $v0WithPayloadExt->resourceExt = new XdrSorobanResourcesExtV0([0]);
        $v0WithPayloadResponse = new SimulateTransactionResponse([]);
        $v0WithPayloadResponse->transactionData = new XdrSorobanTransactionData(
            $v0WithPayloadExt,
            new XdrSorobanResources(
                new XdrLedgerFootprint([], [XdrLedgerKey::forContractCode(str_repeat("\x11", 32))]),
                100000,
                1000,
                1000
            ),
            50000
        );
        $webAuth->setSorobanServer($this->createMockSorobanServer($v0WithPayloadResponse));
        try {
            $webAuth->verifySimulation([$serverEntry, $clientEntry], $this->clientContractId);
            $this->fail('Expected ContractChallengeValidationErrorSimulationFailed for ext discriminant 0 with resourceExt');
        } catch (ContractChallengeValidationErrorSimulationFailed $e) {
            $this->assertSame(
                'Transaction simulation read_write footprint contains disallowed ledger entry type: CONTRACT_CODE (not marked for restoration)',
                $e->getMessage()
            );
        }

        // Case 10: In-memory ext discriminant 1 with null resourceExt
        $missingResourceExtResponse = new SimulateTransactionResponse([]);
        $missingResourceExtResponse->transactionData = new XdrSorobanTransactionData(
            new XdrSorobanTransactionDataExt(1),
            new XdrSorobanResources(new XdrLedgerFootprint([], []), 100000, 1000, 1000),
            50000
        );
        $webAuth->setSorobanServer($this->createMockSorobanServer($missingResourceExtResponse));
        try {
            $webAuth->verifySimulation([$serverEntry, $clientEntry], $this->clientContractId);
            $this->fail('Expected ContractChallengeValidationErrorSimulationFailed for ext discriminant 1 without resourceExt');
        } catch (ContractChallengeValidationErrorSimulationFailed $e) {
            $this->assertSame(
                'Transaction simulation transaction data has ext discriminant 1 without resourceExt',
                $e->getMessage()
            );
        }

        // Case 11: In-memory unsupported ext discriminant
        $unsupportedExtResponse = new SimulateTransactionResponse([]);
        $unsupportedExtResponse->transactionData = new XdrSorobanTransactionData(
            new XdrSorobanTransactionDataExt(2),
            new XdrSorobanResources(new XdrLedgerFootprint([], []), 100000, 1000, 1000),
            50000
        );
        $webAuth->setSorobanServer($this->createMockSorobanServer($unsupportedExtResponse));
        try {
            $webAuth->verifySimulation([$serverEntry, $clientEntry], $this->clientContractId);
            $this->fail('Expected ContractChallengeValidationErrorSimulationFailed for unsupported ext discriminant');
        } catch (ContractChallengeValidationErrorSimulationFailed $e) {
            $this->assertSame(
                'Transaction simulation transaction data has unsupported ext discriminant: 2',
                $e->getMessage()
            );
        }

        // Case 12: In-memory negative index in archivedSorobanEntries
        $negativeIndexExt = new XdrSorobanTransactionDataExt(1);
        $negativeIndexExt->resourceExt = new XdrSorobanResourcesExtV0([-1]);
        $negativeIndexResponse = new SimulateTransactionResponse([]);
        $negativeIndexResponse->transactionData = new XdrSorobanTransactionData(
            $negativeIndexExt,
            new XdrSorobanResources(
                new XdrLedgerFootprint([], [XdrLedgerKey::forContractCode(str_repeat("\x11", 32))]),
                100000,
                1000,
                1000
            ),
            50000
        );
        $webAuth->setSorobanServer($this->createMockSorobanServer($negativeIndexResponse));
        try {
            $webAuth->verifySimulation([$serverEntry, $clientEntry], $this->clientContractId);
            $this->fail('Expected ContractChallengeValidationErrorSimulationFailed for negative archivedSorobanEntries index');
        } catch (ContractChallengeValidationErrorSimulationFailed $e) {
            $this->assertSame(
                'Transaction simulation transaction data contains an invalid archivedSorobanEntries index',
                $e->getMessage()
            );
        }

        // Case 13: Non-C clientAccountId cannot gain restored client contract data or instance allowance even when listed
        $nonContractClientId = KeyPair::random()->getAccountId();
        $nonContractClientInstanceResponse = $this->buildSimulationResponse(
            [
                XdrLedgerKey::forContractData(
                    XdrSCAddress::forAccountId($nonContractClientId),
                    XdrSCVal::forLedgerKeyContractInstance(),
                    XdrContractDataDurability::PERSISTENT()
                ),
            ],
            archivedSorobanEntries: [0]
        );
        $webAuth->setSorobanServer($this->createMockSorobanServer($nonContractClientInstanceResponse));
        try {
            $webAuth->verifySimulation([$serverEntry, $clientEntry], $nonContractClientId);
            $this->fail('Expected ContractChallengeValidationErrorSimulationFailed for non-C client contract instance');
        } catch (ContractChallengeValidationErrorSimulationFailed $e) {
            $this->assertSame(
                "Transaction simulation read_write footprint contains disallowed contract data entry for $nonContractClientId with key type SCV_LEDGER_KEY_CONTRACT_INSTANCE",
                $e->getMessage()
            );
            $this->assertNull($e->getSimulationError());
            $this->assertNull($e->getRpcError());
        }

        $nonContractClientSymbolResponse = $this->buildSimulationResponse(
            [
                XdrLedgerKey::forContractData(
                    XdrSCAddress::forAccountId($nonContractClientId),
                    XdrSCVal::forSymbol('admin'),
                    XdrContractDataDurability::PERSISTENT()
                ),
            ],
            archivedSorobanEntries: [0]
        );
        $webAuth->setSorobanServer($this->createMockSorobanServer($nonContractClientSymbolResponse));
        try {
            $webAuth->verifySimulation([$serverEntry, $clientEntry], $nonContractClientId);
            $this->fail('Expected ContractChallengeValidationErrorSimulationFailed for non-C client contract symbol data');
        } catch (ContractChallengeValidationErrorSimulationFailed $e) {
            $this->assertSame(
                "Transaction simulation read_write footprint contains disallowed contract data entry for $nonContractClientId with key type SCV_SYMBOL",
                $e->getMessage()
            );
            $this->assertNull($e->getSimulationError());
            $this->assertNull($e->getRpcError());
        }

        // A non-C clientAccountId still preserves existing WEB_AUTH_CONTRACT_ID instance eligibility
        $webAuthInstanceOnlyResponse = $this->buildSimulationResponse(
            [$this->buildContractInstanceKey($this->webAuthContractId)],
            archivedSorobanEntries: [0]
        );
        $webAuthInstanceRpc = $this->createMock(SorobanServer::class);
        $webAuthInstanceRpc->expects($this->once())->method('simulateTransaction')->willReturn($webAuthInstanceOnlyResponse);
        $webAuth->setSorobanServer($webAuthInstanceRpc);
        $webAuth->verifySimulation([$serverEntry, $clientEntry], $nonContractClientId);

        // Case 14: Distinct C... clientDomainAccountId gets no independent contract instance allowance even when listed
        $cdContractId = 'CCJCTOZFKPNTFLMORB7RBNKDQU42PBKGVTI4DIWVEMUCXRHWCYXGRRV7';
        $cdContractEntry = $this->buildAuthEntry(
            $cdContractId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12348, 1000000
        );
        $cdContractInstanceResponse = $this->buildSimulationResponse(
            [$this->buildContractInstanceKey($cdContractId)],
            archivedSorobanEntries: [0]
        );
        $webAuth->setSorobanServer($this->createMockSorobanServer($cdContractInstanceResponse));
        try {
            $webAuth->verifySimulation([$serverEntry, $clientEntry, $cdContractEntry], $this->clientContractId, $cdContractId);
            $this->fail('Expected ContractChallengeValidationErrorSimulationFailed for distinct C... client domain contract instance');
        } catch (ContractChallengeValidationErrorSimulationFailed $e) {
            $this->assertSame(
                "Transaction simulation read_write footprint contains disallowed contract data entry for $cdContractId with key type SCV_LEDGER_KEY_CONTRACT_INSTANCE",
                $e->getMessage()
            );
        }

        // Case 15: clientDomainAccountId equal to client or web auth contract retains that address's existing eligibility
        $clientInstanceOverlapResponse = $this->buildSimulationResponse(
            [$this->buildContractInstanceKey($this->clientContractId)],
            archivedSorobanEntries: [0]
        );
        $clientOverlapRpc = $this->createMock(SorobanServer::class);
        $clientOverlapRpc->expects($this->once())->method('simulateTransaction')->willReturn($clientInstanceOverlapResponse);
        $webAuth->setSorobanServer($clientOverlapRpc);
        $webAuth->verifySimulation([$serverEntry, $clientEntry], $this->clientContractId, $this->clientContractId);

        $webAuthContractEntry = $this->buildAuthEntry(
            $this->webAuthContractId, $this->webAuthContractId, 'web_auth_verify', $argsMap, 12349, 1000000
        );
        $webAuthOverlapRpc = $this->createMock(SorobanServer::class);
        $webAuthOverlapRpc->expects($this->once())->method('simulateTransaction')->willReturn($webAuthInstanceOnlyResponse);
        $webAuth->setSorobanServer($webAuthOverlapRpc);
        $webAuth->verifySimulation([$serverEntry, $clientEntry, $webAuthContractEntry], $this->clientContractId, $this->webAuthContractId);

        // Case 16: ContractChallengeValidationErrorSimulationFailed preserves ErrorException constructor compatibility
        $msgOnly = new ContractChallengeValidationErrorSimulationFailed('Message only');
        $this->assertSame('Message only', $msgOnly->getMessage());
        $this->assertSame(0, $msgOnly->getCode());
        $this->assertSame(E_ERROR, $msgOnly->getSeverity());
        $this->assertNull($msgOnly->getPrevious());
        $this->assertNull($msgOnly->getSimulationError());
        $this->assertNull($msgOnly->getRpcError());

        $cause = new RuntimeException('root cause');
        $withPrevious = new ContractChallengeValidationErrorSimulationFailed('With cause', previous: $cause);
        $this->assertSame('With cause', $withPrevious->getMessage());
        $this->assertSame($cause, $withPrevious->getPrevious());
        $this->assertNull($withPrevious->getSimulationError());
        $this->assertNull($withPrevious->getRpcError());

        $rpcErr = SorobanRpcErrorResponse::fromJson([
            'jsonrpc' => '2.0',
            'id' => 1,
            'error' => ['code' => -32600, 'message' => 'Invalid Request'],
        ]);
        $fullPositional = new ContractChallengeValidationErrorSimulationFailed(
            'Full positional',
            7,
            E_USER_WARNING,
            'custom.php',
            42,
            $cause,
            'HostError: trap',
            $rpcErr
        );
        $this->assertSame('Full positional', $fullPositional->getMessage());
        $this->assertSame(7, $fullPositional->getCode());
        $this->assertSame(E_USER_WARNING, $fullPositional->getSeverity());
        $this->assertSame('custom.php', $fullPositional->getFile());
        $this->assertSame(42, $fullPositional->getLine());
        $this->assertSame($cause, $fullPositional->getPrevious());
        $this->assertSame('HostError: trap', $fullPositional->getSimulationError());
        $this->assertSame($rpcErr, $fullPositional->getRpcError());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function reservedAuthEndpointQueryParameters(): array
    {
        return [
            'account' => [
                'https://auth.example.stellar.org/sep45?tenant=acme&account=CDZJIDQW5WTPAZ64PGIJGVEIDNK72LL3LKUZWG3G6GWXYQKI2JNIVFNV',
                'account',
            ],
            'home_domain' => [
                'https://auth.example.stellar.org/sep45?tenant=acme&home_domain=other.example.org',
                'home_domain',
            ],
            'percent-encoded home_domain' => [
                'https://auth.example.stellar.org/sep45?tenant=acme&home%5Fdomain=other.example.org',
                'home_domain',
            ],
            'client_domain' => [
                'https://auth.example.stellar.org/sep45?client_domain=evil.example.com&tenant=acme',
                'client_domain',
            ],
            'bare client_domain key' => [
                'https://auth.example.stellar.org/sep45?tenant=acme&client_domain',
                'client_domain',
            ],
        ];
    }

    #[DataProvider('reservedAuthEndpointQueryParameters')]
    public function testAuthEndpointRejectsReservedQueryParametersBeforeRequest(
        string $endpoint,
        string $reservedParameter
    ): void {
        $expectedMessage = "Endpoint query must not contain request parameter '$reservedParameter'";

        $builderMock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => 'AAAAAA=='])),
        ]);
        $builderClient = new Client(['handler' => HandlerStack::create($builderMock)]);
        $builder = (new ContractChallengeRequestBuilder($endpoint, $builderClient))
            ->forAccountId($this->clientContractId)
            ->forHomeDomain($this->domain);

        try {
            $builder->buildUrl();
            $this->fail("Expected InvalidArgumentException from buildUrl() for '$reservedParameter'");
        } catch (InvalidArgumentException $e) {
            $this->assertSame($expectedMessage, $e->getMessage());
        }

        try {
            $builder->execute();
            $this->fail("Expected InvalidArgumentException from execute() for '$reservedParameter'");
        } catch (InvalidArgumentException $e) {
            $this->assertSame($expectedMessage, $e->getMessage());
        }
        $this->assertCount(1, $builderMock, 'No HTTP request must be sent when endpoint query has a reserved parameter');
        $this->assertNull($builderMock->getLastRequest());

        foreach (['getChallenge', 'jwtToken'] as $method) {
            $mock = new MockHandler([
                new Response(200, [], json_encode(['authorization_entries' => 'AAAAAA=='])),
            ]);
            $webAuth = new WebAuthForContracts(
                $endpoint, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
            );
            $webAuth->setMockHandler($mock);

            try {
                if ($method === 'getChallenge') {
                    $webAuth->getChallenge($this->clientContractId, $this->domain);
                } else {
                    $webAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain);
                }
                $this->fail("Expected InvalidArgumentException from $method() for '$reservedParameter'");
            } catch (InvalidArgumentException $e) {
                $this->assertSame($expectedMessage, $e->getMessage());
            }
            $this->assertCount(1, $mock, "$method() must not send any HTTP request");
            $this->assertNull($mock->getLastRequest());
        }
    }

    public function testClientDomainSigningThreeCasesThroughJwtTokenAndSignAuthorizationEntries(): void
    {
        $clientDomain = 'wallet.example.com';
        $signingKeyPair = KeyPair::random();
        $publicOnlyKeyPair = KeyPair::fromAccountId($signingKeyPair->getAccountId());
        $clientSigner = KeyPair::random();

        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'cd-three-cases-nonce',
            $clientDomain,
            $signingKeyPair->getAccountId()
        );

        // Case 1: KeyPair with private key plus callback signs locally and never invokes callback
        $case1Requests = [];
        $case1Handler = function (RequestInterface $request) use ($challengeXdr, &$case1Requests): PromiseInterface {
            $uri = (string) $request->getUri();
            $case1Requests[] = ['method' => $request->getMethod(), 'uri' => $uri, 'body' => (string) $request->getBody()];
            if (str_contains($uri, 'stellar.toml')) {
                return Create::promiseFor(new Response(500, [], 'TOML must not be requested'));
            }
            if ($request->getMethod() === 'GET') {
                return Create::promiseFor(new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])));
            }
            return Create::promiseFor(new Response(200, [], json_encode(['token' => $this->successJWTToken])));
        };
        $webAuth1 = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain,
            Network::testnet(), new Client(['handler' => HandlerStack::create($case1Handler)])
        );
        $webAuth1->setSorobanServer($this->createMockSorobanServer(
            $this->buildDefaultSimulationResponse([$signingKeyPair->getAccountId()])
        ));

        $case1CallbackCalls = 0;
        $case1Callback = function (SorobanAuthorizationEntry $entry) use (&$case1CallbackCalls): SorobanAuthorizationEntry {
            $case1CallbackCalls++;
            return $entry;
        };
        $token1 = $webAuth1->jwtToken(
            $this->clientContractId, [$clientSigner], $this->domain, $clientDomain, $signingKeyPair, $case1Callback
        );
        $this->assertSame($this->successJWTToken, $token1);
        $this->assertSame(0, $case1CallbackCalls);
        $this->assertCount(2, $case1Requests);
        parse_str($case1Requests[1]['body'], $case1Post);
        $case1Submitted = $webAuth1->decodeAuthorizationEntries($case1Post['authorization_entries']);
        $this->assertTrue($signingKeyPair->verifySignature(
            $this->extractFirstSignatureBytes($case1Submitted[2]->credentials->getAddressCredentials()->signature),
            Hash::generate($case1Submitted[2]->buildPreimage(Network::testnet())->encode())
        ));

        $directCase1 = $webAuth1->signAuthorizationEntries(
            $webAuth1->decodeAuthorizationEntries($challengeXdr),
            $this->clientContractId,
            [$clientSigner],
            500001,
            $signingKeyPair,
            $case1Callback
        );
        $this->assertSame(0, $case1CallbackCalls);
        $this->assertSame(500001, $directCase1[2]->credentials->getAddressCredentials()->signatureExpirationLedger);
        $this->assertTrue($signingKeyPair->verifySignature(
            $this->extractFirstSignatureBytes($directCase1[2]->credentials->getAddressCredentials()->signature),
            Hash::generate($directCase1[2]->buildPreimage(Network::testnet())->encode())
        ));

        // Case 2: Public-only KeyPair plus callback skips stellar.toml and invokes callback with binding
        $case2Requests = [];
        $tomlRequested = false;
        $case2Handler = function (RequestInterface $request) use ($challengeXdr, &$case2Requests, &$tomlRequested): PromiseInterface {
            $uri = (string) $request->getUri();
            $case2Requests[] = ['method' => $request->getMethod(), 'uri' => $uri, 'body' => (string) $request->getBody()];
            if (str_contains($uri, 'stellar.toml')) {
                $tomlRequested = true;
                return Create::promiseFor(new Response(500, [], 'TOML must not be requested'));
            }
            if ($request->getMethod() === 'GET') {
                return Create::promiseFor(new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])));
            }
            return Create::promiseFor(new Response(200, [], json_encode(['token' => $this->successJWTToken])));
        };
        $webAuth2 = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain,
            Network::testnet(), new Client(['handler' => HandlerStack::create($case2Handler)])
        );
        $webAuth2->setSorobanServer($this->createMockSorobanServer(
            $this->buildDefaultSimulationResponse([$signingKeyPair->getAccountId()])
        ));

        $case2CallbackCalls = 0;
        $expirationSeenByCallback = null;
        $case2Callback = function (SorobanAuthorizationEntry $entry) use ($signingKeyPair, &$case2CallbackCalls, &$expirationSeenByCallback): SorobanAuthorizationEntry {
            $case2CallbackCalls++;
            $expirationSeenByCallback = $entry->credentials->getAddressCredentials()->signatureExpirationLedger;
            $entry->sign($signingKeyPair, Network::testnet());
            return $entry;
        };

        $token2 = $webAuth2->jwtToken(
            $this->clientContractId, [$clientSigner], $this->domain, $clientDomain, $publicOnlyKeyPair, $case2Callback
        );
        $this->assertSame($this->successJWTToken, $token2);
        $this->assertFalse($tomlRequested, 'Public-only key pair supplies the account ID without fetching stellar.toml');
        $this->assertSame(1, $case2CallbackCalls);
        $this->assertSame(123466, $expirationSeenByCallback);
        $this->assertCount(2, $case2Requests);
        parse_str($case2Requests[1]['body'], $case2Post);
        $case2Submitted = $webAuth2->decodeAuthorizationEntries($case2Post['authorization_entries']);
        $this->assertCount(3, $case2Submitted);
        $this->assertSame(123466, $case2Submitted[2]->credentials->getAddressCredentials()->signatureExpirationLedger);
        $this->assertTrue($signingKeyPair->verifySignature(
            $this->extractFirstSignatureBytes($case2Submitted[2]->credentials->getAddressCredentials()->signature),
            Hash::generate($case2Submitted[2]->buildPreimage(Network::testnet())->encode())
        ));

        // Case 2 callback binding still rejects tampered answer without sending token POST
        $tamperMock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuthTamper = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuthTamper->setMockHandler($tamperMock);
        $webAuthTamper->setSorobanServer($this->createMockSorobanServer(
            $this->buildDefaultSimulationResponse([$signingKeyPair->getAccountId()])
        ));
        try {
            $webAuthTamper->jwtToken(
                $this->clientContractId,
                [$clientSigner],
                $this->domain,
                $clientDomain,
                $publicOnlyKeyPair,
                function (SorobanAuthorizationEntry $entry) use ($signingKeyPair): SorobanAuthorizationEntry {
                    $entry->credentials->getAddressCredentials()->nonce = 999999;
                    $entry->sign($signingKeyPair, Network::testnet());
                    return $entry;
                }
            );
            $this->fail('Expected InvalidArgumentException for tampered callback answer with public-only key pair');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Client domain signing callback returned a different authorization entry', $e->getMessage());
        }
        $this->assertCount(1, $tamperMock, 'Token response must remain queued: no POST sent');
        $this->assertSame('GET', $tamperMock->getLastRequest()->getMethod());

        // Case 2 through direct signAuthorizationEntries() with $clientDomainAccountId omitted (null)
        $directCase2Calls = 0;
        $directCase2 = $webAuth2->signAuthorizationEntries(
            $webAuth2->decodeAuthorizationEntries($challengeXdr),
            $this->clientContractId,
            [$clientSigner],
            500002,
            $publicOnlyKeyPair,
            function (SorobanAuthorizationEntry $entry) use ($signingKeyPair, &$directCase2Calls): SorobanAuthorizationEntry {
                $directCase2Calls++;
                $entry->sign($signingKeyPair, Network::testnet());
                return $entry;
            }
        );
        $this->assertSame(1, $directCase2Calls);
        $this->assertSame(500002, $directCase2[2]->credentials->getAddressCredentials()->signatureExpirationLedger);
        $this->assertTrue($signingKeyPair->verifySignature(
            $this->extractFirstSignatureBytes($directCase2[2]->credentials->getAddressCredentials()->signature),
            Hash::generate($directCase2[2]->buildPreimage(Network::testnet())->encode())
        ));

        // Case 2 when server ignored client_domain: no TOML request and callback not invoked
        $ignoredChallengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'cd-public-only-ignored-nonce'
        );
        $ignoredMock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $ignoredChallengeXdr])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuthIgnored = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuthIgnored->setMockHandler($ignoredMock);
        $webAuthIgnored->setSorobanServer($this->createMockSorobanServer());
        $ignoredCallbackCalls = 0;
        $this->assertSame(
            $this->successJWTToken,
            $webAuthIgnored->jwtToken(
                $this->clientContractId,
                [$clientSigner],
                $this->domain,
                $clientDomain,
                $publicOnlyKeyPair,
                function (SorobanAuthorizationEntry $entry) use (&$ignoredCallbackCalls): SorobanAuthorizationEntry {
                    $ignoredCallbackCalls++;
                    return $entry;
                }
            )
        );
        $this->assertSame(0, $ignoredCallbackCalls);
        $this->assertCount(0, $ignoredMock);

        // Case 3: Public-only KeyPair without callback throws InvalidArgumentException before first request
        $expectedPublicOnlyMessage = 'Client domain key pair for account ' . $publicOnlyKeyPair->getAccountId()
            . ' must include a private key when no signing callback is provided';
        $case3Mock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
        ]);
        $unusedRpc = $this->createMock(SorobanServer::class);
        $unusedRpc->expects($this->never())->method('getLatestLedger');
        $unusedRpc->expects($this->never())->method('simulateTransaction');
        $webAuth3 = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth3->setMockHandler($case3Mock);
        $webAuth3->setSorobanServer($unusedRpc);

        try {
            $webAuth3->jwtToken(
                $this->clientContractId, [$clientSigner], $this->domain, $clientDomain, $publicOnlyKeyPair
            );
            $this->fail('Expected InvalidArgumentException for public-only clientDomainKeyPair without callback');
        } catch (InvalidArgumentException $e) {
            $this->assertSame($expectedPublicOnlyMessage, $e->getMessage());
        }
        $this->assertCount(1, $case3Mock, 'No HTTP request must be sent when public-only key pair has no callback');
        $this->assertNull($case3Mock->getLastRequest());

        // Case 3 through direct signAuthorizationEntries() throws before mutating client entry
        $directCase3Entries = $webAuth3->decodeAuthorizationEntries($challengeXdr);
        $clientXdrBefore = $directCase3Entries[1]->toBase64Xdr();
        try {
            $webAuth3->signAuthorizationEntries(
                $directCase3Entries,
                $this->clientContractId,
                [$clientSigner],
                500003,
                $publicOnlyKeyPair
            );
            $this->fail('Expected InvalidArgumentException from signAuthorizationEntries() for public-only key pair without callback');
        } catch (InvalidArgumentException $e) {
            $this->assertSame($expectedPublicOnlyMessage, $e->getMessage());
        }
        $this->assertSame($clientXdrBefore, $directCase3Entries[1]->toBase64Xdr());

        // Existing gating: when $clientDomain is null, passing a public-only key pair does not throw or sign for domain
        $noDomainMock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $ignoredChallengeXdr])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuthNoDomain = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuthNoDomain->setMockHandler($noDomainMock);
        $webAuthNoDomain->setSorobanServer($this->createMockSorobanServer());
        $this->assertSame(
            $this->successJWTToken,
            $webAuthNoDomain->jwtToken($this->clientContractId, [$clientSigner], $this->domain, null, $publicOnlyKeyPair)
        );
        $this->assertCount(0, $noDomainMock);
    }

    public function testSignAuthorizationEntriesPublicOnlyClientDomainKeyWithoutCallback(): void
    {
        $clientSigner = KeyPair::random();
        $clientDomain = 'wallet.example.com';
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $publicOnlyServerKey = KeyPair::fromAccountId($this->serverAccountId);

        $sameKeyArgs = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'direct-public-only-shared-nonce',
            $clientDomain,
            $this->serverAccountId
        );

        // 1. One server-signed shared entry: succeeds, shared entry unchanged, client entry signed
        $sharedEntry = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $sameKeyArgs, 30001, 1000000
        );
        $sharedEntry->sign($this->serverKeyPair, Network::testnet());
        $expectedSharedXdr = $sharedEntry->toBase64Xdr();
        $clientEntry1 = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $sameKeyArgs, 30002, 1000000
        );

        $signedSingle = $webAuth->signAuthorizationEntries(
            [$sharedEntry, $clientEntry1],
            $this->clientContractId,
            [$clientSigner],
            500010,
            $publicOnlyServerKey
        );
        $this->assertCount(2, $signedSingle);
        $this->assertSame($expectedSharedXdr, $signedSingle[0]->toBase64Xdr());
        $this->assertSame(500010, $signedSingle[1]->credentials->getAddressCredentials()->signatureExpirationLedger);
        $this->assertTrue($clientSigner->verifySignature(
            $this->extractFirstSignatureBytes($signedSingle[1]->credentials->getAddressCredentials()->signature),
            Hash::generate($signedSingle[1]->buildPreimage(Network::testnet())->encode())
        ));

        // 2. Two server-signed shared entries (ADDRESS + ADDRESS_V2): succeeds, both unchanged, client entry signed
        $sharedEntryA = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $sameKeyArgs, 30101, 1000000
        );
        $sharedEntryA->sign($this->serverKeyPair, Network::testnet());
        $sharedEntryB = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $sameKeyArgs, 30102, 1000000
        );
        $sharedEntryB->credentials = SorobanCredentials::forAddressCredentialsV2(
            $sharedEntryB->credentials->getAddressCredentials()
        );
        $sharedEntryB->sign($this->serverKeyPair, Network::testnet());
        $expectedSharedAXdr = $sharedEntryA->toBase64Xdr();
        $expectedSharedBXdr = $sharedEntryB->toBase64Xdr();
        $clientEntry2 = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $sameKeyArgs, 30103, 1000000
        );

        $signedTwo = $webAuth->signAuthorizationEntries(
            [$sharedEntryA, $clientEntry2, $sharedEntryB],
            $this->clientContractId,
            [$clientSigner],
            500011,
            $publicOnlyServerKey
        );
        $this->assertCount(3, $signedTwo);
        $this->assertSame($expectedSharedAXdr, $signedTwo[0]->toBase64Xdr());
        $this->assertSame($expectedSharedBXdr, $signedTwo[2]->toBase64Xdr());
        $this->assertSame(500011, $signedTwo[1]->credentials->getAddressCredentials()->signatureExpirationLedger);
        $this->assertTrue($clientSigner->verifySignature(
            $this->extractFirstSignatureBytes($signedTwo[1]->credentials->getAddressCredentials()->signature),
            Hash::generate($signedTwo[1]->buildPreimage(Network::testnet())->encode())
        ));

        // 3. Entries without any entry for the public-only key's account: succeeds
        $distinctPublicOnly = KeyPair::fromAccountId(KeyPair::random()->getAccountId());
        $baseArgs = $this->buildArgsMap(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId, 'direct-public-only-no-match'
        );
        $serverEntry3 = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $baseArgs, 30201, 1000000
        );
        $serverEntry3->sign($this->serverKeyPair, Network::testnet());
        $expectedServer3Xdr = $serverEntry3->toBase64Xdr();
        $clientEntry3 = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $baseArgs, 30202, 1000000
        );

        $signedNoMatch = $webAuth->signAuthorizationEntries(
            [$serverEntry3, $clientEntry3],
            $this->clientContractId,
            [$clientSigner],
            500012,
            $distinctPublicOnly
        );
        $this->assertCount(2, $signedNoMatch);
        $this->assertSame($expectedServer3Xdr, $signedNoMatch[0]->toBase64Xdr());
        $this->assertSame(500012, $signedNoMatch[1]->credentials->getAddressCredentials()->signatureExpirationLedger);
        $this->assertTrue($clientSigner->verifySignature(
            $this->extractFirstSignatureBytes($signedNoMatch[1]->credentials->getAddressCredentials()->signature),
            Hash::generate($signedNoMatch[1]->buildPreimage(Network::testnet())->encode())
        ));

        $sourceAccountEntry = new SorobanAuthorizationEntry(
            SorobanCredentials::forSourceAccount(),
            $clientEntry3->rootInvocation
        );
        $signedSourceOnly = $webAuth->signAuthorizationEntries(
            [$sourceAccountEntry],
            $this->clientContractId,
            [$clientSigner],
            500012,
            $distinctPublicOnly
        );
        $this->assertCount(1, $signedSourceOnly);
        $this->assertTrue($signedSourceOnly[0]->credentials->isSourceAccount());

        // 4. Unsigned client domain entry: throws before mutating client entry
        $distinctArgs = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'direct-public-only-unsigned',
            $clientDomain,
            $distinctPublicOnly->getAccountId()
        );
        $serverEntry4 = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $distinctArgs, 30301, 1000000
        );
        $serverEntry4->sign($this->serverKeyPair, Network::testnet());
        $clientEntry4 = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $distinctArgs, 30302, 1000000
        );
        $unsignedCdEntry = $this->buildAuthEntry(
            $distinctPublicOnly->getAccountId(), $this->webAuthContractId, 'web_auth_verify', $distinctArgs, 30303, 1000000
        );
        $client4XdrBefore = $clientEntry4->toBase64Xdr();
        $cd4XdrBefore = $unsignedCdEntry->toBase64Xdr();

        try {
            $webAuth->signAuthorizationEntries(
                [$serverEntry4, $clientEntry4, $unsignedCdEntry],
                $this->clientContractId,
                [$clientSigner],
                500013,
                $distinctPublicOnly
            );
            $this->fail('Expected InvalidArgumentException for unsigned client domain entry with public-only key');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                'Client domain key pair for account ' . $distinctPublicOnly->getAccountId()
                    . ' must include a private key when no signing callback is provided',
                $e->getMessage()
            );
        }
        $this->assertSame($client4XdrBefore, $clientEntry4->toBase64Xdr());
        $this->assertSame($cd4XdrBefore, $unsignedCdEntry->toBase64Xdr());

        // 5. Shared server address where a second entry lacks a valid server signature: throws before mutating client entry
        $clientEntry5 = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $sameKeyArgs, 30402, 1000000
        );
        $unsignedServerEntry = $this->buildAuthEntry(
            $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $sameKeyArgs, 30403, 1000000
        );
        $client5XdrBefore = $clientEntry5->toBase64Xdr();
        try {
            $webAuth->signAuthorizationEntries(
                [$sharedEntry, $clientEntry5, $unsignedServerEntry],
                $this->clientContractId,
                [$clientSigner],
                500014,
                $publicOnlyServerKey
            );
            $this->fail('Expected InvalidArgumentException when a shared server address entry lacks a valid server signature');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                'Client domain key pair for account ' . $this->serverAccountId
                    . ' must include a private key when no signing callback is provided',
                $e->getMessage()
            );
        }
        $this->assertSame($client5XdrBefore, $clientEntry5->toBase64Xdr());
    }

    public function testDuplicateAuthorizationEntriesValidationAndSigning(): void
    {
        $clientSigner = KeyPair::random();
        $clientDomain = 'wallet.example.com';
        $cdKeyPair = KeyPair::random();
        $cdAccountId = $cdKeyPair->getAccountId();
        $fourthAddress = KeyPair::random()->getAccountId();

        $baseArgs = $this->buildArgsMap(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId, 'dup-base-nonce'
        );
        $distinctCdArgs = $this->buildArgsMap(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId,
            'dup-cd-nonce', $clientDomain, $cdAccountId
        );
        $sameKeyCdArgs = $this->buildArgsMap(
            $this->clientContractId, $this->domain, 'auth.example.stellar.org', $this->serverAccountId,
            'dup-same-key-nonce', $clientDomain, $this->serverAccountId
        );

        $makeSignedServerEntry = function (XdrSCVal $args, int $nonce, string $arm = 'ADDRESS'): SorobanAuthorizationEntry {
            $entry = $this->buildAuthEntry(
                $this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $args, $nonce, 1000000
            );
            if ($arm === 'ADDRESS_V2') {
                $entry->credentials = SorobanCredentials::forAddressCredentialsV2(
                    $entry->credentials->getAddressCredentials()
                );
            } elseif ($arm === 'ADDRESS_WITH_DELEGATES') {
                $entry = SorobanAuthorizationEntry::withDelegates($entry, 1000000, [
                    new SorobanDelegateDescriptor(KeyPair::random()->getAccountId()),
                ]);
            }
            $entry->sign($this->serverKeyPair, Network::testnet());
            return $entry;
        };

        // 1. Two client entries rejected (including across mixed ADDRESS + ADDRESS_V2 arms)
        $clientEntryV2 = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $baseArgs, 20002, 1000000
        );
        $clientEntryV2->credentials = SorobanCredentials::forAddressCredentialsV2(
            $clientEntryV2->credentials->getAddressCredentials()
        );
        $twoClientEntries = [
            $makeSignedServerEntry($baseArgs, 20001),
            $this->buildAuthEntry($this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $baseArgs, 20003, 1000000),
            $clientEntryV2,
        ];

        // 2. Two server entries rejected when server != client domain (no client domain; mixed ADDRESS + ADDRESS_WITH_DELEGATES)
        $twoServerNoCd = [
            $makeSignedServerEntry($baseArgs, 20101),
            $makeSignedServerEntry($baseArgs, 20102, 'ADDRESS_WITH_DELEGATES'),
            $this->buildAuthEntry($this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $baseArgs, 20103, 1000000),
        ];

        // 3. Two server entries rejected when distinct client domain is present
        $twoServerDistinctCd = [
            $makeSignedServerEntry($distinctCdArgs, 20201),
            $makeSignedServerEntry($distinctCdArgs, 20202),
            $this->buildAuthEntry($this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $distinctCdArgs, 20203, 1000000),
            $this->buildAuthEntry($cdAccountId, $this->webAuthContractId, 'web_auth_verify', $distinctCdArgs, 20204, 1000000),
        ];

        // 4. Two client domain entries rejected when server != client domain
        $twoCdEntries = [
            $makeSignedServerEntry($distinctCdArgs, 20301),
            $this->buildAuthEntry($this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $distinctCdArgs, 20302, 1000000),
            $this->buildAuthEntry($cdAccountId, $this->webAuthContractId, 'web_auth_verify', $distinctCdArgs, 20303, 1000000),
            $this->buildAuthEntry($cdAccountId, $this->webAuthContractId, 'web_auth_verify', $distinctCdArgs, 20304, 1000000),
        ];

        // 5. Three server entries rejected even when server == client domain
        $threeSameKeyEntries = [
            $makeSignedServerEntry($sameKeyCdArgs, 20401),
            $makeSignedServerEntry($sameKeyCdArgs, 20402),
            $makeSignedServerEntry($sameKeyCdArgs, 20403),
            $this->buildAuthEntry($this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $sameKeyCdArgs, 20404, 1000000),
        ];

        // 6. Two server entries rejected when client requested domain with server key, but server ignored client_domain
        $twoServerIgnoredCd = [
            $makeSignedServerEntry($baseArgs, 20501),
            $makeSignedServerEntry($baseArgs, 20502),
            $this->buildAuthEntry($this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $baseArgs, 20503, 1000000),
        ];

        $rejectionCases = [
            'two client entries' => [
                $twoClientEntries,
                null,
                null,
                ContractChallengeValidationError::class,
                "Duplicate authorization entry for client account: {$this->clientContractId}",
            ],
            'two server entries without client domain' => [
                $twoServerNoCd,
                null,
                null,
                ContractChallengeValidationError::class,
                "Duplicate authorization entry for server account: {$this->serverAccountId}",
            ],
            'two server entries with distinct client domain' => [
                $twoServerDistinctCd,
                $clientDomain,
                $cdKeyPair,
                ContractChallengeValidationError::class,
                "Duplicate authorization entry for server account: {$this->serverAccountId}",
            ],
            'two client domain entries' => [
                $twoCdEntries,
                $clientDomain,
                $cdKeyPair,
                ContractChallengeValidationError::class,
                "Duplicate authorization entry for client domain account: $cdAccountId",
            ],
            'three entries for shared server and client domain address' => [
                $threeSameKeyEntries,
                $clientDomain,
                $this->serverKeyPair,
                ContractChallengeValidationError::class,
                "Duplicate authorization entry for server account: {$this->serverAccountId}",
            ],
            'two server entries when server ignored client domain' => [
                $twoServerIgnoredCd,
                $clientDomain,
                $this->serverKeyPair,
                ContractChallengeValidationError::class,
                "Duplicate authorization entry for server account: {$this->serverAccountId}",
            ],
            'two entries for shared server and client domain where second lacks valid server signature' => [
                [
                    $makeSignedServerEntry($sameKeyCdArgs, 20601),
                    $this->buildAuthEntry($this->serverAccountId, $this->webAuthContractId, 'web_auth_verify', $sameKeyCdArgs, 20602, 1000000),
                    $this->buildAuthEntry($this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $sameKeyCdArgs, 20603, 1000000),
                ],
                $clientDomain,
                $this->serverKeyPair,
                ContractChallengeValidationErrorInvalidServerSignature::class,
                'Server authorization entry has invalid signature',
            ],
        ];

        foreach ($rejectionCases as $label => [$entries, $reqDomain, $reqKeyPair, $expectedClass, $expectedMessage]) {
            $mock = new MockHandler([
                new Response(200, [], json_encode(['authorization_entries' => $this->encodeAuthEntries($entries)])),
                new Response(200, [], json_encode(['token' => $this->successJWTToken])),
            ]);
            $webAuth = new WebAuthForContracts(
                $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
            );
            $webAuth->setMockHandler($mock);
            $webAuth->setSorobanServer($this->createMockSorobanServer());

            $error = null;
            try {
                $webAuth->jwtToken($this->clientContractId, [$clientSigner], $this->domain, $reqDomain, $reqKeyPair);
            } catch (ContractChallengeValidationError $e) {
                $error = $e;
            }
            $this->assertNotNull($error, "Case '$label' must throw $expectedClass");
            $this->assertSame($expectedClass, $error::class, "Case '$label'");
            $this->assertSame($expectedMessage, $error->getMessage(), "Case '$label'");
            $this->assertCount(1, $mock, "Case '$label': token POST must not be sent");
            $this->assertSame('GET', $mock->getLastRequest()->getMethod());
        }

        // 7. server == client domain with one shared entry accepted (public-only key + callback: unchanged, callback not invoked)
        $sharedSingleServer = $makeSignedServerEntry($sameKeyCdArgs, 20701);
        $sharedSingleServerXdr = $sharedSingleServer->toBase64Xdr();
        $sharedSingleClient = $this->buildAuthEntry(
            $this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $sameKeyCdArgs, 20702, 1000000
        );
        $singleMock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $this->encodeAuthEntries([$sharedSingleServer, $sharedSingleClient])])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuthSingle = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuthSingle->setMockHandler($singleMock);
        $webAuthSingle->setSorobanServer($this->createMockSorobanServer());
        $singleCallbackCalls = 0;
        $tokenSingle = $webAuthSingle->jwtToken(
            $this->clientContractId,
            [$clientSigner],
            $this->domain,
            $clientDomain,
            KeyPair::fromAccountId($this->serverAccountId),
            function (SorobanAuthorizationEntry $entry) use (&$singleCallbackCalls): SorobanAuthorizationEntry {
                $singleCallbackCalls++;
                return $entry;
            }
        );
        $this->assertSame($this->successJWTToken, $tokenSingle);
        $this->assertSame(0, $singleCallbackCalls);
        parse_str((string) $singleMock->getLastRequest()->getBody(), $singlePost);
        $singleSubmitted = $webAuthSingle->decodeAuthorizationEntries($singlePost['authorization_entries']);
        $this->assertCount(2, $singleSubmitted);
        $this->assertSame($sharedSingleServerXdr, $singleSubmitted[0]->toBase64Xdr());

        // 8. server == client domain with two entries that both carry a valid server signature:
        //    accepted, both unchanged after signing, callback not invoked (tested for local key, callback, and public-only + callback)
        $serverEntry1 = $makeSignedServerEntry($sameKeyCdArgs, 20801);
        $serverEntry2 = $makeSignedServerEntry($sameKeyCdArgs, 20802, 'ADDRESS_V2');
        $expectedServer1Xdr = $serverEntry1->toBase64Xdr();
        $expectedServer2Xdr = $serverEntry2->toBase64Xdr();
        $twoSharedChallengeXdr = $this->encodeAuthEntries([
            $serverEntry1,
            $this->buildAuthEntry($this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $sameKeyCdArgs, 20803, 1000000),
            $serverEntry2,
        ]);

        $twoSharedConfigs = [
            'local key pair' => [$this->serverKeyPair, null, false],
            'callback via stellar.toml' => [null, true, true],
            'public-only key pair plus callback' => [KeyPair::fromAccountId($this->serverAccountId), true, false],
        ];
        foreach ($twoSharedConfigs as $label => [$kp, $useCallback, $needsToml]) {
            $responses = [new Response(200, [], json_encode(['authorization_entries' => $twoSharedChallengeXdr]))];
            if ($needsToml) {
                $responses[] = new Response(200, [], 'SIGNING_KEY = "' . $this->serverAccountId . '"');
            }
            $responses[] = new Response(200, [], json_encode(['token' => $this->successJWTToken]));
            $mockTwo = new MockHandler($responses);
            $webAuthTwo = new WebAuthForContracts(
                $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
            );
            $webAuthTwo->setMockHandler($mockTwo);
            $webAuthTwo->setSorobanServer($this->createMockSorobanServer());

            $cbCalls = 0;
            $cb = $useCallback
                ? function (SorobanAuthorizationEntry $entry) use (&$cbCalls): SorobanAuthorizationEntry {
                    $cbCalls++;
                    return $entry;
                }
                : null;

            $tokenTwo = $webAuthTwo->jwtToken(
                $this->clientContractId, [$clientSigner], $this->domain, $clientDomain, $kp, $cb
            );
            $this->assertSame($this->successJWTToken, $tokenTwo, "Case '$label'");
            $this->assertSame(0, $cbCalls, "Case '$label': callback must not be invoked");
            $this->assertCount(0, $mockTwo);
            parse_str((string) $mockTwo->getLastRequest()->getBody(), $postTwo);
            $submittedTwo = $webAuthTwo->decodeAuthorizationEntries($postTwo['authorization_entries']);
            $this->assertCount(3, $submittedTwo);
            $this->assertSame($expectedServer1Xdr, $submittedTwo[0]->toBase64Xdr(), "Case '$label': first server entry unchanged");
            $this->assertSame($expectedServer2Xdr, $submittedTwo[2]->toBase64Xdr(), "Case '$label': second server entry unchanged");
            $this->assertCount(1, $submittedTwo[1]->credentials->getAddressCredentials()->signature->vec);
        }

        // 9. Entry for a fourth address passes validation and is returned unsigned
        $fourthEntry = $this->buildAuthEntry(
            $fourthAddress, $this->webAuthContractId, 'web_auth_verify', $distinctCdArgs, 20904, 1000000
        );
        $expectedFourthXdr = $fourthEntry->toBase64Xdr();
        $fourthChallengeXdr = $this->encodeAuthEntries([
            $makeSignedServerEntry($distinctCdArgs, 20901),
            $this->buildAuthEntry($this->clientContractId, $this->webAuthContractId, 'web_auth_verify', $distinctCdArgs, 20902, 1000000),
            $this->buildAuthEntry($cdAccountId, $this->webAuthContractId, 'web_auth_verify', $distinctCdArgs, 20903, 1000000),
            $fourthEntry,
        ]);
        $fourthMock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $fourthChallengeXdr])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuthFourth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuthFourth->setMockHandler($fourthMock);
        $webAuthFourth->setSorobanServer($this->createMockSorobanServer(
            $this->buildDefaultSimulationResponse([$cdAccountId])
        ));

        $tokenFourth = $webAuthFourth->jwtToken(
            $this->clientContractId, [$clientSigner], $this->domain, $clientDomain, $cdKeyPair
        );
        $this->assertSame($this->successJWTToken, $tokenFourth);
        $this->assertCount(0, $fourthMock);
        parse_str((string) $fourthMock->getLastRequest()->getBody(), $fourthPost);
        $fourthSubmitted = $webAuthFourth->decodeAuthorizationEntries($fourthPost['authorization_entries']);
        $this->assertCount(4, $fourthSubmitted);
        $this->assertSame($expectedFourthXdr, $fourthSubmitted[3]->toBase64Xdr());
        $this->assertSame(
            XdrSCValType::SCV_VOID,
            $fourthSubmitted[3]->credentials->getAddressCredentials()->signature->type->value
        );
    }

    /**
     * @return array<string, array{int, string, string, ?string}>
     */
    public static function challengeGetNon200ErrorMessageCases(): array
    {
        return [
            '400 JSON error string' => [
                400,
                '{"error":"Invalid account"}',
                'Invalid account',
                null,
            ],
            '429 JSON error string' => [
                429,
                '{"error":"Too many challenge requests"}',
                'Too many challenge requests',
                null,
            ],
            '400 JSON zero string error' => [
                400,
                '{"error":"0"}',
                '0',
                null,
            ],
            '400 JSON empty error string falls back to status' => [
                400,
                '{"error":""}',
                'Unexpected challenge response status: 400',
                '{"error":""}',
            ],
            '400 JSON non-string error falls back to status' => [
                400,
                '{"error":123}',
                'Unexpected challenge response status: 400',
                '123',
            ],
            '502 HTML body falls back to status without leaking body' => [
                502,
                '<html><body><h1>502 Bad Gateway</h1><p>upstream detail</p></body></html>',
                'Unexpected challenge response status: 502',
                'Bad Gateway',
            ],
            '503 empty body falls back to status' => [
                503,
                '',
                'Unexpected challenge response status: 503',
                null,
            ],
        ];
    }

    #[DataProvider('challengeGetNon200ErrorMessageCases')]
    public function testChallengeGetNon200ErrorMessageAndAttachedResponse(
        int $statusCode,
        string $body,
        string $expectedMessage,
        ?string $forbiddenSubstring
    ): void {
        // Direct ContractChallengeRequestBuilder: checks message, status, and attached response object
        $response = new Response($statusCode, [], $body);
        $builderClient = new Client(['handler' => HandlerStack::create(new MockHandler([$response]))]);
        $builder = (new ContractChallengeRequestBuilder($this->authServer, $builderClient))
            ->forAccountId($this->clientContractId)
            ->forHomeDomain($this->domain);

        try {
            $builder->execute();
            $this->fail("Expected HorizonRequestException for status $statusCode");
        } catch (HorizonRequestException $e) {
            $this->assertSame($statusCode, $e->getStatusCode());
            $this->assertSame($expectedMessage, $e->getMessage());
            $this->assertSame($response, $e->getHttpResponse());
            $this->assertSame($body, (string) $e->getHttpResponse()->getBody());
            if ($forbiddenSubstring !== null) {
                $this->assertStringNotContainsString($forbiddenSubstring, $e->getMessage());
            }
        }

        // Through WebAuthForContracts::jwtToken(): checks ContractChallengeRequestErrorResponse and no POST
        $mock = new MockHandler([
            new Response($statusCode, [], $body),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        try {
            $webAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain);
            $this->fail("Expected ContractChallengeRequestErrorResponse for status $statusCode");
        } catch (ContractChallengeRequestErrorResponse $e) {
            $this->assertSame($statusCode, $e->getHttpStatusCode());
            $this->assertSame($expectedMessage, $e->getMessage());
            if ($forbiddenSubstring !== null) {
                $this->assertStringNotContainsString($forbiddenSubstring, $e->getMessage());
            }
        }
        $this->assertCount(1, $mock, 'Token response must remain queued: no POST sent');
        $this->assertSame('GET', $mock->getLastRequest()->getMethod());
    }

    public function testEmptyNetworkPassphraseAcceptedAndNonEmptyDifferingRejected(): void
    {
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'empty-passphrase-nonce'
        );

        // Empty network_passphrase treated like absent: accepted and full flow succeeds
        $mockEmpty = new MockHandler([
            new Response(200, [], json_encode([
                'authorization_entries' => $challengeXdr,
                'network_passphrase' => '',
            ])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mockEmpty);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $this->assertSame(
            $this->successJWTToken,
            $webAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain)
        );
        $this->assertCount(0, $mockEmpty);
        $this->assertSame('POST', $mockEmpty->getLastRequest()->getMethod());

        // Non-empty differing value ("0") is still rejected without sending POST
        $mockZero = new MockHandler([
            new Response(200, [], json_encode([
                'authorization_entries' => $challengeXdr,
                'network_passphrase' => '0',
            ])),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth->setMockHandler($mockZero);
        try {
            $webAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain);
            $this->fail('Expected ContractChallengeValidationErrorInvalidNetworkPassphrase for "0"');
        } catch (ContractChallengeValidationErrorInvalidNetworkPassphrase $e) {
            $this->assertSame(
                "Network passphrase mismatch. Expected: 'Test SDF Network ; September 2015', Got: '0'",
                $e->getMessage()
            );
        }
        $this->assertCount(1, $mockZero, 'Token response must remain queued: no POST sent');
        $this->assertSame('GET', $mockZero->getLastRequest()->getMethod());
    }

    public function testWebAuthDomainWithAndWithoutEndpointPort(): void
    {
        $clientSigner = KeyPair::random();

        // Endpoint with explicit port accepts both host and host:port (case-insensitively)
        $endpointWithPort = 'https://Auth.Example.Stellar.Org:8443/sep45';
        foreach ([
            'host only lowercase' => 'auth.example.stellar.org',
            'host only uppercase' => 'AUTH.EXAMPLE.STELLAR.ORG',
            'host and port lowercase' => 'auth.example.stellar.org:8443',
            'host and port uppercase' => 'AUTH.EXAMPLE.STELLAR.ORG:8443',
        ] as $label => $webAuthDomainArg) {
            $challengeXdr = $this->buildValidChallenge(
                $this->clientContractId,
                $this->domain,
                $webAuthDomainArg,
                $this->serverAccountId,
                'port-accept-' . $label
            );
            $mock = new MockHandler([
                new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
                new Response(200, [], json_encode(['token' => $this->successJWTToken])),
            ]);
            $webAuth = new WebAuthForContracts(
                $endpointWithPort, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
            );
            $webAuth->setMockHandler($mock);
            $webAuth->setSorobanServer($this->createMockSorobanServer());

            $this->assertSame(
                $this->successJWTToken,
                $webAuth->jwtToken($this->clientContractId, [$clientSigner], $this->domain),
                "Case '$label' must succeed"
            );
            $this->assertCount(0, $mock);
        }

        // Endpoint with explicit port rejects another port; endpoint without port rejects host:port
        foreach ([
            'endpoint with port vs different port' => [
                $endpointWithPort,
                'auth.example.stellar.org:9443',
            ],
            'endpoint without port vs default https port' => [
                'https://auth.example.stellar.org/sep45',
                'auth.example.stellar.org:443',
            ],
            'endpoint without port vs custom port' => [
                'https://auth.example.stellar.org/sep45',
                'auth.example.stellar.org:8443',
            ],
        ] as $label => [$endpoint, $webAuthDomainArg]) {
            $challengeXdr = $this->buildValidChallenge(
                $this->clientContractId,
                $this->domain,
                $webAuthDomainArg,
                $this->serverAccountId,
                'port-reject-' . $label
            );
            $mock = new MockHandler([
                new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
                new Response(200, [], json_encode(['token' => $this->successJWTToken])),
            ]);
            $webAuth = new WebAuthForContracts(
                $endpoint, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
            );
            $webAuth->setMockHandler($mock);
            $webAuth->setSorobanServer($this->createMockSorobanServer());

            try {
                $webAuth->jwtToken($this->clientContractId, [$clientSigner], $this->domain);
                $this->fail("Case '$label' must throw ContractChallengeValidationErrorInvalidWebAuthDomain");
            } catch (ContractChallengeValidationErrorInvalidWebAuthDomain $e) {
                $this->assertSame('Web auth domain argument does not match server domain', $e->getMessage());
            }
            $this->assertCount(1, $mock, "Case '$label': no POST must be sent");
            $this->assertSame('GET', $mock->getLastRequest()->getMethod());
        }
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function invalidDiscoveredClientDomainSigningKeys(): array
    {
        return [
            'muxed M key' => ['MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAAAJLK', false],
            'C contract id' => ['CA7A3N2BB35XMTFPAYWVZEF4TEYXW7DAEWDXJNQGUPR5SWSM2UVZCJM2', true],
            'arbitrary string' => ['UNEXPECTED-CLIENT-DOMAIN-KEY-VALUE', false],
        ];
    }

    #[DataProvider('invalidDiscoveredClientDomainSigningKeys')]
    public function testClientDomainTomlRejectsNonAccountSigningKeyBeforeCallbackAndPost(
        string $tomlSigningKey,
        bool $useInChallenge
    ): void {
        $clientDomain = 'client.example.com';
        $challengeDomainAccount = $useInChallenge ? $tomlSigningKey : KeyPair::random()->getAccountId();
        $challengeXdr = $this->buildValidChallenge(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'cd-discovered-key-nonce',
            $clientDomain,
            $challengeDomainAccount
        );

        $mock = new MockHandler([
            new Response(200, [], json_encode(['authorization_entries' => $challengeXdr])),
            new Response(200, [], 'SIGNING_KEY = "' . $tomlSigningKey . '"'),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $sorobanServer = $this->createMock(SorobanServer::class);
        $sorobanServer->expects($this->never())->method('getLatestLedger');
        $sorobanServer->expects($this->never())->method('simulateTransaction');

        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($sorobanServer);

        $callbackCalls = 0;
        $callback = function (SorobanAuthorizationEntry $entry) use (&$callbackCalls): SorobanAuthorizationEntry {
            $callbackCalls++;
            return $entry;
        };

        $error = null;
        try {
            $webAuth->jwtToken(
                $this->clientContractId,
                [KeyPair::random()],
                $this->domain,
                $clientDomain,
                null,
                $callback
            );
        } catch (InvalidArgumentException $e) {
            $error = $e;
        }

        $this->assertNotNull($error, 'Expected InvalidArgumentException for non-G SIGNING_KEY in client domain stellar.toml');
        $this->assertSame(InvalidArgumentException::class, $error::class);
        $this->assertSame(
            'Invalid client domain: SIGNING_KEY in stellar.toml must be a valid ed25519 account address (G...)',
            $error->getMessage()
        );
        $this->assertStringNotContainsString($tomlSigningKey, $error->getMessage());
        $this->assertSame(0, $callbackCalls, 'Callback must not run when discovered SIGNING_KEY is invalid');
        $this->assertCount(1, $mock, 'Token response must remain queued: no POST sent');
        $this->assertSame('GET', $mock->getLastRequest()->getMethod());
        $this->assertSame('https://client.example.com/.well-known/stellar.toml', (string) $mock->getLastRequest()->getUri());
    }

    /**
     * @return array<string, array{string, string, ?string}>
     */
    public static function challengeRedirectRefusalBodyCases(): array
    {
        return [
            'Horizon-shaped type and detail' => [
                '{"type":"x","detail":"TEXT"}',
                'Unexpected challenge response status: 302',
                'TEXT',
            ],
            'Horizon-shaped type without detail' => [
                '{"type":"x"}',
                'Unexpected challenge response status: 302',
                null,
            ],
            'Horizon-shaped type with non-string detail' => [
                '{"type":"x","detail":5}',
                'Unexpected challenge response status: 302',
                null,
            ],
            'SEP-45 JSON error body' => [
                '{"error":"denied"}',
                'denied',
                null,
            ],
        ];
    }

    #[DataProvider('challengeRedirectRefusalBodyCases')]
    public function testChallengeRedirectRefusalUsesStatusHandlingForResponseBodies(
        string $body,
        string $expectedMessage,
        ?string $forbiddenSubstring
    ): void {
        $redirectLocation = 'http://auth.example.stellar.org/downgraded';

        // Direct ContractChallengeRequestBuilder::execute()
        $redirectResponse = new Response(302, ['Location' => $redirectLocation], $body);
        $builderMock = new MockHandler([
            $redirectResponse,
            new Response(200, [], json_encode(['authorization_entries' => 'AAAAAA=='])),
        ]);
        $builderClient = new Client(['handler' => HandlerStack::create($builderMock)]);
        $builder = (new ContractChallengeRequestBuilder($this->authServer, $builderClient))
            ->forAccountId($this->clientContractId)
            ->forHomeDomain($this->domain);

        $builderError = null;
        try {
            $builder->execute();
        } catch (HorizonRequestException $e) {
            $builderError = $e;
        }

        $this->assertNotNull($builderError, 'Expected HorizonRequestException on refused redirect');
        $this->assertSame(HorizonRequestException::class, $builderError::class);
        $this->assertSame(302, $builderError->getStatusCode());
        $this->assertSame($expectedMessage, $builderError->getMessage());
        $this->assertSame($redirectResponse, $builderError->getHttpResponse());
        $this->assertNull($builderError->getHorizonErrorResponse());
        if ($forbiddenSubstring !== null) {
            $this->assertStringNotContainsString($forbiddenSubstring, $builderError->getMessage());
        }
        $this->assertCount(1, $builderMock, 'Redirect target must not be requested');
        $this->assertSame('https', $builderMock->getLastRequest()->getUri()->getScheme());

        // Through WebAuthForContracts::jwtToken()
        $mock = new MockHandler([
            new Response(302, ['Location' => $redirectLocation], $body),
            new Response(200, [], json_encode(['token' => $this->successJWTToken])),
        ]);
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $webAuth->setMockHandler($mock);
        $webAuth->setSorobanServer($this->createMockSorobanServer());

        $jwtError = null;
        try {
            $webAuth->jwtToken($this->clientContractId, [KeyPair::random()], $this->domain);
        } catch (ContractChallengeRequestErrorResponse $e) {
            $jwtError = $e;
        }

        $this->assertNotNull($jwtError, 'Expected ContractChallengeRequestErrorResponse on refused redirect');
        $this->assertSame(ContractChallengeRequestErrorResponse::class, $jwtError::class);
        $this->assertSame(302, $jwtError->getHttpStatusCode());
        $this->assertSame($expectedMessage, $jwtError->getMessage());
        if ($forbiddenSubstring !== null) {
            $this->assertStringNotContainsString($forbiddenSubstring, $jwtError->getMessage());
        }
        $this->assertCount(1, $mock, 'Redirect target and token response must remain queued');
        $this->assertSame('GET', $mock->getLastRequest()->getMethod());
        $this->assertSame('https', $mock->getLastRequest()->getUri()->getScheme());
    }

    public function testChallengeRequestBuilderConnectExceptionPropagatesTransportFailure(): void
    {
        $request = new Request('GET', 'https://auth.example.stellar.org');
        $connectException = new ConnectException('Connection timed out', $request);
        $mock = new MockHandler([$connectException]);
        $httpClient = new Client(['handler' => HandlerStack::create($mock)]);
        $builder = (new ContractChallengeRequestBuilder($this->authServer, $httpClient))
            ->forAccountId($this->clientContractId)
            ->forHomeDomain($this->domain);

        $challenge = null;
        $error = null;
        try {
            $challenge = $builder->execute();
        } catch (HorizonRequestException $e) {
            $error = $e;
        }

        $this->assertNull($challenge, 'No challenge must be returned on ConnectException');
        $this->assertNotNull($error, 'Expected HorizonRequestException on ConnectException');
        $this->assertSame(HorizonRequestException::class, $error::class);
        $this->assertSame(0, $error->getCode());
        $this->assertSame('Connection timed out', $error->getMessage());
        $this->assertNull($error->getStatusCode());
        $this->assertNull($error->getHttpResponse());
        $this->assertNull($error->getHorizonErrorResponse());
        $this->assertSame($connectException, $error->getPrevious());
        $this->assertCount(0, $mock);
    }

    /**
     * @return array<string, array{class-string<RequestException>, string, string}>
     */
    public static function challengeHttp200RequestExceptionCases(): array
    {
        return [
            'RequestException with valid challenge HTTP 200 body' => [
                RequestException::class,
                'Transfer interrupted after HTTP 200 headers',
                '{"authorization_entries":"AAAAAA==","network_passphrase":"Test SDF Network ; September 2015"}',
            ],
            'RequestException with Horizon-shaped non-string detail HTTP 200 body' => [
                RequestException::class,
                'Transfer interrupted with Horizon-shaped 200 body',
                '{"type":"x","detail":5}',
            ],
            'BadResponseException with Horizon-shaped non-string detail HTTP 200 body' => [
                BadResponseException::class,
                'Bad response exception with Horizon-shaped 200 body',
                '{"type":"x","detail":5}',
            ],
        ];
    }

    /**
     * @param class-string<RequestException> $exceptionClass
     */
    #[DataProvider('challengeHttp200RequestExceptionCases')]
    public function testChallengeRequestBuilderHttp200RequestExceptionRemainsTransportFailure(
        string $exceptionClass,
        string $exceptionMessage,
        string $body
    ): void {
        $request = new Request('GET', 'https://auth.example.stellar.org');
        $response = new Response(200, [], $body);
        $guzzleException = new $exceptionClass($exceptionMessage, $request, $response);
        $mock = new MockHandler([$guzzleException]);
        $httpClient = new Client(['handler' => HandlerStack::create($mock)]);
        $builder = (new ContractChallengeRequestBuilder($this->authServer, $httpClient))
            ->forAccountId($this->clientContractId)
            ->forHomeDomain($this->domain);

        $challenge = null;
        $error = null;
        try {
            $challenge = $builder->execute();
        } catch (HorizonRequestException $e) {
            $error = $e;
        }

        $this->assertNull($challenge, 'No challenge must be returned on failed HTTP 200 transfer');
        $this->assertNotNull($error, 'Expected HorizonRequestException on RequestException with HTTP 200 response');
        $this->assertSame(HorizonRequestException::class, $error::class);
        $this->assertSame(0, $error->getCode());
        $this->assertSame($exceptionMessage, $error->getMessage());
        $this->assertSame(200, $error->getStatusCode());
        $this->assertSame($response, $error->getHttpResponse());
        $this->assertNull($error->getHorizonErrorResponse());

        $previous = $error->getPrevious();
        $this->assertInstanceOf(RuntimeException::class, $previous);
        $this->assertSame(RuntimeException::class, $previous::class);
        $this->assertSame(0, $previous->getCode());
        $this->assertSame($exceptionMessage, $previous->getMessage());
        $this->assertSame($guzzleException, $previous->getPrevious());
        $this->assertCount(0, $mock);
    }

    public function testContractChallengeRequestBuilderPreservesPlusFieldNameAgainstSpaceParameter(): void
    {
        $httpClient = new Client(['handler' => HandlerStack::create(new MockHandler([]))]);
        $builder = (new ContractChallengeRequestBuilder(
            'https://auth.example.stellar.org/sep45?a+b=1',
            $httpClient
        ))
            ->forAccountId($this->clientContractId)
            ->forHomeDomain($this->domain)
            ->forQueryParameters(['a b' => '2']);

        $this->assertSame(
            'https://auth.example.stellar.org/sep45?a+b=1&account=CDZJIDQW5WTPAZ64PGIJGVEIDNK72LL3LKUZWG3G6GWXYQKI2JNIVFNV&home_domain=example.stellar.org&a%20b=2',
            $builder->buildUrl()
        );
    }

    public function testValidateChallengeRejectsNonSorobanAuthorizationEntryElement(): void
    {
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );

        $error = null;
        try {
            $webAuth->validateChallenge(['not-an-entry'], $this->clientContractId, $this->domain);
        } catch (ContractChallengeValidationError $e) {
            $error = $e;
        }

        $this->assertNotNull($error, 'Expected ContractChallengeValidationError for non-SorobanAuthorizationEntry element');
        $this->assertSame(ContractChallengeValidationError::class, $error::class);
        $this->assertSame('Invalid authorization entry type', $error->getMessage());
    }

    public function testValidateChallengeRejectsAccountAddressAsInvocationContractAddress(): void
    {
        $webAuth = new WebAuthForContracts(
            $this->authServer, $this->webAuthContractId, $this->serverAccountId, $this->domain, Network::testnet()
        );
        $argsMap = $this->buildArgsMap(
            $this->clientContractId,
            $this->domain,
            'auth.example.stellar.org',
            $this->serverAccountId,
            'non-contract-invocation-addr-nonce'
        );
        $entry = $this->buildAuthEntry(
            $this->serverAccountId,
            $this->webAuthContractId,
            'web_auth_verify',
            $argsMap,
            12345,
            1000000
        );
        $entry->rootInvocation->function->contractFn->contractAddress = XdrSCAddress::forAccountId($this->serverAccountId);

        $error = null;
        try {
            $webAuth->validateChallenge([$entry], $this->clientContractId, $this->domain);
        } catch (ContractChallengeValidationErrorInvalidContractAddress $e) {
            $error = $e;
        }

        $this->assertNotNull($error, 'Expected ContractChallengeValidationErrorInvalidContractAddress for account invocation address');
        $this->assertSame(ContractChallengeValidationErrorInvalidContractAddress::class, $error::class);
        $this->assertSame('Contract address is not a contract type', $error->getMessage());
    }
}
