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
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use RuntimeException;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\Toml\StellarToml;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Util\Hash;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrEncoder;
use Soneso\StellarSDK\Xdr\XdrEnvelopeType;
use Soneso\StellarSDK\Xdr\XdrHashIDPreimage;
use Soneso\StellarSDK\Xdr\XdrHashIDPreimageSorobanAuthorization;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizationEntry;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType;

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
 * The authentication flow consists of three steps:
 * 1. Request authorization entries from the server (challenge)
 * 2. Validate and sign the entries with the client's private key(s)
 * 3. Submit the signed entries back to the server to receive a JWT token
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
 *    Verify the nonce is consistent across all authorization entries and unique. The nonce
 *    provides replay protection.
 *
 * 7. Signature Expiration Ledger (HIGH):
 *    Set appropriate signature expiration ledger when signing to limit the validity window.
 *    This provides time-based replay protection.
 *
 * 8. Network Passphrase Validation (HIGH):
 *    When the challenge response includes a network_passphrase field, validate it matches the
 *    expected network. This prevents cross-network authentication attacks where signatures from
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
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Specification
 * @see StellarToml For discovering the auth endpoint
 */
class WebAuthForContracts
{
    private string $authEndpoint;
    private string $webAuthContractId;
    private string $serverSigningKey;
    private string $serverHomeDomain;
    private Network $network;
    private Client $httpClient;
    private bool $useFormUrlEncoded = false;
    private string $sorobanRpcUrl;

    /**
     * Constructor.
     *
     * @param string $authEndpoint WEB_AUTH_FOR_CONTRACTS_ENDPOINT from stellar.toml
     * @param string $webAuthContractId WEB_AUTH_CONTRACT_ID from stellar.toml (C... address)
     * @param string $serverSigningKey SIGNING_KEY from stellar.toml (G... address)
     * @param string $serverHomeDomain The server home domain where the stellar.toml was loaded from
     * @param Network $network The network used (testnet or pubnet)
     * @param Client|null $httpClient Optional HTTP client to be used for requests
     * @param string|null $sorobanRpcUrl Optional Soroban RPC URL. If not provided, defaults to
     *                                   testnet or public network URL based on the network parameter.
     * @throws InvalidArgumentException if any parameter is invalid
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
        // Validate webAuthContractId
        if (!str_starts_with($webAuthContractId, 'C')) {
            throw new InvalidArgumentException(
                "webAuthContractId must be a contract address starting with 'C'"
            );
        }

        // Validate serverSigningKey
        if (!str_starts_with($serverSigningKey, 'G')) {
            throw new InvalidArgumentException(
                "serverSigningKey must be an account address starting with 'G'"
            );
        }

        // Validate authEndpoint is a valid URL
        if (filter_var($authEndpoint, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException(
                "authEndpoint must be a valid URL"
            );
        }

        // Validate serverHomeDomain is not empty
        if (empty(trim($serverHomeDomain))) {
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

        // Set Soroban RPC URL based on network if not provided
        if ($sorobanRpcUrl === null) {
            $this->sorobanRpcUrl = $this->network->getNetworkPassphrase() === Network::testnet()->getNetworkPassphrase()
                ? 'https://soroban-testnet.stellar.org'
                : 'https://soroban.stellar.org';
        } else {
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
     * @param Network $network The network used (testnet or pubnet)
     * @param Client|null $httpClient Optional HTTP client to be used for requests
     * @return WebAuthForContracts configured instance
     * @throws Exception if required fields are missing from stellar.toml
     */
    public static function fromDomain(string $domain, Network $network, ?Client $httpClient = null): WebAuthForContracts
    {
        $stellarToml = StellarToml::fromDomain($domain, $httpClient);
        $webAuthForContractsEndpoint = $stellarToml->getGeneralInformation()->webAuthForContractsEndpoint;
        $webAuthContractId = $stellarToml->getGeneralInformation()->webAuthContractId;
        $signingKey = $stellarToml->getGeneralInformation()->signingKey;

        if (!$webAuthForContractsEndpoint) {
            throw new Exception("No WEB_AUTH_FOR_CONTRACTS_ENDPOINT found in stellar.toml");
        }
        if (!$webAuthContractId) {
            throw new Exception("No WEB_AUTH_CONTRACT_ID found in stellar.toml");
        }
        if (!$signingKey) {
            throw new Exception("No auth server SIGNING_KEY found in stellar.toml");
        }

        return new WebAuthForContracts(
            $webAuthForContractsEndpoint,
            $webAuthContractId,
            $signingKey,
            $domain,
            $network,
            $httpClient
        );
    }

