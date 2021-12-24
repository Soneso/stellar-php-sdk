
### Create Account

In this example we will let Friendbot fund a testnet account. In the main net however we need another already existing account to be able to create a new one.

### Friendbot (testnet only)

```php
$sdk = StellarSDK::getTestNetInstance();

// Create a random key pair for our new account.
$keyPair = KeyPair::random();

// Ask the Friendbot to create our new account in the stellar network (only available in testnet).
$funded = FriendBot::fundTestAccount($keyPair->getAccountId());

// Load the data of the new account from stellar.
$account = $sdk->requestAccount($keyPair->getAccountId());
```

### Create Account Operation

```php
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
```