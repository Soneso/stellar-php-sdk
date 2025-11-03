<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Responses\Response;

/**
 * Base class for all effect responses in the Stellar network
 *
 * This response represents the changes that occur in the ledger as a result of operations.
 * Effects are more granular than operations and show the specific state changes that occurred.
 * The base EffectResponse class contains common fields present in all effect types, including
 * the effect ID, account affected, timestamps, and effect type information.
 *
 * The fromJson factory method automatically deserializes JSON into the appropriate specific
 * effect subclass based on the effect type (e.g., AccountCreatedEffectResponse,
 * TrustlineCreatedEffectResponse, TradeEffectResponse, etc.).
 *
 * Key fields:
 * - Effect ID and paging token
 * - Account affected by the effect (with optional muxed account details)
 * - Effect type (both numeric and human-readable)
 * - Creation timestamp
 *
 * Returned by Horizon endpoints:
 * - GET /effects - All effects
 * - GET /accounts/{account_id}/effects - Effects for an account
 * - GET /transactions/{transaction_hash}/effects - Effects from a transaction
 * - GET /operations/{operation_id}/effects - Effects from an operation
 * - GET /ledgers/{sequence}/effects - Effects in a ledger
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectType For effect type constants
 * @see AccountCreatedEffectResponse For account creation effects
 * @see TradeEffectResponse For trade effects
 * @see https://developers.stellar.org/api/resources/effects Horizon Effects API
 * @since 1.0.0
 */
class EffectResponse extends Response
{

    private string $effectId;
    private EffectLinksResponse $links;
    private string $pagingToken;
    private string $createdAt;
    private string $account;
    private ?string $accountMuxed = null;
    private ?string $accountMuxedId = null;
    private string $humanReadableEffectType;
    private int $effectType;

    /**
     * Gets the unique identifier for this effect
     *
     * @return string The effect ID
     */
    public function getEffectId(): string
    {
        return $this->effectId;
    }

    /**
     * Gets the links to related resources for this effect
     *
     * @return EffectLinksResponse The navigation links
     */
    public function getLinks(): EffectLinksResponse
    {
        return $this->links;
    }

    /**
     * Gets the paging token for this effect in list results
     *
     * @return string The paging token used for cursor-based pagination
     */
    public function getPagingToken(): string
    {
        return $this->pagingToken;
    }

    /**
     * Gets the timestamp when this effect was created
     *
     * @return string The creation time in ISO 8601 format
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Gets the account address affected by this effect
     *
     * @return string The account ID
     */
    public function getAccount(): string
    {
        return $this->account;
    }

    /**
     * Gets the muxed account address if the account is multiplexed
     *
     * @return string|null The muxed account address, or null if not multiplexed
     */
    public function getAccountMuxed(): ?string
    {
        return $this->accountMuxed;
    }

    /**
     * Gets the muxed account ID if the account is multiplexed
     *
     * @return string|null The muxed account ID, or null if not multiplexed
     */
    public function getAccountMuxedId(): ?string
    {
        return $this->accountMuxedId;
    }

    /**
     * Gets the human-readable description of the effect type
     *
     * @return string The effect type name (e.g., "account_created", "trade")
     */
    public function getHumanReadableEffectType(): string
    {
        return $this->humanReadableEffectType;
    }

    /**
     * Gets the numeric effect type identifier
     *
     * @return int The effect type code as defined in EffectType constants
     */
    public function getEffectType(): int
    {
        return $this->effectType;
    }


    protected function loadFromJson(array $json) : void {
        if (isset($json['_links'])) $this->links = EffectLinksResponse::fromJson($json['_links']);
        if (isset($json['id'])) $this->effectId = $json['id'];
        if (isset($json['paging_token'])) $this->pagingToken = $json['paging_token'];
        if (isset($json['created_at'])) $this->createdAt = $json['created_at'];

        if (isset($json['account'])) $this->account = $json['account'];
        if (isset($json['account_muxed'])) $this->accountMuxed = $json['account_muxed'];
        if (isset($json['account_muxed_id'])) $this->accountMuxedId = $json['account_muxed_id'];
        if (isset($json['type'])) $this->humanReadableEffectType = $json['type'];
        if (isset($json['type_i'])) $this->effectType = $json['type_i'];
    }

