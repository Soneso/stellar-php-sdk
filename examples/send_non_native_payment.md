
### Send a non native ("IOM") payment

In this example we will send a non native payment (IOM - a custom token) from a sender stellar account to a receiver stellar account.

To be able to send the funds, both accounts must trust the issuer and token. For this we will create the corresponding trustlines first.

Then we need to send some IOM from the issuer to the sender so that the sender can send IOM to the receiver in the next step.

At the end, the sender sends 200 IOM (non native payment) to the receiver.

```php
$sdk = StellarSDK::getTestNetInstance();

// Create the key pairs of issuer, sender and receiver from their secret seeds. We will need them for signing.
$issuerKeyPair = KeyPair::fromSeed("SD3UQ2IRQSC4VM4CPMRD6H6EOGSZWUTX3K3DP6GJRBDPL4UL5RQIQTD4");
$senderKeyPair = KeyPair::fromSeed("SCYMI7XBFZUMKNTTGZSEJWWDMR4KA2QTDPUKTAMIDI353NFHA3MMQST7");
$receiverKeyPair = KeyPair::fromSeed("SD3ZC4QWYNXL2XIK4GZXGOTZU5CTD2XRWSCAW4GJYUBOKZQ4GQASYAWG");

// Account Ids.
$issuerAccountId = $issuerKeyPair->getAccountId();
$senderAccountId = $senderKeyPair->getAccountId();
$receiverAccountId = $receiverKeyPair->getAccountId();

// Define the custom asset/token issued by the issuer account.
$iomAsset = new AssetTypeCreditAlphaNum4("IOM", $issuerAccountId);

// Prepare a change trust operation so that we can create trustlines for both, the sender and receiver.
// Both need to trust the IOM asset issued by the issuer account so that they can hold the token/asset.
// Trust limit is 10000.
$chOp = (new ChangeTrustOperationBuilder($iomAsset, "10000"))->build();

// Load the sender account data from the stellar network so that we have it's current sequence number.
$sender = $sdk->requestAccount($senderAccountId);

// Build the transaction for the trustline (sender trusts custom asset).
$transaction = (new TransactionBuilder($sender))->addOperation($chOp)->build();

// The sender signs the transaction.
$transaction->sign($senderKeyPair, Network::testnet());

// Submit the transaction to stellar.
$sdk->submitTransaction($transaction);

// Load the receiver account so that we have it's current sequence number.
$receiver = $sdk->requestAccount($receiverAccountId);

// Build the transaction for the trustline (receiver trusts custom asset).
$transaction = (new TransactionBuilder($receiver))->addOperation($chOp )->build();

// The receiver signs the transaction.
$transaction->sign($receiverKeyPair, Network::testnet());

// Submit the transaction.
$sdk->submitTransaction($transaction);

// Load the issuer account so that we have it's current sequence number.
$issuer = $sdk->requestAccount($issuerAccountId);

// Send 500 IOM non native payment from issuer to sender.
$paymentOperation = (new PaymentOperationBuilder($senderAccountId, $iomAsset, "500"))->build();
$transaction = (new TransactionBuilder($issuer))->addOperation($paymentOperation)->build();

// The issuer signs the transaction.
$transaction->sign($issuerKeyPair, Network::testnet());

// Submit the transaction.
$sdk->submitTransaction($transaction);

// The sender now has 500 IOM and can send to the receiver.
// Send 200 IOM (non native payment) from sender to receiver.
$paymentOperation = (new PaymentOperationBuilder($receiverAccountId, $iomAsset, "200"))->build();
$transaction = (new TransactionBuilder($sender))->addOperation($paymentOperation)->build();

// The sender signs the transaction.
$transaction->sign($senderKeyPair, Network::testnet());

// Submit the transaction to stellar.
$sdk->submitTransaction($transaction);

// Check that the receiver obtained the 200 IOM.
$receiver = $sdk->requestAccount($receiverAccountId);
foreach ($receiver->getBalances() as $balance) {
    if ($balance->getAssetType() != Asset::TYPE_NATIVE
        && $balance->getAssetCode() == "IOM"
        && floatval($balance->getBalance()) > 199) {
        print("received IOM payment");
        break;
    }
}
```
