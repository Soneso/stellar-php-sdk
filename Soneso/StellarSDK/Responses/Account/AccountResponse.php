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

    public function getAccountId() : string {
        return $this->accountId;
    }

    public function getSequenceNumber() : BigInteger {
        return $this->sequenceNumber;
    }

    public function getSubentryCount() : int {
        return $this->subentryCount;
    }

    public function getInflationDestination() : ?string {
        return $this->inflationDestination;
    }

    public function getHomeDomain() : ?string {
        return $this->homeDomain;
    }

    public function getLastModifiedLedger() : int {
        return $this->lastModifiedLedger;
    }

    public function getLastModifiedTime() : string {
        return $this->lastModifiedTime;
    }

    public function getThresholds() : AccountThresholdsResponse {
        return $this->thresholds;
    }

    public function getFlags() : AccountFlagsResponse {
        return $this->flags;
    }

    public function getLinks() : AccountLinksResponse {
        return $this->links;
    }

    public function getBalances() : AccountBalancesResponse {
        return $this->balances;
    }

    public function getSigners() : AccountSignersResponse {
        return $this->signers;
    }

    public function getData() : AccountDataResponse {
        return $this->data;
    }

    public function getNumSponsoring() : int {
        return $this->numSponsoring;
    }

    public function getNumSponsored() : int {
        return $this->numSponsored;
    }

    public function getSponsor() : ?string {
        return $this->sponsor;
    }

    public function getPagingToken() : string {
        return $this->pagingToken;
    }

    /**
     * @return int|null
     */
    public function getSequenceLedger(): ?int
    {
        return $this->sequenceLedger;
    }

    /**
     * @return string|null
     */
    public function getSequenceTime(): ?string
    {
        return $this->sequenceTime;
    }

    /**
     * @return int|null
     */
    public function getMuxedAccountMed25519Id(): ?int
    {
        return $this->muxedAccountMed25519Id;
    }

    /**
     * ID to be used if this account is used as MuxedAccountMed25519.
     * @param int|null $muxedAccountMed25519Id
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
    
    public static function fromJson(array $json) : AccountResponse {
        $result = new AccountResponse();
        $result->loadFromJson($json);
        return $result;
    }

    public function getKeyPair(): KeyPair {
        return $this->keyPair;
    }

    public function getIncrementedSequenceNumber(): BigInteger {
        return $this->sequenceNumber->add(new BigInteger(1));
    }

    public function incrementSequenceNumber(): void {
        $this->sequenceNumber = $this->getIncrementedSequenceNumber();
    }

    public function getMuxedAccount(): MuxedAccount {
        return new MuxedAccount($this->accountId, $this->muxedAccountMed25519Id);
    }
}

