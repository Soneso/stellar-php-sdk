<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\URIScheme;

/// Implements utility methods for SEP-007 - URI Scheme to facilitate delegated signing
/// https://github.com/stellar/stellar-protocol/blob/v2.1.0/ecosystem/sep-0007.md
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use RuntimeException;
use Soneso\StellarSDK\AbstractTransaction;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\SEP\Toml\StellarToml;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;
use InvalidArgumentException;

/**
 * Implements SEP-7 URI Scheme to facilitate delegated signing.
 *
 * This class provides utility methods for generating, signing, validating, and submitting
 * SEP-7 compliant URI requests for delegated transaction signing. The URIs enable wallets
 * to sign transactions on behalf of applications without exposing secret keys.
 *
 * Key features:
 * - Generate URI for transaction signing (tx operation)
 * - Generate URI for payment requests (pay operation)
 * - Sign URIs with origin domain verification
 * - Validate URI signatures against stellar.toml
 * - Submit signed transactions to network or callback URLs
 *
 * @see https://github.com/stellar/stellar-protocol/blob/v2.1.0/ecosystem/sep-0007.md
 * @package Soneso\StellarSDK\SEP\URIScheme
 */
class URIScheme
{
    /** SEP-7 URI scheme prefix */
    const uriSchemeName = 'web+stellar:';

    /** Transaction signing operation type */
    const signOperation = 'tx?';

    /** Payment request operation type */
    const payOperation = 'pay?';

    /** XDR parameter name for transaction envelope */
    const xdrParameterName = 'xdr';

    /** Replace parameter name for field replacement (SEP-11 format) */
    const replaceParameterName = 'replace';

    /** Callback parameter name for transaction submission URL */
    const callbackParameterName = 'callback';

    /** Public key parameter name for signing account specification */
    const publicKeyParameterName = 'pubkey';

    /** Chain parameter name for nested SEP-7 URI */
    const chainParameterName = 'chain';

    /** Message parameter name for user-facing message */
    const messageParameterName = 'msg';

    /** Network passphrase parameter name */
    const networkPassphraseParameterName = 'network_passphrase';

    /** Origin domain parameter name for request verification */
    const originDomainParameterName = 'origin_domain';

    /** Signature parameter name for URI signing */
    const signatureParameterName = 'signature';

    /** Destination parameter name for payment recipient */
    const destinationParameterName = 'destination';

    /** Amount parameter name for payment amount */
    const amountParameterName = 'amount';

    /** Asset code parameter name */
    const assetCodeParameterName = 'asset_code';

    /** Asset issuer parameter name */
    const assetIssuerParameterName = 'asset_issuer';

    /** Memo parameter name */
    const memoParameterName = 'memo';

    /** Memo type parameter name */
    const memoTypeParameterName = 'memo_type';

    /** SEP-7 signature payload prefix */
    const uriSchemePrefix = 'stellar.sep.7 - URI Scheme';

    private Client $httpClient;

    /**
     * Creates a new URIScheme instance with default HTTP client.
     *
     * Initializes Guzzle HTTP client for stellar.toml fetching and callback
     * URL submissions. Use setMockHandlerStack() for testing scenarios.
     */
    public function __construct()
    {
        $this->httpClient = new Client();
    }

