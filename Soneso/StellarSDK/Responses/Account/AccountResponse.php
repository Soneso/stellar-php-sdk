<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Account;

use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Responses\Response;
use Soneso\StellarSDK\TransactionBuilderAccount;

/**
 * Represents a single account on the Stellar network with all its details
 *
 * This response contains comprehensive account information including balances, signers, thresholds,
 * flags, data entries, and sponsorship details. The account response is returned by the accounts
 * endpoint and implements TransactionBuilderAccount for use in transaction building.
 *
 * Key fields:
 * - Account ID and sequence number for transaction building
 * - Balances for all assets held by the account
 * - Signers and their weights for multi-signature operations
 * - Thresholds for low, medium, and high security operations
 * - Authorization flags for asset issuers
 * - Data entries stored on the account
 * - Sponsorship information for reserve requirements
 *
 * The account response is returned by these Horizon endpoints:
 * - GET /accounts/{account_id} - Single account details
 * - GET /accounts - List of accounts
 *
 * @package Soneso\StellarSDK\Responses\Account
 * @see https://developers.stellar.org/api/resources/accounts Horizon Accounts API
 * @see AccountBalanceResponse For balance details
 * @see AccountSignerResponse For signer details
 * @see AccountThresholdsResponse For threshold values
 * @see AccountFlagsResponse For authorization flags
 * @since 1.0.0
 */
class AccountResponse extends Response implements TransactionBuilderAccount
{

    private string $accountId;
    private BigInteger $sequenceNumber;
    private int $subentryCount;
    private ?string $inflationDestination = null;
    private ?string $homeDomain = null;
    private int $lastModifiedLedger;
    private string $lastModifiedTime;
    private AccountThresholdsResponse $thresholds;
    private AccountFlagsResponse $flags;
    private AccountBalancesResponse $balances;
    private AccountSignersResponse $signers;
    private AccountDataResponse $data;
    private AccountLinksResponse $links;
    private int $numSponsoring;
    private int $numSponsored;
    private ?string $sponsor = null;
    private string $pagingToken;
    private KeyPair $keyPair;
    private ?int $muxedAccountMed25519Id = null; // ID to be used if this account is used as MuxedAccountMed25519
    private ?int $sequenceLedger = null;
    private ?string $sequenceTime = null;

    /**
     * Gets the unique account ID (public key) of this account
     *
     * @return string The account ID in Stellar public key format (G...)
     */
    public function getAccountId() : string {
        return $this->accountId;
    }

    /**
     * Gets the current sequence number for this account
     *
     * The sequence number must be incremented for each transaction submitted by this account.
     *
     * @return BigInteger The current sequence number
     */
    public function getSequenceNumber() : BigInteger {
        return $this->sequenceNumber;
    }

    /**
     * Gets the number of subentries (trustlines, offers, data, etc.) on this account
     *
     * Each subentry requires a base reserve to be maintained in the account balance.
     *
     * @return int The count of subentries
     */
    public function getSubentryCount() : int {
        return $this->subentryCount;
    }

    /**
     * Gets the account designated to receive inflation
     *
     * @return string|null The inflation destination account ID, or null if not set
     */
    public function getInflationDestination() : ?string {
        return $this->inflationDestination;
    }

    /**
     * Gets the home domain associated with this account
     *
     * The home domain can be used for federation and to identify the organization controlling the account.
     *
     * @return string|null The home domain, or null if not set
     */
    public function getHomeDomain() : ?string {
        return $this->homeDomain;
    }

    /**
     * Gets the ledger sequence number when this account was last modified
     *
     * @return int The ledger sequence number
     */
    public function getLastModifiedLedger() : int {
        return $this->lastModifiedLedger;
    }

    /**
     * Gets the timestamp when this account was last modified
     *
     * @return string The modification time in ISO 8601 format
     */
    public function getLastModifiedTime() : string {
        return $this->lastModifiedTime;
    }

    /**
     * Gets the signature thresholds for this account
     *
     * Thresholds determine the weight required for low, medium, and high security operations.
     *
     * @return AccountThresholdsResponse The threshold values
     */
    public function getThresholds() : AccountThresholdsResponse {
        return $this->thresholds;
    }

    /**
     * Gets the authorization flags set on this account
     *
     * Flags control whether this account (as an issuer) requires authorization for trustlines.
     *
     * @return AccountFlagsResponse The authorization flags
     */
    public function getFlags() : AccountFlagsResponse {
        return $this->flags;
    }

    /**
     * Gets the links to related resources for this account
     *
     * @return AccountLinksResponse Links to effects, offers, operations, etc.
     */
    public function getLinks() : AccountLinksResponse {
        return $this->links;
    }

    /**
     * Gets all asset balances held by this account
     *
     * Includes native XLM balance and all trustline balances.
     *
     * @return AccountBalancesResponse Collection of account balances
     */
    public function getBalances() : AccountBalancesResponse {
        return $this->balances;
    }

    /**
     * Gets all signers configured for this account
     *
     * Signers can authorize transactions on behalf of the account based on their weights.
     *
     * @return AccountSignersResponse Collection of account signers
     */
    public function getSigners() : AccountSignersResponse {
        return $this->signers;
    }

    /**
     * Gets all data entries stored on this account
     *
     * Data entries are key-value pairs stored on the ledger.
     *
     * @return AccountDataResponse Collection of data entries
     */
    public function getData() : AccountDataResponse {
        return $this->data;
    }

    /**
     * Gets the number of reserves this account is sponsoring for other accounts
     *
     * @return int The count of sponsored reserves
     */
    public function getNumSponsoring() : int {
        return $this->numSponsoring;
    }

