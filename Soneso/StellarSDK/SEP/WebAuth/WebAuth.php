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
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use Soneso\StellarSDK\AbstractTransaction;
use Soneso\StellarSDK\Util\UrlValidator;
use Soneso\StellarSDK\Crypto\KeyPair;
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
 *    them. Time bounds prevent replay attacks by limiting challenge validity to approximately
 *    15 minutes. Expired challenges should never be signed or submitted, as they may have been
 *    intercepted and are being replayed by an attacker.
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

    /**
     * Constructor.
     * @param string $authEndpoint Endpoint to be used for the authentication procedure. Usually taken from stellar.toml.
     * @param string $serverSigningKey The server public key, taken from stellar.toml.
     * @param string $serverHomeDomain The server home domain of the server where the stellar.toml was loaded from.
     * @param Network $network The network used.
     * @param ?Client $httpClient Optional http client to be used for requests.
     */
    public function __construct(string $authEndpoint, string $serverSigningKey, string $serverHomeDomain, Network $network, ?Client $httpClient = null)
    {
        UrlValidator::validateHttpsRequired($authEndpoint);
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
     *
     * @param string $clientAccountId The account id of the client/user to get the JWT token for (G... or M... address).
     * @param array<KeyPair> $signers Array of KeyPair objects (with secret keys) used to sign the challenge transaction.
     *                                Must include signers with sufficient weight to meet the server's threshold requirements.
     *                                For accounts that don't exist, must include the master key. For existing accounts,
     *                                must provide signers that meet the required threshold (typically medium threshold).
     *                                Minimum: 1 signer. The combined weight must satisfy server authentication requirements.
     * @param int|string|null $memo optional, ID memo identifying the user of a shared (omnibus/pooled) client account.
     *                       Only permitted if the client account id is a Stellar account (G...), never with a muxed
     *                       account (M...). Null means no memo; every id from 0 to 2^64-1, as an unsigned decimal string
     *                       above PHP_INT_MAX, is valid and is sent to the server and verified against the returned challenge.
     * @param string|null $homeDomain optional, home domain to request from a server that may serve multiple home domains.
     *                       A non-null, non-empty value is sent as home_domain; the first operation's key must equal that value
     *                       followed by " auth". Null or the empty string omits the parameter and validates against the server home domain.
     * @param string|null $clientDomain optional, domain of the client hosting it's stellar.toml. If clientDomain is provided,
     * you also need to provide the clientDomainKeyPair or a clientDomainSigningCallback for client domain transaction signing.
     * @param KeyPair|null $clientDomainKeyPair optional, KeyPair of the client domain account including the seed (used for signing the transaction if client domain is provided)
     * @param callable|null $clientDomainSigningCallback Optional callback for SEP-10 client domain verification when signing cannot be performed locally.
     * The callback receives a base64-encoded transaction envelope XDR string and must return the same transaction signed by the client domain account
     * as a base64-encoded transaction envelope XDR string. Used when the client domain signing key is not available locally (e.g., signing occurs on a separate server).
     * Its answer is strictly decoded and its encoded transaction body must match the validated challenge, even if the callback takes its argument by reference.
     * Challenge signatures, callback signatures and local signatures are merged in that order, taking each complete decorated signature once (at most 20).
     * A client_domain operation requires authorization by the expected key, through a signature with the correct hint over the challenge hash for this network
     * or through local signing with clientDomainKeyPair. A local key pair takes precedence over a stellar.toml lookup.
     * A non-string answer or invalid base64 or XDR throws InvalidArgumentException; a wrong envelope type or changed body throws ChallengeValidationError;
     * missing client domain authorization or more than 20 merged signatures throws ChallengeValidationErrorInvalidSignature before submission.
     * Callback signature: function(string $transactionXdr): string
     * @return string JWT token that can be used to authenticate requests to protected services.
     * @throws ChallengeValidationError
     * @throws ChallengeValidationErrorInvalidHomeDomain
     * @throws ChallengeValidationErrorInvalidMemoType
     * @throws ChallengeValidationErrorInvalidMemoValue
     * @throws ChallengeValidationErrorInvalidSeqNr
     * @throws ChallengeValidationErrorInvalidSignature
     * @throws ChallengeValidationErrorInvalidSourceAccount
     * @throws ChallengeValidationErrorInvalidTimeBounds
     * @throws ChallengeValidationErrorInvalidWebAuthDomain
     * @throws ChallengeValidationErrorMemoAndMuxedAccount
     * @throws SubmitCompletedChallengeErrorResponseException
     * @throws SubmitCompletedChallengeTimeoutResponseException
     * @throws SubmitCompletedChallengeUnknownResponseException
     * @throws GuzzleException|ChallengeRequestErrorResponse|ChallengeValidationErrorInvalidOperationType
     * @throws InvalidArgumentException
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
                             ?callable $clientDomainSigningCallback = null) : string {

        $expectedHomeDomain = $homeDomain !== null && $homeDomain !== '' ? $homeDomain : $this->serverHomeDomain;

        // get the challenge transaction from the web auth server
        $transaction = $this->getChallenge($clientAccountId, $memo, $homeDomain, $clientDomain);

        $clientDomainSignerAccountId = null;
        if ($clientDomain !== null) {
            if ($clientDomainKeyPair !== null) {
                $clientDomainSignerAccountId = $clientDomainKeyPair?->getAccountId();
            } else if ($clientDomainSigningCallback !== null) {
                try {
                    $toml = StellarToml::fromDomain($clientDomain, $this->httpClient);
                    $clientDomainSignerAccountId = $toml->generalInformation?->signingKey;
                    if ($clientDomainSignerAccountId === null) {
                        throw new Exception("Could not find signing key in stellar.toml");
                    }
                } catch (Exception $e) {
                    throw new InvalidArgumentException("Invalid client domain: " . $e->getMessage());
                }
            } else {
                throw new InvalidArgumentException("Client domain key pair or Client domain signing callback is missing");
            }
        }
        // validate the transaction received from the web auth server.
        $this->validateChallenge($transaction, $clientAccountId, $expectedHomeDomain, $clientDomainSignerAccountId, $this->gracePeriod, $memo);

        if ($clientDomainKeyPair) {
            array_push($signers, $clientDomainKeyPair);
        }

        // sign the transaction received from the web auth server using the provided user/client keypair by parameter.
        $signedTransaction = $this->signTransaction($transaction, $signers, $clientDomainSigningCallback,
            $clientDomainSignerAccountId, $clientDomainKeyPair);

        // request the jwt token by sending back the signed challenge transaction to the web auth server.
        return $this->sendSignedChallengeTransaction($signedTransaction);
    }


    /**
     * Sends the signed challenge transaction back to the web auth server to obtain the jwt token.
     * In case of success, it returns the jwt token obtained from the web auth server.
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
        $response = $this->httpClient->post($this->authEndpoint, [RequestOptions::JSON => ['transaction' => $base64EnvelopeXDR], 'http_errors' => false]);
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
            if ($result->getError()) {
                throw new SubmitCompletedChallengeErrorResponseException($result->getError());
            } else if ($result->getJwtToken()) {
                return $result->getJwtToken();
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
     * its strictly decoded transaction body must be byte-identical to the validated body. The output retains the validated body
     * and merges challenge, callback and local signatures in order, deduplicating complete decorated signatures in the callback path.
     * When a client_domain operation and an expected key are present, a signature with that key's hint must verify over the challenge
     * hash, either from the challenge, the callback or local client domain signing. This is checked before asking the user to sign.
     * @param string $challengeTransaction the base64 encoded transaction envelope to sign.
     * @param array<KeyPair> $signers the key pairs of the signers to sign the transaction with.
     * @param callable|null $clientDomainSigningCallback Optional callback for SEP-10 client domain verification when signing cannot be performed locally.
     * The callback receives a base64-encoded transaction envelope XDR string and must return the same transaction signed by the client domain account
     * as a base64-encoded transaction envelope XDR string. Used when the client domain signing key is not available locally (e.g., signing occurs on a separate server).
     * Callback signature: function(string $transactionXdr): string
     * @param string|null $clientDomainAccountId Expected client domain signing key, resolved from the local key pair or stellar.toml.
     * @param KeyPair|null $clientDomainKeyPair Optional local client domain signer, also included in $signers.
     * @return string the signed transaction as base64 encoded transaction envelope
     * @throws InvalidArgumentException if the callback answer is not a string or an envelope contains invalid base64, invalid XDR or trailing bytes.
     * @throws ChallengeValidationError if an envelope has an invalid type or the callback returns a different transaction body.
     * @throws ChallengeValidationErrorInvalidSignature if client domain authorization is missing or the callback path merges more than 20 signatures.
     * @throws \Soneso\StellarSDK\Crypto\CryptoException If a signer keypair fails to produce a signature
     */
    private function signTransaction(string $challengeTransaction, array $signers, ?callable $clientDomainSigningCallback = null,
                                     ?string $clientDomainAccountId = null, ?KeyPair $clientDomainKeyPair = null) : string {
        $res = base64_decode($challengeTransaction, true);
        if ($res === false) {
            throw new InvalidArgumentException('Invalid base64-encoded transaction envelope');
        }
        $xdr = new XdrBuffer($res);
        $envelopeXdr = XdrTransactionEnvelope::decode($xdr);
        $xdr->assertFullyConsumed(XdrTransactionEnvelope::class);
        if ($envelopeXdr->getType()->getValue() != XdrEnvelopeType::ENVELOPE_TYPE_TX) {
            throw new ChallengeValidationError("Invalid transaction type");
        }
        $txHash = AbstractTransaction::fromEnvelopeXdr($envelopeXdr)->hash($this->network);
        $validatedSignatures = $envelopeXdr->getV1()->getSignatures();
        $signatures = $validatedSignatures;
        $localDomainSignature = null;
        $appendSignature = null;
        if ($clientDomainSigningCallback !== null) {
            $validatedBody = $envelopeXdr->getV1()->getTx()->encode();
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
            if ($callbackEnvelope->getType()->getValue() != XdrEnvelopeType::ENVELOPE_TYPE_TX) {
                throw new ChallengeValidationError("Invalid transaction type");
            }
            if ($callbackEnvelope->getV1()->getTx()->encode() !== $validatedBody) {
                throw new ChallengeValidationError('Client domain signing callback returned a different transaction');
            }

            $signatures = [];
            $encodedSignatures = [];
            $appendSignature = static function (XdrDecoratedSignature $signature) use (&$signatures, &$encodedSignatures): void {
                $encoded = $signature->encode();
                if (!in_array($encoded, $encodedSignatures, true)) {
                    if (count($signatures) === 20) {
                        throw new ChallengeValidationErrorInvalidSignature('Invalid transaction envelope, more than 20 signatures');
                    }
                    $encodedSignatures[] = $encoded;
                    $signatures[] = $signature;
                }
            };
            foreach (array_merge($validatedSignatures, $callbackEnvelope->getV1()->getSignatures()) as $signature) {
                $appendSignature($signature);
            }

            if ($clientDomainAccountId !== null) {
                $requiresDomainSignature = false;
                foreach ($envelopeXdr->getV1()->getTx()->getOperations() as $operation) {
                    if ($operation->getBody()->getManageDataOperation()->getKey() === 'client_domain') {
                        $requiresDomainSignature = true;
                        break;
                    }
                }
                if ($requiresDomainSignature) {
                    $domainKey = KeyPair::fromAccountId($clientDomainAccountId);
                    $authorizationSignatures = $signatures;
                    if ($clientDomainKeyPair !== null) {
                        $localDomainSignature = $clientDomainKeyPair->signDecorated($txHash);
                        $authorizationSignatures[] = $localDomainSignature;
                    }
                    $authorized = false;
                    foreach ($authorizationSignatures as $signature) {
                        if ($signature->getHint() === $domainKey->getHint()
                            && $domainKey->verifySignature($signature->getSignature(), $txHash)) {
                            $authorized = true;
                            break;
                        }
                    }
                    if (!$authorized) {
                        throw new ChallengeValidationErrorInvalidSignature('Invalid client domain signature');
                    }
                }
            }
        }
        foreach ($signers as $signer) {
            if ($signer instanceof KeyPair) {
                $signature = $signer === $clientDomainKeyPair && $localDomainSignature !== null
                    ? $localDomainSignature : $signer->signDecorated($txHash);
                if ($appendSignature !== null) {
                    $appendSignature($signature);
                } else {
                    array_push($signatures, $signature);
                }
            }
        }
        $envelopeXdr->getV1()->setSignatures($signatures);
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
     * @return string Base64-encoded XDR transaction envelope.
     * @throws ChallengeRequestErrorResponse
     * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#challenge SEP-10 Challenge Endpoint
     */
    private function getChallenge(string $clientAccountId, int|string|null $memo = null, ?string $homeDomain = null, ?string $clientDomain = null) : string {

        $response = $this->getChallengeResponse($clientAccountId, $memo, $homeDomain, $clientDomain);
        $transaction = $response->getTransaction();
        if (!$transaction) {
            throw new ChallengeRequestErrorResponse("Error parsing challenge response");
        }
        return $transaction;
    }

    /**
     * Validates the challenge transaction received from the web auth server.
     *
     * Performs comprehensive validation according to SEP-10 requirements including sequence number,
     * signatures, time bounds, operations, source accounts, home domain, and web auth domain. The transaction source must be
     * an unmuxed account equal to the server signing key. A muxed source is rejected also when the server signing key is an M address.
     * Operation 0 must contain a 64-byte base64 nonce that strictly decodes to 48 bytes;
     * nonce entropy is not assessed.
     *
     * @param string $challengeTransaction Base64-encoded XDR transaction envelope to validate.
     * @param string $userAccountId The client account ID that should be authenticated.
     * @param string $expectedHomeDomain The resolved home domain: the first operation's key must equal this value followed by " auth".
     * @param string|null $clientDomainAccountId Optional client domain account for domain verification.
     * @param int|null $timeBoundsGracePeriod Optional grace period in seconds for time bounds validation.
     * @param int|string|null $memo The memo requested from the server: every id from 0 to 2^64-1, as an unsigned decimal string above PHP_INT_MAX.
     *                       It must be present in the challenge as an ID memo holding exactly that value. Null means no memo was requested;
     *                       a challenge that carries one anyway is accepted, provided its type is ID.
     * @throws ChallengeValidationError
     * @throws ChallengeValidationErrorInvalidHomeDomain
     * @throws ChallengeValidationErrorInvalidMemoType
     * @throws ChallengeValidationErrorInvalidMemoValue
     * @throws ChallengeValidationErrorInvalidSeqNr
     * @throws ChallengeValidationErrorInvalidSignature
     * @throws ChallengeValidationErrorInvalidSourceAccount
     * @throws ChallengeValidationErrorInvalidTimeBounds
     * @throws ChallengeValidationErrorInvalidWebAuthDomain
     * @throws ChallengeValidationErrorMemoAndMuxedAccount
     * @throws ChallengeValidationErrorInvalidOperationType
     * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#challenge SEP-10 Challenge Validation
     */
    private function validateChallenge(string $challengeTransaction, string $userAccountId, string $expectedHomeDomain, ?string $clientDomainAccountId = null, ?int $timeBoundsGracePeriod = null, int|string|null $memo = null) {
        $res = base64_decode($challengeTransaction, true);
        if ($res === false) {
            throw new InvalidArgumentException('Invalid base64-encoded challenge transaction');
        }
        $xdr = new XdrBuffer($res);
        $envelopeXdr = XdrTransactionEnvelope::decode($xdr);
        $xdr->assertFullyConsumed(XdrTransactionEnvelope::class);

        if ($envelopeXdr->getType()->getValue() != XdrEnvelopeType::ENVELOPE_TYPE_TX) {
            throw new ChallengeValidationError("Invalid transaction type received in challenge");
        }

        $transaction = $envelopeXdr->getV1()->getTx();
        if ($transaction->getSequenceNumber()->sequenceNumber->toString() != "0") {
            throw new ChallengeValidationErrorInvalidSeqNr("Invalid transaction, sequence number not 0");
        }
        if ($transaction->getSourceAccount()->getDiscriminant() !== XdrCryptoKeyType::KEY_TYPE_ED25519
            || MuxedAccount::fromXdr($transaction->getSourceAccount())->getAccountId() !== $this->serverSigningKey) {
            throw new ChallengeValidationErrorInvalidSourceAccount('Invalid transaction source account, expected the server signing key');
        }

        if ($transaction->getMemo()->getType()->getValue() != XdrMemoType::MEMO_NONE) {
            if (str_starts_with($userAccountId, "M")) {
                throw new ChallengeValidationErrorMemoAndMuxedAccount("Memo and muxed account (M...) found");
            } else if ($transaction->getMemo()->getType()->getValue() != XdrMemoType::MEMO_ID) {
                throw new ChallengeValidationErrorInvalidMemoType("invalid memo type");
            } else if ($memo !== null && Memo::fromXdr($transaction->getMemo())->getIdAsString() !== (string)$memo) {
                throw new ChallengeValidationErrorInvalidMemoValue("invalid memo value");
            }
        } else if ($memo !== null) {
            throw new ChallengeValidationErrorInvalidMemoValue("missing memo");
        }

        if (count($transaction->getOperations()) == 0) {
            throw new ChallengeValidationError("invalid number of operations (0)");
        }

        $operations = $transaction->getOperations();
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

            if ($count > 0) {
                if ($dataName == "client_domain") {
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
                $nonce = $dataValue->getValue();
                $decodedNonce = $nonce !== null ? base64_decode($nonce, true) : false;
                if ($nonce === null || strlen($nonce) !== 64 || $decodedNonce === false || strlen($decodedNonce) !== 48) {
                    throw new ChallengeValidationError('Invalid nonce in operation[0]: expected 64 base64 bytes encoding 48 bytes');
                }
            }
            if ($count > 0 && $dataName == "web_auth_domain") {
                $parse = parse_url($this->authEndpoint);
                $host = $parse['host'];
                if ($host != $dataValue->getValue()) {
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
        if ($timeBounds->getMinTimestamp() < 0 || $timeBounds->getMaxTimestamp() < 0 ||
            $currentTime < $timeBounds->getMinTimestamp() - $grace ||
            $currentTime > $timeBounds->getMaxTimestamp() + $grace) {
            throw new ChallengeValidationErrorInvalidTimeBounds(
                "Invalid transaction, invalid time bounds");
        }

        // The envelope is signed at the transaction level, so its signature is
        // verified once: it must carry exactly one signature, produced by the
        // server signing key over the transaction hash.
        $signatures = $envelopeXdr->getV1()->getSignatures();
        if (count($signatures) != 1) {
            throw new ChallengeValidationErrorInvalidSignature("Invalid transaction envelope, invalid number of signatures");
        }
        $firstSignature = $signatures[0];

        if (!$firstSignature instanceof XdrDecoratedSignature) {
            throw new ChallengeValidationErrorInvalidSignature("Invalid transaction envelope, invalid signature type");
        }
        // validate signature
        $serverKeyPair = KeyPair::fromAccountId($this->serverSigningKey);
        $transactionHash = (AbstractTransaction::fromEnvelopeXdr($envelopeXdr))->hash($this->network);
        $valid = $serverKeyPair->verifySignature($firstSignature->getSignature(), $transactionHash);
        if (!$valid) {
            throw new ChallengeValidationErrorInvalidSignature("Invalid transaction envelope, invalid signature");
        }
    }


    /**
     * @param string $accountId
     * @param int|string|null $memo Null means no memo; every id from 0 to 2^64-1, as an unsigned decimal string above PHP_INT_MAX, is sent as the memo request parameter
     *                       and is rejected if the account id is a muxed account (M...).
     * @param string|null $homeDomain
     * @param string|null $clientDomain
     * @return ChallengeResponse
     * @throws ChallengeRequestErrorResponse
     */
    private function getChallengeResponse(string $accountId, int|string|null $memo = null, ?string $homeDomain = null, ?string $clientDomain = null) : ChallengeResponse {
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
        if ($clientDomain) {
            $requestBuilder = $requestBuilder->forClientDomain($clientDomain);
        }
        try {
            return $requestBuilder->execute();
        } catch (HorizonRequestException $e) {
            throw new ChallengeRequestErrorResponse($e->getMessage(), $e->getStatusCode());
        }
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