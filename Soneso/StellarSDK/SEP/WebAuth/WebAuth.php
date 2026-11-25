<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use Soneso\StellarSDK\AbstractTransaction;
use Soneso\StellarSDK\Util\UrlValidator;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\Toml\StellarToml;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrCryptoKeyType;
use Soneso\StellarSDK\Xdr\XdrDecoratedSignature;
use Soneso\StellarSDK\Xdr\XdrEnvelopeType;
use Soneso\StellarSDK\Xdr\XdrMemoType;
use Soneso\StellarSDK\Xdr\XdrOperation;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;

/**
 * Implements SEP-10 Web Authentication protocol
 *
 * This class provides a complete implementation of the SEP-10 (Stellar Web Authentication)
 * protocol, which enables wallets and clients to prove they control a Stellar account by
 * signing a challenge transaction provided by an anchor's authentication server.
 *
 * The authentication flow consists of three steps:
 * 1. Request a challenge transaction from the server
 * 2. Sign the challenge with the client's private key(s)
 * 3. Submit the signed challenge back to the server to receive a JWT token
 *
 * The JWT token can then be used to authenticate subsequent requests to other SEP
 * services such as SEP-24 (hosted deposits/withdrawals), SEP-31 (cross-border payments),
 * or SEP-12 (KYC). The token typically has a limited validity period.
 *
 * This implementation supports standard accounts, muxed accounts, and client domain
 * verification for non-custodial wallets.
 *
 * SECURITY WARNINGS:
 *
 * 1. Sequence Number Validation (CRITICAL):
 *    Always verify the challenge transaction has sequence number 0. This ensures the transaction
 *    cannot be executed on the Stellar network. A non-zero sequence number could allow a malicious
 *    server to trick clients into signing executable transactions that transfer funds or modify
 *    account settings. This validation prevents transaction execution attacks.
 *
 * 2. Server Signature Verification (CRITICAL):
 *    Always verify the challenge is signed by the server's signing key from stellar.toml. This
 *    prevents man-in-the-middle attacks where an attacker intercepts the authentication flow and
 *    provides a fake challenge to capture client signatures. The server signature proves the
 *    challenge originated from the legitimate authentication server.
 *
 * 3. Time Bounds Validation (HIGH):
 *    Verify the challenge transaction's time bounds are valid and the current time falls within
 *    them, allowing the configured clock-skew grace period on both sides. The server chooses the
 *    lifetime. Missing, infinite, reversed or out-of-window bounds are rejected.
 *
 * 4. JWT Token Security (HIGH):
 *    Store JWT tokens securely and never expose them in logs, URLs, or insecure storage. Tokens
 *    grant access to authenticated services and should be treated as credentials. Use HTTPS for
 *    all requests with tokens. Respect token expiration times and request new authentication
 *    when tokens expire. Tokens should never be shared between different users or applications.
 *
 * 5. Home Domain Validation (HIGH):
 *    Verify the home domain in the challenge matches the expected service. This prevents domain
 *    confusion attacks where a challenge for one service is used to authenticate with another.
 *    Always validate the first operation's key matches "<expected_domain> auth".
 *
 * 6. Network Passphrase:
 *    Use the correct network passphrase (testnet or pubnet) when signing. Mixing passphrases
 *    can lead to signature validation failures or security vulnerabilities. Verify the network
 *    passphrase matches your intended network before proceeding with authentication.
 *
 * @package Soneso\StellarSDK\SEP\WebAuth
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md SEP-10 Specification v3.4.1
 * @see StellarToml For discovering the auth endpoint
 */
class WebAuth
{
    private string $authEndpoint;
    private string $serverSigningKey;
    private Network $network;
    private string $serverHomeDomain;
    private Client $httpClient;
    private int $gracePeriod = 60 * 5;
    // SEP-10 requires iat and exp (lines 196-203) but sets no authorization lifetime; SDK policy limits it to 15 minutes.
    private const AUTHORIZATION_TOKEN_LIFETIME = 900;

    /**
     * Constructor.
     * @param string $authEndpoint Endpoint to be used for the authentication procedure. Usually taken from stellar.toml.
     * @param string $serverSigningKey Valid G... server public key, taken from stellar.toml.
     * @param string $serverHomeDomain The server home domain of the server where the stellar.toml was loaded from.
     * @param Network $network The network used.
     * @param ?Client $httpClient Optional http client to be used for requests.
     * @throws InvalidArgumentException If the endpoint or server signing key is invalid.
     */
    public function __construct(string $authEndpoint, string $serverSigningKey, string $serverHomeDomain, Network $network, ?Client $httpClient = null)
    {
        UrlValidator::validateHttpsRequired($authEndpoint);
        if (!StrKey::isValidAccountId($serverSigningKey)) {
            throw new InvalidArgumentException('Server signing key must be a valid G... account ID');
        }
        $this->authEndpoint = $authEndpoint;
        $this->serverSigningKey = $serverSigningKey;
        $this->network = $network;
        $this->serverHomeDomain = $serverHomeDomain;

        if ($httpClient === null) {
            $this->httpClient = new Client();
        } else {
            $this->httpClient = $httpClient;
        }
    }

    /**
     * Sets the grace period (in seconds) for challenge transaction time bounds validation.
     *
     * The grace period accommodates clock skew between the client and the auth server.
     * A value of 0 enforces strict time bounds. Default is 300 seconds (5 minutes).
     *
     * @param int $seconds Grace period in seconds. Must be non-negative.
     */
    public function setGracePeriod(int $seconds): void
    {
        if ($seconds < 0) {
            throw new InvalidArgumentException('Grace period must be non-negative');
        }
        $this->gracePeriod = $seconds;
    }

