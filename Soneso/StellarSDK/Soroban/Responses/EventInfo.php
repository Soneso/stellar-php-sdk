<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * Part of the getEvents request.
 *
 * @package Soneso\StellarSDK\Soroban\Responses
 * @see https://developers.stellar.org/network/soroban-rpc/api-reference/methods/getEvents
 * @see GetEventsResponse For the complete event query response
 */
class EventInfo
{
    /**
     * @param string $type The type of event emission (contract, diagnostic, or system)
     * @param int $ledger Sequence number of the ledger in which this event was emitted
     * @param string $ledgerClosedAt ISO-8601 timestamp of the ledger closing time
     * @param string $contractId StrKey representation of the contract address that emitted this event
     * @param string $id Unique identifier for this event
     * @param array<string> $topic List of topic values this event was emitted with (1-4 items)
     * @param string $value The emitted body value of the event (base64-encoded XDR string)
     * @param bool|null $inSuccessfulContractCall Deprecated indicator if the event was emitted during successful contract call
     * @param string $txHash The transaction hash which triggered this event
     * @param int|null $opIndex Operation index within the transaction (protocol >= 23)
     * @param int|null $txIndex Transaction index within the ledger (protocol >= 23)
     */
    public function __construct(
        public string $type,
        public int $ledger,
        public string $ledgerClosedAt,
        public string $contractId,
        public string $id,
        public array $topic,
        public string $value,
        public ?bool $inSuccessfulContractCall,
        public string $txHash,
        public ?int $opIndex = null,
        public ?int $txIndex = null,
    )
    {
    }

    /**
     * Creates an instance from JSON-RPC response data
     *
     * @param array<string,mixed> $json The JSON response data
     * @return static The created instance
     */
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
     * @return string The type of event emission (contract, diagnostic, or system)
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type The type of event emission
     * @return void
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return int Sequence number of the ledger in which this event was emitted
     */
    public function getLedger(): int
    {
        return $this->ledger;
    }

    /**
     * @param int $ledger Sequence number of the ledger
     * @return void
     */
    public function setLedger(int $ledger): void
    {
        $this->ledger = $ledger;
    }

    /**
     * @return string ISO-8601 timestamp of the ledger closing time
     */
    public function getLedgerClosedAt(): string
    {
        return $this->ledgerClosedAt;
    }

    /**
     * @param string $ledgerClosedAt ISO-8601 timestamp of the ledger closing time
     * @return void
     */
    public function setLedgerClosedAt(string $ledgerClosedAt): void
    {
        $this->ledgerClosedAt = $ledgerClosedAt;
    }

    /**
     * @return string StrKey representation of the contract address that emitted this event
     */
    public function getContractId(): string
    {
        return $this->contractId;
    }

    /**
     * @param string $contractId StrKey representation of the contract address
     * @return void
     */
    public function setContractId(string $contractId): void
    {
        $this->contractId = $contractId;
    }

    /**
     * @return string Unique identifier for this event
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id Unique identifier for this event
     * @return void
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return array<string> List of topic values this event was emitted with
     */
    public function getTopic(): array
    {
        return $this->topic;
    }

    /**
     * @param array<string> $topic List of topic values
     * @return void
     */
    public function setTopic(array $topic): void
    {
        $this->topic = $topic;
    }

    /**
     * @return string The emitted body value of the event (base64-encoded XDR string)
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value The emitted body value of the event
     * @return void
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    /**
     * @return bool|null Deprecated indicator if the event was emitted during successful contract call
     */
    public function getInSuccessfulContractCall(): ?bool
    {
        return $this->inSuccessfulContractCall;
    }

    /**
     * @param bool|null $inSuccessfulContractCall Deprecated success indicator
     * @return void
     */
    public function setInSuccessfulContractCall(?bool $inSuccessfulContractCall): void
    {
        $this->inSuccessfulContractCall = $inSuccessfulContractCall;
    }

    /**
     * @return string The transaction hash which triggered this event
     */
    public function getTxHash(): string
    {
        return $this->txHash;
    }

    /**
     * @param string $txHash The transaction hash
     * @return void
     */
    public function setTxHash(string $txHash): void
    {
        $this->txHash = $txHash;
    }

    /**
     * @return int|null Operation index within the transaction (protocol >= 23)
     */
    public function getOpIndex(): ?int
    {
        return $this->opIndex;
    }

    /**
     * @param int|null $opIndex Operation index
     * @return void
     */
    public function setOpIndex(?int $opIndex): void
    {
        $this->opIndex = $opIndex;
    }

    /**
     * @return int|null Transaction index within the ledger (protocol >= 23)
     */
    public function getTxIndex(): ?int
    {
        return $this->txIndex;
    }

    /**
     * @param int|null $txIndex Transaction index
     * @return void
     */
    public function setTxIndex(?int $txIndex): void
    {
        $this->txIndex = $txIndex;
    }
}