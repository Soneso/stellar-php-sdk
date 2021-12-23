<?php

namespace Soneso\StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;

class ExamplesTest extends TestCase
{
    public function testQuickstart(): void
    {
        $keyPair = KeyPair::random();
        print($keyPair->getAccountId().PHP_EOL);
        print($keyPair->getSecretSeed().PHP_EOL);
        $this->assertTrue(true);
        $funded = FriendBot::fundTestAccount($keyPair->getAccountId());
        print ($funded ? "account funded" : "account not funded");

        $sdk = StellarSDK::getTestNetInstance();

        /// Create a key pair for your existing account.
        $keyA = KeyPair::fromSeed($keyPair->getSecretSeed());

        /// Load the data of your account from the stellar network.
        $accA = $sdk->requestAccount($keyA->getAccountId());

        /// Create a keypair for a new account.
        $keyB = KeyPair::random();

        /// Create the operation builder.
        $createAccBuilder = new CreateAccountOperationBuilder($keyB->getAccountId(), "1.0"); // send 3 XLM (lumen)

        // Create the transaction.
        $transaction = (new TransactionBuilder($accA))
            ->addOperation($createAccBuilder->build())
            ->build();

        /// Sign the transaction with the key pair of your existing account.
        $transaction->sign($keyA, Network::testnet());

        /// Submit the transaction to the stellar network.
        $response = $sdk->submitTransaction($transaction);

        if ($response->isSuccessful()) {
            printf (PHP_EOL."account %s created", $keyB->getAccountId());
        }

        $accountId = $keyB->getAccountId();//"GCQHNQR2VM5OPXSTWZSF7ISDLE5XZRF73LNU6EOZXFQG2IJFU4WB7VFY";

        // Request the account data.
        $account = $sdk->requestAccount($accountId);

        // You can check the `balance`, `sequence`, `flags`, `signers`, `data` etc.
        foreach ($account->getBalances() as $balance) {
            switch ($balance->getAssetType()) {
                case Asset::TYPE_NATIVE:
                    printf (PHP_EOL."Balance: %s XLM", $balance->getBalance() );
                    break;
                default:
                    printf(PHP_EOL."Balance: %s %s Issuer: %s",
                        $balance->getBalance(), $balance->getAssetCode(),
                        $balance->getAssetIssuer());
            }
        }

        print(PHP_EOL."Sequence number: ".$account->getSequenceNumber());

        foreach ($account->getSigners() as $signer) {
            print(PHP_EOL."Signer public key: ".$signer->getKey());
        }

        $accountId = $account->getAccountId();

        $operationsPage = $sdk->payments()->forAccount($accountId)->order("desc")->execute();

        foreach ($operationsPage->getOperations() as $payment) {
            if ($payment->isTransactionSuccessful()) {
                print(PHP_EOL."Transaction hash: ".$payment->getTransactionHash());
            }
        }


        $senderKeyPair = KeyPair::fromSeed("SA52PD5FN425CUONRMMX2CY5HB6I473A5OYNIVU67INROUZ6W4SPHXZB");
        $destination = "GCRFFUKMUWWBRIA6ABRDFL5NKO6CKDB2IOX7MOS2TRLXNXQD255Z2MYG";

        // Load sender account data from the stellar network.
        $sender = $sdk->requestAccount($senderKeyPair->getAccountId());

        // Build the transaction to send 100 XLM native payment from sender to destination
        $paymentOperation = (new PaymentOperationBuilder($destination,Asset::native(), "100"))->build();
        $transaction = (new TransactionBuilder($sender))
            ->addOperation($paymentOperation)
            ->build();

        // Sign the transaction with the sender's key pair.
        $transaction->sign($senderKeyPair, Network::testnet());

        // Submit the transaction to the stellar network.
        $response = $sdk->submitTransaction($transaction);
        if ($response->isSuccessful()) {
            print(PHP_EOL."Payment sent");
        }
    }
}