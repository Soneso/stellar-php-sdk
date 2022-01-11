
### SEP-0001 - stellar.toml

This examples shows how to obtain the parsed data from a ```stellar.toml``` file. For more details see: [SEP-0001](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0001.md).

The data can be parsed from a string or loaded and parsed from a given domain.

#### From string

To parse the data from a string you can use the default constructor of the ```StellarToml```  class.
```php
$toml = '
# Sample stellar.toml
VERSION="2.0.0"
# ...';

$stellarToml = new StellarToml($toml);
$generalInformation = $stellarToml->getGeneralInformation();
print($generalInformation->version);
```
After parsing, the ```StellarToml``` class provides information for (if available): ```generalInformation```, ```documentation```, ```principals```, ```currencies``` and ```validators```.

#### From domain

To load and parse the data from a domain you can use the fromDomain constructor of the StellarToml class. It automatically composes  the needed url. In the following example the data is loaded from: https://soneso.com/.well-known/stellar.toml - only the domain "soneso.com" has to be provided:
```php
$stellarToml = StellarToml::fromDomain("soneso.com");
$generalInformation = $stellarToml->getGeneralInformation();
//...
```
After parsing, the ```StellarToml``` class provides information for (if available): ```generalInformation```, ```documentation```, ```principals```, ```currencies``` and ```validators```.

#### Linked currency
Alternately to specifying a specific currency in it's content, ```stellar.toml``` can link out to a separate TOML file for a given currency by specifying ```toml="https://DOMAIN/.well-known/CURRENCY.toml"``` as the currency's only field.

```# Sample stellar.toml
VERSION="2.0.0"
#...
[[CURRENCIES]]
toml="https://soneso.com/.well-known/TESTC.toml"
#...
```
To load the data of this currency, you can use the static method:  ```StellarToml::currencyFromUrl(string $toml)```  - as shown in the example below:

```php
$stellarToml = StellarToml::fromDomain("soneso.com");
$currencies = $stellarToml->getCurrencies();
foreach ($currencies as $currency) {
  if ($currency->toml) {
    $linkedCurrency = StellarToml::currencyFromUrl($currency->toml);
    print($linkedCurrency->code);
  }
}
```
