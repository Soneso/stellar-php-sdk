<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Soneso\StellarSDK\Responses\Response;

class InfoResponse extends Response
{
    private array $depositAssets; //[string => DepositAsset]
    private array $withdrawAssets; //[string => WithdrawAsset]
    private AnchorTransactionsInfo $transactionsInfo;
    private ?AnchorTransactionInfo $transactionInfo;
    private ?AnchorFeeInfo $feeInfo;

    /**
     * @return array
     */
    public function getDepositAssets(): array
    {
        return $this->depositAssets;
    }

    /**
     * @return array
     */
    public function getWithdrawAssets(): array
    {
        return $this->withdrawAssets;
    }

    /**
     * @return AnchorTransactionsInfo
     */
    public function getTransactionsInfo(): AnchorTransactionsInfo
    {
        return $this->transactionsInfo;
    }

    /**
     * @return AnchorTransactionInfo|null
     */
    public function getTransactionInfo(): ?AnchorTransactionInfo
    {
        return $this->transactionInfo;
    }


    /**
     * @return AnchorFeeInfo|null
     */
    public function getFeeInfo(): ?AnchorFeeInfo
    {
        return $this->feeInfo;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['deposit'])) {
            $this->depositAssets = array();
            $jsonFields = $json['deposit'];
            foreach(array_keys($jsonFields) as $key) {
                $value = DepositAsset::fromJson($jsonFields[$key]);
                $this->depositAssets += [$key => $value];
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
        if (isset($json['fee'])) $this->feeInfo = AnchorFeeInfo::fromJson($json['fee']);
        if (isset($json['transactions'])) $this->transactionsInfo = AnchorTransactionsInfo::fromJson($json['transactions']);
        if (isset($json['transaction'])) $this->transactionInfo = AnchorTransactionInfo::fromJson($json['transaction']);
    }

    public static function fromJson(array $json) : InfoResponse
    {
        $result = new InfoResponse();
        $result->loadFromJson($json);
        return $result;
    }
}