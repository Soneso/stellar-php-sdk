
### Merge account

In this example we will merge an account Y into another account X. After merging, account Y will not exist any more and account X will posses the funds of accountY.

First we create two random accounts (X and Y) by asking Freindbot. Then we merge Y into X and check the result.

```php
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
```
