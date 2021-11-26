<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Responses\Link\LinkResponse;

class EffectLinksResponse
{
    private LinkResponse $operation;
    private LinkResponse $precedes;
    private LinkResponse $succeeds;

    /**
     * @return LinkResponse
     */
    public function getOperation(): LinkResponse
    {
        return $this->operation;
    }

    /**
     * @return LinkResponse
     */
    public function getPrecedes(): LinkResponse
    {
        return $this->precedes;
    }

    /**
     * @return LinkResponse
     */
    public function getSucceeds(): LinkResponse
    {
        return $this->succeeds;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['self'])) $this->self = LinkResponse::fromJson($json['self']);
        if (isset($json['effects'])) $this->effects = LinkResponse::fromJson($json['effects']);
        if (isset($json['operation'])) $this->operation = LinkResponse::fromJson($json['operation']);
        if (isset($json['precedes'])) $this->precedes = LinkResponse::fromJson($json['precedes']);
        if (isset($json['succeeds'])) $this->succeeds = LinkResponse::fromJson($json['succeeds']);
    }

    public static function fromJson(array $json) : EffectLinksResponse {
        $result = new EffectLinksResponse();
        $result->loadFromJson($json);
        return $result;
    }
}