    /** Creates a WebAuth instance by loading the needed data from the stellar.toml file hosted on the given domain.
     *  e.g. fromDomain("soneso.com", Network::testnet())
     * @param string $domain The domain from which to get the stellar information
     * @param Network $network The network used.
     * @param ?Client $httpClient Optional http client to be used for requests.
     * @return WebAuth
     * @throws Exception
     */
    public static function fromDomain(string $domain, Network $network, ?Client $httpClient = null) : WebAuth {

        $stellarToml = StellarToml::fromDomain($domain, $httpClient);
        $webAuthEndpoint = $stellarToml->getGeneralInformation()->webAuthEndpoint;
        $signingKey = $stellarToml->getGeneralInformation()->signingKey;
        if (!$webAuthEndpoint) {
            throw new Exception("No WEB_AUTH_ENDPOINT found in stellar.toml");
        }
        if (!$signingKey) {
            throw new Exception("No auth server SIGNING_KEY found in stellar.toml");
        }
        return new WebAuth($webAuthEndpoint, $signingKey, $domain, $network, $httpClient);
    }

    /**
     * Get JWT token for wallet.
     *
     * Executes the complete SEP-10 authentication flow: requests a challenge, validates it,
     * signs it with the provided signers, and submits it to obtain a JWT token.
     * V0 and V1 challenges are accepted and retain their envelope form. At least one signature with the
     * server key's hint must verify for the configured network. A non-empty advertised network passphrase
     * must match the configured network before the challenge is validated or signed.
     * The challenge GET and token POST succeed only on HTTP 200. Endpoint query parameters are merged into
     * the GET query; the POST uses the configured endpoint unchanged. Endpoint query keys account, memo,
     * home_domain and client_domain are rejected to prevent collisions with request parameters.
     * HTTPS requests only follow HTTPS redirects. Client accounts and signers are checked before the GET.
     * Decorated signatures are deduplicated with or without a callback, with at most 20 in the submitted envelope.
     * A web_auth_domain operation matches the endpoint host case-insensitively. An explicit endpoint port also permits host:port.
     *
     * @param string $clientAccountId The account id of the client/user to get the JWT token for (G... or M... address).
     * @param array<KeyPair> $signers Array of KeyPair objects (with secret keys) used to sign the challenge transaction.
     *                                Successful server authentication requires sufficient signer weight for existing accounts,
     *                                or the master key when the server supports accounts that do not exist.
     *                                An empty array still requests, validates and submits the challenge without adding client signatures;
     *                                the server decides whether the supplied signatures satisfy its authentication requirements.
     * @param int|string|null $memo optional, ID memo identifying the user of a shared (omnibus/pooled) client account.
     *                       Only permitted if the client account id is a Stellar account (G...), never with a muxed
     *                       account (M...). Null requires a challenge without a memo; every id from 0 to 2^64-1, as an unsigned decimal string
     *                       above PHP_INT_MAX, is valid and is sent to the server and verified against the returned challenge.
     * @param string|null $homeDomain optional, home domain to request from a server that may serve multiple home domains.
     *                       A non-null, non-empty value is sent as home_domain; the first operation's key must equal that value
     *                       followed by " auth". Null or the empty string omits the parameter and validates against the server home domain.
     * @param string|null $clientDomain Optional domain hosting the client's stellar.toml. The empty string is rejected before
     * any request. Every non-null value, including "0", is sent and requires a key pair or signing callback.
     * Domain signing occurs only when the validated challenge contains a client_domain operation; otherwise the flow
     * continues without a domain signature, callback invocation or client domain stellar.toml lookup.
     * @param KeyPair|null $clientDomainKeyPair Optional local domain signing key, used only when a domain is requested and
     * its operation exists. A private key signs locally and takes precedence over the callback. A public-only key requires
     * a callback when a domain is requested, or the call fails before any request. Its account ID takes precedence over
     * discovery for validating the operation source. Without a requested domain, the key and callback are unused.
     * @param callable|null $clientDomainSigningCallback Optional callback for SEP-10 client domain verification when signing cannot be performed locally.
     * The callback is used only when a domain is requested, its operation exists and no private domain key is supplied. Without a key pair,
     * the expected operation source is resolved from that domain's stellar.toml only when the operation exists.
     * The callback receives a base64-encoded transaction envelope XDR string and must return the same transaction signed by the client domain account
     * as a base64-encoded transaction envelope XDR string. Used when the client domain signing key is not available locally (e.g., signing occurs on a separate server).
     * Its answer is strictly decoded and its encoded transaction body must match the validated challenge, even if the callback takes its argument by reference.
     * Challenge signatures, callback signatures and local signatures are merged in that order, taking each complete decorated signature once (at most 20).
     * A local key pair takes precedence over a stellar.toml lookup for the expected source account of the client_domain operation.
     * A non-string answer or invalid base64 or XDR throws InvalidArgumentException; a wrong envelope type or changed body throws ChallengeValidationError;
     * more than 20 merged signatures throws ChallengeValidationErrorInvalidSignature before submission.
     * Callback signature: function(string $transactionXdr): string
     * @param KeyPair|callable|null $authorizationSigner Optional signer for a Bearer JWT on the challenge GET only.
     * A KeyPair must have a private key. The caller supplies a Client Domain Account key when clientDomain is specified,
     * otherwise a Client Account key. The SDK signs with EdDSA and sets integer iat and exp claims 900 seconds apart.
     * Claims include web_auth_endpoint and the transmitted account, memo (decimal string), home_domain and client_domain parameters.
     * A callable(array $claims): string receives those claims and returns a token valid as an HTTP header value that is not empty or only spaces and tabs.
     * The token is sent without verification; HTTP header normalization removes its trailing spaces and tabs.
     * Generated authorization overrides the HTTP client's Authorization header and auth option for the challenge GET only.
     * Null preserves the caller's HTTP authentication. Discovery and token POST requests retain the caller's authentication.
     * @return string Non-empty token from HTTP 200, including "0". Empty token/error fields count as absent;
     * a non-empty error takes precedence over a token. Missing token and error produce an unknown-error exception.
     * @throws ChallengeValidationError For an unsupported envelope type, no operations, an invalid nonce, a callback answer with a different transaction
     * or envelope type, or a non-empty network passphrase that differs from the configured network.
     * @throws ChallengeValidationErrorInvalidHomeDomain
     * @throws ChallengeValidationErrorInvalidMemoType
     * @throws ChallengeValidationErrorInvalidMemoValue If a requested memo is missing or differs, or the challenge for a G account carries an unrequested ID memo.
     * @throws ChallengeValidationErrorInvalidSeqNr
     * @throws ChallengeValidationErrorInvalidSignature If the server signature is invalid or signature limits fail.
     * @throws ChallengeValidationErrorInvalidSourceAccount
     * @throws ChallengeValidationErrorInvalidTimeBounds
     * @throws ChallengeValidationErrorInvalidWebAuthDomain
     * @throws ChallengeValidationErrorMemoAndMuxedAccount
     * @throws SubmitCompletedChallengeErrorResponseException On HTTP 400 or an invalid HTTP 200 token response.
     * @throws SubmitCompletedChallengeTimeoutResponseException On HTTP 504.
     * @throws SubmitCompletedChallengeUnknownResponseException On any other non-200 token response.
     * @throws GuzzleException|ChallengeRequestErrorResponse|ChallengeValidationErrorInvalidOperationType
     * @throws InvalidArgumentException For an empty client domain, a requested domain without a signer, endpoint query collisions,
     * invalid client account, a client signer without a private key, a public-only domain key without a callback, invalid memo input,
     * failed domain discovery, invalid base64, invalid XDR or trailing bytes in the challenge or a callback answer, a non-string callback answer, a public-only authorization key, an empty/non-string authorization token or invalid authorization header bytes.
     * @throws \JsonException If authorization claims cannot be JSON encoded.
     * @throws \Soneso\StellarSDK\Crypto\CryptoException If a signer keypair fails to produce a signature
     * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md SEP-10 Complete Flow
     * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#verification SEP-10 Signature Verification
     */
    public function jwtToken(string $clientAccountId,
                             array $signers,
                             int|string|null $memo = null,
                             ?string $homeDomain = null,
                             ?string $clientDomain = null,
                             ?KeyPair $clientDomainKeyPair = null,
                             ?callable $clientDomainSigningCallback = null,
                             KeyPair|callable|null $authorizationSigner = null) : string {

        // Validate without echoing the input: a mistakenly pasted seed must never enter a URL or an error message.
        if (!StrKey::isValidAccountId($clientAccountId) && !StrKey::isValidMuxedAccountId($clientAccountId)) {
            throw new InvalidArgumentException('Client account must be a valid G... or M... account ID');
        }
        // An empty list still requests, validates and submits the challenge: the server decides whether
        // the supplied signatures suffice (SEP-10 lines 93-94, 100-104). Each supplied signer must be usable
        // locally, so invalid entries fail without initiating authentication.
        foreach ($signers as $signer) {
            if (!$signer instanceof KeyPair || $signer->getPrivateKey() === null) {
                throw new InvalidArgumentException('Every client signer must be a KeyPair with a private key');
            }
        }
        // Empty domains cannot identify a TOML host; "0" remains a requested domain, so truthiness is unsuitable.
        if ($clientDomain === '') {
            throw new InvalidArgumentException('Client domain must not be empty');
        }
        if ($clientDomain !== null && $clientDomainKeyPair === null && $clientDomainSigningCallback === null) {
            throw new InvalidArgumentException("Client domain key pair or Client domain signing callback is missing");
        }
        // SDK policy requires a signing path upfront for a requested domain: a public-only key identifies the account
        // but requires a callback to obtain its signature (SEP-10 lines 89-92). Unrequested domain inputs are unused.
        if ($clientDomain !== null && $clientDomainKeyPair !== null
            && $clientDomainKeyPair->getPrivateKey() === null && $clientDomainSigningCallback === null) {
            throw new InvalidArgumentException('Client domain signer must have a private key');
        }
        if ($authorizationSigner instanceof KeyPair && $authorizationSigner->getPrivateKey() === null) {
            throw new InvalidArgumentException('Authorization signer must have a private key');
        }
        // An omitted home_domain uses the server default (SEP-10 line 267); a supplied one scopes the nonce key.
        $expectedHomeDomain = $homeDomain !== null && $homeDomain !== '' ? $homeDomain : $this->serverHomeDomain;
        $transaction = $this->getChallenge($clientAccountId, $memo, $homeDomain, $clientDomain, $authorizationSigner);

        // Resolve the domain account only when its operation exists: servers may ignore client_domain
        // (SEP-10 lines 268, 595-598), so an ignored domain must not require discovery or a signature.
        $resolveClientDomainAccount = function () use ($clientDomain, $clientDomainKeyPair): ?string {
            if ($clientDomain === null) {
                return null;
            }
            if ($clientDomainKeyPair !== null) {
                // A supplied key identifies the expected account, including for remote signing, so discovery is unnecessary.
                return $clientDomainKeyPair->getAccountId();
            }
            try {
                $toml = StellarToml::fromDomain($clientDomain, $this->httpClient);
                $accountId = $toml->generalInformation?->signingKey;
                if ($accountId === null) {
                    throw new Exception("Could not find signing key in stellar.toml");
                }
                // The published client domain account must be a G address (SEP-10 lines 583-585).
                if (!StrKey::isValidAccountId($accountId)) {
                    throw new Exception('Signing key must be a valid G... account ID');
                }
                return $accountId;
            } catch (Exception $e) {
                throw new InvalidArgumentException("Invalid client domain: " . $e->getMessage());
            }
        };
        $hasClientDomainOperation = $this->validateChallenge($transaction, $clientAccountId, $expectedHomeDomain,
            $resolveClientDomainAccount, $this->gracePeriod, $memo);
        $signClientDomain = $clientDomain !== null && $hasClientDomainOperation;
        // A private domain key takes precedence: one local signature satisfies the domain-signing step
        // (SEP-10 lines 89-92), so invoking a remote signer as well is unnecessary.
        if ($signClientDomain && $clientDomainKeyPair !== null && $clientDomainKeyPair->getPrivateKey() !== null) {
            array_push($signers, $clientDomainKeyPair);
            $clientDomainSigningCallback = null;
        }
        $signedTransaction = $this->signTransaction($transaction, $signers,
            $signClientDomain ? $clientDomainSigningCallback : null);

        // request the jwt token by sending back the signed challenge transaction to the web auth server.
        return $this->sendSignedChallengeTransaction($signedTransaction);
    }


