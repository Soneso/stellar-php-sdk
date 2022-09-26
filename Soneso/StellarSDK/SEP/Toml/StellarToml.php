<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Toml;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Yosymfony\Toml\Toml;

/// see: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0001.md
/// Supported version: 2.5.0
class StellarToml
{
    private ?GeneralInformation $generalInformation = null;
    private ?Documentation $documentation = null;
    private ?Principals $principals = null;
    private ?Currencies $currencies = null;
    private ?Validators $validators = null;

    public function __construct(string $toml) {

        $object = Toml::Parse($toml, true);
        $this->generalInformation = new GeneralInformation();
        if (isset($object->VERSION)) {
            $this->generalInformation->version = $object->VERSION;
        }
        if (isset($object->NETWORK_PASSPHRASE)) {
            $this->generalInformation->networkPassphrase = $object->NETWORK_PASSPHRASE;
        }
        if (isset($object->FEDERATION_SERVER)) {
            $this->generalInformation->federationServer = $object->FEDERATION_SERVER;
        }
        if (isset($object->AUTH_SERVER)) {
            $this->generalInformation->authServer = $object->AUTH_SERVER;
        }
        if (isset($object->TRANSFER_SERVER)) {
            $this->generalInformation->transferServer = $object->TRANSFER_SERVER;
        }
        if (isset($object->TRANSFER_SERVER_SEP0024)) {
            $this->generalInformation->transferServerSep24 = $object->TRANSFER_SERVER_SEP0024;
        }
        if (isset($object->KYC_SERVER)) {
            $this->generalInformation->kYCServer = $object->KYC_SERVER;
        }
        if (isset($object->WEB_AUTH_ENDPOINT)) {
            $this->generalInformation->webAuthEndpoint = $object->WEB_AUTH_ENDPOINT;
        }
        if (isset($object->SIGNING_KEY)) {
            $this->generalInformation->signingKey = $object->SIGNING_KEY;
        }
        if (isset($object->HORIZON_URL)) {
            $this->generalInformation->horizonUrl = $object->HORIZON_URL;
        }
        if (isset($object->ACCOUNTS)) {
            $this->generalInformation->accounts = $object->ACCOUNTS;
        }
        if (isset($object->URI_REQUEST_SIGNING_KEY)) {
            $this->generalInformation->uriRequestSigningKey = $object->URI_REQUEST_SIGNING_KEY;
        }
        if (isset($object->DIRECT_PAYMENT_SERVER)) {
            $this->generalInformation->directPaymentServer = $object->DIRECT_PAYMENT_SERVER;
        }
        if (isset($object->ANCHOR_QUOTE_SERVER)) {
            $this->generalInformation->anchorQuoteServer = $object->ANCHOR_QUOTE_SERVER;
        }

        if (isset($object->DOCUMENTATION)) {
            $this->documentation = new Documentation();
            $documentationArr = $object->DOCUMENTATION;
            if (array_key_exists("ORG_NAME", $documentationArr)) {
                $this->documentation->orgName = $documentationArr["ORG_NAME"];
            }
            if (array_key_exists("ORG_DBA", $documentationArr)) {
                $this->documentation->orgDBA = $documentationArr["ORG_DBA"];
            }
            if (array_key_exists("ORG_URL", $documentationArr)) {
                $this->documentation->orgUrl = $documentationArr["ORG_URL"];
            }
            if (array_key_exists("ORG_LOGO", $documentationArr)) {
                $this->documentation->orgLogo = $documentationArr["ORG_LOGO"];
            }
            if (array_key_exists("ORG_DESCRIPTION", $documentationArr)) {
                $this->documentation->orgDescription = $documentationArr["ORG_DESCRIPTION"];
            }
            if (array_key_exists("ORG_PHYSICAL_ADDRESS", $documentationArr)) {
                $this->documentation->orgPhysicalAddress = $documentationArr["ORG_PHYSICAL_ADDRESS"];
            }
            if (array_key_exists("ORG_PHYSICAL_ADDRESS_ATTESTATION", $documentationArr)) {
                $this->documentation->orgPhysicalAddressAttestation = $documentationArr["ORG_PHYSICAL_ADDRESS_ATTESTATION"];
            }
            if (array_key_exists("ORG_PHONE_NUMBER", $documentationArr)) {
                $this->documentation->orgPhoneNumber = $documentationArr["ORG_PHONE_NUMBER"];
            }
            if (array_key_exists("ORG_PHONE_NUMBER_ATTESTATION", $documentationArr)) {
                $this->documentation->orgPhoneNumberAttestation = $documentationArr["ORG_PHONE_NUMBER_ATTESTATION"];
            }
            if (array_key_exists("ORG_KEYBASE", $documentationArr)) {
                $this->documentation->orgKeybase = $documentationArr["ORG_KEYBASE"];
            }
            if (array_key_exists("ORG_TWITTER", $documentationArr)) {
                $this->documentation->orgTwitter = $documentationArr["ORG_TWITTER"];
            }
            if (array_key_exists("ORG_GITHUB", $documentationArr)) {
                $this->documentation->orgGithub = $documentationArr["ORG_GITHUB"];
            }
            if (array_key_exists("ORG_OFFICIAL_EMAIL", $documentationArr)) {
                $this->documentation->orgOfficialEmail = $documentationArr["ORG_OFFICIAL_EMAIL"];
            }
            if (array_key_exists("ORG_SUPPORT_EMAIL", $documentationArr)) {
                $this->documentation->orgSupportEmail = $documentationArr["ORG_SUPPORT_EMAIL"];
            }
            if (array_key_exists("ORG_LICENSING_AUTHORITY", $documentationArr)) {
                $this->documentation->orgLicensingAuthority = $documentationArr["ORG_LICENSING_AUTHORITY"];
            }
            if (array_key_exists("ORG_LICENSE_TYPE", $documentationArr)) {
                $this->documentation->orgLicenseType = $documentationArr["ORG_LICENSE_TYPE"];
            }
            if (array_key_exists("ORG_LICENSE_NUMBER", $documentationArr)) {
                $this->documentation->orgLicenseNumber = $documentationArr["ORG_LICENSE_NUMBER"];
            }
        }

        if (isset($object->PRINCIPALS)) {
            $this->principals = new Principals();
            $principalsArr = $object->PRINCIPALS;
            foreach ($principalsArr as $pointOfContact) {
                $poc = new PointOfContact();
                if (array_key_exists("name", $pointOfContact)) {
                    $poc->name = $pointOfContact["name"];
                }
                if (array_key_exists("email", $pointOfContact)) {
                    $poc->email = $pointOfContact["email"];
                }
                if (array_key_exists("keybase", $pointOfContact)) {
                    $poc->keybase = $pointOfContact["keybase"];
                }
                if (array_key_exists("twitter", $pointOfContact)) {
                    $poc->twitter = $pointOfContact["twitter"];
                }
                if (array_key_exists("telegram", $pointOfContact)) {
                    $poc->telegram = $pointOfContact["telegram"];
                }
                if (array_key_exists("github", $pointOfContact)) {
                    $poc->github = $pointOfContact["github"];
                }
                if (array_key_exists("id_photo_hash", $pointOfContact)) {
                    $poc->idPhotoHash = $pointOfContact["id_photo_hash"];
                }
                if (array_key_exists("verification_photo_hash", $pointOfContact)) {
                    $poc->verificationPhotoHash = $pointOfContact["verification_photo_hash"];
                }
                $this->principals->add($poc);
            }
        }

        if (isset($object->CURRENCIES)) {
            $this->currencies = new Currencies();
            $currenciesArr = $object->CURRENCIES;
            foreach ($currenciesArr as $currency) {
                $this->currencies->add(self::currencyFromItem($currency));
            }
        }

        if (isset($object->VALIDATORS)) {
            $this->validators = new Validators();
            $validatorsArr = $object->VALIDATORS;
            foreach ($validatorsArr as $item) {
                $validator = new Validator();
                if (array_key_exists("ALIAS", $item)) {
                    $validator->alias = $item["ALIAS"];
                }
                if (array_key_exists("DISPLAY_NAME", $item)) {
                    $validator->displayName = $item["DISPLAY_NAME"];
                }
                if (array_key_exists("PUBLIC_KEY", $item)) {
                    $validator->publicKey = $item["PUBLIC_KEY"];
                }
                if (array_key_exists("HOST", $item)) {
                    $validator->host = $item["HOST"];
                }
                if (array_key_exists("HISTORY", $item)) {
                    $validator->history = $item["HISTORY"];
                }
                $this->validators->add($validator);
            }
        }
    }

