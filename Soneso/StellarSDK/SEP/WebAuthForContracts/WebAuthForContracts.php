<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\Psr7\Utils as Psr7Utils;
use GuzzleHttp\RedirectMiddleware;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use phpseclib3\Math\BigInteger;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Util\UrlValidator;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\Toml\StellarToml;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Requests\SimulateTransactionRequest;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Soroban\SorobanAuthAddressGuard;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\Hash;
use Soneso\StellarSDK\Xdr\XdrContractDataDurability;
use Soneso\StellarSDK\Xdr\XdrEncoder;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrSCMapEntry;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizationEntries;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionDataExt;

/**
 * Implements SEP-45 Web Authentication for Contract Accounts protocol
 *
 * This class provides a complete implementation of the SEP-45 (Stellar Web Authentication for
 * Contract Accounts) protocol, which enables wallets and clients to prove they control a Soroban
 * contract account by signing authorization entries provided by an anchor's authentication server.
 *
 * SEP-45 is specifically for contract accounts (C... addresses). For traditional Stellar accounts
 * (G... and M... addresses), use SEP-10 (WebAuth class) instead.
 *
 * The authentication flow consists of four steps:
 * 1. Request authorization entries from the server (challenge)
 * 2. Validate and sign the entries with the client's private key(s)
 * 3. Simulate the signed entries via Soroban RPC and verify the read-write ledger footprint
 * 4. Submit the signed entries back to the server to receive a JWT token
 *
 * The JWT token can then be used to authenticate subsequent requests to other SEP
 * services such as SEP-24 (hosted deposits/withdrawals), SEP-31 (cross-border payments),
 * or SEP-12 (KYC). The token typically has a limited validity period.
 *
 * This implementation supports contract accounts and client domain verification for
 * non-custodial wallets.
 *
 * SECURITY WARNINGS:
 *
 * 1. Contract Address Validation (CRITICAL):
 *    Always verify that the contract_address in all authorization entries matches the
 *    WEB_AUTH_CONTRACT_ID from stellar.toml. This ensures the authorization is for the
 *    correct web authentication contract and prevents substitution attacks.
 *
 * 2. Sub-Invocations Check (CRITICAL):
 *    Always verify that no authorization entry contains sub-invocations. Sub-invocations
 *    could authorize additional unintended contract operations beyond authentication.
 *
 * 3. Server Signature Verification (CRITICAL):
 *    Always verify the challenge is signed by the server's signing key from stellar.toml. This
 *    prevents man-in-the-middle attacks where an attacker intercepts the authentication flow and
 *    provides a fake challenge to capture client signatures.
 *
 * 4. Function Name Validation (CRITICAL):
 *    Verify the function name is exactly "web_auth_verify". Any other function could perform
 *    unintended operations.
 *
 * 5. Args Validation (HIGH):
 *    Verify all function arguments match expected values including account, home_domain,
 *    web_auth_domain, web_auth_domain_account, and nonce. Inconsistencies could indicate
 *    a malicious or malformed challenge.
 *
 * 6. Nonce Consistency (HIGH):
 *    Verify the nonce is present and consistent across all authorization entries. The server
 *    generates the nonce for replay protection; SEP-45 asks the server to check that it was not
 *    used before.
 *
 * 7. Signature Expiration Ledger (HIGH):
 *    Set appropriate signature expiration ledger when signing to limit the validity window.
 *    This provides time-based replay protection.
 *
 * 8. Network Passphrase Validation (HIGH):
 *    When the challenge response includes a non-empty network_passphrase field, validate it matches
 *    the expected network. This prevents cross-network authentication attacks where signatures from
 *    one network could be replayed on another network.
 *
 * 9. JWT Token Security (HIGH):
 *    Store JWT tokens securely and never expose them in logs, URLs, or insecure storage. Tokens
 *    grant access to authenticated services and should be treated as credentials. Use HTTPS for
 *    all requests with tokens.
 *
 * 10. Network Passphrase Consistency:
 *     Use the correct network passphrase (testnet or pubnet) when signing. Mixing passphrases
 *     can lead to signature validation failures or security vulnerabilities.
 *
 * 11. Simulation and Footprint Verification (CRITICAL):
 *     Simulate the signed authorization entries via Soroban RPC in enforcing auth mode before
 *     submitting them to the server and verify that the read-write footprint contains only
 *     permitted entries: nonce contract_data entries for the client, server, and optional client
 *     domain accounts, plus entries listed for restoration in SorobanTransactionData.ext (a
 *     persistent non-nonce contract_data entry for the client contract, a persistent contract
 *     instance for WEB_AUTH_CONTRACT_ID, or a CONTRACT_CODE entry). Allowing restored client
 *     contract data is an SDK policy beyond the footprint list of SEP-45 lines 113-127, because
 *     the entry belongs to the caller's own contract, only that contract can write it, and an
 *     archived client contract could not authenticate otherwise. This check verifies the
 *     permitted read_write footprint; listing an entry for restoration marks it for restoration
 *     (CAP-66) and proves neither that it was archived nor that the invocation leaves it unchanged.
 *
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Specification
 * @see StellarToml For discovering the auth endpoint
 */
class WebAuthForContracts
{
    /**
     * Neutral source account used locally when simulating signed SEP-45 authorization entries.
     */
    private const SIMULATION_SOURCE_ACCOUNT = 'GAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWHF';

    private string $authEndpoint;
    private string $webAuthContractId;
    private string $serverSigningKey;
    private string $serverHomeDomain;
    private Network $network;
    private Client $httpClient;
    private bool $useFormUrlEncoded = true;
    private string $sorobanRpcUrl;
    private ?SorobanServer $sorobanServer = null;

    /**
     * Constructor.
     *
     * @param string $authEndpoint WEB_AUTH_FOR_CONTRACTS_ENDPOINT from stellar.toml
     * @param string $webAuthContractId WEB_AUTH_CONTRACT_ID from stellar.toml (valid C... contract strkey)
     * @param string $serverSigningKey SIGNING_KEY from stellar.toml (valid G... ed25519 account strkey)
     * @param string $serverHomeDomain The server home domain where the stellar.toml was loaded from
     * @param Network $network The network used (testnet, pubnet, or a custom network)
     * @param Client|null $httpClient Optional HTTP client to be used for requests
     * @param string|null $sorobanRpcUrl Optional Soroban RPC URL. Defaults to https://soroban-testnet.stellar.org
     *                                   for the testnet network; required for any other network, including the
     *                                   public network.
     * @throws InvalidArgumentException if any parameter is invalid or $sorobanRpcUrl is omitted for a non-testnet network
     */
    public function __construct(
        string $authEndpoint,
        string $webAuthContractId,
        string $serverSigningKey,
        string $serverHomeDomain,
        Network $network,
        ?Client $httpClient = null,
        ?string $sorobanRpcUrl = null
    ) {
        // Validate full strkey checksums upfront rather than prefix only (spec 47, 52, 63-65)
        // so malformed IDs fail at construction instead of later during signing or verification.
        if (!StrKey::isValidContractId($webAuthContractId)) {
            throw new InvalidArgumentException(
                "webAuthContractId must be a valid contract address (C...)"
            );
        }

        if (!StrKey::isValidAccountId($serverSigningKey)) {
            throw new InvalidArgumentException(
                "serverSigningKey must be a valid ed25519 account address (G...)"
            );
        }

        // SDK transport security policy: require HTTPS for auth endpoints (localhost HTTP allowed
        // for local development via UrlValidator).
        UrlValidator::validateHttpsRequired($authEndpoint);

        // Compare trimmed value explicitly rather than using empty() so "0" is accepted as non-empty.
        if (trim($serverHomeDomain) === '') {
            throw new InvalidArgumentException(
                "serverHomeDomain must not be empty"
            );
        }

        $this->authEndpoint = $authEndpoint;
        $this->webAuthContractId = $webAuthContractId;
        $this->serverSigningKey = $serverSigningKey;
        $this->serverHomeDomain = $serverHomeDomain;
        $this->network = $network;

        if ($httpClient === null) {
            $this->httpClient = new Client();
        } else {
            $this->httpClient = $httpClient;
        }

        // Default the Soroban RPC URL only for the Stellar testnet. No public-network RPC
        // endpoint is operated for general use, so callers on any other network choose a provider.
        if ($sorobanRpcUrl === null) {
            $passphrase = $this->network->getNetworkPassphrase();
            if ($passphrase === Network::testnet()->getNetworkPassphrase()) {
                $this->sorobanRpcUrl = 'https://soroban-testnet.stellar.org';
            } else {
                throw new InvalidArgumentException(
                    "sorobanRpcUrl is required for network passphrase '$passphrase'; only testnet has a default"
                );
            }
        } else {
            // SDK transport security policy: require HTTPS for custom RPC URLs (localhost allowed).
            UrlValidator::validateHttpsRequired($sorobanRpcUrl);
            $this->sorobanRpcUrl = $sorobanRpcUrl;
        }
    }

    /**
     * Creates a WebAuthForContracts instance by loading the needed data from the stellar.toml
     * file hosted on the given domain.
     *
     * Example: fromDomain("soneso.com", Network::testnet())
     *
     * @param string $domain The domain from which to get the stellar information
     * @param Network $network The network used (testnet, pubnet, or a custom network)
     * @param Client|null $httpClient Optional HTTP client to be used for requests
     * @param string|null $sorobanRpcUrl Optional Soroban RPC URL. Defaults to https://soroban-testnet.stellar.org
     *                                   for the testnet network; required for any other network, including the
     *                                   public network.
     * @return WebAuthForContracts configured instance
     * @throws InvalidArgumentException if discovered parameters are invalid or $sorobanRpcUrl is omitted for a non-testnet network
     * @throws Exception if stellar.toml cannot be fetched or parsed, or required fields are missing from it
     */
    public static function fromDomain(
        string $domain,
        Network $network,
        ?Client $httpClient = null,
        ?string $sorobanRpcUrl = null
    ): WebAuthForContracts {
        $stellarToml = StellarToml::fromDomain($domain, $httpClient);
        $generalInfo = $stellarToml->getGeneralInformation();
        $webAuthForContractsEndpoint = $generalInfo?->webAuthForContractsEndpoint;
        $webAuthContractId = $generalInfo?->webAuthContractId;
        $signingKey = $generalInfo?->signingKey;

        // Compare explicitly against null and '' instead of PHP truthiness so a literal "0" value
        // in stellar.toml is treated as present and forwarded to constructor validation (spec 71-73).
        if ($webAuthForContractsEndpoint === null || $webAuthForContractsEndpoint === '') {
            throw new Exception("No WEB_AUTH_FOR_CONTRACTS_ENDPOINT found in stellar.toml");
        }
        if ($webAuthContractId === null || $webAuthContractId === '') {
            throw new Exception("No WEB_AUTH_CONTRACT_ID found in stellar.toml");
        }
        if ($signingKey === null || $signingKey === '') {
            throw new Exception("No auth server SIGNING_KEY found in stellar.toml");
        }

        return new WebAuthForContracts(
            $webAuthForContractsEndpoint,
            $webAuthContractId,
            $signingKey,
            $domain,
            $network,
            $httpClient,
            $sorobanRpcUrl
        );
    }

