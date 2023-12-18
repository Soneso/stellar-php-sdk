<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Requests;

class ResourceConfig
{
    public int $instructionLeeway; // number of add CPU instructions to reserve

    /**
     * @param int $instructionLeeway allows budget instruction leeway used in preflight calculations to be configured. number of add'l CPU instructions to reserve.
     */
    public function __construct(int $instructionLeeway)
    {
        $this->instructionLeeway = $instructionLeeway;
    }


    public function getRequestParams() : array {
        $params = array();
        $params['instructionLeeway'] = $this->instructionLeeway;
        return $params;
    }

    public function getInstructionLeeway(): int
    {
        return $this->instructionLeeway;
    }

    public function setInstructionLeeway(int $instructionLeeway): void
    {
        $this->instructionLeeway = $instructionLeeway;
    }



}