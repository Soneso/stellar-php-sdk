<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\KYCService;

use Soneso\StellarSDK\Responses\Response;

class PutCustomerInfoResponse extends Response
{
    /// An identifier for the updated or created customer.
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