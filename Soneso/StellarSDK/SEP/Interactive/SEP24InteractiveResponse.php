<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\Responses\Response;

class SEP24InteractiveResponse extends Response
{
    /// Always set to interactive_customer_info_needed.
    public string $type;

    /// URL hosted by the anchor. The wallet should show this URL to the user as a popup.
    public string $url;

    /// The anchor's internal ID for this deposit / withdrawal request. The wallet will use this ID to query the /transaction endpoint to check status of the request.
    public string $id;

    protected function loadFromJson(array $json) : void {
        if (isset($json['type'])) $this->type = $json['type'];
        if (isset($json['url'])) $this->url = $json['url'];
        if (isset($json['id'])) $this->id = $json['id'];
    }

    public static function fromJson(array $json) : SEP24InteractiveResponse
    {
        $result = new SEP24InteractiveResponse();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }
}