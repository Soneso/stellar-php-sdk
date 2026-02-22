# Advanced Features

Less common but important patterns.

## Multi-Signature Accounts

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\Signer;
use Soneso\StellarSDK\AbstractTransaction;

$sdk     = StellarSDK::getTestNetInstance();
$network = Network::testnet();

$primaryKeyPair   = KeyPair::fromSeed(getenv('PRIMARY_SEED'));
$secondaryKeyPair = KeyPair::random();
$primaryId        = $primaryKeyPair->getAccountId();

// Step 1: Add a signer and set thresholds IN A SINGLE TRANSACTION
// IMPORTANT: Always add signers and set thresholds together, never in separate
// transactions — otherwise setting thresholds first may lock you out.
$sourceAccount = $sdk->requestAccount($primaryId);

$setOptionsOp = (new SetOptionsOperationBuilder())
    ->setSigner(
        Signer::ed25519PublicKey($secondaryKeyPair), 1
    )
    ->setLowThreshold(1)
    ->setMediumThreshold(2) // payments require 2 signers
    ->setHighThreshold(2)
    ->setMasterKeyWeight(1) // master key weight = 1
    ->build();

$tx = (new TransactionBuilder($sourceAccount))
    ->addOperation($setOptionsOp)
    ->build();

$tx->sign($primaryKeyPair, $network);
$sdk->submitTransaction($tx);

// Verify signers (getSigners() returns AccountSignersResponse — use ->count() not count())
$acct = $sdk->requestAccount($primaryId);
$signers = $acct->getSigners();
echo 'Signer count: ' . $signers->count() . PHP_EOL;
foreach ($signers as $signer) {
    echo 'Signer: ' . $signer->getKey() . ' weight: ' . $signer->getWeight() . PHP_EOL;
}

// Step 2: Multi-sig payment (requires 2 signatures to meet medium threshold)
$sourceAccount = $sdk->requestAccount($primaryId);

$paymentTx = (new TransactionBuilder($sourceAccount))
    ->addOperation(
        (new \Soneso\StellarSDK\PaymentOperationBuilder('GDEST...', \Soneso\StellarSDK\Asset::native(), '50.00'))->build()
    )
    ->build();

// Share XDR with co-signer
$xdrToShare = $paymentTx->toEnvelopeXdrBase64();

// Each signer signs independently
$paymentTx->sign($primaryKeyPair, $network);
$paymentTx->sign($secondaryKeyPair, $network);

$response = $sdk->submitTransaction($paymentTx);
echo 'Multi-sig TX hash: ' . $response->getHash() . PHP_EOL;
```

## Fee Bump Transactions

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\FeeBumpTransactionBuilder;

$sdk     = StellarSDK::getTestNetInstance();
$network = Network::testnet();

// $innerTransaction is a signed Transaction that needs a fee bump
$feePayerKeyPair = KeyPair::fromSeed(getenv('FEE_PAYER_SEED'));

$feeBumpTx = (new FeeBumpTransactionBuilder($innerTransaction))
    ->setFeeAccount($feePayerKeyPair->getAccountId()) // string account ID, not KeyPair
    ->setBaseFee(500) // higher fee per operation in stroops
    ->build();

$feeBumpTx->sign($feePayerKeyPair, $network);

$response = $sdk->submitTransaction($feeBumpTx);
echo 'Fee bump TX hash: ' . $response->getHash() . PHP_EOL;

// Access inner transaction hash from the response
$innerTx = $response->getInnerTransactionResponse();
if ($innerTx !== null) {
    echo 'Inner TX hash: ' . $innerTx->getHash() . PHP_EOL;
}
```

## Sponsored Reserves

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\BeginSponsoringFutureReservesOperationBuilder;
use Soneso\StellarSDK\EndSponsoringFutureReservesOperationBuilder;
use Soneso\StellarSDK\CreateAccountOperationBuilder;

$sdk     = StellarSDK::getTestNetInstance();
$network = Network::testnet();

$sponsorKeyPair = KeyPair::fromSeed(getenv('SPONSOR_SEED'));
$newKeyPair     = KeyPair::random();
$sponsorId      = $sponsorKeyPair->getAccountId();

$sponsorAccount = $sdk->requestAccount($sponsorId);

