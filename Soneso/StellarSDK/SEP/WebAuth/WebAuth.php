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
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\Toml\StellarToml;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrDecoratedSignature;
use Soneso\StellarSDK\Xdr\XdrEnvelopeType;
use Soneso\StellarSDK\Xdr\XdrMemoType;
use Soneso\StellarSDK\Xdr\XdrOperation;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;

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
    public function __construct(string $authEndpoint, string $serverSigningKey, string $serverHomeDomain, Network $network, ?Client $httpClient = null) {
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
     * @param string $clientAccountId The account id of the client/user to get the JWT token for.
     * @param array $signers list of signers (keypairs including secret seed) of the client account
     * @param int|null $memo optional, ID memo of the client account if muxed and accountId starts with G
     * @param string|null $homeDomain optional, used for requesting the challenge depending on the home domain if needed. The web auth server may serve multiple home domains.
     * @param string|null $clientDomain optional, domain of the client hosting it's stellar.toml. If clientDomain is provided,
     * you also need to provide the clientDomainKeyPair or a clientDomainSigningCallback for client domain transaction signing.
     * @param KeyPair|null $clientDomainKeyPair optional, KeyPair of the client domain account including the seed (used for signing the transaction if client domain is provided)
     * @param callable|null $clientDomainSigningCallback optional, a function used for signing the transaction if client domain is provided.
     * The function must accept a string as parameter representing the base64 encoded transaction envelope that needs to be signed. After signing it with the client domain account,
     * the function must return the signed transaction as base64 encoded transaction envelope. This is useful if you don't want or cannot provide the clientDomainKeyPair, e.g. if the signing
     * occurs on a different server.
     * @return string JWT token.
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
     */
    public function jwtToken(string $clientAccountId,
                             array $signers,
                             ?int $memo = null,
                             ?string $homeDomain = null,
                             ?string $clientDomain = null,
                             ?KeyPair $clientDomainKeyPair = null,
                             ?callable $clientDomainSigningCallback = null) : string {

        // get the challenge transaction from the web auth server
        $transaction = $this->getChallenge($clientAccountId, $memo, $homeDomain, $clientDomain);

        $clientDomainSignerAccountId = null;
        if ($clientDomain != null) {
            if ($clientDomainKeyPair != null) {
                $clientDomainSignerAccountId = $clientDomainKeyPair?->getAccountId();
            } else if ($clientDomainSigningCallback != null) {
                try {
                    $toml = StellarToml::fromDomain($clientDomain);
                    $clientDomainSignerAccountId = $toml->generalInformation?->signingKey;
                    if ($clientDomainSignerAccountId == null) {
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
        $this->validateChallenge($transaction, $clientAccountId, $clientDomainSignerAccountId, $this->gracePeriod, $memo);

        if ($clientDomainKeyPair) {
            array_push($signers, $clientDomainKeyPair);
        }

        // sign the transaction received from the web auth server using the provided user/client keypair by parameter.
        $signedTransaction = $this->signTransaction($transaction, $signers, $clientDomainSigningCallback);

        // request the jwt token by sending back the signed challenge transaction to the web auth server.
        return $this->sendSignedChallengeTransaction($signedTransaction);
    }


    /**
     * Sends the signed challenge transaction back to the web auth server to obtain the jwt token.
     * In case of success, it returns the jwt token obtained from the web auth server.
     * @param string $base64EnvelopeXDR
     * @return string
     * @throws SubmitCompletedChallengeErrorResponseException
     * @throws SubmitCompletedChallengeTimeoutResponseException
     * @throws SubmitCompletedChallengeUnknownResponseException
     * @throws GuzzleException
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
     * Decodes and signs the challenge transaction with the provided signer key pairs. If a clientDomainSigningCallback is provided,
     * the callback will be called to also sign the transaction.
     * @param string $challengeTransaction the base64 encoded transaction envelope to sign.
     * @param array<KeyPair> $signers the key pairs of the signers to sign the transaction with.
     * @param callable|null $clientDomainSigningCallback optional, a function used for signing the transaction if client domain is provided.
     *  The function must accept a string as parameter representing the base64 encoded transaction envelope that needs to be signed. After signing it with the client domain account,
     *  the function must return the signed transaction as base64 encoded transaction envelope.
     * @return string the signed transaction as base64 encoded transaction envelope
     * @throws ChallengeValidationError if the given base64 encoded transaction envelope has an invalid envelope type.
     */
    private function signTransaction(string $challengeTransaction, array $signers, ?callable $clientDomainSigningCallback = null) : string {
        $b64TxEnvelopeToSign = $challengeTransaction;
        if ($clientDomainSigningCallback != null) {
            $b64TxEnvelopeToSign = $clientDomainSigningCallback($challengeTransaction);
        }
        $res = base64_decode($b64TxEnvelopeToSign);
        $xdr = new XdrBuffer($res);
        $envelopeXdr = XdrTransactionEnvelope::decode($xdr);
        if ($envelopeXdr->getType()->getValue() != XdrEnvelopeType::ENVELOPE_TYPE_TX) {
            throw new ChallengeValidationError("Invalid transaction type");
        }
        $txHash = AbstractTransaction::fromEnvelopeXdr($envelopeXdr)->hash($this->network);
        $signatures = $envelopeXdr->getV1()->getSignatures();
        foreach ($signers as $signer) {
            if ($signer instanceof KeyPair) {
                $signature = $signer->signDecorated($txHash);
                if ($signature) {
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
     * @param string $clientAccountId The account id of the client/user that requests the challenge.
     * @param int|null $memo optional, ID memo of the client account if muxed and accountId starts with G
     * @param string|null $homeDomain optional, used for requesting the challenge depending on the home domain if needed. The web auth server may serve multiple home domains.
     * @param string|null $clientDomain optional, domain of the client hosting it's stellar.toml
     * @return string
     * @throws ChallengeRequestErrorResponse
     */
    private function getChallenge(string $clientAccountId, ?int $memo = null, ?string $homeDomain = null, ?string $clientDomain = null) : string {

        $response = $this->getChallengeResponse($clientAccountId, $memo, $homeDomain, $clientDomain);
        $transaction = $response->getTransaction();
        if (!$transaction) {
            throw new ChallengeRequestErrorResponse("Error parsing challenge response");
        }
        return $transaction;
    }

    /** Validates the challenge transaction received from the web auth server.
     * @param string $challengeTransaction
     * @param string $userAccountId
     * @param string|null $clientDomainAccountId
     * @param int|null $timeBoundsGracePeriod
     * @param int|null $memo
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
     */
    private function validateChallenge(string $challengeTransaction, string $userAccountId,  ?string $clientDomainAccountId = null, ?int $timeBoundsGracePeriod = null, ?int $memo = null) {
        $res = base64_decode($challengeTransaction);
        $xdr = new XdrBuffer($res);
        $envelopeXdr = XdrTransactionEnvelope::decode($xdr);

        if ($envelopeXdr->getType()->getValue() != XdrEnvelopeType::ENVELOPE_TYPE_TX) {
            throw new ChallengeValidationError("Invalid transaction type received in challenge");
        }

        $transaction = $envelopeXdr->getV1()->getTx();
        if ($transaction->getSequenceNumber()->getValue()->toString() != "0") {
            throw new ChallengeValidationErrorInvalidSeqNr("Invalid transaction, sequence number not 0");
        }

        if ($transaction->getMemo()->getType()->getValue() != XdrMemoType::MEMO_NONE) {
            if (strpos($userAccountId, "M" ) === 0) {
                throw new ChallengeValidationErrorMemoAndMuxedAccount("Memo and muxed account (M...) found");
            } else if ($transaction->getMemo()->getType()->getValue() != XdrMemoType::MEMO_ID) {
                throw new ChallengeValidationErrorInvalidMemoType("invalid memo type");
            } else if ($memo && $transaction->getMemo()->getId() != $memo) {
                throw new ChallengeValidationErrorInvalidMemoValue("invalid memo value");
            }
        } else if ($memo) {
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

            if ($count == 0 && $dataName != ($this->serverHomeDomain . " auth")) {
                throw new ChallengeValidationErrorInvalidHomeDomain("invalid source account in operation[".$count."]");
            }

            $dataValue = $operation->getBody()->getManageDataOperation()->getValue();
            if ($count > 0 && $dataName == "web_auth_domain") {
                $parse = parse_url($this->authEndpoint);
                $host = $parse['host'];
                if ($host != $dataValue->getValue()) {
                    throw new ChallengeValidationErrorInvalidWebAuthDomain("invalid web auth domain in operation[".$count."]");
                }
            }

            // check timebounds
            $timeBounds = $transaction->getTimeBounds();
            if ($timeBounds) {
                $grace = 0;
                if ($timeBoundsGracePeriod) {
                    $grace = $timeBoundsGracePeriod;
                }
                $currentTime = round(microtime(true));
                if ($currentTime < $timeBounds->getMinTimestamp() - $grace ||
                    $currentTime > $timeBounds->getMaxTimestamp() + $grace) {
                    throw new ChallengeValidationErrorInvalidTimeBounds(
                        "Invalid transaction, invalid time bounds");
                }
            }

            // the envelope must have one signature and it must be valid: transaction signed by the server
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
            $count += 1;
        }
    }


    /**
     * @param string $accountId
     * @param int|null $memo
     * @param string|null $homeDomain
     * @param string|null $clientDomain
     * @return ChallengeResponse
     * @throws ChallengeRequestErrorResponse
     */
    private function getChallengeResponse(string $accountId, ?int $memo = null, ?string $homeDomain = null, ?string $clientDomain = null) : ChallengeResponse {
        if ($memo && strpos($accountId, "M" ) === 0) {
            throw new InvalidArgumentException("memo cannot be used if accountId is a muxed account");
        }
        $requestBuilder = (new ChallengeRequestBuilder($this->authEndpoint, $this->httpClient))->forAccountId($accountId);
        if ($memo) {
            $requestBuilder = $requestBuilder->forMemo($memo);
        }
        if ($homeDomain) {
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

    public function setMockHandler(MockHandler $handler) {
        $handlerStack = HandlerStack::create($handler);
        $this->httpClient = new Client(['handler' => $handlerStack]);
    }
}