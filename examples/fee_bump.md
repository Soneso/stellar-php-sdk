
### Fee bump transaction

In this example we will let a payer account pay the fee for another transaction by using the fee bump transaction.

```php
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
```
