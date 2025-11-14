<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Link;

/**
 * Represents a hypermedia link in Horizon API responses
 *
 * Links are used throughout Horizon responses to provide navigation between related resources
 * following the HAL specification. Contains the href URL and whether it requires template expansion.
 *
 * @package Soneso\StellarSDK\Responses\Link
 * @see https://developers.stellar.org Stellar developer docs Horizon Response Format
 * @since 1.0.0
 */
class LinkResponse {

    private string $href;
    private bool $templated;

    /**
     * Gets the link href URL
     *
     * @return string The URL to the linked resource
     */
    public function getHref() : string {
        return $this->href;
    }

    /**
     * Checks if the link is templated
     *
     * @return bool True if the href contains URI template variables
     */
    public function isTemplated() : bool {
        return $this->templated;
    }
    
    protected function loadFromJson(array $json) : void {
        if (isset($json['href'])) $this->href = $json['href'];
        if (isset($json['templated'])) $this->templated = $json['templated'];
    }
    
    public static function fromJson(array $json) : LinkResponse {
        $result = new LinkResponse();
        $result->loadFromJson($json);
        return $result;
    }
}

