### SEP-0010 - Stellar Web Authentication



Stellar Web Authentication is described in [SEP-0010](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0010.md). The SEP defines the standard way for clients such as wallets or exchanges to create authenticated web sessions on behalf of a user who holds a Stellar account. A wallet may want to authenticate with any web service which requires a Stellar account ownership verification, for example, to upload KYC information to an anchor in an authenticated way.

The following examples show how to use the php stellar sdk to create authenticated web sessions on behalf of a user who holds a Stellar account.



**Create a WebAuth instance**

by providing the domain hosting the stellar.toml file:

```php
$webauth = WebAuth::fromDomain("place.domain.com", Network::testnet());
```

**Request the jwtToken**

for the web session by providing the account id of the client/user and the signers of the client account to sign the challenge from the web auth server:

```php
$clientKeyPair = KeyPair::fromSeed($clientSecretSeed);
$jwtToken = $webAuth->jwtToken($clientKeyPair->getAccountId(),[clientKeyPair]);
```

That is all you need to do. The method ```jwtToken``` will request the challenge from the web auth server, validate it, sign it on behalf of the user and send it back to the web auth server. The web auth server will than respond with the jwt token.



### Client Attribution support
The php sdk also provides client attribution support. To use it, pass the client domain and the client domain account key pair for signing:

```php
$clientKeyPair = KeyPair::fromSeed($clientSecretSeed);
$clientDomainAccountKeyPair = KeyPair::fromSeed($clientDomainAccountSecretSeed);
$jwtToken = $webAuth.jwtToken($clientKeyPair->getAccountId(),[$clientKeyPair],clientDomain:"place.client.com", clientDomainKeyPair: $clientDomainAccountKeyPair);
```
### More examples
You can find more examples in the test cases.