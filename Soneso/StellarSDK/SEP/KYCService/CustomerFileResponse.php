<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\KYCService;


use Soneso\StellarSDK\Responses\Response;

/**
 * Response object for POST /customer/files endpoint.
 *
 * This response is returned after successfully uploading a file (such as ID documents, proof of
 * address, or other supporting documentation). The file_id can be used in subsequent PUT /customer
 * requests by appending _file_id to the appropriate SEP-9 field name.
 *
 * Files are stored temporarily and may expire if not referenced in a customer record within the
 * timeframe indicated by expires_at.
 *
 * @package Soneso\StellarSDK\SEP\KYCService
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#customer-files SEP-12 v1.15.0
 */
class CustomerFileResponse extends Response
{

    /**
     * @var string $fileId Unique identifier for the file object.
     */
    public string $fileId;

    /**
     * @var string $contentType The Content-Type of the file.
     */
    public string $contentType;

    /**
     * @var int $size The size in bytes of the file object.
     */
    public int $size;

    /**
     * @var string|null $expiresAt (optional) The date and time the file will be discarded by the server if not referenced by the client in a PUT /customer request.
     */
    public ?string $expiresAt = null;

    /**
     * @var string|null $customerId (optional) The id of the customer this file is associated with. If the customer record does not yet exist this will be null.
     */
    public ?string $customerId = null;

    protected function loadFromJson(array $json) : void {
        if (isset($json['file_id'])) $this->fileId = $json['file_id'];
        if (isset($json['content_type'])) $this->contentType = $json['content_type'];
        if (isset($json['size'])) $this->size = $json['size'];
        if (isset($json['expires_at'])) $this->expiresAt = $json['expires_at'];
        if (isset($json['customer_id'])) $this->customerId = $json['customer_id'];
    }

    public static function fromJson(array $json) : CustomerFileResponse
    {
        $result = new CustomerFileResponse();
        $result->loadFromJson($json);
        return $result;
    }
}