    /**
     * Gets the number of reserves being sponsored for this account
     *
     * @return int The count of reserves being sponsored
     */
    public function getNumSponsored() : int {
        return $this->numSponsored;
    }

    /**
     * Gets the account ID of the sponsor for this account
     *
     * @return string|null The sponsor account ID, or null if not sponsored
     */
    public function getSponsor() : ?string {
        return $this->sponsor;
    }

    /**
     * Gets the paging token for this account in list results
     *
     * @return string The paging token used for cursor-based pagination
     */
    public function getPagingToken() : string {
        return $this->pagingToken;
    }

    /**
     * Gets the ledger sequence number when the sequence number was last updated
     *
     * @return int|null The ledger sequence number, or null if not available
     */
    public function getSequenceLedger(): ?int
    {
        return $this->sequenceLedger;
    }

    /**
     * Gets the timestamp when the sequence number was last updated
     *
     * @return string|null The sequence update time in ISO 8601 format, or null if not available
     */
    public function getSequenceTime(): ?string
    {
        return $this->sequenceTime;
    }

    /**
     * Gets the multiplexed account ID if this account is used as a MuxedAccountMed25519
     *
     * @return int|null The muxed account ID, or null if not set
     */
    public function getMuxedAccountMed25519Id(): ?int
    {
        return $this->muxedAccountMed25519Id;
    }

    /**
     * Sets the multiplexed account ID to be used if this account is used as MuxedAccountMed25519
     *
     * @param int|null $muxedAccountMed25519Id The muxed account ID, or null to unset
     */
    public function setMuxedAccountMed25519Id(?int $muxedAccountMed25519Id): void
    {
        $this->muxedAccountMed25519Id = $muxedAccountMed25519Id;
    }

    protected function loadFromJson(array $json) : void {
        
        if (isset($json['account_id'])) $this->accountId = $json['account_id'];
        $this->keyPair = KeyPair::fromAccountId($this->accountId);
        if (isset($json['sequence'])) $this->sequenceNumber = new BigInteger($json['sequence']);
        if (isset($json['subentry_count'])) $this->subentryCount = $json['subentry_count'];
        if (isset($json['inflation_destination'])) $this->inflationDestination = $json['inflation_destination'];
        if (isset($json['home_domain'])) $this->homeDomain = $json['home_domain'];
        if (isset($json['last_modified_ledger'])) $this->lastModifiedLedger = $json['last_modified_ledger'];
        if (isset($json['last_modified_time'])) $this->lastModifiedTime = $json['last_modified_time'];
        if (isset($json['thresholds'])) $this->thresholds = AccountThresholdsResponse::fromJson($json['thresholds']);
        if (isset($json['flags'])) $this->flags = AccountFlagsResponse::fromJson($json['flags']);

        if (isset($json['balances'])) {
            $this->balances = new AccountBalancesResponse();
            foreach ($json['balances'] as $jsonBalance) {
                $balance = AccountBalanceResponse::fromJson($jsonBalance);
                $this->balances->add($balance);
            }
        }

        if (isset($json['signers'])) {
            $this->signers = new AccountSignersResponse();
            foreach ($json['signers'] as $jsonSigner) {
                $signer = AccountSignerResponse::fromJson($jsonSigner);
                $this->signers->add($signer);
            }
        }
        
        if (isset($json['data'])) $this->data = AccountDataResponse::fromJson($json);
        if (isset($json['_links'])) $this->links = AccountLinksResponse::fromJson($json['_links']);
        if (isset($json['num_sponsoring'])) $this->numSponsoring = $json['num_sponsoring'];
        if (isset($json['num_sponsored'])) $this->numSponsored = $json['num_sponsored'];
        if (isset($json['sponsor'])) $this->sponsor = $json['sponsor'];
        if (isset($json['paging_token'])) $this->pagingToken = $json['paging_token'];
        if (isset($json['sequence_ledger'])) $this->sequenceLedger = $json['sequence_ledger'];
        if (isset($json['sequence_time'])) $this->sequenceTime = $json['sequence_time'];
    }
    
    /**
     * Creates an AccountResponse instance from JSON data
     *
     * @param array $json The JSON array containing account data from Horizon
     * @return AccountResponse The parsed account response
     */
    public static function fromJson(array $json) : AccountResponse {
        $result = new AccountResponse();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * Gets the KeyPair for this account
     *
     * The KeyPair is derived from the account ID and can be used for signing operations.
     *
     * @return KeyPair The cryptographic key pair for this account
     */
    public function getKeyPair(): KeyPair {
        return $this->keyPair;
    }

    /**
     * Gets the next sequence number that should be used for a transaction
     *
     * This returns the current sequence number plus one, which is required for the next transaction.
     *
     * @return BigInteger The incremented sequence number
     */
    public function getIncrementedSequenceNumber(): BigInteger {
        return $this->sequenceNumber->add(new BigInteger(1));
    }

    /**
     * Increments the sequence number by one
     *
     * This should be called after successfully submitting a transaction to keep the local
     * account state in sync with the network.
     */
    public function incrementSequenceNumber(): void {
        $this->sequenceNumber = $this->getIncrementedSequenceNumber();
    }

    /**
     * Gets a MuxedAccount representation of this account
     *
     * If a muxed account ID has been set, this returns a multiplexed account.
     *
     * @return MuxedAccount The muxed account representation
     */
    public function getMuxedAccount(): MuxedAccount {
        return new MuxedAccount($this->accountId, $this->muxedAccountMed25519Id);
    }
}

