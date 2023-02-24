<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

class EventInfo
{
    public string $type;
    public string $ledger;
    public string $ledgerClosedAt; // datetime
    public string $contractId;
    public string $id;
    public string $pagingToken;
    public array $topic; // [str]
    public EventInfoValue $value;

    /**
     * @param string $type
     * @param string $ledger
     * @param string $ledgerClosedAt
     * @param string $contractId
     * @param string $id
     * @param string $pagingToken
     * @param array $topic
     * @param EventInfoValue $value
     */
    public function __construct(string $type, string $ledger, string $ledgerClosedAt, string $contractId, string $id, string $pagingToken, array $topic, EventInfoValue $value)
    {
        $this->type = $type;
        $this->ledger = $ledger;
        $this->ledgerClosedAt = $ledgerClosedAt;
        $this->contractId = $contractId;
        $this->id = $id;
        $this->pagingToken = $pagingToken;
        $this->topic = $topic;
        $this->value = $value;
    }

    public static function fromJson(array $json): EventInfo
    {
        $type = $json['type'];
        $ledger = $json['ledger'];
        $ledgerClosedAt = $json['ledgerClosedAt'];
        $contractId = $json['contractId'];
        $id = $json['id'];
        $pagingToken = $json['pagingToken'];
        $value = EventInfoValue::fromJson($json['value']);
        $topic = array();
        foreach ($json['topic'] as $val) {
            array_push($topic, $val);
        }

        return new EventInfo($type, $ledger, $ledgerClosedAt, $contractId, $id, $pagingToken, $topic, $value);
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
     * @return string
     */
    public function getLedger(): string
    {
        return $this->ledger;
    }

    /**
     * @param string $ledger
     */
    public function setLedger(string $ledger): void
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
     * @return string
     */
    public function getPagingToken(): string
    {
        return $this->pagingToken;
    }

    /**
     * @param string $pagingToken
     */
    public function setPagingToken(string $pagingToken): void
    {
        $this->pagingToken = $pagingToken;
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
     * @return EventInfoValue
     */
    public function getValue(): EventInfoValue
    {
        return $this->value;
    }

    /**
     * @param EventInfoValue $value
     */
    public function setValue(EventInfoValue $value): void
    {
        $this->value = $value;
    }

}