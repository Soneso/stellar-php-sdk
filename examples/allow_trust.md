
### Allow trust

In this example we will update the ```authorized``` flag of an existing trustline. This can only be called by the issuer of a trustline’s asset, and only when ```AUTHORIZATION REQUIRED``` (at the minimum) has been set on the issuer’s account.

The issuer can only clear the ```authorized``` flag if the issuer has the ```AUTH_REVOCABLE_FLAG``` set. Otherwise, the issuer can only set the authorized flag.

If the issuer clears the ```authorized``` flag, all offers owned by the trustor that are either selling type or buying type will be deleted. 

```php
 // Create two random key pairs, we will need them later for signing.
$issuerKeypair = KeyPair::random();
$trustorKeypair = KeyPair::random();

// Account Ids.
$issuerAccountId = $issuerKeypair->getAccountId();
$trustorAccountId = $trustorKeypair->getAccountId();

// Create trustor account.
FriendBot::fundTestAccount($trustorAccountId);

// Load the trustor account so that we can later create the trustline.
$trustorAccount =  $sdk->requestAccount($trustorAccountId);

// Create the issuer account.
$cao = (new CreateAccountOperationBuilder($issuerAccountId, "10"))->build();
$transaction = (new TransactionBuilder($trustorAccount))->addOperation($cao)->build();

// Sign the transaction.
$transaction->sign($trustorKeypair, Network::testnet());

// Submit the transaction to stellar.
$sdk->submitTransaction($transaction);

// Load the issuer account.
$issuerAccount = $sdk->requestAccount($issuerAccountId);

// Set up the flags on the isser account.
$sopb = new SetOptionsOperationBuilder();
$sopb->setSetFlags(3); // Auth required, auth revocable

// Build the transaction.
$transaction = (new TransactionBuilder($issuerAccount))->addOperation($sopb->build())->build();

// Sign the transaction.
$transaction->sign($issuerKeypair, Network::testnet());

// Submit the transaction to stellar.
$sdk->submitTransaction($transaction);

// Reload the issuer account to check the flags.
$issuerAccount = $sdk->requestAccount($issuerAccountId);
if ($issuerAccount->getFlags()->isAuthRequired()
    && $issuerAccount->getFlags()->isAuthRevocable()
    && !$issuerAccount->getFlags()->isAuthImmutable()) {
    print(PHP_EOL."issuer account flags correctly set");
}

// Define our custom asset.
$assetCode = "ASTRO";
$astroDollar = new AssetTypeCreditAlphaNum12($assetCode, $issuerAccountId);

// Build the trustline.
$limit = "10000";

// Build the operation.
$cto = (new ChangeTrustOperationBuilder($astroDollar, $limit))->build();

// Build the transaction.
$transaction = (new TransactionBuilder($trustorAccount))->addOperation($cto)->build();

// Sign the transaction.
$transaction->sign($trustorKeypair, Network::testnet());

// Submit the transaction to stellar.
$sdk->submitTransaction($transaction);

// Reload the trustor account to see if the trustline has been created.
$trustorAccount = $sdk->requestAccount($trustorAccountId);
foreach ($trustorAccount->getBalances() as $balance) {
    if ($balance->getAssetCode() == $assetCode) {
        print(PHP_EOL."trustline awailable");
        break;
    }
}

// Now lets try to send some custom asset funds to the trustor account.
// This should not work, because the issuer must authorize the trustline first.
$po = (new PaymentOperationBuilder($trustorAccountId, $astroDollar, "100"))->build();
$transaction = (new TransactionBuilder($issuerAccount))->addOperation($po)->build();

// Sign the transaction.
$transaction->sign($issuerKeypair, Network::testnet());

// Submit the transaction to stellar.
try {
    $response = $sdk->submitTransaction($transaction);
} catch (HorizonRequestException $e) {
    print(PHP_EOL."trustline is not authorized.");
}

// Now let's authorize the trustline.
// Build the allow trust operation. Set the authorized flag to 1.
$aop = (new AllowTrustOperationBuilder($trustorAccountId, $assetCode, 1, 0))->build(); // authorize
$transaction = (new TransactionBuilder($issuerAccount))->addOperation($aop)->build();

// Sign the transaction.
$transaction->sign($issuerKeypair, Network::testnet());

// Submit the transaction to stellar.
$response = $sdk->submitTransaction($transaction);

// Try again to send the payment. Should work now.
$po = (new PaymentOperationBuilder($trustorAccountId, $astroDollar, "100"))->build();
$transaction = (new TransactionBuilder($issuerAccount))->addOperation($po)->build();

// Sign the transaction.
$transaction->sign($issuerKeypair, Network::testnet());

// Submit the transaction to stellar.
$response = $sdk->submitTransaction($transaction);

if ($response->isSuccessful()) { // authorized.
    print(PHP_EOL."success - trustline is now authorized.");
}

// Now create an offer, to see if it will be deleted after we will remove the authorized flag.
$amountSelling = "100";
$price = "0.5";
$cpso = (new CreatePassiveSellOfferOperationBuilder($astroDollar, Asset::native(), $amountSelling, Price::fromString($price)))->build();
$transaction = (new TransactionBuilder($trustorAccount))->addOperation($cpso)->build();

// Sign the transaction.
$transaction->sign($trustorKeypair, Network::testnet());

// Submit the transaction to stellar.
$response = $sdk->submitTransaction($transaction);

// Check if the offer has been added.
$offersPage = $sdk->offers()->forAccount($trustorAccountId)->execute();
$offer = $offersPage->getOffers()->toArray()[0];

if ($offer->getBuying() == Asset::native()
    && $offer->getSelling() == $astroDollar) {
    print(PHP_EOL."offer found");
}

// Now lets remove the authorization. To do so, we set the authorized flag to 0.
// This should also delete the offer.
$aop = (new AllowTrustOperationBuilder($trustorAccountId, $assetCode, 0, 0))->build(); // not authorized
$transaction = (new TransactionBuilder($issuerAccount))->addOperation($aop)->build();

// Sign the transaction.
$transaction->sign($issuerKeypair, Network::testnet());

// Submit the transaction to stellar.
$sdk->submitTransaction($transaction);

// Check if the offer has been deleted.
$offersPage = $sdk->offers()->forAccount($trustorAccountId)->execute();

if ($offersPage->getOffers()->count() == 0) {
    print(PHP_EOL."success, offer has been deleted");
}

// Now, let's authorize the trustline again and then authorize it only to maintain liabilities.
$aop = (new AllowTrustOperationBuilder($trustorAccountId, $assetCode, 1, 0))->build(); // authorize
$transaction = (new TransactionBuilder($issuerAccount))->addOperation($aop)->build();

// Sign the transaction.
$transaction->sign($issuerKeypair, Network::testnet());

// Submit the transaction to stellar.
$sdk->submitTransaction($transaction);

// Create the offer again.
$cpso = (new CreatePassiveSellOfferOperationBuilder($astroDollar, Asset::native(), $amountSelling, Price::fromString($price)))->build();
$transaction = (new TransactionBuilder($trustorAccount))->addOperation($cpso)->build();

// Sign the transaction.
$transaction->sign($trustorKeypair, Network::testnet());

// Submit the transaction to stellar.
$sdk->submitTransaction($transaction);

// Check that the offer has been created.
$offersPage = $sdk->offers()->forAccount($trustorAccountId)->execute();

if ($offersPage->getOffers()->count() == 1) {
    print(PHP_EOL."offer has been created");
}

// Now let's deautorize the trustline but allow the trustor to maintain his offer.
// For this, we set the authorized flag to 2.
$aop = (new AllowTrustOperationBuilder($trustorAccountId, $assetCode, 0, 1))->build(); // authorized to maintain liabilities.
$transaction = (new TransactionBuilder($issuerAccount))->addOperation($aop)->build();

// Sign the transaction.
$transaction->sign($issuerKeypair, Network::testnet());

// Submit the transaction to stellar.
$sdk->submitTransaction($transaction);

// Load the offers to see if our offer is still there.
// Check that the offer has been created.
$offersPage = $sdk->offers()->forAccount($trustorAccountId)->execute();

if ($offersPage->getOffers()->count() == 1) {
    print(PHP_EOL."offer exists");
}

// Next, let's try to send some ASTRO to the trustor account.
// This should not work, since the trustline has been deauthorized before.
$po = (new PaymentOperationBuilder($trustorAccountId, $astroDollar, "100"))->build();
$transaction = (new TransactionBuilder($issuerAccount))->addOperation($po)->build();

// Sign the transaction.
$transaction->sign($issuerKeypair, Network::testnet());

try {
    // Submit the transaction to stellar.
    $sdk->submitTransaction($transaction);
} catch (HorizonRequestException $e) {
    print(PHP_EOL."payment correctly blocked.");
}
```
