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
     */
    public function __construct(string $authEndpoint, string $serverSigningKey, string $serverHomeDomain, Network $network) {
        $this->authEndpoint = $authEndpoint;
        $this->serverSigningKey = $serverSigningKey;
        $this->network = $network;
        $this->serverHomeDomain = $serverHomeDomain;
        $this->httpClient = new Client([
            'base_uri' => $this->authEndpoint,
            'exceptions' => false,
        ]);
    }


    /** Creates a WebAuth instance by loading the needed data from the stellar.toml file hosted on the given domain.
     *  e.g. fromDomain("soneso.com", Network::testnet())
     * @param string $domain The domain from which to get the stellar information
     * @param Network $network The network used.
     * @return WebAuth
     * @throws Exception
     */
    public static function fromDomain(string $domain, Network $network) : WebAuth {

        $stellarToml = StellarToml::fromDomain($domain);
        $webAuthEndpoint = $stellarToml->getGeneralInformation()->webAuthEndpoint;
        $signingKey = $stellarToml->getGeneralInformation()->signingKey;
        if (!$webAuthEndpoint) {
            throw new Exception("No WEB_AUTH_ENDPOINT found in stellar.toml");
        }
        if (!$signingKey) {
            throw new Exception("No auth server SIGNING_KEY found in stellar.toml");
        }
        return new WebAuth($webAuthEndpoint, $signingKey, $domain, $network);
    }

    /**
     * Get JWT token for wallet.
     * @param string $clientAccountId The account id of the client/user to get the JWT token for.
     * @param array $signers list of signers (keypairs including secret seed) of the client account
     * @param int|null $memo optional, ID memo of the client account if muxed and accountId starts with G
     * @param string|null $homeDomain optional, used for requesting the challenge depending on the home domain if needed. The web auth server may serve multiple home domains.
     * @param string|null $clientDomain optional, domain of the client hosting it's stellar.toml
     * @param KeyPair|null $clientDomainKeyPair optional, KeyPair of the client domain account including the seed (mandatory and used for signing the transaction if client domain is provided)
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
     * @throws GuzzleException|ChallengeRequestErrorResponse
     */
    public function jwtToken(string $clientAccountId, array $signers, ?int $memo = null, ?string $homeDomain = null, ?string $clientDomain = null, ?KeyPair $clientDomainKeyPair = null) : string {

        // get the challenge transaction from the web auth server
        $transaction = $this->getChallenge($clientAccountId,$memo, $homeDomain, $clientDomain);

        // validate the transaction received from the web auth server.
        $this->validateChallenge($transaction, $clientAccountId, $clientDomainKeyPair?->getAccountId(), $this->gracePeriod, $memo);

        if ($clientDomainKeyPair) {
            array_push($signers, $clientDomainKeyPair);
        }

        // sign the transaction received from the web auth server using the provided user/client keypair by parameter.
        $signedTransaction = $this->signTransaction($transaction, $signers);

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
        $response = $this->httpClient->post($this->authEndpoint, [RequestOptions::JSON => ['transaction' => $base64EnvelopeXDR]]);
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
            throw new SubmitCompletedChallengeUnknownResponseException($response->getBody(), $response->getStatusCode());
        }
    }

    /**
     * @param string $challengeTransaction
     * @param array $signers
     * @return string
     * @throws ChallengeValidationError
     */
    private function signTransaction(string $challengeTransaction, array $signers) : string {
        $res = base64_decode($challengeTransaction);
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
        $requestBuilder = (new ChallengeRequestBuilder($this->httpClient))->forAccountId($accountId);
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