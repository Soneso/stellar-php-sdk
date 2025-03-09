<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Contract;

use Soneso\StellarSDK\Xdr\XdrSCVal;

class AssembledTransactionOptions
{
    public ClientOptions $clientOptions;
    public MethodOptions $methodOptions;

    /**
     * @var string $method name of the contract method to call
     */
    public string $method;

    /**
     * @var array<XdrSCVal>|null $arguments arguments to pass to the method call
     */
    public ?array $arguments = null;

    /**
     * @var bool $enableServerLogging enable soroban server logging (helpful for debugging). Default: false.
     */
    public bool $enableServerLogging = false;

    /**
     * @param ClientOptions $clientOptions client options.
     * @param MethodOptions $methodOptions method options.
     * @param string $method name of the contract method to call.
     * @param array<XdrSCVal>|null $arguments arguments to pass to the method call.
     * @param bool $enableServerLogging enable soroban server logging (helpful for debugging). Default: false.
     */
    public function __construct(ClientOptions $clientOptions, MethodOptions $methodOptions, string $method, ?array $arguments = null, bool $enableServerLogging = false)
    {
        $this->clientOptions = $clientOptions;
        $this->methodOptions = $methodOptions;
        $this->method = $method;
        $this->arguments = $arguments;
        $this->enableServerLogging = $enableServerLogging;
    }

}