    /**
     * @throws Exception
     */
    public static function fromDomain(string $domain, ?Client $httpClient = null) : StellarToml {
        $url = "https://" . $domain . "/.well-known/stellar.toml";
        $client = $httpClient;
        if ($client == null) {
            $client = new Client();
        }
        try {
            $request = new Request('GET', $url, RequestBuilder::HEADERS);
            $response = $client->send($request);
            if ($response->getStatusCode() != 200) {
                throw new Exception("Stellar toml not found. Response status code ". $response->getStatusCode());
            }
            return new StellarToml((string)$response->getBody());
        } catch (GuzzleException $e) {
            throw new Exception("Stellar toml not found. ". $e->getMessage());
        }
    }

    /// Alternately to specifying a currency in its content, stellar.toml can link out to a separate TOML file for the currency by specifying toml="https://DOMAIN/.well-known/CURRENCY.toml" as the currency's only field.
    /// In this case you can use this method to load the currency data from the received link (Currency.toml).
    /**
     * @throws Exception
     */
    public static function currencyFromUrl(string $toml) : Currency {
        $httpClient = new Client();
        try {
            $request = new Request('GET', $toml, RequestBuilder::HEADERS);
            $response = $httpClient->send($request);
            if ($response->getStatusCode() != 200) {
                throw new Exception("Currency toml not found. Response status code ". $response->getStatusCode());
            }
            $item = Toml::Parse((string)$response->getBody());
            return self::currencyFromItem($item);
        } catch (GuzzleException $e) {
            throw new Exception("Currency toml not found. ". $e->getMessage());
        }
    }

