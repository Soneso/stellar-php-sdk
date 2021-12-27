<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Account;

class AccountDataResponse
{
    private array $data;

    public function __construct(array $data = array()) {
        $this->data = $data;
    }
    
    public function get(string $key) : ?string {
        if (array_key_exists($key, $this->getData())) {
            return base64_decode($this->data[$key], true);
        }
        return null;
    }

    public function getBase64Encoded(string $key) : ?string {
        if (array_key_exists($key, $this->getData())) {
            return $this->data[$key];
        }
        return null;
    }

    public function getKeys() : array {
        return array_keys($this->getData());
    }
    
    public function getData() : array {
        return $this->data;
    }
    
    public static function fromJson(array $json) : AccountDataResponse {
        $accountData = array();
        if (isset($json['data'])) {
            foreach ($json['data'] as $key => $value) {
                $accountData[$key] = $value;
            }
        }
        return new AccountDataResponse($accountData);
    }
}

