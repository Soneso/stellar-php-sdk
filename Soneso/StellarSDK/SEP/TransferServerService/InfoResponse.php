<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Soneso\StellarSDK\Responses\Response;

/**
 * Response from info endpoint describing anchor capabilities and supported assets.
 *
 * Contains comprehensive information about what the anchor's transfer server supports,
 * including lists of supported assets for deposits and withdrawals (both regular and
 * exchange operations), fee structures, endpoint capabilities, and feature flags.
 *
 * This is the discovery endpoint response that clients should query first to understand
 * what operations are available and how to interact with the anchor.
 *
 * @package Soneso\StellarSDK\SEP\TransferServerService
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md SEP-06 Specification
 * @see TransferServerService::info()
 * @see DepositAsset
 * @see WithdrawAsset
 * @see AnchorFeatureFlags
 */
class InfoResponse extends Response
{
    /**
     * @var array<array-key, DepositAsset>|null $depositAssets deposit assets of the info response.
     */
    public ?array $depositAssets = null;

    /**
     * @var array<array-key, DepositExchangeAsset>|null $depositExchangeAssets deposit exchange assets of the info response.
     */
    public ?array $depositExchangeAssets = null;

    /**
     * @var array<array-key, WithdrawAsset>|null $withdrawAssets withdrawal assets of the info response.
     */
    public ?array $withdrawAssets = null;

    /**
     * @var array<array-key, WithdrawExchangeAsset>|null $withdrawExchangeAssets withdrawal exchange assets of the info response.
     */
    public ?array $withdrawExchangeAssets = null;

    /**
     * @var AnchorFeeInfo|null $feeInfo info about the support of the fee endpoint.
     */
    public ?AnchorFeeInfo $feeInfo = null;

    /**
     * @var AnchorTransactionsInfo|null $transactionsInfo info about the support of the transactions endpoint.
     */
    public ?AnchorTransactionsInfo $transactionsInfo = null;

    /**
     * @var AnchorTransactionInfo|null $transactionInfo info about the support of the transaction endpoint.
     */
    public ?AnchorTransactionInfo $transactionInfo = null;

    /**
     * @var AnchorFeatureFlags|null $featureFlags contains boolean values indicating whether specific features are supported by the anchor.
     */
    public ?AnchorFeatureFlags $featureFlags = null;


    protected function loadFromJson(array $json) : void {
        if (isset($json['deposit'])) {
            $this->depositAssets = array();
            $jsonFields = $json['deposit'];
            foreach(array_keys($jsonFields) as $key) {
                $value = DepositAsset::fromJson($jsonFields[$key]);
                $this->depositAssets += [$key => $value];
            }
        }
        if (isset($json['deposit-exchange'])) {
            $this->depositExchangeAssets = array();
            $jsonFields = $json['deposit-exchange'];
            foreach(array_keys($jsonFields) as $key) {
                $value = DepositExchangeAsset::fromJson($jsonFields[$key]);
                $this->depositExchangeAssets += [$key => $value];
            }
        }
        if (isset($json['withdraw'])) {
            $this->withdrawAssets = array();
            $jsonFields = $json['withdraw'];
            foreach(array_keys($jsonFields) as $key) {
                $value = WithdrawAsset::fromJson($jsonFields[$key]);
                $this->withdrawAssets += [$key => $value];
            }
        }
        if (isset($json['withdraw-exchange'])) {
            $this->withdrawExchangeAssets = array();
            $jsonFields = $json['withdraw-exchange'];
            foreach(array_keys($jsonFields) as $key) {
                $value = WithdrawExchangeAsset::fromJson($jsonFields[$key]);
                $this->withdrawExchangeAssets += [$key => $value];
            }
        }
        if (isset($json['fee'])) $this->feeInfo = AnchorFeeInfo::fromJson($json['fee']);
        if (isset($json['transactions'])) $this->transactionsInfo = AnchorTransactionsInfo::fromJson($json['transactions']);
        if (isset($json['transaction'])) $this->transactionInfo = AnchorTransactionInfo::fromJson($json['transaction']);
        if (isset($json['features'])) $this->featureFlags = AnchorFeatureFlags::fromJson($json['features']);
    }

    public static function fromJson(array $json) : InfoResponse
    {
        $result = new InfoResponse();
        $result->loadFromJson($json);
        return $result;
    }
}