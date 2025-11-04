<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Root;

use Soneso\StellarSDK\Responses\Response;

/**
 * Represents the Horizon API root endpoint response
 *
 * This response contains metadata about the Horizon server and the connected Stellar network.
 * The root endpoint provides essential information for clients including version numbers,
 * network identification, protocol versions, and ledger synchronization status.
 *
 * Key fields:
 * - Horizon and Stellar Core version information
 * - Network passphrase for network identification
 * - Current protocol version supported by the network
 * - Latest ledger numbers and timestamps for history and ingestion
 * - Elder ledger indicating oldest available historical data
 *
 * This endpoint is typically the first API call made by clients to verify connectivity,
 * determine network type (mainnet, testnet, etc.), check version compatibility, and assess
 * synchronization status before making other requests.
 *
 * Returned by Horizon endpoint:
 * - GET / - Root endpoint with server and network metadata
 *
 * @package Soneso\StellarSDK\Responses\Root
 * @see Response For base response functionality
 * @see https://developers.stellar.org/api/introduction Horizon API Introduction
 * @see https://developers.stellar.org/api/resources/get-network-info Horizon Root Endpoint
 */
class RootResponse extends Response
{
    private string $horizonVersion;
    private string $coreVersion;
    private int $ingestLatestLedger;
    private int $historyLatestLedger;
    private string $historyLatestLedgerClosedAt;
    private int $historyElderLedger;
    private int $coreLatestLedger;
    private string $networkPassphrase;
    private int $protocolVersion;
    private int $currentProtocolVersion;
    private int $coreSupportedProtocolVersion;
    

    /**
     * Gets the version of the Horizon API server
     *
     * @return string The Horizon version string
     */
    public function getHorizonVersion() : string {
        return $this->horizonVersion;
    }

    /**
     * Gets the version of the connected Stellar Core
     *
     * @return string The Stellar Core version string
     */
    public function getCoreVersion() : string {
        return $this->coreVersion;
    }

    /**
     * Gets the latest ledger sequence stored in Horizon's history database
     *
     * @return int The history latest ledger sequence
     */
    public function getHistoryLatestLedger() : int {
        return $this->historyLatestLedger;
    }

    /**
     * Gets the timestamp when the history latest ledger closed
     *
     * @return string The close time in ISO 8601 format
     */
    public function getHistoryLatestLedgerClosedAt() : string {
        return $this->historyLatestLedgerClosedAt;
    }

    /**
     * Gets the latest ledger sequence ingested by Horizon
     *
     * @return int The ingest latest ledger sequence
     */
    public function getIngestLatestLedger() : int {
        return $this->ingestLatestLedger;
    }

    /**
     * Gets the oldest ledger sequence available in Horizon's history
     *
     * @return int The history elder ledger sequence
     */
    public function getHistoryElderLedger() : int {
        return $this->historyElderLedger;
    }

    /**
     * Gets the latest ledger sequence known to Stellar Core
     *
     * @return int The core latest ledger sequence
     */
    public function getCoreLatestLedger() : int {
        return $this->coreLatestLedger;
    }

    /**
     * Gets the network passphrase identifying the Stellar network
     *
     * @return string The network passphrase (e.g., "Public Global Stellar Network ; September 2015")
     */
    public function getNetworkPassphrase() : string {
        return $this->networkPassphrase;
    }

    /**
     * Gets the protocol version supported by the network
     *
     * @return int The protocol version number
     */
    public function getProtocolVersion() : int {
        return $this->protocolVersion;
    }

    /**
     * Gets the current protocol version of the network
     *
     * @return int The current protocol version number
     */
    public function getCurrentProtocolVersion() : int {
        return $this->currentProtocolVersion;
    }

    /**
     * Gets the maximum protocol version supported by Stellar Core
     *
     * @return int The core supported protocol version number
     */
    public function getCoreSupportedProtocolVersion() : int {
        return $this->coreSupportedProtocolVersion;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['horizon_version'])) $this->horizonVersion = $json['horizon_version'];
        if (isset($json['core_version'])) $this->coreVersion = $json['core_version'];
        if (isset($json['ingest_latest_ledger'])) $this->ingestLatestLedger = $json['ingest_latest_ledger'];
        if (isset($json['history_latest_ledger'])) $this->historyLatestLedger = $json['history_latest_ledger'];
        if (isset($json['history_latest_ledger_closed_at'])) $this->historyLatestLedgerClosedAt = $json['history_latest_ledger_closed_at'];
        if (isset($json['history_elder_ledger'])) $this->historyElderLedger = $json['history_elder_ledger'];
        if (isset($json['core_latest_ledger'])) $this->coreLatestLedger = $json['core_latest_ledger'];
        if (isset($json['network_passphrase']))$this->networkPassphrase = $json['network_passphrase'];
        if (isset($json['protocol_version'])) $this->protocolVersion = $json['protocol_version'];
        if (isset($json['current_protocol_version'])) $this->currentProtocolVersion = $json['current_protocol_version'];
        if (isset($json['core_supported_protocol_version'])) $this->coreSupportedProtocolVersion = $json['core_supported_protocol_version'];
        
        parent::loadFromJson($json);
    }
    
    /**
     * Creates a RootResponse from JSON data
     *
     * @param array $json Associative array of parsed JSON response
     * @return RootResponse The populated root response
     */
    public static function fromJson(array $json) : RootResponse {
        $result = new RootResponse();
        $result->loadFromJson($json);
        return $result;
    }
}