    /**
     * Generates a SEP-7 URI for the 'tx' operation to request transaction signing.
     *
     * Creates a web+stellar:tx URI that requests a wallet to sign a specific transaction.
     * The transaction is encoded as XDR and URL-encoded. Optional parameters enable
     * field replacement, callbacks, signature chaining, and origin verification.
     *
     * @param string $transactionEnvelopeXdrBase64 Base64-encoded XDR TransactionEnvelope to be signed
     * @param string|null $replace URL-encoded Txrep field replacement specification (SEP-11 format)
     * @param string|null $callback URL-encoded callback URL (prefix with "url:") for signed transaction submission
     * @param string|null $publicKey Stellar public key specifying which account should sign
     * @param string|null $chain URL-encoded nested SEP-7 URI for transaction chaining (max 7 levels)
     * @param string|null $message Optional message for wallet user (max 300 characters before encoding)
     * @param string|null $networkPassphrase Network passphrase (omit for public network)
     * @param string|null $originDomain Fully qualified domain name of request originator (requires signature)
     * @param string|null $signature Base64 URL-encoded signature of URI (generated via signURI method)
     * @return string Complete SEP-7 URI string (web+stellar:tx?...)
     *
     * @see https://github.com/stellar/stellar-protocol/blob/v2.1.0/ecosystem/sep-0007.md#operation-tx
     */
    public function generateSignTransactionURI(string $transactionEnvelopeXdrBase64,
                                               ?string $replace = null,
                                               ?string $callback = null,
                                               ?string $publicKey = null,
                                               ?string $chain = null,
                                               ?string $message = null,
                                               ?string $networkPassphrase = null,
                                               ?string $originDomain = null,
                                               ?string $signature = null) : string {

        $result = URIScheme::uriSchemeName . URIScheme::signOperation;

        $queryParams = array();

        $queryParams[URIScheme::xdrParameterName] = urlencode($transactionEnvelopeXdrBase64);

        if ($replace != null) {
            $queryParams[URIScheme::replaceParameterName] = urlencode($replace);
        }

        if ($callback != null) {
            $queryParams[URIScheme::callbackParameterName] = urlencode($callback);
        }

        if ($publicKey != null) {
            $queryParams[URIScheme::publicKeyParameterName] = urlencode($publicKey);
        }

        if ($chain != null) {
            $queryParams[URIScheme::chainParameterName] = urlencode($chain);
        }

        if ($message != null) {
            $queryParams[URIScheme::messageParameterName] = urlencode($message);
        }

        if ($networkPassphrase != null) {
            $queryParams[URIScheme::networkPassphraseParameterName] = urlencode($networkPassphrase);
        }

        if ($originDomain != null) {
            $queryParams[URIScheme::originDomainParameterName] = urlencode($originDomain);
        }

        if ($signature != null) {
            $queryParams[URIScheme::signatureParameterName] = urlencode($signature);
        }

        foreach ($queryParams as $name => $value) {
            $result .= $name . "=" . $value . "&";
        }

        return substr($result,0,-1);
    }

