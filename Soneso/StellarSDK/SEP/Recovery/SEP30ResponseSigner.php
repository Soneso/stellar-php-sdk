<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

class SEP30ResponseSigner
{
    public string $key;

    /**
     * @param string $key
     */
    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public static function fromJson(array $json) : SEP30ResponseSigner
    {
        return new SEP30ResponseSigner($json['key']);
    }
}