<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Soneso\StellarSDK\Responses\Response;

class AnchorField extends Response
{
    private string $description;
    private ?bool $optional = null;
    private ?array $choices = null;

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return bool|null
     */
    public function getOptional(): ?bool
    {
        return $this->optional;
    }

    /**
     * @return array|null
     */
    public function getChoices(): ?array
    {
        return $this->choices;
    } //[string]

    protected function loadFromJson(array $json) : void {
        if (isset($json['description'])) $this->description = $json['description'];
        if (isset($json['optional'])) $this->optional = $json['optional'];
        if (isset($json['choices'])) {
            $this->choices = array();
            foreach ($json['choices'] as $choice) {
                array_push($this->choices, $choice);
            }
        }
    }

    public static function fromJson(array $json) : AnchorField
    {
        $result = new AnchorField();
        $result->loadFromJson($json);
        return $result;
    }
}