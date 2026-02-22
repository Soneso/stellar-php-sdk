# Stellar Operations Reference

Complete guide to all 26 Stellar operations using the PHP SDK. Every operation uses the builder pattern: construct a builder, configure it, call `build()`, then add to a transaction.

## Common Patterns

All operations follow the builder pattern. A transaction can contain up to 100 operations.

**Single operation:**

```php
$sourceAccount = $sdk->requestAccount($sourceAccountId);

$op = (new SomeOperationBuilder(/* params */))->build();

$transaction = (new TransactionBuilder($sourceAccount))
    ->addOperation($op)
    ->build();

$transaction->sign($sourceKeyPair, Network::testnet());
$response = $sdk->submitTransaction($transaction);
```

**Multiple operations with per-operation source accounts:**

```php
$sourceAccount = $sdk->requestAccount($sourceAccountId);

$op1 = (new CreateAccountOperationBuilder($destId, '100'))->build();
$op2 = (new PaymentOperationBuilder($destId, Asset::native(), '50'))
    ->setSourceAccount($otherAccountId) // overrides the transaction's source for this operation only
    ->build();

$transaction = (new TransactionBuilder($sourceAccount))
    ->addOperations([$op1, $op2]) // or chain ->addOperation($op1)->addOperation($op2)
    ->build();

// All source accounts involved must sign
$transaction->sign($sourceKeyPair, Network::testnet());
$transaction->sign($otherKeyPair, Network::testnet());
$response = $sdk->submitTransaction($transaction);
```

---

## Account Operations

### Create Account

Creates and funds a new Stellar account on the network.

```php
use Soneso\StellarSDK\CreateAccountOperationBuilder;

// $destination: G... account ID to create
// $startingBalance: initial XLM balance as string
$operation = (new CreateAccountOperationBuilder(
    $destinationAccountId,
    '100.0'
))->build();
```

**Parameters:** `string $destination`, `string $startingBalance`
**Errors:** `op_underfunded` (insufficient balance), `op_already_exists` (destination exists), `op_malformed` (invalid params)

### Payment

Sends a payment of any asset from source to destination.

```php
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeNative;

// Native XLM payment
$xlmPayment = (new PaymentOperationBuilder(
    $destinationAccountId,
    Asset::native(),
    '50.0'
))->build();

// Issued asset payment
$usdAsset = Asset::createNonNativeAsset('USD', $issuerAccountId);
$usdPayment = (new PaymentOperationBuilder(
    $destinationAccountId,
    $usdAsset,
    '100.0'
))->build();
```

**Parameters:** `string $destinationAccountId`, `Asset $asset`, `string $amount`
**Errors:** `op_underfunded`, `op_no_destination`, `op_no_trust`, `op_line_full`

---

## Path Payment Operations

### Path Payment Strict Receive

Sends a payment through a path of assets, guaranteeing the destination receives an exact amount.

```php
use Soneso\StellarSDK\PathPaymentStrictReceiveOperationBuilder;
use Soneso\StellarSDK\Asset;

$sendAsset = Asset::native();
$destAsset = Asset::createNonNativeAsset('EUR', $eurIssuerAccountId);

$operation = (new PathPaymentStrictReceiveOperationBuilder(
    $sendAsset,       // asset to deduct from source
    '200.0',          // max amount to send
    $destinationAccountId,
    $destAsset,       // asset destination receives
    '100.0'           // exact amount destination receives
))
    ->setPath([$usdAsset]) // optional intermediate assets
    ->build();
```

**Parameters:** `Asset $sendAsset`, `string $sendMax`, `string $destinationAccountId`, `Asset $destAsset`, `string $destAmount`

### Path Payment Strict Send

Sends an exact amount from the source, destination receives at least a minimum.

