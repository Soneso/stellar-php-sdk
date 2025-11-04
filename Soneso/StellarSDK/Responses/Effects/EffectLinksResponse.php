<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Responses\Link\LinkResponse;

/**
 * Represents HAL navigation links for effect resources
 *
 * This response provides hypermedia links to related resources and navigation
 * endpoints following the HAL specification. Enables traversal of effects and
 * related operations.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse
 * @see https://developers.stellar.org/api/introduction/response-format
 * @see https://developers.stellar.org/api/resources/effects
 */
class EffectLinksResponse
{
    private LinkResponse $operation;
    private LinkResponse $precedes;
    private LinkResponse $succeeds;

    /**
     * Gets the link to the operation that created this effect
     *
     * @return LinkResponse The operation link
     */
    public function getOperation(): LinkResponse
    {
        return $this->operation;
    }

    /**
     * Gets the link to the preceding effect
     *
     * @return LinkResponse The precedes link
     */
    public function getPrecedes(): LinkResponse
    {
        return $this->precedes;
    }

    /**
     * Gets the link to the succeeding effect
     *
     * @return LinkResponse The succeeds link
     */
    public function getSucceeds(): LinkResponse
    {
        return $this->succeeds;
    }

    /**
     * Loads object data from JSON array
     *
     * @param array $json JSON data array
     * @return void
     */
    protected function loadFromJson(array $json) : void {

        if (isset($json['self'])) $this->self = LinkResponse::fromJson($json['self']);
        if (isset($json['effects'])) $this->effects = LinkResponse::fromJson($json['effects']);
        if (isset($json['operation'])) $this->operation = LinkResponse::fromJson($json['operation']);
        if (isset($json['precedes'])) $this->precedes = LinkResponse::fromJson($json['precedes']);
        if (isset($json['succeeds'])) $this->succeeds = LinkResponse::fromJson($json['succeeds']);
    }

    /**
     * Creates an instance from JSON data
     *
     * @param array $json JSON data array
     * @return EffectLinksResponse
     */
    public static function fromJson(array $json) : EffectLinksResponse {
        $result = new EffectLinksResponse();
        $result->loadFromJson($json);
        return $result;
    }
}
