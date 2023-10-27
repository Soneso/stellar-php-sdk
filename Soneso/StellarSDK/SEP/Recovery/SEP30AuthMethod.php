<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

class SEP30AuthMethod
{
    public string $type;
    public string $value;

    /**
     * @param string $type
     * @param string $value
     */
    public function __construct(string $type, string $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    public function toJson() : array {
        return array(
            'type' => $this->type,
            'value' => $this->value
        );
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }


}