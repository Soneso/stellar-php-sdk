<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\KYCService;

use Soneso\StellarSDK\Responses\Response;

class GetCustomerFilesResponse extends Response
{

    /**
     * @var array<CustomerFileResponse> $files
     */
    public array $files = array();


    protected function loadFromJson(array $json) : void {
        if (isset($json['files'])) {
            foreach ($json['files'] as $file) {
                $this->files[] = CustomerFileResponse::fromJson($file);
            }
        }
    }

    public static function fromJson(array $json) : GetCustomerFilesResponse
    {
        $result = new GetCustomerFilesResponse();
        $result->loadFromJson($json);
        return $result;
    }
}