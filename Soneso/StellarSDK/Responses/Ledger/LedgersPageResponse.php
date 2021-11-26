<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Ledger;

use Soneso\StellarSDK\Responses\Page\PageResponse;
use Soneso\StellarSDK\Responses\Page\PagingLinksResponse;

class LedgersPageResponse extends PageResponse
{
    private PagingLinksResponse $links;
    private LedgersResponse $ledgers;

    /**
     * @return PagingLinksResponse
     */
    public function getLinks(): PagingLinksResponse
    {
        return $this->links;
    }

    /**
     * @return LedgersResponse
     */
    public function getLedgers(): LedgersResponse
    {
        return $this->ledgers;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['_links'])) $this->links = PagingLinksResponse::fromJson($json['_links']);
        if (isset($json['_embedded']['records'])) {
            $this->ledgers = new LedgersResponse();
            foreach ($json['_embedded']['records'] as $jsonLedger) {
                $ledger = LedgerResponse::fromJson($jsonLedger);
                $this->ledgers->add($ledger);
            }
        }
    }

    public static function fromJson(array $json) : LedgersPageResponse
    {
        $result = new LedgersPageResponse();
        $result->loadFromJson($json);
        return $result;
    }
}