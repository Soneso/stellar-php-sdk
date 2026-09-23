# SEP-29: Account Memo Requirements

SEP-29 prevents lost funds by allowing accounts to require incoming payments include a memo. Exchanges and custodians use this to identify which customer a payment belongs to. Without a memo, deposits can't be credited to the right user.

**Use SEP-29 when:**
- Sending payments to exchanges or custodial services
- Building a payment flow that needs to handle destinations requiring a memo
- Running an exchange and requiring memos on incoming deposits

**Spec:** [SEP-0029](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0029.md)

## Quick Example

The submit methods check the destinations of a transaction that carries no memo. If a destination requires one, the submit call throws `AccountRequiresMemoException` and nothing is sent to the network. Catch it, rebuild the transaction with a memo, and submit again:

```php
<?php

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\AccountRequiresMemoException;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk = StellarSDK::getTestNetInstance();
$senderKeyPair = KeyPair::fromSeed('SCT2SAMWPIMPCEPAXIAX2YBK7N3RECO5WC6AW27WA64ILQ3SNGKR7SC3');
$destinationId = "GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENPLAY";

$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());

$paymentOp = (new PaymentOperationBuilder($destinationId, Asset::native(), "100.0"))
    ->build();

$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation($paymentOp)
    ->build();
$transaction->sign($senderKeyPair, Network::testnet());

try {
    $response = $sdk->submitTransaction($transaction);
    echo "Payment sent: " . $response->getHash() . PHP_EOL;
} catch (AccountRequiresMemoException $e) {
    echo "Account " . $e->getAccountId() . " requires a memo, operation "
        . $e->getOperationIndex() . PHP_EOL;

    // build() advanced the sequence number held in $senderAccount
    $senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());

    $transaction = (new TransactionBuilder($senderAccount))
        ->addOperation($paymentOp)
        ->addMemo(Memo::text("user-123"))
        ->build();
    $transaction->sign($senderKeyPair, Network::testnet());
    $sdk->submitTransaction($transaction);
}
```

## How It Works

Accounts signal memo requirement by setting a data entry with key `config.memo_required` and value `1` (following the [SEP-18](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0018.md) namespace convention).

**What the check does**, in this order:

- A fee bump transaction is unwrapped: the check reads the memo and the operations of its inner transaction.
- A transaction that already carries a memo passes without any request to Horizon. Every memo type other than `Memo::none()` counts as a memo present, including an id memo of `0`.
- Destinations are collected in operation order. Multiplexed destinations (M-addresses) are skipped, since the multiplexing id already identifies the customer.
- Each distinct destination is looked up once, in the order the operations name them, and the walk stops at the first account whose `config.memo_required` entry holds `1`.
- A destination Horizon does not know is skipped. The network reports the missing account when the transaction is submitted.
- The first hit throws `AccountRequiresMemoException`. `getAccountId()` returns the destination account id, `getOperationIndex()` the zero-based index of the first payment, path payment or account merge operation that names it as a non-multiplexed destination, counted over all operations of the checked transaction.

**Checked operation types:** `PaymentOperation`, `PathPaymentStrictSendOperation`, `PathPaymentStrictReceiveOperation`, `AccountMergeOperation`

All four submit methods run the check: `submitTransaction()`, `submitAsyncTransaction()`, `submitTransactionEnvelopeXdrBase64()` and `submitAsyncTransactionEnvelopeXdrBase64()`. The envelope variants decode the base64 string first; an envelope string the SDK's XDR decoder rejects is submitted unchecked, so that Horizon reports it. Each method takes `skipMemoRequiredCheck` as its last parameter; passing `true` submits without the check.

`checkMemoRequired()` runs the same walk without submitting anything. It returns the account id of the first destination requiring a memo, or `false` when the transaction satisfies SEP-29. Use it when you want to know about a memo requirement before building or signing.

## Detailed Usage

### Setting Memo Requirement on Your Account

Exchanges and custodial services should set the `config.memo_required` data entry to ensure senders include a memo. Use a `ManageDataOperation` to add the entry:

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\ManageDataOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk = StellarSDK::getTestNetInstance();
$exchangeKeyPair = KeyPair::fromSeed("SBMSVD4KKELKGZXHBUQTIROWUAPQASDX7KEJITARP4VMZ6KLUHOGPTYW");
$exchangeAccount = $sdk->requestAccount($exchangeKeyPair->getAccountId());

// Set memo_required flag
$setMemoRequired = (new ManageDataOperationBuilder("config.memo_required", "1"))
    ->build();

$transaction = (new TransactionBuilder($exchangeAccount))
    ->addOperation($setMemoRequired)
    ->build();

$transaction->sign($exchangeKeyPair, Network::testnet());
$sdk->submitTransaction($transaction);
```

To remove the requirement later, pass `null` as the value. This deletes the data entry entirely:

```php
<?php

