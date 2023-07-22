<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;
use Soneso\StellarSDK\Responses\Response;
use Soneso\StellarSDK\Responses\Transaction\TransactionResponse;

class OperationResponse extends Response
{

    private string $operationId;
    private OperationLinksResponse $links;
    private string $pagingToken;
    private string $sourceAccount;
    private ?string $sourceAccountMuxed = null;
    private ?string $sourceAccountMuxedId = null;
    private string $humanReadableOperationType;
    private int $operationType;
    private string $createdAt;
    private string $transactionHash;
    public bool $transactionSuccessful;
    // optional transaction if requested by join parameter
    private ?TransactionResponse $transaction = null;

    /**
     * @return string
     */
    public function getOperationId(): string
    {
        return $this->operationId;
    }

    /**
     * @return OperationLinksResponse
     */
    public function getLinks(): OperationLinksResponse
    {
        return $this->links;
    }

    /**
     * @return string
     */
    public function getPagingToken(): string
    {
        return $this->pagingToken;
    }

    /**
     * @return string
     */
    public function getSourceAccount(): string
    {
        return $this->sourceAccount;
    }

    /**
     * @return string|null
     */
    public function getSourceAccountMuxed(): ?string
    {
        return $this->sourceAccountMuxed;
    }

    /**
     * @return string|null
     */
    public function getSourceAccountMuxedId(): ?string
    {
        return $this->sourceAccountMuxedId;
    }

    /**
     * @return string
     */
    public function getHumanReadableOperationType(): string
    {
        return $this->humanReadableOperationType;
    }

    /**
     * @return int
     */
    public function getOperationType(): int
    {
        return $this->operationType;
    }

    /**
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * @return string
     */
    public function getTransactionHash(): string
    {
        return $this->transactionHash;
    }

    /**
     * @return bool
     */
    public function isTransactionSuccessful(): bool
    {
        return $this->transactionSuccessful;
    }

    /**
     * @return TransactionResponse|null
     */
    public function getTransaction(): ?TransactionResponse
    {
        return $this->transaction;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['_links'])) $this->links = OperationLinksResponse::fromJson($json['_links']);
        if (isset($json['id'])) $this->operationId = $json['id'];
        if (isset($json['paging_token'])) $this->pagingToken = $json['paging_token'];
        if (isset($json['source_account'])) $this->sourceAccount = $json['source_account'];
        if (isset($json['source_account_muxed'])) $this->sourceAccountMuxed = $json['source_account_muxed'];
        if (isset($json['source_account_muxed_id'])) $this->sourceAccountMuxedId = $json['source_account_muxed_id'];
        if (isset($json['type'])) $this->humanReadableOperationType = $json['type'];
        if (isset($json['type_i'])) $this->operationType = $json['type_i'];
        if (isset($json['created_at'])) $this->createdAt = $json['created_at'];
        if (isset($json['transaction_hash'])) $this->transactionHash = $json['transaction_hash'];
        if (isset($json['transaction_successful'])) $this->transactionSuccessful = $json['transaction_successful'];
        if (isset($json['transaction'])) $this->transaction = TransactionResponse::fromJson($json['transaction']);
    }

    public static function fromJson(array $jsonData) : OperationResponse {
        if (isset($jsonData['type_i'])) {
            $operationType = $jsonData['type_i'];
            return match ($operationType) {
                OperationType::CREATE_ACCOUNT => CreateAccountOperationResponse::fromJson($jsonData),
                OperationType::PAYMENT => PaymentOperationResponse::fromJson($jsonData),
                OperationType::PATH_PAYMENT => PathPaymentStrictReceiveOperationResponse::fromJson($jsonData),
                OperationType::PATH_PAYMENT_STRICT_SEND => PathPaymentStrictSendOperationResponse::fromJson($jsonData),
                OperationType::MANAGE_SELL_OFFER => ManageSellOfferOperationResponse::fromJson($jsonData),
                OperationType::MANAGE_BUY_OFFER => ManageBuyOfferOperationResponse::fromJson($jsonData),
                OperationType::CREATE_PASSIVE_SELL_OFFER => CreatePassiveSellOfferResponse::fromJson($jsonData),
                OperationType::SET_OPTIONS => SetOptionsOperationResponse::fromJson($jsonData),
                OperationType::CHANGE_TRUST => ChangeTrustOperationResponse::fromJson($jsonData),
                OperationType::ALLOW_TRUST => AllowTrustOperationResponse::fromJson($jsonData),
                OperationType::ACCOUNT_MERGE => AccountMergeOperationResponse::fromJson($jsonData),
                OperationType::INFLATION => InflationOperationResponse::fromJson($jsonData),
                OperationType::MANAGE_DATA => ManageDataOperationResponse::fromJson($jsonData),
                OperationType::BUMP_SEQUENCE => BumpSequenceOperationResponse::fromJson($jsonData),
                OperationType::CREATE_CLAIMABLE_BALANCE => CreateClaimableBalanceOperationResponse::fromJson($jsonData),
                OperationType::CLAIM_CLAIMABLE_BALANCE => ClaimClaimableBalanceOperationResponse::fromJson($jsonData),
                OperationType::BEGIN_SPONSORING_FUTURE_RESERVES => BeginSponsoringFutureReservesOperationResponse::fromJson($jsonData),
                OperationType::END_SPONSORING_FUTURE_RESERVES => EndSponsoringFutureReservesOperationResponse::fromJson($jsonData),
                OperationType::REVOKE_SPONSORSHIP => RevokeSponsorshipOperationResponse::fromJson($jsonData),
                OperationType::CLAWBACK => ClawbackOperationResponse::fromJson($jsonData),
                OperationType::CLAWBACK_CLAIMABLE_BALANCE => ClawbackClaimableBalanceOperationResponse::fromJson($jsonData),
                OperationType::SET_TRUSTLINE_FLAGS => SetTrustlineFlagsOperationResponse::fromJson($jsonData),
                OperationType::LIQUIDITY_POOL_DEPOSIT => LiquidityPoolDepositOperationResponse::fromJson($jsonData),
                OperationType::LIQUIDITY_POOL_WITHDRAW => LiquidityPoolWithdrawOperationResponse::fromJson($jsonData),
                OperationType::INVOKE_HOST_FUNCTION => InvokeHostFunctionOperationResponse::fromJson($jsonData),
                OperationType::BUMP_FOOTPRINT_EXPIRATION => BumpFootprintExpirationOperationResponse::fromJson($jsonData),
                OperationType::RESTORE_FOOTPRINT => RestoreFootprintOperationResponse::fromJson($jsonData),
                default => throw new \InvalidArgumentException(sprintf("Unknown operation type: %s", $operationType)),
            };
        } else {
            throw new \InvalidArgumentException("No operation type_i found in json data");
        }
    }
}