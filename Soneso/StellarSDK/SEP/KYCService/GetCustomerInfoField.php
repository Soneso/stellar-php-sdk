<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\KYCService;

use Soneso\StellarSDK\Responses\Response;

/**
 * Represents a KYC field that the anchor requires but has not yet received from the customer.
 *
 * The CustomerInfoField object defines the pieces of information the anchor has not yet received
 * for the customer. It is required for the NEEDS_INFO status but may be included with any status.
 * Fields should be specified as an object with keys representing the SEP-9 field names required.
 * Customers in the ACCEPTED status should not have any required fields present in the object,
 * since all required fields should have already been provided.
 *
 * @package Soneso\StellarSDK\SEP\KYCService
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#fields SEP-12 v1.15.0
 */
class GetCustomerInfoField extends Response
{
    /**
     * @var string $type The data type of the field value. Can be "string", "binary", "number", or "date".
     */
    public string $type;
    /**
     * @var string|null $description A human-readable description of this field, especially important if this is not a SEP-9 field.
     */
    public ?string $description = null;
    /**
     * @var array<string>|null $choices An array of valid values for this field.
     */
    public ?array $choices = null;

    /**
     * @var bool $optional A boolean whether this field is required to proceed or not. Defaults to false.
     */
    public bool $optional  = false;

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return array<string>|null
     */
    public function getChoices(): ?array
    {
        return $this->choices;
    }

    /**
     * @return bool
     */
    public function isOptional(): bool
    {
        return $this->optional;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['type'])) $this->type = $json['type'];
        if (isset($json['description'])) $this->description = $json['description'];
        if (isset($json['choices'])) {
            $this->choices = array();
            foreach ($json['choices'] as $choice) {
                $this->choices[] = $choice;
            }
        }
        if (isset($json['optional'])) $this->optional = $json['optional'];
    }

    public static function fromJson(array $json) : GetCustomerInfoField
    {
        $result = new GetCustomerInfoField();
        $result->loadFromJson($json);
        return $result;
    }

}