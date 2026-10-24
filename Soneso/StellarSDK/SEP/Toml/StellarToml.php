<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Toml;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Soneso\StellarSDK\Constants\NetworkConstants;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\Util\UrlValidator;
use Yosymfony\Toml\Toml;

/**
 * Represents a parsed stellar.toml file according to SEP-1 specification.
 *
 * The stellar.toml file is used to provide a common place where the Internet
 * can find information about an organization's Stellar integration.
 *
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0001.md SEP-1 Stellar Info File
 * @version 2.7.0
 */
class StellarToml
{
    public ?GeneralInformation $generalInformation = null;
    public ?Documentation $documentation = null;
    public ?Principals $principals = null;
    public ?Currencies $currencies = null;
    public ?Validators $validators = null;

    /**
     * Creates a StellarToml instance from a TOML string.
     *
     * Parses the provided TOML content and populates the general information,
     * documentation, principals, currencies, and validators data.
     *
     * @param string $toml The TOML content to parse
     * @throws Exception If the TOML is malformed or a field has the wrong type
     */
    public function __construct(string $toml) {

        $object = Toml::Parse($toml, true);
        $this->generalInformation = new GeneralInformation();
        if (isset($object->VERSION)) {
            self::assignField($this->generalInformation, "version", $object->VERSION, "VERSION");
        }
        if (isset($object->NETWORK_PASSPHRASE)) {
            self::assignField($this->generalInformation, "networkPassphrase", $object->NETWORK_PASSPHRASE, "NETWORK_PASSPHRASE");
        }
        if (isset($object->FEDERATION_SERVER)) {
            self::assignField($this->generalInformation, "federationServer", $object->FEDERATION_SERVER, "FEDERATION_SERVER");
        }
        if (isset($object->AUTH_SERVER)) {
            self::assignField($this->generalInformation, "authServer", $object->AUTH_SERVER, "AUTH_SERVER");
        }
        if (isset($object->TRANSFER_SERVER)) {
            self::assignField($this->generalInformation, "transferServer", $object->TRANSFER_SERVER, "TRANSFER_SERVER");
        }
        if (isset($object->TRANSFER_SERVER_SEP0024)) {
            self::assignField($this->generalInformation, "transferServerSep24", $object->TRANSFER_SERVER_SEP0024, "TRANSFER_SERVER_SEP0024");
        }
        if (isset($object->KYC_SERVER)) {
            self::assignField($this->generalInformation, "kYCServer", $object->KYC_SERVER, "KYC_SERVER");
        }
        if (isset($object->WEB_AUTH_ENDPOINT)) {
            self::assignField($this->generalInformation, "webAuthEndpoint", $object->WEB_AUTH_ENDPOINT, "WEB_AUTH_ENDPOINT");
        }
        if (isset($object->WEB_AUTH_FOR_CONTRACTS_ENDPOINT)) {
            self::assignField($this->generalInformation, "webAuthForContractsEndpoint", $object->WEB_AUTH_FOR_CONTRACTS_ENDPOINT, "WEB_AUTH_FOR_CONTRACTS_ENDPOINT");
        }
        if (isset($object->WEB_AUTH_CONTRACT_ID)) {
            self::assignField($this->generalInformation, "webAuthContractId", $object->WEB_AUTH_CONTRACT_ID, "WEB_AUTH_CONTRACT_ID");
        }
        if (isset($object->SIGNING_KEY)) {
            self::assignField($this->generalInformation, "signingKey", $object->SIGNING_KEY, "SIGNING_KEY");
        }
        if (isset($object->HORIZON_URL)) {
            self::assignField($this->generalInformation, "horizonUrl", $object->HORIZON_URL, "HORIZON_URL");
        }
        if (isset($object->ACCOUNTS)) {
            self::assignField($this->generalInformation, "accounts", $object->ACCOUNTS, "ACCOUNTS");
        }
        if (isset($object->URI_REQUEST_SIGNING_KEY)) {
            self::assignField($this->generalInformation, "uriRequestSigningKey", $object->URI_REQUEST_SIGNING_KEY, "URI_REQUEST_SIGNING_KEY");
        }
        if (isset($object->DIRECT_PAYMENT_SERVER)) {
            self::assignField($this->generalInformation, "directPaymentServer", $object->DIRECT_PAYMENT_SERVER, "DIRECT_PAYMENT_SERVER");
        }
        if (isset($object->ANCHOR_QUOTE_SERVER)) {
            self::assignField($this->generalInformation, "anchorQuoteServer", $object->ANCHOR_QUOTE_SERVER, "ANCHOR_QUOTE_SERVER");
        }

        if (isset($object->DOCUMENTATION)) {
            $this->documentation = new Documentation();
            $documentationArr = self::requireTable($object->DOCUMENTATION, "DOCUMENTATION");
            if (array_key_exists("ORG_NAME", $documentationArr)) {
                self::assignField($this->documentation, "orgName", $documentationArr["ORG_NAME"], "ORG_NAME");
            }
            if (array_key_exists("ORG_DBA", $documentationArr)) {
                self::assignField($this->documentation, "orgDBA", $documentationArr["ORG_DBA"], "ORG_DBA");
            }
            if (array_key_exists("ORG_URL", $documentationArr)) {
                self::assignField($this->documentation, "orgUrl", $documentationArr["ORG_URL"], "ORG_URL");
            }
            if (array_key_exists("ORG_LOGO", $documentationArr)) {
                self::assignField($this->documentation, "orgLogo", $documentationArr["ORG_LOGO"], "ORG_LOGO");
            }
            if (array_key_exists("ORG_DESCRIPTION", $documentationArr)) {
                self::assignField($this->documentation, "orgDescription", $documentationArr["ORG_DESCRIPTION"], "ORG_DESCRIPTION");
            }
            if (array_key_exists("ORG_PHYSICAL_ADDRESS", $documentationArr)) {
                self::assignField($this->documentation, "orgPhysicalAddress", $documentationArr["ORG_PHYSICAL_ADDRESS"], "ORG_PHYSICAL_ADDRESS");
            }
            if (array_key_exists("ORG_PHYSICAL_ADDRESS_ATTESTATION", $documentationArr)) {
                self::assignField($this->documentation, "orgPhysicalAddressAttestation", $documentationArr["ORG_PHYSICAL_ADDRESS_ATTESTATION"], "ORG_PHYSICAL_ADDRESS_ATTESTATION");
            }
            if (array_key_exists("ORG_PHONE_NUMBER", $documentationArr)) {
                self::assignField($this->documentation, "orgPhoneNumber", $documentationArr["ORG_PHONE_NUMBER"], "ORG_PHONE_NUMBER");
            }
            if (array_key_exists("ORG_PHONE_NUMBER_ATTESTATION", $documentationArr)) {
                self::assignField($this->documentation, "orgPhoneNumberAttestation", $documentationArr["ORG_PHONE_NUMBER_ATTESTATION"], "ORG_PHONE_NUMBER_ATTESTATION");
            }
            if (array_key_exists("ORG_KEYBASE", $documentationArr)) {
                self::assignField($this->documentation, "orgKeybase", $documentationArr["ORG_KEYBASE"], "ORG_KEYBASE");
            }
            if (array_key_exists("ORG_TWITTER", $documentationArr)) {
                self::assignField($this->documentation, "orgTwitter", $documentationArr["ORG_TWITTER"], "ORG_TWITTER");
            }
            if (array_key_exists("ORG_GITHUB", $documentationArr)) {
                self::assignField($this->documentation, "orgGithub", $documentationArr["ORG_GITHUB"], "ORG_GITHUB");
            }
            if (array_key_exists("ORG_OFFICIAL_EMAIL", $documentationArr)) {
                self::assignField($this->documentation, "orgOfficialEmail", $documentationArr["ORG_OFFICIAL_EMAIL"], "ORG_OFFICIAL_EMAIL");
            }
            if (array_key_exists("ORG_SUPPORT_EMAIL", $documentationArr)) {
                self::assignField($this->documentation, "orgSupportEmail", $documentationArr["ORG_SUPPORT_EMAIL"], "ORG_SUPPORT_EMAIL");
            }
            if (array_key_exists("ORG_LICENSING_AUTHORITY", $documentationArr)) {
                self::assignField($this->documentation, "orgLicensingAuthority", $documentationArr["ORG_LICENSING_AUTHORITY"], "ORG_LICENSING_AUTHORITY");
            }
            if (array_key_exists("ORG_LICENSE_TYPE", $documentationArr)) {
                self::assignField($this->documentation, "orgLicenseType", $documentationArr["ORG_LICENSE_TYPE"], "ORG_LICENSE_TYPE");
            }
            if (array_key_exists("ORG_LICENSE_NUMBER", $documentationArr)) {
                self::assignField($this->documentation, "orgLicenseNumber", $documentationArr["ORG_LICENSE_NUMBER"], "ORG_LICENSE_NUMBER");
            }
        }

        if (isset($object->PRINCIPALS)) {
            $this->principals = new Principals();
            $principalsArr = self::requireArray($object->PRINCIPALS, "PRINCIPALS");
            foreach ($principalsArr as $pointOfContact) {
                $pointOfContact = self::requireArray($pointOfContact, "PRINCIPALS");
                $poc = new PointOfContact();
                if (array_key_exists("name", $pointOfContact)) {
                    self::assignField($poc, "name", $pointOfContact["name"], "PRINCIPALS.name");
                }
                if (array_key_exists("email", $pointOfContact)) {
                    self::assignField($poc, "email", $pointOfContact["email"], "PRINCIPALS.email");
                }
                if (array_key_exists("keybase", $pointOfContact)) {
                    self::assignField($poc, "keybase", $pointOfContact["keybase"], "PRINCIPALS.keybase");
                }
                if (array_key_exists("twitter", $pointOfContact)) {
                    self::assignField($poc, "twitter", $pointOfContact["twitter"], "PRINCIPALS.twitter");
                }
                if (array_key_exists("telegram", $pointOfContact)) {
                    self::assignField($poc, "telegram", $pointOfContact["telegram"], "PRINCIPALS.telegram");
                }
                if (array_key_exists("github", $pointOfContact)) {
                    self::assignField($poc, "github", $pointOfContact["github"], "PRINCIPALS.github");
                }
                if (array_key_exists("id_photo_hash", $pointOfContact)) {
                    self::assignField($poc, "idPhotoHash", $pointOfContact["id_photo_hash"], "PRINCIPALS.id_photo_hash");
                }
                if (array_key_exists("verification_photo_hash", $pointOfContact)) {
                    self::assignField($poc, "verificationPhotoHash", $pointOfContact["verification_photo_hash"], "PRINCIPALS.verification_photo_hash");
                }
                $this->principals->add($poc);
            }
        }

        if (isset($object->CURRENCIES)) {
            $this->currencies = new Currencies();
            $currenciesArr = self::requireArray($object->CURRENCIES, "CURRENCIES");
            foreach ($currenciesArr as $currency) {
                $this->currencies->add(self::currencyFromItem(self::requireArray($currency, "CURRENCIES")));
            }
        }

        if (isset($object->VALIDATORS)) {
            $this->validators = new Validators();
            $validatorsArr = self::requireArray($object->VALIDATORS, "VALIDATORS");
            foreach ($validatorsArr as $item) {
                $item = self::requireArray($item, "VALIDATORS");
                $validator = new Validator();
                if (array_key_exists("ALIAS", $item)) {
                    self::assignField($validator, "alias", $item["ALIAS"], "ALIAS");
                }
                if (array_key_exists("DISPLAY_NAME", $item)) {
                    self::assignField($validator, "displayName", $item["DISPLAY_NAME"], "DISPLAY_NAME");
                }
                if (array_key_exists("PUBLIC_KEY", $item)) {
                    self::assignField($validator, "publicKey", $item["PUBLIC_KEY"], "PUBLIC_KEY");
                }
                if (array_key_exists("HOST", $item)) {
                    self::assignField($validator, "host", $item["HOST"], "HOST");
                }
                if (array_key_exists("HISTORY", $item)) {
                    self::assignField($validator, "history", $item["HISTORY"], "HISTORY");
                }
                $this->validators->add($validator);
            }
        }
    }

