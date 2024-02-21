<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

class SEP38FeeDetails
{
    public string $name;
    public string $amount;
    public ?string $description = null;

    /**
     * @param string $name
     * @param string $amount
     * @param string|null $description
     */
    public function __construct(string $name, string $amount, ?string $description = null)
    {
        $this->name = $name;
        $this->amount = $amount;
        $this->description = $description;
    }

    public static function fromJson(array $json) : SEP38FeeDetails
    {
        $description = null;
        if (isset($json['description'])) {
            $description = $json['description'];
        }
        return new SEP38FeeDetails($json['name'], $json['amount'], $description);
    }

}