use Soneso\StellarSDK\ManageDataOperationBuilder;

$removeMemoRequired = (new ManageDataOperationBuilder("config.memo_required", null))
    ->build();
```

### Transactions with Multiple Destinations

A transaction can pay several recipients. The exception tells you which operation to fix: `getOperationIndex()` counts all operations of the transaction, starting at zero, and names the first payment, path payment or account merge operation that has the reported account as a non-multiplexed destination.

```php
<?php

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\AccountRequiresMemoException;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk = StellarSDK::getTestNetInstance();
$senderKeyPair = KeyPair::fromSeed('SCT2SAMWPIMPCEPAXIAX2YBK7N3RECO5WC6AW27WA64ILQ3SNGKR7SC3');
$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());

// Batch payment to multiple recipients
$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation((new PaymentOperationBuilder(
        "GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENPLAY",
        Asset::native(), "100.0"))->build())
    ->addOperation((new PaymentOperationBuilder(
        "GCKUD4BHIYSBER7DI6TPMYQ4KNDEUKVMN44VKSUQGEFXWLNTHIIQF22Z",
        Asset::native(), "50.0"))->build())
    ->build();
$transaction->sign($senderKeyPair, Network::testnet());

try {
    $sdk->submitTransaction($transaction);
} catch (AccountRequiresMemoException $e) {
    // Operation 0 is the first payment, operation 1 the second
    echo "Operation " . $e->getOperationIndex() . " pays "
        . $e->getAccountId() . ", which requires a memo" . PHP_EOL;
}
```

A single memo satisfies the requirement for every destination of the transaction.

### Account Merge Operations

The check covers `AccountMergeOperation`, since merging sends the account balance to the destination:

```php
<?php

use Soneso\StellarSDK\AccountMergeOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\AccountRequiresMemoException;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk = StellarSDK::getTestNetInstance();
$sourceKeyPair = KeyPair::fromSeed('SCT2SAMWPIMPCEPAXIAX2YBK7N3RECO5WC6AW27WA64ILQ3SNGKR7SC3');
$destinationId = "GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENPLAY";

$sourceAccount = $sdk->requestAccount($sourceKeyPair->getAccountId());

$mergeOp = (new AccountMergeOperationBuilder($destinationId))->build();

$transaction = (new TransactionBuilder($sourceAccount))
    ->addOperation($mergeOp)
    ->build();
$transaction->sign($sourceKeyPair, Network::testnet());

try {
    $sdk->submitTransaction($transaction);
} catch (AccountRequiresMemoException $e) {
    $sourceAccount = $sdk->requestAccount($sourceKeyPair->getAccountId());

    $transaction = (new TransactionBuilder($sourceAccount))
        ->addOperation($mergeOp)
        ->addMemo(Memo::text("closing-account"))
        ->build();
    $transaction->sign($sourceKeyPair, Network::testnet());
    $sdk->submitTransaction($transaction);
}
```

### Multiplexed Accounts (M-addresses)

Per the SEP-29 specification, multiplexed accounts are excluded from memo requirement checks. They already encode user identification in the address itself, so the submit performs no lookup for them:

```php
<?php

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk = StellarSDK::getTestNetInstance();
$senderKeyPair = KeyPair::fromSeed('SCT2SAMWPIMPCEPAXIAX2YBK7N3RECO5WC6AW27WA64ILQ3SNGKR7SC3');
$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());

// Create a muxed destination with user ID embedded
$baseAccountId = "GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENPLAY";
$muxedDestination = new MuxedAccount($baseAccountId, 12345);

$paymentOp = (PaymentOperationBuilder::forMuxedDestinationAccount(
    $muxedDestination, Asset::native(), "100.0"))->build();

$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation($paymentOp)
    ->build();

$transaction->sign($senderKeyPair, Network::testnet());

// The muxed destination is exempt, so this submit makes no account lookup
$sdk->submitTransaction($transaction);
```

### Fee Bump Transactions

Submitting a fee bump transaction checks the inner transaction: its memo decides whether the check runs at all, and its operations name the destinations.

```php
<?php

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\AccountRequiresMemoException;
use Soneso\StellarSDK\FeeBumpTransactionBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk = StellarSDK::getTestNetInstance();
$innerKeyPair = KeyPair::fromSeed('SCT2SAMWPIMPCEPAXIAX2YBK7N3RECO5WC6AW27WA64ILQ3SNGKR7SC3');
$feePayerKeyPair = KeyPair::fromSeed("SBMSVD4KKELKGZXHBUQTIROWUAPQASDX7KEJITARP4VMZ6KLUHOGPTYW");

$innerAccount = $sdk->requestAccount($innerKeyPair->getAccountId());

$paymentOp = (new PaymentOperationBuilder(
    "GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENPLAY",
    Asset::native(), "100.0"))->build();

$innerTx = (new TransactionBuilder($innerAccount))
    ->addOperation($paymentOp)
    ->build();
