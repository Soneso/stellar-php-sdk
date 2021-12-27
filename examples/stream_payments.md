
### Stream for payments

In this example we will listen for received payments for an account.

```php
$sdk = StellarSDK::getTestNetInstance();

 // Create two accounts, so that we can send a payment.
$keypair1 = KeyPair::random();
$keypair2 = KeyPair::random();
$acc1Id = $keypair1->getAccountId();
$acc2Id = $keypair2->getAccountId();
FriendBot::fundTestAccount($acc1Id);
FriendBot::fundTestAccount($acc2Id);

// create a child process that listens to payment steam
$pid = pcntl_fork();

if ($pid == 0) {
    // Subscribe to listen for payments for account 2.
    // If we set the cursor to "now" it will not receive old events such as the create account operation.
    $sdk->payments()->forAccount($acc2Id)->cursor("now")->stream(function(OperationResponse $payment) {
        printf('Payment operation %s id %s' . PHP_EOL, get_class($payment), $payment->getOperationId());
        // exit as soon as our specific payment has been received
        if ($payment instanceof PaymentOperationResponse && floatval($payment->getAmount()) == 100.00) {
            exit(1);
        }
    });
}

// send the payment from account 1 to account 2
$acc1 = $sdk->requestAccount($acc1Id);
$paymentOperation = (new PaymentOperationBuilder($acc2Id, Asset::native(), "100"))->build();
$transaction = (new TransactionBuilder($acc1))->addOperation($paymentOperation)->build();
$transaction->sign($keypair1, Network::testnet());
$response = $sdk->submitTransaction($transaction);

// wait for child process to finish.
while (pcntl_waitpid(0, $status) != -1) {
    $status = pcntl_wexitstatus($status);
    echo "Completed with status: $status \n";
}
```
