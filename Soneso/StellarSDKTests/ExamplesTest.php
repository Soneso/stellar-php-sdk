<?php

namespace Soneso\StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
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
        print($keyPair->getAccountId() . PHP_EOL);
        print($keyPair->getSecretSeed() . PHP_EOL);
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
            printf(PHP_EOL . "account %s created", $keyB->getAccountId());
        }

        $accountId = $keyB->getAccountId();//"GCQHNQR2VM5OPXSTWZSF7ISDLE5XZRF73LNU6EOZXFQG2IJFU4WB7VFY";

        // Request the account data.
        $account = $sdk->requestAccount($accountId);

        // You can check the `balance`, `sequence`, `flags`, `signers`, `data` etc.
        foreach ($account->getBalances() as $balance) {
            switch ($balance->getAssetType()) {
                case Asset::TYPE_NATIVE:
                    printf(PHP_EOL . "Balance: %s XLM", $balance->getBalance());
                    break;
                default:
                    printf(PHP_EOL . "Balance: %s %s Issuer: %s",
                        $balance->getBalance(), $balance->getAssetCode(),
                        $balance->getAssetIssuer());
            }
        }

        print(PHP_EOL . "Sequence number: " . $account->getSequenceNumber());

        foreach ($account->getSigners() as $signer) {
            print(PHP_EOL . "Signer public key: " . $signer->getKey());
        }

        $accountId = $account->getAccountId();

        $operationsPage = $sdk->payments()->forAccount($accountId)->order("desc")->execute();

        foreach ($operationsPage->getOperations() as $payment) {
            if ($payment->isTransactionSuccessful()) {
                print(PHP_EOL . "Transaction hash: " . $payment->getTransactionHash());
            }
        }


        $senderKeyPair = KeyPair::fromSeed("SA52PD5FN425CUONRMMX2CY5HB6I473A5OYNIVU67INROUZ6W4SPHXZB");
        $destination = "GCRFFUKMUWWBRIA6ABRDFL5NKO6CKDB2IOX7MOS2TRLXNXQD255Z2MYG";

        // Load sender account data from the stellar network.
        $sender = $sdk->requestAccount($senderKeyPair->getAccountId());

        // Build the transaction to send 100 XLM native payment from sender to destination
        $paymentOperation = (new PaymentOperationBuilder($destination, Asset::native(), "100"))->build();
        $transaction = (new TransactionBuilder($sender))
            ->addOperation($paymentOperation)
            ->build();

        // Sign the transaction with the sender's key pair.
        $transaction->sign($senderKeyPair, Network::testnet());

        // Submit the transaction to the stellar network.
        $response = $sdk->submitTransaction($transaction);
        if ($response->isSuccessful()) {
            print(PHP_EOL . "Payment sent");
        }
    }

    public function testCreateAccount(): void
    {

        // friendbot

        $sdk = StellarSDK::getTestNetInstance();

        // Create a random key pair for our new account.
        $keyPair = KeyPair::random();

        // Ask the Friendbot to create our new account in the stellar network (only available in testnet).
        $funded = FriendBot::fundTestAccount($keyPair->getAccountId());

        // Load the data of the new account from stellar.
        $account = $sdk->requestAccount($keyPair->getAccountId());

        $this->assertNotNull($account);


        // Create

        $sdk = StellarSDK::getTestNetInstance();

        // Build a key pair from the seed of an existing account. We will need it for signing.
        $existingAccountKeyPair = KeyPair::fromSeed("SAYCJIDYFEUY4IYBTOLACOV33BWIBQUAO7YKNMNGQX7QHFGE364KHKDR");

        // Existing account id.
        $existingAccountId = $existingAccountKeyPair->getAccountId();

        // Create a random keypair for a new account to be created.
        $newAccountKeyPair = KeyPair::random();

        // Load the data of the existing account so that we receive it's current sequence number.
        $existingAccount = $sdk->requestAccount($existingAccountId);

        // Build a transaction containing a create account operation to create the new account.
        // Starting balance: 10 XLM.
        $createAccountOperation = (new CreateAccountOperationBuilder($newAccountKeyPair->getAccountId(), "10"))->build();
        $transaction = (new TransactionBuilder($existingAccount))->addOperation($createAccountOperation)->build();

        // Sign the transaction with the key pair of the existing account.
        $transaction->sign($existingAccountKeyPair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Load the data of the new created account.
        $newAccount = $sdk->requestAccount($newAccountKeyPair->getAccountId());

        $this->assertNotNull($newAccount);

    }

    public function testSendNonNative(): void
    {

        $sdk = StellarSDK::getTestNetInstance();

        // Create the key pairs of issuer, sender and receiver from their secret seeds. We will need them for signing.
        $issuerKeyPair = KeyPair::fromSeed("SD3UQ2IRQSC4VM4CPMRD6H6EOGSZWUTX3K3DP6GJRBDPL4UL5RQIQTD4");
        $senderKeyPair = KeyPair::fromSeed("SCYMI7XBFZUMKNTTGZSEJWWDMR4KA2QTDPUKTAMIDI353NFHA3MMQST7");
        $receiverKeyPair = KeyPair::fromSeed("SD3ZC4QWYNXL2XIK4GZXGOTZU5CTD2XRWSCAW4GJYUBOKZQ4GQASYAWG");

        // Account Ids.
        $issuerAccountId = $issuerKeyPair->getAccountId();
        $senderAccountId = $senderKeyPair->getAccountId();
        $receiverAccountId = $receiverKeyPair->getAccountId();

        // Define the custom asset/token issued by the issuer account.
        $iomAsset = new AssetTypeCreditAlphaNum4("IOM", $issuerAccountId);

        // Prepare a change trust operation so that we can create trustlines for both, the sender and receiver.
        // Both need to trust the IOM asset issued by the issuer account so that they can hold the token/asset.
        // Trust limit is 10000.
        $chOp = (new ChangeTrustOperationBuilder($iomAsset, "10000"))->build();

        // Load the sender account data from the stellar network so that we have it's current sequence number.
        $sender = $sdk->requestAccount($senderAccountId);

        // Build the transaction for the trustline (sender trusts custom asset).
        $transaction = (new TransactionBuilder($sender))->addOperation($chOp)->build();

        // The sender signs the transaction.
        $transaction->sign($senderKeyPair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Load the receiver account so that we have it's current sequence number.
        $receiver = $sdk->requestAccount($receiverAccountId);

        // Build the transaction for the trustline (receiver trusts custom asset).
        $transaction = (new TransactionBuilder($receiver))->addOperation($chOp )->build();

        // The receiver signs the transaction.
        $transaction->sign($receiverKeyPair, Network::testnet());

        // Submit the transaction.
        $sdk->submitTransaction($transaction);

        // Load the issuer account so that we have it's current sequence number.
        $issuer = $sdk->requestAccount($issuerAccountId);

        // Send 500 IOM non native payment from issuer to sender.
        $paymentOperation = (new PaymentOperationBuilder($senderAccountId, $iomAsset, "500"))->build();
        $transaction = (new TransactionBuilder($issuer))->addOperation($paymentOperation)->build();

        // The issuer signs the transaction.
        $transaction->sign($issuerKeyPair, Network::testnet());

        // Submit the transaction.
        $sdk->submitTransaction($transaction);

        // The sender now has 500 IOM and can send to the receiver.
        // Send 200 IOM (non native payment) from sender to receiver.
        $paymentOperation = (new PaymentOperationBuilder($receiverAccountId, $iomAsset, "200"))->build();
        $transaction = (new TransactionBuilder($sender))->addOperation($paymentOperation)->build();

        // The sender signs the transaction.
        $transaction->sign($senderKeyPair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Check that the receiver obtained the 200 IOM.
        $receiver = $sdk->requestAccount($receiverAccountId);
        foreach ($receiver->getBalances() as $balance) {
            if ($balance->getAssetType() != Asset::TYPE_NATIVE
                && $balance->getAssetCode() == "IOM"
                && floatval($balance->getBalance()) > 199) {
                print("received IOM payment");
                break;
            }
        }
        $this->assertTrue(true);
    }
}