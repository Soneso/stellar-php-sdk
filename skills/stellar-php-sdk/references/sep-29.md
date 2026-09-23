# SEP-29: Account Memo Requirements

**Purpose:** Prevent lost funds by allowing accounts to require incoming payments include a memo.
**Prerequisites:** None
**SDK Namespace:** `Soneso\StellarSDK` (methods on the `StellarSDK` class)
**Spec:** [SEP-0029](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0029.md)

Exchanges and custodial services use SEP-29 to identify which customer a deposit belongs to. Without a memo, incoming payments cannot be credited to the right user. The SDK submit methods run the check for you: a memo-less transaction paying a destination that requires a memo throws `AccountRequiresMemoException` before anything reaches the network.

## Method Signatures

```php
// On a StellarSDK instance:
public function submitTransaction(
    AbstractTransaction $transaction,
    bool $skipMemoRequiredCheck = false,
): SubmitTransactionResponse

public function submitAsyncTransaction(
    AbstractTransaction $transaction,
    bool $skipMemoRequiredCheck = false,
): SubmitAsyncTransactionResponse

public function submitTransactionEnvelopeXdrBase64(
    string $transactionEnvelopeXdrBase64,
    bool $skipMemoRequiredCheck = false,
): SubmitTransactionResponse

public function submitAsyncTransactionEnvelopeXdrBase64(
    string $transactionEnvelopeXdrBase64,
    bool $skipMemoRequiredCheck = false,
): SubmitAsyncTransactionResponse

public function checkMemoRequired(AbstractTransaction $transaction): string|false

// Soneso\StellarSDK\Exceptions\AccountRequiresMemoException extends \Exception
public function getAccountId(): string      // destination requiring the memo
public function getOperationIndex(): int    // zero-based index of the first payment, path payment
                                            // or account merge operation that names it as a
                                            // non-multiplexed destination
```

**Submit methods:** run the check unless `$skipMemoRequiredCheck` is `true`, then submit as usual. They throw `AccountRequiresMemoException` for the first destination that requires a memo, and `HorizonRequestException` for submission failures and for destination lookups that fail with anything other than 404.

**Exception message** (exact format):

```
Destination account GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENPLAY of operation 2 requires a memo in the transaction.
```

**`checkMemoRequired()` returns:**
- `string`: the account ID (G-address) of the first destination requiring a memo
- `false`: no destination requires a memo the transaction does not carry

**No lookup is made when:**
- The transaction (or the inner transaction of a fee bump) carries a memo of any type other than `MEMO_TYPE_NONE`
- No operation names a non-multiplexed destination of a checked type

**Lookup rules:**
- A `FeeBumpTransaction` is checked through its inner transaction
- A destination Horizon answers 404 for is skipped; the network reports the missing account on submission
- Any other lookup failure throws `HorizonRequestException`

**Operation types checked:** `PaymentOperation`, `PathPaymentStrictSendOperation`, `PathPaymentStrictReceiveOperation`, `AccountMergeOperation`

**Muxed accounts skipped:** Destinations with an M-address (muxed account with a numeric ID, including ID `0`) are excluded from the check. Muxed accounts encode user identification in the address itself.

## Quick Start

Submit the payment, and rebuild it with a memo if the destination requires one:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\AccountRequiresMemoException;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk           = StellarSDK::getTestNetInstance();
$senderKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$destinationId = 'GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENPLAY';

$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());

$paymentOp = (new PaymentOperationBuilder($destinationId, Asset::native(), '100.0'))
    ->build();

$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation($paymentOp)
    ->build();
$transaction->sign($senderKeyPair, Network::testnet());

try {
    $response = $sdk->submitTransaction($transaction);
} catch (AccountRequiresMemoException $e) {
    echo 'Memo required by ' . $e->getAccountId()
        . ' (operation ' . $e->getOperationIndex() . ')' . PHP_EOL;

    // build() already incremented the sequence number held in $senderAccount,
    // so reload the account to get the on-chain sequence
    $senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());
    $transaction = (new TransactionBuilder($senderAccount))
        ->addOperation($paymentOp)
        ->addMemo(Memo::text('user-12345'))
        ->build();
    $transaction->sign($senderKeyPair, Network::testnet());
    $response = $sdk->submitTransaction($transaction);
}

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

## How the Check Works Internally

The submit methods and `checkMemoRequired()` share one walk, in this order:

1. If the transaction is a `FeeBumpTransaction`, continue with its inner transaction (`getInnerTx()`); the fee bump envelope itself carries neither memo nor operations
2. If the transaction has any memo (type != `MEMO_TYPE_NONE`) → no violation, and no network call is made
3. Collect destinations from qualifying operations (`PaymentOperation`, `PathPaymentStrictSendOperation`, `PathPaymentStrictReceiveOperation`, `AccountMergeOperation`), in operation order, skipping any destination whose `getId()` is non-null (muxed accounts). The operation index counts every operation of the transaction, including those without a destination. A destination named twice keeps the index of the first operation that names it
4. Look the collected destinations up one by one with `requestAccount()`, in collection order. A lookup that fails with HTTP 404 skips that destination; any other failure throws `HorizonRequestException`
5. The first account whose `$account->getData()->get('config.memo_required')` is identical to the string `'1'` is the hit. The submit methods throw `AccountRequiresMemoException` for it, `checkMemoRequired()` returns its account ID. No further destination is looked up
6. Without a hit, the submit methods submit and `checkMemoRequired()` returns `false`

`submitTransactionEnvelopeXdrBase64()` and `submitAsyncTransactionEnvelopeXdrBase64()` decode the base64 string with `AbstractTransaction::fromEnvelopeBase64XdrString()` and run the same walk on the result. An envelope string the SDK's XDR decoder rejects is submitted unchecked, so that Horizon reports it.

## Transactions with Multiple Destinations

When a transaction has multiple payment operations, the check reports the first destination that requires a memo. `getOperationIndex()` points at the operation to fix. A single memo satisfies the requirement for all destinations in the transaction.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\AccountRequiresMemoException;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk           = StellarSDK::getTestNetInstance();
$senderKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());

$op1 = (new PaymentOperationBuilder(
    'GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENPLAY',
    Asset::native(), '100.0'))->build();
$op2 = (new PaymentOperationBuilder(
    'GCKUD4BHIYSBER7DI6TPMYQ4KNDEUKVMN44VKSUQGEFXWLNTHIIQF22Z',
    Asset::native(), '50.0'))->build();

$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation($op1)
    ->addOperation($op2)
    ->build();
$transaction->sign($senderKeyPair, Network::testnet());

try {
    $sdk->submitTransaction($transaction);
} catch (AccountRequiresMemoException $e) {
    // Operation 0 is $op1, operation 1 is $op2
    echo "Operation {$e->getOperationIndex()} pays {$e->getAccountId()}, which requires a memo" . PHP_EOL;

    // Reload account to reset the sequence number, then rebuild with a memo
    $senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());
    $transaction = (new TransactionBuilder($senderAccount))
        ->addOperation($op1)
        ->addOperation($op2)
        ->addMemo(Memo::text('batch-ref-001'))
        ->build();
    $transaction->sign($senderKeyPair, Network::testnet());
    $sdk->submitTransaction($transaction);
}
```

## AccountMergeOperation

`AccountMergeOperation` is also checked because merging sends the full account balance to the destination:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\AccountMergeOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\AccountRequiresMemoException;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk           = StellarSDK::getTestNetInstance();
$sourceKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$destinationId = 'GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENPLAY';

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
        ->addMemo(Memo::text('closing'))
        ->build();
    $transaction->sign($sourceKeyPair, Network::testnet());
    $sdk->submitTransaction($transaction);
}
```

## Fee Bump Transactions

Submitting a fee bump checks its **inner transaction**: the inner memo decides whether the check runs, the inner operations name the destinations, and `getOperationIndex()` counts the inner operations.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\AccountRequiresMemoException;
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
    'GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENPLAY',
    Asset::native(), '100.0'))->build();

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
    // The memo belongs on the inner transaction; rebuild and wrap it again
    $innerAccount = $sdk->requestAccount($innerKeyPair->getAccountId());
    $innerTx = (new TransactionBuilder($innerAccount))
        ->addOperation($paymentOp)
        ->addMemo(Memo::text('user-ref'))
        ->build();
    $innerTx->sign($innerKeyPair, Network::testnet());

    $feeBumpTx = (new FeeBumpTransactionBuilder($innerTx))
        ->setBaseFee(200)
        ->setFeeAccount($feePayerKeyPair->getAccountId())
        ->build();
    $feeBumpTx->sign($feePayerKeyPair, Network::testnet());
    $sdk->submitTransaction($feeBumpTx);
}
```

## Muxed Account Destinations

Muxed accounts (M-addresses) are skipped by the check. The numeric ID embedded in the M-address already identifies the sub-account, so no memo is needed:

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
    'GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENPLAY',
    12345  // user ID encoded in M-address
);

$paymentOp = (PaymentOperationBuilder::forMuxedDestinationAccount(
    $muxedDestination, Asset::native(), '100.0'))->build();

$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation($paymentOp)
    ->build();

$transaction->sign($senderKeyPair, Network::testnet());

// Muxed destinations are exempt, so this submit makes no account lookup
$sdk->submitTransaction($transaction);
```

## Common Pitfalls

**Wrong: catching only `HorizonRequestException` around a submit call:**

