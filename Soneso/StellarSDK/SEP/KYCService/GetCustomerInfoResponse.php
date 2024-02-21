<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\KYCService;

use Soneso\StellarSDK\Responses\Response;

/**
 * Represents a customer info request response.
 */
class GetCustomerInfoResponse extends Response
{
    /**
     * @var string|null $id ID of the customer, if the customer has already been created via a PUT /customer request.
     */
    public ?string $id = null;

    /**
     * @var string $status Status of the customers KYC process.
     */
    public string $status;

    /**
     * @var array<array-key, GetCustomerInfoField>|null $fields An array containing the fields the anchor has not yet received for the given customer of the type provided in the request. Required for customers in the NEEDS_INFO status. See Fields for more detailed information.
     */
    public ?array $fields = null;

    /**
     * @var array<array-key, GetCustomerInfoProvidedField>|null $providedFields An array containing the fields the anchor has received for the given customer. Required for customers whose information needs verification via customerVerification.
     */
    public ?array $providedFields = null;

    /**
     * @var string|null $message Human-readable message describing the current state of customer's KYC process.
     */
    public ?string $message = null;

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return array<array-key, GetCustomerInfoField>|null
     */
    public function getFields(): ?array
    {
        return $this->fields;
    }

    /**
     * @return array<array-key, GetCustomerInfoProvidedField>|null
     */
    public function getProvidedFields(): ?array
    {
        return $this->providedFields;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['id'])) $this->id = $json['id'];
        if (isset($json['status'])) $this->status = $json['status'];
        if (isset($json['message'])) $this->message = $json['message'];

        if (isset($json['fields'])) {
            $this->fields = array();
            $jsonFields = $json['fields'];
            foreach(array_keys($jsonFields) as $key) {
                $value = GetCustomerInfoField::fromJson($jsonFields[$key]);
                $this->fields += [$key => $value];
            }
        }
        if (isset($json['provided_fields'])) {
            $this->providedFields = array();
            $jsonProvidedFields = $json['provided_fields'];
            foreach(array_keys($jsonProvidedFields) as $key) {
                $value = GetCustomerInfoProvidedField::fromJson($jsonProvidedFields[$key]);
                $this->providedFields += [$key => $value];
            }
        }
    }

    public static function fromJson(array $json) : GetCustomerInfoResponse
    {
        $result = new GetCustomerInfoResponse();
        $result->loadFromJson($json);
        return $result;
    }
}