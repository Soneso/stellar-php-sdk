
### Change trust

In this example we will create, update, and delete a trustline. For more on trustlines, please refer to the [assets documentation](https://www.stellar.org/developers/learn/concepts/assets.html).

We will let one account, called trustor, trust another account that is the issuer of a custom token called "ASTRO". Then, we will modify and finally delete the trustline.

```php
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

// Check if the trustline exists.
$found = false;
foreach ($trustorAccount->getBalances() as $balance) {
    if ($balance->getAssetCode() == $assetCode) {
        $found = true;
        break;
    }
}

if (!$found) {
    print(PHP_EOL."success, trustline deleted");
}
```
