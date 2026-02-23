# SEP-29: Account Memo Requirements

**Purpose:** Prevent lost funds by allowing accounts to require incoming payments include a memo.
**Prerequisites:** None
**SDK Namespace:** `Soneso\StellarSDK` (method on `StellarSDK` class)
**Spec:** [SEP-0029](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0029.md)

Exchanges and custodial services use SEP-29 to identify which customer a deposit belongs to. Without a memo, incoming payments cannot be credited to the right user. Use `checkMemoRequired()` before submitting any payment to a destination you do not control.

## checkMemoRequired() — Method Signature

```php
// On StellarSDK instance:
public function checkMemoRequired(AbstractTransaction $transaction): string|false
```

**Returns:**
- `string` — the account ID (G-address) of the first destination requiring a memo
- `false` — if no destination requires a memo, or if the check is skipped (see rules below)

**Throws:** `HorizonRequestException` — if any Horizon account lookup fails

**Skip rules (always returns `false` immediately):**
- Transaction is a `FeeBumpTransaction` — check the inner transaction instead
- Transaction already has a memo (any memo type other than `MEMO_TYPE_NONE`)
- No payment-type operations in the transaction

**Operation types checked:** `PaymentOperation`, `PathPaymentStrictSendOperation`, `PathPaymentStrictReceiveOperation`, `AccountMergeOperation`

**Muxed accounts skipped:** Destinations with an M-address (muxed account with a numeric ID) are excluded from the check. Muxed accounts encode user identification in the address itself.

## Quick Start

Check before submitting a payment, and rebuild with a memo if required:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk           = StellarSDK::getTestNetInstance();
$senderKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$destinationId = 'GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENO5GT';

$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());

$paymentOp = (new PaymentOperationBuilder($destinationId, Asset::native(), '100.0'))
    ->build();

$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation($paymentOp)
    ->build();

try {
    $requiresMemo = $sdk->checkMemoRequired($transaction);
} catch (HorizonRequestException $e) {
    // Destination account does not exist yet, or Horizon is unavailable
    exit('Could not verify memo requirement: ' . $e->getMessage());
}

if ($requiresMemo !== false) {
    // Rebuild with a memo — the TransactionBuilder already incremented the
    // sequence number, so reload the account to get the on-chain sequence
    $senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());
    $transaction = (new TransactionBuilder($senderAccount))
        ->addOperation($paymentOp)
        ->addMemo(Memo::text('user-12345'))
        ->build();
}

$transaction->sign($senderKeyPair, Network::testnet());
$response = $sdk->submitTransaction($transaction);
echo 'Hash: ' . $response->getHash() . PHP_EOL;
```

## Setting the Memo-Required Flag on Your Account

Exchanges and custodial services use a `ManageDataOperation` to set the `config.memo_required` data entry. The value must be the string `"1"`.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\ManageDataOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk             = StellarSDK::getTestNetInstance();
$exchangeKeyPair = KeyPair::fromSeed(getenv('EXCHANGE_SECRET_SEED'));
$exchangeAccount = $sdk->requestAccount($exchangeKeyPair->getAccountId());

// Set the flag: key = "config.memo_required", value = "1"
$setFlag = (new ManageDataOperationBuilder('config.memo_required', '1'))->build();

$transaction = (new TransactionBuilder($exchangeAccount))
    ->addOperation($setFlag)
    ->build();

$transaction->sign($exchangeKeyPair, Network::testnet());
$response = $sdk->submitTransaction($transaction);
echo 'Memo required flag set: ' . $response->getHash() . PHP_EOL;
```

To remove the requirement, pass `null` as the value. This deletes the data entry:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\ManageDataOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk             = StellarSDK::getTestNetInstance();
$exchangeKeyPair = KeyPair::fromSeed(getenv('EXCHANGE_SECRET_SEED'));
$exchangeAccount = $sdk->requestAccount($exchangeKeyPair->getAccountId());

// Passing null deletes the data entry entirely
$removeFlag = (new ManageDataOperationBuilder('config.memo_required', null))->build();

