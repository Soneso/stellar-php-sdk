<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Health;

use Soneso\StellarSDK\Responses\Response;

/**
 * Represents the health status of a Horizon instance
 *
 * Contains boolean indicators of database connectivity, Stellar Core status,
 * and synchronization state. Used for monitoring and health checks.
 *
 * @package Soneso\StellarSDK\Responses\Health
 * @see https://developers.stellar.org Stellar developer docs Horizon Health Check
 * @since 1.0.0
 */
class HealthResponse extends Response
{
    private bool $databaseConnected;
    private bool $coreUp;
    private bool $coreSynced;

    /**
     * Gets whether the database is connected
     *
     * @return bool True if database connection is active
     */
    public function getDatabaseConnected(): bool {
        return $this->databaseConnected;
    }

    /**
     * Gets whether Stellar Core is up
     *
     * @return bool True if Stellar Core is running
     */
    public function getCoreUp(): bool {
        return $this->coreUp;
    }

    /**
     * Gets whether Stellar Core is synced with the network
     *
     * @return bool True if Stellar Core is synchronized
     */
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