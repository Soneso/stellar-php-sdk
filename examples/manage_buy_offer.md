
### Manage buy offer

In this example we are going to create, update, and delete an offer to buy one asset for another, otherwise known as a "bid" order on a traditional orderbook.

First we are going to prepare the example by creating an account and a trusted asset, so that we can make a buy offer for it. Then, we are going to create, modify and delete the offer.

```php
// Prepare two random keypairs, we will need the later for signing.
$issuerKeypair = KeyPair::random();
$buyerKeypair = KeyPair::random();

// Account Ids.
$issuerAccountId = $issuerKeypair->getAccountId();
$buyerAccountId = $buyerKeypair->getAccountId();

// Create the buyer account.
FriendBot::fundTestAccount($buyerAccountId);

// Create the issuer account.
$buyerAccount = $sdk->requestAccount($buyerAccountId);
$caOp = (new CreateAccountOperationBuilder($issuerAccountId, "10"))->build();
$transaction = (new TransactionBuilder($buyerAccount))->addOperation($caOp)->build();

// Sign the transaction.
$transaction->sign($buyerKeypair, Network::testnet());

// Submit the transaction to stellar.
$sdk->submitTransaction($transaction);

// Define an asset.
$astroDollar = new AssetTypeCreditAlphaNum12("ASTRO", $issuerAccountId);

// Create a trustline for the buyer account.
$ctOp = (new ChangeTrustOperationBuilder($astroDollar, "10000"))->build();
$transaction = (new TransactionBuilder($buyerAccount))->addOperation($ctOp)->build();

// Sign the transaction.
$transaction->sign($buyerKeypair, Network::testnet());

// Submit the transaction to stellar.
$sdk->submitTransaction($transaction);

// Create the offer.
// I want to pay max. 50 XLM for 100 ASTRO.
$amountBuying = "100"; // Want to buy 100 ASTRO
$price = "0.5"; // Price of 1 unit of buying in terms of selling

// Create the manage buy offer operation. Buying: 100 ASTRO for 50 XLM (price = 0.5 => Price of 1 unit of buying in terms of selling)
$ms = (new ManageBuyOfferOperationBuilder(Asset::native(), $astroDollar, $amountBuying, $price))->build();

// Create the transaction.
$transaction = (new TransactionBuilder($buyerAccount))->addOperation($ms)->build();

// Sign the transaction.
$transaction->sign($buyerKeypair, Network::testnet());

// Submit the transaction to stellar.
$sdk->submitTransaction($transaction);

// Now let's load the offers of our account to see if the offer has been created.
$offersPage = $sdk->offers()->forAccount($buyerAccountId)->execute();
$offer = $offersPage->getOffers()->toArray()[0];

$buyingAssetCode = $offer->getBuying() instanceof AssetTypeCreditAlphaNum ? $offer->getBuying()->getCode() : "XLM";
$sellingAssetCode = $offer->getSelling() instanceof AssetTypeCreditAlphaNum ? $offer->getSelling()->getCode() : "XLM";

printf(PHP_EOL."offerId: %s - buying: %s - selling: %s %s price: %s", $offer->getOfferId(), $buyingAssetCode, $offer->getAmount(), $sellingAssetCode, $offer->getPrice());

// offerId: 16245277 - buying: ASTRO - selling: 50.0000000 XLM price: 2.0000000
// As you can see, the price is stored here as "Price of 1 unit of selling in terms of buying".

// Now lets modify our offer.
$offerId = $offer->getOfferId();

// New data.
$amountBuying = "150";
$price = "0.3";

// Build the manage buy offer operation
$ms = (new ManageBuyOfferOperationBuilder(Asset::native(), $astroDollar, $amountBuying, $price))->setOfferId($offerId)->build();

// Build the transaction.
$transaction = (new TransactionBuilder($buyerAccount))->addOperation($ms)->build();

// Sign the transaction.
$transaction->sign($buyerKeypair, Network::testnet());

// Submit the transaction to stellar.
$sdk->submitTransaction($transaction);

// Load the offer from stellar.
$offersPage = $sdk->offers()->forAccount($buyerAccountId)->execute();
$offer = $offersPage->getOffers()->toArray()[0];

$buyingAssetCode = $offer->getBuying() instanceof AssetTypeCreditAlphaNum ? $offer->getBuying()->getCode() : "XLM";
$sellingAssetCode = $offer->getSelling() instanceof AssetTypeCreditAlphaNum ? $offer->getSelling()->getCode() : "XLM";

printf(PHP_EOL."offerId: %s - buying: %s - selling: %s %s price: %s", $offer->getOfferId(), $buyingAssetCode, $offer->getAmount(), $sellingAssetCode, $offer->getPrice());
// offerId: 16245277 - buying: ASTRO - selling: 45.0000000 XLM price: 3.3333333

// And now let's delete our offer
// To delete, we need to set the amount to 0.
$amountBuying = "0";

// Create the operation
$ms = (new ManageBuyOfferOperationBuilder(Asset::native(), $astroDollar, $amountBuying, $price))->setOfferId($offerId)->build();

// Build the transaction.
$transaction = (new TransactionBuilder($buyerAccount))->addOperation($ms)->build();

// Sign the transaction.
$transaction->sign($buyerKeypair, Network::testnet());

// Submit the transaction to stellar.
$sdk->submitTransaction($transaction);

// check if the offer has been deleted.
$offersPage = $sdk->offers()->forAccount($buyerAccountId)->execute();
if($offersPage->getOffers()->count() == 0) {
    print(PHP_EOL."success");
}
```
