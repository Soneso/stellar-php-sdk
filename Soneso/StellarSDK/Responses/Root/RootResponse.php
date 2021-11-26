<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Root;

use Soneso\StellarSDK\Responses\Response;

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
    

    public function getHorizonVersion() : string {
        return $this->horizonVersion;
    }

    public function getCoreVersion() : string {
        return $this->coreVersion;
    }

    public function getHistoryLatestLedger() : int {
        return $this->historyLatestLedger;
    }

    public function getHistoryLatestLedgerClosedAt() : string {
        return $this->historyLatestLedgerClosedAt;
    }

    public function getIngestLatestLedger() : int {
        return $this->ingestLatestLedger;
    }

    public function getHistoryElderLedger() : int {
        return $this->historyElderLedger;
    }

    public function getCoreLatestLedger() : int {
        return $this->coreLatestLedger;
    }

    public function getNetworkPassphrase() : string {
        return $this->networkPassphrase;
    }

    public function getProtocolVersion() : int {
        return $this->protocolVersion;
    }

    public function getCurrentProtocolVersion() : int {
        return $this->currentProtocolVersion;
    }

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
    
    public static function fromJson(array $json) : RootResponse {
        $result = new RootResponse();
        $result->loadFromJson($json);
        return $result;
    }
}

