<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Page\PageResponse;

/**
 * Represents a paginated response containing multiple effects
 *
 * This response wraps a collection of effects with pagination metadata and navigation
 * links. Supports cursor-based pagination for efficient traversal of large effect sets.
 * Returned by Horizon effects endpoints.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse
 * @see PageResponse
 * @see https://developers.stellar.org/api/introduction/pagination
 * @see https://developers.stellar.org/api/resources/effects
 */
class EffectsPageResponse extends PageResponse
{
    private EffectsResponse $effects;

    /**
     * Gets the collection of effects in this page
     *
     * @return EffectsResponse The effects collection
     */
    public function getEffects(): EffectsResponse {
        return $this->effects;
    }


    /**
     * Loads object data from JSON array
     *
     * @param array $json JSON data array
     * @return void
     */
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

    /**
     * Creates an instance from JSON data
     *
     * @param array $json JSON data array
     * @return EffectsPageResponse
     */
    public static function fromJson(array $json) : EffectsPageResponse {
        $result = new EffectsPageResponse();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * Fetches the next page of effects
     *
     * @return EffectsPageResponse|null The next page, or null if none
     */
    public function getNextPage(): EffectsPageResponse | null {
        return $this->executeRequest(RequestType::EFFECTS_PAGE, $this->getNextPageUrl());
    }

    /**
     * Fetches the previous page of effects
     *
     * @return EffectsPageResponse|null The previous page, or null if none
     */
    public function getPreviousPage(): EffectsPageResponse | null {
        return $this->executeRequest(RequestType::EFFECTS_PAGE, $this->getPrevPageUrl());
    }
}