    /**
     * Sends the signed challenge transaction back to the web auth server to obtain the jwt token.
     * In case of success, it returns the jwt token obtained from the web auth server.
     * HTTP 200 returns a non-empty token (including "0") only without a non-empty error. Empty strings count
     * as absent fields. HTTP 200 without either field and HTTP 400 without an error report an unknown error.
     *
     * @param string $base64EnvelopeXDR The signed challenge transaction as base64-encoded XDR.
     * @return string JWT token for authenticated requests.
     * @throws SubmitCompletedChallengeErrorResponseException
     * @throws SubmitCompletedChallengeTimeoutResponseException
     * @throws SubmitCompletedChallengeUnknownResponseException
     * @throws GuzzleException
     * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#token SEP-10 Token Endpoint
     */
    private function sendSignedChallengeTransaction(string $base64EnvelopeXDR) : string {
        // Evaluate HTTP statuses here so Guzzle cannot bypass SEP-10 response handling (lines 486-492, 505).
        $options = [RequestOptions::JSON => ['transaction' => $base64EnvelopeXDR], 'http_errors' => false];
        $redirects = $this->httpClient->getConfig('allow_redirects');
        // Restrict enabled HTTPS redirects to prevent transport downgrade, preserving disabled or stricter caller policies.
        if (($redirects === true || (is_array($redirects) && $redirects !== [])) && strtolower((string)parse_url($this->authEndpoint, PHP_URL_SCHEME)) === 'https') {
            $redirectOptions = is_array($redirects) ? $redirects : [];
            $redirectOptions['protocols'] = array_values(array_intersect($redirectOptions['protocols'] ?? ['https'], ['https']));
            // No permitted protocol means no redirect, so redirects are disabled.
            $options['allow_redirects'] = $redirectOptions['protocols'] === [] ? false : $redirectOptions;
        }
        $response = $this->httpClient->post($this->authEndpoint, $options);
        $statusCode = $response->getStatusCode();
        if (200 == $statusCode || 400 == $statusCode) {
            $content = $response->getBody()->__toString();
            $jsonData = @json_decode($content, true);
            if (null === $jsonData && json_last_error() != JSON_ERROR_NONE) {
                throw new SubmitCompletedChallengeErrorResponseException(sprintf("Error in json_decode: %s", json_last_error_msg()));
            }
            if (!is_array($jsonData)) {
                throw new SubmitCompletedChallengeErrorResponseException(sprintf("Token response body must be a JSON object or array, got %s", get_debug_type($jsonData)));
            }
            $result = SubmitCompletedChallengeResponse::fromJson($jsonData);
            // Empty strings carry no result; "0" is a value. Explicit errors take precedence to avoid
            // accepting a failed authentication; only HTTP 200 may return a token (SEP-10 lines 486-492, 505).
            $error = $result->getError();
            $token = $result->getJwtToken();
            if ($error !== null && $error !== '') {
                throw new SubmitCompletedChallengeErrorResponseException($error);
            } else if ($statusCode === 200 && $token !== null && $token !== '') {
                return $token;
            } else {
                throw new SubmitCompletedChallengeErrorResponseException("an unknown error occurred");
            }
        } else if (504 == $statusCode) {
            throw new SubmitCompletedChallengeTimeoutResponseException();
        } else {
            throw new SubmitCompletedChallengeUnknownResponseException($response->getBody()->__toString(), $response->getStatusCode());
        }
    }