    /**
     * Sets whether to use application/x-www-form-urlencoded when submitting challenges.
     * By default, application/x-www-form-urlencoded is used.
     *
     * @param bool $useFormUrlEncoded true to use form-urlencoded, false for JSON
     */
    public function setUseFormUrlEncoded(bool $useFormUrlEncoded): void
    {
        $this->useFormUrlEncoded = $useFormUrlEncoded;
    }

    /**
     * Executes the complete SEP-45 authentication flow.
     *
     * This method:
     * 1. Requests a challenge from the server
     * 2. Validates the authorization entries
     * 3. Signs the client entry with provided signers and stamps the signature expiration ledger
     *    on the client and client domain entries
     * 4. Simulates the signed entries via Soroban RPC and verifies the read-write ledger footprint
     * 5. Submits the signed entries to obtain a JWT token
     *
     * A client domain signing callback must preserve the supplied entry's credentials arm, address,
     * nonce, root invocation and complete delegate tree. Only the top-level address signature and
     * signature expiration ledger may change; both are submitted as supplied by the callback.
     * An answer that cannot be encoded and decoded, or changes any other part of the entry, throws
     * InvalidArgumentException before the token request is sent. The callback's signature is not
     * verified and its expiration ledger is not checked for freshness.
     *
     * @param string $clientAccountId Valid contract account strkey (C...) to authenticate
     * @param array<KeyPair> $signers Keypairs with private keys to sign the client authorization entry. For contracts
     *                                that implement __check_auth with signature verification, provide
     *                                the keypairs with sufficient weight to meet the contract's
     *                                authentication requirements. Can be empty for contracts whose
     *                                __check_auth implementation does not require signatures (per SEP-45).
     *                                Any non-KeyPair element or public-key-only KeyPair is rejected before
     *                                the challenge request.
     * @param string|null $homeDomain Optional home domain for the challenge request. If not provided,
     *                                defaults to the server home domain from stellar.toml.
     * @param string|null $clientDomain Optional client domain for verification. Must not be empty;
     *                                  every other non-null value is sent as client_domain and requires
     *                                  a $clientDomainKeyPair with a private key or a $clientDomainSigningCallback.
     *                                  If the server omits both client_domain and client_domain_account from the
     *                                  challenge arguments, authentication continues without client domain
     *                                  signing, callback invocation, or a stellar.toml lookup.
     * @param KeyPair|null $clientDomainKeyPair Optional keypair for client domain verification. When it
     *                                          includes a private key, it signs the client domain entry
     *                                          locally and $clientDomainSigningCallback is not invoked
     *                                          (even when both are passed) and no stellar.toml lookup is
     *                                          performed. When it contains only a public key, it supplies
     *                                          the Client Domain Account ID without a stellar.toml lookup
     *                                          and $clientDomainSigningCallback must be provided to sign
     *                                          the entry (otherwise InvalidArgumentException is thrown
     *                                          before the first request).
     * @param callable|null $clientDomainSigningCallback Optional callback for remote client domain signing,
     *                                                    used when $clientDomainKeyPair is null (resolving
     *                                                    the account ID from stellar.toml) or contains only
     *                                                    a public key. Not invoked when $clientDomainKeyPair
     *                                                    carries a private key or when a shared server entry
     *                                                    already carries a valid server signature.
     *                                                    Callback signature: function(SorobanAuthorizationEntry $authEntry): SorobanAuthorizationEntry
     * @param int|null $signatureExpirationLedger Optional expiration ledger for authorization entries (for
     *                                            replay protection). When null, automatically fetched from
     *                                            Soroban RPC and set to current ledger + 10 (approximately
     *                                            50-60 seconds), including when $signers is empty. Applied to
     *                                            the client entry (even when it carries no signature) and to
     *                                            the locally signed client domain entry, where any existing
     *                                            signature's committed expiration ledger is preserved and a
     *                                            conflicting value throws InvalidArgumentException. On the
     *                                            client domain callback path (unless a shared server entry
     *                                            already carries a valid server signature), the expiration
     *                                            ledger is written directly to the entry before the callback
     *                                            is invoked without conflict checking, and the callback may
     *                                            still set its own expiration ledger on the entry it signs.
     * @return string JWT token that can be used to authenticate requests to protected services
     * @throws ContractChallengeValidationError
     * @throws ContractChallengeValidationErrorInvalidAccount
     * @throws ContractChallengeValidationErrorInvalidArgs
     * @throws ContractChallengeValidationErrorInvalidContractAddress
     * @throws ContractChallengeValidationErrorInvalidFunctionName
     * @throws ContractChallengeValidationErrorInvalidHomeDomain
     * @throws ContractChallengeValidationErrorInvalidNonce
     * @throws ContractChallengeValidationErrorInvalidServerSignature
     * @throws ContractChallengeValidationErrorInvalidWebAuthDomain
     * @throws ContractChallengeValidationErrorInvalidNetworkPassphrase
     * @throws ContractChallengeValidationErrorMissingClientEntry
     * @throws ContractChallengeValidationErrorMissingServerEntry
     * @throws ContractChallengeValidationErrorSimulationFailed
     * @throws ContractChallengeValidationErrorSubInvocationsFound
     * @throws ContractChallengeRequestErrorResponse
     * @throws SubmitContractChallengeErrorResponseException
     * @throws SubmitContractChallengeTimeoutResponseException
     * @throws SubmitContractChallengeUnknownResponseException
     * @throws GuzzleException
     * @throws RuntimeException if fetching the latest ledger or communicating with Soroban RPC fails
     * @throws InvalidArgumentException for invalid arguments, an endpoint query containing a reserved
     * parameter (account, home_domain, or client_domain), a public-only $clientDomainKeyPair without a
     * callback when $clientDomain is requested, a malformed callback answer, or a callback answer that
     * changes the authorization entry beyond its top-level address signature and signature expiration ledger
     */
    public function jwtToken(
        string $clientAccountId,
        array $signers,
        ?string $homeDomain = null,
        ?string $clientDomain = null,
        ?KeyPair $clientDomainKeyPair = null,
        ?callable $clientDomainSigningCallback = null,
        ?int $signatureExpirationLedger = null
    ): string {
        // Validate account, signers, and client domain configuration before issuing the challenge
        // GET so invalid caller configuration fails locally without network requests; the
        // signature expiration ledger is handled later. Reject non-C... addresses without echoing
        // the value (spec 24, 54-55, 224) so mistyped secrets cannot leak into error messages.
        if (!StrKey::isValidContractId($clientAccountId)) {
            throw new InvalidArgumentException("Client account must be a valid contract address (C...)");
        }

        $this->validateSigners($signers);

        // Reject an empty string explicitly; every other non-null value (including "0") is treated
        // as a requested client_domain (spec 226). Require signing credentials before the GET,
        // while deferring stellar.toml resolution until the challenge confirms server support.
        if ($clientDomain === '') {
            throw new InvalidArgumentException("Client domain must not be empty");
        }
        if ($clientDomain !== null) {
            if ($clientDomainKeyPair === null && $clientDomainSigningCallback === null) {
                throw new InvalidArgumentException("Client domain key pair or client domain signing callback is missing");
            }
            // A public-only key pair identifies the Client Domain Account without a stellar.toml
            // lookup, but cannot sign locally; require a signing callback before the first request.
            if ($clientDomainKeyPair !== null && $clientDomainKeyPair->getPrivateKey() === null && $clientDomainSigningCallback === null) {
                throw new InvalidArgumentException(
                    "Client domain key pair for account " . $clientDomainKeyPair->getAccountId()
                        . " must include a private key when no signing callback is provided"
                );
            }
        }

        // Use server home domain as default if not provided
        $effectiveHomeDomain = $homeDomain ?? $this->serverHomeDomain;

        // Get the challenge authorization entries from the web auth server
        $challengeResponse = $this->getChallenge($clientAccountId, $effectiveHomeDomain, $clientDomain);

        // Validate network_passphrase when the server includes a non-empty value (spec 264-267, 593-595).
        // An empty string is treated as absent so servers emitting an empty optional field are accepted.
        $responseNetworkPassphrase = $challengeResponse->getNetworkPassphrase();
        if ($responseNetworkPassphrase !== null && $responseNetworkPassphrase !== '') {
            $expectedNetworkPassphrase = $this->network->getNetworkPassphrase();
            if ($responseNetworkPassphrase !== $expectedNetworkPassphrase) {
                throw new ContractChallengeValidationErrorInvalidNetworkPassphrase(
                    "Network passphrase mismatch. Expected: '$expectedNetworkPassphrase', Got: '$responseNetworkPassphrase'"
                );
            }
        }

        $authEntries = $this->decodeAuthorizationEntries($challengeResponse->getAuthorizationEntries());

        // SEP-45 (spec 226, 257-262, 560-563) lets a server ignore an unsupported client_domain by
        // omitting both client_domain and client_domain_account from the challenge args. Inspect
        // that argument pair before resolving stellar.toml so ignored domains skip both the TOML
        // lookup and client domain signing.
        $clientDomainAccountId = null;
        $effectiveClientDomain = null;
        $effectiveClientDomainKeyPair = null;
        $effectiveClientDomainSigningCallback = null;
        if ($clientDomain !== null && $this->challengeIncludesClientDomain($authEntries)) {
            $effectiveClientDomain = $clientDomain;
            if ($clientDomainKeyPair !== null) {
                $effectiveClientDomainKeyPair = $clientDomainKeyPair;
                $clientDomainAccountId = $clientDomainKeyPair->getAccountId();
                // A key pair with a private key signs locally without invoking the callback;
                // a public-only key pair supplies the Client Domain Account ID without a
                // stellar.toml lookup and delegates signing to the callback (spec 110-112).
                if ($clientDomainKeyPair->getPrivateKey() === null) {
                    $effectiveClientDomainSigningCallback = $clientDomainSigningCallback;
                }
            } else {
                $effectiveClientDomainSigningCallback = $clientDomainSigningCallback;
                try {
                    $toml = StellarToml::fromDomain($clientDomain, $this->httpClient);
                    $clientDomainAccountId = $toml->generalInformation?->signingKey;
                    // Check explicitly against null and '' so "0" is validated rather than treated as missing.
                    if ($clientDomainAccountId === null || $clientDomainAccountId === '') {
                        throw new Exception("Could not find signing key in stellar.toml");
                    }
                    // SEP-45 (spec 549-550) requires the client domain stellar.toml SIGNING_KEY to
                    // be a Stellar public key in the form of a G... address; omit the raw value
                    // from the error message so mistyped secrets cannot leak.
                    if (!StrKey::isValidAccountId($clientDomainAccountId)) {
                        throw new Exception("SIGNING_KEY in stellar.toml must be a valid ed25519 account address (G...)");
                    }
                } catch (Exception $e) {
                    throw new InvalidArgumentException("Invalid client domain: " . $e->getMessage());
                }
            }
        }

        // Validate the authorization entries
        $this->validateChallenge(
            $authEntries,
            $clientAccountId,
            $effectiveHomeDomain,
            $clientDomainAccountId,
            $effectiveClientDomain
        );

        // SEP-45 (spec 525-537) recommends setting a near-future signature expiration ledger and
        // requires clients to enforce signature expiration ledgers for replay protection. Auto-fill
        // latest + 10 even when $signers is empty because the Soroban host enforces expiration on
        // unsigned address credentials too and any client domain entry needs an enforced expiration.
        $effectiveExpirationLedger = $signatureExpirationLedger;
        if ($effectiveExpirationLedger === null) {
            $latestLedgerResponse = $this->getSorobanServer()->getLatestLedger();
            if ($latestLedgerResponse->sequence === null) {
                throw new RuntimeException("Failed to get current ledger from Soroban RPC");
            }
            $effectiveExpirationLedger = $latestLedgerResponse->sequence + 10;
        }

        // Sign the authorization entries
        $signedEntries = $this->signAuthorizationEntries(
            $authEntries,
            $clientAccountId,
            $signers,
            $effectiveExpirationLedger,
            $effectiveClientDomainKeyPair,
            $effectiveClientDomainSigningCallback,
            $clientDomainAccountId
        );

        // Simulate the signed entries and verify the permitted read_write footprint before
        // submitting them to the server (spec 113-127); restoration metadata marks listed entries for restoration
        // (CAP-66) and proves neither that they were archived nor that the invocation leaves them unchanged.
        $this->verifySimulation($signedEntries, $clientAccountId, $clientDomainAccountId);

        // Request the JWT token by sending back the signed authorization entries
        return $this->sendSignedChallenge($signedEntries);
    }

