<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\URIScheme;

/// Implements utility methods for SEP-007 - URI Scheme to facilitate delegated signing
/// https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0007.md
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
 *
 */
class URIScheme
{
    const uriSchemeName = 'web+stellar:';
    const signOperation = 'tx?';
    const payOperation = 'pay?';
    const xdrParameterName = 'xdr';
    const replaceParameterName = 'replace';
    const callbackParameterName = 'callback';
    const publicKeyParameterName = 'pubkey';
    const chainParameterName = 'chain';
    const messageParameterName = 'msg';
    const networkPassphraseParameterName = 'network_passphrase';
    const originDomainParameterName = 'origin_domain';
    const signatureParameterName = 'signature';
    const destinationParameterName = 'destination';
    const amountParameterName = 'amount';
    const assetCodeParameterName = 'asset_code';
    const assetIssuerParameterName = 'asset_issuer';
    const memoParameterName = 'memo';
    const memoTypeParameterName = 'memo_type';
    const uriSchemePrefix = 'stellar.sep.7 - URI Scheme';

    private Client $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'exceptions' => false,
        ]);
    }

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
            $queryParams[URIScheme::messageParameterName] = urlencode($callback);
        }
        if ($message != null) {
            $queryParams[URIScheme::assetCodeParameterName] = urlencode($message);
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
     * @throws HorizonRequestException
     * @throws GuzzleException
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
        if ($callback != null && substr($callback, 0, 4 ) === "url:") {
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

    public function signURI(string $url, KeyPair $signerKeyPair) : string {
        $urlEncodedBase64Signature = $this->sign($url, $signerKeyPair);
        if ($this->verify($url, $urlEncodedBase64Signature, $signerKeyPair)) {
           return $url . "&" . URIScheme::signatureParameterName . "=" . $urlEncodedBase64Signature;
        } else {
            throw new RuntimeException("could not sign uri");
        }
    }

    /**
     * @throws URISchemeError
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

    public function getParameterValue(string $parameterName, string $url): ?string {
        $url_components = parse_url($url);
        parse_str($url_components['query'], $params);
        return $params[$parameterName] ?? null;
    }

    private function verify(string $url, string $urlEncodedBase64Signature, KeyPair $signerPublicKey) : bool {
        $sigParam = '&'.URIScheme::signatureParameterName.'='.$urlEncodedBase64Signature;
        $urlSignatureLess = str_replace($sigParam, '', $url);
        $payloadBytes = $this->getPayload($urlSignatureLess);
        $base64Signature = urldecode($urlEncodedBase64Signature);
        return $signerPublicKey->verifySignature(base64_decode($base64Signature), $payloadBytes);
    }

    private function sign(string $url, KeyPair $signerKeypair) : string {
        $payloadBytes = $this->getPayload($url);
        $signatureBytes = $signerKeypair->sign($payloadBytes);
        $base64Signature = base64_encode($signatureBytes);
        return urlencode($base64Signature);
    }

    private function getPayload(string $url) : string {
        $payloadStart = array();
        for ($i = 0; $i < 36; $i++) {
            $payloadStart[$i] = pack('C', 0);
        }
        $payloadStart[35] = pack('C', 4);
        $urlBytes = $this->stringToBinary(URIScheme::uriSchemePrefix . $url);
        return implode('', $payloadStart) . $urlBytes;
    }

    private function stringToBinary($string) : string {
        $characters = str_split($string);

        $binary = [];
        foreach ($characters as $character) {
            $data = unpack('H*', $character);
            $binary[] = base_convert($data[1], 16, 2);
        }

        return implode(' ', $binary);
    }

    private function binaryToString($binary) : string {
        $binaries = explode(' ', $binary);

        $string = null;
        foreach ($binaries as $binary) {
            $string .= pack('H*', dechex(bindec($binary)));
        }

        return $string;
    }

    private function getXdrTransactionEnvelope(string $url): XdrTransactionEnvelope {
        $base64UrlEncodedTransactionEnvelope = $this->getParameterValue(URIScheme::xdrParameterName, $url);
        if ($base64UrlEncodedTransactionEnvelope != null) {
            $base64TransactionEnvelope = urldecode($base64UrlEncodedTransactionEnvelope);
            return XdrTransactionEnvelope::fromEnvelopeBase64XdrString($base64TransactionEnvelope);
        } else {
            throw new InvalidArgumentException("invalid url: ". $url);
        }
    }

    private function isValidDomainName($domain_name) : bool
    {
        return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name) //valid chars check
            && preg_match("/^.{1,253}$/", $domain_name) //overall length check
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name)   ); //length of each label
    }

    public function setMockHandlerStack(HandlerStack $handlerStack) {
        $this->httpClient = new Client(['handler' => $handlerStack]);
    }
}