```php
use Soneso\StellarSDK\PathPaymentStrictSendOperationBuilder;
use Soneso\StellarSDK\Asset;

$sendAsset = Asset::native();
$destAsset = Asset::createNonNativeAsset('EUR', $eurIssuerAccountId);

$operation = (new PathPaymentStrictSendOperationBuilder(
    $sendAsset,
    '100.0',          // exact amount to send
    $destinationAccountId,
    $destAsset,
    '90.0'            // minimum destination receives
))
    ->setPath([$usdAsset])
    ->build();
```

**Parameters:** `Asset $sendAsset`, `string $sendAmount`, `string $destinationAccountId`, `Asset $destAsset`, `string $destMin`

---

## DEX Offer Operations

### Manage Sell Offer

Creates, updates, or deletes a sell offer on the DEX. Set amount to `'0'` to delete.

```php
use Soneso\StellarSDK\ManageSellOfferOperationBuilder;
use Soneso\StellarSDK\Asset;

$selling = Asset::native();
$buying = Asset::createNonNativeAsset('USD', $issuerAccountId);

// Create new offer (offerId defaults to 0)
$createOffer = (new ManageSellOfferOperationBuilder(
    $selling,
    $buying,
    '100.0',   // amount of selling asset
    '2.5'      // price: 1 selling = 2.5 buying
))->build();

// Update existing offer
$updateOffer = (new ManageSellOfferOperationBuilder($selling, $buying, '150.0', '2.8'))
    ->setOfferId(12345)
    ->build();

// Delete offer (amount = 0)
$deleteOffer = (new ManageSellOfferOperationBuilder($selling, $buying, '0', '1'))
    ->setOfferId(12345)
    ->build();
```

**Parameters:** `Asset $selling`, `Asset $buying`, `string $amount`, `string $price`

### Manage Buy Offer

Creates, updates, or deletes a buy offer. Same pattern as sell but `$amount` is the buy amount.

```php
use Soneso\StellarSDK\ManageBuyOfferOperationBuilder;
use Soneso\StellarSDK\Asset;

$selling = Asset::native();
$buying = Asset::createNonNativeAsset('USD', $issuerAccountId);

$operation = (new ManageBuyOfferOperationBuilder(
    $selling,
    $buying,
    '50.0',    // amount of buying asset to buy
    '0.4'      // price: 1 buying in terms of selling
))
    ->setOfferId(0) // 0 = new offer
    ->build();
```

**Parameters:** `Asset $selling`, `Asset $buying`, `string $amount`, `string $price`

### Create Passive Sell Offer

Creates a passive sell offer that does not act on existing matching offers. Uses `Price` object directly.

```php
use Soneso\StellarSDK\CreatePassiveSellOfferOperationBuilder;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Price;

$selling = Asset::native();
$buying = Asset::createNonNativeAsset('USD', $issuerAccountId);
$price = Price::fromString('2.5');

$operation = (new CreatePassiveSellOfferOperationBuilder(
    $selling,
    $buying,
    '100.0',
    $price
))->build();
```

**Parameters:** `Asset $selling`, `Asset $buying`, `string $amount`, `Price $price`

---

## Account Configuration Operations

### Set Options

Configures account properties. All setters are optional -- call only those you need.

```php
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Xdr\XdrSignerKey;

// Set home domain and thresholds
$operation = (new SetOptionsOperationBuilder())
    ->setHomeDomain('example.com')
    ->setLowThreshold(1)
    ->setMediumThreshold(2)
    ->setHighThreshold(3)
    ->setMasterKeyWeight(1)
    ->build();

// Add a signer
$signerKeyPair = KeyPair::fromAccountId($additionalSignerAccountId);
$signerKey = $signerKeyPair->getXdrSignerKey();

$addSigner = (new SetOptionsOperationBuilder())
    ->setSigner($signerKey, 1) // weight 1
    ->build();
```

**Methods:** `setInflationDestination()`, `setClearFlags()`, `setSetFlags()`, `setMasterKeyWeight()`, `setLowThreshold()`, `setMediumThreshold()`, `setHighThreshold()`, `setHomeDomain()`, `setSigner(XdrSignerKey, int)`