$transaction = (new TransactionBuilder($exchangeAccount))
    ->addOperation($removeFlag)
    ->build();

$transaction->sign($exchangeKeyPair, Network::testnet());
$sdk->submitTransaction($transaction);
```

## How checkMemoRequired() Works Internally

The SDK performs these checks in order:

1. If `$transaction` is a `FeeBumpTransaction` → return `false` immediately
2. If the transaction has any memo (type != `MEMO_TYPE_NONE`) → return `false` immediately
3. Collect all destinations from qualifying operations (`PaymentOperation`, `PathPaymentStrictSendOperation`, `PathPaymentStrictReceiveOperation`, `AccountMergeOperation`) — skipping any destination whose `getId()` returns a non-null value (muxed accounts)
4. If no destinations remain → return `false`
5. For each destination, call `requestAccount($destination)` on Horizon and check `$account->getData()->get("config.memo_required") == "1"`
6. Return the first matching account ID, or `false` if none match

## Transactions with Multiple Destinations

When a transaction has multiple payment operations, `checkMemoRequired()` checks all destinations and returns the first that requires a memo. A single memo satisfies the requirement for all destinations in the transaction.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk           = StellarSDK::getTestNetInstance();
$senderKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());

$op1 = (new PaymentOperationBuilder(
    'GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENO5GT',
    Asset::native(), '100.0'))->build();
$op2 = (new PaymentOperationBuilder(
    'GCKUD4BHIYSBER7DI6TPMYQ4KNDEUKVMN44VKSUQGEFXWLNTHIIQE7FB',
    Asset::native(), '50.0'))->build();

$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation($op1)
    ->addOperation($op2)
    ->build();

try {
    $accountRequiringMemo = $sdk->checkMemoRequired($transaction);
} catch (HorizonRequestException $e) {
    exit('Lookup failed: ' . $e->getMessage());
}

if ($accountRequiringMemo !== false) {
    echo "Account {$accountRequiringMemo} requires a memo — add one before submitting." . PHP_EOL;
    // Reload account to reset sequence number, then rebuild with memo
    $senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());
    $transaction = (new TransactionBuilder($senderAccount))
        ->addOperation($op1)
        ->addOperation($op2)
        ->addMemo(Memo::text('batch-ref-001'))
        ->build();
}

$transaction->sign($senderKeyPair, Network::testnet());
$sdk->submitTransaction($transaction);
```

## AccountMergeOperation

`AccountMergeOperation` is also checked because merging sends the full account balance to the destination:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\AccountMergeOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk           = StellarSDK::getTestNetInstance();
$sourceKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$destinationId = 'GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENO5GT';

$sourceAccount = $sdk->requestAccount($sourceKeyPair->getAccountId());

$mergeOp = (new AccountMergeOperationBuilder($destinationId))->build();

$transaction = (new TransactionBuilder($sourceAccount))
    ->addOperation($mergeOp)
    ->build();

try {
    $requiresMemo = $sdk->checkMemoRequired($transaction);
} catch (HorizonRequestException $e) {
    exit('Lookup failed: ' . $e->getMessage());
}

if ($requiresMemo !== false) {
    $sourceAccount = $sdk->requestAccount($sourceKeyPair->getAccountId());
    $transaction = (new TransactionBuilder($sourceAccount))
        ->addOperation($mergeOp)
        ->addMemo(Memo::text('closing'))
        ->build();
}