```php
// WRONG: AccountRequiresMemoException extends \Exception, not HorizonRequestException,
// so it escapes this catch and reaches the caller unhandled
try {
    $sdk->submitTransaction($transaction);
} catch (HorizonRequestException $e) {
    echo 'Submission failed: ' . $e->getMessage();
}

// CORRECT: catch the memo exception too, and handle it before the Horizon catch
try {
    $sdk->submitTransaction($transaction);
} catch (AccountRequiresMemoException $e) {
    echo "Add a memo for {$e->getAccountId()} (operation {$e->getOperationIndex()})";
} catch (HorizonRequestException $e) {
    echo 'Submission failed: ' . $e->getMessage();
}
```

**Wrong: passing `skipMemoRequiredCheck: true` by default to avoid the extra request:**

```php
// WRONG: making this the default sends memo-less payments to exchanges
// that cannot credit them
$sdk->submitTransaction($transaction, skipMemoRequiredCheck: true);

// CORRECT: leave the check on. It costs one account lookup per distinct non-muxed
// destination, and only for transactions that carry no memo at all
$sdk->submitTransaction($transaction);

// CORRECT: skip it for a destination whose data entries you control, or one
// checkMemoRequired() already cleared in the same flow
$sdk->submitTransaction($paymentToOwnHotWallet, skipMemoRequiredCheck: true);
```

**Wrong: checking the return value of `checkMemoRequired()` with `==` against the string `"false"`, or with a truthy check:**

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

**Wrong: not reloading the account after the first `TransactionBuilder::build()` call:**

```php
// WRONG: build() increments the sequence number; reusing $senderAccount gives tx_bad_seq
$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());
$tx = (new TransactionBuilder($senderAccount))->addOperation($op)->build();
// ... the submit throws AccountRequiresMemoException ...
$tx2 = (new TransactionBuilder($senderAccount))->addOperation($op)->addMemo(Memo::text('user-12345'))->build();
// tx2 has a stale sequence number → tx_bad_seq on submit

// CORRECT: reload the account before rebuilding
$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());
$tx2 = (new TransactionBuilder($senderAccount))->addOperation($op)->addMemo(Memo::text('user-12345'))->build();
```

**Wrong: using a non-`"1"` value when setting the flag:**

```php
// WRONG: these values will NOT trigger the memo requirement check
new ManageDataOperationBuilder('config.memo_required', 'true')
new ManageDataOperationBuilder('config.memo_required', '1 ')  // trailing space

// CORRECT: must be exactly the string "1"
new ManageDataOperationBuilder('config.memo_required', '1')
```

The builder's `$value` parameter is typed `?string`, so the int `1` is not a third wrong value: under `declare(strict_types=1)` it raises a `TypeError`, and without strict types it is coerced to `'1'` and sets the entry correctly.

**Wrong: expecting the check to validate memo *type* or *content*:**

```php
// The check looks at whether a memo is present, not at its type or value.
// Memo::none() counts as no memo; any other type (text, id, hash, return) makes
// the submit skip the check without any network call, an id memo of 0 included.
$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation($paymentOp)
    ->addMemo(Memo::id(99999))  // any memo type skips the check
    ->build();

$sdk->checkMemoRequired($transaction); // returns false - memo already present
```

## Error Handling

Destination lookups use `requestAccount()`. A destination Horizon answers 404 for is skipped by the check, and the network decides the outcome. A missing destination usually fails the operation (`op_no_destination` for a payment or path payment, `op_no_account` for an account merge), but an earlier operation of the same transaction can create the account, and a payment that returns an asset to its issuer succeeds even after the issuer account was merged away. Every other lookup failure, and every submission failure, surfaces as `HorizonRequestException`:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\AccountRequiresMemoException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk           = StellarSDK::getTestNetInstance();
$senderKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());

$paymentOp = (new PaymentOperationBuilder(
    'GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3UBEZ3ENPLAY',
    Asset::native(), '50.0'))->build();

$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation($paymentOp)
    ->build();
$transaction->sign($senderKeyPair, Network::testnet());

try {
    $sdk->submitTransaction($transaction);
} catch (AccountRequiresMemoException $e) {
    echo 'Memo required by ' . $e->getAccountId()
        . ', operation ' . $e->getOperationIndex() . PHP_EOL;
} catch (HorizonRequestException $e) {
    $statusCode = $e->getStatusCode(); // 429 = rate limited, 5xx = Horizon unavailable
    echo 'Submit failed (' . $statusCode . '): ' . $e->getMessage() . PHP_EOL;
}
```

## Related SEPs

- **[SEP-10](sep-10.md)** — Web Authentication (often required by exchanges that use memos)
- **[SEP-24](sep-24.md)** — Interactive deposit/withdrawal (anchors provide deposit memos per user)
- **[SEP-31](sep-31.md)** — Cross-border payments (uses memos for transaction tracking)
