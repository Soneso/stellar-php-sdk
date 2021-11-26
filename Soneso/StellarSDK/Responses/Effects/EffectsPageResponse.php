<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Responses\Page\PageResponse;
use Soneso\StellarSDK\Responses\Page\PagingLinksResponse;

class EffectsPageResponse extends PageResponse
{
    private PagingLinksResponse $links;
    private EffectsResponse $effects;

    /**
     * @return PagingLinksResponse
     */
    public function getLinks(): PagingLinksResponse
    {
        return $this->links;
    }

    /**
     * @return EffectsResponse
     */
    public function getEffects(): EffectsResponse
    {
        return $this->effects;
    }


    protected function loadFromJson(array $json) : void {

        if (isset($json['_links'])) $this->links = PagingLinksResponse::fromJson($json['_links']);
        if (isset($json['_embedded']['records'])) {
            $this->effects = new EffectsResponse();
            foreach ($json['_embedded']['records'] as $jsonData) {
                $value = EffectResponse::fromJson($jsonData);
                $this->effects->add($value);
            }
        }
    }

    public static function fromJson(array $json) : EffectsPageResponse
    {
        $result = new EffectsPageResponse();
        $result->loadFromJson($json);
        return $result;
    }
}