    /**
     * Signs the validated challenge hash for the configured network. A callback receives a separate copy of the envelope string;
     * its strictly decoded envelope form and transaction body must match the validated challenge. The output retains the validated body
     * and merges challenge, callback and local signatures in order, deduplicating complete decorated signatures with or without a callback.
     * @param string $challengeTransaction the base64 encoded transaction envelope to sign.
     * @param array<KeyPair> $signers the key pairs of the signers to sign the transaction with.
     * @param callable|null $clientDomainSigningCallback Optional callback for SEP-10 client domain verification when signing cannot be performed locally.
     * The callback receives a base64-encoded transaction envelope XDR string and must return the same transaction signed by the client domain account
     * as a base64-encoded transaction envelope XDR string. Used when the client domain signing key is not available locally (e.g., signing occurs on a separate server).
     * Callback signature: function(string $transactionXdr): string
     * @return string the signed transaction as base64 encoded transaction envelope
     * @throws InvalidArgumentException if the callback answer is not a string or an envelope contains invalid base64, invalid XDR or trailing bytes.
     * @throws ChallengeValidationError if an envelope has an invalid type or the callback returns a different transaction body.
     * @throws ChallengeValidationErrorInvalidSignature if merging exceeds 20 signatures.
     * @throws \Soneso\StellarSDK\Crypto\CryptoException If a signer keypair fails to produce a signature
     */
    private function signTransaction(string $challengeTransaction, array $signers, ?callable $clientDomainSigningCallback = null) : string {
        $res = base64_decode($challengeTransaction, true);
        if ($res === false) {
            throw new InvalidArgumentException('Invalid base64-encoded transaction envelope');
        }
        $xdr = new XdrBuffer($res);
        $envelopeXdr = XdrTransactionEnvelope::decode($xdr);
        // Require exactly one envelope so every accepted byte participates in the decoded transaction or signatures.
        $xdr->assertFullyConsumed(XdrTransactionEnvelope::class);
        $envelopeType = $envelopeXdr->getType()->getValue();
        if (!in_array($envelopeType, [XdrEnvelopeType::ENVELOPE_TYPE_TX, XdrEnvelopeType::ENVELOPE_TYPE_TX_V0], true)) {
            throw new ChallengeValidationError("Invalid transaction type");
        }
        $transactionEnvelope = $envelopeType === XdrEnvelopeType::ENVELOPE_TYPE_TX_V0
            ? $envelopeXdr->getV0() : $envelopeXdr->getV1();
        // The typed hash uses the V1 signature payload for V0 too; the original envelope retains the server's wire form.
        $txHash = AbstractTransaction::fromEnvelopeXdr($envelopeXdr)->hash($this->network);
        $validatedSignatures = $transactionEnvelope->getSignatures();
        $signatures = [];
        $encodedSignatures = [];
        $appendSignature = static function (XdrDecoratedSignature $signature) use (&$signatures, &$encodedSignatures): void {
            $encoded = $signature->encode();
            // Deduplicate complete decorated signatures, not hints, because hints can collide between distinct signers.
            if (!in_array($encoded, $encodedSignatures, true)) {
                // Stellar transaction envelopes hold at most 20 decorated signatures.
                if (count($signatures) === 20) {
                    throw new ChallengeValidationErrorInvalidSignature('Invalid transaction envelope, more than 20 signatures');
                }
                $encodedSignatures[] = $encoded;
                $signatures[] = $signature;
            }
        };
        // SDK policy preserves original signatures first, deduplicating exact decorated signatures, so a callback
        // cannot discard them by removing or reordering them. SEP-10 does not require this order; validation
        // verifies one server signature (lines 71-72, 165-172, 312), retaining all others unverified.
        foreach ($validatedSignatures as $signature) {
            $appendSignature($signature);
        }
        if ($clientDomainSigningCallback !== null) {
            $validatedBody = $transactionEnvelope->getTx()->encode();
            // The callback may overwrite its argument by reference. All validation and signing baselines are independent of it.
            $callbackArgument = $challengeTransaction;
            $callbackResult = $clientDomainSigningCallback($callbackArgument);
            if (!is_string($callbackResult)) {
                throw new InvalidArgumentException('Invalid base64-encoded transaction envelope');
            }
            $res = base64_decode($callbackResult, true);
            if ($res === false) {
                throw new InvalidArgumentException('Invalid base64-encoded transaction envelope');
            }
            $xdr = new XdrBuffer($res);
            $callbackEnvelope = XdrTransactionEnvelope::decode($xdr);
            $xdr->assertFullyConsumed(XdrTransactionEnvelope::class);
            if ($callbackEnvelope->getType()->getValue() !== $envelopeType) {
                throw new ChallengeValidationError("Invalid transaction type");
            }
            $callbackTransactionEnvelope = $envelopeType === XdrEnvelopeType::ENVELOPE_TYPE_TX_V0
                ? $callbackEnvelope->getV0() : $callbackEnvelope->getV1();
            // Only signatures may change: byte equality prevents remote signing from substituting a different transaction.
            if ($callbackTransactionEnvelope->getTx()->encode() !== $validatedBody) {
                throw new ChallengeValidationError('Client domain signing callback returned a different transaction');
            }

            foreach ($callbackTransactionEnvelope->getSignatures() as $signature) {
                $appendSignature($signature);
            }
        }
        foreach ($signers as $signer) {
            $appendSignature($signer->signDecorated($txHash));
        }
        $transactionEnvelope->setSignatures($signatures);
        $bytes = $envelopeXdr->encode();
        return base64_encode($bytes);
    }