### Change Trust

Creates, updates, or removes a trustline for an issued asset.

```php
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\Asset;

$asset = Asset::createNonNativeAsset('USD', $issuerAccountId);

// Create trustline (default limit = max)
$createTrust = (new ChangeTrustOperationBuilder($asset))->build();

// Set custom limit
$limitedTrust = (new ChangeTrustOperationBuilder($asset, '1000.0'))->build();

// Remove trustline (limit = 0)
$removeTrust = (new ChangeTrustOperationBuilder($asset, '0'))->build();
```

**Parameters:** `Asset $asset`, `?string $limit = null`

### Allow Trust (Deprecated)

Authorizes or deauthorizes a trustline. Use SetTrustLineFlags instead for new code.

```php
use Soneso\StellarSDK\AllowTrustOperationBuilder;

$operation = (new AllowTrustOperationBuilder(
    $trustorAccountId,     // account that created the trustline
    'USD',                 // asset code
    true,                  // authorized
    false                  // authorizedToMaintainLiabilities
))->build();
```

**Parameters:** `string $trustor`, `string $assetCode`, `bool $authorized`, `bool $authorizedToMaintainLiabilities`

### Account Merge

Merges the source account into a destination, transferring all remaining XLM.

```php
use Soneso\StellarSDK\AccountMergeOperationBuilder;

$operation = (new AccountMergeOperationBuilder(
    $destinationAccountId
))->build();
```

**Parameters:** `string $destinationAccountId`

### Manage Data

Sets or deletes a data entry (key-value pair) on an account.

```php
use Soneso\StellarSDK\ManageDataOperationBuilder;

// Set data entry
$setData = (new ManageDataOperationBuilder('config_key', 'config_value'))->build();

// Delete data entry (null value)
$deleteData = (new ManageDataOperationBuilder('config_key', null))->build();
```

**Parameters:** `string $key`, `?string $value = null`

### Bump Sequence

Sets the source account sequence number to a higher value.

```php
use Soneso\StellarSDK\BumpSequenceOperationBuilder;
use phpseclib3\Math\BigInteger;

$bumpTo = new BigInteger('1234567890');
$operation = (new BumpSequenceOperationBuilder($bumpTo))->build();
```

**Parameters:** `BigInteger $bumpTo`

---

## Claimable Balance Operations

### Create Claimable Balance

Creates a balance that specified claimants can claim under defined conditions.

```php
use Soneso\StellarSDK\CreateClaimableBalanceOperationBuilder;
use Soneso\StellarSDK\Claimant;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Xdr\XdrClaimPredicate;
use Soneso\StellarSDK\Xdr\XdrClaimPredicateType;

// Unconditional claimant
$unconditionalPredicate = new XdrClaimPredicate(
    new XdrClaimPredicateType(XdrClaimPredicateType::UNCONDITIONAL)
);
$claimant = new Claimant($claimantAccountId, $unconditionalPredicate);

$operation = (new CreateClaimableBalanceOperationBuilder(
    [$claimant],
    Asset::native(),
    '100.0'
))->build();
```

**Parameters:** `array<Claimant> $claimants`, `Asset $asset`, `string $amount`

### Claim Claimable Balance

Claims an existing claimable balance by its ID.

```php
use Soneso\StellarSDK\ClaimClaimableBalanceOperationBuilder;

$operation = (new ClaimClaimableBalanceOperationBuilder(
    $balanceId // e.g., '00000000...' hex string
))->build();
```

**Parameters:** `string $balanceId`

---

## Sponsorship Operations

### Begin Sponsoring Future Reserves

Begins sponsoring reserves for the specified account. Must be paired with EndSponsoringFutureReserves.

```php
use Soneso\StellarSDK\BeginSponsoringFutureReservesOperationBuilder;

$operation = (new BeginSponsoringFutureReservesOperationBuilder(
    $sponsoredAccountId
))->build();
```

