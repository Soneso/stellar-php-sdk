<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Link;

class LinkResponse {

    private string $href;
    private bool $templated;
    
    public function getHref() : string {
        return $this->href;
    }
    
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

