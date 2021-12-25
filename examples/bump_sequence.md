
### Bump account sequence number

In this example we will bump the sequence number of an account to a higher number.

```php
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
```