    /**
     * Generates a SEP-7 URI for the 'pay' operation to request payment.
     *
     * Creates a web+stellar:pay URI requesting payment to a destination account.
     * Unlike the tx operation, pay allows wallets to choose the payment method
     * (direct payment or path payment) and source asset, providing flexibility.
     *
     * @param string $destinationAccountId Stellar account ID or payment address for payment recipient
     * @param string|null $amount Amount to send (omit to let user specify donation amount)
     * @param string|null $assetCode Asset code (XLM if omitted)
     * @param string|null $assetIssuer Asset issuer account ID (XLM if omitted)
     * @param string|null $memo Transaction memo value (base64 encode MEMO_HASH/MEMO_RETURN, URL encode all types)
     * @param string|null $memoType Memo type: MEMO_TEXT, MEMO_ID, MEMO_HASH, or MEMO_RETURN
     * @param string|null $callback URL-encoded callback URL (prefix with "url:") for signed transaction submission
     * @param string|null $message Optional message for wallet user (max 300 characters before encoding)
     * @param string|null $networkPassphrase Network passphrase (omit for public network)
     * @param string|null $originDomain Fully qualified domain name of request originator (requires signature)
     * @param string|null $signature Base64 URL-encoded signature of URI (generated via signURI method)
     * @return string Complete SEP-7 URI string (web+stellar:pay?...)
     *
     * @see https://github.com/stellar/stellar-protocol/blob/v2.1.0/ecosystem/sep-0007.md#operation-pay
     */
    public function generatePayOperationURI(string $destinationAccountId,
                                            ?string $amount = null,
                                            ?string $assetCode = null,
                                            ?string $assetIssuer = null,
                                            ?string $memo = null,
                                            ?string $memoType = null,
                                            ?string $callback = null,
                                            ?string $message = null,
                                            ?string $networkPassphrase = null,
                                            ?string $originDomain = null,
                                            ?string $signature = null) : string {


        $result = URIScheme::uriSchemeName . URIScheme::payOperation;

        $queryParams = array();

        $queryParams[URIScheme::destinationParameterName] = urlencode($destinationAccountId);

        if ($amount != null) {
            $queryParams[URIScheme::amountParameterName] = urlencode($amount);
        }

        if ($assetCode != null) {
            $queryParams[URIScheme::assetCodeParameterName] = urlencode($assetCode);
        }
        if ($assetIssuer != null) {
            $queryParams[URIScheme::assetIssuerParameterName] = urlencode($assetIssuer);
        }
        if ($memo != null) {
            $queryParams[URIScheme::memoParameterName] = urlencode($memo);
        }
        if ($memoType != null) {
            $queryParams[URIScheme::memoTypeParameterName] = urlencode($memoType);
        }
        if ($callback != null) {
            $queryParams[URIScheme::callbackParameterName] = urlencode($callback);
        }
        if ($message != null) {
            $queryParams[URIScheme::messageParameterName] = urlencode($message);
        }
        if ($networkPassphrase != null) {
            $queryParams[URIScheme::networkPassphraseParameterName] = urlencode($networkPassphrase);
        }
        if ($originDomain != null) {
            $queryParams[URIScheme::originDomainParameterName] = urlencode($originDomain);
        }
        if ($signature != null) {
            $queryParams[URIScheme::signatureParameterName] = urlencode($signature);
        }

        foreach ($queryParams as $name => $value) {
            $result .= $name . "=" . $value . "&";
        }

        return substr($result,0,-1);
    }

    /**
     * Signs a transaction from a SEP-7 URI and submits it to network or callback URL.
     *
     * This method extracts the transaction from the URI, signs it with the provided keypair,
     * and submits it either to a callback URL (if specified) or directly to the Stellar network.
     *
     * Security Warning: This method performs actual transaction signing and submission.
     * Always validate the URI using checkUIRSchemeIsValid() and obtain explicit user
     * consent before calling this method. Never auto-submit transactions without user review.
     *
     * @param string $url Complete SEP-7 URI containing transaction XDR
     * @param KeyPair $signerKeyPair Keypair used to sign the transaction
     * @param Network|null $network Stellar network (defaults to public network if omitted)
     * @return SubmitUriSchemeTransactionResponse Response containing either submitTransactionResponse or callBackResponse
     *
     * @throws HorizonRequestException If submission to Stellar network fails
     * @throws GuzzleException If HTTP request to callback URL fails
     * @throws InvalidArgumentException If URL does not contain valid XDR parameter
     *
     * @see SubmitUriSchemeTransactionResponse
     */
    public function signAndSubmitTransaction(string $url, KeyPair $signerKeyPair, ?Network $network = null) : SubmitUriSchemeTransactionResponse {

        $net = Network::public();
        if ($network != null) {
            $net = $network;
        }
        $envelope = $this->getXdrTransactionEnvelope($url);
        $absTransaction = AbstractTransaction::fromEnvelopeXdr($envelope);
        $absTransaction->sign($signerKeyPair,$net);

        $callback = $this->getParameterValue(URIScheme::callbackParameterName, $url);
        if ($callback != null && str_starts_with($callback, "url:")) {
            $callbackUrl = substr($callback, 4);
            $headers = array();
            $headers = array_merge($headers, RequestBuilder::HEADERS);
            $headers = array_merge($headers, ['Content-Type' => 'application/x-www-form-urlencoded']);

            $res = $this->httpClient->post($callbackUrl, [
                'headers' => $headers,
                'form_params' => [
                    'xdr' => urlencode($absTransaction->toEnvelopeXdrBase64())
                ]
            ]);
            return new SubmitUriSchemeTransactionResponse(null,$res);
        } else {
            $sdk = $net->getNetworkPassphrase() == Network::testnet()->getNetworkPassphrase() ? StellarSDK::getTestNetInstance() : StellarSDK::getPublicNetInstance();
            $response = $sdk->submitTransaction($absTransaction);
            return new SubmitUriSchemeTransactionResponse($response,null);
        }
    }

