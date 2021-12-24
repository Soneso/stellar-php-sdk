
### Send a native (XLM) payment

In this example we will send a native payment (XLM - Stellar Lumens) from a sender stellar account to a destination stellar account.

```php
// First create the sender key pair from the secret seed of the sender so we can use it later for signing.
$senderKeyPair = KeyPair::fromSeed("SA52PD5FN425CUONRMMX2CY5HB6I473A5OYNIVU67INROUZ6W4SPHXZB");

// Next, we need the account id of the receiver so that we can use to as a destination of our payment. 
$destination = "GCRFFUKMUWWBRIA6ABRDFL5NKO6CKDB2IOX7MOS2TRLXNXQD255Z2MYG";

// Load sender's account data from the stellar network. It contains the current sequence number.
$sender = $sdk->requestAccount($senderKeyPair->getAccountId());

// Build the transaction to send 100 XLM native payment from sender to destination
$paymentOperation = (new PaymentOperationBuilder($destination, Asset::native(), "100"))->build();
$transaction = (new TransactionBuilder($sender))->addOperation($paymentOperation)->build();

// Sign the transaction with the sender's key pair.
$transaction->sign($senderKeyPair, Network::testnet());

// Submit the transaction to the stellar network.
$response = $sdk->submitTransaction($transaction);
if ($response->isSuccessful()) {
    print(PHP_EOL."Payment sent");
}
```