    /**
     * Requests a challenge from the authentication server.
     *
     * @param string $clientAccountId Valid contract account strkey (C...) to authenticate
     * @param string|null $homeDomain Optional home domain for the request. If not provided,
     *                                defaults to the server home domain from stellar.toml.
     * @param string|null $clientDomain Optional client domain (must not be empty)
     * @return ContractChallengeResponse The challenge response
     * @throws ContractChallengeRequestErrorResponse on request failure or non-200 HTTP status
     * @throws InvalidArgumentException if $clientAccountId is not a valid contract strkey, $clientDomain
     *                                  is an empty string, or the auth endpoint query contains a reserved
     *                                  request parameter (account, home_domain, or client_domain)
     */
    public function getChallenge(
        string $clientAccountId,
        ?string $homeDomain = null,
        ?string $clientDomain = null
    ): ContractChallengeResponse {
        // Reject invalid C... addresses before the GET without echoing the value (spec 24, 224).
        if (!StrKey::isValidContractId($clientAccountId)) {
            throw new InvalidArgumentException("Client account must be a valid contract address (C...)");
        }
        // Reject empty string explicitly while treating every other non-null value (including "0") as requested.
        if ($clientDomain === '') {
            throw new InvalidArgumentException("Client domain must not be empty");
        }
        $effectiveHomeDomain = $homeDomain ?? $this->serverHomeDomain;
        return $this->getChallengeResponse($clientAccountId, $effectiveHomeDomain, $clientDomain);
    }