    public static function currencyFromItem(array $item) : Currency {
        $currency = new Currency();
        if (array_key_exists("toml", $item)) {
            $currency->toml = $item["toml"];
        }
        if (array_key_exists("code", $item)) {
            $currency->code = $item["code"];
        }
        if (array_key_exists("code_template", $item)) {
            $currency->codeTemplate = $item["code_template"];
        }
        if (array_key_exists("issuer", $item)) {
            $currency->issuer = $item["issuer"];
        }
        if (array_key_exists("status", $item)) {
            $currency->status = $item["status"];
        }
        if (array_key_exists("display_decimals", $item)) {
            $currency->displayDecimals = $item["display_decimals"];
        }
        if (array_key_exists("name", $item)) {
            $currency->name = $item["name"];
        }
        if (array_key_exists("desc", $item)) {
            $currency->desc = $item["desc"];
        }
        if (array_key_exists("conditions", $item)) {
            $currency->conditions = $item["conditions"];
        }
        if (array_key_exists("image", $item)) {
            $currency->image = $item["image"];
        }
        if (array_key_exists("fixed_number", $item)) {
            $currency->fixedNumber = $item["fixed_number"];
        }
        if (array_key_exists("max_number", $item)) {
            $currency->maxNumber = $item["max_number"];
        }
        if (array_key_exists("is_unlimited", $item)) {
            $currency->isUnlimited = $item["is_unlimited"];
        }
        if (array_key_exists("is_asset_anchored", $item)) {
            $currency->isAssetAnchored = $item["is_asset_anchored"];
        }
        if (array_key_exists("anchor_asset_type", $item)) {
            $currency->anchorAssetType = $item["anchor_asset_type"];
        }
        if (array_key_exists("anchor_asset", $item)) {
            $currency->anchorAsset = $item["anchor_asset"];
        }
        if (array_key_exists("redemption_instructions", $item)) {
            $currency->redemptionInstructions = $item["redemption_instructions"];
        }
        if (array_key_exists("collateral_addresses", $item)) {
            $currency->collateralAddresses = $item["collateral_addresses"];
        }
        if (array_key_exists("collateral_address_messages", $item)) {
            $currency->collateralAddressMessages = $item["collateral_address_messages"];
        }
        if (array_key_exists("collateral_address_signatures", $item)) {
            $currency->collateralAddressSignatures = $item["collateral_address_signatures"];
        }
        if (array_key_exists("regulated", $item)) {
            $currency->regulated = $item["regulated"];
        }
        if (array_key_exists("approval_server", $item)) {
            $currency->approvalServer = $item["approval_server"];
        }
        if (array_key_exists("approval_criteria", $item)) {
            $currency->approvalCriteria = $item["approval_criteria"];
        }
        return $currency;
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