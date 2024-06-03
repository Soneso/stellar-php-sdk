<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Soroban\Responses;

use Soneso\StellarSDK\Xdr\XdrLedgerEntry;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;

/**
 * Part of the simulate transaction response
 * See: https://developers.stellar.org/network/soroban-rpc/api-reference/methods/simulateTransaction
 */
class LedgerEntryChange
{
    /**
     * @var string $type 'created', 'updated' or 'deleted'
     */
    public string $type;
    /**
     * @var string $key XdrLedgerKey in base64 for this delta
     */
    public string $key;

    /**
     * @var string|null $before XdrLedgerEntry in base64 (state prior to simulation)
     */
    public ?string $before = null;

    /**
     * @var string|null $after XdrLedgerEntry in base64 (state after simulation)
     */
    public ?string $after = null;

    /**
     * @param string $type Indicates if the entry was 'created', 'updated', or 'deleted'
     * @param string $key the XdrLedgerKey in base64 for this delta
     * @param string|null $before XdrLedgerEntry in base64 (state prior to simulation)
     * @param string|null $after XdrLedgerEntry in base64 (state after simulation)
     */
    public function __construct(
        string $type,
        string $key,
        ?string $before = null,
        ?string $after = null,
    )
    {
        $this->type = $type;
        $this->key = $key;
        $this->before = $before;
        $this->after = $after;
    }

    public static function fromJson(array $json) : LedgerEntryChange {
        $before = null;
        if(isset($json['before'])) {
            $before = $json['before'];
        }

        $after = null;
        if(isset($json['after'])) {
            $after = $json['after'];
        }
        return new LedgerEntryChange(
            type: $json['type'],
            key: $json['key'],
            before: $before,
            after: $after,
        );
    }

    /**
     * @return string Indicates if the entry was 'created', 'updated', or 'deleted'
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type Indicates if the entry was 'created', 'updated', or 'deleted'
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string the XdrLedgerKey in base64 for this delta.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key the XdrLedgerKey in base64 for this delta
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * @return string|null XdrLedgerEntry in base64 (state prior to simulation)
     */
    public function getBefore(): ?string
    {
        return $this->before;
    }

    /**
     * @param string|null $before XdrLedgerEntry in base64 (state prior to simulation)
     */
    public function setBefore(?string $before): void
    {
        $this->before = $before;
    }

    /**
     * @return string|null XdrLedgerEntry in base64 (state after simulation)
     */
    public function getAfter(): ?string
    {
        return $this->after;
    }

    /**
     * @param string|null $after XdrLedgerEntry in base64 (state after simulation)
     */
    public function setAfter(?string $after): void
    {
        $this->after = $after;
    }

    /**
     * @return XdrLedgerKey the XdrLedgerKey for this delta
     */
    public function getKeyXdr() : XdrLedgerKey {
        return XdrLedgerKey::fromBase64Xdr($this->key);
    }

    /**
     * @return XdrLedgerEntry|null XdrLedgerEntry (state after to simulation)
     */
    public function getAfterXdr() : ?XdrLedgerEntry {
        if($this->after !== null) {
            return XdrLedgerEntry::fromBase64Xdr($this->after);
        }
        return null;
    }

    /**
     * @return XdrLedgerEntry|null XdrLedgerEntry (state prior to simulation)
     */
    public function getBeforeXdr() : ?XdrLedgerEntry {
        if($this->before !== null) {
            return XdrLedgerEntry::fromBase64Xdr($this->before);
        }
        return null;
    }

}