    /**
     * Validates the authorization entries from the challenge response.
     *
     * Validation steps:
     * 1. Each entry has no sub-invocations
     * 2. contract_address matches WEB_AUTH_CONTRACT_ID
     * 3. function_name is "web_auth_verify"
     * 4. Each entry has a single Map<Symbol, String> argument with unique keys, equal across all entries
     * 5. Argument values match expected account, home_domain, web_auth_domain (case-insensitive host,
     *    or either host or host:port when the auth endpoint specifies an explicit port),
     *    web_auth_domain_account, non-empty nonce, and client domain values when present
     * 6. Server entry exists and contains a valid signature from the server signing key
     * 7. Client entry exists, and client domain entry exists when client domain arguments are present
     * 8. No duplicate entries exist for the server, client, or active client domain account across
     *    ADDRESS, ADDRESS_V2, and ADDRESS_WITH_DELEGATES credentials, except up to two entries for
     *    the server address when the challenge includes the client domain role and both share that
     *    address (each verified for a valid server signature). Entries for other addresses pass.
     *
     * Roles are recognized independently, so one entry or two server-signed entries can serve both
     * the server and client domain roles when both use the same key. If a client domain was requested
     * and the server omitted both client_domain and client_domain_account from the arguments,
     * validation accepts the challenge without requiring a client domain entry.
     *
     * @param array<SorobanAuthorizationEntry> $authEntries Entries to validate
     * @param string $clientAccountId Expected client account
     * @param string|null $homeDomain Optional expected home domain. If not provided,
     *                                defaults to the server home domain from stellar.toml.
     * @param string|null $clientDomainAccountId Expected client domain account
     * @param string|null $clientDomain Optional expected client domain (must not be empty). When null with
     *                                  $clientDomainAccountId set, client_domain_account is checked
     *                                  and the client_domain value is not compared.
     * @throws ContractChallengeValidationError on validation failure
     * @throws ContractChallengeValidationErrorInvalidAccount
     * @throws ContractChallengeValidationErrorInvalidArgs
     * @throws ContractChallengeValidationErrorInvalidContractAddress
     * @throws ContractChallengeValidationErrorInvalidFunctionName
     * @throws ContractChallengeValidationErrorInvalidHomeDomain
     * @throws ContractChallengeValidationErrorInvalidNonce
     * @throws ContractChallengeValidationErrorInvalidServerSignature
     * @throws ContractChallengeValidationErrorInvalidWebAuthDomain
     * @throws ContractChallengeValidationErrorMissingClientEntry
     * @throws ContractChallengeValidationErrorMissingServerEntry
     * @throws ContractChallengeValidationErrorSubInvocationsFound
     * @throws InvalidArgumentException if $clientDomain is empty, or if $clientDomain is provided without
     *                                  $clientDomainAccountId for a challenge containing client domain arguments
     */
    public function validateChallenge(
        array $authEntries,
        string $clientAccountId,
        ?string $homeDomain = null,
        ?string $clientDomainAccountId = null,
        ?string $clientDomain = null
    ): void {
        if ($clientDomain === '') {
            throw new InvalidArgumentException("Client domain must not be empty");
        }
        if ($authEntries === []) {
            throw new ContractChallengeValidationError("No authorization entries found");
        }

        // Use server home domain as default if not provided
        $effectiveHomeDomain = $homeDomain ?? $this->serverHomeDomain;

        // Extract host and optional explicit port from the auth endpoint URL for web_auth_domain validation.
        $parse = parse_url($this->authEndpoint);
        $webAuthDomainHost = strtolower($parse['host'] ?? '');
        $webAuthDomainWithPort = isset($parse['port'])
            ? $webAuthDomainHost . ':' . $parse['port']
            : null;

        $expectedContractIdHex = null;
        /** @var array<string, string>|null $firstArgs */
        $firstArgs = null;

        // Pass 1: Validate invocation structure and args map format/equality across all entries
        foreach ($authEntries as $entry) {
            if (!($entry instanceof SorobanAuthorizationEntry)) {
                throw new ContractChallengeValidationError("Invalid authorization entry type");
            }

            $rootInvocation = $entry->rootInvocation;

            // Check 1: No sub-invocations
            if (count($rootInvocation->subInvocations) > 0) {
                throw new ContractChallengeValidationErrorSubInvocationsFound(
                    "Authorization entry contains sub-invocations"
                );
            }

            // Check 2: Function must be contract function
            $function = $rootInvocation->function;
            if ($function->contractFn === null) {
                throw new ContractChallengeValidationError("Authorization entry is not a contract function");
            }

            // Check 3: Contract address matches WEB_AUTH_CONTRACT_ID
            $contractAddress = Address::fromXdr($function->contractFn->contractAddress);
            if ($contractAddress->type !== Address::TYPE_CONTRACT) {
                throw new ContractChallengeValidationErrorInvalidContractAddress(
                    "Contract address is not a contract type"
                );
            }
            $expectedContractIdHex ??= StrKey::decodeContractIdHex($this->webAuthContractId);
            if ($contractAddress->contractId !== $expectedContractIdHex) {
                throw new ContractChallengeValidationErrorInvalidContractAddress(
                    "Contract address does not match WEB_AUTH_CONTRACT_ID"
                );
            }

            // Check 4: Function name is "web_auth_verify"
            $functionName = $function->contractFn->functionName;
            if ($functionName !== "web_auth_verify") {
                throw new ContractChallengeValidationErrorInvalidFunctionName(
                    "Function name is not 'web_auth_verify': $functionName"
                );
            }

            // Check 5: Extract and compare the complete args map (including unknown keys) across
            // all entries (spec 92-93). Check nonce divergence first so a nonce mismatch throws
            // the more specific InvalidNonce exception rather than generic InvalidArgs (spec 263).
            $args = $this->extractArgsFromEntry($entry);
            if ($firstArgs === null) {
                $firstArgs = $args;
            } else if (!$this->areArgsMapsEqual($firstArgs, $args)) {
                if (($firstArgs['nonce'] ?? null) !== ($args['nonce'] ?? null)) {
                    throw new ContractChallengeValidationErrorInvalidNonce(
                        "Nonce is not consistent across authorization entries"
                    );
                }
                throw new ContractChallengeValidationErrorInvalidArgs(
                    "Arguments are not consistent across authorization entries"
                );
            }
        }

        // Value checks run once after all entries' argument maps are confirmed equal.
        // Validate account
        if (!isset($firstArgs['account']) || $firstArgs['account'] !== $clientAccountId) {
            throw new ContractChallengeValidationErrorInvalidAccount(
                "Account argument does not match client account"
            );
        }

        // Validate home_domain
        if (!isset($firstArgs['home_domain']) || $firstArgs['home_domain'] !== $effectiveHomeDomain) {
            throw new ContractChallengeValidationErrorInvalidHomeDomain(
                "Home domain argument does not match expected home domain"
            );
        }

        // Validate web_auth_domain (case-insensitive). The specification says only "the Server's
        // domain" (spec 96, 141, 255) and servers differ on whether an explicit endpoint port is
        // included, so both host and host:port are accepted when the endpoint specifies a port.
        // Without a port in the endpoint URL, only the bare host matches.
        $actualWebAuthDomain = isset($firstArgs['web_auth_domain']) ? strtolower($firstArgs['web_auth_domain']) : null;
        $webAuthDomainMatches = $actualWebAuthDomain !== null && (
            $actualWebAuthDomain === $webAuthDomainHost
            || ($webAuthDomainWithPort !== null && $actualWebAuthDomain === $webAuthDomainWithPort)
        );
        if (!$webAuthDomainMatches) {
            throw new ContractChallengeValidationErrorInvalidWebAuthDomain(
                "Web auth domain argument does not match server domain"
            );
        }

        // Validate web_auth_domain_account
        if (!isset($firstArgs['web_auth_domain_account']) || $firstArgs['web_auth_domain_account'] !== $this->serverSigningKey) {
            throw new ContractChallengeValidationErrorInvalidArgs(
                "Web auth domain account does not match server signing key"
            );
        }

        // Validate nonce presence and non-emptiness (spec 263, 444-448, 511): an empty string is
        // treated as missing.
        if (!isset($firstArgs['nonce']) || $firstArgs['nonce'] === '') {
            throw new ContractChallengeValidationErrorInvalidNonce("Nonce argument is missing or empty");
        }

        // Validate client domain arguments (spec 98-101, 226, 257-262, 560-563).
        // When a client domain was requested but the challenge carries neither argument, the
        // server ignored client_domain as spec 226 and 560-563 permit, so validation passes
        // without requiring a client domain entry.
        $hasClientDomainArg = array_key_exists('client_domain', $firstArgs);
        $hasClientDomainAccountArg = array_key_exists('client_domain_account', $firstArgs);
        $hasAnyClientDomainArg = $hasClientDomainArg || $hasClientDomainAccountArg;
        $clientDomainRequested = $clientDomainAccountId !== null || $clientDomain !== null;
        $expectClientDomainEntry = false;

        if ($hasAnyClientDomainArg) {
            // Robustness policy (spec 98-101 is silent on unsolicited client domain args): reject
            // challenges carrying client domain args when none was requested so the client signs
            // only what it asked for.
            if (!$clientDomainRequested) {
                throw new ContractChallengeValidationErrorInvalidArgs(
                    "Challenge contains client domain arguments when no client domain was requested"
                );
            }
            // Spec 98-101 and 257-262 define client_domain and client_domain_account as a pair.
            if (!$hasClientDomainArg || !$hasClientDomainAccountArg) {
                throw new ContractChallengeValidationErrorInvalidArgs(
                    "Both client_domain and client_domain_account arguments must be present"
                );
            }
            // For compatibility with direct callers that pass only $clientDomainAccountId and
            // omit the optional $clientDomain parameter, skip the client_domain value comparison
            // when $clientDomain is null while still requiring the argument pair above and
            // verifying client_domain_account below.
            if ($clientDomain !== null && $firstArgs['client_domain'] !== $clientDomain) {
                throw new ContractChallengeValidationErrorInvalidArgs(
                    "Client domain argument does not match expected value"
                );
            }
            // Caller configuration error: validating client_domain_account (spec 100-101) requires
            // the expected Client Domain Account ID when the challenge carries client domain args.
            if ($clientDomainAccountId === null) {
                throw new InvalidArgumentException(
                    "clientDomainAccountId is required when validating a challenge with client domain arguments"
                );
            }
            if ($firstArgs['client_domain_account'] !== $clientDomainAccountId) {
                throw new ContractChallengeValidationErrorInvalidArgs(
                    "Client domain account does not match expected value"
                );
            }
            $expectClientDomainEntry = true;
        }

        // Pass 2: Validate credentials, count entries per role across all three address arms
        // (ADDRESS, ADDRESS_V2, ADDRESS_WITH_DELEGATES), and verify server signatures.
        // SEP-45 (spec 239-245) describes one authorization entry each for the Server Account,
        // the Client Account, and optionally the Client Domain Account. Duplicate entries for a
        // role are rejected, with one exception: when the challenge includes the client domain
        // role and the Server Account and Client Domain Account are the same address, up to two
        // entries for that address are accepted because the specification does not say whether
        // such a server sends one shared entry or two separate entries; a third entry is rejected,
        // and each entry for the server address must carry a valid server signature. Entries for
        // any other address pass validation and are not signed by the client.
        $serverEntryCount = 0;
        $clientEntryCount = 0;
        $clientDomainEntryCount = 0;
        $maxServerEntries = ($expectClientDomainEntry && $clientDomainAccountId === $this->serverSigningKey) ? 2 : 1;

        foreach ($authEntries as $entry) {
            // All three address arms (ADDRESS, ADDRESS_V2, ADDRESS_WITH_DELEGATES) are recognized.
            // Source-account credentials cannot carry an address and are rejected.
            $credentials = $entry->credentials;
            if ($credentials->isSourceAccount()) {
                throw new ContractChallengeValidationError(
                    "Authorization entry uses source-account credentials; an address credential arm is required"
                );
            }
            $innerCreds = $credentials->getAddressCredentials();
            if ($innerCreds !== null) {
                $credentialsAddressStr = $innerCreds->address->toStrKey();

                // Roles are checked with independent if statements (not else-if) so one entry can
                // satisfy both the server and client domain roles when both use the same key.
                if ($credentialsAddressStr === $this->serverSigningKey) {
                    $serverEntryCount++;
                    if ($serverEntryCount > $maxServerEntries) {
                        throw new ContractChallengeValidationError(
                            "Duplicate authorization entry for server account: {$this->serverSigningKey}"
                        );
                    }
                    // Verify server signature on every entry for the server address
                    if (!$this->verifyServerSignature($entry, $this->network)) {
                        throw new ContractChallengeValidationErrorInvalidServerSignature(
                            "Server authorization entry has invalid signature"
                        );
                    }
                }
                if ($credentialsAddressStr === $clientAccountId) {
                    $clientEntryCount++;
                    if ($clientEntryCount > 1) {
                        throw new ContractChallengeValidationError(
                            "Duplicate authorization entry for client account: $clientAccountId"
                        );
                    }
                }
                if ($expectClientDomainEntry && $credentialsAddressStr === $clientDomainAccountId) {
                    $clientDomainEntryCount++;
                    // When Server Account and Client Domain Account share the same key, the server
                    // branch above enforces the cap of two entries for that address.
                    if ($clientDomainAccountId !== $this->serverSigningKey && $clientDomainEntryCount > 1) {
                        throw new ContractChallengeValidationError(
                            "Duplicate authorization entry for client domain account: $clientDomainAccountId"
                        );
                    }
                }
            }
        }

        // Check 6: Server entry must exist
        if ($serverEntryCount === 0) {
            throw new ContractChallengeValidationErrorMissingServerEntry(
                "No authorization entry found for server account"
            );
        }

        // Check 7: Client entry must exist
        if ($clientEntryCount === 0) {
            throw new ContractChallengeValidationErrorMissingClientEntry(
                "No authorization entry found for client account"
            );
        }

        // Check 8: Client domain entry must exist when client domain arguments are present
        if ($expectClientDomainEntry && $clientDomainEntryCount === 0) {
            throw new ContractChallengeValidationErrorMissingClientEntry(
                "No authorization entry found for client domain account"
            );
        }
    }

