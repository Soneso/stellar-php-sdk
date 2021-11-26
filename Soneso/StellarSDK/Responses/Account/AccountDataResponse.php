<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Account;

use JetBrains\PhpStorm\Pure;

class AccountDataResponse
{
    private array $data;

    public function __construct(array $data = array()) {
        $this->data = $data;
    }
    
    public function get(string $key) : string {
        return $this->data[$key];
    }
    
    public function getData() : array {
        return $this->data;
    }
    
    /**
     * Decodes the data for the given if encoded with MIME base64
     * @link http://www.php.net/manual/en/function.base64-decode.php
     * @param string $key The key for the data.
     * @param bool $strict [optional] If the strict parameter is set to true
     * then the function will return
     * false if the input contains character from outside the base64
     * alphabet. Otherwise, invalid characters will be silently discarded.
     * @return string the decoded data or false on failure. The returned data may be
     * binary.
     */
    public function getDecoded(string $key, bool $strict = null) : string {
        return base64_decode($this->data[$key], $strict);
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

