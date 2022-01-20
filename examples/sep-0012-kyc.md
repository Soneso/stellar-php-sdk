
### SEP-0012 - KYCService

Helps clients to upload KYC (or other) information to anchors and other services in a standard way defined by [SEP-0012: KYC API](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md).




### Create a KYCService instance

**By providing the domain hosting the stellar.toml file**

```php
$kycService = KYCService::fromDomain("place.domain.com");
```

This will automatically load and parse the stellar.toml file. It will then create the KYCService instance by using the kyc server url provided in the stellar.toml file. If no kyc server url is provided, than it will use the transfer server url to create the instance.

**Or by providing the service url**

Alternatively one can create a KYCService instance by providing the kyc service url directly via the constructor:

```php
$kycService = new KYCService("http://api.stellar-anchor.org/kyc");
```



### Get customer info

This endpoint (described [here](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#customer-get)) allows clients to:

1. Fetch the fields the server requires in order to register a new customer via a PUT /customer request or
2. Check the status of a customer that may already be registered

With the php sdk you can use the ```getCustomerInfo``` method of your ```KYCService``` instance to get the info:

```php
$request = new GetCustomerInfoRequest();
$request->jwt = $jwtToken; // token received from stellar web auth - sep-0010
$request->id = $customerId; // if customer already exists

$response = $kycService->getCustomerInfo($request);
print($response->getStatus());
```



### Upload customer info to an anchor

This endpoint (described [here](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#customer-put)) allows clients to upload customer information to an anchor in an authenticated and idempotent fashion. With the php sdk you can use the ```putCustomerInfo``` method of your ```KYCService``` instance to upload the data:

```php
$naturalPersonFields = new NaturalPersonKYCFields();
$naturalPersonFields->firstName = "John";
$naturalPersonFields->lastName = "Doe";
$naturalPersonFields->mobileNumber = "(718) 454-7453";
$naturalPersonFields->photoIdBack = $idBackImgData;

$kycFields = new StandardKYCFields();
$kycFields->naturalPersonKYCFields = $naturalPersonFields;
// ...

$request = new PutCustomerInfoRequest();
$request->jwt = $jwtToken; // token received from stellar web auth - sep-0010
$request->KYCFields = $kycFields;

$response = $kycService->putCustomerInfo($request);
print($response->getId());
```



### Customer verification

This endpoint (described [here](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#customer-put-verification)) allows servers to accept data values, usually confirmation codes, that verify a previously provided customer data field, such as mobile_number or email_address. With the php sdk you can use the ```putCustomerVerification``` method of your ```KYCService``` instance to send the data values:

```php
$request = new PutCustomerVerificationRequest();
$request->jwt = $jwtToken; // token received from stellar web auth - sep-0010

$fields = array();
$fields["id"] = $customerId;
$fields["mobile_number_verification"] = "2735021";
$request->verificationFields = $fields;

$response = $kycService->putCustomerVerification($request);
print($response->getStatus());
```



### Delete customer

This endpoint (described [here](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#customer-delete)) allows clients to delete all personal information that the anchor has stored about a given customer. With the php sdk you can use the ```deleteCustomer``` method of your ```KYCService``` instance to delete the data:

```php
$response = $kycService->deleteCustomer($accountId, $jwtToken);
print($response->getStatusCode());
```



### Customer callback url

This endpoint (described [here](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#customer-callback-put)) allows the wallet to provide a callback URL to the anchor. Whenever the user's status field changes, the anchor will issue a POST request to the callback URL. With the php sdk you can use the ```putCustomerCallback``` method of your ```KYCService``` instance to set the callback url:

```php
$request = new PutCustomerCallbackRequest();
$request->jwt = $jwtToken; // token received from stellar web auth - sep-0010
$request->url = "https://qxd-wallet.com/ccup";

$response = $kycService->putCustomerCallback($request);
print($response->getStatusCode());
```



### Further readings

For more info, see also the class documentation of  ```KYCService```  and the sdk's sep-0012 test cases.

