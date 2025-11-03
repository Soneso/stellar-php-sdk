<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Ledger;

use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Page\PageResponse;

/**
 * Represents a paginated collection of ledgers from Horizon
 *
 * @package Soneso\StellarSDK\Responses\Ledger
 * @see PageResponse For pagination functionality
 * @see LedgerResponse For individual ledger details
 * @see https://developers.stellar.org/api/resources/ledgers Horizon Ledgers API
 * @since 1.0.0
 */
class LedgersPageResponse extends PageResponse
{
    private LedgersResponse $ledgers;

    /**
     * Gets the collection of ledgers in this page
     *
     * @return LedgersResponse The ledgers collection
     */
    public function getLedgers(): LedgersResponse {
        return $this->ledgers;
    }

    protected function loadFromJson(array $json) : void {
        parent::loadFromJson($json);
        if (isset($json['_embedded']['records'])) {
            $this->ledgers = new LedgersResponse();
            foreach ($json['_embedded']['records'] as $jsonLedger) {
                $ledger = LedgerResponse::fromJson($jsonLedger);
                $this->ledgers->add($ledger);
            }
        }
    }

    public static function fromJson(array $json) : LedgersPageResponse {
        $result = new LedgersPageResponse();
        $result->loadFromJson($json);
        return $result;
    }

    public function getNextPage(): LedgersPageResponse | null {
        return $this->executeRequest(RequestType::LEDGERS_PAGE, $this->getNextPageUrl());
    }

    public function getPreviousPage(): LedgersPageResponse | null {
        return $this->executeRequest(RequestType::LEDGERS_PAGE, $this->getPrevPageUrl());
    }
}