    /**
     * Signs the authorization entries for the client account and stamps expiration ledgers.
     *
     * In a manual flow, callers must call validateChallenge() before calling this method.
     * This method does not validate the entries and signs any matching client or client domain entry.
     *
     * When $signatureExpirationLedger is non-null, it is applied to the client entry even when
     * $signers is empty and to the locally signed client domain entry; in both paths any existing
     * signature's committed expiration ledger is preserved via resolveSignatureExpirationLedger()
     * and a conflicting value throws InvalidArgumentException. On the client domain callback path
     * (unless a shared server entry already carries a valid server signature), a non-null
     * $signatureExpirationLedger is written directly to the entry before the callback is invoked
     * without conflict checking, and the callback may still set its own expiration ledger on the
     * entry it returns.
     *
     * Client domain signing rules:
     * - When $clientDomainKeyPair includes a private key, it signs the client domain entry locally
     *   and $clientDomainSigningCallback is not invoked, even when both are passed.
     * - When $clientDomainKeyPair contains only a public key and $clientDomainSigningCallback is
     *   provided, the key pair supplies the Client Domain Account ID and the callback signs the
     *   client domain entry with the answer binding below.
     * - When $clientDomainKeyPair contains only a public key and no callback is provided,
     *   InvalidArgumentException is thrown only if a client domain entry requires signing (an
     *   entry for that account exists and is not already a server entry carrying a valid server
     *   signature).
     *
     * The client domain callback receives an independent copy after any supplied expiration ledger
     * is set. Its answer must preserve the credentials arm, address, nonce, root invocation and complete
     * delegate tree. Only the top-level address signature and signature expiration ledger may change.
     * The answer is encoded once and independent decoded copies are used for comparison and the returned
     * entry, which preserves the callback's signature and expiration ledger. Malformed answers and
     * structural mismatches throw InvalidArgumentException. The callback's signature is not verified
     * and its expiration ledger is not checked for freshness. Any entry whose address is also the
     * server signing key and that already carries a valid signature from the server signing key is
     * preserved unchanged without duplicating the signature or invoking the callback.
     *
     * The returned array is a 0-indexed list in the same order as $authEntries, regardless of
     * whether $authEntries uses sparse or associative keys.
     *
     * @param array<SorobanAuthorizationEntry> $authEntries Entries to sign
     * @param string $clientAccountId Client account to sign for
     * @param array<KeyPair> $signers Keypairs with private keys to sign the client authorization entry. For contracts
     *                                that implement __check_auth with signature verification, provide
     *                                the keypairs with sufficient weight to meet the contract's
     *                                authentication requirements. Can be empty for contracts whose
     *                                __check_auth implementation does not require signatures (per SEP-45).
     * @param int|null $signatureExpirationLedger Optional expiration ledger. When null, each entry keeps its
     *                                            existing expiration ledger. When non-null, applied to the
     *                                            client entry (including when $signers is empty) and to the
     *                                            locally signed client domain entry, preserving any existing
     *                                            signature's committed expiration ledger and throwing
     *                                            InvalidArgumentException on conflict. On the client domain
     *                                            callback path (unless a shared server entry already carries
     *                                            a valid server signature), written directly to the entry
     *                                            before the callback runs without conflict checking; the
     *                                            callback may still set its own expiration ledger on the
     *                                            entry it returns.
     * @param KeyPair|null $clientDomainKeyPair Optional client domain keypair. When it includes a private
     *                                          key, it signs locally and takes precedence over
     *                                          $clientDomainSigningCallback. When public-only, it supplies
     *                                          the Client Domain Account ID for $clientDomainSigningCallback
     *                                          and throws InvalidArgumentException if no callback is provided
     *                                          and a client domain entry requires signing.
     * @param callable|null $clientDomainSigningCallback Optional callback for remote signing, used when
     *                                                   $clientDomainKeyPair is null or public-only. The
     *                                                   callback receives a single SorobanAuthorizationEntry
     *                                                   (the client domain entry) and must return a signed
     *                                                   SorobanAuthorizationEntry.
     * @param string|null $clientDomainAccountId Client domain account ID (used when $clientDomainKeyPair
     *                                           is null and $clientDomainSigningCallback is provided)
     * @return list<SorobanAuthorizationEntry> Signed entries in input order
     * @throws RuntimeException if no address credentials are found in entry
     * @throws InvalidArgumentException if any element in $signers is not a KeyPair with a private key,
     * $clientDomainKeyPair has no private key and no callback is provided when a client domain entry
     * requires signing,
     * a non-null expiration ledger conflicts with a committed signature on the client entry or locally
     * signed client domain entry, the callback returns another type, a malformed entry, or an entry with
     * changes beyond its top-level address signature and signature expiration ledger, or the client
     * domain entry is not found; also if an entry being signed locally or passed to the client domain
     * callback has a muxed account (M...) or muxed contract (W...) credential address
     * @throws Exception if credentials address could not be converted to StrKey representation
     */
    public function signAuthorizationEntries(
        array $authEntries,
        string $clientAccountId,
        array $signers,
        ?int $signatureExpirationLedger,
        ?KeyPair $clientDomainKeyPair = null,
        ?callable $clientDomainSigningCallback = null,
        ?string $clientDomainAccountId = null
    ): array {
        $this->validateSigners($signers);

        // Client domain signing policy (spec 110-112): a key pair with a private key signs locally
        // without calling the callback; a public-only key pair supplies the Client Domain Account
        // ID for the callback. When a public-only key pair is passed without a callback, reject
        // only when an entry for that account actually requires signing (skipping entries for the
        // server signing key that already carry a valid server signature, or challenges with no
        // entry for that account), and check before mutating any entry.
        $localClientDomainKeyPair = null;
        if ($clientDomainKeyPair !== null) {
            if ($clientDomainKeyPair->getPrivateKey() !== null) {
                $localClientDomainKeyPair = $clientDomainKeyPair;
            } elseif ($clientDomainSigningCallback === null) {
                $publicOnlyAccountId = $clientDomainKeyPair->getAccountId();
                foreach ($authEntries as $entry) {
                    $innerCreds = $entry->credentials->getAddressCredentials();
                    if ($innerCreds === null) {
                        continue;
                    }
                    $credentialsAddressStr = $innerCreds->address->toStrKey();
                    if ($credentialsAddressStr !== $publicOnlyAccountId) {
                        continue;
                    }
                    if (
                        $credentialsAddressStr === $this->serverSigningKey
                        && $this->verifyServerSignature($entry, $this->network)
                    ) {
                        continue;
                    }
                    throw new InvalidArgumentException(
                        "Client domain key pair for account $publicOnlyAccountId"
                            . " must include a private key when no signing callback is provided"
                    );
                }
            }
        }
        $resolvedClientDomainAccountId = $clientDomainKeyPair !== null
            ? $clientDomainKeyPair->getAccountId()
            : $clientDomainAccountId;

        $signedEntries = [];
        /** @var list<int> $clientDomainEntryIndices */
        $clientDomainEntryIndices = [];

        foreach ($authEntries as $entry) {
            // Record the 0-based position in $signedEntries rather than the input key so sparse
            // or associative $authEntries arrays still map the callback to the exact matching slot.
            $outputIndex = count($signedEntries);
            $credentials = $entry->credentials;
            // Resolve the inner address credentials for all three address arms.
            // Source-account entries have no address and are passed through unsigned.
            $innerCreds = $credentials->getAddressCredentials();
            if ($innerCreds !== null) {
                $credentialsAddressStr = $innerCreds->address->toStrKey();

                // Sign client entry, passing expiration to sign() so it is applied before hashing.
                // When no client signers are provided, still stamp the expiration ledger because
                // the Soroban host enforces expiration on unsigned address credentials too, using
                // resolveSignatureExpirationLedger() so any pre-existing signature is not invalidated.
                if ($credentialsAddressStr === $clientAccountId) {
                    $signedByClient = false;
                    foreach ($signers as $signer) {
                        $entry->sign($signer, $this->network, $signatureExpirationLedger);
                        $signedByClient = true;
                    }
                    if (!$signedByClient && $signatureExpirationLedger !== null) {
                        $innerCreds->signatureExpirationLedger = $entry->resolveSignatureExpirationLedger($signatureExpirationLedger);
                        $entry->credentials->writeBackAddressCredentials($innerCreds);
                    }
                }

                // Sign client domain entry with local keypair unless the entry serves the
                // server address and already carries a valid server signature.
                if (
                    $localClientDomainKeyPair !== null
                    && $credentialsAddressStr === $localClientDomainKeyPair->getAccountId()
                    && !(
                        $credentialsAddressStr === $this->serverSigningKey
                        && $this->verifyServerSignature($entry, $this->network)
                    )
                ) {
                    $entry->sign($localClientDomainKeyPair, $this->network, $signatureExpirationLedger);
                }

                // Track matching client domain entry indices in $signedEntries for the callback path.
                if ($resolvedClientDomainAccountId !== null && $credentialsAddressStr === $resolvedClientDomainAccountId) {
                    $clientDomainEntryIndices[] = $outputIndex;
                }
            }

            $signedEntries[] = $entry;
        }

        // Invoke client domain signing callback when no local private key pair was provided
        if ($localClientDomainKeyPair === null && $clientDomainSigningCallback !== null) {
            if ($clientDomainEntryIndices === []) {
                throw new InvalidArgumentException(
                    "Client domain entry not found for account: $resolvedClientDomainAccountId"
                );
            }

            foreach ($clientDomainEntryIndices as $entryIndex) {
                $clientDomainEntry = $signedEntries[$entryIndex];
                // Preserve any entry for the server address that already carries a valid server
                // signature (covering both one shared entry and two separate server-signed entries).
                $sharedServerEntryAlreadySigned = $resolvedClientDomainAccountId === $this->serverSigningKey
                    && $this->verifyServerSignature($clientDomainEntry, $this->network);

                if (!$sharedServerEntryAlreadySigned) {
                    // Send only the client domain entry to the callback. Validate the credential
                    // address before stamping expiration or calling out because the Soroban host
                    // rejects muxed (M.../W...) auth credential addresses (CAP-0067, CAP-0084).
                    // Stamp the expiration first, as the client and client-domain-keypair
                    // branches do through sign(), so the remote signer signs over the
                    // intended expiration ledger rather than the challenge default.
                    $cdCreds = $clientDomainEntry->credentials->getAddressCredentials();
                    if ($cdCreds !== null) {
                        SorobanAuthAddressGuard::requireCredentialAddress($cdCreds->address);
                        if ($signatureExpirationLedger !== null) {
                            $cdCreds->signatureExpirationLedger = $signatureExpirationLedger;
                            $clientDomainEntry->credentials->writeBackAddressCredentials($cdCreds);
                        }
                    }
                    // Keep an immutable baseline and give the callback its own object graph, including
                    // nested invocations and delegates. A callback may also replace its argument by reference.
                    $baselineXdr = $clientDomainEntry->toBase64Xdr();
                    $callbackArgument = SorobanAuthorizationEntry::fromBase64Xdr($baselineXdr);
                    $signedEntry = $clientDomainSigningCallback($callbackArgument);

                    // Validate callback return value
                    if (!($signedEntry instanceof SorobanAuthorizationEntry)) {
                        throw new InvalidArgumentException(
                            "Client domain signing callback must return a SorobanAuthorizationEntry object"
                        );
                    }

                    $baseline = SorobanAuthorizationEntry::fromBase64Xdr($baselineXdr);
                    // Encode the callback answer once and decode two independent copies so
                    // neutralizing the comparison copy does not mutate the returned entry and a
                    // custom object cannot serialize differently between validation and submission.
                    try {
                        $answerXdr = $signedEntry->toBase64Xdr();
                        $comparisonEntry = SorobanAuthorizationEntry::fromBase64Xdr($answerXdr);
                        $returnedEntry = SorobanAuthorizationEntry::fromBase64Xdr($answerXdr);
                    } catch (\Throwable $e) {
                        throw new InvalidArgumentException(
                            'Client domain signing callback returned an invalid authorization entry', 0, $e
                        );
                    }
                    // Neutralize the top-level signature and expiration ledger in both independent copies.
                    // The complete encoding still compares the credentials arm, address, nonce, root invocation
                    // and every delegate field and order.
                    foreach ([$baseline, $comparisonEntry] as $comparison) {
                        $credentials = $comparison->credentials->getAddressCredentials();
                        if ($credentials !== null) {
                            $credentials->signature = XdrSCVal::forVoid();
                            $credentials->signatureExpirationLedger = 0;
                            $comparison->credentials->writeBackAddressCredentials($credentials);
                        }
                    }
                    if ($comparisonEntry->toBase64Xdr() !== $baseline->toBase64Xdr()) {
                        throw new InvalidArgumentException('Client domain signing callback returned a different authorization entry');
                    }

                    // Return an independent copy of the exact answer used for comparison.
                    $signedEntries[$entryIndex] = $returnedEntry;
                }
            }
        }

        return $signedEntries;
    }

