
### Path Payments

In this example we will send a custom token (IOM) from a sender account to a receiver account. 
But the receiver account can not hold the IOM asset because it has no trustline for this asset. 
The receiver can hold ECO, our second custom token. 
To send IOM but receive ECO we will send a path payment. But for this we need a path through offers so that the assets can be exchaged/traded.
In the following code, we will construct such an example and send the funds with path payment strict send and path payment strict send.

```php
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
```
