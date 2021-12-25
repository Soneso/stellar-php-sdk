<?php

namespace Soneso\StellarSDKTests;

use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\AccountMergeOperationBuilder;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\BumpSequenceOperationBuilder;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\ManageSellOfferOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PathPaymentStrictReceiveOperationBuilder;
use Soneso\StellarSDK\PathPaymentStrictSendOperationBuilder;
use Soneso\StellarSDK\PaymentOperationBuilder;
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
        $transaction = (new TransactionBuilder($receiver))->addOperation($chOp)->build();

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

    public function testPathPayments(): void
    {
        $sdk = StellarSDK::getTestNetInstance();

        // Prepare new random key pairs, we will need them for signing.
        $issuerKeyPair = KeyPair::random();
        $senderKeyPair = KeyPair::random();
        $firstMiddlemanKeyPair = KeyPair::random();
        $secondMiddlemanKeyPair = KeyPair::random();
        $receiverKeyPair = KeyPair::random();

        // Account Ids.
        $issuerAccountId = $issuerKeyPair->getAccountId();
        $senderAccountId = $senderKeyPair->getAccountId();
        $firstMiddlemanAccountId = $firstMiddlemanKeyPair->getAccountId();
        $secondMiddlemanAccountId = $secondMiddlemanKeyPair->getAccountId();
        $receiverAccountId = $receiverKeyPair->getAccountId();

        // Fund the issuer account.
        FriendBot::fundTestAccount($issuerAccountId);

        // Load the issuer account so that we have it's current sequence number.
        $issuer = $sdk->requestAccount($issuerAccountId);

        // Fund sender, middleman and receiver accounts from our issuer account.
        // Create the accounts for our example.
        $transaction = (new TransactionBuilder($issuer))
            ->addOperation((new CreateAccountOperationBuilder($senderAccountId, "10"))->build())
            ->addOperation((new CreateAccountOperationBuilder($firstMiddlemanAccountId, "10"))->build())
            ->addOperation((new CreateAccountOperationBuilder($secondMiddlemanAccountId, "10"))->build())
            ->addOperation((new CreateAccountOperationBuilder($receiverAccountId, "10"))->build())
            ->build();

        // Sign the transaction.
        $transaction->sign($issuerKeyPair, Network::testnet());

        // Submit the transaction.
        $sdk->submitTransaction($transaction);

        // Load the data of the accounts so that we can create the trustlines in the next step.
        $sender = $sdk->requestAccount($senderAccountId);
        $firstMiddleman = $sdk->requestAccount($firstMiddlemanAccountId);
        $secondMiddleman = $sdk->requestAccount($secondMiddlemanAccountId);
        $receiver = $sdk->requestAccount($receiverAccountId);

        // Define our custom tokens.
        $iomAsset = new AssetTypeCreditAlphaNum4("IOM", $issuerAccountId);
        $moonAsset = new AssetTypeCreditAlphaNum4("MOON", $issuerAccountId);
        $ecoAsset = new AssetTypeCreditAlphaNum4("ECO", $issuerAccountId);

        // Let the sender trust IOM.
        $ctIOMOp = (new ChangeTrustOperationBuilder($iomAsset, "200999"))->build();

        // Build the transaction.
        $transaction = (new TransactionBuilder($sender))->addOperation($ctIOMOp)->build();

        // Sign the transaction.
        $transaction->sign($senderKeyPair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Let the first middleman trust both IOM and MOON.
        $ctMOONOp = (new ChangeTrustOperationBuilder($moonAsset, "200999"))->build();

        // Build the transaction.
        $transaction = (new TransactionBuilder($firstMiddleman))->addOperation($ctIOMOp)->addOperation($ctMOONOp)->build();

        // Sign the transaction.
        $transaction->sign($firstMiddlemanKeyPair, Network::testnet());

        // Submit the transaction.
        $sdk->submitTransaction($transaction);

        // Let the second middleman trust both MOON and ECO.
        $ctECOOp = (new ChangeTrustOperationBuilder($ecoAsset, "200999"))->build();

        // Build the transaction.
        $transaction = (new TransactionBuilder($secondMiddleman))->addOperation($ctMOONOp)->addOperation($ctECOOp)->build();

        // Sign the transaction.
        $transaction->sign($secondMiddlemanKeyPair, Network::testnet());

        // Submit the transaction.
        $sdk->submitTransaction($transaction);

        // Let the receiver trust ECO.
        $transaction = (new TransactionBuilder($receiver))->addOperation($ctECOOp)->build();

        // Sign.
        $transaction->sign($receiverKeyPair, Network::testnet());

        // Submit the transaction.
        $sdk->submitTransaction($transaction);

        // Now send assets to the accounts from the issuer, so that we can start our case.
        // Send 100 IOM to sender.
        // Send 100 MOON to first middleman.
        // Send 100 ECO to second middleman.
        $transaction = (new TransactionBuilder($issuer))
            ->addOperation((new PaymentOperationBuilder($senderAccountId, $iomAsset, "100"))->build())
            ->addOperation((new PaymentOperationBuilder($firstMiddlemanAccountId, $moonAsset, "100"))->build())
            ->addOperation((new PaymentOperationBuilder($secondMiddlemanAccountId, $ecoAsset, "100"))->build())
            ->build();

        // Sign.
        $transaction->sign($issuerKeyPair, Network::testnet());

        // Submit the transaction.
        $sdk->submitTransaction($transaction);

        // Now let the first middleman offer MOON for IOM: 1 IOM = 2 MOON. Offered Amount: 100 MOON.
        $sellOfferOp = (new ManageSellOfferOperationBuilder($moonAsset, $iomAsset, "100", "0.5", "0"))->build();

        // Build the transaction.
        $transaction = (new TransactionBuilder($firstMiddleman))->addOperation($sellOfferOp)->build();

        // Sign.
        $transaction->sign($firstMiddlemanKeyPair, Network::testnet());

        // Submit the transaction.
        $sdk->submitTransaction($transaction);

        // Now let the second middleman offer ECO for MOON: 1 MOON = 2 ECO. Offered Amount: 100 ECO.
        $sellOfferOp = (new ManageSellOfferOperationBuilder($ecoAsset, $moonAsset, "100", "0.5", "0"))->build();

        // Build the transaction.
        $transaction = (new TransactionBuilder($secondMiddleman))->addOperation($sellOfferOp)->build();

        // Sign.
        $transaction->sign($secondMiddlemanKeyPair, Network::testnet());

        // Submit the transaction.
        $sdk->submitTransaction($transaction);

        // In this example we are going to wait a couple of seconds to be sure that the ledger closed and the offers are available.
        // In your app you should stream for the offers and continue as soon as they are available.
        sleep(5);

        // Everything is prepared now. We can use path payment to send IOM but receive ECO.
        // We will need to provide the path, so lets request/find it first
        $strictSendPathsPage = $sdk->findStrictSendPaths()->forSourceAsset($iomAsset)
            ->forSourceAmount("10")->forDestinationAssets([$ecoAsset])->execute();

        // Here is our payment path.
        $path = $strictSendPathsPage->getPaths()->toArray()[0]->getPath()->toArray();

        // First path payment strict send. Send exactly 10 IOM, receive minimum 38 ECO (it will be 40).
        $strictSend = (new PathPaymentStrictSendOperationBuilder($iomAsset, "10", $receiverAccountId, $ecoAsset, "38"))
            ->setPath($path)->build();

        // Build the transaction.
        $transaction = (new TransactionBuilder($sender))->addOperation($strictSend)->build();

        // Sign.
        $transaction->sign($senderKeyPair, Network::testnet());

        // Submit the transaction.
        $sdk->submitTransaction($transaction);

        // Check if the receiver received the ECOs.
        $receiver = $sdk->requestAccount($receiverAccountId);
        foreach ($receiver->getBalances() as $balance) {
            if ($balance->getAssetType() != Asset::TYPE_NATIVE
                && $balance->getAssetCode() == "ECO") {
                printf(PHP_EOL."Receiver received %s ECO", $balance->getBalance());
                break;
            }
        }

        // And now a path payment strict receive.
        // Find the path.
        // We want the receiver to receive exactly 8 ECO.
        $strictReceivePathsPage = $sdk->findStrictReceivePaths()
            ->forDestinationAsset($ecoAsset)->forDestinationAmount("8")
            ->forSourceAccount($senderAccountId)->execute();

        // Here is our payment path.
        $path = $strictReceivePathsPage->getPaths()->toArray()[0]->getPath()->toArray();

        // The sender sends max 2 IOM.
        $strictReceive = (new PathPaymentStrictReceiveOperationBuilder($iomAsset, "2", $receiverAccountId, $ecoAsset, "8"))
        ->setPath($path)->build();

        // Build the transaction.
        $transaction = (new TransactionBuilder($sender))->addOperation($strictReceive)->build();

        // Sign.
        $transaction->sign($senderKeyPair, Network::testnet());

        // Submit the transaction.
        $sdk->submitTransaction($transaction);

        // Check id the reciver received the ECOs.
        $receiver = $sdk->requestAccount($receiverAccountId);
        foreach ($receiver->getBalances() as $balance) {
            if ($balance->getAssetType() != Asset::TYPE_NATIVE
                && $balance->getAssetCode() == "ECO") {
                printf(PHP_EOL."Receiver has %s ECO", $balance->getBalance());
                break;
            }
        }
        print(PHP_EOL."Success! :)");
        $this->assertTrue(true);
    }

    public function testMergeAccount(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        // Create random key pairs for two accounts.
        $keyPairX = KeyPair::random();
        $keyPairY = KeyPair::random();

        // Account Ids.
        $accountXId = $keyPairX->getAccountId();
        $accountYId = $keyPairY->getAccountId();

        // Create both accounts.
        FriendBot::fundTestAccount($accountXId);
        FriendBot::fundTestAccount($accountYId);

        // Prepare the operation for merging account Y into account X.
        $accMergeOp = (new AccountMergeOperationBuilder($accountXId))->build();

        // Load the data of account Y so that we have it's current sequence number.
        $accountY = $sdk->requestAccount($accountYId);

        // Build the transaction to merge account Y into account X.
        $transaction = (new TransactionBuilder($accountY))->addOperation($accMergeOp)->build();

        // Account Y signs the transaction - R.I.P :)
        $transaction->sign($keyPairY, Network::testnet());

        // Submit the transaction.
        $response = $sdk->submitTransaction($transaction);

        if ($response->isSuccessful()) {
            print(PHP_EOL."successfully merged");
        }

        // Check that account Y has been removed.
        try {
            $accountY = $sdk->requestAccount($accountYId);
            print(PHP_EOL."account still exists: ".$accountYId);
        } catch(HorizonRequestException $e) {
            if($e->getCode() == 404) {
                print(PHP_EOL."success, account not found");
            }
        }

        // Check if accountX received the funds from accountY.
        $accountX = $sdk->requestAccount($accountXId);
        foreach ($accountX->getBalances() as $balance) {
            if ($balance->getAssetType() == Asset::TYPE_NATIVE) {
                printf(PHP_EOL."X has %s XLM", $balance->getBalance());
                break;
            }
        }
        $this->assertTrue(true);
    }

    public function testBumpSequence(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        // Create a random key pair for a new account.
        $accountKeyPair = KeyPair::random();

        // Account Id.
        $accountId = $accountKeyPair->getAccountId();

        // Create account.
        FriendBot::fundTestAccount($accountId);

        // Load account data to get the current sequence number.
        $account = $sdk->requestAccount($accountId);

        // Remember current sequence number.
        $startSequence = $account->getSequenceNumber();

        // Prepare the bump sequence operation to bump the sequence number to current + 10.
        $ten = new BigInteger(10);
        $bumpSequenceOp = (new BumpSequenceOperationBuilder($startSequence->add($ten)))->build();

        // Prepare the transaction.
        $transaction = (new TransactionBuilder($account))->addOperation($bumpSequenceOp)->build();

        // Sign the transaction.
        $transaction->sign($accountKeyPair, Network::testnet());

        // Submit the transaction.
        $response = $sdk->submitTransaction($transaction);

        // Load the account again.
        $account = $sdk->requestAccount($accountId);

        // Check that the new sequence number has correctly been bumped.
        if ($startSequence->add($ten) == $account->getSequenceNumber()) {
            print("success");
        } else {
            print("failed");
        }
        $this->assertTrue(true);
    }
}