    public static function fromJson(array $jsonData) : EffectResponse {
        if (isset($jsonData['type_i'])) {
            $effectType = $jsonData['type_i'];
            return match ($effectType) {
                EffectType::ACCOUNT_CREATED => AccountCreatedEffectResponse::fromJson($jsonData),
                EffectType::ACCOUNT_REMOVED => AccountRemovedEffectResponse::fromJson($jsonData),
                EffectType::ACCOUNT_CREDITED => AccountCreditedEffectResponse::fromJson($jsonData),
                EffectType::ACCOUNT_DEBITED => AccountDebitedEffectResponse::fromJson($jsonData),
                EffectType::ACCOUNT_THRESHOLDS_UPDATED => AccountThresholdsUpdatedEffectResponse::fromJson($jsonData),
                EffectType::ACCOUNT_HOME_DOMAIN_UPDATED => AccountHomeDomainUpdatedEffectResponse::fromJson($jsonData),
                EffectType::ACCOUNT_FLAGS_UPDATED => AccountFlagsUpdatedEffectResponse::fromJson($jsonData),
                EffectType::ACCOUNT_INFLATION_DESTINATION_UPDATED => AccountInflationDestinationUpdatedEffectResponse::fromJson($jsonData),
                EffectType::SIGNER_CREATED => SignerCreatedEffectResponse::fromJson($jsonData),
                EffectType::SIGNER_UPDATED => SignerUpdatedEffectResponse::fromJson($jsonData),
                EffectType::SIGNER_REMOVED => SignerRemovedEffectResponse::fromJson($jsonData),
                EffectType::TRUSTLINE_CREATED => TrustlineCreatedEffectResponse::fromJson($jsonData),
                EffectType::TRUSTLINE_REMOVED => TrustlineRemovedEffectResponse::fromJson($jsonData),
                EffectType::TRUSTLINE_UPDATED => TrustlineUpdatedEffectResponse::fromJson($jsonData),
                EffectType::TRUSTLINE_AUTHORIZED => TrustlineAuthorizedEffectResponse::fromJson($jsonData),
                EffectType::TRUSTLINE_DEAUTHORIZED => TrustlineDeauthorizedEffectResponse::fromJson($jsonData),
                EffectType::TRUSTLINE_AUTHORIZED_TO_MAINTAIN_LIABILITIES => TrustlineAuthorizedToMaintainLiabilitiesEffectResponse::fromJson($jsonData),
                EffectType::TRUSTLINE_FLAGS_UPDATED => TrustlineFlagsUpdatedEffectResponse::fromJson($jsonData),
                EffectType::OFFER_CREATED => OfferCreatedEffectResponse::fromJson($jsonData),
                EffectType::OFFER_UPDATED => OfferUpdatedEffectResponse::fromJson($jsonData),
                EffectType::OFFER_REMOVED => OfferRemovedEffectResponse::fromJson($jsonData),
                EffectType::TRADE => TradeEffectResponse::fromJson($jsonData),
                EffectType::SEQUENCE_BUMPED => SequenceBumpedEffectResponse::fromJson($jsonData),
                EffectType::DATA_CREATED => DataCreatedEffectResponse::fromJson($jsonData),
                EffectType::DATA_UPDATED => DataUpdatedEffectResponse::fromJson($jsonData),
                EffectType::DATA_REMOVED => DataRemovedEffectResponse::fromJson($jsonData),
                EffectType::CLAIMABLE_BALANCE_CREATED => ClaimableBalanceCreatedEffectResponse::fromJson($jsonData),
                EffectType::CLAIMABLE_BALANCE_CLAIMANT_CREATED => ClaimableBalanceClaimantCreatedEffectResponse::fromJson($jsonData),
                EffectType::CLAIMABLE_BALANCE_CLAIMED => ClaimableBalanceClaimedEffectResponse::fromJson($jsonData),
                EffectType::ACCOUNT_SPONSORSHIP_CREATED => AccountSponsorshipCreatedEffectResponse::fromJson($jsonData),
                EffectType::ACCOUNT_SPONSORSHIP_UPDATED => AccountSponsorshipUpdatedEffectResponse::fromJson($jsonData),
                EffectType::ACCOUNT_SPONSORSHIP_REMOVED => AccountSponsorshipRemovedEffectResponse::fromJson($jsonData),
                EffectType::TRUSTLINE_SPONSORSHIP_CREATED => TrustlineSponsorshipCreatedEffectResponse::fromJson($jsonData),
                EffectType::TRUSTLINE_SPONSORSHIP_UPDATED => TrustlineSponsorshipUpdatedEffectResponse::fromJson($jsonData),
                EffectType::TRUSTLINE_SPONSORSHIP_REMOVED => TrustlineSponsorshipRemovedEffectResponse::fromJson($jsonData),
                EffectType::DATA_SPONSORSHIP_CREATED => DataSponsorshipCreatedEffectResponse::fromJson($jsonData),
                EffectType::DATA_SPONSORSHIP_UPDATED => DataSponsorshipUpdatedEffectResponse::fromJson($jsonData),
                EffectType::DATA_SPONSORSHIP_REMOVED => DataSponsorshipRemovedEffectResponse::fromJson($jsonData),
                EffectType::CLAIMABLE_BALANCE_SPONSORSHIP_CREATED => ClaimableBalanceSponsorshipCreatedEffectResponse::fromJson($jsonData),
                EffectType::CLAIMABLE_BALANCE_SPONSORSHIP_UPDATED => ClaimableBalanceSponsorshipUpdatedEffectResponse::fromJson($jsonData),
                EffectType::CLAIMABLE_BALANCE_SPONSORSHIP_REMOVED => ClaimableBalanceSponsorshipRemovedEffectResponse::fromJson($jsonData),
                EffectType::SIGNER_SPONSORSHIP_CREATED => SignerSponsorshipCreatedEffectResponse::fromJson($jsonData),
                EffectType::SIGNER_SPONSORSHIP_UPDATED => SignerSponsorshipUpdatedEffectResponse::fromJson($jsonData),
                EffectType::SIGNER_SPONSORSHIP_REMOVED => SignerSponsorshipRemovedEffectResponse::fromJson($jsonData),
                EffectType::CLAIMABLE_BALANCE_CLAWED_BACK => ClaimableBalanceClawedBackEffectResponse::fromJson($jsonData),
                EffectType::LIQUIDITY_POOL_DEPOSITED => LiquidityPoolDepositedEffectResponse::fromJson($jsonData),
                EffectType::LIQUIDITY_POOL_WITHDREW => LiquidityPoolWithdrewEffectResponse::fromJson($jsonData),
                EffectType::LIQUIDITY_POOL_TRADE => LiquidityPoolTradeEffectResponse::fromJson($jsonData),
                EffectType::LIQUIDITY_POOL_CREATED => LiquidityPoolCreatedEffectResponse::fromJson($jsonData),
                EffectType::LIQUIDITY_POOL_REMOVED => LiquidityPoolRemovedEffectResponse::fromJson($jsonData),
                EffectType::LIQUIDITY_POOL_REVOKED => LiquidityPoolRevokedEffectResponse::fromJson($jsonData),
                EffectType::CONTRACT_CREDITED => ContractCreditedEffectResponse::fromJson($jsonData),
                EffectType::CONTRACT_DEBITED => ContractDebitedEffectResponse::fromJson($jsonData),
                default => throw new \InvalidArgumentException(sprintf("Unknown operation type: %s", $effectType)),
            };
        } else {
            throw new \InvalidArgumentException("No effect type_i found in json data");
        }
    }
}