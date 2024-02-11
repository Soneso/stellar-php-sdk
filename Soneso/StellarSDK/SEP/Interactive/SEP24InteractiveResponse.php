<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\Responses\Response;

class SEP24InteractiveResponse extends Response
{
    /**
     * @var string $type Always set to 'interactive_customer_info_needed'.
     */
    public string $type;

    /**
     * @var string $url URL hosted by the anchor. The wallet should show this URL to the user as a popup.
     */
    public string $url;

    /**
     * @var string $id The anchor's internal ID for this deposit / withdrawal request.
     * The wallet should use this ID to query the /transaction endpoint to check status of the request.
     */
    public string $id;

    /**
     * Loads the needed data from a json array.
     * @param array<array-key, mixed> $json the data array to read from.
     * @return void
     */
    protected function loadFromJson(array $json) : void {
        if (isset($json['type'])) $this->type = $json['type'];
        if (isset($json['url'])) $this->url = $json['url'];
        if (isset($json['id'])) $this->id = $json['id'];
    }

    /**
     * Constructs a new instance of SEP24InteractiveResponse by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP24InteractiveResponse the object containing the parsed data.
     */
    public static function fromJson(array $json) : SEP24InteractiveResponse
    {
        $result = new SEP24InteractiveResponse();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * @return string Always set to 'interactive_customer_info_needed'.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type Always set to 'interactive_customer_info_needed'.
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string URL hosted by the anchor. The wallet should show this URL to the user as a popup.
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url URL hosted by the anchor. The wallet should show this URL to the user as a popup.
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return string The anchor's internal ID for this deposit / withdrawal request.
     * The wallet should use this ID to query the /transaction endpoint to check status of the request.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id The anchor's internal ID for this deposit / withdrawal request.
     * The wallet should use this ID to query the /transaction endpoint to check status of the request.
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }
}