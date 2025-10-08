<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Health;

use Soneso\StellarSDK\Responses\Response;

class HealthResponse extends Response
{
    private bool $databaseConnected;
    private bool $coreUp;
    private bool $coreSynced;

    public function getDatabaseConnected(): bool {
        return $this->databaseConnected;
    }

    public function getCoreUp(): bool {
        return $this->coreUp;
    }

    public function getCoreSynced(): bool {
        return $this->coreSynced;
    }

    protected function loadFromJson(array $json): void {
        if (isset($json['database_connected'])) {
            $this->databaseConnected = $json['database_connected'];
        }
        if (isset($json['core_up'])) {
            $this->coreUp = $json['core_up'];
        }
        if (isset($json['core_synced'])) {
            $this->coreSynced = $json['core_synced'];
        }

        parent::loadFromJson($json);
    }

    public static function fromJson(array $json): HealthResponse {
        $result = new HealthResponse();
        $result->loadFromJson($json);
        return $result;
    }
}