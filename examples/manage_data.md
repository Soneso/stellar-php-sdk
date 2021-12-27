
### Create a trustline

In this example we will set a data entry (key value pair) into an account within the stellar network.
To do so, we will create a new account and add the data entry by submitting a transaction that contains the prepared manage data operation.
After that, we will reload the account from stellar, read and compare the data entry.
In the last step we will delete the entry.

```php
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
```
