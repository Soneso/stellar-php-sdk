<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

use Soneso\StellarSDK\Xdr\XdrSCVal;

/**
 * Part of the getEvents request.
 * See: https://developers.stellar.org/network/soroban-rpc/api-reference/methods/getEvents
 */
class EventInfo
{
    /**
     * @var string $type The type of event emission. Possible values: contract, diagnostic, system.
     */
    public string $type;

    /**
     * @var int $ledger Sequence number of the ledger in which this event was emitted.
     */
    public int $ledger;

    /**
     * @var string $ledgerClosedAt ISO-8601 timestamp of the ledger closing time.
     */
    public string $ledgerClosedAt;

    /**
     * @var string $contractId StrKey representation of the contract address that emitted this event. ("C...").
     */
    public string $contractId;

    /**
     * @var ?string $pagingToken for paging if protocol version < 22 (Duplicate of id field, but in the standard place for pagination tokens)
     */
    public ?string $pagingToken = null;

    /**
     * @var string $id Unique identifier for this event.
     */
    public string $id;

    /**
     * @var array<String> $topic List containing the topic this event was emitted with. (>= 1 items, <= 4 items).
     */
    public array $topic;

    /**
     * @var string $value The emitted body value of the event (serialized in a base64 xdr string).
     */
    public string $value;

    /**
     * @var bool $inSuccessfulContractCall If true the event was emitted during a successful contract call.
     */
    public bool $inSuccessfulContractCall;

    /**
     * @var string $txHash The transaction which triggered this event.
     */
    public string $txHash;

    /**
     * @param string $type The type of event emission. Possible values: contract, diagnostic, system.
     * @param int $ledger Sequence number of the ledger in which this event was emitted.
     * @param string $ledgerClosedAt ISO-8601 timestamp of the ledger closing time.
     * @param string $contractId StrKey representation of the contract address that emitted this event. ("C...").
     * @param string $id Unique identifier for this event.
     * @param array<String> $topic List containing the topic this event was emitted with. (>= 1 items, <= 4 items).
     * @param string $value The emitted body value of the event (serialized in a base64 xdr string).
     * @param bool $inSuccessfulContractCall If true the event was emitted during a successful contract call.
     * @param string $txHash The transaction which triggered this event.
     */
    public function __construct(
        string $type,
        int $ledger,
        string $ledgerClosedAt,
        string $contractId,
        string $id,
        array $topic,
        string $value,
        bool $inSuccessfulContractCall,
        string $txHash,
        ?string $pagingToken = null,
    )
    {
        $this->type = $type;
        $this->ledger = $ledger;
        $this->ledgerClosedAt = $ledgerClosedAt;
        $this->contractId = $contractId;
        $this->id = $id;
        $this->topic = $topic;
        $this->value = $value;
        $this->inSuccessfulContractCall = $inSuccessfulContractCall;
        $this->txHash = $txHash;
        $this->pagingToken = $pagingToken;
    }

    public static function fromJson(array $json): EventInfo
    {
        $type = $json['type'];
        $ledger = $json['ledger'];
        $ledgerClosedAt = $json['ledgerClosedAt'];
        $contractId = $json['contractId'];
        $id = $json['id'];
        $value = $json['value']['xdr'] ?? $json['value'];
        $topic = array();
        foreach ($json['topic'] as $val) {
            $topic[] = $val;
        }
        $inSuccessfulContractCall = $json['inSuccessfulContractCall'];
        $txHash = $json['txHash'];
        $pagingToken = null;
        if (isset($json['pagingToken'])) {
            $pagingToken = $json['pagingToken']; // protocol < 22
        }
        return new EventInfo(
            $type,
            $ledger,
            $ledgerClosedAt,
            $contractId,
            $id,
            $topic,
            $value,
            $inSuccessfulContractCall,
            $txHash,
            pagingToken: $pagingToken
        );
    }

    /**
     * @return string The type of event emission. Possible values: contract, diagnostic, system.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type The type of event emission. Possible values: contract, diagnostic, system.
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return int Sequence number of the ledger in which this event was emitted.
     */
    public function getLedger(): int
    {
        return $this->ledger;
    }

    /**
     * @param int $ledger Sequence number of the ledger in which this event was emitted.
     */
    public function setLedger(int $ledger): void
    {
        $this->ledger = $ledger;
    }

    /**
     * @return string ISO-8601 timestamp of the ledger closing time.
     */
    public function getLedgerClosedAt(): string
    {
        return $this->ledgerClosedAt;
    }

    /**
     * @param string $ledgerClosedAt ISO-8601 timestamp of the ledger closing time.
     */
    public function setLedgerClosedAt(string $ledgerClosedAt): void
    {
        $this->ledgerClosedAt = $ledgerClosedAt;
    }

    /**
     * @return string StrKey representation of the contract address that emitted this event. ("C...").
     */
    public function getContractId(): string
    {
        return $this->contractId;
    }

    /**
     * @param string $contractId StrKey representation of the contract address that emitted this event. ("C...").
     */
    public function setContractId(string $contractId): void
    {
        $this->contractId = $contractId;
    }

    /**
     * @return string Unique identifier for this event.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id Unique identifier for this event.
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return array<String> List containing the topic this event was emitted with. (>= 1 items, <= 4 items).
     */
    public function getTopic(): array
    {
        return $this->topic;
    }

    /**
     * @param array<String> $topic List containing the topic this event was emitted with. (>= 1 items, <= 4 items).
     */
    public function setTopic(array $topic): void
    {
        $this->topic = $topic;
    }

    /**
     * @return XdrSCVal The emitted body value of the event.
     */
    public function getValueXdr(): XdrSCVal {
        return XdrSCVal::fromBase64Xdr($this->value);
    }

    /**
     * @return string The emitted body value of the event (serialized in a base64 xdr string).
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value The emitted body value of the event (serialized in a base64 xdr string).
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    /**
     * @return bool If true the event was emitted during a successful contract call.
     */
    public function isInSuccessfulContractCall(): bool
    {
        return $this->inSuccessfulContractCall;
    }

    /**
     * @param bool $inSuccessfulContractCall If true the event was emitted during a successful contract call.
     */
    public function setInSuccessfulContractCall(bool $inSuccessfulContractCall): void
    {
        $this->inSuccessfulContractCall = $inSuccessfulContractCall;
    }

    /**
     * @return string|null for paging, only available for protocol version < 22
     */
    public function getPagingToken(): ?string
    {
        return $this->pagingToken;
    }

    /**
     * @param string|null $pagingToken for paging, only for protocol version < 22
     */
    public function setPagingToken(?string $pagingToken): void
    {
        $this->pagingToken = $pagingToken;
    }

    /**
     * @return string
     */
    public function getTxHash(): string
    {
        return $this->txHash;
    }

    /**
     * @param string $txHash
     */
    public function setTxHash(string $txHash): void
    {
        $this->txHash = $txHash;
    }

}