    /**
     * Signs a SEP-7 URI with a keypair and appends the signature parameter.
     *
     * Generates a cryptographic signature of the URI request using the SEP-7 signing
     * algorithm (36-byte prefix with signature of "stellar.sep.7 - URI Scheme" + URL).
     * The signature enables wallets to verify the URI originated from the specified domain.
     *
     * Security Warning: Only sign URIs that you have generated and validated. Signing
     * untrusted URIs could enable phishing attacks. The signature will be verified
     * against the URI_REQUEST_SIGNING_KEY in your domain's stellar.toml file.
     *
     * @param string $url SEP-7 URI to sign (must include origin_domain parameter)
     * @param KeyPair $signerKeyPair Keypair for signing (public key must match stellar.toml URI_REQUEST_SIGNING_KEY)
     * @return string Signed URI with appended signature parameter
     *
     * @throws RuntimeException If signature verification fails after signing
     *
     * @see https://github.com/stellar/stellar-protocol/blob/v2.1.0/ecosystem/sep-0007.md#request-signing
     */
    public function signURI(string $url, KeyPair $signerKeyPair) : string {
        $urlEncodedBase64Signature = $this->sign($url, $signerKeyPair);
        if ($this->verify($url, $urlEncodedBase64Signature, $signerKeyPair)) {
           return $url . "&" . URIScheme::signatureParameterName . "=" . $urlEncodedBase64Signature;
        } else {
            throw new RuntimeException("could not sign uri");
        }
    }

    /**
     * Validates a signed SEP-7 URI by verifying signature and origin domain.
     *
     * Performs complete validation workflow per SEP-7 security requirements:
     * 1. Verifies origin_domain parameter exists and is valid FQDN
     * 2. Verifies signature parameter exists
     * 3. Fetches stellar.toml from origin domain
     * 4. Extracts URI_REQUEST_SIGNING_KEY from stellar.toml
     * 5. Cryptographically verifies signature against signing key
     *
     * Security Warning: Always validate signed URIs before displaying origin_domain to users
     * or processing transactions. This prevents homograph attacks and unauthorized transaction
     * requests. Consider implementing stellar.toml caching to improve performance and reduce
     * network requests.
     *
     * Note: Method name contains typo (UIR instead of URI). Maintained for backward compatibility.
     *
     * @param string $url Complete SEP-7 URI with origin_domain and signature parameters
     * @return bool Returns true if validation succeeds
     *
     * @throws URISchemeError With specific error code if validation fails:
     *   - URISchemeError::missingOriginDomain (code 2): origin_domain parameter missing
     *   - URISchemeError::invalidOriginDomain (code 1): origin_domain is not valid FQDN
     *   - URISchemeError::missingSignature (code 3): signature parameter missing
     *   - URISchemeError::tomlNotFoundOrInvalid (code 4): stellar.toml not found or invalid
     *   - URISchemeError::tomlSignatureMissing (code 5): URI_REQUEST_SIGNING_KEY not in stellar.toml
     *   - URISchemeError::invalidSignature (code 0): signature verification failed
     *
     * @see https://github.com/stellar/stellar-protocol/blob/v2.1.0/ecosystem/sep-0007.md#request-signing
     * @see URISchemeError
     */
    public function checkUIRSchemeIsValid(string $url) : bool {

        $originDomain = $this->getParameterValue(URIScheme::originDomainParameterName, $url);
        if ($originDomain == null) {
            throw new URISchemeError(code:URISchemeError::missingOriginDomain);
        }

        if (!$this->isValidDomainName($originDomain)) {
            throw new URISchemeError(code:URISchemeError::invalidOriginDomain);
        }

        $signature = $this->getParameterValue(URIScheme::signatureParameterName, $url);
        if ($signature == null) {
            throw new URISchemeError(code:URISchemeError::missingSignature);
        }

        $toml = null;
        try {
            $toml = StellarToml::fromDomain($originDomain, $this->httpClient);
        } catch (Exception $e) {
            throw new URISchemeError(code:URISchemeError::tomlNotFoundOrInvalid);
        }

        $uriRequestSigningKey = $toml->getGeneralInformation()?->uriRequestSigningKey;
        if ($uriRequestSigningKey == null) {
            throw new URISchemeError(code:URISchemeError::tomlSignatureMissing);
        }

        $signerPublicKey = KeyPair::fromAccountId($uriRequestSigningKey);
        try {
            if (!$this->verify($url, urlencode($signature), $signerPublicKey)) {
                throw new URISchemeError(code:URISchemeError::invalidSignature);
            }
        } catch (Exception) {
            throw new URISchemeError(code:URISchemeError::invalidSignature);
        }
        return true;
    }

