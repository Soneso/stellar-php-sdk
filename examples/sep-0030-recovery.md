
# SEP-0030 - Account Recovery: multi-party recovery of Stellar accounts

Enables an individual (e.g., a user or wallet) to regain access to a Stellar account as defined by
[SEP-0030: Account Recovery](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0030.md).


## Create a RecoveryService instance

**By providing the recovery server url directly via the constructor:**

```php
$service = new RecoveryService("http://api.stellar-test.org/recovery");
```

## Register an account

```php
$request = new SEP30Request([$senderIdentity, $receiverIdentity]);
$response = $service->registerAccount($addressA, $request, $jwtToken);
```

## Update identities for an account

```php
$request = new SEP30Request([$senderIdentity, $receiverIdentity]);
$response = $service->updateIdentitiesForAccount($addressA, $request, $jwtToken);
```

## Sign a transaction

```php
$transaction = "AAAAAHAHhQtYBh5F2zA6...";
$response = $service->signTransaction($addressA, $signingAddress, $transaction, $jwtToken);
```

## Get account details

```php
$response = $service->accountDetails($addressA, $jwtToken);
```

## Delete account

```php
$response = $service->deleteAccount($addressA, $jwtToken);
```


## List accounts

```php
$response = $service->accounts($this->jwtToken, 
after: "GA5TKKASNJZGZAP6FH65HO77CST7CJNYRTW4YPBNPXYMZAHHMTHDZKDQ");
```

### Further readings

SDK's [SEP-30 test cases](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SEP030Test.php).