$transaction->sign($sourceKeyPair, Network::testnet());
$sdk->submitTransaction($transaction);
```

## Fee Bump Transactions

`checkMemoRequired()` always returns `false` for fee bump transactions. Check the **inner transaction** before wrapping it:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\FeeBumpTransactionBuilder;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk             = StellarSDK::getTestNetInstance();
$innerKeyPair    = KeyPair::fromSeed(getenv('INNER_SECRET_SEED'));
$feePayerKeyPair = KeyPair::fromSeed(getenv('FEE_PAYER_SECRET_SEED'));

$innerAccount = $sdk->requestAccount($innerKeyPair->getAccountId());

$paymentOp = (new PaymentOperationBuilder(
    'GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENO5GT',
    Asset::native(), '100.0'))->build();

// Build and check the INNER transaction before wrapping
$innerTx = (new TransactionBuilder($innerAccount))
    ->addOperation($paymentOp)
    ->build();

// WRONG: check the fee bump — checkMemoRequired() always returns false for FeeBumpTransaction
// CORRECT: check the inner transaction
try {
    $requiresMemo = $sdk->checkMemoRequired($innerTx);
} catch (HorizonRequestException $e) {
    exit('Lookup failed: ' . $e->getMessage());
}

if ($requiresMemo !== false) {
    $innerAccount = $sdk->requestAccount($innerKeyPair->getAccountId());
    $innerTx = (new TransactionBuilder($innerAccount))
        ->addOperation($paymentOp)
        ->addMemo(Memo::text('user-ref'))
        ->build();
}

$innerTx->sign($innerKeyPair, Network::testnet());

$feeBumpTx = (new FeeBumpTransactionBuilder($innerTx))
    ->setBaseFee(200)
    ->setFeeAccount($feePayerKeyPair->getAccountId())
    ->build();

$feeBumpTx->sign($feePayerKeyPair, Network::testnet());
$sdk->submitTransaction($feeBumpTx);
```

## Muxed Account Destinations

Muxed accounts (M-addresses) are automatically skipped by `checkMemoRequired()`. The numeric ID embedded in the M-address already identifies the sub-account, so no memo is needed:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk           = StellarSDK::getTestNetInstance();
$senderKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());

// G-address of the exchange, plus user ID 12345 embedded in the address
$muxedDestination = new MuxedAccount(
    'GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENO5GT',
    12345  // user ID encoded in M-address
);

$paymentOp = (PaymentOperationBuilder::forMuxedDestinationAccount(
    $muxedDestination, Asset::native(), '100.0'))->build();

$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation($paymentOp)
    ->build();

// Always returns false for muxed destinations — no network call is made for them
$requiresMemo = $sdk->checkMemoRequired($transaction);
// $requiresMemo === false

$transaction->sign($senderKeyPair, Network::testnet());
$sdk->submitTransaction($transaction);
```

## Common Pitfalls

**Wrong: checking return value with `==` against string `"false"` or truthy check:**

```php
// WRONG: non-empty string is truthy; "false" never equals false
if ($sdk->checkMemoRequired($transaction)) {
    // This branch fires even when a G-address string is returned
    // AND when testing against a string "false" — use strict comparison
}

// CORRECT: use strict identity check
$result = $sdk->checkMemoRequired($transaction);
if ($result !== false) {
    echo "Memo required by: {$result}";
}
```

**Wrong: checking a fee bump transaction instead of the inner transaction:**

```php
// WRONG: fee bumps always return false — you'll miss the memo requirement
$feeBump = (new FeeBumpTransactionBuilder($innerTx))->...->build();
$sdk->checkMemoRequired($feeBump); // always false

// CORRECT: check the inner transaction before wrapping it
$sdk->checkMemoRequired($innerTx); // checks destinations correctly
```

**Wrong: not reloading the account after the first `TransactionBuilder::build()` call:**

```php
// WRONG: build() increments the sequence number; reusing $senderAccount gives tx_bad_seq
$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());
$tx = (new TransactionBuilder($senderAccount))->addOperation($op)->build();
// ... checkMemoRequired returns non-false ...
$tx2 = (new TransactionBuilder($senderAccount))->addOperation($op)->addMemo(...)->build();
// tx2 has a stale sequence number → tx_bad_seq on submit

