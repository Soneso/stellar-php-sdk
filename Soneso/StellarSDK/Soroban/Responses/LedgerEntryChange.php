<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Soroban\Responses;

use Soneso\StellarSDK\Xdr\XdrLedgerEntry;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;

class LedgerEntryChange
{
    /**
     * @var string $type 'created', 'updated' or 'deleted'
     */
    public string $type;
    /**
     * @var string $key XdrLedgerKey in base64
     */
    public string $key;

    /**
     * @var string|null $before XdrLedgerEntry in base64
     */
    public ?string $before;

    /**
     * @var string|null $after XdrLedgerEntry in base64
     */
    public ?string $after;

    /**
     * @param string $type
     * @param string $key
     * @param string|null $before
     * @param string|null $after
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
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * @return string|null
     */
    public function getBefore(): ?string
    {
        return $this->before;
    }

    /**
     * @param string|null $before
     */
    public function setBefore(?string $before): void
    {
        $this->before = $before;
    }

    /**
     * @return string|null
     */
    public function getAfter(): ?string
    {
        return $this->after;
    }

    /**
     * @param string|null $after
     */
    public function setAfter(?string $after): void
    {
        $this->after = $after;
    }

    public function getKeyXdr() : XdrLedgerKey {
        return XdrLedgerKey::fromBase64Xdr($this->key);
    }

    public function getAfterXdr() : ?XdrLedgerEntry {
        if($this->after !== null) {
            return XdrLedgerEntry::fromBase64Xdr($this->after);
        }
        return null;
    }

    public function getBeforeXdr() : ?XdrLedgerEntry {
        if($this->before !== null) {
            return XdrLedgerEntry::fromBase64Xdr($this->before);
        }
        return null;
    }

}