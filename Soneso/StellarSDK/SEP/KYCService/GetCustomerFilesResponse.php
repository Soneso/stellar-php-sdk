<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\KYCService;

use Soneso\StellarSDK\Responses\Response;

/**
 * Response object for GET /customer/files endpoint.
 *
 * This response contains a list of files that have been uploaded for a customer. Files can be
 * queried by file_id to retrieve a specific file, or by customer_id to retrieve all files
 * associated with a customer.
 *
 * @package Soneso\StellarSDK\SEP\KYCService
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#customer-files SEP-12 v1.15.0
 */
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