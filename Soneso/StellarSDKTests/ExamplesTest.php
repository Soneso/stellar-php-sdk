<?php

namespace Soneso\StellarSDKTests;

use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\AccountMergeOperationBuilder;
use Soneso\StellarSDK\AllowTrustOperationBuilder;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum;
use Soneso\StellarSDK\AssetTypeCreditAlphanum12;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\BumpSequenceOperationBuilder;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\CreatePassiveSellOfferOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\FeeBumpTransactionBuilder;
use Soneso\StellarSDK\ManageBuyOfferOperationBuilder;
use Soneso\StellarSDK\ManageDataOperationBuilder;
use Soneso\StellarSDK\ManageSellOfferOperationBuilder;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PathPaymentStrictReceiveOperationBuilder;
use Soneso\StellarSDK\PathPaymentStrictSendOperationBuilder;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Price;
use Soneso\StellarSDK\Responses\Operations\OperationResponse;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;
use Soneso\StellarSDK\SEP\Derivation\WordList;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
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

    public function testManageData(): void
    {
        $sdk = StellarSDK::getTestNetInstance();

        // Create a random keypair for our new account.
        $keyPair = KeyPair::random();

        // Account Id.
        $accountId = $keyPair->getAccountId();

        // Create account.
        FriendBot::fundTestAccount($accountId);

        // Load account data including it's current sequence number.
        $account = $sdk->requestAccount($accountId);

        // Define a key value pair to save as a data entry.
        $key = "soneso";
        $value = "is cool!";

        // Prepare the manage data operation.
        $manageDataOperation = (new ManageDataOperationBuilder($key, $value))->build();

        // Create the transaction.
        $transaction = (new TransactionBuilder($account))->addOperation($manageDataOperation)->build();

        // Sign the transaction.
        $transaction->sign($keyPair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        $account = $sdk->requestAccount($accountId);

        // Compare.
        if ($account->getData()->get($key) === $value) {
            print("okay");
        } else {
            print("failed");
        }

        // In the next step we prepare the operation to delete the entry by passing null as a value.
        $manageDataOperation = (new ManageDataOperationBuilder($key, null))->build();

        // Prepare the transaction.
        $transaction = (new TransactionBuilder($account))->addOperation($manageDataOperation)->build();

        // Sign the transaction.
        $transaction->sign($keyPair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Reload account.
        $account = $sdk->requestAccount($accountId);

        if (!in_array($key, $account->getData()->getKeys())) {
            print(PHP_EOL."success");
        }

        $this->assertTrue(true);
    }

    public function testManageBuyOffer(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        // Prepare two random keypairs, we will need the later for signing.
        $issuerKeypair = KeyPair::random();
        $buyerKeypair = KeyPair::random();

        // Account Ids.
        $issuerAccountId = $issuerKeypair->getAccountId();
        $buyerAccountId = $buyerKeypair->getAccountId();

        // Create the buyer account.
        FriendBot::fundTestAccount($buyerAccountId);

        // Create the issuer account.
        $buyerAccount = $sdk->requestAccount($buyerAccountId);
        $caOp = (new CreateAccountOperationBuilder($issuerAccountId, "10"))->build();
        $transaction = (new TransactionBuilder($buyerAccount))->addOperation($caOp)->build();

        // Sign the transaction.
        $transaction->sign($buyerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Define an asset.
        $astroDollar = new AssetTypeCreditAlphaNum12("ASTRO", $issuerAccountId);

        // Create a trustline for the buyer account.
        $ctOp = (new ChangeTrustOperationBuilder($astroDollar, "10000"))->build();
        $transaction = (new TransactionBuilder($buyerAccount))->addOperation($ctOp)->build();

        // Sign the transaction.
        $transaction->sign($buyerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Create the offer.
        // I want to pay max. 50 XLM for 100 ASTRO.
        $amountBuying = "100"; // Want to buy 100 ASTRO
        $price = "0.5"; // Price of 1 unit of buying in terms of selling

        // Create the manage buy offer operation. Buying: 100 ASTRO for 50 XLM (price = 0.5 => Price of 1 unit of buying in terms of selling)
        $ms = (new ManageBuyOfferOperationBuilder(Asset::native(), $astroDollar, $amountBuying, $price))->build();

        // Create the transaction.
        $transaction = (new TransactionBuilder($buyerAccount))->addOperation($ms)->build();

        // Sign the transaction.
        $transaction->sign($buyerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Now let's load the offers of our account to see if the offer has been created.
        $offersPage = $sdk->offers()->forAccount($buyerAccountId)->execute();
        $offer = $offersPage->getOffers()->toArray()[0];

        $buyingAssetCode = $offer->getBuying() instanceof AssetTypeCreditAlphaNum ? $offer->getBuying()->getCode() : "XLM";
        $sellingAssetCode = $offer->getSelling() instanceof AssetTypeCreditAlphaNum ? $offer->getSelling()->getCode() : "XLM";

        printf(PHP_EOL."offerId: %s - buying: %s - selling: %s %s price: %s", $offer->getOfferId(), $buyingAssetCode, $offer->getAmount(), $sellingAssetCode, $offer->getPrice());

        // offerId: 16245277 - buying: ASTRO - selling: 50.0000000 XLM price: 2.0000000
        // As you can see, the price is stored here as "Price of 1 unit of selling in terms of buying".

        // Now lets modify our offer.
        $offerId = $offer->getOfferId();

        // New data.
        $amountBuying = "150";
        $price = "0.3";

        // Build the manage buy offer operation
        $ms = (new ManageBuyOfferOperationBuilder(Asset::native(), $astroDollar, $amountBuying, $price))->setOfferId($offerId)->build();

        // Build the transaction.
        $transaction = (new TransactionBuilder($buyerAccount))->addOperation($ms)->build();

        // Sign the transaction.
        $transaction->sign($buyerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Load the offer from stellar.
        $offersPage = $sdk->offers()->forAccount($buyerAccountId)->execute();
        $offer = $offersPage->getOffers()->toArray()[0];

        $buyingAssetCode = $offer->getBuying() instanceof AssetTypeCreditAlphaNum ? $offer->getBuying()->getCode() : "XLM";
        $sellingAssetCode = $offer->getSelling() instanceof AssetTypeCreditAlphaNum ? $offer->getSelling()->getCode() : "XLM";

        printf(PHP_EOL."offerId: %s - buying: %s - selling: %s %s price: %s", $offer->getOfferId(), $buyingAssetCode, $offer->getAmount(), $sellingAssetCode, $offer->getPrice());
        // offerId: 16245277 - buying: ASTRO - selling: 45.0000000 XLM price: 3.3333333

        // And now let's delete our offer
        // To delete, we need to set the amount to 0.
        $amountBuying = "0";

        // Create the operation
        $ms = (new ManageBuyOfferOperationBuilder(Asset::native(), $astroDollar, $amountBuying, $price))->setOfferId($offerId)->build();

        // Build the transaction.
        $transaction = (new TransactionBuilder($buyerAccount))->addOperation($ms)->build();

        // Sign the transaction.
        $transaction->sign($buyerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // check if the offer has been deleted.
        $offersPage = $sdk->offers()->forAccount($buyerAccountId)->execute();
        if($offersPage->getOffers()->count() == 0) {
            print(PHP_EOL."success");
        }
        $this->assertTrue(true);
    }

    public function testManageSellOffer(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        // Prepare two random keypairs, we will need the later for signing.
        $issuerKeypair = KeyPair::random();
        $sellerKeypair = KeyPair::random();

        // Account Ids.
        $issuerAccountId = $issuerKeypair->getAccountId();
        $sellerAccountId = $sellerKeypair->getAccountId();

        // Create the buyer account.
        FriendBot::fundTestAccount($sellerAccountId);

        // Create the issuer account.
        $sellerAccount = $sdk->requestAccount($sellerAccountId);
        $caOp = (new CreateAccountOperationBuilder($issuerAccountId, "10"))->build();
        $transaction = (new TransactionBuilder($sellerAccount))->addOperation($caOp)->build();

        // Sign the transaction.
        $transaction->sign($sellerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Define an asset.
        $moonDollar = new AssetTypeCreditAlphaNum4("MOON", $issuerAccountId);

        // Create a trustline for the seller account.
        $ctOp = (new ChangeTrustOperationBuilder($moonDollar, "10000"))->build();
        $transaction = (new TransactionBuilder($sellerAccount))->addOperation($ctOp)->build();

        // Sign the transaction.
        $transaction->sign($sellerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Send 2000 MOON asset to the seller account.
        $paymentOp = (new PaymentOperationBuilder($sellerAccountId, $moonDollar, "2000"))->build();

        $issuerAccount = $sdk->requestAccount($issuerAccountId);
        $transaction = (new TransactionBuilder($issuerAccount))->addOperation($paymentOp)->build();

        // Sign the transaction.
        $transaction->sign($issuerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Create the offer.
        // I want to sell 100 MOON for 50 XLM.
        $amountSelling = "100"; // Want to buy 100 ASTRO
        $price = "0.5"; // Price of 1 unit of selling in terms of buying

        // Create the manage sell offer operation. Selling: 100 MOON for 50 XLM (price = 0.5 => Price of 1 unit of selling in terms of buying.)
        $ms = (new ManageSellOfferOperationBuilder($moonDollar, Asset::native(), $amountSelling, $price))->build();

        // Create the transaction.
        $transaction = (new TransactionBuilder($sellerAccount))->addOperation($ms)->build();

        // Sign the transaction.
        $transaction->sign($sellerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Now let's load the offers of our account to see if the offer has been created.
        $offersPage = $sdk->offers()->forAccount($sellerAccountId)->execute();
        $offer = $offersPage->getOffers()->toArray()[0];

        $buyingAssetCode = $offer->getBuying() instanceof AssetTypeCreditAlphaNum ? $offer->getBuying()->getCode() : "XLM";
        $sellingAssetCode = $offer->getSelling() instanceof AssetTypeCreditAlphaNum ? $offer->getSelling()->getCode() : "XLM";

        printf(PHP_EOL."offerId: %s - selling: %s %s buying: %s - price: %s", $offer->getOfferId(), $offer->getAmount(), $sellingAssetCode, $buyingAssetCode, $offer->getPrice());

        // offerId: 16252986 - selling: 100.0000000 MOON buying: XLM price: 0.5000000
        // Price of 1 unit of selling in terms of buying.

        // Now lets modify our offer.
        $offerId = $offer->getOfferId();

        // New data.
        $amountSelling = "150";
        $price = "0.3";

        // Build the manage sell offer operation
        $ms = (new ManageSellOfferOperationBuilder($moonDollar, Asset::native(), $amountSelling, $price))->setOfferId($offerId)->build();

        // Build the transaction.
        $transaction = (new TransactionBuilder($sellerAccount))->addOperation($ms)->build();

        // Sign the transaction.
        $transaction->sign($sellerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Load the offer from stellar.
        $offersPage = $sdk->offers()->forAccount($sellerAccountId)->execute();
        $offer = $offersPage->getOffers()->toArray()[0];

        $buyingAssetCode = $offer->getBuying() instanceof AssetTypeCreditAlphaNum ? $offer->getBuying()->getCode() : "XLM";
        $sellingAssetCode = $offer->getSelling() instanceof AssetTypeCreditAlphaNum ? $offer->getSelling()->getCode() : "XLM";

        printf(PHP_EOL."offerId: %s - selling: %s %s buying: %s - price: %s", $offer->getOfferId(), $offer->getAmount(), $sellingAssetCode, $buyingAssetCode, $offer->getPrice());
        // offerId: 16252986 - selling: 150.0000000 MOON buying: XLM price: 0.3000000

        // And now let's delete our offer
        // To delete, we need to set the amount to 0.
        $amountSelling = "0";

        // Build the manage sell offer operation
        $ms = (new ManageSellOfferOperationBuilder($moonDollar, Asset::native(), $amountSelling, $price))->setOfferId($offerId)->build();

        // Build the transaction.
        $transaction = (new TransactionBuilder($sellerAccount))->addOperation($ms)->build();

        // Sign the transaction.
        $transaction->sign($sellerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // check if the offer has been deleted.
        $offersPage = $sdk->offers()->forAccount($sellerAccountId)->execute();
        if ($offersPage->getOffers()->count() == 0) {
            print(PHP_EOL."success");
        }
        $this->assertTrue(true);
    }

    public function testCreatePassiveSellOffer(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        // Prepare two random keypairs, we will need the later for signing.
        $issuerKeypair = KeyPair::random();
        $sellerKeypair = KeyPair::random();

        // Account Ids.
        $issuerAccountId = $issuerKeypair->getAccountId();
        $sellerAccountId = $sellerKeypair->getAccountId();

        // Create the buyer account.
        FriendBot::fundTestAccount($sellerAccountId);

        // Create the issuer account.
        $sellerAccount = $sdk->requestAccount($sellerAccountId);
        $caOp = (new CreateAccountOperationBuilder($issuerAccountId, "10"))->build();
        $transaction = (new TransactionBuilder($sellerAccount))->addOperation($caOp)->build();

        // Sign the transaction.
        $transaction->sign($sellerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Define our custom asset.
        $marsDollar = new AssetTypeCreditAlphaNum4("MARS", $issuerAccountId);

        // Let the seller account trust our issuer and custom asset.
        $ctOp = (new ChangeTrustOperationBuilder($marsDollar, "10000"))->build();
        $transaction = (new TransactionBuilder($sellerAccount))->addOperation($ctOp)->build();

        // Sign the transaction.
        $transaction->sign($sellerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Send a couple of custom asset MARS funds from the issuer to the seller account so that the seller can offer them
        $paymentOp = (new PaymentOperationBuilder($sellerAccountId, $marsDollar, "2000"))->build();

        $issuerAccount = $sdk->requestAccount($issuerAccountId);
        $transaction = (new TransactionBuilder($issuerAccount))->addOperation($paymentOp)->build();

        // Sign the transaction.
        $transaction->sign($issuerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Create the offer.
        // I want to sell 100 MARS for 50 XLM.
        $amountSelling = "100";
        $price = "0.5";

        // Create the passive sell offer operation. Selling: 100 MARS for 50 XLM (price = 0.5 => Price of 1 unit of selling in terms of buying.)
        $ms = (new ManageSellOfferOperationBuilder($marsDollar, Asset::native(), $amountSelling, $price))->build();

        // Create the transaction.
        $transaction = (new TransactionBuilder($sellerAccount))->addOperation($ms)->build();

        // Sign the transaction.
        $transaction->sign($sellerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Now let's load the offers of our account to see if the offer has been created.
        $offersPage = $sdk->offers()->forAccount($sellerAccountId)->execute();
        $offer = $offersPage->getOffers()->toArray()[0];

        $buyingAssetCode = $offer->getBuying() instanceof AssetTypeCreditAlphaNum ? $offer->getBuying()->getCode() : "XLM";
        $sellingAssetCode = $offer->getSelling() instanceof AssetTypeCreditAlphaNum ? $offer->getSelling()->getCode() : "XLM";

        printf(PHP_EOL."offerId: %s - selling: %s %s buying: %s - price: %s", $offer->getOfferId(), $offer->getAmount(), $sellingAssetCode, $buyingAssetCode, $offer->getPrice());

        // offerId: 16260716 - selling: 100.0000000 MARS buying: XLM price: 0.5000000
        // Price of 1 unit of selling in terms of buying.

        // Now let's modify our offer.
        $offerId = $offer->getOfferId();

        // New data.
        $amountSelling = "150";
        $price = "0.3";

        // Build the manage sell offer operation
        $ms = (new ManageSellOfferOperationBuilder($marsDollar, Asset::native(), $amountSelling, $price))->setOfferId($offerId)->build();

        // Build the transaction.
        $transaction = (new TransactionBuilder($sellerAccount))->addOperation($ms)->build();

        // Sign the transaction.
        $transaction->sign($sellerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Load the offer from stellar.
        $offersPage = $sdk->offers()->forAccount($sellerAccountId)->execute();
        $offer = $offersPage->getOffers()->toArray()[0];

        $buyingAssetCode = $offer->getBuying() instanceof AssetTypeCreditAlphaNum ? $offer->getBuying()->getCode() : "XLM";
        $sellingAssetCode = $offer->getSelling() instanceof AssetTypeCreditAlphaNum ? $offer->getSelling()->getCode() : "XLM";

        printf(PHP_EOL."offerId: %s - selling: %s %s buying: %s - price: %s", $offer->getOfferId(), $offer->getAmount(), $sellingAssetCode, $buyingAssetCode, $offer->getPrice());
        // offerId: 16252986 - selling: 150.0000000 MARS buying: XLM price: 0.3000000

        // And now let's delete our offer
        // To delete, we need to set the amount to 0.
        $amountSelling = "0";

        // Build the manage sell offer operation
        $ms = (new ManageSellOfferOperationBuilder($marsDollar, Asset::native(), $amountSelling, $price))->setOfferId($offerId)->build();

        // Build the transaction.
        $transaction = (new TransactionBuilder($sellerAccount))->addOperation($ms)->build();

        // Sign the transaction.
        $transaction->sign($sellerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // check if the offer has been deleted.
        $offersPage = $sdk->offers()->forAccount($sellerAccountId)->execute();
        if ($offersPage->getOffers()->count() == 0) {
            print(PHP_EOL."success");
        }
        $this->assertTrue(true);
    }

    public function testChangeTrust(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        // Create two random key pairs, we will need them later for signing.
        $issuerKeypair = KeyPair::random();
        $trustorKeypair = KeyPair::random();

        // Account Ids.
        $issuerAccountId = $issuerKeypair->getAccountId();
        $trustorAccountId = $trustorKeypair->getAccountId();

        // Create trustor account.
        FriendBot::fundTestAccount($trustorAccountId);

        // Load the trustor account so that we can later create the trustline.
        $trustorAccount =  $sdk->requestAccount($trustorAccountId);

        // Create the issuer account.
        $cao = (new CreateAccountOperationBuilder($issuerAccountId, "10"))->build();
        $transaction = (new TransactionBuilder($trustorAccount))->addOperation($cao)->build();

        // Sign the transaction.
        $transaction->sign($trustorKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Create our custom asset.
        $assetCode = "ASTRO";
        $astroDollar = new AssetTypeCreditAlphaNum12($assetCode, $issuerAccountId);

        // Create the trustline. Limit: 10000 ASTRO.
        $limit = "10000";

        // Build the operation.
        $cto = (new ChangeTrustOperationBuilder($astroDollar, $limit))->build();

        // Build the transaction.
        $transaction = (new TransactionBuilder($trustorAccount))->addOperation($cto)->build();

        // Sign the transaction.
        $transaction->sign($trustorKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Load the trustor account again to see if the trustline has been created.
        $trustorAccount =  $sdk->requestAccount($trustorAccountId);

        // Check if the trustline exists.
        foreach ($trustorAccount->getBalances() as $balance) {
            if ($balance->getAssetCode() == $assetCode) {
                print(PHP_EOL."Trustline for ".$assetCode." found. Limit: ".$balance->getLimit());
                // Trustline for ASTRO found. Limit: 10000.0
                break;
            }
        }

        // Now, let's modify the trustline, change the trust limit to 40000.
        $limit = "40000";

        // Build the operation.
        $cto = (new ChangeTrustOperationBuilder($astroDollar, $limit))->build();

        // Build the transaction.
        $transaction = (new TransactionBuilder($trustorAccount))->addOperation($cto)->build();

        // Sign the transaction.
        $transaction->sign($trustorKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Load the trustor account again to see if the trustline has been created.
        $trustorAccount =  $sdk->requestAccount($trustorAccountId);

        // Check if the trustline exists.
        foreach ($trustorAccount->getBalances() as $balance) {
            if ($balance->getAssetCode() == $assetCode) {
                print(PHP_EOL."Trustline for ".$assetCode." found. Limit: ".$balance->getLimit());
                // Trustline for ASTRO found. Limit: 40000.0
                break;
            }
        }

        // And now let's delete the trustline.
        // To delete it, we must set the trust limit to zero.
        $limit = "0";

        // Build the operation.
        $cto = (new ChangeTrustOperationBuilder($astroDollar, $limit))->build();

        // Build the transaction.
        $transaction = (new TransactionBuilder($trustorAccount))->addOperation($cto)->build();

        // Sign the transaction.
        $transaction->sign($trustorKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Load the trustor account again to see if the trustline has been created.
        $trustorAccount =  $sdk->requestAccount($trustorAccountId);

        $found = false;
        // Check if the trustline exists.
        foreach ($trustorAccount->getBalances() as $balance) {
            if ($balance->getAssetCode() == $assetCode) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            print(PHP_EOL."success, trustline deleted");
        }
        $this->assertTrue(true);
    }

    public function testAllowTrust(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        // Create two random key pairs, we will need them later for signing.
        $issuerKeypair = KeyPair::random();
        $trustorKeypair = KeyPair::random();

        // Account Ids.
        $issuerAccountId = $issuerKeypair->getAccountId();
        $trustorAccountId = $trustorKeypair->getAccountId();

        // Create trustor account.
        FriendBot::fundTestAccount($trustorAccountId);

        // Load the trustor account so that we can later create the trustline.
        $trustorAccount =  $sdk->requestAccount($trustorAccountId);

        // Create the issuer account.
        $cao = (new CreateAccountOperationBuilder($issuerAccountId, "10"))->build();
        $transaction = (new TransactionBuilder($trustorAccount))->addOperation($cao)->build();

        // Sign the transaction.
        $transaction->sign($trustorKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Load the issuer account.
        $issuerAccount = $sdk->requestAccount($issuerAccountId);

        // Set up the flags on the isser account.
        $sopb = new SetOptionsOperationBuilder();
        $sopb->setSetFlags(3); // Auth required, auth revocable

        // Build the transaction.
        $transaction = (new TransactionBuilder($issuerAccount))->addOperation($sopb->build())->build();

        // Sign the transaction.
        $transaction->sign($issuerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Reload the issuer account to check the flags.
        $issuerAccount = $sdk->requestAccount($issuerAccountId);
        if ($issuerAccount->getFlags()->isAuthRequired()
            && $issuerAccount->getFlags()->isAuthRevocable()
            && !$issuerAccount->getFlags()->isAuthImmutable()) {
            print(PHP_EOL."issuer account flags correctly set");
        }

        // Define our custom asset.
        $assetCode = "ASTRO";
        $astroDollar = new AssetTypeCreditAlphaNum12($assetCode, $issuerAccountId);

        // Build the trustline.
        $limit = "10000";

        // Build the operation.
        $cto = (new ChangeTrustOperationBuilder($astroDollar, $limit))->build();

        // Build the transaction.
        $transaction = (new TransactionBuilder($trustorAccount))->addOperation($cto)->build();

        // Sign the transaction.
        $transaction->sign($trustorKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Reload the trustor account to see if the trustline has been created.
        $trustorAccount = $sdk->requestAccount($trustorAccountId);
        foreach ($trustorAccount->getBalances() as $balance) {
            if ($balance->getAssetCode() == $assetCode) {
                print(PHP_EOL."trustline awailable");
                break;
            }
        }

        // Now lets try to send some custom asset funds to the trustor account.
        // This should not work, because the issuer must authorize the trustline first.
        $po = (new PaymentOperationBuilder($trustorAccountId, $astroDollar, "100"))->build();
        $transaction = (new TransactionBuilder($issuerAccount))->addOperation($po)->build();

        // Sign the transaction.
        $transaction->sign($issuerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        try {
            $response = $sdk->submitTransaction($transaction);
        } catch (HorizonRequestException $e) {
            print(PHP_EOL."trustline is not authorized.");
        }

        // Now let's authorize the trustline.
        // Build the allow trust operation. Set the authorized flag to 1.
        $aop = (new AllowTrustOperationBuilder($trustorAccountId, $assetCode, 1, 0))->build(); // authorize
        $transaction = (new TransactionBuilder($issuerAccount))->addOperation($aop)->build();

        // Sign the transaction.
        $transaction->sign($issuerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $response = $sdk->submitTransaction($transaction);

        // Try again to send the payment. Should work now.
        $po = (new PaymentOperationBuilder($trustorAccountId, $astroDollar, "100"))->build();
        $transaction = (new TransactionBuilder($issuerAccount))->addOperation($po)->build();

        // Sign the transaction.
        $transaction->sign($issuerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $response = $sdk->submitTransaction($transaction);

        if ($response->isSuccessful()) { // authorized.
            print(PHP_EOL."success - trustline is now authorized.");
        }

        // Now create an offer, to see if it will be deleted after we will remove the authorized flag.
        $amountSelling = "100";
        $price = "0.5";
        $cpso = (new CreatePassiveSellOfferOperationBuilder($astroDollar, Asset::native(), $amountSelling, Price::fromString($price)))->build();
        $transaction = (new TransactionBuilder($trustorAccount))->addOperation($cpso)->build();

        // Sign the transaction.
        $transaction->sign($trustorKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $response = $sdk->submitTransaction($transaction);

        // Check if the offer has been added.
        $offersPage = $sdk->offers()->forAccount($trustorAccountId)->execute();
        $offer = $offersPage->getOffers()->toArray()[0];

        if ($offer->getBuying() == Asset::native()
            && $offer->getSelling() == $astroDollar) {
            print(PHP_EOL."offer found");
        }

        // Now lets remove the authorization. To do so, we set the authorized flag to 0.
        // This should also delete the offer.
        $aop = (new AllowTrustOperationBuilder($trustorAccountId, $assetCode, 0, 0))->build(); // not authorized
        $transaction = (new TransactionBuilder($issuerAccount))->addOperation($aop)->build();

        // Sign the transaction.
        $transaction->sign($issuerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Check if the offer has been deleted.
        $offersPage = $sdk->offers()->forAccount($trustorAccountId)->execute();

        if ($offersPage->getOffers()->count() == 0) {
            print(PHP_EOL."success, offer has been deleted");
        }

        // Now, let's authorize the trustline again and then authorize it only to maintain liabilities.
        $aop = (new AllowTrustOperationBuilder($trustorAccountId, $assetCode, 1, 0))->build(); // authorize
        $transaction = (new TransactionBuilder($issuerAccount))->addOperation($aop)->build();

        // Sign the transaction.
        $transaction->sign($issuerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Create the offer again.
        $cpso = (new CreatePassiveSellOfferOperationBuilder($astroDollar, Asset::native(), $amountSelling, Price::fromString($price)))->build();
        $transaction = (new TransactionBuilder($trustorAccount))->addOperation($cpso)->build();

        // Sign the transaction.
        $transaction->sign($trustorKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Check that the offer has been created.
        $offersPage = $sdk->offers()->forAccount($trustorAccountId)->execute();

        if ($offersPage->getOffers()->count() == 1) {
            print(PHP_EOL."offer has been created");
        }

        // Now let's deautorize the trustline but allow the trustor to maintain his offer.
        // For this, we set the authorized flag to 2.
        $aop = (new AllowTrustOperationBuilder($trustorAccountId, $assetCode, 0, 1))->build(); // authorized to maintain liabilities.
        $transaction = (new TransactionBuilder($issuerAccount))->addOperation($aop)->build();

        // Sign the transaction.
        $transaction->sign($issuerKeypair, Network::testnet());

        // Submit the transaction to stellar.
        $sdk->submitTransaction($transaction);

        // Load the offers to see if our offer is still there.
        // Check that the offer has been created.
        $offersPage = $sdk->offers()->forAccount($trustorAccountId)->execute();

        if ($offersPage->getOffers()->count() == 1) {
            print(PHP_EOL."offer exists");
        }

        // Next, let's try to send some ASTRO to the trustor account.
        // This should not work, since the trustline has been deauthorized before.
        $po = (new PaymentOperationBuilder($trustorAccountId, $astroDollar, "100"))->build();
        $transaction = (new TransactionBuilder($issuerAccount))->addOperation($po)->build();

        // Sign the transaction.
        $transaction->sign($issuerKeypair, Network::testnet());

        try {
            // Submit the transaction to stellar.
            $sdk->submitTransaction($transaction);
        } catch (HorizonRequestException $e) {
            print(PHP_EOL."payment correctly blocked.");
        }

        $this->assertTrue(true);
    }

    public function testFeeBump(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        // Create 3 random Keypairs, we will need them later for signing.
        $sourceKeyPair = KeyPair::random();
        $destinationKeyPair = KeyPair::random();
        $payerKeyPair = KeyPair::random();

        // Account Ids.
        $payerId = $payerKeyPair->getAccountId();
        $sourceId = $sourceKeyPair->getAccountId();
        $destinationId = $destinationKeyPair->getAccountId();

        // Create the source and the payer account.
        FriendBot::fundTestAccount($sourceId);
        FriendBot::fundTestAccount($payerId);

        // Load the current data of the source account so that we can create the inner transaction.
        $sourceAccount = $sdk->requestAccount($sourceId);

        // Build the inner transaction which will create the destination account by using the source account.
        $innerTx = (new TransactionBuilder($sourceAccount))->addOperation(
            (new CreateAccountOperationBuilder($destinationId, "10"))->build())->build();

        // Sign the inner transaction with the source account key pair.
        $innerTx->sign($sourceKeyPair, Network::testnet());

        // Build the fee bump transaction to let the payer account pay the fee for the inner transaction.
        // The base fee for the fee bump transaction must be higher than the fee of the inner transaction.
        $feeBump = (new FeeBumpTransactionBuilder($innerTx))->setBaseFee(200)->setFeeAccount($payerId)->build();

        // Sign the fee bump transaction with the payer keypair
        $feeBump->sign($payerKeyPair, Network::testnet());

        // Submit the fee bump transaction containing the inner transaction.
        $response = $sdk->submitTransaction($feeBump);

        // Let's check if the destination account has been created and received the funds.
        $destination = $sdk->requestAccount($destinationId);

        foreach ($destination->getBalances() as $balance) {
            if ($balance->getAssetType() == Asset::TYPE_NATIVE) {
                if (floatval($balance->getBalance()) > 9) {
                    print("Success :)");
                }
            }
        }

        // You can load the transaction data with sdk.transactions
        $transaction = $sdk->requestTransaction($response->getHash());

        // Same for the inner transaction.
        $transaction = $sdk->requestTransaction($transaction->getInnerTransactionResponse()->getHash());

        $this->assertTrue(true);
    }

    public function testMuxedAccountPayment(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        // Create two random key pairs, we will need them later for signing.
        $senderKeyPair = KeyPair::random();
        $receiverKeyPair = KeyPair::random();

        // AccountIds
        $accountCId = $receiverKeyPair->getAccountId();
        $senderAccountId = $senderKeyPair->getAccountId();

        // Create the sender account.
        FriendBot::fundTestAccount($senderAccountId);

        // Load the current account data of the sender account.
        $accountA = $sdk->requestAccount($senderAccountId);

        // Create the receiver account.
        $transaction = (new TransactionBuilder($accountA))
            ->addOperation((new CreateAccountOperationBuilder($accountCId, "10"))->build())
            ->build();

        // Sign.
        $transaction->sign($senderKeyPair, Network::testnet());

        // Submit.
        $sdk->submitTransaction($transaction);

        // Now let's create the mxued accounts to be used in the payment transaction.
        $muxedDestinationAccount = new MuxedAccount($accountCId, 8298298319);
        $muxedSourceAccount = new MuxedAccount($senderAccountId, 2442424242);

        // Build the payment operation.
        // We use the muxed account objects for destination and for source here.
        // This is not needed, you can also use only a muxed source account or muxed destination account.
        $paymentOperation = PaymentOperationBuilder::forMuxedDestinationAccount($muxedDestinationAccount, Asset::native(), "100")
            ->setMuxedSourceAccount($muxedSourceAccount)->build();

        // Build the transaction.
        // If we want to use a Med25519 muxed account with id as a source of the transaction, we can just set the id in our account object.
        $accountA->setMuxedAccountMed25519Id(44498494844);
        $transaction = (new TransactionBuilder($accountA))->addOperation($paymentOperation)->build();

        // Sign.
        $transaction->sign($senderKeyPair, Network::testnet());

        // Submit.
        $response = $sdk->submitTransaction($transaction);

        // Have a look to the transaction and the contents of the envelope in Stellar Laboratory
        // https://laboratory.stellar.org/#explorer?resource=transactions&endpoint=single&network=test
        print($response->getHash());

        $this->assertTrue(true);
    }

    public function testStreamPayments(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $accountId = "GCDBA6GFGEHAMVAMRL6R2733EXUENJ35EMYNA2LE7WWJPVANORVC4UNA";

        $sdk->payments()->forAccount($accountId)->cursor("now")->stream(function(OperationResponse $response) {
            if ($response instanceof PaymentOperationResponse) {
                switch ($response->getAsset()->getType()) {
                    case Asset::TYPE_NATIVE:
                        printf("Payment of %s XLM from %s received.", $response->getAmount(), $response->getSourceAccount());
                        break;
                    default:
                        printf("Payment of %s %s from %s received.", $response->getAmount(),  $response->getAsset()->getCode(), $response->getSourceAccount());
                }
                if (floatval($response->getAmount()) > 0.5) {
                    exit;
                }
            }
        });
    }

    public function testSEP005(): void
    {
        $mnemonic =  Mnemonic::generate12WordsMnemonic();
        print implode(" ", $mnemonic->words) . PHP_EOL;
        // bind struggle sausage repair machine fee setup finish transfer stamp benefit economy

        $mnemonic =  Mnemonic::generate24WordsMnemonic();
        print implode(" ", $mnemonic->words) . PHP_EOL;
        // cabbage verb depart erase cable eye crowd approve tower umbrella violin tube island tortoise suspect resemble harbor twelve romance away rug current robust practice

        $frenchMnemonic = Mnemonic::generate12WordsMnemonic(WordList::LANGUAGE_FRENCH);
        print implode(" ", $frenchMnemonic->words) . PHP_EOL;
        // traction maniable punaise flasque digital maussade usuel joueur volcan vaccin tasse concert

        $koreanMnemonic =  Mnemonic::generate24WordsMnemonic(WordList::LANGUAGE_KOREAN);
        print implode(" ", $koreanMnemonic->words) . PHP_EOL;
        // 배꼽 복숭아 오십 방바닥 변호사 교육 평가 휴일 고무신 여론 복사 부근 반응 페인트 예감 악수 하순 양주 줄거리 용기 온종일 의학 핑계 학급


        $mnemonic = Mnemonic::mnemonicFromWords("shell green recycle learn purchase able oxygen right echo claim hill again hidden evidence nice decade panic enemy cake version say furnace garment glue");

        $keyPair0 = KeyPair::fromMnemonic($mnemonic, 0);
        print($keyPair0->getAccountId() . " : " . $keyPair0->getSecretSeed() . PHP_EOL);
        // GCVSEBHB6CTMEHUHIUY4DDFMWQ7PJTHFZGOK2JUD5EG2ARNVS6S22E3K : SATLGMF3SP2V47SJLBFVKZZJQARDOBDQ7DNSSPUV7NLQNPN3QB7M74XH

        $keyPair1 = KeyPair::fromMnemonic($mnemonic, 1);
        print($keyPair1->getAccountId() . " : " . $keyPair1->getSecretSeed() . PHP_EOL);
        // GBPHPX7SZKYEDV5CVOA5JOJE2RHJJDCJMRWMV4KBOIE5VSDJ6VAESR2W : SCAYXPIDEUVDGDTKF4NGVMN7HCZOTZJ43E62EEYKVUYXEE7HMU4DFQA6


        $mnemonic = Mnemonic::mnemonicFromWords("절차 튀김 건강 평가 테스트 민족 몹시 어른 주민 형제 발레 만점 산길 물고기 방면 여학생 결국 수명 애정 정치 관심 상자 축하 고무신",
            WordList::LANGUAGE_KOREAN);

        $keyPair0 = KeyPair::fromMnemonic($mnemonic, 0);
        print($keyPair0->getAccountId() . " : " . $keyPair0->getSecretSeed() . PHP_EOL);
        // GBEAH7ADD5NRYA5YGXDMSWB7PK7J44DYG5I7SVL2FYHCPH5ZH4EJC3YP : SAINP4ANECVGSF5SBNWZIQDX3XTGFLSTCWVHJN4BE5AFY42DOCPS6MEW

        $keyPair1 = KeyPair::fromMnemonic($mnemonic, 1);
        print($keyPair1->getAccountId() . " : " . $keyPair1->getSecretSeed() . PHP_EOL);
        // GCCSXBOX7Y54MT74FGBTL3OI6IOPTB7LSCSLZXKMHG4X56DYN2DPKUTF : SAB26ECJ3TATPR3MHA75IL4KPRXAQWMCGRYKIK3DWXW7Y53DOPVA2YZP


        $mnemonic = Mnemonic::mnemonicFromWords("cable spray genius state float twenty onion head street palace net private method loan turn phrase state blanket interest dry amazing dress blast tube");
        $passphrase = "p4ssphr4se";

        $keyPair0 = KeyPair::fromMnemonic($mnemonic, 0, $passphrase);
        print($keyPair0->getAccountId() . " : " . $keyPair0->getSecretSeed() . PHP_EOL);
        // GDAHPZ2NSYIIHZXM56Y36SBVTV5QKFIZGYMMBHOU53ETUSWTP62B63EQ : SAFWTGXVS7ELMNCXELFWCFZOPMHUZ5LXNBGUVRCY3FHLFPXK4QPXYP2X

        $keyPair1 = KeyPair::fromMnemonic($mnemonic, 1, $passphrase);
        print($keyPair1->getAccountId() . " : " . $keyPair1->getSecretSeed() . PHP_EOL);
        // GDY47CJARRHHL66JH3RJURDYXAMIQ5DMXZLP3TDAUJ6IN2GUOFX4OJOC : SBQPDFUGLMWJYEYXFRM5TQX3AX2BR47WKI4FDS7EJQUSEUUVY72MZPJF

        $bip39SeedHex = "e4a5a632e70943ae7f07659df1332160937fad82587216a4c64315a0fb39497ee4a01f76ddab4cba68147977f3a147b6ad584c41808e8238a07f6cc4b582f186";

        $keyPair0 = KeyPair::fromBip39SeedHex($bip39SeedHex, 0);
        print($keyPair0->getAccountId() . " : " . $keyPair0->getSecretSeed() . PHP_EOL);
        // GDRXE2BQUC3AZNPVFSCEZ76NJ3WWL25FYFK6RGZGIEKWE4SOOHSUJUJ6 : SBGWSG6BTNCKCOB3DIFBGCVMUPQFYPA2G4O34RMTB343OYPXU5DJDVMN

        $keyPair1 = KeyPair::fromBip39SeedHex($bip39SeedHex, 1);
        print($keyPair1->getAccountId() . " : " . $keyPair1->getSecretSeed() . PHP_EOL);
        // GBAW5XGWORWVFE2XTJYDTLDHXTY2Q2MO73HYCGB3XMFMQ562Q2W2GJQX : SCEPFFWGAG5P2VX5DHIYK3XEMZYLTYWIPWYEKXFHSK25RVMIUNJ7CTIS

        $mnemonic = Mnemonic::generate24WordsMnemonic(WordList::LANGUAGE_ITALIAN);

        print("BIP 39 seed: " .  $mnemonic->bip39SeedHex() . PHP_EOL);
        // BIP 39 seed: 54e3061b46c2ceeb9acb29b7c879f5c06414ecc70938ac9c8579fd7d188e9b96162d0477d3af08c86d8cda34949783849518b7be031da5b1fc068735846df573

        print("BIP 39 seed with passphrase: " .  $mnemonic->bip39SeedHex("p4ssphr4se") . PHP_EOL);
        // BIP 39 seed with passphrase: a277e7c3670cf371692a6ef6f9c4177dac6cba69d467b577a430193def40a1512bfedaec8a7cddc7b38573518f242f2b0178048389eeb5dbccaf4ee5556027a2

        print("m/44'/148' key: " .  $mnemonic->m44148keyHex() . PHP_EOL);
        // m/44'/148' key: c5af1061efaa129ef0e0b56e38b8139a27dcfef0f4cbbdfa45b8128a1ac89fbe

        print("m/44'/148' key with passphrase: " .  $mnemonic->m44148keyHex("p4ssphr4se") . PHP_EOL);
        // m/44'/148' key with passphrase: 896dacfe28cac6362e5df6a98482d82719853313feab117a01081110a5e5ca25

        $this->assertTrue(true);
    }

}