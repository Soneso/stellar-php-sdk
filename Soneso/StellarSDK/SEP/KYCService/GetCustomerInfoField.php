<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\SEP\KYCService;

use Soneso\StellarSDK\Responses\Response;

/// The CustomerInfoField object defines the pieces of information the anchor has not yet received for the customer. It is required for the NEEDS_INFO status but may be included with any status.
/// Fields should be specified as an object with keys representing the SEP-9 field names required.
/// Customers in the ACCEPTED status should not have any required fields present in the object, since all required fields should have already been provided.
class GetCustomerInfoField extends Response
{
    /// The data type of the field value. Can be "string", "binary", "number", or "date".
    private string $type;
    /// A human-readable description of this field, especially important if this is not a SEP-9 field.
    private ?string $description = null;
    /// (optional) An array of valid values for this field.
    private ?array $choices = null;
    /// (optional) A boolean whether this field is required to proceed or not. Defaults to false.
    private bool $optional  = false;

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
     * @return array|null
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
                array_push($this->choices, $choice);
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