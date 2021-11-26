<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

class BumpSequenceOperationResponse extends OperationResponse
{

    private string $bumpTo;

    protected function loadFromJson(array $json): void
    {

        if (isset($json['bump_to'])) $this->bumpTo = $json['bump_to'];

        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData): BumpSequenceOperationResponse
    {
        $result = new BumpSequenceOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}