    /**
     * Extracts a parameter value from a SEP-7 URI.
     *
     * Utility method for parsing query parameters from SEP-7 URIs.
     * Handles standard URL parsing and returns null if parameter not found.
     *
     * @param string $parameterName Name of the query parameter to extract
     * @param string $url Complete SEP-7 URI containing query parameters
     * @return string|null Parameter value if found, null otherwise
     */
    public function getParameterValue(string $parameterName, string $url): ?string {
        $url_components = parse_url($url);
        parse_str($url_components['query'], $params);
        return $params[$parameterName] ?? null;
    }

    /**
     * Verifies a URI signature using the SEP-7 signature algorithm.
     *
     * Internal method implementing SEP-7 signature verification:
     * 1. Removes signature parameter from URL
     * 2. Generates payload with 36-byte prefix
     * 3. URL-decodes and base64-decodes signature
     * 4. Verifies signature against payload using Ed25519
     *
     * @param string $url Complete SEP-7 URI with signature parameter
     * @param string $urlEncodedBase64Signature URL-encoded base64 signature
     * @param KeyPair $signerPublicKey Public key for signature verification
     * @return bool True if signature is valid, false otherwise
     */
    private function verify(string $url, string $urlEncodedBase64Signature, KeyPair $signerPublicKey) : bool {
        $sigParam = '&'.URIScheme::signatureParameterName.'='.$urlEncodedBase64Signature;
        $urlSignatureLess = str_replace($sigParam, '', $url);
        $payloadBytes = $this->getPayload($urlSignatureLess);
        $base64Signature = urldecode($urlEncodedBase64Signature);
        $signature = base64_decode($base64Signature, true);
        if ($signature === false) {
            throw new InvalidArgumentException('Invalid base64-encoded signature');
        }
        return $signerPublicKey->verifySignature($signature, $payloadBytes);
    }

    /**
     * Generates a signature for a SEP-7 URI using Ed25519 signing.
     *
     * Implements SEP-7 signature generation:
     * 1. Generates payload with 36-byte prefix and URI
     * 2. Signs payload using Ed25519
     * 3. Base64 encodes and URL-encodes signature
     *
     * @param string $url SEP-7 URI to sign (without signature parameter)
     * @param KeyPair $signerKeypair Keypair for signing
     * @return string URL-encoded base64 signature
     */
    private function sign(string $url, KeyPair $signerKeypair) : string {
        $payloadBytes = $this->getPayload($url);
        $signatureBytes = $signerKeypair->sign($payloadBytes);
        $base64Signature = base64_encode($signatureBytes);
        return urlencode($base64Signature);
    }

