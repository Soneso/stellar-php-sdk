# SEP-29: Account Memo Requirements

SEP-29 prevents lost funds by allowing accounts to require incoming payments include a memo. Exchanges and custodians use this to identify which customer a payment belongs to. Without a memo, deposits can't be credited to the right user.

**Use SEP-29 when:**
- Sending payments to exchanges or custodial services
- Building a payment flow that needs to validate destinations before submission
- Running an exchange and requiring memos on incoming deposits

**Spec:** [SEP-0029](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0029.md)

## Quick Example

Check whether destination accounts require a memo before submitting a payment. If any destination requires a memo and the transaction lacks one, rebuild the transaction with a memo attached:

```php
<?php

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk = StellarSDK::getTestNetInstance();
$senderKeyPair = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CJDQ66EQ7DZTPBRJFN4A");
$destinationId = "GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENO5GT";

$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());

$paymentOp = (new PaymentOperationBuilder($destinationId, Asset::native(), "100.0"))
    ->build();

$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation($paymentOp)
    ->build();

// Check if destination requires a memo
$requiresMemo = $sdk->checkMemoRequired($transaction);

if ($requiresMemo !== false) {
    echo "Account {$requiresMemo} requires a memo. Rebuild with one.";
    $transaction = (new TransactionBuilder($senderAccount))
        ->addOperation($paymentOp)
        ->addMemo(Memo::text("user-123"))
        ->build();
}

$transaction->sign($senderKeyPair, Network::testnet());
$response = $sdk->submitTransaction($transaction);
```

## How It Works

Accounts signal memo requirement by setting a data entry with key `config.memo_required` and value `1` (following the [SEP-18](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0018.md) namespace convention).

**What to expect from `checkMemoRequired()`:**

- Returns `false` for fee bump transactions — check the inner transaction instead
- Returns `false` if the transaction already has a memo
- Skips muxed accounts (M-addresses) since they encode user identification in the address
- Makes network calls to Horizon to check each destination's account data
- Returns the first account ID requiring a memo, or `false` if none do

**Checked operation types:** `PaymentOperation`, `PathPaymentStrictSendOperation`, `PathPaymentStrictReceiveOperation`, `AccountMergeOperation`

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

When a transaction contains multiple payment operations, the SDK checks all destination accounts. It returns the first account ID requiring a memo, allowing you to inform the user which recipient needs one:

```php
<?php

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk = StellarSDK::getTestNetInstance();
$senderKeyPair = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CJDQ66EQ7DZTPBRJFN4A");
$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());

// Batch payment to multiple recipients
$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation((new PaymentOperationBuilder(
        "GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENO5GT",
        Asset::native(), "100.0"))->build())
    ->addOperation((new PaymentOperationBuilder(
        "GCKUD4BHIYSBER7DI6TPMYQ4KNDEUKVMN44VKSUQGEFXWLNTHIIQE7FB",
        Asset::native(), "50.0"))->build())
    ->build();

$accountRequiringMemo = $sdk->checkMemoRequired($transaction);

if ($accountRequiringMemo !== false) {
    echo "Cannot batch: {$accountRequiringMemo} requires a memo.";
}
```

### Account Merge Operations

The memo check also applies to `AccountMergeOperation`, since merging sends the account balance to the destination. This example validates before merging an account:

```php
<?php

use Soneso\StellarSDK\AccountMergeOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk = StellarSDK::getTestNetInstance();
$sourceKeyPair = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CJDQ66EQ7DZTPBRJFN4A");
$destinationId = "GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENO5GT";

$sourceAccount = $sdk->requestAccount($sourceKeyPair->getAccountId());

$mergeOp = (new AccountMergeOperationBuilder($destinationId))->build();

$transaction = (new TransactionBuilder($sourceAccount))
    ->addOperation($mergeOp)
    ->build();

$requiresMemo = $sdk->checkMemoRequired($transaction);

if ($requiresMemo !== false) {
    // Rebuild with memo before merging
    $transaction = (new TransactionBuilder($sourceAccount))
        ->addOperation($mergeOp)
        ->addMemo(Memo::text("closing-account"))
        ->build();
}

$transaction->sign($sourceKeyPair, Network::testnet());
$sdk->submitTransaction($transaction);
```

### Multiplexed Accounts (M-addresses)

Per the SEP-29 specification, multiplexed accounts are excluded from memo requirement checks. Muxed accounts (M-addresses) already encode user identification in the address itself, making a separate memo unnecessary:

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
$senderKeyPair = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CJDQ66EQ7DZTPBRJFN4A");
$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());

// Create a muxed destination with user ID embedded
$baseAccountId = "GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENO5GT";
$muxedDestination = new MuxedAccount($baseAccountId, 12345);

$paymentOp = (PaymentOperationBuilder::forMuxedDestinationAccount(
    $muxedDestination, Asset::native(), "100.0"))->build();

$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation($paymentOp)
    ->build();

// Returns false for muxed accounts, so no memo check needed
$requiresMemo = $sdk->checkMemoRequired($transaction);
// $requiresMemo === false

$transaction->sign($senderKeyPair, Network::testnet());
$sdk->submitTransaction($transaction);
```

## Integration with Payment Flows

Use memo requirement checking as part of your payment validation flow. Check requirements before showing the confirmation screen to provide a better user experience:

```php
<?php

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
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

    try {
        $requiresMemo = $sdk->checkMemoRequired($transaction);
    } catch (HorizonRequestException $e) {
        return [
            'success' => false,
            'error' => 'destination_lookup_failed',
            'message' => 'Could not verify destination account',
        ];
    }

    if ($requiresMemo !== false && $memo === null) {
        return [
            'success' => false,
            'error' => 'memo_required',
            'account' => $requiresMemo,
        ];
    }

    $transaction->sign($senderKeyPair, Network::testnet());
    $response = $sdk->submitTransaction($transaction);

    return ['success' => true, 'hash' => $response->getHash()];
}
```

## Error Handling

The `checkMemoRequired()` method queries Horizon for each destination account's data. If any lookup fails, it throws a `HorizonRequestException`. Common causes include the destination account not existing yet or Horizon being unavailable:

```php
<?php

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk = StellarSDK::getTestNetInstance();
$senderKeyPair = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CJDQ66EQ7DZTPBRJFN4A");
$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());

$paymentOp = (new PaymentOperationBuilder(
    "GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENO5GT",
    Asset::native(), "50.0"))->build();

$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation($paymentOp)
    ->build();

try {
    $requiresMemo = $sdk->checkMemoRequired($transaction);
} catch (HorizonRequestException $e) {
    // Destination account might not exist yet, or Horizon is unavailable
    echo "Could not verify memo requirement: " . $e->getMessage();
}
```

**Important notes:**
- Fee bump transactions always return `false`. Check the inner transaction before wrapping it
- The method only validates memo *presence*, not memo *type* (SEP-29 intentionally omits type validation)

## Related SEPs

- **[SEP-10](sep-10.md)** — Web authentication (often used by exchanges that require memos)
- **[SEP-24](sep-24.md)** — Interactive deposit/withdrawal (anchors provide deposit memos)
- **[SEP-31](sep-31.md)** — Cross-border payments (uses memos for transaction tracking)

---

[Back to SEP Overview](README.md)