**Parameters:** `string $sponsoredId`

### End Sponsoring Future Reserves

Ends the current sponsorship. Source must be the sponsored account.

```php
use Soneso\StellarSDK\EndSponsoringFutureReservesOperationBuilder;

$operation = (new EndSponsoringFutureReservesOperationBuilder())
    ->setSourceAccount($sponsoredAccountId)
    ->build();
```

**Parameters:** none (no constructor args)

### Revoke Sponsorship

Revokes sponsorship of various ledger entries. Uses method chaining to select the entry type.

```php
use Soneso\StellarSDK\RevokeSponsorshipOperationBuilder;
use Soneso\StellarSDK\Asset;

// Revoke account sponsorship
$revokeAccount = (new RevokeSponsorshipOperationBuilder())
    ->revokeAccountSponsorship($accountId)
    ->build();

// Revoke trustline sponsorship
$asset = Asset::createNonNativeAsset('USD', $issuerAccountId);
$revokeTrustline = (new RevokeSponsorshipOperationBuilder())
    ->revokeTrustlineSponsorship($accountId, $asset)
    ->build();

// Revoke data entry sponsorship
$revokeData = (new RevokeSponsorshipOperationBuilder())
    ->revokeDataSponsorship($accountId, 'data_key')
    ->build();

// Revoke offer sponsorship
$revokeOffer = (new RevokeSponsorshipOperationBuilder())
    ->revokeOfferSponsorship($accountId, $offerId)
    ->build();

// Revoke claimable balance sponsorship
$revokeBalance = (new RevokeSponsorshipOperationBuilder())
    ->revokeClaimableBalanceSponsorship($balanceId)
    ->build();

// Revoke signer sponsorship
$revokeSigner = (new RevokeSponsorshipOperationBuilder())
    ->revokeEd25519Signer($signerAccountId, $ed25519AccountId)
    ->build();
```

**Methods:** `revokeAccountSponsorship()`, `revokeTrustlineSponsorship()`, `revokeDataSponsorship()`, `revokeOfferSponsorship()`, `revokeClaimableBalanceSponsorship()`, `revokeEd25519Signer()`, `revokePreAuthTxSigner()`, `revokeSha256HashSigner()`

---

## Clawback Operations

### Clawback

Claws back an issued asset from an account. The source must be the asset issuer with `AUTH_CLAWBACK_ENABLED`.

```php
use Soneso\StellarSDK\ClawbackOperationBuilder;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\MuxedAccount;

$asset = Asset::createNonNativeAsset('USD', $issuerAccountId);
$from = MuxedAccount::fromAccountId($targetAccountId);

$operation = (new ClawbackOperationBuilder(
    $asset,
    $from,
    '50.0'
))->build();
```

**Parameters:** `Asset $asset`, `MuxedAccount $from`, `string $amount`

### Clawback Claimable Balance

Claws back a claimable balance. Source must be the issuer of the asset in the balance.

```php
use Soneso\StellarSDK\ClawbackClaimableBalanceOperationBuilder;

$operation = (new ClawbackClaimableBalanceOperationBuilder(
    $balanceId
))->build();
```

**Parameters:** `string $balanceId`

### Set Trust Line Flags

Sets or clears flags on a trustline. Replaces AllowTrust for fine-grained control.

```php
use Soneso\StellarSDK\SetTrustLineFlagsOperationBuilder;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Xdr\XdrTrustLineFlags;

$asset = Asset::createNonNativeAsset('USD', $issuerAccountId);

$operation = (new SetTrustLineFlagsOperationBuilder(
    $trustorAccountId,
    $asset,
    0,                                          // clearFlags
    XdrTrustLineFlags::AUTHORIZED_FLAG          // setFlags
))->build();
```

**Parameters:** `string $trustorId`, `Asset $asset`, `int $clearFlags`, `int $setFlags`

---

## Liquidity Pool Operations

### Liquidity Pool Deposit

