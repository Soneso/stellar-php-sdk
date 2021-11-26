<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Util;

class Hash
{
    /**
     * Returns the raw bytes of a sha-256 hash of $data
     *
     * @param string $data
     * @return string
     */
    public static function generate(string $data): string
    {
        return hash('sha256', $data, true);
    }

    /**
     * Returns a string representation of the sha-256 hash of $data
     *
     * @param string $data
     * @return string
     */
    public static function asString(string $data): string
    {
        return hash('sha256', $data, false);
    }
}