<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\KYCService;

use Soneso\StellarSDK\Responses\Response;

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
        if (isset($json['error'])) $this->optional = $json['error'];
    }

    public static function fromJson(array $json) : GetCustomerInfoProvidedField
    {
        $result = new GetCustomerInfoProvidedField();
        $result->loadFromJson($json);
        return $result;
    }
}