// CORRECT: reload the account before rebuilding
$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());
$tx2 = (new TransactionBuilder($senderAccount))->addOperation($op)->addMemo(...)->build();
```

**Wrong: using a non-`"1"` value when setting the flag:**

```php
// WRONG: these values will NOT trigger the memo requirement check
new ManageDataOperationBuilder('config.memo_required', 'true')
new ManageDataOperationBuilder('config.memo_required', '1 ')  // trailing space
new ManageDataOperationBuilder('config.memo_required', 1)     // int, not string

// CORRECT: must be exactly the string "1"
new ManageDataOperationBuilder('config.memo_required', '1')
```

**Wrong: expecting `checkMemoRequired()` to validate memo *type* or *content*:**

```php
// The SDK only checks whether a memo is present, not its type or value.
// A Memo::none() still counts as no memo; any other type (text, id, hash, return)
// causes the method to return false immediately without making any network calls.
$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation($paymentOp)
    ->addMemo(Memo::id(99999))  // any memo type skips the check
    ->build();

$sdk->checkMemoRequired($transaction); // returns false — memo already present
```

## Error Handling

`checkMemoRequired()` calls `requestAccount()` on Horizon for each non-muxed destination. If the destination account does not exist yet, or Horizon is unavailable, a `HorizonRequestException` is thrown:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk           = StellarSDK::getTestNetInstance();
$senderKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());

$paymentOp = (new PaymentOperationBuilder(
    'GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENO5GT',
    Asset::native(), '50.0'))->build();

$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation($paymentOp)
    ->build();

try {
    $requiresMemo = $sdk->checkMemoRequired($transaction);
} catch (HorizonRequestException $e) {
    $statusCode = $e->getStatusCode(); // 404 = account not found, 429 = rate limited
    echo 'Memo check failed (' . $statusCode . '): ' . $e->getMessage() . PHP_EOL;
    // Decide: abort payment, skip the check, or retry
}
```

## Related SEPs

- **[SEP-10](sep-10.md)** — Web Authentication (often required by exchanges that use memos)
- **[SEP-24](sep-24.md)** — Interactive deposit/withdrawal (anchors provide deposit memos per user)
- **[SEP-31](sep-31.md)** — Cross-border payments (uses memos for transaction tracking)

<!-- DISCREPANCIES / JUDGMENT CALLS:

1. The SDK docs (sdk_sources/stellar-php-sdk/docs/sep/sep-29.md) are comprehensive
   and correct. All code in this file was verified against the actual SDK source at
   Soneso/StellarSDK/StellarSDK.php lines 531-576.

2. The muxed-account skip condition: The source code checks `!$destination->getId()`.
   MuxedAccount::getId() returns ?int (null if no ID, int if muxed). The condition
   `!$destination->getId()` pushes the destination into the array only when getId()
   returns null or 0 (falsy). In practice, all muxed accounts with a user ID set
   (any non-zero int) are excluded. This is documented accurately above.

3. The SDK docs example for muxed accounts uses
   `PaymentOperationBuilder::forMuxedDestinationAccount($muxedDestination, Asset::native(), "100.0")`
   which was verified in PaymentOperationBuilder.php line 61 as the correct static
   factory signature: `forMuxedDestinationAccount(MuxedAccount $destination, Asset $asset, string $amount)`.

4. Sequence number reload after a failed check: The SDK docs do not mention this,
   but it is a real pitfall. TransactionBuilder::build() mutates the source account's
   sequence number in memory. If checkMemoRequired() returns non-false and you need
   to rebuild, you must reload the account from Horizon. Added as a WRONG/CORRECT
   pattern since this is a genuine, non-obvious pitfall.

5. No unit tests for checkMemoRequired() were found in the test suite under
   Soneso/StellarSDKTests/Unit/. The only test is the integration test in
   PaymentsTest.php::testCheckMemoRequirements() (line 898), which confirms the
   full flow: fund → set config.memo_required → build payment tx → checkMemoRequired
   returns the destination account ID.

6. The "value must be exactly '1'" pitfall is inferred directly from the source:
   `$account->getData()->get($key) == "1"` (loose equality). Trailing spaces or
   other truthy values will not match. Documented as a WRONG/CORRECT pattern.
-->