    /**
     * Get challenge transaction from the web auth server. Returns base64 xdr transaction envelope received from the web auth server.
     *
     * @param string $clientAccountId The account id of the client/user that requests the challenge.
     * @param int|string|null $memo optional, ID memo identifying the user of a shared client account. Null means no memo;
     *                       every id from 0 to 2^64-1, as an unsigned decimal string above PHP_INT_MAX, is sent to the server.
     * @param string|null $homeDomain optional, used for requesting the challenge depending on the home domain if needed. The web auth server may serve multiple home domains.
     * @param string|null $clientDomain optional, domain of the client hosting it's stellar.toml
     * @param KeyPair|callable|null $authorizationSigner Optional signer for the challenge request authorization token.
     * @return string Base64-encoded XDR transaction envelope.
     * @throws ChallengeRequestErrorResponse
     * @throws ChallengeValidationError If the response advertises a different non-empty network passphrase.
     * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#challenge SEP-10 Challenge Endpoint
     */
    private function getChallenge(string $clientAccountId, int|string|null $memo = null, ?string $homeDomain = null, ?string $clientDomain = null, KeyPair|callable|null $authorizationSigner = null) : string {

        $response = $this->getChallengeResponse($clientAccountId, $memo, $homeDomain, $clientDomain, $authorizationSigner);
        $transaction = $response->getTransaction();
        // A missing envelope is a response error regardless of any advertised network; validation needs a transaction.
        if (!$transaction) {
            throw new ChallengeRequestErrorResponse("Error parsing challenge response");
        }
        $passphrase = $response->getNetworkPassphrase();
        // The optional field diagnoses network misconfiguration (SEP-10 lines 313-316); SDK policy rejects
        // a non-empty mismatch before signing, while an absent or empty field supplies nothing to compare.
        if ($passphrase !== null && $passphrase !== '' && $passphrase !== $this->network->getNetworkPassphrase()) {
            throw new ChallengeValidationError('Challenge network passphrase "' . $passphrase
                . '" does not match configured network passphrase "' . $this->network->getNetworkPassphrase() . '"');
        }
        return $transaction;
    }