$innerTx->sign($innerKeyPair, Network::testnet());

$feeBumpTx = (new FeeBumpTransactionBuilder($innerTx))
    ->setBaseFee(200)
    ->setFeeAccount($feePayerKeyPair->getAccountId())
    ->build();
$feeBumpTx->sign($feePayerKeyPair, Network::testnet());

try {
    $sdk->submitTransaction($feeBumpTx);
} catch (AccountRequiresMemoException $e) {
    // The operation index counts the operations of the inner transaction
    echo "Inner operation " . $e->getOperationIndex() . " pays "
        . $e->getAccountId() . ", which requires a memo" . PHP_EOL;
}
```

### Skipping the Check

`skipMemoRequiredCheck: true` submits the transaction without looking at its destinations. Use it when the destination is already known to be safe, for example an account you control or one you verified earlier in the same flow:

```php
<?php

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk = StellarSDK::getTestNetInstance();
$senderKeyPair = KeyPair::fromSeed('SCT2SAMWPIMPCEPAXIAX2YBK7N3RECO5WC6AW27WA64ILQ3SNGKR7SC3');
$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());

// Own hot wallet, no memo requirement possible
$ownWalletId = "GCKUD4BHIYSBER7DI6TPMYQ4KNDEUKVMN44VKSUQGEFXWLNTHIIQF22Z";

$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation((new PaymentOperationBuilder(
        $ownWalletId, Asset::native(), "25.0"))->build())
    ->build();
$transaction->sign($senderKeyPair, Network::testnet());

$sdk->submitTransaction($transaction, skipMemoRequiredCheck: true);
```

## Integration with Payment Flows

A payment function can report the memo requirement back to the caller by catching the exception around the submit call:

```php
<?php

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\AccountRequiresMemoException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

function sendPayment(
    StellarSDK $sdk,
    KeyPair $senderKeyPair,
    string $destinationId,
    string $amount,
    ?string $memo = null
): array {
    try {
        $senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());
    } catch (HorizonRequestException $e) {
        return [
            'success' => false,
            'error' => 'account_not_found',
            'message' => 'Sender account does not exist',
        ];
    }

    $paymentOp = (new PaymentOperationBuilder($destinationId, Asset::native(), $amount))
        ->build();

    $builder = (new TransactionBuilder($senderAccount))
        ->addOperation($paymentOp);

    if ($memo !== null) {
        $builder->addMemo(Memo::text($memo));
    }

    $transaction = $builder->build();
    $transaction->sign($senderKeyPair, Network::testnet());

    try {
        $response = $sdk->submitTransaction($transaction);
    } catch (AccountRequiresMemoException $e) {
        // Ask the user for a memo and call this function again with it
        return [
            'success' => false,
            'error' => 'memo_required',
            'account' => $e->getAccountId(),
        ];
    } catch (HorizonRequestException $e) {
        return [
            'success' => false,
            'error' => 'submission_failed',
            'message' => $e->getMessage(),
        ];
    }

    return ['success' => true, 'hash' => $response->getHash()];
}
```

## Error Handling

`AccountRequiresMemoException` extends `\Exception`, not `HorizonRequestException`, so a catch block written for Horizon errors does not cover it. Its message reads `Destination account <account id> of operation <index> requires a memo in the transaction.`

A destination lookup that fails for a reason other than the account being unknown surfaces as `HorizonRequestException` from the submit call, with the status code Horizon returned:

```php
<?php

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\AccountRequiresMemoException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk = StellarSDK::getTestNetInstance();
$senderKeyPair = KeyPair::fromSeed('SCT2SAMWPIMPCEPAXIAX2YBK7N3RECO5WC6AW27WA64ILQ3SNGKR7SC3');
$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());

$paymentOp = (new PaymentOperationBuilder(
    "GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENPLAY",
    Asset::native(), "50.0"))->build();

$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation($paymentOp)
    ->build();
$transaction->sign($senderKeyPair, Network::testnet());

try {
    $sdk->submitTransaction($transaction);
} catch (AccountRequiresMemoException $e) {
    echo "Memo required by " . $e->getAccountId() . PHP_EOL;
} catch (HorizonRequestException $e) {
    echo "Horizon error (" . $e->getStatusCode() . "): " . $e->getMessage() . PHP_EOL;
}
```

**Important notes:**
- A destination account Horizon does not know is skipped by the check. The submission then fails with the network's own `op_no_destination` result
- The check looks at memo *presence*, not memo *type* (SEP-29 intentionally omits type validation)

## Related SEPs

- **[SEP-10](sep-10.md)** — Web authentication (often used by exchanges that require memos)
- **[SEP-24](sep-24.md)** — Interactive deposit/withdrawal (anchors provide deposit memos)
- **[SEP-31](sep-31.md)** — Cross-border payments (uses memos for transaction tracking)

---

[Back to SEP Overview](README.md)