    /**
     * Simulates the signed authorization entries via Soroban RPC and verifies the read-write footprint.
     *
     * Builds a local transaction using a neutral source account with sequence number zero and a single
     * InvokeHostFunctionOperation calling web_auth_verify on the web auth contract with the challenge
     * arguments and the signed entries attached unchanged, explicitly setting authMode to "enforce"
     * so Soroban RPC validates the signed authorization entries as the network would.
     *
     * Accepts only read_write footprint entries that are contract_data ledger_key_nonce entries for the
     * client account, the server signing account, and (when $clientDomainAccountId is provided and an
     * entry for it is present in $signedEntries) the client domain account, or entries marked for
     * restoration. A persistent contract_data entry with any non-nonce key (including the instance key)
     * for the client contract (when $clientAccountId is a valid C... contract strkey), a persistent
     * ledger_key_contract_instance entry for WEB_AUTH_CONTRACT_ID, or a CONTRACT_CODE entry is accepted
     * in read_write only when its zero-based read_write index is marked for restoration in
     * transactionData.ext.resourceExt.archivedSorobanEntries with ext.discriminant === 1, and any
     * restoration index that is out of range, out of strictly increasing order, or points to a nonce or
     * non-persistent entry is rejected. Any subset of this allowlist (including an empty read_write set)
     * is accepted; read_only entries are not restricted.
     *
     * This method verifies the permitted read_write footprint. Listing an entry in
     * archivedSorobanEntries marks it for restoration (CAP-66); it proves neither that the entry was
     * archived nor that the invocation leaves it unchanged. Allowing restored persistent contract_data entries for
     * the client contract is an SDK policy beyond the footprint list of SEP-45 lines 113-127,
     * because the entry belongs to the caller's own contract, only that contract can write it, and
     * an archived client contract could not authenticate otherwise.
     *
     * When Soroban RPC returns a JSON-RPC error or simulation execution error, the exception keeps its
     * static message and exposes the untrusted RPC error or simulation error text through
     * ContractChallengeValidationErrorSimulationFailed::getRpcError() and getSimulationError().
     *
     * @param array<SorobanAuthorizationEntry> $signedEntries Signed authorization entries
     * @param string $clientAccountId Client contract account strkey (C...)
     * @param string|null $clientDomainAccountId Optional client domain account strkey
     * @return void
     * @throws ContractChallengeValidationErrorSimulationFailed if simulation fails, transaction data is missing,
     *                                                          its restoration extension is malformed or unsupported,
     *                                                          or read_write contains a disallowed ledger key
     * @throws GuzzleException if the Soroban RPC HTTP transport fails
     * @throws RuntimeException if Soroban RPC fails at the transport layer
     * @throws InvalidArgumentException if the Soroban RPC response is not valid JSON or holds invalid XDR
     * @throws \TypeError if the Soroban RPC response holds a JSON value of an unexpected type
     */
    public function verifySimulation(
        array $signedEntries,
        string $clientAccountId,
        ?string $clientDomainAccountId = null
    ): void {
        // Normalize sparse or associative caller arrays to a 0-indexed list so $entries[0] exists
        // and the operation's authorization list is sequentially indexed.
        $entries = array_values($signedEntries);
        if ($entries === []) {
            throw new ContractChallengeValidationErrorSimulationFailed("No authorization entries to simulate");
        }

        $contractFn = $entries[0]->rootInvocation->function->contractFn;
        if ($contractFn === null) {
            throw new ContractChallengeValidationErrorSimulationFailed("Authorization entry is not a contract function");
        }

        // Build a local transaction with a neutral unfunded G... source account at sequence 0
        // (without incrementing sequence or fetching an account on-chain) and attach the signed
        // entries unchanged as address credentials (spec 113-115).
        $hostFunction = new InvokeContractHostFunction(
            $this->webAuthContractId,
            "web_auth_verify",
            $contractFn->args
        );
        $operation = (new InvokeHostFunctionOperationBuilder($hostFunction, $entries))->build();
        $sourceAccount = new Account(self::SIMULATION_SOURCE_ACCOUNT, new BigInteger(0));
        $transaction = (new TransactionBuilder($sourceAccount))
            ->setIncrementSequenceNumber(false)
            ->addOperation($operation)
            ->build();

        // Pass authMode "enforce" explicitly: the footprint check is only meaningful when the RPC
        // server validates the signed authorization entries as the network would; the server's
        // default for a transaction with auth entries is the same mode, and the explicit value
        // keeps the check independent of that default.
        $response = $this->getSorobanServer()->simulateTransaction(
            new SimulateTransactionRequest($transaction, null, 'enforce')
        );

        if ($response->error !== null) {
            $code = $response->error->code;
            // Keep untrusted RPC error text out of the exception message; callers inspect
            // getRpcError() when they need the JSON-RPC error code and message.
            throw new ContractChallengeValidationErrorSimulationFailed(
                $code !== null
                    ? "Transaction simulation RPC error (code $code)"
                    : "Transaction simulation RPC error",
                rpcError: $response->error
            );
        }

        if ($response->resultError !== null) {
            // Keep untrusted simulation error text out of the exception message; callers inspect
            // getSimulationError() when they need the server's resultError text.
            throw new ContractChallengeValidationErrorSimulationFailed(
                "Transaction simulation failed",
                simulationError: $response->resultError
            );
        }

        // Reject missing transactionData rather than treating it as an empty footprint, because
        // spec 113-116 requires verifying the transaction's simulated read_write footprint.
        $transactionData = $response->transactionData;
        if ($transactionData === null) {
            throw new ContractChallengeValidationErrorSimulationFailed(
                "Transaction simulation response did not include transaction data"
            );
        }

        // Spec 116-127 restricts only read_write as an allowlist (any subset, including empty,
        // passes); read_only is intentionally unrestricted.
        $readWrite = array_values($transactionData->resources->footprint->readWrite);
        $restoredIndices = $this->extractRestoredReadWriteIndices($transactionData->ext, count($readWrite));

        // Spec 122-124 allows the Client Domain Account nonce only when an authorization entry for
        // that account was actually present in the challenge.
        $allowClientDomainNonce = $clientDomainAccountId !== null
            && $this->hasEntryForAddress($entries, $clientDomainAccountId);

        foreach ($readWrite as $index => $ledgerKey) {
            if (!($ledgerKey instanceof XdrLedgerKey)) {
                throw new ContractChallengeValidationErrorSimulationFailed(
                    "Transaction simulation read_write footprint contains a malformed ledger key"
                );
            }

            $isMarkedForRestoration = isset($restoredIndices[$index]);
            $entryType = $ledgerKey->type->getValue();

            // Allowance 1 (CAP-66): CONTRACT_CODE is permitted in read_write only when listed in
            // archivedSorobanEntries, because executing web_auth_verify or the client contract's
            // __check_auth may need to restore archived Wasm bytecode. The listing marks the entry
            // for restoration (CAP-66); it proves neither that the entry was archived nor that the
            // invocation leaves it unchanged. Unlisted CONTRACT_CODE entries are rejected (spec 113-127).
            if ($entryType === XdrLedgerEntryType::CONTRACT_CODE) {
                if ($ledgerKey->contractCode === null) {
                    throw new ContractChallengeValidationErrorSimulationFailed(
                        "Transaction simulation read_write footprint contains disallowed ledger entry type: "
                        . $ledgerKey->type->enumName()
                    );
                }
                if (!$isMarkedForRestoration) {
                    throw new ContractChallengeValidationErrorSimulationFailed(
                        "Transaction simulation read_write footprint contains disallowed ledger entry type: "
                        . $ledgerKey->type->enumName()
                        . " (not marked for restoration)"
                    );
                }
                continue;
            }

            // Spec 116-117 restricts all remaining read_write entries to CONTRACT_DATA (rejecting
            // ACCOUNT, TRUSTLINE, TTL, and other ledger entry types whether listed or not).
            if ($entryType !== XdrLedgerEntryType::CONTRACT_DATA || $ledgerKey->contractData === null) {
                throw new ContractChallengeValidationErrorSimulationFailed(
                    "Transaction simulation read_write footprint contains disallowed ledger entry type: "
                    . $ledgerKey->type->enumName()
                );
            }

            $contractData = $ledgerKey->contractData;
            try {
                $contractAddress = $contractData->contract->toStrKey();
            } catch (\Throwable $e) {
                throw new ContractChallengeValidationErrorSimulationFailed(
                    "Transaction simulation read_write footprint contains an invalid contract data address",
                    previous: $e
                );
            }

            // Allowance 2 (spec 118-124): run nonce validation before any restoration allowance.
            // Only ledger_key_nonce entries for the Client Account, Server Account, and (when
            // present in the challenge) Client Domain Account may be written; nonce values are not
            // compared. A nonce entry cannot be marked for CAP-66 restoration because auth nonces
            // are temporary entries created for replay tracking, not archived persistent state.
            $keyType = $contractData->key->type->getValue();
            if ($keyType === XdrSCValType::SCV_LEDGER_KEY_NONCE) {
                $allowedNonceAddress = $contractAddress === $clientAccountId
                    || $contractAddress === $this->serverSigningKey
                    || ($allowClientDomainNonce && $contractAddress === $clientDomainAccountId);
                if (!$allowedNonceAddress) {
                    throw new ContractChallengeValidationErrorSimulationFailed(
                        "Transaction simulation read_write footprint contains disallowed contract data entry for "
                        . "$contractAddress with key type " . $contractData->key->type->enumName()
                    );
                }
                if ($isMarkedForRestoration) {
                    throw new ContractChallengeValidationErrorSimulationFailed(
                        "Transaction simulation read_write footprint contains malformed restoration entry: "
                        . $ledgerKey->type->enumName()
                        . " with key type " . $contractData->key->type->enumName()
                        . " cannot be marked for restoration"
                    );
                }
                continue;
            }

            // Allowance 3 (CAP-66 + spec 125-127): restored persistent CONTRACT_DATA entries.
            // This check verifies the permitted read_write footprint; listing an entry in
            // archivedSorobanEntries marks it for restoration (CAP-66) and proves neither that it
            // was archived nor that the invocation leaves it unchanged.
            // - WEB_AUTH_CONTRACT_ID (instance key only): spec 125-127 permits an archived web
            //   auth contract instance in read_write; non-instance keys of the web auth contract
            //   remain outside the allowlist.
            // - Client contract (instance key here, and any other non-nonce key below): SDK policy
            //   beyond the footprint list of SEP-45 lines 113-127, because the entry belongs to
            //   the caller's own contract, only that contract can write it, and an archived client
            //   contract could not authenticate otherwise. Requiring a valid C... contract strkey
            //   prevents non-contract direct-call addresses from matching.
            $isRestorableInstance = $keyType === XdrSCValType::SCV_LEDGER_KEY_CONTRACT_INSTANCE
                && (
                    $contractAddress === $this->webAuthContractId
                    || ($contractAddress === $clientAccountId && StrKey::isValidContractId($clientAccountId))
                );
            if ($isRestorableInstance) {
                if (!$isMarkedForRestoration) {
                    throw new ContractChallengeValidationErrorSimulationFailed(
                        "Transaction simulation read_write footprint contains disallowed "
                        . $ledgerKey->type->enumName()
                        . " entry for $contractAddress with key type "
                        . $contractData->key->type->enumName()
                        . " (not marked for restoration)"
                    );
                }
                if ($contractData->durability->getValue() !== XdrContractDataDurability::PERSISTENT) {
                    throw new ContractChallengeValidationErrorSimulationFailed(
                        "Transaction simulation read_write footprint contains malformed restoration entry: non-persistent "
                        . $ledgerKey->type->enumName()
                        . " with key type " . $contractData->key->type->enumName()
                        . " cannot be marked for restoration"
                    );
                }
                continue;
            }

            if (
                $contractAddress === $clientAccountId
                && StrKey::isValidContractId($clientAccountId)
                && $isMarkedForRestoration
                && $contractData->durability->getValue() === XdrContractDataDurability::PERSISTENT
            ) {
                continue;
            }

            // Reject any remaining CONTRACT_DATA entry (unlisted or non-persistent non-instance
            // client contract data, third contracts, non-instance web auth contract data, or
            // non-nonce server/client-domain data).
            throw new ContractChallengeValidationErrorSimulationFailed(
                "Transaction simulation read_write footprint contains disallowed contract data entry for "
                . "$contractAddress with key type " . $contractData->key->type->enumName()
            );
        }
    }