    /**
     * Validates the challenge transaction received from the web auth server.
     *
     * Validates SEP-10 requirements including sequence number,
     * signatures, time bounds, operations, source accounts, home domain, and web auth domain. The transaction source must be
     * an unmuxed account equal to the valid G... server signing key.
     * Operation 0 must contain a 64-byte base64 nonce that strictly decodes to 48 bytes;
     * nonce entropy is not assessed.
     * The web_auth_domain value matches the endpoint host case-insensitively; with an explicit endpoint port,
     * either host or host:port is accepted. Without an explicit port, only the host is accepted.
     *
     * @param string $challengeTransaction Base64-encoded XDR transaction envelope to validate.
     * @param string $userAccountId The client account ID that should be authenticated.
     * @param string $expectedHomeDomain The resolved home domain: the first operation's key must equal this value followed by " auth".
     * @param callable(): ?string $resolveClientDomainAccount Resolves the expected client domain account only when its operation exists.
     * @param int|null $timeBoundsGracePeriod Optional grace period in seconds for time bounds validation.
     * @param int|string|null $memo The memo requested from the server: every id from 0 to 2^64-1, as an unsigned decimal string above PHP_INT_MAX.
     *                       It must be present in the challenge as an ID memo holding exactly that value. Null means no memo was requested;
     *                       the challenge must then contain no memo.
     * @return bool Whether the validated challenge contains a client_domain operation.
     * @throws ChallengeValidationError
     * @throws ChallengeValidationErrorInvalidHomeDomain
     * @throws ChallengeValidationErrorInvalidMemoType
     * @throws ChallengeValidationErrorInvalidMemoValue If a requested memo is missing or differs, or the challenge for a G account carries an unrequested ID memo.
     * @throws ChallengeValidationErrorInvalidSeqNr
     * @throws ChallengeValidationErrorInvalidSignature If no signature with the server key's hint verifies for the configured network.
     * @throws ChallengeValidationErrorInvalidSourceAccount
     * @throws ChallengeValidationErrorInvalidTimeBounds
     * @throws ChallengeValidationErrorInvalidWebAuthDomain
     * @throws ChallengeValidationErrorMemoAndMuxedAccount
     * @throws ChallengeValidationErrorInvalidOperationType
     * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#challenge SEP-10 Challenge Validation
     */
    private function validateChallenge(string $challengeTransaction, string $userAccountId, string $expectedHomeDomain, callable $resolveClientDomainAccount, ?int $timeBoundsGracePeriod = null, int|string|null $memo = null): bool {
        $res = base64_decode($challengeTransaction, true);
        if ($res === false) {
            throw new InvalidArgumentException('Invalid base64-encoded challenge transaction');
        }
        $xdr = new XdrBuffer($res);
        $envelopeXdr = XdrTransactionEnvelope::decode($xdr);
        // Trailing bytes are outside the decoded envelope and cannot be validated as part of the challenge.
        $xdr->assertFullyConsumed(XdrTransactionEnvelope::class);

        $envelopeType = $envelopeXdr->getType()->getValue();
        // SEP-10 defines the challenge transaction, not its envelope version (lines 283-312).
        // V0 and V1 carry that transaction; a fee-bump envelope is not the challenge itself.
        if (!in_array($envelopeType, [XdrEnvelopeType::ENVELOPE_TYPE_TX, XdrEnvelopeType::ENVELOPE_TYPE_TX_V0], true)) {
            throw new ChallengeValidationError("Invalid transaction type received in challenge");
        }

        // Validate decoded fields before typed conversion, which can reject operations before SEP-10 checks.
        $transaction = $envelopeType === XdrEnvelopeType::ENVELOPE_TYPE_TX_V0
            ? $envelopeXdr->getV0()->getTx() : $envelopeXdr->getV1()->getTx();
        if ($transaction->getSequenceNumber()->sequenceNumber->toString() != "0") {
            throw new ChallengeValidationErrorInvalidSeqNr("Invalid transaction, sequence number not 0");
        }
        // The source must be the Server Account, an Ed25519 G... SIGNING_KEY (SEP-10 lines 35, 40, 284);
        // a muxed source is not that account id, even when it embeds the same Ed25519 key.
        $validSourceAccount = $envelopeType === XdrEnvelopeType::ENVELOPE_TYPE_TX_V0
            ? KeyPair::fromPublicKey($envelopeXdr->getV0()->getTx()->getSourceAccountEd25519())->getAccountId() === $this->serverSigningKey
            : ($envelopeXdr->getV1()->getTx()->getSourceAccount()->getDiscriminant() === XdrCryptoKeyType::KEY_TYPE_ED25519
                && MuxedAccount::fromXdr($envelopeXdr->getV1()->getTx()->getSourceAccount())->getAccountId() === $this->serverSigningKey);
        if (!$validSourceAccount) {
            throw new ChallengeValidationErrorInvalidSourceAccount('Invalid transaction source account, expected the server signing key');
        }

        if ($transaction->getMemo()->getType()->getValue() != XdrMemoType::MEMO_NONE) {
            if (str_starts_with($userAccountId, "M")) {
                throw new ChallengeValidationErrorMemoAndMuxedAccount("Memo and muxed account (M...) found");
            } else if ($transaction->getMemo()->getType()->getValue() != XdrMemoType::MEMO_ID) {
                throw new ChallengeValidationErrorInvalidMemoType("invalid memo type");
            } else if ($memo === null) {
                throw new ChallengeValidationErrorInvalidMemoValue('Challenge carries a memo that was not requested');
            } else if (Memo::fromXdr($transaction->getMemo())->getIdAsString() !== (string)$memo) {
                // Compare decimal strings to preserve the requested memo (SEP-10 line 289): a uint64 id can exceed PHP_INT_MAX.
                throw new ChallengeValidationErrorInvalidMemoValue("invalid memo value");
            }
        } else if ($memo !== null) {
            throw new ChallengeValidationErrorInvalidMemoValue("missing memo");
        }

        if (count($transaction->getOperations()) == 0) {
            throw new ChallengeValidationError("invalid number of operations (0)");
        }

        $operations = $transaction->getOperations();
        $hasClientDomainOperation = false;
        $clientDomainAccountId = null;
        $count = 0;
        foreach ($operations as $operation) {
            if (!$operation instanceof XdrOperation) {
                throw new ChallengeValidationError("invalid type of operation " . $count);
            }
            if(!$operation->getSourceAccount()) {
                throw new ChallengeValidationErrorInvalidSourceAccount(
                    "invalid source account in operation[" . $count . "]");
            }

            $opSourceAccountId = (MuxedAccount::fromXdr($operation->getSourceAccount()))->getAccountId();

            if ($count == 0 && $opSourceAccountId != $userAccountId) {
                throw new ChallengeValidationErrorInvalidSourceAccount(
                    "invalid source account in operation[" . $count . "]");
            }
            // all operations must be manage data operations
            if($operation->getBody()->getType()->getValue() != XdrOperationType::MANAGE_DATA
                || !$operation->getBody()->getManageDataOperation()) {
                throw new ChallengeValidationErrorInvalidOperationType("invalid type of operation " . $count);
            }

            $dataName = $operation->getBody()->getManageDataOperation()->getKey();

            // Unknown Manage Data keys are allowed for future extensions, but must be server-sourced
            // (SEP-10 lines 310-311); client_domain alone uses the domain account.
            if ($count > 0) {
                if ($dataName == "client_domain") {
                    // Domain verification checks the operation source (SEP-10 lines 84-92), not its data value.
                    // Resolve once so multiple domain operations share the same expected account.
                    if (!$hasClientDomainOperation) {
                        $clientDomainAccountId = $resolveClientDomainAccount();
                        $hasClientDomainOperation = true;
                    }
                    if ($opSourceAccountId != $clientDomainAccountId) {
                        throw new ChallengeValidationErrorInvalidSourceAccount("invalid source account in operation[".$count."]");
                    }
                } else if ($opSourceAccountId != $this->serverSigningKey) {
                    throw new ChallengeValidationErrorInvalidSourceAccount("invalid source account in operation[".$count."]");
                }
            }

            if ($count == 0 && $dataName !== ($expectedHomeDomain . " auth")) {
                throw new ChallengeValidationErrorInvalidHomeDomain("invalid home domain in operation[".$count."]");
            }

            $dataValue = $operation->getBody()->getManageDataOperation()->getValue();
            if ($count === 0) {
                // Check the nonce's encoding and lengths (SEP-10 lines 296-298); a single sample cannot prove entropy.
                $nonce = $dataValue->getValue();
                $decodedNonce = $nonce !== null ? base64_decode($nonce, true) : false;
                if ($nonce === null || strlen($nonce) !== 64 || $decodedNonce === false || strlen($decodedNonce) !== 48) {
                    throw new ChallengeValidationError('Invalid nonce in operation[0]: expected 64 base64 bytes encoding 48 bytes');
                }
            }
            // web_auth_domain is checked only when present, as required by SEP-10 lines 79-83.
            if ($count > 0 && $dataName == "web_auth_domain") {
                $parse = parse_url($this->authEndpoint);
                $host = $parse['host'];
                $domain = $dataValue->getValue();
                // SEP-10 says only "the Server's domain" (lines 79-83, 300-303) and leaves the port open.
                // Accept host, or host:port only when the endpoint explicitly has that port.
                $hostWithPort = isset($parse['port']) ? $host . ':' . $parse['port'] : $host;
                if ($domain === null || (strtolower($host) !== strtolower($domain)
                    && strtolower($hostWithPort) !== strtolower($domain))) {
                    throw new ChallengeValidationErrorInvalidWebAuthDomain("invalid web auth domain in operation[".$count."]");
                }
            }

            $count += 1;
        }

        // Time bounds are a transaction-level property and are validated once,
        // after the per-operation checks. SEP-10 requires the challenge to carry
        // time bounds, so their absence is rejected (an unbounded challenge could
        // otherwise be replayed indefinitely).
        $timeBounds = $transaction->getTimeBounds();
        if ($timeBounds === null) {
            throw new ChallengeValidationErrorInvalidTimeBounds(
                "Invalid transaction, missing time bounds");
        }
        // A maximum timestamp of 0 means unbounded, which would keep the
        // challenge valid forever and defeat replay protection.
        if ($timeBounds->getMaxTimestamp() == 0) {
            throw new ChallengeValidationErrorInvalidTimeBounds(
                "Invalid transaction, requires non-infinite time bounds");
        }
        $grace = 0;
        if ($timeBoundsGracePeriod) {
            $grace = $timeBoundsGracePeriod;
        }
        $currentTime = round(microtime(true));
        // A time point from 2^63 up is held as a negative int. As a minimum time it never
        // arrives, and as a maximum time it keeps the challenge valid indefinitely, so it is
        // rejected before the signed comparison with the current time.
        // Grace permits clock skew, not reversed bounds: an interval with min > max has no valid time.
        if ($timeBounds->getMinTimestamp() < 0 || $timeBounds->getMaxTimestamp() < 0 ||
            $timeBounds->getMinTimestamp() > $timeBounds->getMaxTimestamp() ||
            $currentTime < $timeBounds->getMinTimestamp() - $grace ||
            $currentTime > $timeBounds->getMaxTimestamp() + $grace) {
            throw new ChallengeValidationErrorInvalidTimeBounds(
                "Invalid transaction, invalid time bounds");
        }

        $signatures = $envelopeType === XdrEnvelopeType::ENVELOPE_TYPE_TX_V0
            ? $envelopeXdr->getV0()->getSignatures() : $envelopeXdr->getV1()->getSignatures();
        $serverKeyPair = KeyPair::fromAccountId($this->serverSigningKey);
        $transactionHash = (AbstractTransaction::fromEnvelopeXdr($envelopeXdr))->hash($this->network);
        // SEP-10 requires the server to sign and the client to verify its signature (lines 71-72, 165-172, 312).
        // Hints can collide, so every matching candidate is tried until one verifies; other signatures are preserved.
        foreach ($signatures as $signature) {
            if ($signature->getHint() === $serverKeyPair->getHint()
                && $serverKeyPair->verifySignature($signature->getSignature(), $transactionHash)) {
                return $hasClientDomainOperation;
            }
        }
        throw new ChallengeValidationErrorInvalidSignature("Invalid transaction envelope, invalid signature");
    }


