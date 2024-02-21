<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;


class SEP38SellDeliveryMethod
{

    public string $name;
    public string $description;

    /**
     * @param string $name
     * @param string $description
     */
    public function __construct(string $name, string $description)
    {
        $this->name = $name;
        $this->description = $description;
    }

    public static function fromJson(array $json) : SEP38SellDeliveryMethod
    {
        return new SEP38SellDeliveryMethod($json['name'], $json['description']);
    }

}