    /**
     * Generates SEP-7 signature payload per specification.
     *
     * Creates payload for signing/verification:
     * - First 35 bytes: all zeros
     * - 36th byte: value 4
     * - Remaining bytes: "stellar.sep.7 - URI Scheme" + URL
     *
     * This format matches the SEP-7 signature specification.
     *
     * @param string $url SEP-7 URI to include in payload
     * @return string Binary payload ready for signing/verification
     *
     * @see https://github.com/stellar/stellar-protocol/blob/v2.1.0/ecosystem/sep-0007.md#request-signing
     */
    private function getPayload(string $url) : string {
        $payloadStart = array();
        for ($i = 0; $i < 36; $i++) {
            $payloadStart[$i] = pack('C', 0);
        }
        $payloadStart[35] = pack('C', 4);
        $urlBytes = $this->stringToBinary(URIScheme::uriSchemePrefix . $url);
        return implode('', $payloadStart) . $urlBytes;
    }

    /**
     * Converts string to binary representation for payload generation.
     *
     * Internal utility for SEP-7 payload construction. Converts each character
     * to its binary representation as part of signature payload generation.
     *
     * @param string $string Input string to convert
     * @return string Space-separated binary representation
     */
    private function stringToBinary($string) : string {
        $characters = str_split($string);

        $binary = [];
        foreach ($characters as $character) {
            $data = unpack('H*', $character);
            $binary[] = base_convert($data[1], 16, 2);
        }

        return implode(' ', $binary);
    }

    /**
     * Converts binary representation back to string.
     *
     * Internal utility for reversing stringToBinary() operation.
     * Not currently used in SEP-7 implementation but provided for completeness.
     *
     * @param string $binary Space-separated binary representation
     * @return string Reconstructed string
     */
    private function binaryToString($binary) : string {
        $binaries = explode(' ', $binary);

        $string = null;
        foreach ($binaries as $binary) {
            $string .= pack('H*', dechex(bindec($binary)));
        }

        return $string;
    }

    /**
     * Extracts and decodes XDR TransactionEnvelope from SEP-7 URI.
     *
     * Parses the 'xdr' parameter from the URI, handles URL-decoding if necessary,
     * and deserializes the base64-encoded XDR TransactionEnvelope.
     *
     * @param string $url SEP-7 URI containing xdr parameter
     * @return XdrTransactionEnvelope Decoded transaction envelope
     *
     * @throws InvalidArgumentException If xdr parameter is missing or invalid
     */
    private function getXdrTransactionEnvelope(string $url): XdrTransactionEnvelope {
        $base64TransactionEnvelope = $this->getParameterValue(URIScheme::xdrParameterName, $url);
        if ($base64TransactionEnvelope != null) {
            if (str_contains($base64TransactionEnvelope, '%')) {
                $base64TransactionEnvelope = urldecode($base64TransactionEnvelope);
            }
            return XdrTransactionEnvelope::fromEnvelopeBase64XdrString($base64TransactionEnvelope);
        } else {
            throw new InvalidArgumentException("invalid url: ". $url);
        }
    }

    /**
     * Validates domain name format per FQDN rules.
     *
     * Validates that domain name:
     * - Contains only valid characters (a-z, 0-9, hyphen)
     * - Has maximum length of 253 characters
     * - Has labels with maximum length of 63 characters each
     *
     * @param string $domain_name Domain name to validate
     * @return bool True if valid FQDN format, false otherwise
     */
    private function isValidDomainName($domain_name) : bool
    {
        return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name) //valid chars check
            && preg_match("/^.{1,253}$/", $domain_name) //overall length check
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name)   ); //length of each label
    }

    /**
     * Sets a mock HTTP handler stack for testing.
     *
     * Replaces the HTTP client with a mock handler for unit testing.
     * Allows testing of stellar.toml fetching and callback URL submissions
     * without making actual HTTP requests.
     *
     * @param HandlerStack $handlerStack Guzzle mock handler stack
     */
    public function setMockHandlerStack(HandlerStack $handlerStack) {
        $this->httpClient = new Client(['handler' => $handlerStack]);
    }
}