    /**
     * Extracts the zero-based read_write indices marked for restoration in transactionData.ext.
     *
     * @param XdrSorobanTransactionDataExt $ext Transaction data extension
     * @param int $readWriteCount Number of entries in the read_write footprint
     * @return array<int, true> Set of zero-based read_write indices marked for restoration
     * @throws ContractChallengeValidationErrorSimulationFailed if ext or archivedSorobanEntries is malformed
     */
    private function extractRestoredReadWriteIndices(
        XdrSorobanTransactionDataExt $ext,
        int $readWriteCount
    ): array {
        // Extension v0 carries no archivedSorobanEntries; ignore any in-memory resourceExt object.
        if ($ext->discriminant === 0) {
            return [];
        }
        // Only ext v0 and v1 (CAP-66 archivedSorobanEntries) are recognized; fail closed on
        // unknown discriminants or missing resourceExt rather than silently ignoring them.
        if ($ext->discriminant !== 1) {
            throw new ContractChallengeValidationErrorSimulationFailed(
                "Transaction simulation transaction data has unsupported ext discriminant: " . $ext->discriminant
            );
        }
        if ($ext->resourceExt === null) {
            throw new ContractChallengeValidationErrorSimulationFailed(
                "Transaction simulation transaction data has ext discriminant 1 without resourceExt"
            );
        }

        // CAP-66 defines archivedSorobanEntries as strictly increasing zero-based indices into
        // read_write; reject out-of-range, duplicate, or unsorted indices as malformed metadata.
        /** @var array<int, true> $restoredIndices */
        $restoredIndices = [];
        $previousIndex = null;
        foreach ($ext->resourceExt->archivedSorobanEntries as $rawIndex) {
            if (!is_int($rawIndex) || $rawIndex < 0 || $rawIndex >= $readWriteCount) {
                throw new ContractChallengeValidationErrorSimulationFailed(
                    "Transaction simulation transaction data contains an invalid archivedSorobanEntries index"
                );
            }
            if ($previousIndex !== null && $rawIndex <= $previousIndex) {
                throw new ContractChallengeValidationErrorSimulationFailed(
                    "Transaction simulation transaction data archivedSorobanEntries indices must be strictly increasing"
                );
            }
            $previousIndex = $rawIndex;
            $restoredIndices[$rawIndex] = true;
        }

        return $restoredIndices;
    }

    /**
     * Submits signed authorization entries to obtain a JWT token.
     *
     * In a manual flow, callers must call validateChallenge(), signAuthorizationEntries(), and
     * verifySimulation() before calling this method. This method submits the entries without validating them.
     *
     * Returns a token only on HTTP 200 when error is absent or empty and token is non-empty.
     * HTTP 200 with a non-empty error (including "0") throws SubmitContractChallengeErrorResponseException
     * with that error message. HTTP 400 always throws SubmitContractChallengeErrorResponseException
     * (using the server's error text when present) and never returns a token. HTTP 504 throws
     * SubmitContractChallengeTimeoutResponseException, and every other HTTP status code throws
     * SubmitContractChallengeUnknownResponseException. When the effective endpoint URI (resolved
     * against the client's base_uri) uses HTTPS, any enabled Guzzle allow_redirects policy on the
     * HTTP client is narrowed to the https protocol without enabling disabled redirects or widening
     * other redirect settings.
     *
     * @param array<SorobanAuthorizationEntry> $signedEntries Signed entries
     * @return string JWT token
     * @throws SubmitContractChallengeErrorResponseException on HTTP 400, an error payload, malformed JSON, or missing token
     * @throws SubmitContractChallengeTimeoutResponseException on HTTP 504 timeout
     * @throws SubmitContractChallengeUnknownResponseException on any other HTTP status code
     * @throws GuzzleException
     */
    public function sendSignedChallenge(array $signedEntries): string
    {
        $base64Xdr = $this->encodeAuthorizationEntries($signedEntries);

        // Disable Guzzle HTTP error exceptions so 400, 504, and other non-200 statuses map to the
        // dedicated SEP-45 exceptions below instead of generic Guzzle exceptions.
        $requestOptions = ['http_errors' => false];
        $redirectPolicy = $this->buildHttpsRedirectPolicy(Psr7Utils::uriFor($this->authEndpoint));
        if ($redirectPolicy !== null) {
            $requestOptions[RequestOptions::ALLOW_REDIRECTS] = $redirectPolicy;
        }
        if ($this->useFormUrlEncoded) {
            $requestOptions[RequestOptions::FORM_PARAMS] = ['authorization_entries' => $base64Xdr];
        } else {
            $requestOptions[RequestOptions::JSON] = ['authorization_entries' => $base64Xdr];
        }

        $response = $this->httpClient->post(
            $this->authEndpoint,
            $requestOptions
        );

        // SEP-45 (spec 400-406, 419) treats only HTTP 200 as success; every other status is an error.
        $statusCode = $response->getStatusCode();
        if (200 === $statusCode || 400 === $statusCode) {
            $content = $response->getBody()->__toString();
            $jsonData = @json_decode($content, true);
            if (null === $jsonData && json_last_error() != JSON_ERROR_NONE) {
                throw new SubmitContractChallengeErrorResponseException(
                    sprintf("Error in json_decode: %s", json_last_error_msg())
                );
            }
            if (!is_array($jsonData)) {
                throw new SubmitContractChallengeErrorResponseException(sprintf("Token response body must be a JSON object or array, got %s", get_debug_type($jsonData)));
            }
            $result = SubmitContractChallengeResponse::fromJson($jsonData);
            $error = $result->getError();
            // Reject HTTP 400 before checking token so an HTTP 400 body carrying a token never succeeds.
            if (400 === $statusCode) {
                throw new SubmitContractChallengeErrorResponseException(
                    $error !== null && $error !== '' ? $error : "An unknown error occurred"
                );
            }
            // Compare explicitly against null and '' instead of PHP truthiness so "0" is not dropped;
            // on HTTP 200 a non-empty error takes precedence over token.
            if ($error !== null && $error !== '') {
                throw new SubmitContractChallengeErrorResponseException($error);
            }
            $jwtToken = $result->getJwtToken();
            if ($jwtToken !== null && $jwtToken !== '') {
                return $jwtToken;
            }
            throw new SubmitContractChallengeErrorResponseException("An unknown error occurred");
        } else if (504 === $statusCode) {
            throw new SubmitContractChallengeTimeoutResponseException();
        } else {
            throw new SubmitContractChallengeUnknownResponseException(
                $response->getBody()->__toString(),
                $statusCode
            );
        }
    }

    /**
     * Resolves the effective request URI against the client's base_uri and narrows any
     * enabled redirect policy to HTTPS when the effective scheme is https.
     *
     * Returns null when the effective scheme is not https or when redirects are disabled
     * on the HTTP client, leaving the client's configured policy untouched. Returns false
     * when the caller's protocols allowlist excludes https, disabling redirects instead
     * of passing an empty protocols list to Guzzle.
     *
     * @param UriInterface $uri Request URI before base_uri resolution
     * @return array<string, mixed>|false|null Narrowed allow_redirects settings, false to disable redirects, or null to keep client defaults
     */
    private function buildHttpsRedirectPolicy(UriInterface $uri): array|false|null
    {
        // Resolve against the client's base_uri first so relative URLs inherit their effective
        // scheme before checking whether HTTPS downgrade protection applies.
        $baseUri = $this->httpClient->getConfig('base_uri');
        if ($baseUri instanceof UriInterface || is_string($baseUri)) {
            $uri = UriResolver::resolve(Psr7Utils::uriFor($baseUri), $uri);
        }
        // Match Guzzle's schemeless authority handling: an authority URI without a scheme is HTTP.
        if ($uri->getScheme() === '' && $uri->getHost() !== '') {
            $uri = $uri->withScheme('http');
        }
        // Leave non-HTTPS endpoints (such as localhost HTTP) on the caller's redirect policy.
        if (strtolower($uri->getScheme()) !== 'https') {
            return null;
        }

        // Prevent HTTPS-to-HTTP downgrade redirects when posting signed authorization entries
        // (Guzzle defaults allow both http and https). Keep redirects disabled when disabled by caller.
        $configured = $this->httpClient->getConfig(RequestOptions::ALLOW_REDIRECTS);
        if ($configured === true) {
            $policy = RedirectMiddleware::$defaultSettings;
            $policy['protocols'] = ['https'];
            return $policy;
        }
        if (!is_array($configured) || $configured === []) {
            return null;
        }

        // Narrow a custom protocol allowlist to https only when https is already permitted; never
        // widen a caller's protocol restriction by adding https when it was excluded. Disable
        // redirects instead of passing an empty protocols list, which Guzzle deprecates.
        $policy = $configured;
        if (!array_key_exists('protocols', $policy)) {
            $policy['protocols'] = ['https'];
        } elseif (is_array($policy['protocols'])) {
            if (!in_array('https', $policy['protocols'], true)) {
                return false;
            }
            $policy['protocols'] = ['https'];
        }

        return $policy;
    }

    /**
     * Decodes authorization entries from base64 XDR.
     *
     * @param string $base64Xdr Base64-encoded XDR array of SorobanAuthorizationEntry
     * @return array<SorobanAuthorizationEntry> Decoded authorization entries
     * @throws ContractChallengeValidationError if decoding fails
     */
    public function decodeAuthorizationEntries(string $base64Xdr): array
    {
        try {
            $entries = array_map(SorobanAuthorizationEntry::fromXdr(...),
                XdrSorobanAuthorizationEntries::fromBase64Xdr($base64Xdr)->sorobanAuthorizationEntries);

            return $entries;
        } catch (Exception $e) {
            throw new ContractChallengeValidationError("Failed to decode authorization entries: " . $e->getMessage());
        }
    }

    /**
     * Validates that every element in $signers is a KeyPair containing a private key.
     *
     * @param array<mixed> $signers Signers list to validate
     * @throws InvalidArgumentException if any element is not a KeyPair or has no private key
     */
    private function validateSigners(array $signers): void
    {
        foreach ($signers as $signer) {
            if (!($signer instanceof KeyPair)) {
                throw new InvalidArgumentException(
                    sprintf("All signers must be KeyPair instances, got %s", get_debug_type($signer))
                );
            }
            if ($signer->getPrivateKey() === null) {
                throw new InvalidArgumentException(
                    "Signer KeyPair for account " . $signer->getAccountId() . " must include a private key"
                );
            }
        }
    }