    /**
     * Fetches and parses stellar.toml from a domain.
     *
     * Constructs the URL https://DOMAIN/.well-known/stellar.toml and retrieves
     * the stellar.toml file from the specified domain.
     *
     * @param string $domain The domain to fetch the stellar.toml from (without protocol)
     * @param Client|null $httpClient Optional HTTP client for testing or custom configuration
     * @return StellarToml The parsed stellar.toml file
     * @throws Exception If the stellar.toml file cannot be fetched or parsed
     */
    public static function fromDomain(string $domain, ?Client $httpClient = null) : StellarToml {
        UrlValidator::validateDomain($domain);
        $url = "https://" . $domain . "/.well-known/stellar.toml";
        $client = $httpClient;
        if ($client === null) {
            $client = new Client();
        }
        try {
            $request = new Request('GET', $url, RequestBuilder::HEADERS);
            $response = $client->send($request);
            if ($response->getStatusCode() != NetworkConstants::HTTP_OK) {
                throw new Exception("Stellar toml not found. Response status code ". $response->getStatusCode());
            }
            return new StellarToml((string)$response->getBody());
        } catch (GuzzleException $e) {
            throw new Exception("Stellar toml not found. ". $e->getMessage());
        }
    }

    /**
     * Loads currency data from a separate TOML file URL.
     *
     * Alternately to specifying a currency in its content, stellar.toml can link out
     * to a separate TOML file for the currency by specifying toml="https://DOMAIN/.well-known/CURRENCY.toml"
     * as the currency's only field. This method loads the currency data from that URL.
     *
     * @param string $toml The URL to the currency TOML file
     * @param Client|null $httpClient Optional HTTP client to use for the request. Default is Guzzle.
     * @return Currency The parsed currency data
     * @throws Exception If the currency TOML file cannot be fetched or parsed, has no fields, or has a field of the wrong type
     */
    public static function currencyFromUrl(string $toml, ?Client $httpClient = null) : Currency {
        UrlValidator::validateHttpsRequired($toml);
        if ($httpClient === null) {
            $httpClient = new Client();
        }
        try {
            $request = new Request('GET', $toml, RequestBuilder::HEADERS);
            $response = $httpClient->send($request);
            if ($response->getStatusCode() != NetworkConstants::HTTP_OK) {
                throw new Exception("Currency toml not found. Response status code ". $response->getStatusCode());
            }
            $item = Toml::Parse((string)$response->getBody());
            if (!is_array($item)) {
                throw new Exception("Invalid currency toml {$toml}: no fields");
            }
            return self::currencyFromItem($item);
        } catch (GuzzleException $e) {
            throw new Exception("Currency toml not found. ". $e->getMessage());
        }
    }

