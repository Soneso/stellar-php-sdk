<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\KYCService;

use Soneso\StellarSDK\Responses\Response;

/**
 * Represents a KYC field that has been provided by the customer and its verification status.
 *
 * This object contains information about fields the anchor has already received from the customer.
 * Each field includes its verification status indicating whether it has been accepted, rejected,
 * or requires verification. This is particularly important for fields that need verification via
 * the customer verification endpoint.
 *
 * Field status values:
 * - ACCEPTED: The field has been validated and accepted by the anchor.
 * - PROCESSING: The field is being reviewed and has not yet been approved or rejected.
 * - REJECTED: The field was rejected and the error property explains why.
 * - VERIFICATION_REQUIRED: The field needs additional verification (e.g., confirmation code).
 *
 * @package Soneso\StellarSDK\SEP\KYCService
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#provided-fields SEP-12 v1.15.0
 */
class GetCustomerInfoProvidedField extends Response
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
     * @var string|null $status One of the values described here: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#field-statuses
     * If the server does not wish to expose which field(s) were accepted or rejected, this property will be omitted.
     */
    public ?string $status = null;

    /**
     * @var string|null $error The human-readable description of why the field is REJECTED.
     */
    public ?string $error = null;

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

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
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
        if (isset($json['status'])) $this->status = $json['status'];
        if (isset($json['error'])) $this->error = $json['error'];
    }

    public static function fromJson(array $json) : GetCustomerInfoProvidedField
    {
        $result = new GetCustomerInfoProvidedField();
        $result->loadFromJson($json);
        return $result;
    }
}