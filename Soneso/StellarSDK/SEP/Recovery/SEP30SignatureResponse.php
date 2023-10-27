<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

class SEP30SignatureResponse
{
    public string $signature;
    public string $networkPassphrase;

    /**
     * @param string $signature
     * @param string $networkPassphrase
     */
    public function __construct(string $signature, string $networkPassphrase)
    {
        $this->signature = $signature;
        $this->networkPassphrase = $networkPassphrase;
    }

    public static function fromJson(array $json) : SEP30SignatureResponse
    {
        return new SEP30SignatureResponse($json['signature'], $json['network_passphrase']);
    }

    /**
     * @return string
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * @param string $signature
     */
    public function setSignature(string $signature): void
    {
        $this->signature = $signature;
    }

    /**
     * @return string
     */
    public function getNetworkPassphrase(): string
    {
        return $this->networkPassphrase;
    }

    /**
     * @param string $networkPassphrase
     */
    public function setNetworkPassphrase(string $networkPassphrase): void
    {
        $this->networkPassphrase = $networkPassphrase;
    }

}