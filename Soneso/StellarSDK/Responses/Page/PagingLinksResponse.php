<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Page;

use Soneso\StellarSDK\Responses\Link\LinkResponse;

class PagingLinksResponse
{
    private LinkResponse $self;
    private ?LinkResponse $next;
    private ?LinkResponse $prev;

    /**
     * @return LinkResponse
     */
    public function getSelf(): LinkResponse
    {
        return $this->self;
    }

    /**
     * @return LinkResponse|null
     */
    public function getNext(): ?LinkResponse
    {
        return $this->next;
    }

    /**
     * @return LinkResponse|null
     */
    public function getPrev(): ?LinkResponse
    {
        return $this->prev;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['self'])) $this->self = LinkResponse::fromJson($json['self']);
        if (isset($json['next'])) $this->next = LinkResponse::fromJson($json['next']);
        if (isset($json['prev'])) $this->prev = LinkResponse::fromJson($json['prev']);
    }

    public static function fromJson(array $json) : PagingLinksResponse {
        $result = new PagingLinksResponse();
        $result->loadFromJson($json);
        return $result;
    }
}