// Sponsor pays reserves for the new account
$tx = (new TransactionBuilder($sponsorAccount))
    ->addOperation(
        (new BeginSponsoringFutureReservesOperationBuilder($newKeyPair->getAccountId()))->build()
    )
    ->addOperation(
        (new CreateAccountOperationBuilder($newKeyPair->getAccountId(), '0'))->build()
    )
    ->addOperation(
        (new EndSponsoringFutureReservesOperationBuilder())
            ->setSourceAccount($newKeyPair->getAccountId())
            ->build()
    )
    ->build();

// Both sponsor and new account must sign
$tx->sign($sponsorKeyPair, $network);
$tx->sign($newKeyPair, $network);

$response = $sdk->submitTransaction($tx);
echo 'Sponsored account created: ' . $newKeyPair->getAccountId() . PHP_EOL;

// Verify sponsorship
$sponsoredAccount = $sdk->requestAccount($newKeyPair->getAccountId());
echo 'Sponsored account exists with 0 XLM (reserves are sponsored)' . PHP_EOL;

$sponsorAccount = $sdk->requestAccount($sponsorId);
echo 'Sponsor numSponsoring: ' . $sponsorAccount->getNumSponsoring() . PHP_EOL;
echo 'Sponsored numSponsored: ' . $sponsoredAccount->getNumSponsored() . PHP_EOL;
```

## Liquidity Pools (AMM)

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\AssetTypePoolShare;
use Soneso\StellarSDK\LiquidityPoolDepositOperationBuilder;
use Soneso\StellarSDK\Price;

$sdk     = StellarSDK::getTestNetInstance();
$network = Network::testnet();

$keyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$account = $sdk->requestAccount($keyPair->getAccountId());

$assetA = Asset::native();
$assetB = Asset::createNonNativeAsset('USDC', 'GISSUER...');

// Step 1: Establish trustline to the pool share
$poolShareAsset = new AssetTypePoolShare(
    assetA: $assetA,
    assetB: $assetB,
);

$trustOp = (new ChangeTrustOperationBuilder($poolShareAsset))->build();

$tx = (new TransactionBuilder($account))
    ->addOperation($trustOp)
    ->build();

$tx->sign($keyPair, $network);
$sdk->submitTransaction($tx);

// Step 2: Deposit into the pool
$account = $sdk->requestAccount($keyPair->getAccountId()); // refresh sequence

$poolId = $poolShareAsset->getPoolId(); // compute pool ID from asset pair

$depositOp = (new LiquidityPoolDepositOperationBuilder(
    $poolId,
    '100.00', // max amount A
    '100.00', // max amount B
    new Price(1, 2),  // min price A/B (0.50)
    new Price(2, 1),  // max price A/B (2.00)
))->build();

$tx = (new TransactionBuilder($account))
    ->addOperation($depositOp)
    ->build();

$tx->sign($keyPair, $network);
$sdk->submitTransaction($tx);
echo 'Deposited to pool: ' . $poolId . PHP_EOL;
```

## Muxed Accounts

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Crypto\KeyPair;

// Muxed accounts embed a 64-bit ID within a Stellar account (M... addresses)
$baseKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$baseId      = $baseKeyPair->getAccountId();

// Create muxed account with user ID
$muxedAccount = new MuxedAccount($baseId, 12345);
$mAddress     = $muxedAccount->getAccountId(); // M... address

echo 'Muxed Address: ' . $mAddress . PHP_EOL;
echo 'Base Account: ' . $muxedAccount->getEd25519AccountId() . PHP_EOL;
echo 'Mux ID: ' . $muxedAccount->getMuxedId() . PHP_EOL;

// Use muxed accounts as payment destinations
// PaymentOperationBuilder accepts M... addresses
```

## Async Transaction Submission

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

// Submit without waiting for ingestion (returns immediately)
$asyncResponse = $sdk->submitAsyncTransaction($transaction);

echo 'Hash: ' . $asyncResponse->hash . PHP_EOL;
echo 'Status: ' . $asyncResponse->txStatus . PHP_EOL;
// Possible statuses: PENDING, DUPLICATE, TRY_AGAIN_LATER, ERROR
// Poll getTransaction() later to check final result
```