    /**
     * Encodes authorization entries to base64 XDR.
     *
     * @param array<SorobanAuthorizationEntry> $entries Entries to encode
     * @return string Base64-encoded XDR
     */
    private function encodeAuthorizationEntries(array $entries): string
    {
        $bytes = '';

        // Write array length
        $bytes .= XdrEncoder::unsignedInteger32(count($entries));

        // Write each entry
        foreach ($entries as $entry) {
            $bytes .= $entry->toXdr()->encode();
        }

        return base64_encode($bytes);
    }

    /**
     * Reports whether the decoded challenge entries carry client domain arguments.
     *
     * Decided from the argument pair (client_domain / client_domain_account) on the first entry.
     * Returns false for an empty entry list or unparseable arguments so validateChallenge()
     * surfaces the structural validation error without fetching stellar.toml.
     *
     * @param array<SorobanAuthorizationEntry> $authEntries Decoded challenge entries
     * @return bool True if client_domain or client_domain_account is present in the first entry's args map
     */
    private function challengeIncludesClientDomain(array $authEntries): bool
    {
        if ($authEntries === []) {
            return false;
        }
        $firstEntry = array_values($authEntries)[0];
        try {
            $args = $this->extractArgsFromEntry($firstEntry);
        } catch (ContractChallengeValidationErrorInvalidArgs) {
            // Defer structural error reporting to validateChallenge() without fetching stellar.toml.
            return false;
        }
        return array_key_exists('client_domain', $args)
            || array_key_exists('client_domain_account', $args);
    }

    /**
     * Compares two extracted args maps for equal keys and values, including unknown keys.
     *
     * @param array<string, string> $first First entry's args map
     * @param array<string, string> $other Another entry's args map
     * @return bool True when both maps have the exact same keys and identical string values
     */
    private function areArgsMapsEqual(array $first, array $other): bool
    {
        if (count($first) !== count($other)) {
            return false;
        }
        foreach ($first as $key => $value) {
            if (!array_key_exists($key, $other) || $other[$key] !== $value) {
                return false;
            }
        }
        return true;
    }

    /**
     * Extracts args map from an authorization entry.
     *
     * Requires exactly one argument which is a Map<Symbol, String> with unique keys.
     * Malformed members, duplicate keys, and extra positional arguments are rejected.
     *
     * @param SorobanAuthorizationEntry $entry Entry to extract args from
     * @return array<string, string> Args map
     * @throws ContractChallengeValidationErrorInvalidArgs if args are missing, malformed, or contain duplicate keys
     */
    private function extractArgsFromEntry(SorobanAuthorizationEntry $entry): array
    {
        try {
            $function = $entry->rootInvocation->function;
            if ($function->contractFn === null) {
                throw new ContractChallengeValidationErrorInvalidArgs("Not a contract function");
            }

            // SEP-45 (spec 252, 474) defines web_auth_verify(env, args: Map<Symbol, String>) with
            // exactly one argument; reject extra positional arguments, malformed map members, and
            // duplicate keys rather than skipping or collapsing them.
            $argsArray = $function->contractFn->args;
            if (count($argsArray) !== 1) {
                throw new ContractChallengeValidationErrorInvalidArgs(
                    "Expected exactly one argument, got " . count($argsArray)
                );
            }

            // Single arg must be a map
            $argsVal = array_values($argsArray)[0];
            if (!($argsVal instanceof XdrSCVal) || $argsVal->type->value !== XdrSCValType::SCV_MAP || $argsVal->map === null) {
                throw new ContractChallengeValidationErrorInvalidArgs("Arguments are not in map format");
            }

            $result = [];
            foreach ($argsVal->map as $mapEntry) {
                if (!($mapEntry instanceof XdrSCMapEntry)) {
                    throw new ContractChallengeValidationErrorInvalidArgs("Argument map entry is malformed");
                }

                // Key must be a symbol
                if ($mapEntry->key->type->value !== XdrSCValType::SCV_SYMBOL || $mapEntry->key->sym === null) {
                    throw new ContractChallengeValidationErrorInvalidArgs("Argument map key must be a symbol");
                }
                $key = $mapEntry->key->sym;

                // Value must be a string
                if ($mapEntry->val->type->value !== XdrSCValType::SCV_STRING || $mapEntry->val->str === null) {
                    throw new ContractChallengeValidationErrorInvalidArgs("Argument map value for '$key' must be a string");
                }
                $value = $mapEntry->val->str;

                if (array_key_exists($key, $result)) {
                    throw new ContractChallengeValidationErrorInvalidArgs("Duplicate argument key: '$key'");
                }

                $result[$key] = $value;
            }

            return $result;
        } catch (ContractChallengeValidationErrorInvalidArgs $e) {
            throw $e;
        } catch (Exception $e) {
            throw new ContractChallengeValidationErrorInvalidArgs("Failed to extract args: " . $e->getMessage());
        }
    }

    /**
     * Verifies server signature on an authorization entry.
     *
     * Scans the signature vector for the first element whose public_key matches the server signing
     * key from stellar.toml and verifies its Ed25519 signature over the arm-appropriate preimage hash.
     *
     * @param SorobanAuthorizationEntry $entry Entry to verify
     * @param Network $network Network for signature verification
     * @return bool True if the first element with the server signing key's public_key carries a valid signature
     */
    private function verifyServerSignature(SorobanAuthorizationEntry $entry, Network $network): bool
    {
        try {
            // All three address arms are accepted; source-account credentials cannot carry a signature.
            $innerCreds = $entry->credentials->getAddressCredentials();
            if ($innerCreds === null) {
                return false;
            }

            // Get signature vector from inner credentials (arm-agnostic accessor).
            $signatureVal = $innerCreds->signature;
            if ($signatureVal->type->value !== XdrSCValType::SCV_VEC || $signatureVal->vec === null || count($signatureVal->vec) === 0) {
                return false;
            }

            $serverKeyPair = KeyPair::fromAccountId($this->serverSigningKey);
            $expectedPublicKey = $serverKeyPair->getPublicKey();

            // Build the arm-correct preimage and hash it.
            // ADDRESS -> ENVELOPE_TYPE_SOROBAN_AUTHORIZATION
            // ADDRESS_V2 / ADDRESS_WITH_DELEGATES -> ENVELOPE_TYPE_SOROBAN_AUTHORIZATION_WITH_ADDRESS
            $preimage = $entry->buildPreimage($network);
            $payload = Hash::generate($preimage->encode());

            // Scan every element of the signature vector (spec 102-104: "contains a valid
            // signature from the Server Account") so multi-signer server accounts whose
            // SIGNING_KEY is not at index 0 verify. Return on the first matching public_key entry.
            foreach ($signatureVal->vec as $sigEntry) {
                if (!($sigEntry instanceof XdrSCVal) || $sigEntry->type->value !== XdrSCValType::SCV_MAP || $sigEntry->map === null) {
                    continue;
                }

                $publicKey = null;
                $signature = null;
                foreach ($sigEntry->map as $mapEntry) {
                    if ($mapEntry instanceof XdrSCMapEntry && $mapEntry->key->type->value === XdrSCValType::SCV_SYMBOL) {
                        if ($mapEntry->key->sym === "public_key" && $mapEntry->val->type->value === XdrSCValType::SCV_BYTES && $mapEntry->val->bytes !== null) {
                            $publicKey = $mapEntry->val->bytes->value;
                        } else if ($mapEntry->key->sym === "signature" && $mapEntry->val->type->value === XdrSCValType::SCV_BYTES && $mapEntry->val->bytes !== null) {
                            $signature = $mapEntry->val->bytes->value;
                        }
                    }
                }

                if ($publicKey === $expectedPublicKey) {
                    return $signature !== null && $serverKeyPair->verifySignature($signature, $payload);
                }
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Gets challenge response from the server.
     *
     * @param string $accountId Contract account to authenticate
     * @param string $homeDomain Home domain
     * @param string|null $clientDomain Optional client domain
     * @return ContractChallengeResponse Challenge response
     * @throws ContractChallengeRequestErrorResponse on request failure
     */
    private function getChallengeResponse(
        string $accountId,
        string $homeDomain,
        ?string $clientDomain = null
    ): ContractChallengeResponse {
        $requestBuilder = (new ContractChallengeRequestBuilder($this->authEndpoint, $this->httpClient))
            ->forAccountId($accountId)
            ->forHomeDomain($homeDomain);

        if ($clientDomain !== null) {
            $requestBuilder = $requestBuilder->forClientDomain($clientDomain);
        }

        try {
            return $requestBuilder->execute();
        } catch (HorizonRequestException $e) {
            throw new ContractChallengeRequestErrorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    /**
     * Sets a mock HTTP handler for testing purposes.
     *
     * Replaces the HTTP client with one using the provided mock handler. This allows tests
     * to simulate authentication server responses without making actual HTTP requests.
     *
     * @param MockHandler $handler Guzzle mock handler with predefined responses
     * @return void
     */
    public function setMockHandler(MockHandler $handler): void
    {
        $handlerStack = HandlerStack::create($handler);
        $this->httpClient = new Client(['handler' => $handlerStack]);
    }

    /**
     * Returns the configured Soroban RPC server, creating one lazily from $sorobanRpcUrl when unset.
     *
     * @return SorobanServer The Soroban RPC server instance
     */
    private function getSorobanServer(): SorobanServer
    {
        return $this->sorobanServer ??= new SorobanServer($this->sorobanRpcUrl);
    }

    /**
     * Reports whether any authorization entry carries address credentials for the given address.
     *
     * @param array<SorobanAuthorizationEntry> $entries Authorization entries to inspect
     * @param string $accountId Expected address strkey
     * @return bool True if an entry has address credentials matching $accountId
     */
    private function hasEntryForAddress(array $entries, string $accountId): bool
    {
        foreach ($entries as $entry) {
            $innerCreds = $entry->credentials->getAddressCredentials();
            if ($innerCreds !== null && $innerCreds->address->toStrKey() === $accountId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Sets the Soroban RPC server instance used for latest ledger lookups and transaction simulation.
     *
     * When not set, a server is created automatically from the Soroban RPC URL.
     * Provide a preconfigured instance to customize the underlying HTTP client.
     *
     * @param SorobanServer $sorobanServer The RPC server instance to use
     * @return void
     */
    public function setSorobanServer(SorobanServer $sorobanServer): void
    {
        $this->sorobanServer = $sorobanServer;
    }
}