    /**
     * Sets whether to use application/x-www-form-urlencoded when submitting challenges.
     * By default, application/json is used.
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
     * 3. Signs the client entry with provided signers
     * 4. Submits the signed entries to obtain a JWT token
     *
     * @param string $clientAccountId Contract account (C...) to authenticate
     * @param array<KeyPair> $signers Keypairs to sign the client authorization entry. For contracts
     *                                that implement __check_auth with signature verification, provide
     *                                the keypairs with sufficient weight to meet the contract's
     *                                authentication requirements. Can be empty for contracts whose
     *                                __check_auth implementation does not require signatures (per SEP-45).
     * @param string|null $homeDomain Optional home domain for the challenge request. If not provided,
     *                                defaults to the server home domain from stellar.toml.
     * @param string|null $clientDomain Optional client domain for verification
     * @param KeyPair|null $clientDomainKeyPair Optional keypair for client domain signing
     * @param callable|null $clientDomainSigningCallback Optional callback for remote client domain signing.
     *                                                    Callback signature: function(array $authEntries): array
     * @param int|null $signatureExpirationLedger Optional expiration ledger for signatures (for replay protection).
     *                                            If null and signers are provided, automatically set to current
     *                                            ledger + 10 (approximately 50-60 seconds). If signers array is empty,
     *                                            this parameter is ignored. Per SEP-45, should be set to a near-future
     *                                            ledger for replay protection when signatures are required.
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
     * @throws ContractChallengeValidationErrorSubInvocationsFound
     * @throws ContractChallengeRequestErrorResponse
     * @throws SubmitContractChallengeErrorResponseException
     * @throws SubmitContractChallengeTimeoutResponseException
     * @throws SubmitContractChallengeUnknownResponseException
     * @throws GuzzleException
     * @throws InvalidArgumentException
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
        // Validate client account ID is a contract address
        if (!str_starts_with($clientAccountId, 'C')) {
            throw new InvalidArgumentException("Client account must be a contract address (C...)");
        }

        // Use server home domain as default if not provided
        $effectiveHomeDomain = $homeDomain ?? $this->serverHomeDomain;

        // Get the challenge authorization entries from the web auth server
        $challengeResponse = $this->getChallenge($clientAccountId, $effectiveHomeDomain, $clientDomain);

        // Validate network passphrase if provided in the challenge response
        if ($challengeResponse->getNetworkPassphrase() !== null) {
            $expectedNetworkPassphrase = $this->network->getNetworkPassphrase();
            $responseNetworkPassphrase = $challengeResponse->getNetworkPassphrase();
            if ($responseNetworkPassphrase !== $expectedNetworkPassphrase) {
                throw new ContractChallengeValidationErrorInvalidNetworkPassphrase(
                    "Network passphrase mismatch. Expected: '$expectedNetworkPassphrase', Got: '$responseNetworkPassphrase'"
                );
            }
        }

        $authEntries = $this->decodeAuthorizationEntries($challengeResponse->getAuthorizationEntries());

        // Determine client domain account ID if needed
        $clientDomainAccountId = null;
        if ($clientDomain != null) {
            if ($clientDomainKeyPair != null) {
                $clientDomainAccountId = $clientDomainKeyPair->getAccountId();
            } else if ($clientDomainSigningCallback != null) {
                try {
                    $toml = StellarToml::fromDomain($clientDomain);
                    $clientDomainAccountId = $toml->generalInformation?->signingKey;
                    if ($clientDomainAccountId == null) {
                        throw new Exception("Could not find signing key in stellar.toml");
                    }
                } catch (Exception $e) {
                    throw new InvalidArgumentException("Invalid client domain: " . $e->getMessage());
                }
            } else {
                throw new InvalidArgumentException("Client domain key pair or client domain signing callback is missing");
            }
        }

        // Validate the authorization entries
        $this->validateChallenge($authEntries, $clientAccountId, $effectiveHomeDomain, $clientDomainAccountId);

        // Auto-fill signatureExpirationLedger if not provided
        $effectiveExpirationLedger = $signatureExpirationLedger;
        if (count($signers) > 0 && $effectiveExpirationLedger === null) {
            $sorobanServer = new SorobanServer($this->sorobanRpcUrl);
            $latestLedgerResponse = $sorobanServer->getLatestLedger();
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
            $clientDomainKeyPair,
            $clientDomainSigningCallback
        );

        // Request the JWT token by sending back the signed authorization entries
        return $this->sendSignedChallenge($signedEntries);
    }

    /**
     * Requests a challenge from the authentication server.
     *
     * @param string $clientAccountId Contract account (C...) to authenticate
     * @param string|null $homeDomain Optional home domain for the request. If not provided,
     *                                defaults to the server home domain from stellar.toml.
     * @param string|null $clientDomain Optional client domain
     * @return ContractChallengeResponse The challenge response
     * @throws ContractChallengeRequestErrorResponse on request failure
     */
    public function getChallenge(
        string $clientAccountId,
        ?string $homeDomain = null,
        ?string $clientDomain = null
    ): ContractChallengeResponse {
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
     * 4. Args validation (account, home_domain, web_auth_domain, nonce, etc.)
     * 5. Server entry exists and has valid signature
     * 6. Client entry exists
     *
     * @param array<SorobanAuthorizationEntry> $authEntries Entries to validate
     * @param string $clientAccountId Expected client account
     * @param string|null $homeDomain Optional expected home domain. If not provided,
     *                                defaults to the server home domain from stellar.toml.
     * @param string|null $clientDomainAccountId Expected client domain account
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
     */
    public function validateChallenge(
        array $authEntries,
        string $clientAccountId,
        ?string $homeDomain = null,
        ?string $clientDomainAccountId = null
    ): void {
        if (count($authEntries) == 0) {
            throw new ContractChallengeValidationError("No authorization entries found");
        }

        // Use server home domain as default if not provided
        $effectiveHomeDomain = $homeDomain ?? $this->serverHomeDomain;

        $nonce = null;
        $serverEntryFound = false;
        $clientEntryFound = false;
        $clientDomainEntryFound = false;

        // Extract web_auth_domain from auth endpoint URL (include port if present)
        $parse = parse_url($this->authEndpoint);
        $webAuthDomain = $parse['host'] ?? '';
        if (isset($parse['port'])) {
            $webAuthDomain .= ':' . $parse['port'];
        }

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
            $contractIdHex = $contractAddress->contractId;
            $expectedContractIdHex = StrKey::decodeContractIdHex($this->webAuthContractId);
            if ($contractIdHex !== $expectedContractIdHex) {
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

            // Check 5: Extract and validate args
            $args = $this->extractArgsFromEntry($entry);

            // Validate account
            if (!isset($args['account']) || $args['account'] !== $clientAccountId) {
                throw new ContractChallengeValidationErrorInvalidAccount(
                    "Account argument does not match client account"
                );
            }

            // Validate home_domain
            if (!isset($args['home_domain']) || $args['home_domain'] !== $effectiveHomeDomain) {
                throw new ContractChallengeValidationErrorInvalidHomeDomain(
                    "Home domain argument does not match expected home domain"
                );
            }

            // Validate web_auth_domain
            if (!isset($args['web_auth_domain']) || $args['web_auth_domain'] !== $webAuthDomain) {
                throw new ContractChallengeValidationErrorInvalidWebAuthDomain(
                    "Web auth domain argument does not match server domain"
                );
            }

            // Validate web_auth_domain_account
            if (!isset($args['web_auth_domain_account']) || $args['web_auth_domain_account'] !== $this->serverSigningKey) {
                throw new ContractChallengeValidationErrorInvalidArgs(
                    "Web auth domain account does not match server signing key"
                );
            }

            // Validate nonce consistency
            if (!isset($args['nonce'])) {
                throw new ContractChallengeValidationErrorInvalidNonce("Nonce argument is missing");
            }
            if ($nonce === null) {
                $nonce = $args['nonce'];
            } else if ($nonce !== $args['nonce']) {
                throw new ContractChallengeValidationErrorInvalidNonce(
                    "Nonce is not consistent across authorization entries"
                );
            }

            // Validate client domain if provided
            if ($clientDomainAccountId !== null) {
                if (isset($args['client_domain_account']) && $args['client_domain_account'] !== $clientDomainAccountId) {
                    throw new ContractChallengeValidationErrorInvalidArgs(
                        "Client domain account does not match expected value"
                    );
                }
            }

            // Check which entry this is (server, client, or client domain)
            $credentials = $entry->credentials;
            if ($credentials->addressCredentials !== null) {
                $credentialsAddress = $credentials->addressCredentials->address;
                $credentialsAddressStr = $credentialsAddress->toStrKey();

                if ($credentialsAddressStr === $this->serverSigningKey) {
                    $serverEntryFound = true;
                    // Verify server signature
                    if (!$this->verifyServerSignature($entry, $this->network)) {
                        throw new ContractChallengeValidationErrorInvalidServerSignature(
                            "Server authorization entry has invalid signature"
                        );
                    }
                } else if ($credentialsAddressStr === $clientAccountId) {
                    $clientEntryFound = true;
                } else if ($clientDomainAccountId !== null && $credentialsAddressStr === $clientDomainAccountId) {
                    $clientDomainEntryFound = true;
                }
            }
        }

        // Check 6: Server entry must exist
        if (!$serverEntryFound) {
            throw new ContractChallengeValidationErrorMissingServerEntry(
                "No authorization entry found for server account"
            );
        }

        // Check 7: Client entry must exist
        if (!$clientEntryFound) {
            throw new ContractChallengeValidationErrorMissingClientEntry(
                "No authorization entry found for client account"
            );
        }

        // Check 8: Client domain entry must exist if client domain account is provided
        if ($clientDomainAccountId !== null && !$clientDomainEntryFound) {
            throw new ContractChallengeValidationErrorMissingClientEntry(
                "No authorization entry found for client domain account"
            );
        }
    }

    /**
     * Signs the authorization entries for the client account.
     *
     * @param array<SorobanAuthorizationEntry> $authEntries Entries to sign
     * @param string $clientAccountId Client account to sign for
     * @param array<KeyPair> $signers Keypairs to sign the client authorization entry. For contracts
     *                                that implement __check_auth with signature verification, provide
     *                                the keypairs with sufficient weight to meet the contract's
     *                                authentication requirements. Can be empty for contracts whose
     *                                __check_auth implementation does not require signatures (per SEP-45).
     * @param int|null $signatureExpirationLedger Expiration ledger for signatures. Required if signers
     *                                            array is not empty. Ignored if signers array is empty.
     * @param KeyPair|null $clientDomainKeyPair Optional client domain keypair
     * @param callable|null $clientDomainSigningCallback Optional callback
     * @return array<SorobanAuthorizationEntry> Signed entries
     * @throws RuntimeException if no address credentials are found in entry
     * @throws InvalidArgumentException if callback validation fails
     * @throws Exception if credentials address could not be converted to StrKey representation
     */
    public function signAuthorizationEntries(
        array $authEntries,
        string $clientAccountId,
        array $signers,
        ?int $signatureExpirationLedger,
        ?KeyPair $clientDomainKeyPair = null,
        ?callable $clientDomainSigningCallback = null
    ): array {
        $signedEntries = [];

        foreach ($authEntries as $entry) {
            $credentials = $entry->credentials;
            if ($credentials->addressCredentials !== null) {
                $credentialsAddress = $credentials->addressCredentials->address;
                $credentialsAddressStr = $credentialsAddress->toStrKey();

                // Sign client entry
                if ($credentialsAddressStr === $clientAccountId) {
                    // Set signature expiration ledger if provided
                    if ($signatureExpirationLedger !== null) {
                        $credentials->addressCredentials->signatureExpirationLedger = $signatureExpirationLedger;
                    }

                    // Sign with all provided signers
                    foreach ($signers as $signer) {
                        if ($signer instanceof KeyPair) {
                            $entry->sign($signer, $this->network);
                        }
                    }
                }

                // Sign client domain entry
                if ($clientDomainKeyPair !== null && $credentialsAddressStr === $clientDomainKeyPair->getAccountId()) {
                    if ($signatureExpirationLedger !== null) {
                        $credentials->addressCredentials->signatureExpirationLedger = $signatureExpirationLedger;
                    }
                    $entry->sign($clientDomainKeyPair, $this->network);
                }
            }

            $signedEntries[] = $entry;
        }

        // Call client domain signing callback if provided
        if ($clientDomainSigningCallback !== null) {
            $inputCount = count($signedEntries);
            $callbackResult = $clientDomainSigningCallback($signedEntries);

            // Validate callback return value
            if (!is_array($callbackResult)) {
                throw new InvalidArgumentException("Client domain signing callback must return an array");
            }
            if (count($callbackResult) !== $inputCount) {
                throw new InvalidArgumentException(
                    "Client domain signing callback must return same number of entries as input. " .
                    "Expected: $inputCount, Got: " . count($callbackResult)
                );
            }
            foreach ($callbackResult as $entry) {
                if (!($entry instanceof SorobanAuthorizationEntry)) {
                    throw new InvalidArgumentException(
                        "Client domain signing callback must return array of SorobanAuthorizationEntry objects"
                    );
                }
            }
            $signedEntries = $callbackResult;
        }

        return $signedEntries;
    }

    /**
     * Submits signed authorization entries to obtain a JWT token.
     *
     * @param array<SorobanAuthorizationEntry> $signedEntries Signed entries
     * @return string JWT token
     * @throws SubmitContractChallengeErrorResponseException on error
     * @throws SubmitContractChallengeTimeoutResponseException on timeout
     * @throws SubmitContractChallengeUnknownResponseException on unknown response
     * @throws GuzzleException
     */
    public function sendSignedChallenge(array $signedEntries): string
    {
        $base64Xdr = $this->encodeAuthorizationEntries($signedEntries);

        $requestOptions = ['http_errors' => false];
        if ($this->useFormUrlEncoded) {
            $requestOptions[RequestOptions::FORM_PARAMS] = ['authorization_entries' => $base64Xdr];
        } else {
            $requestOptions[RequestOptions::JSON] = ['authorization_entries' => $base64Xdr];
        }

        $response = $this->httpClient->post(
            $this->authEndpoint,
            $requestOptions
        );

        $statusCode = $response->getStatusCode();
        if (200 == $statusCode || 400 == $statusCode) {
            $content = $response->getBody()->__toString();
            $jsonData = @json_decode($content, true);
            if (null === $jsonData && json_last_error() != JSON_ERROR_NONE) {
                throw new SubmitContractChallengeErrorResponseException(
                    sprintf("Error in json_decode: %s", json_last_error_msg())
                );
            }
            $result = SubmitContractChallengeResponse::fromJson($jsonData);
            if ($result->getError()) {
                throw new SubmitContractChallengeErrorResponseException($result->getError());
            } else if ($result->getJwtToken()) {
                return $result->getJwtToken();
            } else {
                throw new SubmitContractChallengeErrorResponseException("An unknown error occurred");
            }
        } else if (504 == $statusCode) {
            throw new SubmitContractChallengeTimeoutResponseException();
        } else {
            throw new SubmitContractChallengeUnknownResponseException(
                $response->getBody()->__toString(),
                $response->getStatusCode()
            );
        }
    }

    /**
     * Decodes authorization entries from base64 XDR.
     *
     * @param string $base64Xdr Base64-encoded XDR array of SorobanAuthorizationEntry
     * @return array<SorobanAuthorizationEntry> Decoded authorization entries
     * @throws ContractChallengeValidationError if decoding fails
     */
    private function decodeAuthorizationEntries(string $base64Xdr): array
    {
        try {
            $xdr = base64_decode($base64Xdr);
            $xdrBuffer = new XdrBuffer($xdr);

            // Decode as array of SorobanAuthorizationEntry
            $count = $xdrBuffer->readInteger32();
            $entries = [];
            for ($i = 0; $i < $count; $i++) {
                $entries[] = SorobanAuthorizationEntry::fromXdr(XdrSorobanAuthorizationEntry::decode($xdrBuffer));
            }

            return $entries;
        } catch (Exception $e) {
            throw new ContractChallengeValidationError("Failed to decode authorization entries: " . $e->getMessage());
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
     * Extracts args map from authorization entry.
     *
     * The args are expected to be in Map<Symbol, String> format containing:
     * - account: Client contract account (C...)
     * - home_domain: Server's home domain
     * - web_auth_domain: Server's auth domain
     * - web_auth_domain_account: Server's signing key (G...)
     * - nonce: Unique replay protection value
     * - client_domain: Optional client domain
     * - client_domain_account: Optional client domain signing key
     *
     * @param SorobanAuthorizationEntry $entry Entry to extract args from
     * @return array<string, string> Args map
     * @throws ContractChallengeValidationErrorInvalidArgs if args cannot be extracted
     */
    private function extractArgsFromEntry(SorobanAuthorizationEntry $entry): array
    {
        try {
            $function = $entry->rootInvocation->function;
            if ($function->contractFn === null) {
                throw new ContractChallengeValidationErrorInvalidArgs("Not a contract function");
            }

            $argsArray = $function->contractFn->args;
            if (count($argsArray) == 0) {
                throw new ContractChallengeValidationErrorInvalidArgs("No arguments found");
            }

            // First arg should be a map
            $argsVal = $argsArray[0];
            if ($argsVal->type->value !== XdrSCValType::SCV_MAP || $argsVal->map === null) {
                throw new ContractChallengeValidationErrorInvalidArgs("Arguments are not in map format");
            }

            $result = [];
            foreach ($argsVal->map as $mapEntry) {
                // Key should be a symbol
                if ($mapEntry->key->type->value !== XdrSCValType::SCV_SYMBOL || $mapEntry->key->sym === null) {
                    continue;
                }
                $key = $mapEntry->key->sym;

                // Value should be a string
                if ($mapEntry->val->type->value !== XdrSCValType::SCV_STRING || $mapEntry->val->str === null) {
                    continue;
                }
                $value = $mapEntry->val->str;

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
     * Verifies server signature on authorization entry.
     *
     * This method performs two critical security checks:
     * 1. Validates the signature itself is cryptographically valid
     * 2. Verifies the public key extracted from the signature structure matches the expected
     *    server signing key from stellar.toml
     *
     * The second check prevents an attacker from embedding their own public key in the signature
     * structure and signing the challenge with their key. Without this validation, an attacker
     * could intercept the authentication flow, create a fake challenge with their own signing
     * key embedded in the signature, and pass validation. This would enable man-in-the-middle
     * attacks where the attacker can capture client signatures.
     *
     * Security Impact:
     * Critical. Always verify the extracted public key matches the expected server signing key
     * before validating the signature. This prevents signature substitution attacks.
     *
     * @param SorobanAuthorizationEntry $entry Entry to verify
     * @param Network $network Network for signature verification
     * @return bool True if signature is valid and public key matches expected server signing key
     */
    private function verifyServerSignature(SorobanAuthorizationEntry $entry, Network $network): bool
    {
        try {
            $xdrCredentials = $entry->credentials->toXdr();
            if ($entry->credentials->addressCredentials == null ||
                $xdrCredentials->type->value != XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS ||
                $xdrCredentials->address == null) {
                return false;
            }

            // Build authorization preimage
            $networkId = Hash::generate($network->getNetworkPassphrase());
            $authPreimageXdr = new XdrHashIDPreimageSorobanAuthorization(
                $networkId,
                $xdrCredentials->address->nonce,
                $xdrCredentials->address->signatureExpirationLedger,
                $entry->rootInvocation->toXdr()
            );
            $rootInvocationPreimage = new XdrHashIDPreimage(
                new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_SOROBAN_AUTHORIZATION)
            );
            $rootInvocationPreimage->sorobanAuthorization = $authPreimageXdr;

            $payload = Hash::generate($rootInvocationPreimage->encode());

            // Get signature from credentials
            $signatureVal = $entry->credentials->addressCredentials->signature;
            if ($signatureVal->type->value !== XdrSCValType::SCV_VEC || $signatureVal->vec === null || count($signatureVal->vec) == 0) {
                return false;
            }

            // Extract public key and signature from first signature entry
            $firstSig = $signatureVal->vec[0];
            if ($firstSig->type->value !== XdrSCValType::SCV_MAP || $firstSig->map === null) {
                return false;
            }

            $publicKey = null;
            $signature = null;
            foreach ($firstSig->map as $mapEntry) {
                if ($mapEntry->key->type->value === XdrSCValType::SCV_SYMBOL) {
                    if ($mapEntry->key->sym === "public_key" && $mapEntry->val->type->value === XdrSCValType::SCV_BYTES && $mapEntry->val->bytes !== null) {
                        $publicKey = $mapEntry->val->bytes->value;
                    } else if ($mapEntry->key->sym === "signature" && $mapEntry->val->type->value === XdrSCValType::SCV_BYTES && $mapEntry->val->bytes !== null) {
                        $signature = $mapEntry->val->bytes->value;
                    }
                }
            }

            if ($publicKey === null || $signature === null) {
                return false;
            }

            // Verify that extracted public key matches expected server signing key
            $expectedPublicKey = KeyPair::fromAccountId($this->serverSigningKey)->getPublicKey();
            if ($publicKey !== $expectedPublicKey) {
                return false;
            }

            // Verify signature
            $serverKeyPair = KeyPair::fromAccountId($this->serverSigningKey);
            return $serverKeyPair->verifySignature($signature, $payload);
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

        if ($clientDomain) {
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
}