Deposits assets into an AMM liquidity pool.

```php
use Soneso\StellarSDK\LiquidityPoolDepositOperationBuilder;
use Soneso\StellarSDK\Price;

$minPrice = Price::fromString('0.9');
$maxPrice = Price::fromString('1.1');

$operation = (new LiquidityPoolDepositOperationBuilder(
    $liquidityPoolId,   // hex pool ID
    '500.0',            // max amount of asset A
    '500.0',            // max amount of asset B
    $minPrice,
    $maxPrice
))->build();
```

**Parameters:** `string $liquidityPoolId`, `string $maxAmountA`, `string $maxAmountB`, `Price $minPrice`, `Price $maxPrice`

### Liquidity Pool Withdraw

Withdraws assets from an AMM liquidity pool by redeeming pool shares.

```php
use Soneso\StellarSDK\LiquidityPoolWithdrawOperationBuilder;

$operation = (new LiquidityPoolWithdrawOperationBuilder(
    $liquidityPoolId,
    '100.0',           // pool shares to redeem
    '90.0',            // min amount of asset A to receive
    '90.0'             // min amount of asset B to receive
))->build();
```

**Parameters:** `string $liquidityPoolId`, `string $amount`, `string $minAmountA`, `string $minAmountB`

---

## Soroban Operations

### Invoke Host Function

Invokes a Soroban smart contract function. Requires simulation before submission.

```php
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\Xdr\XdrSCVal;

$invokeFunction = new InvokeContractHostFunction(
    $contractId,                       // hex contract ID
    'transfer',                        // function name
    [                                  // arguments as XdrSCVal
        XdrSCVal::forAddress($fromAddress->toXdr()),
        XdrSCVal::forAddress($toAddress->toXdr()),
        XdrSCVal::forI128($amountParts),
    ]
);

$operation = (new InvokeHostFunctionOperationBuilder($invokeFunction))->build();
```

**Parameters:** `HostFunction $function`, `array<SorobanAuthorizationEntry> $auth = []`

See [Smart Contracts Guide](./soroban_contracts.md) for complete deployment and invocation workflows including simulation.

### Extend Footprint TTL

Extends the time-to-live of contract data entries. Used with Soroban transactions.

```php
use Soneso\StellarSDK\ExtendFootprintTTLOperationBuilder;

$operation = (new ExtendFootprintTTLOperationBuilder(
    100000  // extend to this many ledgers
))->build();
```

**Parameters:** `int $extendTo`

### Restore Footprint

Restores archived (expired) contract data entries.

```php
use Soneso\StellarSDK\RestoreFootprintOperationBuilder;

$operation = (new RestoreFootprintOperationBuilder())->build();
```

**Parameters:** none

---

## Operation Result Codes

### Transaction Result Codes

Common transaction-level errors returned after submission:

- `tx_success` -- all operations succeeded
- `tx_failed` -- one or more operations failed
- `tx_too_early` / `tx_too_late` -- outside time bounds
- `tx_bad_seq` -- sequence number mismatch
- `tx_bad_auth` -- invalid or missing signatures
- `tx_insufficient_balance` -- not enough XLM for fees
- `tx_insufficient_fee` -- fee below network minimum
- `tx_no_source_account` -- source account does not exist

### Error Handling Pattern

```php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;

$sdk = StellarSDK::getTestNetInstance();

try {
    $response = $sdk->submitTransaction($transaction);
    if ($response->isSuccessful()) {
        $txHash = $response->getHash();
    } else {
        $resultCodes = $response->getExtras()->getResultCodes();
        $txCode = $resultCodes->getTransactionResultCode();
        $opCodes = $resultCodes->getOperationsResultCodes();
        // Handle specific operation failures
    }
} catch (HorizonRequestException $e) {
    $statusCode = $e->getStatusCode();
    $horizonError = $e->getHorizonErrorResponse();
    // Handle HTTP-level errors (timeout, rate limit, server error)
}
```