    /**
     * Constructs a Currency object from a TOML array item.
     *
     * Parses the currency fields from a TOML array and maps them to a Currency object.
     * This method is used internally by the constructor and currencyFromUrl method.
     *
     * @param array $item The TOML array containing currency fields
     * @return Currency The constructed currency object
     * @throws Exception If a field has an invalid type
     */
    public static function currencyFromItem(array $item) : Currency {
        $currency = new Currency();
        if (array_key_exists("toml", $item)) {
            self::assignField($currency, "toml", $item["toml"], "toml");
        }
        if (array_key_exists("code", $item)) {
            self::assignField($currency, "code", $item["code"], "code");
        }
        if (array_key_exists("code_template", $item)) {
            self::assignField($currency, "codeTemplate", $item["code_template"], "code_template");
        }
        if (array_key_exists("issuer", $item)) {
            self::assignField($currency, "issuer", $item["issuer"], "issuer");
        }
        if (array_key_exists("contract", $item)) {
            self::assignField($currency, "contract", $item["contract"], "contract");
        }
        if (array_key_exists("status", $item)) {
            self::assignField($currency, "status", $item["status"], "status");
        }
        if (array_key_exists("display_decimals", $item)) {
            self::assignField($currency, "displayDecimals", $item["display_decimals"], "display_decimals");
        }
        if (array_key_exists("name", $item)) {
            self::assignField($currency, "name", $item["name"], "name");
        }
        if (array_key_exists("desc", $item)) {
            self::assignField($currency, "desc", $item["desc"], "desc");
        }
        if (array_key_exists("conditions", $item)) {
            self::assignField($currency, "conditions", $item["conditions"], "conditions");
        }
        if (array_key_exists("image", $item)) {
            self::assignField($currency, "image", $item["image"], "image");
        }
        if (array_key_exists("fixed_number", $item)) {
            self::assignField($currency, "fixedNumber", $item["fixed_number"], "fixed_number");
        }
        if (array_key_exists("max_number", $item)) {
            self::assignField($currency, "maxNumber", $item["max_number"], "max_number");
        }
        if (array_key_exists("is_unlimited", $item)) {
            self::assignField($currency, "isUnlimited", $item["is_unlimited"], "is_unlimited");
        }
        if (array_key_exists("is_asset_anchored", $item)) {
            self::assignField($currency, "isAssetAnchored", $item["is_asset_anchored"], "is_asset_anchored");
        }
        if (array_key_exists("anchor_asset_type", $item)) {
            self::assignField($currency, "anchorAssetType", $item["anchor_asset_type"], "anchor_asset_type");
        }
        if (array_key_exists("anchor_asset", $item)) {
            self::assignField($currency, "anchorAsset", $item["anchor_asset"], "anchor_asset");
        }
        if (array_key_exists("attestation_of_reserve", $item)) {
            self::assignField($currency, "attestationOfReserve", $item["attestation_of_reserve"], "attestation_of_reserve");
        }
        if (array_key_exists("redemption_instructions", $item)) {
            self::assignField($currency, "redemptionInstructions", $item["redemption_instructions"], "redemption_instructions");
        }
        if (array_key_exists("collateral_addresses", $item)) {
            self::assignField($currency, "collateralAddresses", $item["collateral_addresses"], "collateral_addresses");
        }
        if (array_key_exists("collateral_address_messages", $item)) {
            self::assignField($currency, "collateralAddressMessages", $item["collateral_address_messages"], "collateral_address_messages");
        }
        if (array_key_exists("collateral_address_signatures", $item)) {
            self::assignField($currency, "collateralAddressSignatures", $item["collateral_address_signatures"], "collateral_address_signatures");
        }
        if (array_key_exists("regulated", $item)) {
            self::assignField($currency, "regulated", $item["regulated"], "regulated");
        }
        if (array_key_exists("approval_server", $item)) {
            self::assignField($currency, "approvalServer", $item["approval_server"], "approval_server");
        }
        if (array_key_exists("approval_criteria", $item)) {
            self::assignField($currency, "approvalCriteria", $item["approval_criteria"], "approval_criteria");
        }
        return $currency;
    }