    /**
     * @param string $accountId
     * @param int|string|null $memo Null means no memo; every id from 0 to 2^64-1, as an unsigned decimal string above PHP_INT_MAX, is sent as the memo request parameter
     *                       and is rejected if the account id is a muxed account (M...).
     * @param string|null $homeDomain
     * @param string|null $clientDomain
     * @param KeyPair|callable|null $authorizationSigner Optional signer for the challenge request authorization token.
     * @return ChallengeResponse
     * @throws ChallengeRequestErrorResponse
     */
    private function getChallengeResponse(string $accountId, int|string|null $memo = null, ?string $homeDomain = null, ?string $clientDomain = null, KeyPair|callable|null $authorizationSigner = null) : ChallengeResponse {
        if ($memo !== null && str_starts_with($accountId, "M")) {
            throw new InvalidArgumentException("memo cannot be used if accountId is a muxed account");
        }
        $requestBuilder = (new ChallengeRequestBuilder($this->authEndpoint, $this->httpClient))->forAccountId($accountId);
        if ($memo !== null) {
            $requestBuilder = $requestBuilder->forMemo($memo);
        }
        if ($homeDomain !== null && $homeDomain !== '') {
            $requestBuilder = $requestBuilder->forHomeDomain($homeDomain);
        }
        if ($clientDomain !== null) {
            $requestBuilder = $requestBuilder->forClientDomain($clientDomain);
        }
        $url = $requestBuilder->buildUrl();
        $token = null;
        if ($authorizationSigner !== null) {
            // Derive claims from the effective query so transmitted memo/domain values match the JWT (SEP-10 lines 204-205).
            $parameters = array_intersect_key(Query::parse((new Uri($url))->getQuery(), PHP_QUERY_RFC3986),
                array_flip(['account', 'memo', 'home_domain', 'client_domain']));
            $issuedAt = time();
            $claims = ['iat' => $issuedAt, 'exp' => $issuedAt + self::AUTHORIZATION_TOKEN_LIFETIME,
                'web_auth_endpoint' => $this->authEndpoint] + $parameters;
            $token = $this->createAuthorizationToken($claims, $authorizationSigner);
        }
        try {
            return $requestBuilder->request($url, $token);
        } catch (HorizonRequestException $e) {
            // ErrorException requires an integer code: 0 represents a missing status, not an HTTP response status.
            // Preserve the original failure as the previous exception for its transport or parsing details.
            throw new ChallengeRequestErrorResponse($e->getMessage(), $e->getStatusCode() ?? 0, previous: $e);
        }
    }

