<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\KYCService;

use Soneso\StellarSDK\Responses\Response;

/**
 * Response object for PUT /customer endpoint operations.
 *
 * This response contains the customer identifier assigned by the anchor after successfully
 * creating or updating customer information. The ID can be used in subsequent requests to
 * identify the customer when querying status or updating information.
 *
 * @package Soneso\StellarSDK\SEP\KYCService
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#customer-put SEP-12 v1.15.0
 */
class PutCustomerInfoResponse extends Response
{
    /**
     * @var string|null $id An identifier for the updated or created customer.
     */
    private ?string $id = null;

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['id'])) $this->id = $json['id'];
    }

    public static function fromJson(array $json) : PutCustomerInfoResponse
    {
        $result = new PutCustomerInfoResponse();
        $result->loadFromJson($json);
        return $result;
    }
}