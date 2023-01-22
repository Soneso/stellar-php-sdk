<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

class Network
{
    private string $networkPassphrase;

    public function __construct(string $networkPassphrase)
    {
        $this->networkPassphrase = $networkPassphrase;
    }

    /**
     * @return string
     */
    public function getNetworkPassphrase(): string
    {
        return $this->networkPassphrase;
    }

    public static function public() : Network {
        return new Network("Public Global Stellar Network ; September 2015");
    }

    public static function testnet() : Network {
        return new Network("Test SDF Network ; September 2015");
    }

    public static function futurenet() : Network {
        return new Network("Test SDF Future Network ; October 2022");
    }
}