    /**
     * Assigns a parsed value to a typed property; a type mismatch becomes an Exception that names the stellar.toml field.
     *
     * @throws Exception If the value has an invalid type
     */
    private static function assignField(object $target, string $property, mixed $value, string $field): void
    {
        try {
            $target->$property = $value;
        } catch (\TypeError $e) {
            throw new Exception("Invalid stellar.toml field {$field}: found " . get_debug_type($value), 0, $e);
        }
    }

    /**
     * @return array<mixed> The parsed table
     * @throws Exception If the value is not a table
     */
    private static function requireTable(mixed $value, string $field): array
    {
        $value = self::requireArray($value, $field);
        if ($value !== [] && array_is_list($value)) {
            throw new Exception("Invalid stellar.toml field {$field}: found list");
        }
        return $value;
    }

    /**
     * @return array<mixed> The parsed table or array
     * @throws Exception If the value is not an array
     */
    private static function requireArray(mixed $value, string $field): array
    {
        if (!is_array($value)) {
            throw new Exception("Invalid stellar.toml field {$field}: found " . get_debug_type($value));
        }
        return $value;
    }

    /**
     * @return GeneralInformation|null
     */
    public function getGeneralInformation(): ?GeneralInformation
    {
        return $this->generalInformation;
    }

    /**
     * @return Documentation|null
     */
    public function getDocumentation(): ?Documentation
    {
        return $this->documentation;
    }

    /**
     * @return Principals|null
     */
    public function getPrincipals(): ?Principals
    {
        return $this->principals;
    }

    /**
     * @return Currencies|null
     */
    public function getCurrencies(): ?Currencies
    {
        return $this->currencies;
    }

    /**
     * @return Validators|null
     */
    public function getValidators(): ?Validators
    {
        return $this->validators;
    }
}