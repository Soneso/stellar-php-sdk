<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\BumpSequenceOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Responses\Transaction\SubmitAsyncTransactionResponse;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertTrue;

class SubmitAsyncTransactionTest  extends TestCase
{
    public function testSubmitAsyncSuccess(): void {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPair = KeyPair::random();
        $accountId = $keyPair->getAccountId();
        FriendBot::fundTestAccount($accountId);

        $account = $sdk->requestAccount($accountId);

        $seqNr = $account->getSequenceNumber();
        $bumpTo = $seqNr->add(new BigInteger(10));
        $bumpSequenceOperation = (new BumpSequenceOperationBuilder($bumpTo))->build();
        $transaction = (new TransactionBuilder($account))
            ->addOperation($bumpSequenceOperation)
            ->build();

        $transaction->sign($keyPair, Network::testnet());
        $response = $sdk->submitAsyncTransaction($transaction);
        $this->assertEquals(SubmitAsyncTransactionResponse::TX_STATUS_PENDING, $response->txStatus);

        // wait a couple of seconds for the ledger to close
        sleep(5);
        $transactionResponse = $sdk->requestTransaction($response->hash);
        $this->assertTrue($transactionResponse->isSuccessful());
    }

    public function testSubmitAsyncDuplicate(): void {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPair = KeyPair::random();
        $accountId = $keyPair->getAccountId();
        FriendBot::fundTestAccount($accountId);

        $account = $sdk->requestAccount($accountId);

        $seqNr = $account->getSequenceNumber();
        $bumpTo = $seqNr->add(new BigInteger(10));
        $bumpSequenceOperation = (new BumpSequenceOperationBuilder($bumpTo))->build();
        $transaction = (new TransactionBuilder($account))
            ->addOperation($bumpSequenceOperation)
            ->build();

        $transaction->sign($keyPair, Network::testnet());
        $response = $sdk->submitAsyncTransaction($transaction);
        $this->assertEquals(SubmitAsyncTransactionResponse::TX_STATUS_PENDING, $response->txStatus, );
        sleep(1);
        $response = $sdk->submitAsyncTransaction($transaction);
        $this->assertEquals(SubmitAsyncTransactionResponse::TX_STATUS_DUPLICATE, $response->txStatus);
        $this->assertEquals(409, $response->httpStatusCode);

        // wait a couple of seconds for the ledger to close
        sleep(5);
        $transactionResponse = $sdk->requestTransaction($response->hash);
        $this->assertTrue($transactionResponse->isSuccessful());
    }

    public function testSubmitAsyncMalformed(): void {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPair = KeyPair::random();
        $accountId = $keyPair->getAccountId();
        FriendBot::fundTestAccount($accountId);

        $account = $sdk->requestAccount($accountId);

        $seqNr = $account->getSequenceNumber();
        $bumpTo = $seqNr->add(new BigInteger(10));
        $bumpSequenceOperation = (new BumpSequenceOperationBuilder($bumpTo))->build();
        $transaction = (new TransactionBuilder($account))
            ->addOperation($bumpSequenceOperation)
            ->build();

        $transaction->sign($keyPair, Network::testnet());
        $txEnvelopeXdrBase64 = $transaction->toEnvelopeXdrBase64();
        $txEnvelopeXdrBase64 = substr($txEnvelopeXdrBase64, -5);

        $thrown = false;
        try {
            $sdk->submitAsyncTransactionEnvelopeXdrBase64($txEnvelopeXdrBase64);
        } catch (HorizonRequestException $e) {
            assertEquals(400, $e->getStatusCode());
            $horizonErrorResponse = $e->getHorizonErrorResponse();
            assertNotNull($horizonErrorResponse);
            assertEquals(400, $horizonErrorResponse->status);
            assertEquals($txEnvelopeXdrBase64, $horizonErrorResponse->extras->getEnvelopeXdr());
            $thrown = true;
        }
        assertTrue($thrown);
    }

    public function testSubmitAsyncError(): void {
        $sdk = StellarSDK::getTestNetInstance();
        $keyPair = KeyPair::random();
        $accountId = $keyPair->getAccountId();
        FriendBot::fundTestAccount($accountId);

        $account = new Account($accountId, new BigInteger(1000000));

        $seqNr = $account->getSequenceNumber();
        $bumpTo = $seqNr->add(new BigInteger(10));
        $bumpSequenceOperation = (new BumpSequenceOperationBuilder($bumpTo))->build();
        $transaction = (new TransactionBuilder($account))
            ->addOperation($bumpSequenceOperation)
            ->build();

        $transaction->sign($keyPair, Network::testnet());
        $response = $sdk->submitAsyncTransaction($transaction);
        $this->assertEquals(SubmitAsyncTransactionResponse::TX_STATUS_ERROR, $response->txStatus);
        $this->assertEquals(400, $response->httpStatusCode);

    }

}