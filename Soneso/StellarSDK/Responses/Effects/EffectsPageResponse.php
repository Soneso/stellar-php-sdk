<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Page\PageResponse;

class EffectsPageResponse extends PageResponse
{
    private EffectsResponse $effects;

    /**
     * @return EffectsResponse
     */
    public function getEffects(): EffectsResponse {
        return $this->effects;
    }


    protected function loadFromJson(array $json) : void {
        parent::loadFromJson($json);
        if (isset($json['_embedded']['records'])) {
            $this->effects = new EffectsResponse();
            foreach ($json['_embedded']['records'] as $jsonData) {
                $value = EffectResponse::fromJson($jsonData);
                $this->effects->add($value);
            }
        }
    }

    public static function fromJson(array $json) : EffectsPageResponse {
        $result = new EffectsPageResponse();
        $result->loadFromJson($json);
        return $result;
    }

    public function getNextPage(): EffectsPageResponse | null {
        return $this->executeRequest(RequestType::EFFECTS_PAGE, $this->getNextPageUrl());
    }

    public function getPreviousPage(): EffectsPageResponse | null {
        return $this->executeRequest(RequestType::EFFECTS_PAGE, $this->getPrevPageUrl());
    }
}