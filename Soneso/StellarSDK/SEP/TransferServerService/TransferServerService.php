<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Soneso\StellarSDK\SEP\Toml\StellarToml;

class TransferServerService
{
    private string $serviceAddress;
    private Client $httpClient;

    /**
     * @param string $serviceAddress
     */
    public function __construct(string $serviceAddress)
    {
        $this->serviceAddress = $serviceAddress;
        $this->httpClient = new Client([
            'base_uri' => $this->serviceAddress,
            'exceptions' => false,
        ]);
    }

    /**
     * @param string $domain
     * @return TransferServerService
     * @throws Exception
     */
    public static function fromDomain(string $domain) : TransferServerService {
        $stellarToml = StellarToml::fromDomain($domain);
        $address = $stellarToml->getGeneralInformation()->transferServer;
        if (!$address) {
            throw new Exception("No transfer service found in stellar.toml");
        }
        return new TransferServerService($address);
    }

    /**
     * Get basic info from the anchor about what their TRANSFER_SERVER supports.
     * @param string $jwt token previously received from the anchor via the SEP-10 authentication flow
     * @param string|null $language (optional) Language code specified using ISO 639-1. description fields in the response should be in this language. Defaults to en.
     * @return InfoResponse response
     * @throws GuzzleException if en request error occurs.
     */
    public function info(string $jwt, ?string $language = null) : InfoResponse {
        $requestBuilder = new InfoRequestBuilder($this->httpClient, $jwt);
        if($language) {
            $requestBuilder = $requestBuilder->forQueryParameters(["lang" => $language]);
        }
        return $requestBuilder->execute();
    }

    /**
     * A deposit is when a user sends an external token (BTC via Bitcoin, USD via bank transfer, etc...)
     * to an address held by an anchor. In turn, the anchor sends an equal amount of tokens on the
     * Stellar network (minus fees) to the user's Stellar account.
     * The deposit endpoint allows a wallet to get deposit information from an anchor, so a user has
     * all the information needed to initiate a deposit. It also lets the anchor specify
     * additional information (if desired) that the user must submit via the /customer endpoint
     * to be able to deposit.
     * @param DepositRequest $request request
     * @return DepositResponse response
     * @throws GuzzleException if en request error occurs.
     */
    public function deposit(DepositRequest $request) : DepositResponse {
        $requestBuilder = new DepositRequesteBuilder($this->httpClient, $request->jwt);
        $queryParameters = array();
        $queryParameters += ["asset_code" => $request->assetCode];
        $queryParameters += ["account" => $request->account];
        if ($request->memoType) {
            $queryParameters += ["memo_type" => $request->memoType];
        }
        if ($request->memo) {
            $queryParameters += ["memo" => $request->memo];
        }
        if ($request->emailAddress) {
            $queryParameters += ["email_address" => $request->emailAddress];
        }
        if ($request->type) {
            $queryParameters += ["type" => $request->type];
        }
        if ($request->walletName) {
            $queryParameters += ["wallet_name" => $request->walletName];
        }
        if ($request->walletUrl) {
            $queryParameters += ["wallet_url" => $request->walletUrl];
        }
        if ($request->lang) {
            $queryParameters += ["lang" => $request->lang];
        }
        if ($request->onChangeCallback) {
            $queryParameters += ["on_change_callback" => $request->onChangeCallback];
        }
        if ($request->amount) {
            $queryParameters += ["amount" => $request->amount];
        }
        if ($request->countryCode) {
            $queryParameters += ["country_code" => $request->countryCode];
        }
        if ($request->claimableBalanceSupported) {
            $queryParameters += ["claimable_balance_supported" => $request->claimableBalanceSupported];
        }

        //TODO handle forbidden response!!!
        return $requestBuilder->execute();
    }
}