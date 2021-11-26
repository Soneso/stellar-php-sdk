<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Account;

class AccountSignerResponse
{

    private string $key;
    private string $type;
    private int $weight;
    private string $sponsor;
    
    public function getKey() : string {
        return $this->key;
    }

    public function getType() : string {
        return $this->type;
    }

    public function getWeight() : int {
        return $this->weight;
    }

    public function getSponsor() : string {
        return $this->sponsor;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['key'])) $this->key = $json['key'];
        if (isset($json['type'])) $this->type = $json['type'];
        if (isset($json['weight'])) $this->weight = $json['weight'];
        if (isset($json['sponsor'])) $this->sponsor = $json['sponsor'];
    }
    
    public static function fromJson(array $json) : AccountSignerResponse {
        $result = new AccountSignerResponse();
        $result->loadFromJson($json);
        return $result;
    }
    
}

