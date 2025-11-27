<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * Represents a single ledger in the getLedgers response.
 *
 * @package Soneso\StellarSDK\Soroban\Responses
 * @see https://developers.stellar.org/docs/data/rpc/api-reference/methods/getLedgers
 */
class LedgerInfo
{
    /**
     * @param string $hash Hash of the ledger as a hex-encoded string
     * @param int $sequence Sequence number of the ledger
     * @param string $ledgerCloseTime Unix timestamp of the ledger close time as a string
     * @param string|null $headerXdr Base64-encoded ledger header XDR
     * @param string|null $metadataXdr Base64-encoded ledger metadata XDR
     */
    public function __construct(
        public string $hash,
        public int $sequence,
        public string $ledgerCloseTime,
        public ?string $headerXdr = null,
        public ?string $metadataXdr = null,
    )
    {
    }

    /**
     * Creates an instance from JSON-RPC response data
     *
     * @param array<string,mixed> $json The JSON response data
     * @return static The created instance
     */
    public static function fromJson(array $json): LedgerInfo
    {
        return new LedgerInfo(
            $json['hash'],
            $json['sequence'],
            $json['ledgerCloseTime'],
            $json['headerXdr'] ?? null,
            $json['metadataXdr'] ?? null,
        );
    }

    /**
     * @return string Hash of the ledger as a hex-encoded string.
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @param string $hash Hash of the ledger as a hex-encoded string.
     */
    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }

    /**
     * @return int Sequence number of the ledger.
     */
    public function getSequence(): int
    {
        return $this->sequence;
    }

    /**
     * @param int $sequence Sequence number of the ledger.
     */
    public function setSequence(int $sequence): void
    {
        $this->sequence = $sequence;
    }

    /**
     * @return string The unix timestamp of the close time of the ledger as a string.
     */
    public function getLedgerCloseTime(): string
    {
        return $this->ledgerCloseTime;
    }

    /**
     * @param string $ledgerCloseTime The unix timestamp of the close time of the ledger as a string.
     */
    public function setLedgerCloseTime(string $ledgerCloseTime): void
    {
        $this->ledgerCloseTime = $ledgerCloseTime;
    }

    /**
     * @return string|null Base64-encoded ledger header XDR (optional).
     */
    public function getHeaderXdr(): ?string
    {
        return $this->headerXdr;
    }

    /**
     * @param string|null $headerXdr Base64-encoded ledger header XDR (optional).
     */
    public function setHeaderXdr(?string $headerXdr): void
    {
        $this->headerXdr = $headerXdr;
    }

    /**
     * @return string|null Base64-encoded ledger metadata XDR (optional).
     */
    public function getMetadataXdr(): ?string
    {
        return $this->metadataXdr;
    }

    /**
     * @param string|null $metadataXdr Base64-encoded ledger metadata XDR (optional).
     */
    public function setMetadataXdr(?string $metadataXdr): void
    {
        $this->metadataXdr = $metadataXdr;
    }
}
