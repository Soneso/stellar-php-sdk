
### Create passive sell offer

In this example we are going to create an offer to sell one asset for another, otherwise known as a "ask" order or “offer” on a traditional orderbook, _without taking a reverse offer of equal price_.

First we are going to prepare the example by creating a seller account, an issuer account and a trusted asset. Then, we send some custom asset funds to from the issuer account to the seller account so that the seller is able to offer them for sale. Then, we are going to create, modify and delete the passive sell offer.

```php
$issuerKeypair = KeyPair::random();
$sellerKeypair = KeyPair::random();

// Account Ids.
$issuerAccountId = $issuerKeypair->getAccountId();
$sellerAccountId = $sellerKeypair->getAccountId();

// Create the buyer account.
FriendBot::fundTestAccount($sellerAccountId);

// Create the issuer account.
$sellerAccount = $sdk->requestAccount($sellerAccountId);
$caOp = (new CreateAccountOperationBuilder($issuerAccountId, "10"))->build();
$transaction = (new TransactionBuilder($sellerAccount))->addOperation($caOp)->build();

// Sign the transaction.
$transaction->sign($sellerKeypair, Network::testnet());

// Submit the transaction to stellar.
$sdk->submitTransaction($transaction);

// Define our custom asset.
$marsDollar = new AssetTypeCreditAlphaNum4("MARS", $issuerAccountId);

// Let the seller account trust our issuer and custom asset.
$ctOp = (new ChangeTrustOperationBuilder($marsDollar, "10000"))->build();
$transaction = (new TransactionBuilder($sellerAccount))->addOperation($ctOp)->build();

// Sign the transaction.
$transaction->sign($sellerKeypair, Network::testnet());

// Submit the transaction to stellar.
$sdk->submitTransaction($transaction);

// Send a couple of custom asset MARS funds from the issuer to the seller account so that the seller can offer them
$paymentOp = (new PaymentOperationBuilder($sellerAccountId, $marsDollar, "2000"))->build();

$issuerAccount = $sdk->requestAccount($issuerAccountId);
$transaction = (new TransactionBuilder($issuerAccount))->addOperation($paymentOp)->build();

// Sign the transaction.
$transaction->sign($issuerKeypair, Network::testnet());

// Submit the transaction to stellar.
$sdk->submitTransaction($transaction);

// Create the offer.
// I want to sell 100 MARS for 50 XLM.
$amountSelling = "100";
$price = "0.5";

// Create the passive sell offer operation. Selling: 100 MARS for 50 XLM (price = 0.5 => Price of 1 unit of selling in terms of buying.)
$ms = (new ManageSellOfferOperationBuilder($marsDollar, Asset::native(), $amountSelling, $price))->build();

// Create the transaction.
$transaction = (new TransactionBuilder($sellerAccount))->addOperation($ms)->build();

// Sign the transaction.
$transaction->sign($sellerKeypair, Network::testnet());

// Submit the transaction to stellar.
$sdk->submitTransaction($transaction);

// Now let's load the offers of our account to see if the offer has been created.
$offersPage = $sdk->offers()->forAccount($sellerAccountId)->execute();
$offer = $offersPage->getOffers()->toArray()[0];

$buyingAssetCode = $offer->getBuying() instanceof AssetTypeCreditAlphaNum ? $offer->getBuying()->getCode() : "XLM";
$sellingAssetCode = $offer->getSelling() instanceof AssetTypeCreditAlphaNum ? $offer->getSelling()->getCode() : "XLM";

printf(PHP_EOL."offerId: %s - selling: %s %s buying: %s - price: %s", $offer->getOfferId(), $offer->getAmount(), $sellingAssetCode, $buyingAssetCode, $offer->getPrice());

// offerId: 16260716 - selling: 100.0000000 MARS buying: XLM price: 0.5000000
// Price of 1 unit of selling in terms of buying.

// Now let's modify our offer.
$offerId = $offer->getOfferId();

// New data.
$amountSelling = "150";
$price = "0.3";

// Build the manage sell offer operation
$ms = (new ManageSellOfferOperationBuilder($marsDollar, Asset::native(), $amountSelling, $price))->setOfferId($offerId)->build();

// Build the transaction.
$transaction = (new TransactionBuilder($sellerAccount))->addOperation($ms)->build();

// Sign the transaction.
$transaction->sign($sellerKeypair, Network::testnet());

// Submit the transaction to stellar.
$sdk->submitTransaction($transaction);

// Load the offer from stellar.
$offersPage = $sdk->offers()->forAccount($sellerAccountId)->execute();
$offer = $offersPage->getOffers()->toArray()[0];

$buyingAssetCode = $offer->getBuying() instanceof AssetTypeCreditAlphaNum ? $offer->getBuying()->getCode() : "XLM";
$sellingAssetCode = $offer->getSelling() instanceof AssetTypeCreditAlphaNum ? $offer->getSelling()->getCode() : "XLM";

printf(PHP_EOL."offerId: %s - selling: %s %s buying: %s - price: %s", $offer->getOfferId(), $offer->getAmount(), $sellingAssetCode, $buyingAssetCode, $offer->getPrice());
// offerId: 16252986 - selling: 150.0000000 MARS buying: XLM price: 0.3000000

// And now let's delete our offer
// To delete, we need to set the amount to 0.
$amountSelling = "0";

// Build the manage sell offer operation
$ms = (new ManageSellOfferOperationBuilder($marsDollar, Asset::native(), $amountSelling, $price))->setOfferId($offerId)->build();

// Build the transaction.
$transaction = (new TransactionBuilder($sellerAccount))->addOperation($ms)->build();

// Sign the transaction.
$transaction->sign($sellerKeypair, Network::testnet());

// Submit the transaction to stellar.
$sdk->submitTransaction($transaction);

// check if the offer has been deleted.
$offersPage = $sdk->offers()->forAccount($sellerAccountId)->execute();
if ($offersPage->getOffers()->count() == 0) {
    print(PHP_EOL."success");
}
```