    /**
     * String tokens are checked for non-empty, valid header bytes by ChallengeRequestBuilder before sending the GET.
     *
     * @param array<string, mixed> $claims Challenge request claims with integer issue and expiry timestamps.
     * @param KeyPair|callable $signer Local signing key or callable(array $claims): string returning an opaque token.
     * @return string Authorization token without the Bearer prefix.
     * @throws InvalidArgumentException If the callable returns a non-string token.
     * @throws \JsonException If claims cannot be JSON encoded.
     * @throws \Soneso\StellarSDK\Crypto\CryptoException If signing fails.
     */
    private function createAuthorizationToken(array $claims, KeyPair|callable $signer): string {
        if ($signer instanceof KeyPair) {
            $encode = static fn(string $bytes): string => rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
            $message = $encode('{"alg":"EdDSA"}') . '.' . $encode(json_encode($claims, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
            return $message . '.' . $encode($signer->sign($message));
        }
        // Remote authorization signing returns an opaque token: the caller selects the account (SEP-10 lines 207-215).
        // Only its type is checked here; the builder owns header validation for every request entry point.
        $token = $signer($claims);
        if (!is_string($token)) {
            throw new InvalidArgumentException('Authorization signer must return a non-empty string');
        }
        return $token;
    }

    /**
     * Set a mock HTTP handler for testing purposes.
     *
     * Replaces the HTTP client with one using the provided mock handler. This allows tests
     * to simulate authentication server responses without making actual HTTP requests.
     *
     * @param MockHandler $handler Guzzle mock handler with predefined responses.
     * @return void
     */
    public function setMockHandler(MockHandler $handler) : void {
        $handlerStack = HandlerStack::create($handler);
        $this->httpClient = new Client(['handler' => $handlerStack]);
    }
}
