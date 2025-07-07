<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

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
     * @var bool|null $inSuccessfulContractCall (deprecated) If true the event was emitted during a successful contract call.
     */
    public ?bool $inSuccessfulContractCall;

    /**
     * @var string $txHash The transaction which triggered this event.
     */
    public string $txHash;

    // starting from protocol 23 opIndex, txIndex will be filled.
    /**
     * @var int|null $opIndex operation index, only available for protocol >= 23
     */
    public ?int $opIndex = null;

    /**
     * @var int|null $txIndex transaction index, only available for protocol >= 23
     */
    public ?int $txIndex = null;


    /**
     * @param string $type The type of event emission. Possible values: contract, diagnostic, system.
     * @param int $ledger Sequence number of the ledger in which this event was emitted.
     * @param string $ledgerClosedAt ISO-8601 timestamp of the ledger closing time.
     * @param string $contractId StrKey representation of the contract address that emitted this event. ("C...").
     * @param string $id Unique identifier for this event.
     * @param array<String> $topic List containing the topic this event was emitted with. (>= 1 items, <= 4 items).
     * @param string $value The emitted body value of the event (serialized in a base64 xdr string).
     * @param bool|null $inSuccessfulContractCall If true the event was emitted during a successful contract call.
     * @param string $txHash The transaction which triggered this event.
     * @param int|null $opIndex operation index.
     * @param int|null $txIndex transaction index.
     */
    public function __construct(
        string $type,
        int $ledger,
        string $ledgerClosedAt,
        string $contractId,
        string $id,
        array $topic,
        string $value,
        ?bool $inSuccessfulContractCall,
        string $txHash,
        ?int $opIndex = null,
        ?int $txIndex = null,
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
        $this->opIndex = $opIndex;
        $this->txIndex = $txIndex;
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
        $inSuccessfulContractCall = null;
        if (isset($json['inSuccessfulContractCall'])) {
            $inSuccessfulContractCall = $json['inSuccessfulContractCall'];
        }

        $txHash = $json['txHash'];

        $opIndex = null;
        if (isset($json['opIndex'])) {
            $opIndex = $json['opIndex'];
        }

        $txIndex = null;
        if (isset($json['txIndex'])) {
            $txIndex = $json['txIndex'];
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
            opIndex: $opIndex,
            txIndex: $txIndex
        );
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getLedger(): int
    {
        return $this->ledger;
    }

    /**
     * @param int $ledger
     */
    public function setLedger(int $ledger): void
    {
        $this->ledger = $ledger;
    }

    /**
     * @return string
     */
    public function getLedgerClosedAt(): string
    {
        return $this->ledgerClosedAt;
    }

    /**
     * @param string $ledgerClosedAt
     */
    public function setLedgerClosedAt(string $ledgerClosedAt): void
    {
        $this->ledgerClosedAt = $ledgerClosedAt;
    }

    /**
     * @return string
     */
    public function getContractId(): string
    {
        return $this->contractId;
    }

    /**
     * @param string $contractId
     */
    public function setContractId(string $contractId): void
    {
        $this->contractId = $contractId;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return array
     */
    public function getTopic(): array
    {
        return $this->topic;
    }

    /**
     * @param array $topic
     */
    public function setTopic(array $topic): void
    {
        $this->topic = $topic;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    /**
     * @return bool|null
     */
    public function getInSuccessfulContractCall(): ?bool
    {
        return $this->inSuccessfulContractCall;
    }

    /**
     * @param bool|null $inSuccessfulContractCall
     */
    public function setInSuccessfulContractCall(?bool $inSuccessfulContractCall): void
    {
        $this->inSuccessfulContractCall = $inSuccessfulContractCall;
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

    /**
     * @return int|null
     */
    public function getOpIndex(): ?int
    {
        return $this->opIndex;
    }

    /**
     * @param int|null $opIndex
     */
    public function setOpIndex(?int $opIndex): void
    {
        $this->opIndex = $opIndex;
    }

    /**
     * @return int|null
     */
    public function getTxIndex(): ?int
    {
        return $this->txIndex;
    }

    /**
     * @param int|null $txIndex
     */
    public function setTxIndex(?int $txIndex): void
    {
        $this->txIndex = $txIndex;
    }
}