
### SEP-0007 - URI Scheme to facilitate delegated signing

URI Scheme to facilitate delegated signing is described in [SEP-0007](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0007.md). This Stellar Ecosystem Proposal introduces a URI Scheme that can be used to generate a URI that will serve as a request to sign a transaction. The URI (request) will typically be signed by the user’s trusted wallet where she stores her secret key(s).

This SDK provides utility features to facilitate the implementation of SEP-0007 in a php wallet or server. These features are implemented in the [URIScheme](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDK/SEP/URIScheme/URIScheme.php) class and are described below.

**Generate a transaction uri**

```php
public function generateSignTransactionURI(string $transactionEnvelopeXdrBase64,
                                           ?string $replace = null,
                                           ?string $callback = null,
                                           ?string $publicKey = null,
                                           ?string $chain = null,
                                           ?string $message = null,
                                           ?string $networkPassphrase = null,
                                           ?string $originDomain = null,
                                           ?string $signature = null) : string {
```
This function can be used to generate a URIScheme compliant URL to serve as a request to sign a transaction.

Example:

```php
$sdk = StellarSDK::getTestNetInstance();
$sourceAccount = $sdk->requestAccount($this->accountId);
$newHomeDomain = "www.soneso.com";

$setOptionsOperation = (new SetOptionsOperationBuilder())
    ->setSourceAccount($this->accountId)
    ->setHomeDomain($newHomeDomain)
    ->build();

$transaction = (new TransactionBuilder($sourceAccount))
    ->addOperation($setOptionsOperation)
    ->build();
    
$uriScheme = new URIScheme();
$url = $uriScheme->generateSignTransactionURI($transaction->toEnvelopeXdrBase64());
print($url);
// web+stellar:tx?xdr=AAAAAgAAAADNQvJCahsRijRFXMHgyGXdar95Wya9ONBFmFGORBZkWAAAAGQABwWpAAAAKwAAAAAAAAAAAAAAAQAAAAEAAAAAzULyQmobEYo0RVzB4Mhl3Wq%2FeVsmvTjQRZhRjkQWZFgAAAAFAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEAAAAOd3d3LnNvbmVzby5jb20AAAAAAAAAAAAAAAAAAA%3D%3D
```

**Generate a pay operation uri**

```php
public function generatePayOperationURI(string $destinationAccountId,
                                        ?string $amount = null,
                                        ?string $assetCode = null,
                                        ?string $assetIssuer = null,
                                        ?string $memo = null,
                                        ?string $memoType = null,
                                        ?string $callback = null,
                                        ?string $message = null,
                                        ?string $networkPassphrase = null,
                                        ?string $originDomain = null,
                                        ?string $signature = null) : string 
```

This function can be used to generate a URIScheme compliant URL to serve as a request to pay a specific address with a specific asset, regardless of the source asset used by the payer.

Example:

```php
$uriScheme = new URIScheme();
$url = $uriScheme->generatePayOperationURI($this->accountId, amount:"123.21",
    assetCode: "ANA", assetIssuer: "GC4HC3AXQDNAMURMHVGMLFGLQELEQBCE4GI7IOKEAWAKBXY7SXXWBTLV");

print($url);
//web+stellar:pay?destination=GDGUF4SCNINRDCRUIVOMDYGIMXOWVP3ZLMTL2OGQIWMFDDSECZSFQMQV&amount=123.21&asset_code=ANA&asset_issuer=GC4HC3AXQDNAMURMHVGMLFGLQELEQBCE4GI7IOKEAWAKBXY7SXXWBTLV
```

**Check if URI Scheme is valid**
```php
public function checkUIRSchemeIsValid(string $url) : bool  
```
Checks if the received SEP-0007 URL is valid; signature and domain must be present and correct for the signer's keypair.
Returns true if valid, otherwise throws the corresponding URISchemeError.

Example:

```php
$uriScheme = new URIScheme();
$url = $uriScheme->generateSignTransactionURI($transaction->toEnvelopeXdrBase64()) . $this->originDomainParam;
try {
    $uriScheme->checkUIRSchemeIsValid($url);
    // success
} catch (URISchemeError $e) {
    if (URISchemeError::missingSignature, $e->getCode()) {
        // handle error
    }
}
```

Possible URISchemeErrors are:

```php
const invalidSignature = 0;
const invalidOriginDomain = 1;
const missingOriginDomain = 2;
const missingSignature = 3;
const tomlNotFoundOrInvalid = 4;
const tomlSignatureMissing = 5;
```

**Sign URI**

```php
public function signURI(string $url, KeyPair $signerKeyPair) : string
```
Signs the URIScheme compliant SEP-0007 url with the signer's key pair. Returns the signed url having the signature parameter attached.
Be careful with this function, you should validate the url and ask the user for permission before using this function.

Example:

```php
print($url);
// web+stellar:tx?xdr=AAAAAgAAAADNQvJCahsRijRFXMHgyGXdar95Wya9ONBFmFGORBZkWAAAAGQABwWpAAAAKwAAAAAAAAAAAAAAAQAAAAEAAAAAzULyQmobEYo0RVzB4Mhl3Wq%2FeVsmvTjQRZhRjkQWZFgAAAAFAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEAAAAOd3d3LnNvbmVzby5jb20AAAAAAAAAAAAAAAAAAA%3D%3D&origin_domain=place.domain.com

$uriScheme = new URIScheme();
$url = $uriScheme->signURI($url, $signerKeyPair);
print($url);
// web+stellar:tx?xdr=AAAAAgAAAADNQvJCahsRijRFXMHgyGXdar95Wya9ONBFmFGORBZkWAAAAGQABwWpAAAAKwAAAAAAAAAAAAAAAQAAAAEAAAAAzULyQmobEYo0RVzB4Mhl3Wq%2FeVsmvTjQRZhRjkQWZFgAAAAFAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEAAAAOd3d3LnNvbmVzby5jb20AAAAAAAAAAAAAAAAAAA%3D%3D&origin_domain=place.domain.com&signature=bIZ53bPKkNe0OoNK8PGLTnzHS%2FBCMzXTvwv1mc4DWc0XC4%2Bp197AmUB%2FIPL1UZAega7cLYv7%2F%2FaflB7CLGqZCw%3D%3D
```

**Sign and submit transaction**

```php
public function signAndSubmitTransaction(string $url, KeyPair $signerKeyPair, ?Network $network = null) : SubmitUriSchemeTransactionResponse
```
Signs the given transaction and submits it to the callback url if available, otherwise it submits it to the stellar network.
Be careful with this function, you should validate the url and ask the user for permission before using this function.

Example:

```php
$uriScheme = new URIScheme();
$response = $uriScheme->signAndSubmitTransaction($url, $signerKeyPair, Network::testnet());
```

```SubmitUriSchemeTransactionResponse``` has two members: ```$submitTransactionResponse``` and ```$callBackResponse```. ```$submitTransactionResponse``` is filled if the transaction has been send to the stellar network. ```$callBackResponse``` is filled if the transaction has been sent to the callback.

```php
class SubmitUriSchemeTransactionResponse
{

    private ?SubmitTransactionResponse $submitTransactionResponse = null;
    private ?ResponseInterface $callBackResponse = null;
    
    //...
}
```

**Get parameter value**

```php
public function getParameterValue(string $parameterName, string $url): ?string 
```

Utility function that returns the value of the given url parameter from the specified SEP-0007 url.


**More examples**

You can find more examples in the [SEP-0007 Test Cases](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SEP007Test.php)